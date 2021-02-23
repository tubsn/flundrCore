<?php

namespace flundr\auth;

use \flundr\database\SQLdb;

class AuthInstall
{

	private $authLogsTable = 'authlogs';
	private $authTokenTable = 'authtokens';
	private $userTable = 'users';

	function __construct() {

		$this->db = new SQLdb(USER_DB_SETTINGS);
		if (defined('TABLE_AUTHLOGS')) { $this->authLogsTable = TABLE_AUTHLOGS; }
		if (defined('TABLE_AUTHTOKENS')) { $this->authTokenTable = TABLE_AUTHTOKENS; }
		if (defined('TABLE_USERS')) { $this->userTable = TABLE_USERS; }

	}

	public function install() {

		$this->create_user_table();
		$this->create_auth_logs_table();
		$this->create_auth_token_table();

		$this->create_user();

		echo 'Jobs Done';

	}



	public function create_user() {

		$email = readline('Enter Admin-User E-Mail: ');
		$password = readline('Choose a Password: ');

		$this->db->table = $this->userTable;

		$user = [
			'email' => $email,
			'firstname' => 'Default',
			'lastname' => 'flundr',
			'level' => 'admin',
			'password' => $password
		];

		$this->db->create($user);

	}

	private function create_user_table() {

		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `$this->userTable` (
			 `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			 `edited` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
			 `created` timestamp NOT NULL DEFAULT current_timestamp(),
			 `username` varchar(60) DEFAULT NULL,
			 `password` varchar(120) NOT NULL,
			 `firstname` varchar(120) DEFAULT NULL,
			 `lastname` varchar(120) DEFAULT NULL,
			 `email` varchar(120) NOT NULL,
			 `groups` varchar(120) DEFAULT NULL,
			 `level` enum('User','Admin') DEFAULT NULL,
			 `rights` varchar(120) DEFAULT NULL,
			 PRIMARY KEY (`id`),
			 UNIQUE KEY `Username` (`username`),
			 UNIQUE KEY `EMail` (`email`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
		);

	}

	private function create_auth_logs_table() {

		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `$this->authLogsTable` (
				`id` int(11) NOT NULL,
				`date` datetime NOT NULL DEFAULT current_timestamp(),
				`ip` varchar(39) NOT NULL,
				`userinfo` varchar(255) DEFAULT NULL,
				`success` tinyint(1) DEFAULT NULL,
				KEY `id` (`id`),
				KEY `success` (`success`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
		);

	}

	private function create_auth_token_table() {

		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `$this->authTokenTable` (
				`userid` int(11) NOT NULL,
				`selector` char(20) NOT NULL,
				`hashed_validator` char(64) NOT NULL,
				`expires` datetime NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
		);

	}

}
