<?php

namespace flundr\auth;

use \flundr\database\SQLdb;
use \flundr\utility\Session;
use \flundr\auth\Auth;
use \flundr\auth\PersistentCookie;
use \flundr\auth\AuthRecorder;

class LoginHandler
{

	private $userDB;
	private $persistentCookie;

	function __construct() {

		if (!defined('USER_DB_SETTINGS')) {
			throw new \Exception("UserDB Config not found. Please check your .env File", 500);
		}			
		
		$this->userDB = new SQLdb(USER_DB_SETTINGS);

		if (defined('TABLE_USERS')) {$this->userDB->table = TABLE_USERS;}
		else {$this->userDB->table = 'users';}

		$this->persistentCookie = new PersistentCookie();
	}

	public function login($userLogin,$userPW) {

		$user = $this->get_user_by_mail($userLogin);

		$recorder = new AuthRecorder($user['id']);

		if (!password_verify($userPW, $user['password'])) {
			$recorder->prevent_brute_force();
			throw new \Exception("Login Failed: wrong Password");
			return false;
		}

		$recorder->login_successful();
		$this->push_to_auth_handler($user);
		$this->persistentCookie->remember_login_for($user['id']);

		return true;
	}

	public function login_by_cookie() {

		$userID = $this->persistentCookie->get_stored_user_id();
		if (!$userID) {return false;}

		$user = $this->userDB->read($userID);
		if (!$user) {return false;}

		$this->push_to_auth_handler($user);

		return true;
	}

	public function login_by_id($userID) {

		$user = $this->userDB->read($userID);
		if (!$user) {return false;}

		$this->push_to_auth_handler($user);

		// Do we need a new Cookie?
		//$this->persistentCookie->remember_login_for($user['id']);
	}

	public function logout() {

		$this->persistentCookie->invalidate_cookie();
		Auth::checkout();
		Session::delete('authUser');

		return true;
	}

	public function list_logins() {

		$recorder = new AuthRecorder(Auth::get('id'));
		return $recorder->list();

	}

	public function profile() {

		$user = $this->userDB->read(Auth::get('id'));
		$user = $this->userDB->remove_fields($user, 'password');

		return $user;
	}

	public function update_profile($userData) {

		$userID = Auth::get('id');

		// Delete all Persistent Logins and create new one if User changes Password
		if (isset($userData['password']) || isset($userData['passwort'])) {
			$this->persistentCookie->invalidate_all_tokens($userID);
			$this->persistentCookie->remember_login_for($userID);
		}

		$this->userDB->protected = ['level']; // the User Level should not be Changeable

		try {

			$updateWorked = $this->userDB->update($userData, $userID);

		} catch (\Exception $e) {

			dd($e);

		}

		$newUser = $this->userDB->read($userID);

		if ($updateWorked && $newUser) {
			$this->push_to_auth_handler($newUser);
			return true;
		}

		return false;
	}

	private function get_user_by_mail($email) {

		$userData = $this->userDB->exact_search($email, 'email');
		if (isset($userData[0])) {
			return $userData[0]; // there "Should" by only one User... :)
		}

		throw new \Exception("Login Failed: User not Found");

	}

	private function push_to_auth_handler($user) {

		// DON'T ever make the Password public!
		if (isset($user['password'])) {unset($user['password']);}

		Session::set('authUser', $user);
		Auth::checkin($user);

	}

}
