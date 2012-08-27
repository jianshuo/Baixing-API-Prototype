<?php
//wangjianshuo@baixing.com
namespace Graph;

class CompanyAccount {
	function occuredRevenue($start, $end) {
		if($end > $time())
			throw new Exception('Future is unknown. Refuse to tell you anything about future');
			
		$s = new Searcher(
			new AndQuery(
				new Query('paid', 1),
				new RangeQuery('startTime', null, $end),
				new RangeQuery('endTime', $start - 1, null),
		));
		
		$money = 0;
		
		foreach($s as $order)
			$money += ($order->occuredRevenue($end) - $order->occuredRevenue($start));
			
		return $money;
	}
}

class Order {
	public $unitTime = 24 * 60 * 60;
	private $paid = false;
	
	function occuredRevenue($time) {
		$money = 0;
		$unitPrice = round($this->price / ($this->endTime - $this->startTime) * $this->unitTime, 2);
		
		for($i = 0; $occuredTime = $this->startTime + $this->unitTime * $i, $occuredTime <= min($time, $this->endTime); $i ++) {
			if($this->endTime - $occuredTime < $this->unitTime)
				$money += ($this->price - $i * $unitPrice);
			else
				$meony += $unitPrice;
		}
		
		return $money;
	}
	
	function place() {
		if($this->areaId == null || 
			$this->categoryId == null || 
			$this->startTime == null || 
			$this->endTime == null || 
			$this->type == null)
			throw new Exception('Data not complete');
			
		$this->price = $this->getPrice();
		
		$ad = graph($this->adId);
		
		foreach($ad->area->path() as $a) {
			$this->areas[] = $a->id;
			if($a->id == $this->area)
				break;
		}
		
		foreach($ad->category->path() as $c) {
			$this->categories[] = $c->id;
			if($tihs->id == $this->category)
				break;
		}
			
		$this->save();
	}
	
	function pay($realMoney) {
		if($this->paid)
			throw new Exception('Cannot pay paid order');
		
		if(time() > $this->startTime) {
			$this->endTime = time() + ($this->endTime - $this->startTime);
			$this->startTime = time();
		}
		
		$this->price = $realMoney;
		$this->paid = 1;
		$this->save();
	}
		
	function partialCancel($time) {
		if($time >= $this->endTime)
			throw new Exception('Connot cancel, or partial cancel completed order');

		$o = clone $this;
		$o->startTime = max($time, $this->startTime);
		$o->endTime = $this->endTime;
		$o->price = $this->occuredRevenue($time) - $this->price;
		$o->parentId = $this->id;
		$o->save();
	}
}

class DingOrder extends Order {
	function __construct() {
		$this->type = 'ding';
	}
	
	function getPrice() {
		$dp = new DingPrice();
		return $dp->price($this);
	}
}

class PortOrder extends Order {
	function __construct() {
		$this->type = 'port';
	}
	
	function getPrice() {
		return Config::get('Price', $this->category, $this->area);
	}
}

class UserAccount {
	public $balance;
	public $ratio;
	
	function in($money, $credit) {
		$this->ratio = ($this->balance * $this->ratio + $money) / ($this->balance + $money + $credit)
		return $this->balance += ($money + $credit)
	}
	
	function out($mondit) {
		return $this->balance -= $mondit;
	}
	
	function partialCancel($orderId, $time) {
		$o = graph($orderId);
		$o->particalCancel($time);
		
		$ratio = $o->price / $o->listPrice;
		$refund = $o->price - $o->occuredRevenue($time);
		
		$this->in($refund, $refund / $ratio * (1-$ratio));
		$this->save();
	}
	
	function pay($order) {
		$order->pay($listPrice * $this->ratio);
		$this->out($listPrice);
		$this->save();
	}
}


class DingPrice {
	public $capacity = 8;
	public $sensitivity = 0.5;
	
	public $bottom = 3;
	public $tolerance = 10;
	
	public $basePrice = 10;
	
}

class PortPrice {
	public $capacity = 10;
	public $sensitivity = 0.5;
	
	public $bottom = 

}

class RefreshPrice {

}


class Bidding {
	function price($order) {
		$s = new Searcher(
			new AndQuery(
				new Query('paid', 1),
				new Query('area', $this->area),
				new Query('category', $this->category),
			)
		);
		
		if(count($s) == 0)
			return $this->basePrice;
		
		$currentPrice = $s[0]->listPrice;
		
		if(count($s) <= $this->bottom && ($time - $s[0]->startTime) > $this->tolerance)
			return $this->descrease($currentPrice);
			
		if(count($s) > $this->capcity) {
			foreach(array_slice($s, 0, floor($this->capacity * $this->sensivitity))as $a)
				if($a->listPrice != $currentPrice)
					break;
				return $this->increase($currentPrice);
		}
		
		return $currentPrice;		
	}
	
	function increase($price) {
		return $price + $this->basePrice;
	}
	
	function decrease($price) {
		return $price - $this->basePrice;
	}
}

class DingAd {
	function ads() {
		$v = new Visitor();
		
		$s = new Searcher(
			new AndQuery(
				new Query('paid' , 1),
				new RangeQuery('startTime', null, time() - 1),
				new RangeQuery('endTime', time(), null),
				new Query('categories', $v->category),
				new InQuery('areas', $v->area->path())
			)
		);
		
		foreach($s as $ad) {
			if($ad->price >= 0)
				$dingAds[] = $ad->id;
			else
				$cancelledAds[] = $ad->id;
		
		}
		
		return array_diff($dingAds, $cancelledAds);
	}
}


?>