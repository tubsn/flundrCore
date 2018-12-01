<?php

namespace flundr\core;

class ErrorHandler
{

	public function __construct($errorData) {

		if(class_exists('\flundr\controller\Error')) {
			new \flundr\controller\Error($errorData);
			die;
		}

		$code = $errorData->getCode();
		$message = $errorData->getMessage();
		$trace = $errorData->getTraceAsString();
		$line = $errorData->getLine();
		$file = $errorData->getFile();

		header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');

		echo '<!DOCTYPE html>
		<html lang="de">
		<head>
			<title> Flundr Error: '.$message.' </title>
			<meta charset="utf-8">
		</head>
		<body style="margin:5% auto; max-width:90%; font-family:Droid Sans, Consolas, sans-serif; font-size:1.4em; line-height:110%; background:#2b303e; color:#f6f6f6;">

		<h1>Flundr Error</h1>
		<hr />
		<h3>'. $message .' (Errorcode: '.$code.')</h3>
		<p>Line <b>'. $line .'</b> in '. $file .'</p>
		<pre>' .$trace. '</pre>
		<hr />
		</body>
		</html>';

		die;

	}

}