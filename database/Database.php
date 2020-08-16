<?php

namespace flundr\database;

interface Database
{

	public function create($array);
	public function read($indices);
	public function update($data, $id);
	public function delete($id);
	public function search($term, $fields);

}
