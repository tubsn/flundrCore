<?php
// Shorthand for Die and Dump with a styled CSS Layout
function dd($var) {

	echo '<!DOCTYPE html>
	<html lang="de">
	<head>
		<title> Flundr Debug Info</title>
		<meta charset="utf-8">
	</head>
	<body style="margin:5% auto; max-width:90%; font-family:Droid Sans, Consolas, sans-serif; font-size:1.4em; line-height:110%; background:#2b303e; color:#f6f6f6;">

	<h1>Flundr Debug Info</h1>
	<hr />
	<pre>';

	print_r($var);

	echo '</pre>
	<hr /><small>Processing Time: ';
	echo (microtime(true)-APP_START)*1000;
	echo' ms</small></body>
	</html>';

	die;
}

// Shorthand for Echoing Arrays and stuff
function dump($var) {
	echo '<pre>';
	print_r($var);
	echo '</pre>';
}

// Shorthand for Including Templates
function tpl($templateName) {
	return TEMPLATES.$templateName.TEMPLATE_EXTENSION;
}

// Create URL Slugs
function slugify($urlString) {

	$urlString = preg_replace('~[^\p{L}\d]+~u', '-', $urlString);
	$urlString = strtolower($urlString);

	$umlaute = [
		'ä'=>'ae', 'ö'=>'oe', 'ü'=>'ue',
		'ß'=>'ss', 'æ'=>'ae', 'ø'=>'oe',
		'å'=>'aa', 'é'=>'e', 'è'=>'e',
	];

	$urlstring = str_replace(array_keys($umlaute), array_values($umlaute), $urlString);
	return trim($urlstring, '-');

}

// Shorthand for Date Transformations
function formatDate($date, $format='Y-m-d') {
	if (is_null($date)) {
		return null;
	}
	$date = new \DateTime($date);
	return $date->format($format);
}

function explode_and_trim($delimiter, $string) {
	return array_map('trim', explode($delimiter, $string));
}

function remove_from_list($id, $list) {
	$elements = explode_and_trim(',' , $list);
	$key = array_search($id, $elements);
	unset($elements[$key]);
	return implode(',', $elements);
}

// Shorthand for wanted HTTP Errors
function abort($code = '403', $message = null) {
	echo $message;
	http_response_code($code);
	exit;
}

function flash_message($data) {
	\flundr\utility\Session::set('flash', $data);
}

// Shorthand
function fm($data) {
	return flash_message($data);
}

function consume_flash() {
	if (\flundr\utility\Session::get('flash')) {
		$flashData = \flundr\utility\Session::get('flash');
		\flundr\utility\Session::unset('flash');
		echo '<aside style="background-color:#c3c3c3; display:inline-block; padding:1.3em 1.6em; position:fixed; top:2%; left:40%; z-index:999;">'.$flashData.'</aside>';
	}
}

function empty_to_null($array) {
	$array = array_map(function($value){
		return (empty($value)) ? null : $value;
	},$array);
	return $array;
}

function session($var) {
	return \flundr\utility\Session::get($var);
}

function logged_in($var = null) {
	return \flundr\auth\Auth::logged_in();
}

function auth($var = null) {
	if (is_null($var)) {return \flundr\auth\Auth::logged_in();}
	return \flundr\auth\Auth::get($var);
}

function auth_profile() {
	return \flundr\auth\Auth::profile();
}

// Checks for Userrights
function auth_rights($rights) {
	return \flundr\auth\Auth::has_right($rights);
}

// Checks for Usergroups
function auth_groups($groups) {
	return \flundr\auth\Auth::has_group($groups);
}
