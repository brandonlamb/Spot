<?php

namespace Spot\Adapter;

class Mock implements AdapterInterface
{
	public function __construct($connection = null) {}
	public function connection() {}
	public function dateFormat() {}
	public function timeFormat() {}
	public function dateTimeFormat() {}
	public function date($format = null) {}
	public function time($format = null) {}
	public function dateTime($format = null) {}
	public function escape($string) {}
	public function create($source, array $data, array $options = array()) {}
	public function read(\Spot\Query $query, array $options = array()) {}
	public function count(\Spot\Query $query, array $options = array()) {}
	public function update($source, array $data, array $where = array(), array $options = array()) {}
	public function delete($source, array $where, array $options = array()) {}
	public function beginTransaction() {}
	public function commit() {}
	public function rollback() {}
}