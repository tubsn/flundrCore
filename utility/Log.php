<?php

/**
 * Creates a Logfile
 */

namespace flundr\utility;

class Log
{
	public static function write($data) {
		$time = date("Y-m-d H:i:s");
		$logEntry = $time . " " . $data . "\n";
		if (!is_dir(LOGS)) {mkdir(LOGS);}
		file_put_contents(LOGS . 'mainlog.log', $logEntry, FILE_APPEND);
	}

	public static function error($data) {
		$time = date("Y-m-d H:i:s");
		$day = date("Y-m-d");
		$logEntry = $time . " " . $data . "\n";

		$directory = LOGS . 'error';
		if (!is_dir($directory)) {mkdir($directory);}
		$filename = $day.'.log';
		$path = $directory . DIRECTORY_SEPARATOR . $filename;

		file_put_contents($path, $logEntry, FILE_APPEND);
	}

}
