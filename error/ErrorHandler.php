<?php

namespace flundr\error;
use \flundr\utility\Log;

class ErrorHandler
{

	private $code;
	private $message;
	private $trace;
	private $line;
	private $file;

	public function __construct($errorData) {

		$this->load_error_data($errorData);
		$this->log_error_to_file();

		$errorHandlerClass = CONTROLLER_NAMESPACE.'Error';
		if(class_exists($errorHandlerClass)) {
			new $errorHandlerClass($errorData);
			die;
		}

		$this->show_error_to_user($errorData);
	}

	private function load_error_data($errorData) {
		$this->code = $errorData->getCode();
		$this->message = $errorData->getMessage();
		$this->trace = $errorData->getTraceAsString();
		$this->line = $errorData->getLine();
		$this->file = $errorData->getFile();
	}

	private function log_error_to_file() {
		$logString = $this->message . ' | ErrorCode: ' . $this->code;
		Log::error($logString);
	}

	private function show_error_to_user() {

		$showtrace = true;
		if (ENV_PRODUCTION) {$showtrace = false;}

		header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');

		echo '<!DOCTYPE html>
		<html lang="de">
		<head>
			<title> Flundr Error: '.$this->message.' </title>
			<meta charset="utf-8">
		</head>
		<body style="margin:5% auto; max-width:90%; font-family:Droid Sans, Consolas, sans-serif; font-size:1.4em; line-height:110%; background:#2b303e; color:#f6f6f6;">

		<h1>Flundr Error</h1>
		<hr />
		<h3>'. $this->message .' (Errorcode: '.$this->code.')</h3>';
		if ($showtrace) {
			echo '<p>Line <b>'. $this->line .'</b> in '. $this->file .'</p>';
			echo '<pre>' .$this->trace. '</pre>';
		}
		echo '<hr />
		</body>
		</html>';

		die;

	}

}