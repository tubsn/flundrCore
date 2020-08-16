<?php

namespace flundr\mvc;
use flundr\utility\Session;
use flundr\auth\Auth;

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
		$classPath = VIEW_NAMESPACE . $type;
		if (!class_exists($classPath)) {throw new \Exception('Requested View not found: <mark>'. $classPath .'</mark>', 1);}
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
		$classPath = MODEL_NAMESPACE . $name;
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

}