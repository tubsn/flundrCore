<?php

namespace flundr\auth;

use \flundr\database\SQLdb;
use \flundr\security\CryptLib;

class PersistentCookie
{

	private $db;
	private $cookieName = 'auth';
	private $cookieExpire = '+30 Days';

	function __construct() {

		$this->db = new SQLdb(USER_DB_SETTINGS);

		if (defined('TABLE_AUTHTOKENS')) {$this->db->table = TABLE_AUTHTOKENS;}
		else {$this->db->table = 'authtokens';}

		$this->db->primaryIndex = 'userid'; // Auth Table is using this as the Primary Index

		if (defined('LOGINCOOKIE_NAME')) {$this->cookieName = LOGINCOOKIE_NAME;}
		if (defined('LOGINCOOKIE_EXPIRE')) {$this->cookieExpire = LOGINCOOKIE_EXPIRE;}
	}

	public function get_stored_user_id() {

		$userID = $this->validate_cookie($this->cookieName);
		if (!$userID) {return false;}

		$this->remember_login_for($userID);

		return $userID;
	}

	public function remember_login_for($userID) {

		if (!$userID) {return false;}

		// Cookie Setup
		$randomToken = CryptLib::generate_key(20);
		$selector = uniqid();
		$expireTime = new \DateTime($this->cookieExpire);
		$DBExpireTime = $expireTime->format('Y-m-d H:i:s');
		$cookieExpireTime = $expireTime->format('U');

		// Database Entry with hashed Token
		$this->db->create([
			'selector' => $selector,
			'hashed_validator' => hash('sha256', $randomToken ),
			'userid' => $userID,
			'expires' => $DBExpireTime
		]);

		// Combining Selector and unhashed Token in the Users Cookie
		$cookieContent = $selector . ':' . $randomToken;
		setcookie($this->cookieName, $cookieContent, $cookieExpireTime, '/', '', false, true);

		return true;

	}


	private function validate_cookie($cookieName) {

		$loginCookie = $this->read_user_cookie($cookieName);
		if (!$loginCookie) {return false;}

		$authToken = $this->get_token_from_authDB($loginCookie['selectorID']);
		if (!$authToken) {return false;}

		// Stored AuthToken is hashed so we need to hash the Users Cookie too
		$hashedCookieToken = hash('sha256', $loginCookie['token']);
		if (!hash_equals($authToken['hashed_validator'], $hashedCookieToken)) { return false; }

		return $authToken['userid'];
	}

	private function get_token_from_authDB($selectorID) {
		$this->db->primaryIndex = 'selector';

		$authToken = $this->db->read($selectorID);
		$this->invalidate_token($selectorID); // Tokens are one time use only

		if (empty($authToken) || $this->is_token_expired($authToken['expires'])) { return false; }

		return $authToken;
	}

	private function is_token_expired($timestamp) {
		$expireDate = new \dateTime($timestamp);
		$expireDate = $expireDate->format('U');
		if ($expireDate < time()) {	return true; }
		return false;
	}

	public function invalidate_cookie() {
		$loginCookie = $this->read_user_cookie($this->cookieName);
		$this->invalidate_token($loginCookie['selectorID']);
	}

	public function delete_cookie() {
		setcookie($this->cookieName, null, -1, '/');
	}

	public function invalidate_token($selectorID) {
		if ($selectorID) {
			$this->db->primaryIndex = 'selector';
			$this->db->delete($selectorID);
		}
	}

	public function invalidate_all_tokens($userID) {
		if ($userID) {$this->db->delete($userID);}
	}

	private function read_user_cookie($cookieName) {
		if (isset($_COOKIE[$cookieName])) {
			// the Cookiecontent is combined selector:token
			$cookieContent = explode(':', $_COOKIE[$cookieName], 2);
			return ['selectorID' => $cookieContent[0],
					'token' => $cookieContent[1]];
		}
		return false;
	}

}
