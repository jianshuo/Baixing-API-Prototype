<?php
//wangjianshuo@baixing.com
namespace Graph;

class CompanyAccount {
	function occuredRevenue($starTime, $endTime) {
		$money = 0;
		
		if($endTime > time()) 
			throw new Exception("Refuse to tell you future, because that may be inaccurate");
		
		$s = new Searcher(
			new AndQuery(
				new Query('type', 'Order'),
				new Query('paid', 1),
				new RangeQuery('startTime', null, $endTime),
				new RangeQuery('endTime', $startTime - 1, null)
			)
		);
		
		foreach($s as $order)
			$money += ($order->occuredRevenue($endTime) - $order->occuredRevenue($startTime));
			
		return $money;
	}
}

class UserAccount {
	public $balance; // money + credit;
	public $ratio; // money / balance;
	
	function in($money, $credit){
		$this->ratio = ($this->balance * $this->ratio + $money) / ($this->balance + $money + $credit);
		$this->balance += $money + $credit;
		$this->save();
	}
	
	function out($mondit) { //mondit = money + credit
		$this->balance -= $mondit;
		$this->save();
	}
	
	function pay($order) {
		$order->pay($order->listPrice * $this->ratio);
		$this->out($order->listPrice);
	}
	
	function partialCancel($order, $time) {
		$order->particalCancel($time);
		
		$refund = $order->price - $order->occuredRevenue();
		$ratio = $order->price / $order->listPrice;
		$this->in($refund, $refund / $ratio * (1 - $ratio));
	}
}

class Order {
	public $unitTime = 24 * 60 * 60; // 1 day
	public $paid = false;
	
	function occuredRevenue($time) {
		$money = 0;
		$unitPrice = round(($this->price) / ($this->endTime - $this->startTime) * $this->unitTime, 2); 
		for($i = 0; $occuredTime = $this->startTime + $this->unitTime * $i, $occuredTime < min($time, $endTime); $i++)
			if($this->endTime - $this->occuredTime <= $this->unitTime)
				$money += ($this->price - $unitPrice * $i);
			else
				$money += $unitPrice;
		return $money;		
	}
	
	function place() {
		$pa = new BiddingPrice();
		$this->listPrice = $pa->price($order);
		$this->save();
	}
	
	function partialCancel($time) {
		if($time >= $this->endTime)
			throw new Exception('Cannot cancel completed order');
		
		$neg = clone $this;
		$neg->startTime = max($this->startTime, $time);
		$neg->price = $this->occuredRevenue($time) - $this->price;
		$neg->save();
	}
	
	function attributes() {
		$ad = graph($this->adId);
		
		$categories = array():
		$areas = array();
		
		foreach(array_reverse($ad->category->path()) as $c) {
			$categories[] = $c->id;
			if($c->id == $this->category->id)
				return;
		}
		
		foreach(array_reverse($ad->area->path()) as $a) {
			$areas[] = $a->id;
			if($a->id == $this->area->id)
				return;
		}
		
		return array_merge(parent::attributes(), compact('categoreis', 'areas'));
	}
}

class DingPrice extends BiddingPrice {
	public $capacity = 12;
	public $sensitivity = 0.5;
	public $bottom = 3;
	public $tolerance = 30; // day
	public $basePrice = 10; // RMB
}

class RefreshPrice extends BiddingPrice {
	public $capacity = 12;
	public $sensitivity = 0.5;
	public $bottom = 3;
	public $tolerance = 30; // day
	public $basePrice = 10; // RMB
}

class BiddingPrice {
	function price($order) {
		$s = new Searcher(
			new AndQuery(
				new Query('type', 'Order'),
				new Query('paid', 1),
				new Query('area', $order->area),
				new Query('category', $order->category)
			),
			array('order' => 'startTime DESC')
		);
		
		if(count($s) == 0)
			return $this->basePrice;
		
		$currentPrice = $s[0]->listPrice;
		
		if(count($s) > $this->capacity) {
			foreach(array_slice($s, 0 floor($this->sensitivity * $this->capacity) as $o)
				if($o->listPrice != $currentPrice)
					return $currentPrice;
			return $this->increase($currentPrice);
		}
		
		if(count($s) <= $this->bottom &&
			time() - $s[0]->startTime > $this->tolerance)
			return $this->decrease($currentPrice);
			
		return $currentPrice;
	}
	
	function increase($price) {
		return $price + 10;
	}
	
	function decrease($price) {
		return $price - 10;
	}
}

class DingAds {
	function ads() {
		$v = new Visitor();
		$s = new Searcher(
			new AndQuery(
				new Query('type', 'Order'),
				new Query('paid', 1),
				new RangQuery('startTime', null, time() + 1),
				new RangeQuery('endTime', time(), null),
				new Query('categories', $v->category),
				new InQuery('areas', $v->area->path())
				)
			)
		);
		
		$dingAds = array();
		$negAds = array();
		
		foreach($s as $o)
			if($o->price >= 0)
				$dingAds[] = $o->id;
			else
				$negAds[] = $o->id;
				
		return array_diff($dingAds, $negAds);
	}
}


/**
 * 1. AccuralBasedAccouting DONE
 * 2. Ding (Category, Filter, City, Province, China) DONE
 * 3. Place order, pay, refund, partialCancel DONE
 * 4. Pricing (Ding bidding based pricing, Refresh bidding, list bidding) DONE
 * 5. User Account, real money, fake money DONE
 * 6. Ding display DONE
 * 7) Refresh 5 RMB
 * 8) listing fee 10 RMB
 * 9) Sales tools
 * 10) Automatic invoice
 * 11) Port service
 *
 *    o-----o-----o-----o-----ø--
 *                  ^ time
 *        o-----o-----o-----o-----o----
 *  
 *											o-----o-----
 *
 *  |------|------|------|------|------|------|------|
 *        ^ startTime                ^ endTime
 *
 *  Order
 *  =====
 *  orderId
 *  starTime
 *  endTime
 *  listPrice
 *  price
 *  paid
 *  area
 *  category
 *  adId
 *
 *  1. Order never change after paid.
 *  2. Range [startTime, endTime)
 *  3. price, money refer to real money,
 *  4. listPrice, $credit refer to fake money.
 */
 
 
 















 