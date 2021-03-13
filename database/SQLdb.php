<?php

namespace flundr\database;

use \PDO;
use \PDOException;
use \flundr\security\Sanitize;
use \flundr\security\CryptLib;

class SQLdb implements Database
{

	public $connection;

	public $table = null;
	public $columns = '*';
	public $primaryIndex = 'id';
	public $orderby = 'id';
	public $order = 'ASC';
	public $offset = 0;
	public $limit = 10000;
	public $protected = null;

	protected $dbname;
	protected $host = 'localhost';
	protected $port = 3306;
	protected $charset = 'utf8mb4';

	function __construct($config) {
		$this->load_db_settings($config);
		$this->register_db_connection($config['user'], $config['password']);
	}

	private function register_db_connection($username, $password) {

		$PDOSetupString = 'mysql:host='.$this->host.';dbname='.$this->dbname.';charset='.$this->charset.';port='.$this->port;

		try {
			$connection = new PDO($PDOSetupString, $username, $password);
			$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Errormode Exceptions
			$connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // Fetch as Associative Array
		}

		catch(PDOException $errorData) {
			http_response_code(500);
			echo '<h1>Database Connection Error:</h1>';
			echo '<p>' . $errorData->getFile() . '<br/>Error in Line: ' . $errorData->getLine() . '</p>';
			echo '<mark>'. $errorData->getMessage().'</mark>';
			die;
		}

		$this->connection = $connection;

	}

	private function load_db_settings(array $config) {
		$this->host 	= $config['host'];
		$this->dbname 	= $config['dbname'];
		if (isset($config['port'])) {$this->port = $config['port'];}
		if (isset($config['charset'])) {$this->port = $config['charset'];}
	}

	public function query($query) {
		return $this->connection->exec($query);
	}

	public function read($indices = null) {

		if (is_null($indices)) {return null;}
		if (is_array($indices)) {return $this->read_multiple_records($indices);}

		return $this->read_single_record($indices);
	}

	public function read_all() {
		$SQLstatement = $this->connection->prepare(
			"SELECT ".$this->columns()."
			 FROM `$this->table`
			 ORDER BY $this->orderby $this->order
			 LIMIT $this->offset, $this->limit"
		);

		$SQLstatement->execute();
		return ($SQLstatement->fetchall());
	}

	private function read_single_record($id) {

		$SQLstatement = $this->connection->prepare(
			"SELECT ".$this->columns()."
			 FROM `$this->table`
			 WHERE `$this->primaryIndex` = :ID"
		);

		$SQLstatement->execute([':ID' => $id]);
		return ($SQLstatement->fetch());
	}

	private function read_multiple_records(array $ids) {

		$listOfIds = implode(',', array_map('intval', $ids)); // Intval for all IDs

		$SQLstatement = $this->connection->prepare(
			"SELECT ".$this->columns()."
			 FROM `$this->table`
			 WHERE `$this->primaryIndex` IN ($listOfIds)
			 ORDER BY FIELD(`$this->table`.`$this->primaryIndex`, $listOfIds)
			 LIMIT $this->offset, $this->limit"
		);
		$SQLstatement->execute();
		return ($SQLstatement->fetchall());
	}


	public function search($term, $columns) {

		if (is_array($columns)) {
			$columns = implode(', ', $columns);
			$SQLstatement = $this->connection->prepare(
				"SELECT ".$this->columns()."
				 FROM `$this->table`
				 WHERE CONCAT_WS('', $columns) LIKE :term
			 	 ORDER BY $this->orderby $this->order
			 	 LIMIT $this->offset, $this->limit"
			);
		} else {
			$SQLstatement = $this->connection->prepare(
				"SELECT ".$this->columns()."
				 FROM `$this->table`
				 WHERE `$columns` LIKE :term
			 	 ORDER BY $this->orderby $this->order
			 	 LIMIT $this->offset, $this->limit"
			);
		}

		$SQLstatement->execute([':term' => '%'.$term.'%']);
		$output = $SQLstatement->fetchall();
		if (empty($output)) {return null;}
		return $output;

	}

	public function exact_search($term, $column) {

		$SQLstatement = $this->connection->prepare(
			"SELECT ".$this->columns()."
			 FROM `$this->table`
			 WHERE `$column` = :term"
		);

		$SQLstatement->execute([':term' => $term]);
		$output = $SQLstatement->fetchall();
		if (empty($output)) {return null;}
		return $output;

	}

	public function table_fields() {

		$stmt = $this->connection->prepare(
			"SHOW columns FROM $this->table FROM $this->dbName"
		);

		$stmt->execute();
		return $stmt->fetchAll();

	}


	private function prepare_for_mass_insertion(array $data, $keepIDs = false) {

		$data = Sanitize::mass_input($data);

		$fieldNames = '';
		$valueNames = '';
		$updateFields = '';
		$values = [];

		foreach ($data as $fieldName => $value) {

			// Ignore CSRF Token's and Primary Indexes
			if (strtolower($fieldName) == 'challange' || strtolower($fieldName) == 'csrftoken' ) {continue;}
			if (!$keepIDs && strtolower($fieldName) == $this->primaryIndex) {continue;}

			// Always hash Passwords
			if (strtolower($fieldName) == 'password' || strtolower($fieldName) == 'passwort') {
				if (empty($value)) {continue;} // exclude Field if Password is Empty
				$values[':'.$fieldName] = CryptLib::hash($value);
			}

			// Process general fields
			else { $values[':'.$fieldName] = $value; }

			// Security Masking the Fieldnames with ` Pairs
			$fieldNames .= "`".str_replace("`", "``", $fieldName)."`, ";
			$valueNames .= ":".$fieldName.", ";
			$updateFields .= "`".str_replace("`", "``", $fieldName)."` = :".$fieldName.", ";

		}

		if (empty($fieldNames)) {return null;}

		$fieldNames= rtrim($fieldNames, ', '); // Remove excess , in $fieldNames
		$valueNames= rtrim($valueNames, ', '); // Remove excess , in $valueNames
		$updateFields= rtrim($updateFields, ', '); // Remove excess , in $updateFields

		return ['fieldNames' => $fieldNames, 'valueNames' => $valueNames, 'updateFields' => $updateFields,'values' => $values];

	}


	public function remove_fields($dataArray, $fieldsToStrip) {

		if(empty($fieldsToStrip)) {return $dataArray;}
		if (!is_array($fieldsToStrip)) { $fieldsToStrip = [$fieldsToStrip]; }

		foreach ($fieldsToStrip as $fieldName) {
			if (isset($dataArray[$fieldName])) { unset($dataArray[$fieldName]); }
		}

		return $dataArray;
	}

	private function columns() {
		if (is_array($this->columns)) {	return implode(',', $this->columns); }
		if (empty($this->columns) || $this->columns == '*') {return '*';}

		return $this->columns;
	}


	public function create($newRecord) {

		$data = $this->prepare_for_mass_insertion($newRecord, true); // true = keep the PrimaryIndex

		try {
			$stmt = $this->connection->prepare(
				"INSERT INTO `$this->table` (".$data['fieldNames'].") VALUES (".$data['valueNames'].")"
			);

			$stmt->execute($data['values']);
			return ($this->connection->lastInsertId());

		} catch(\PDOException $e) {
			die ($e->getMessage()); // die on error
		}

		return false;
	}


	public function delete($id) {
		$SQLstatement = $this->connection->prepare(
			"DELETE FROM `$this->table`
			 WHERE `$this->primaryIndex` = :ID"
		);

		$SQLstatement->execute([':ID' => $id]);
		return $SQLstatement->rowCount();
	}


	public function update($record, $id) {

		// Sanitize Data, Hash Passwords and Pre-Format for PDO->prepare
		$record = $this->remove_fields($record, $this->protected);
		$record = $this->prepare_for_mass_insertion($record);

		if (empty($record)) {return null;}

		// Add the Target RowID to the PDO Query
		$record['values'][':RowID'] = $id;

		try {
			$stmt = $this->connection->prepare(
				"UPDATE `$this->table`
				SET ".$record['updateFields']."
				WHERE `$this->primaryIndex` = :RowID LIMIT 1"
			);
			$stmt->execute($record['values']);

			return $stmt->rowCount(); // Returns true if something got changed

		}
		catch(\PDOException $e) {
			die ($e->getMessage()); // die on error
		}

		return null;
	}

}
