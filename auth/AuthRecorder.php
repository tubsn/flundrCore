<?php

namespace flundr\auth;

use \flundr\database\SQLdb;
use \flundr\utility\Log;

class AuthRecorder
{

	private $table = 'authlogs';
	private $db;

	private $timeFrame = 5;
	private $userID;

	function __construct($userID) {

		$this->db = new SQLdb(USER_DB_SETTINGS);
		if (defined('TABLE_THROTTLE')) {
			$this->db->table = TABLE_THROTTLE;
		}
		else {$this->db->table = $this->table;}

		$this->userID = $userID;

	}

	public function prevent_brute_force() {

		$attempts = $this->count_failed_logins();

		if ($attempts > 10) {
			sleep(10);
			Log::error('Possible Brute Force Attack - Logins Throttled for UserID: ' . $this->userID);
		}
		elseif ($attempts > 6) {sleep(3);}
		elseif ($attempts > 2) {usleep(300000);}

		$this->register_login();

	}

	public function login_successful() {
		$this->register_login(true);
		$this->delete_failed_logins();
	}

	public function list() {
		return $this->get_successful_logins();
	}

	private function register_login($success = false) {

		$ip = $_SERVER['REMOTE_ADDR'];
		$ip = substr($ip,0,-3) . 'xxx';
		$userAgent = $_SERVER['HTTP_USER_AGENT'];

		$userInfo = [
			'id' => $this->userID,
			'ip' => $ip,
			'userinfo' => $userAgent,
		];

		if ($success) {$userInfo['success'] = 1;}

		$this->db->create($userInfo);

	}

	private function delete_failed_logins() {

		$SQLstatement = $this->db->connection->prepare(
			"DELETE FROM `$this->table`
			 WHERE `id` = :userID AND `success` is null"
		);

		$SQLstatement->execute([':userID' => $this->userID]);
		return $SQLstatement->rowCount();

	}

	private function count_failed_logins() {

		$SQLstatement = $this->db->connection->prepare(
			"SELECT COUNT(*) FROM `$this->table`
			 WHERE `id` = :userID
			 AND `success` is null
			 AND `date` > NOW() - INTERVAL $this->timeFrame MINUTE"
		);

		$SQLstatement->execute([':userID' => $this->userID]);
		return $SQLstatement->fetch()['COUNT(*)'];

	}

	private function get_successful_logins() {

		$SQLstatement = $this->db->connection->prepare(
			"SELECT * FROM `$this->table`
			 WHERE `id` = :userID AND `success` = 1
			 ORDER BY `date` DESC"
		);

		$SQLstatement->execute([':userID' => $this->userID]);
		return $SQLstatement->fetchall();

	}

	public function create_table() {

		$this->db->query(
			"CREATE TABLE IF NOT EXISTS `$this->table` (
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

}
