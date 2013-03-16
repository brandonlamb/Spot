<?php

namespace Spot\Adapter;

use Spot\Query;

class Mock implements AdapterInterface
{
	/**
	 * @var array, fake datasource columns
	 * columns['tableName'] = ['name', 'address', 'etc']
	 */
	protected $columns;

	/**
	 * @var array, fake datasource
	 * datasource['tableName1'] = [array(), array()]
	 */
	protected $datasource;

	/**
	 * If passed an array assume its a format to use for datasource
	 */
	public function __construct($connection = null)
	{
		$this->datasource = is_array($connection) ? $connection : array();

		$this->fieldTypeMap = array(
			'string' => array('adapter_type' => 'varchar', 'length' => 255),
			'email' => array('adapter_type' => 'varchar', 'length' => 255),
			'url' => array('adapter_type' => 'varchar', 'length' => 255),
			'tel' => array('adapter_type' => 'varchar', 'length' => 255),
			'password' => array('adapter_type' => 'varchar', 'length' => 255),
			'text' => array('adapter_type' => 'text'),
			'int' => array('adapter_type' => 'int'),
			'integer' => array('adapter_type' => 'int'),
			'bool' => array('adapter_type' => 'tinyint', 'length' => 1),
			'boolean' => array('adapter_type' => 'tinyint', 'length' => 1),
			'float' => array('adapter_type' => 'float'),
			'double' => array('adapter_type' => 'double'),
			'decimal' => array('adapter_type' => 'decimal'),
			'date' => array('adapter_type' => 'date'),
			'datetime' => array('adapter_type' => 'datetime'),
			'year' => array('adapter_type' => 'year', 'length' => 4),
			'month' => array('adapter_type' => 'month', 'length' => 2),
			'time' => array('adapter_type' => 'time'),
			'timestamp' => array('adapter_type' => 'int', 'length' => 11),
		);
	}

	public function connection()
	{
		return $this->datasource;
	}

	public function dateFormat()
	{
		return $this->formatDate;
	}

	public function timeFormat()
	{
		return $this->formatTime;
	}

	public function dateTimeFormat()
	{
		return $this->formatDatetime;
	}

	public function date($format = null)
	{
		if (null === $format) {
			$format = $this->dateFormat();
		}
		return $this->dateTimeObject($format . ' ' . $this->timeFormat());
	}

	public function time($format = null)
	{
		if (null === $format) {
			$format = $this->timeFormat();
		}
		return $this->dateTimeObject($this->dateFormat() . ' ' . $format);
	}

	public function dateTime($format = null)
	{
		if (null === $format) {
			$format = $this->dateTimeFormat();
		}
		return $this->dateTimeObject($format);
	}

	protected function dateTimeObject($format)
	{
		// Already a timestamp? @link http://www.php.net/manual/en/function.is-int.php#97006
		if (is_int($format) || is_float($format)) {
			$dt = new \DateTime();

			// Timestamps must be prefixed with '@' symbol
			$dt->setTimestamp($format);
		} else {
			$dt = new \DateTime();
			$dt->format($format);
		}
		return $dt;
	}

	public function escape($string)
	{
		return $string;
	}

	public function create($source, array $data, array $options = array())
	{
		$this->datasource[$source][] = $data;
		return true;
	}

	public function read(Query $query, array $options = array())
	{

	}

	public function update($source, array $data, array $where = array(), array $options = array())
	{

	}

	public function delete($source, array $where, array $options = array())
	{

	}

	public function count(Query $query, array $options = array())
	{
		return 0;
	}

	public function beginTransaction() {}
	public function commit() {}
	public function rollback() {}

	public function migrate($table, array $fields, array $options = array())
	{
		// Clear any existing data
		$this->truncateDatasource($table);

		// We only want column names, not their properties
		$columns = array();
		foreach ($fields as $key => $value) {
			$columns[] = $key;
		}
		$this->columns[$table] = $columns;

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
		$this->datasource[$source] = array();
		return $this;
	}

	public function dropDatasource($source)
	{
		if (isset($this->datasource[$source])) {
			unset($this->datasource[$source]);
		}
		return $this;
	}
}