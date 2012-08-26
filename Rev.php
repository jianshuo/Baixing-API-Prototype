<?php
namespace Graph;

/**
*     o-----------o-----------o-----------o-----------o-----------o-----------o-----------
*
*          o-----------o-----------o-----------ø----
*
*                                                   o------
*                                                                      o-----------o-----------
*
* |___________|___________|___________|___________|___________|___________|___________
*                                ^               ^
*                              start            end
*
*  上图的o代表在那一个时刻付钱。ø表示那一个时间段的钱比其他时间段少，因为时间不够一个时间单位
*  设计理念：
*     1. 所有时间的开始点为闭区间，结束点为开区间
*     2. 订单一旦付钱，则不允许做任何改动。改动用开新订单，和负订单方式完成
*     3. 如果遇到小树需要进位，选最终对客户有利的方式
*/


class CompanyAccount {	
	function occuredRevenue($start, $end) {
		if($end > time()) // You cannot predict future whether they cancel service
			throw new \Exception('Calculating future accural revenue may cause error');
			
		$s = new Searcher(new AndQuery(
			new Query('paid', 1),
			new RangeQuery('startTime', null, $end), // Select those in concerns
			new RangeQuery('endTime', $start - 1, null) // Make selection broader, hack for >=
		);
		
		$money = 0;

		foreach($s as $ding) {
			$money += ($ding->occuredRevenue($end) - $ding->occuredRevenue($start));
		}
		
		return $money;
	}
}

class Order {
	public $unitTime = 24 * 60 * 60; // Round revenue to 24 hours.
	private $paid = false;

	function occuredRevenue($time) {
		$money = 0;
		$unitPrice = round($this->price / ($this->endTime - $this->startTime) * $this->unitTime, 2);
		
		for($u = 0; $this->startTime + $u * $unitTime < min($this->endTime, $time); $u ++) {
			$occurTime = $u * $this->unitTime + $this->startTime;
			if($occurTime <= $time) //等于非常重要，表示就在那一个点付钱
				if($this->endTime - $occurTime < $this->unitTime)
					$money += ($this->price - $unitPrice * $u);
				else
					$money += $unitPrice;
		}
		return $money
	}

	function negativeOrder($time) {
		if($time >= $this->endTime)
			throw new Exception("Order completed or not started. You cannot change it");
	
		$nd = new Ding();
		$nd->parentId = $this->id;
		$nd->startTime = max($time, $this->startTime);
		$nd->endTime = $this->endTime;
		$nd->price = -1 * ($this->price - $this->occuredRevenue($occurTime));
		$nd->save();
	}
	
	function pay($realMoney) {
		if($this->paid)
			throw new Exception("You paid twice for the same order");

		if(time() > $this->startTime) { //如果错过了开始时间，自动推迟以免用户受损失
			$this->endTime = time() + ($this->endTime - $this->startTime)
			$this->startTime = time();
		}

		$this->paidPrice = $realMoney;
		
		$this->paid = 1; //after paid, the order cannot be modified
		$this->save();
	}
	
	function place() {
		$this->price = $this->price(new DingPricing());
		$this->save();
	}
	
	function price($algorithm) {
		return $algorithm->price($this);
	}
}

class DingPricing {
	public $sensitivty = 0.5;
	public $capacity = 8;
	public $bottom = 3;
	public $tolerance = 30 * 60 * 60;
	
	function price($order) {
		$os = new Searcher(
			new AndQuery(
				new Query('target', $order->target),
				new Query('paid', 1),	
				new RangeQuery('price', 0, null),
			),
			array('order' => 'startTime DESC')
		);
		
		if(count($os) == 0)
			return $this->basePrice;
		
		$currentPrice = $os[0]->price;
		if(count($os) > $this->capacity) {
			foreach(array_slice($os, 0, floor($this->capacity * $this->sensitivity) as $o) {
				if($o->price != $currentPrice)
					break;
			}
			return $this->increasePrice($currentPrice);
		}
		
		if(count($os) <= $this->bottom && $os[0]->startTime - time() > $this->tolerance) {
			return $this->decreasePrice($currentPrice);
		}
	}
}

/**
 *   PRE-MONEY:
 *    realMoney = balance * ratio;
 *    usableMoney = balance;
 *
 *   TRANSACTION:
 *    realMoney = money;
 *    usableMoney = credit;
 *
 *   POST-MONEY
 *    realMoney = balance * ratio + money;
 *    usableMoney = balance + credit + money
 *    ratio = realMoney / usableMoney = (balance * ratio + money) / (balance + credit + money)
 *
 */

class UserAccount {
	private $balance;
	private $ratio;
	
	function in($money, $credit) {
		$this->ratio = round(($this->balance * $this->ratio + $money) / ($this->balance + $credit + $money), 4);
		$this->balance += round($money + $credit, 2);
		return $this->balance;
	}
	
	function out($mondit) { //mondit = money + credit
		$this->balance -= round($mondit, 2);
		return $this->balance;
	}
	
	function balance() {
		return $this->balance;
	}
	
	function ratio() {
		return $this->ratio;
	}
}

function chaoge_transaction() {
	$args = func_get_args();
	
	chaoge_transaction_begin();
	
	try{
		call_user_func(array(shift($args), shift($args)), $args);
		chaoge_transaction_commit();
	} catch ($e) {
		chaoge_transaction_rollback();
		throw $e;
	}
}


class Purchase {
	function partialCancel($orderId, $time) {
		$o = graph($orderId);
		$o->negativeOrder($time);
		$ua = $o->get('user_account');
		$ratio = $o->paidPrice / $o->price;
		$ua->in($o->paidPrice, $o->paidPrice / $ratio * (1- $ratio));
		
		$o->save();
		$ua->save();
	}
	
	function renew($orderId, $days) {
		if(time() > $this->endTime)
			throw new Exception("Create a new order. Don't renew.");
			
		$o = graph($orderId);
		
		$nd = new Ding();
		$nd->startTime = $o->endTime;
		$nd->endTime = $o->startTime + $days;
		$nd->save();
	}
	
	function buy() {
		
	}
	
	function refund() {
	
	}
}

$d = new Ding();
$d->startTime = time();
$d->endTime = time() + 30 * 60 * 60;
$d->placeOrder();

//Change Category for $adId;
$order = shift(graph($adId)->active_order());
$order->partialCancel(time());
$o = new Ding();
$o->startTime = time();
$o->endTime = time() + $days * 24 * 60 * 60;
$o->place();

//买10天送2天
$order = new Order();
$order->startTime = time();
$order->endTime = time() + (10 + 2) * 24 * 60 * 60;
$oder->price = 100;
$order->place();

//部分停止
chaoge_transaction('Purchase', 'partialCancel', $orderId, time());

?>