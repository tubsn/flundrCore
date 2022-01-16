<?php

/* Inspired by Mattias Geniar - https://github.com/mattiasgeniar/php-percentages */

namespace flundr\utility;

class PercentCalculator
{

	static $undefinedOutput = null;

	public static function percentage(float $a, float $b, int $decimals = 2) {
		if ($b == 0) {return self::$undefinedOutput;}
		$result = ($a * 100 / $b);
		return round($result, $decimals);
	}

	public static function difference(float $a, float $b, int $decimals = 2) {
		if ($a == 0) {return self::$undefinedOutput;}
		$result = ($b - $a) / $a * 100;
		return round($result, $decimals);
	}

	public static function of(float $percent, float $value, int $decimals = 2) {
		$result = $value * ($percent / 100);
		return round($result, $decimals);
	}

	public static function extension(float $percent, float $a, float $b, int $decimals = 2) {
		$movement = abs($a - $b);
        if ($a > $b) { $result = $a - ($movement * $percent / 100); }
		else { $result = $a + ($movement * $percent / 100); }
		return round($result, $decimals);
	}

}
