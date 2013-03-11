<?php

namespace Spot\Adapter;

use Spot\Query;

class Mock implements AdapterInterface
{
	/**
	 * @var array, fake datasource
	 * datasource['tableName1']
	 */
	protected $datasource;

	/**
	 * If passed an array assume its a format to use for datasource
	 */
	public function __construct($connection = null)
	{
		$this->datasource = is_array($connection) ? $connection : array();
	}

	public function connection() {}
	public function dateFormat() {}
	public function timeFormat() {}
	public function dateTimeFormat() {}
	public function date($format = null) {}
	public function time($format = null) {}
	public function dateTime($format = null) {}
	public function escape($string) {}
	public function create($source, array $data, array $options = array()) {}

	public function read(Query $query, array $options = array())
	{

	}

	public function count( Query $query, array $options = array()) {}
	public function update($source, array $data, array $where = array(), array $options = array()) {}
	public function delete($source, array $where, array $options = array()) {}
	public function beginTransaction() {}
	public function commit() {}
	public function rollback() {}

	public function migrate($table, array $fields, array $options = array())
	{
		return $this;
	}

	public function createDatabase($database)
	{
		return $this;
	}

	public function dropDatabase($database)
	{
		return $this;
	}

	public function truncateDatasource($source)
	{
		return $this;
	}

	public function dropDatasource($source)
	{
		return $this;
	}
}