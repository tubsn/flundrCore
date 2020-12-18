<?php

namespace flundr\routing;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;

class Router {

	public $controller;
	public $action;
	public $parameters;

	public function __construct($method, $uri) {
		$this->lookup_routes($method, $uri);
	}

	public function lookup_routes($method, $uri) {

		$method = $this->cleanup_method($method);
		$uri = $this->cleanup_uri($uri);

		// Creates a FastRoute Dispatcher https://github.com/nikic/FastRoute
		$dispatcher = $this->load_configured_routes();
		$routeInfo = $dispatcher->dispatch($method, $uri);

		// Route Output
		switch ($routeInfo[0]) {
		    case Dispatcher::NOT_FOUND:
				throw new \Exception('URL Not Found', 404);
		        break;
		    case Dispatcher::METHOD_NOT_ALLOWED:
		        $allowedMethods = $routeInfo[1]; // Fastroute Allowed Methods
				throw new \Exception('Error Request Method not allowed.<br/>Allowed Methods: '. implode(' | ', $allowedMethods), 405);
		        break;
		    case Dispatcher::FOUND:
			 	// Fastroute 1 = Handler, 2 = Paramters
		        $this->resolve_route($routeInfo[1],$routeInfo[2]);
		        break;
			default:
				throw new \Exception('General Route Error');
		}

	}

	private function load_configured_routes() {
		return \FastRoute\simpleDispatcher(function(RouteCollector $routes) {
			require_once ROUTEFILE; // Externaly Configured Routefile
		});
	}

	private function resolve_route($handler,$parameters) {

		if (strpos($handler,'@')) {
			$this->controller = explode('@',$handler)[0];
			$this->action = explode('@',$handler)[1];
		} else {
			$this->controller = $handler;
			$this->action = 'index';
		}

		$this->parameters = $parameters;

	}


	private function cleanup_uri($URIstring) {
		$URIstring = strtok($URIstring,'?'); // Remove ?_ Get Paramaters
		$URIstring = rawurldecode($URIstring); // Cleans HTML Entities
		return $URIstring;
	}

	private function cleanup_method($methodString) {
		return $methodString; // May need Sanitizing?
	}

}
