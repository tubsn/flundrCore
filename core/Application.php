<?php

namespace flundr\core;

use flundr\error\ErrorHandler;
use flundr\routing\Router;
use flundr\auth\LoginHandler;
use flundr\auth\Auth;
use flundr\utility\Session;
use flundr\security\CryptLib;

class Application {

	private $controller = CONTROLLER_NAMESPACE . 'Home';
	private $action = 'index';
	private $parameters = [];

	public function __construct() {

		try {
			Session::init();
			$this->handle_URL_routing();
			$this->identify_user();
			$this->run_controller();
		}

		catch (\Exception $errorData) {
			new ErrorHandler($errorData);
		}

	}

	private function handle_URL_routing() {
		$router = new Router($_SERVER['REQUEST_METHOD'], $_SERVER["REQUEST_URI"]);
		$this->controller = CONTROLLER_NAMESPACE . $router->controller;
		$this->action = $router->action;
		$this->parameters = $router->parameters;
	}

	protected function identify_user() {
		if (!Session::get('CSRFToken')) {
			Session::set('CSRFToken', CryptLib::generate_key(12,1));
		}

		// load possible Session User into the Auth helper
		Auth::checkin(Session::get('authUser'));
		if (Auth::logged_in()) {return;}

		if (defined('LOGINCOOKIE_NAME')) {$cookieName = LOGINCOOKIE_NAME;}
		else {$cookieName = 'auth';}

		if (isset($_COOKIE[$cookieName])) {
			$loginHandler = new LoginHandler();
			$loginHandler->login_by_cookie();
			unset($loginHandler);
		}

	}

	private function run_controller() {

		if (!class_exists($this->controller)) {
			throw new \Exception('Controller (<mark>'.$this->controller.'</mark>) not found',404);
		}

		// Replace the Controller Namestring with a Controller Instance
		$this->controller = new $this->controller;

		if (!is_callable([$this->controller,$this->action])) {
			throw new \Exception('Action/Method (<mark>'.$this->action.'</mark>) not found or is private',404);
		}

		return call_user_func_array([$this->controller,$this->action], $this->parameters);
	}
}
