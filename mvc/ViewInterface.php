<?php

namespace flundr\mvc;

interface ViewInterface
{
	public function render($string, $array);
	public function redirect($string, $int);
}
