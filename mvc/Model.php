<?php

namespace flundr\mvc;

abstract class Model {

	public $columns = '*';
	public $limit = 1000;
	protected $db;

	public function query($query) {
		return $this->db->query($query);
	}

	public function get($id, $columns = null) {
		$this->db->columns = $columns ?? $this->columns;
		return $this->db->read($id);
	}

	public function read($id, $columns = null) {
		$this->db->columns = $columns ?? $this->columns;
		return $this->db->read($id);
	}

	public function all($columns = null) {
		$this->db->columns = $columns ?? $this->columns;
		return $this->db->read_all();
	}

	public function read_all($columns = null) {
		$this->db->columns = $columns ?? $this->columns;
		return $this->db->read_all();
	}

	public function search($needle, $haystack, $columns = null) {
		$this->db->columns = $columns ?? $this->columns;
		return $this->db->search($needle, $haystack);
	}

	public function exact_search($needle, $haystack, $columns = null) {
		$this->db->columns = $columns ?? $this->columns;
		return $this->db->exact_search($needle, $haystack);
	}

	public function new($data) {
		return $this->db->create($data);
	}

	public function create($data) {
		return $this->db->create($data);
	}

	public function set(array $data, $id) {
		return $this->db->update($data, $id);
	}

	public function update(array $data, $id) {
		return $this->db->update($data, $id);
	}

	public function delete($id) {
		return $this->db->delete($id);
	}

	private function remove_fields($dataArray, $fieldsToStrip) {

		if(empty($fieldsToStrip)) {return $dataArray;}
		if (!is_array($fieldsToStrip)) { $fieldsToStrip = [$fieldsToStrip]; }

		foreach ($fieldsToStrip as $fieldName) {
			if (isset($dataArray[$fieldName])) { unset($dataArray[$fieldName]); }
		}

		return $dataArray;
	}


}