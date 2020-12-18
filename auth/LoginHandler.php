<?php

namespace flundr\auth;

use \flundr\database\SQLdb;
use \flundr\utility\Session;
use \flundr\auth\Auth;
use \flundr\auth\PersistentCookie;

class LoginHandler
{

	private $userDB;
	private $persistentCookie;

	function __construct() {

		$this->userDB = new SQLdb(USER_DB_SETTINGS);

		if (defined('TABLE_USERS')) {$this->userDB->table = TABLE_USERS;}
		else {$this->userDB->table = 'users';}

		$this->persistentCookie = new PersistentCookie();
	}

	public function login($userLogin,$userPW) {

		$user = $this->get_user_by_mail($userLogin);

		if (!$user) {
			throw new \Exception("Login Failed: Wrong Password or Username");
			return false;
		}

		if (!password_verify($userPW, $user['password'])) {
			throw new \Exception("Login Failed: Wrong Password or Username");
			return false;
		}

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
		$this->persistentCookie->remember_login_for($user['id']);
	}

	public function logout() {

		$this->persistentCookie->invalidate(Auth::get('id'));
		Auth::checkout();
		Session::delete('authUser');

		return true;
	}

	public function profile() {

		$user = $this->userDB->read(Auth::get('id'));
		$user = $this->userDB->remove_fields($user, 'password');

		return $user;
	}

	public function update_profile($userData) {

		$userID = Auth::get('id');

		// Delete all Persistent Logins if User changes Password
		if (isset($userData['password']) || isset($userData['passwort'])) {
			$this->persistentCookie->invalidate($userID);
		}

		$this->userDB->protected = ['level']; // the User Level should not be Changeable

		$updateWorked = $this->userDB->update($userData, $userID);
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

		return null;
	}

	private function push_to_auth_handler($user) {

		// DON'T ever make the Password public!
		if (isset($user['password'])) {unset($user['password']);}

		Session::set('authUser', $user);
		Auth::checkin($user);

	}

}
