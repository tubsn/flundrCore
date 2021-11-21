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

	    $keys = array_keys($data);
	    $is_assoc = array_keys($keys) !== $keys;

		if ($is_assoc) {
			echo self::assoc_array_to_html_table_template($data);
		}

		else {
			echo self::array_to_html_table_template($data);
		}

	}

	private static function assoc_array_to_html_table_template($data) {

		$out = '<table class="fancy js-sortable">' . PHP_EOL;
		$out .= '	<thead>' . PHP_EOL;
		$out .= '		<tr>' . PHP_EOL;
		$out .= '			<th>Index</th>' . PHP_EOL;
		$out .= '			<th>Value</th>' . PHP_EOL;
		$out .= '		</tr>' . PHP_EOL;
		$out .= '	</thead>' . PHP_EOL;
		$out .= '	<tbody>' . PHP_EOL;

		foreach ($data as $key => $value) {
			$out .= '		<tr>' . PHP_EOL;
			$out .= '			<td class="narrow">' . $key . '</td>' . PHP_EOL;
			$out .= '			<td class="narrow">' . $value . '</td>' . PHP_EOL;
			$out .= '		</tr>' . PHP_EOL;
		}

		$out .= '	</tbody>' . PHP_EOL;
		$out .= '</table>' . PHP_EOL;

		return $out;

	}

	private static function array_to_html_table_template($data) {

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


	public function export_to_csv(array $data, $fileName = 'export.csv') {

		header( 'Content-Type: text/csv;charset=UTF-8' );
		header( 'Content-Disposition: attachment;filename=' . $fileName);

		echo "\xEF\xBB\xBF"; // UTF-8 BOM (Forces Excel to read the File with UTF-8)
		$output = fopen('php://output', 'w');

		$headerColumns = array_keys($data[array_key_first($data)]);

		// Excel can't have uppercase ID as the first Column
		if ($headerColumns[0] == 'ID') {
			unset($headerColumns[0]);
			array_unshift($headerColumns, 'id');
		}

		// First Line is the header
		fputcsv($output, $headerColumns, ';');

		foreach ($data as $line) {
			fputcsv($output, $line, ';');
		}

	}


}
