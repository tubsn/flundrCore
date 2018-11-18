<?php

namespace flundr\core;

class SQLCon {

	public $connection;

	public function __construct($host, $user, $pw, $dbName) {

		try {
			$connection = new \PDO("mysql:host=$host;dbname=$dbName;charset=utf8mb4", $user, $pw);
			$connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION); // PDO Error Mode Try Catch
			$connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC); // PDO Fetch Mode

			$this->connection = $connection;
		}

		catch(\PDOException $e) {
			die ('<div class="error"><h1>DB Connection failed:</h1><p>'.$e->getMessage().'</p></div>');
		}

	}


}