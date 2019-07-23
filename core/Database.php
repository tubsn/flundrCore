<?php

namespace flundr\core;

class Database {

	protected $db;
	public $table;

	public function connect($dbHost, $dbUser, $dbPW, $dbName) {

		$database = new SQLCon($dbHost,$dbUser,$dbPW,$dbName);
		$this->db = $database->connection;

	}

	public function formatPDOPrepare(array $data, $keepIDs = false) {

		$fieldNames = '';
		$valueNames = '';
		$updateFields = '';
		$values = [];

		foreach ($data as $fieldName => $value) {

			// Remove CSRF Challange Token from Post Forms
			if (strtolower($fieldName) == 'challange') {continue;}

			// Remove IDs as long as keep IDs is false
			if (!$keepIDs && strtolower($fieldName) == 'id') {continue;}

			// Ignore empty Fields "" but not NULL Values
			if ((empty($value) && !is_null($value)) && ($value !== 0) && ($value !== '0')) {continue;}
			//if ((empty($value) && !is_null($value))) {continue;}

			// If Fieldname == Password and not Empty -> hash the Password
			if (strtolower($fieldName) == 'password' || strtolower($fieldName) == 'passwort') {
				if (empty($value)) {continue;} // exclude Field if Password is Empty
				$values[':'.$fieldName] = cryptLib::hashPW($value); // Hash Password
			}
			else {
				$values[':'.$fieldName] = $value; // Output every other Value
			}

			// Security Masking the Fieldnames with ` Pairs
			$fieldNames .= "`".str_replace("`", "``", $fieldName)."`, ";
			$valueNames .= ":".$fieldName.", ";

			$updateFields .= "`".str_replace("`", "``", $fieldName)."` = :".$fieldName.", ";

		}

		$fieldNames= rtrim($fieldNames, ', '); // Remove excess , in $fieldNames
		$valueNames= rtrim($valueNames, ', '); // Remove excess , in $valueNames
		$updateFields= rtrim($updateFields, ', '); // Remove excess , in $updateFields

		return ['fieldNames' => $fieldNames, 'valueNames' => $valueNames, 'updateFields' => $updateFields,'values' => $values];

	} // End Secure Prepare Data

	public function setTable($tableName) {
		$this->table = $tableName;
	}

	public function getTable() {
		return $this->table;
	}

	// Pass Query to the DB
	public function query($query) {
		return $this->db->query($query);
	}

	// Selects exactely one Element
	public function get($id, $fields = ['*']) {
		$id = intval($id); // Limit has to be int

		$fieldsString = implode (',', $fields); // Selected fields comma separated

		$stmt = $this->db->prepare("SELECT $fieldsString FROM `$this->table`
										WHERE `ID` = :ID ");
		$stmt->execute([':ID' => $id]);
		return ($stmt->fetch()); // return Element
	}

	// Selects some Elements based on an Array of IDs
	public function getSome($ids=[], $orderBy='ID', $order='ASC') {

		$listOfIds = implode(',',array_map('intval', $ids)); // Intval for all IDs
		$orderBy = "`".str_replace("`", "``", $orderBy)."`"; // Escaping the OrderBy String
		$order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC'; // Order only ASC or DESC

		$stmt = $this->db->prepare("SELECT * FROM `$this->table`
										WHERE `ID` IN ($listOfIds) ORDER BY $orderBy $order");
		$stmt->execute();
		return ($stmt->fetchAll()); // return Entry

	}

	// Selects all Elements
	public function getAll($offset=0, $limit=50, $orderBy='ID', $order='ASC') {
		$offset = intval($offset); // Limit has to be int
		$limit = intval($limit); // Limit has to be int
		$limit = $offset . ',' . $limit; // Limit + Offest
		$orderBy = "`".str_replace("`", "``", $orderBy)."`"; // Escaping the OrderBy String
		$order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC'; // Order only ASC or DESC

		$stmt = $this->db->prepare("SELECT * FROM `$this->table`
									ORDER BY $orderBy $order LIMIT $limit");
		$stmt->execute();
		return ($stmt->fetchAll()); // return Elements Array


	}

	// Edit Stuff in DB
	public function set($data, $id, $args=[]) {

		$id = abs(intval($id)); // Force positive INT

		if (!in_array('html',$args)) {
			$data = saniLib::sanitizeData($data);
		}

		// Sanitize Data, Hash Passwords and Pre-Format for PDO->prepare
		$data = $this->formatPDOPrepare($data);

		// Reassign the preformated fields to Variables
		$updateFields=$data['updateFields']; // `Fieldname` = :Fieldname, ...
		$values=$data['values']; // Escaped Values for excecute

		try {
			$stmt = $this->db->prepare("UPDATE `$this->table` SET $updateFields WHERE `ID` = $id LIMIT 1");
			$stmt->execute($values);

			return $stmt->rowCount(); // Returns 1 if something got changed
		}
		catch(\PDOException $e) {
			//new errorController('<b>'.__CLASS__.' Could not Edit Post: </b> '.$e->getMessage());
			echo $e->getMessage();
			die; // error
		}

	}

	// Replace Stuff even IDs, use with care! As this actually deletes rows
	public function replace($data) {

		$data = saniLib::sanitizeData($data);

		// Sanitize Data, Hash Passwords and Pre-Format for PDO->prepare
		$data = $this->formatPDOPrepare($data,true);

		// Reassign the preformated fields to Variables for the Query
		$fieldNames=$data['fieldNames']; // `Fieldname1`,`Fieldname2` ...
		$valueNames=$data['valueNames']; // :Fieldname1,:Fieldname2, ...
		$values=$data['values']; // Escaped Values

		try {
			$stmt = $this->db->prepare("REPLACE INTO `$this->table` ($fieldNames) VALUES ($valueNames)");

			$stmt->execute($values);
			return ($this->db->lastInsertId()); // Return a New ID on Success
		}
		catch(\PDOException $e) {
			//new errorController('<b>'.__CLASS__.' Could not create Element: </b> '.$e->getMessage());
			echo $e->getMessage();
			die; // error
		}

	}


	// Create Stuff in DB
	public function create($data, $args=[]) {

		if (!in_array('html',$args)) {
			$data = saniLib::sanitizeData($data);
		}

		// Sanitize Data, Hash Passwords and Pre-Format for PDO->prepare
		$data = $this->formatPDOPrepare($data);

		// Reassign the preformated fields to Variables for the Query
		$fieldNames=$data['fieldNames']; // `Fieldname1`,`Fieldname2` ...
		$valueNames=$data['valueNames']; // :Fieldname1,:Fieldname2, ...
		$values=$data['values']; // Escaped Values

		try {
			$stmt = $this->db->prepare("INSERT INTO `$this->table` ($fieldNames) VALUES ($valueNames)");
			$stmt->execute($values);
			return ($this->db->lastInsertId()); // Return a New ID on Success
		}
		catch(\PDOException $e) {
			//new errorController('<b>'.__CLASS__.' Could not create Element: </b> '.$e->getMessage());
			echo $e->getMessage();
			die; // error
		}

	}

	// delete stuff in DB
	public function delete($id) {
		$id = abs(intval($id)); // Force positive INT
		return $this->db->exec("DELETE FROM `$this->table` WHERE `ID` = $id");
	}


	public function getFields() {

		try {
			$stmt = $this->db->prepare("
				SELECT column_name
				FROM information_schema.columns
				WHERE  table_name = '$this->table'
			");
			$stmt->execute();
			$columns = $stmt->fetchAll();

			$fieldNames = [];

			foreach ($columns as $field) {
				array_push($fieldNames, $field['column_name']);
			}

			return $fieldNames;

		}

		catch(\PDOException $e) {
			echo $e->getMessage();
			die; // error
		}

	}

	public function checkForNull($array) {
		// Check for Empty Fields and Convert them to Null
		return array_map(array($this, 'setToNull'), $array);

	}

	private function setToNull($entry) {

		if ($entry == '') {
			return null;
		}

		return $entry;

	}

	// Date Reformat Helper
	public function formatDate($date, $format='Y-m-d') {
		if (is_null($date)) {
			return null;
		}
		$date = new \DateTime($date);
		return $date->format($format);
	}

}