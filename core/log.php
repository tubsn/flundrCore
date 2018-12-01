<?php

/**
 * Creates a Logfile
 */

namespace flundr\core;

class log
{
	public static function write($data) {
		if (DEBUG_MODE == false) {return false;}
		$time = date("Y-m-d H:i:s");
		$logEntry = $time . " " . $data . "\n";
		file_put_contents("../logs/log.txt", $logEntry, FILE_APPEND);
	}
}
?>