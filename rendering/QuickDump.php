<?php

namespace flundr\rendering;

class QuickDump {


	public static function dump_and_die($var) {

		echo '<!DOCTYPE html>
		<html lang="de">
		<head>
			<title> Flundr Debug Info</title>
			<meta charset="utf-8">
		</head>
		<body style="margin:5% auto; max-width:90%; font-family:Droid Sans, Consolas, sans-serif; font-size:1.4em; line-height:110%; background:#2b303e; color:#f6f6f6;">

		<h1>Flundr Debug Info</h1>
		<hr />
		<pre>';

		print_r($var);

		echo '</pre>
		<hr /><small>Processing Time: ';
		echo (microtime(true)-APP_START)*1000;
		echo' ms</small></body>
		</html>';

		die;

	}

	public static function dump($var) {
		echo '<pre>';
		print_r($var);
		echo '</pre>';
	}

	public static function dump_table($data) {
		echo self::table_template($data);
	}

	private static function table_template($data) {

		$out = '<table class="fancy js-sortable">' . PHP_EOL;
		$out .= '	<thead>' . PHP_EOL;
		$out .= '		<tr>' . PHP_EOL;

		foreach (array_keys($data[0]) as $fieldname) {
			$out .= '			<th>' . $fieldname . '</th>' . PHP_EOL;
		}

		$out .= '		</tr>' . PHP_EOL;
		$out .= '	</thead>' . PHP_EOL;
		$out .= '	<tbody>' . PHP_EOL;

		foreach ($data as $item) {
			$out .= '		<tr>' . PHP_EOL;

			foreach ($item as $value) {
				$out .= '			<td class="narrow">' . $value . '</td>' . PHP_EOL;
			}

			$out .= '		</tr>' . PHP_EOL;
		}

		$out .= '	</tbody>' . PHP_EOL;
		$out .= '</table>' . PHP_EOL;

		return $out;

	}

}
