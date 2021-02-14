<?php

namespace flundr\auth;

use \flundr\database\SQLdb;
use \flundr\security\CryptLib;
use \flundr\message\Email;
use \flundr\auth\LoginHandler;

class PasswordReset
{

	private $authDB;
	private $userDB;
	private $resetExpire = '+1 Minutes';
	public $mailTemplate = 'auth/reset_pw_email';
	public $mailSubject = 'Password reset requested';
	public $mailFrom = 'no-reply@flundr.de';
	public $mailFromName = 'Flundr Login';
	
	function __construct() {

		$this->authDB = new SQLdb(USER_DB_SETTINGS);
		if (defined('TABLE_AUTHTOKENS')) {$this->authDB->table = TABLE_AUTHTOKENS;}
		else {$this->authDB->table = 'authtokens';}
		$this->authDB->primaryIndex = 'userid'; // Auth Table is using this as the Primary Index

		$this->userDB = new SQLdb(USER_DB_SETTINGS);
		if (defined('TABLE_USERS')) {$this->userDB->table = TABLE_USERS;}
		else {$this->userDB->table = 'users';}

	}


	public function by_email($email) {

		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			throw new \Exception("Invalid E-Mail Address", 400);
		}

		$email = filter_var($email, FILTER_SANITIZE_EMAIL);

		$userID = $this->get_userID_by_mail($email);
		$resetToken = $this->generate_reset_token($userID);
		$this->send_reset_token($email, $resetToken);

	}

	public function change_password($resetToken, $password = null) {

		if (is_null($password) || empty($password)) {throw new \Exception("You did not provide a new password", 400);}
		if (strlen($password) < 5) {throw new \Exception("Your password is too short", 400);}

		$authToken = $this->validate_reset_token($resetToken);

		$userID = $authToken['userid'];
		if (empty($userID)) {throw new \Exception("Reset Token invalid", 403);}

		$this->userDB->update(['password' => $password], $userID);

		$loginHandler = new LoginHandler();
		$loginHandler->login_by_id($userID);

		$this->invalidate_token($authToken['selector']);

		return true;

	}

	public function check_token_integrity($token) {
		$this->decode_reset_token($token);
	}

	private function send_reset_token($targetEmail, $resetToken) {

		$client['ip'] = $_SERVER['REMOTE_ADDR'];
		$client['browser'] = $_SERVER['HTTP_USER_AGENT'];
		$client['date'] = date('D, d.m.Y');
		$client['time'] = date('H:i:s');
		$client['email'] = $targetEmail;
		$client['token'] = $resetToken;


		$resetMail = new Email();
		$resetMail->subject = $this->mailSubject;
		$resetMail->from = $this->mailFrom;
		$resetMail->fromName = $this->mailFromName;
		$resetMail->to = [$targetEmail];

		if ($this->mailTemplate) {
			$resetMail->send($this->mailTemplate, $client);
		}

	}

	private function generate_reset_token($userID) {

		if (!$userID) {return false;}

		// Cookie Setup
		$randomToken = CryptLib::generate_key(20);
		$selector = uniqid();
		$expireTime = new \DateTime($this->resetExpire);
		$DBExpireTime = $expireTime->format('Y-m-d H:i:s');

		// Database Entry with hashed Token
		$this->authDB->create([
			'selector' => $selector,
			'hashed_validator' => hash('sha256', $randomToken ),
			'userid' => $userID,
			'expires' => $DBExpireTime
		]);

		// Combining Selector and unhashed Token in the Users Cookie
		$token = $selector . ':' . $randomToken;

		// The URL-Safe Base64 Token needs to be converted back later
		return base64_encode($token);
	}


	private function get_userID_by_mail($email) {

		$userData = $this->userDB->exact_search($email, 'email');
		if (isset($userData[0])) {return $userData[0]['id'];}

		return null;
	}

	private function decode_reset_token($token) {

		if (strlen($token) != 56) {throw new \Exception("Reset Token invalid", 403);}

		$token = base64_decode($token);
		$tokenContent = explode(':', $token, 2);
		if (!isset($tokenContent[1])) {throw new \Exception("Reset Token invalid", 403);}

		return ['selectorID' => $tokenContent[0], 'token' => $tokenContent[1]];
	}


	private function validate_reset_token($userToken) {
		$decodedToken = $this->decode_reset_token($userToken);
		$selectorID = $decodedToken['selectorID'];
		$hashedUserToken = hash('sha256', $decodedToken['token']);

		$authToken = $this->get_token_from_authDB($selectorID);

		if (!hash_equals($authToken['hashed_validator'], $hashedUserToken)) {
			throw new \Exception("Reset Token did not match", 403);
		}

		return $authToken;

	}


	private function get_token_from_authDB($selectorID) {
		$this->authDB->primaryIndex = 'selector';
		$authToken = $this->authDB->read($selectorID);

		if (empty($authToken)) {
			throw new \Exception("Reset Token invalid", 403);
		}

		if ($this->is_token_expired($authToken['expires'])) {
			$this->invalidate_token($selectorID);
			throw new \Exception("Reset Token expired", 408);
		}
		return $authToken;
	}


	private function is_token_expired($timestamp) {
		$expireDate = new \dateTime($timestamp);
		$expireDate = $expireDate->format('U');
		if ($expireDate < time()) {	return true; }
		return false;
	}

	private function invalidate_token($selectorID) {
		return $this->authDB->delete($selectorID);
	}

}
