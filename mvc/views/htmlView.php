<?php

namespace flundr\mvc\views;

use \flundr\rendering\TemplateEngine;
use \flundr\utility\Session;
use \flundr\mvc\ViewInterface;

abstract class htmlView implements ViewInterface {

	public $title = null;
	public $description = null;
	public $css = null;
	public $fonts = null;
	public $meta = null;
	public $js = null;
	public $framework = null;
	public $templates = [];
	public $templateVars = [];

	public function render($mainTemplate = null, $controllerData = []) {

		// Assign the Template picked in the Controller as the "main" Template
		if (!is_null($mainTemplate)) {$this->templates['main'] = $mainTemplate;}

		if (isset($controllerData['page'])) {
			// $page is reserved, cause it is overwritten later
			throw new \Exception('The Data you passed to the View contains the reserved
			Varname <mark>"page"</mark>. Please rename your Variable.', 403);
		}

		$templateData = $this->combine_data_sources($this->templateVars, $controllerData);

		$templateEngine = new TemplateEngine($this->templates, $templateData);
		$templateEngine->render();

	}

	// Allows User to directly inject Data to the Views $templateVars
	public function __set($index, $value) {
		$this->templateVars[$index] = $value;
		/* Validation?
		if (isset($this->templateVars[$index])) {
			$this->templateVars[$index] = $value;
		} else {throw new \Exception('Template Variable "'.$index.'" not Registered in '. get_class(), 403);}
		*/
	}

	public function __get($name) {
		return $this->templateVars[$name];
	}

	// Helper Function for HTML Redirects
	public function redirect($url, $code='301') {
		header("Location:" . $url, true, $code); exit;
	}


	private function combine_data_sources($templateVars, $controllerData) {

		// Attention: Data passed to the View overwrites the Templates defaults
		$dataPackage = array_merge($templateVars, $controllerData);

		// Meta Data is gathered in a "page" Variable
		$page['title'] = $this->title;
		$page['description'] = $this->description;
		$page['css'] = $this->to_array($this->css);
		$page['fonts'] = $this->fonts;
		$page['meta'] = $this->meta;
		$page['js'] = $this->to_array($this->js);
		$page['framework'] = $this->to_array($this->framework);

		$dataPackage['page'] = $page;

		// Important Template Globals from the Session
		$dataPackage['CSRFToken'] = Session::get('CSRFToken');
		$dataPackage['referer'] = Session::get('referer');

		return $dataPackage;

	}

	private function to_array($data) {
		if (is_array($data)) {return $data;}
		return [$data];
	}


}

