<?php

namespace flundr\mvc\views;

use \flundr\mvc\ViewInterface;

class xmlView implements ViewInterface {

	public $tinyHead = 'defaults/xml-doc-header';
	public $body = null;

	public function render($bodyTemplate, array $templateData = []) {

		header ("Content-type: text/xml; charset=UTF-8");
		$head = '<?xml version="1.0" encoding="UTF-8"?>';

		$this->body = $bodyTemplate;
		$this->data = $templateData;

		$layout = [
			$this->tinyHead,
			$this->body,
		];

		$this->process_templates($layout);

	}

}