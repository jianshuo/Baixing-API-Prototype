<?php
namespace Graph;

class AccuralAccout {	
	function occuredRevenue($start, $end) {
		if($end > time()) // You cannot predict future whether they cancel service
			throw new \Exception('Calculating future accural revenue may cause error');
			
		$s = new Searcher(new AndQuery(
			new RangeQuery('startTime', null, $end + 1), // Select those in concerns
			new RangeQuery('endTime', $start - 1, null) // Make selection broader, hack for >=
		);
		
		$money = 0;

		foreach($s as $ding) {
			$money += ($ding->occuredRevenue($end) - $ding->occuredRevenue($start));
		}
		
		return $money;
	}
}

class Ding {
	public $unitTime = 24 * 60 * 60; // Round revenue to 24 hours.

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
		$nd->startTime = max($time, $this->startTime);
		$nd->endTime = $this->endTime;
		$nd->price = -1 * ($this->price - $this->occuredRevenue($occurTime));
		$nd->save();
}


/**
*     o-----------o-----------o-----------o-----------o-----------o-----------o-----------
*
*          o-----------o-----------o-----------ø----
*
*                                                                      o-----------o-----------
*
* |___________|___________|___________|___________|___________|___________|___________
*                                               ^
*                                              time
*
*/


?>