<?php
// Shorthand for Die and Dump with a styled CSS Layout
function dd($var) {
	\flundr\rendering\QuickDump::dump_and_die($var);
}

// Shorthand for Echoing Arrays and stuff
function dump($var) {
	\flundr\rendering\QuickDump::dump($var);
}

// Dumps an Array to HTML Table
function dump_table(array $data) {
	\flundr\rendering\QuickDump::dump_table($data);
}
function table_dump($data) {dump_table($data);}


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

function percentage($a, $b, $decimals = 2) {
	return \flundr\utility\PercentCalculator::percentage($a, $b, $decimals);
}

function percentage_of($a, $b, $decimals = 2) {
	return \flundr\utility\PercentCalculator::of($a, $b, $decimals);
}

function percentage_difference($a, $b, $decimals = 2) {
	return \flundr\utility\PercentCalculator::difference($a, $b, $decimals);
}

function gnum($number, $decimals = 0, $defaultIfEmpty = '-') {
	if (empty($number) && $number != 0 || $number === null) {return $defaultIfEmpty;}
	return number_format($number,$decimals,',','.');
}

function array_group_by($key, $array) {
	return \flundr\utility\ArrayTools::group_by($key, $array);
}

function array_sum_grouped_by($value, $key, $array) {
	return \flundr\utility\ArrayTools::sum_grouped_by($value, $key, $array);
}

function array_count_by_value($value, $key, $array) {
	return \flundr\utility\ArrayTools::count_by_value($value, $key, $array);
}

// Shorthand for Date Transformations
function formatDate($date, $format='Y-m-d', $default=null) {
	if (is_null($date)) {return $default;}
	$date = new \DateTime($date);
	return $date->format($format);
}

function explode_and_trim($delimiter, $string) {
	return array_map('trim', explode($delimiter, $string));
}

function remove_from_list($id, $list) {
	$elements = explode_and_trim(',' , $list);
	$key = array_search($id, $elements);
	if (!$key) {return $list;}
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
