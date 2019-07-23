<?php

namespace flundr\core;

abstract class Controller {

	protected $view;
	protected $models;

	// Get Model data if possible
	public function __get($name) {

		if (isset($this->models[$name])) {
			try {

				if (!is_object($this->models[$name])) {
					$this->init_model($this->models[$name], $name);
				}

				return $this->models[$name];
			}

			catch (\Exception $e) {
				echo $e->getMessage();
			}
		}
	}

	// Bind a View to the Controller
	public function view($type = 'HTML') {
		$classPath = '\\flundr\\view\\' . $type;
		$this->view = new $classPath;

		return $this->view;
	}

	// Bind one or more Models to the Controller
	public function models($models) {
	$models = explode(',', $models);

		foreach ($models as $model) {
			$this->register_model($model);
		}

	}

	// register Model without initialisation
	public function register_model($name) {
		$classPath = '\\flundr\\model\\' . $name;
		$this->models[$name] = $classPath;
	}

	// Initialisation of the Model
	protected function init_model($classPath, $name) {

		if (!class_exists($classPath)) {
			throw new \Exception(
				'<pre><mark>Model "' . $name . '" not found or not declared!</mark></pre>'
			);
			return;
		}

		$this->models[$name] = new $classPath;
	}

	// Helper for User Access
	protected function checkUserAccess() {

		// Ok if user is Logged in
		if (Session::get('userLoggedIn') == true) {
			return true;
		}

		// Ok if User is from Intranet
		if (in_array($_SERVER['REMOTE_ADDR'], ALLOWEDIPS)) {
			return true;
		}

		// set Referer for Redirects
		Session::set('referer', $_SERVER['REQUEST_URI']);

		// Display Login Page
		header('Location: /login/'); die;

	}

	// Helper for Date stuff
	public function formatDate($date, $format='Y-m-d') {
		if (is_null($date)) {
			return null;
		}
		$date = new \DateTime($date);
		return $date->format($format);
	}

}