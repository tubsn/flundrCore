<?php

namespace flundr\controlpanel\models;
use \flundr\database\SQLdb;
use \flundr\mvc\Model;

class User extends Model
{

	public function __construct() {

		$this->db = new SQLdb(USER_DB_SETTINGS);

		if (defined('TABLE_USERS')) {$this->db->table = TABLE_USERS;}
		else {$this->db->table = 'users';}

		$this->db->columns = ['id','email','firstname','lastname','groups','rights'];

	}

	public function list($orderby = 'id', $order = 'ASC') {
		$this->db->orderby = $orderby;
		$this->db->order = $order;
		return $this->db->read_all();
	}

}
