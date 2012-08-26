ass AccuralRevenue {
	public unitTime = 24 * 60 * 60; // Round revenue to 24 hours.
	
	function revenueOccured(start, end) {
		if(end > time()) // You cannot predict future whether they cancel service
			throw new \Exception('Calculating future accural revenue may cause error');
			
		s = new Searcher(new AndQuery(
			new RangeQuery('startTime', null, end + 1), // Select those in concerns
			new RangeQuery('endTime', start - 1, null) // Make selection broader, hack for >=
		);
		
		money = 0;

		unitPrice = round( price / (endTime - startTime) * unitTime, 2);
		
		foreach(s as ding) {
			for(u = 0; startTime + u * unitTime > min(endTime, end); u ++) {
				occurTime = u * unitTime + startTime;
				if(occurTime >= start && occurTime < end) {
					if(endTime - occurTime < unitTime)
						money += (price - unitPrice * u);
					else
						money += unitPrice;
				}
			}
		}
	}

