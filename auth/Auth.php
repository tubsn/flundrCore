<?php

namespace flundr\auth;

use flundr\utility\Session;
use flundr\auth\LoginHandler;

class Auth
{

	private static $authUser = null;
	public static $loginPageUrl = '/login';

	// User Status
	private static $loggedIn = false;
	private static $validIP = false;

	public static function checkin($authUser = null) {
		self::$authUser = $authUser;
		self::check_for_valid_login();
		self::check_for_allowed_ip();
	}

	public static function checkout() {
		self::$authUser = false;
		self::$loggedIn = false;
	}

	public static function refresh_auth() {
		$loginHandler = new LoginHandler();
		$loginHandler->login_by_id(self::get('id'));
		unset($loginHandler);
	}

	public static function loginpage() {
		self::redirect_to_login();
	}

	public static function get($index) {
		return self::$authUser[$index] ?? null;
	}

	public static function profile() {
		return self::$authUser;
	}

	public static function logged_in() {
		return self::$loggedIn;
	}

	public static function valid_ip() {
		return self::$validIP;
	}

	public static function has_right($rights) {
		return self::has_feature($rights, 'rights');
	}

	public static function has_group($groups) {
		return self::has_feature($groups, 'groups');
	}

	// added for easier syntax
	public static function has_rights($rights) {
		return self::has_feature($rights, 'rights');
	}

	public static function has_groups($groups) {
		return self::has_feature($groups, 'groups');
	}


	private static function has_feature($features, $fieldname) {

		if (empty(self::$authUser) || self::$authUser == false) {return false;}
		if (empty(self::$authUser[$fieldname])) {return false;}
		
		$userFeatures = array_map('trim', explode(',', self::$authUser[$fieldname]));
		$features = array_map('trim', explode(',', $features));

		foreach ($features as $feature) {
			if (in_array($feature, $userFeatures)) {return true;}
		}

		return false;
	}

	private static function check_for_valid_login() {
		// Actually we are not checking anything, but it should not be empty :)
		if (is_array(self::$authUser) && !empty(self::$authUser)) {
			self::$loggedIn = true;
		}
	}

	private static function allowed_ips() {
		if (defined('ALLOWED_IPS')) {return ALLOWED_IPS;}
		return [];
	}

	private static function check_for_allowed_ip() {
		$ipFieldName = 'REMOTE_ADDR';
		if (defined()) {$ipFieldName = SERVER_IP_FIELDNAME;}

		if (in_array($_SERVER[$ipFieldName], self::allowed_ips())) {
			self::$validIP = true;
		}
	}

	private static function redirect_to_login() {
		// Save a Referer to get Back after the Login
		Session::set('referer', $_SERVER['REQUEST_URI']);
		header('Location:' . self::$loginPageUrl);
		die;
	}

}
