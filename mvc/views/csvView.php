<?php

namespace flundr\mvc\views;
use \flundr\mvc\ViewInterface;

class csvView implements ViewInterface {

	public $title = 'export.csv'; // Filename for the Download

	public function render($templateName, $templateData = []) {

		$templatePath = TEMPLATES . $templateName . TEMPLATE_EXTENSION;

		if (!file_exists($templatePath)) {
			throw new \Exception("Template not found: <br/><mark>$templatePath</mark>", 500);
		}

		// Send CSV Header
		header( 'Content-Type: text/csv;charset=UTF-8' );
		header( 'Content-Disposition: attachment;filename=' . $this->title);

		// UTF-8 BOM (Forces Excel to read the File with UTF-8)
		echo "\xEF\xBB\xBF";

		extract($templateData, EXTR_SKIP);

		require $templatePath;

	}

	// Helper Function for HTML Redirects
	public function redirect($url, $code='301') {
		header("Location:" . $url, true, $code); exit;
	}

	// Json Export
	public function json($templateData) {
		header("Content-type: application/json; charset=utf-8");
		echo json_encode($templateData);
	}

}
