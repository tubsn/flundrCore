<?php

namespace flundr\auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTAuth
{

	public function create_token($userID = null, $domain = null, $expireTime = '+5 minutes') {

		if (is_null($domain)) {$domain = $_SERVER['HTTP_HOST'];}

		$secret_Key = ENCRYPTION_KEY;
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

		$token = JWT::decode($token, new Key(ENCRYPTION_KEY, 'HS512'));
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

		$headers = apache_request_headers();
		$auth = $headers['Authorization'];

		if (! preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
			throw new \Exception("Bad Request - Token not Supplied", 400);
		}

		$token = $matches[1];
		
		if (!$token) {
			throw new \Exception("Bad Request", 400);
		}

		return $this->authenticate($token, $domain);

	}

}
