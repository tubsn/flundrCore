<?php

namespace flundr\auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTAuth
{

	private $encryptionKey = null;

	function __construct($encryptionKey = null) {

		if (defined('ENCRYPTION_KEY')) { $configEncryptionKey = ENCRYPTION_KEY; }
		else {$configEncryptionKey = null;}
		$this->encryptionKey = $encryptionKey ?? $configEncryptionKey;
		if (is_null($this->encryptionKey)) {throw new Exception("No encryption Key Provided", 500);}
	}

	public function create_token($userID = null, $domain = null, $expireTime = '+5 minutes') {

		if (is_null($domain)) {$domain = $_SERVER['HTTP_HOST'];}

		$secret_Key = $this->encryptionKey;
		$date = new \DateTimeImmutable();
		$expire_at = $date->modify($expireTime)->getTimestamp();

		$request_data = [
			'iat'  => $date->getTimestamp(), // Issued at: time when the token was generated
			'iss'  => $domain, // Issuer
			'nbf'  => $date->getTimestamp(), // Not before
			'exp'  => $expire_at, // Expire
			'sub' => $userID, // User name
		];

		return JWT::encode($request_data, $secret_Key, 'HS512');

	}

	public function authenticate($token, $domain = null) {
		$token = JWT::decode($token, new Key($this->encryptionKey, 'HS512'));
		$now = new \DateTimeImmutable();

		if (!$domain) {$domain = $_SERVER['HTTP_HOST'];}

		if ($token->iss !== $domain) {
			throw new \Exception("Request from Invalid Source", 401);
		}

		if ($token->nbf > $now->getTimestamp() || $token->exp < $now->getTimestamp()) {
			throw new \Exception("Unauthorized", 401);
		}

		return $token->sub;
	}

	public function authenticate_via_header($domain = null) {

		$header = $this->authorization_header();

		if (empty($header)) {
			throw new \Exception("Bad Request - Header not Supplied", 400);
		}

		if (! preg_match('/Bearer\s(\S+)/', $header, $matches)) {
			throw new \Exception("Bad Request - Token not Supplied", 400);
		}

		$token = $matches[1];
		
		if (!$token) {
			throw new \Exception("Bad Request", 400);
		}

		return $this->authenticate($token, $domain);

	}

	// Based on https://stackoverflow.com/a/40582472
	public function authorization_header(){
		$headers = null;
		if (isset($_SERVER['Authorization'])) {
			$headers = trim($_SERVER["Authorization"]);
		}
		else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { 
			$headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
		} 

		elseif (function_exists('apache_request_headers')) {
			
			$requestHeaders = apache_request_headers();
			$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));

			if (isset($requestHeaders['Authorization'])) {
				$headers = trim($requestHeaders['Authorization']);
			}
		}
		return $headers;
	}


}
