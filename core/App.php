<?php

namespace flundr\core;

use flundr\model\userDB;

class App {

	const CONTROLLER_NAMESPACE = '\\flundr\\controller\\';

	private $controller = self::CONTROLLER_NAMESPACE . 'Home';
	private $action = 'index';
	private $parameters = [];
	private $isProductionEnv = true;

	public function __construct() {

		if (DEBUG_MODE) {
			$this->isProductionEnv = false;
			error_reporting(E_ALL);
		}

		try {
			$this->identifyUser();
			$this->handle_URL_routing();
			$this->run_controller();
		}

		catch (\Exception $errorData) {
			new ErrorHandler($errorData);
		}

	}


	//Starts the Global Session and Checks if the User has an Login Cookie
	protected function identifyUser() {

		// Check if there is a UserDB setup
		if (!class_exists('\flundr\model\userDB')) {
			return;
		}

		Session::init();

		// Check if User is logged in
		if (Session::get('userLoggedIn') == false) {

			// Check if Logincookie exists or return
			if (!isset($_COOKIE[LOGINCOOKIE_NAME])) {return false;}

			// Try to login with Cookie
			$userDB = new userDB();
			$userDB->loginWithCookie();

			// Set Session Info from UserDB Model Data
			if ($userDB->userLoggedIn == true) {
				Session::set('userLoggedIn', true);
				Session::set('CSRFToken', cryptLib::generateKey(12,1)); // CSRF Token for this Session
				Session::set('userID', $userDB->userID);
				Session::set('userName', $userDB->userName);
				Session::set('userMail', $userDB->userMail);
				Session::set('userRights', $userDB->userRights);
				Session::set('userLevel', $userDB->userLevel);
			}

			unset($userDB);
		}
	}

	private function handle_URL_routing() {

		// Get Url without ?_GET Parameters
		$url = strtok($_SERVER["REQUEST_URI"],'?');

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
			if($this->isProductionEnv) {
				// In Production Environment use nice Errors
				throw new \Exception('Requested URL not Found',0);
			}
			throw new \Exception('Controller/Route (<mark>'.$this->controller.'</mark>) not found',2);
		}

		// Assign the Controller to the App
		$this->controller = new $this->controller;

		if (!is_callable([$this->controller,$this->action])) {
			if($this->isProductionEnv) {
				// In Production Environment use nice Errors
				throw new \Exception('Requested URL not Found',0);
			}
			throw new \Exception('Method/Action (<mark>'.$this->action.'</mark>) not found or is private',2);
		}

		// Call the Controller with Action and possible Params
		call_user_func_array([$this->controller,$this->action], $this->parameters);
	}
}