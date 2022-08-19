<?php

namespace flundr\date;

class Datepicker
{

	public $pickerFormat = 'M Y';
	public $intervalFormat = 'P1M';
	public $includeCurrentDate = true;
	public $decending = true;

	public function months($startDate = null, $endDate = null) {
		if ($this->includeCurrentDate) {$this->intervalFormat = 'P1D';}
		return $this->array_with_dates($startDate, $endDate);
	}

	private function array_with_dates($startDate = null, $endDate = null) {

		if (!$startDate) {$startDate = date("Y-m-d", strtotime('last year'));}

		$start = $this->create_date_object($startDate);
		$end = $this->create_date_object($endDate);

		$interval = new \DateInterval($this->intervalFormat);
		$period = new \DatePeriod($start, $interval, $end);

		$monthList = [];
		foreach ($period as $date) {
			$index = $date->format($this->pickerFormat);
			$monthList[$index]['start'] = $date->format('Y-m') . '-01';
			$monthList[$index]['end'] = $date->format('Y-m-t');
		}

		if ($this->decending) {return array_reverse($monthList);}
		return $monthList;

	}

	private function create_date_object($dateString = null) {
		if (is_null($dateString)) {return new \DateTime(date('Y-m-d'));}
		return new \DateTime(date('Y-m-d', strtotime($dateString)));
	}

}
