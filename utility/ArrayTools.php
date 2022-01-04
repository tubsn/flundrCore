<?php

namespace flundr\utility;

class ArrayTools
{

	public static function group_by($key, array $data) {

		$result = [];

		foreach($data as $value) {
			if (array_key_exists($key, $value)) {
			    $result[$value[$key]][] = $value;
			}
			else {
			    $result[""][] = $value;
			}
		}

		return $result;
	}

	public static function sum_grouped_by($keyToSum, $keyToGroup, array $data) {

		$result = [];

		foreach($data as $value) {
			if (array_key_exists($keyToGroup, $value)) {
				if (!isset($result[$value[$keyToGroup]])) {
					$result[$value[$keyToGroup]] = $value[$keyToSum];
				}
			    $result[$value[$keyToGroup]] = $result[$value[$keyToGroup]] + $value[$keyToSum];
			}
			else {
				if (!isset($result[''])) {
					$result[''] = $value[$keyToSum];
				}
			    $result[''] = $result[$value[$keyToGroup]] + $value[$keyToSum];
			}
		}

		return $result;
	}

}
