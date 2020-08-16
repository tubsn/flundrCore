<?php

namespace flundr\mvc\views;

use \flundr\mvc\ViewInterface;

class jsonView implements ViewInterface {


	public function __construct() {

		// Always throw Errors in JSON
		error_reporting(0);
		register_shutdown_function(array($this, 'errors_to_json'));

	}


	public function render($bodyTemplate, array $templateData = []) {

		header("Content-type: application/json; charset=utf-8");
		$this->data = $templateData;
		$this->process_templates([$bodyTemplate]);

	}

	public function errors_to_json() {

		http_response_code(404);
		$error = error_get_last();

		// Show only Fatal Errors
		if ($error && $error['type'] <= 4) {
			echo json_encode(['Error' => $error['message'] . ' in Line: ' . $error['line'] ]);
			//echo json_encode($error);
		}

	}

	public function json($templateData) {

		header("Content-type: application/json; charset=utf-8");
		echo json_encode($templateData);

	}

}