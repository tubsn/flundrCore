<?php

namespace flundr\core;

class App {

	const CONTROLLER_NAMESPACE = '\\flundr\\controller\\';

	private $controller = self::CONTROLLER_NAMESPACE . 'Home';
	private $action = 'index';
	private $parameters = [];

	public function __construct() {

		if (DEBUG_MODE) {
			error_reporting(E_ALL);
		}

		try {
			$this->handle_URL_routing();
			$this->run_controller();
		}

		catch (\Exception $e) {
			http_response_code(404);
			echo $e->getMessage();
		}

	}

	private function handle_URL_routing() {

		$url = $_SERVER['SCRIPT_URL'];

		if ($url === '/') {return;}

		// trim slashes to prevent empty fields in URL Array
		$url = trim ($url, '/');
		$url = explode('/',$url);

		// Redirect if default Controller is called redundantly
		// Bad for SEO if www.test.de = www.test.de/home
		if (strtolower($url[0]) == 'home') {
			header("HTTP/1.1 301 Moved Permanently");
			header("Location:/");
		}

		// First Parameter is the Controller Name
		$this->controller = self::CONTROLLER_NAMESPACE . ucfirst($url[0]);
		unset($url[0]);

		// Second Parameter is Action, if it's not a Number/ID
		if (isset($url[1]) && !ctype_digit($url[1])) {
			$this->action = $url[1];
			unset($url[1]);
		}

		// The rest of the URL is used as an Array of Parameters
		$this->parameters = !empty($url) ? array_values($url) : [];

	}

	private function run_controller() {

		if (!class_exists($this->controller)) {
			throw new \Exception('Controller not Found');
		}

		// Assign the Controller to the App
		$this->controller = new $this->controller;

		if (!is_callable([$this->controller,$this->action])) {
			throw new \Exception('Action: '.$this->action.' not found or private');
		}

		// Call the Controller with Action and possible Params
		call_user_func_array([$this->controller,$this->action], $this->parameters);

	}

}