<?php

namespace flundr\utility;

class Breadcrumbs {

	public $breadcrumbs = [];

	public function add($name, $url = null) {
		$name = $this->normalize_input($name);
		array_push($this->breadcrumbs, ['text' => $name, 'url' => $url]);
	}

	private function normalize_input($name) {
		$parts = explode('\\', $name);
		return array_pop($parts);
	}

	public function breadcrumbs() {
		return $this->breadcrumbs;
	}

	public function generate() {
		return $this->breadcrumbs;
	}

}