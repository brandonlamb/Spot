<?php

namespace Spot\Adapter;

/**
 * DB2 Database Adapter
 */
class Db2 extends AbstractAdapter implements AdapterInterface
{
	// Format for date columns, formatted for PHP's date() function
	protected $formatDate = 'Y-m-d';
	protected $formatTime = ' H:i:s';
	protected $formatDatetime = 'Y-m-d H:i:s';

	/**
	 * Escape/quote direct user input
	 *
	 * @param string $string
	 * @todo Not quoting the columns essentially by just returning $field
	 */
	public function escapeField($field)
	{
		return $field;
#		return $field === '*' ? $field : '"' . $field . '"';
	}

	/**
	 * {@inherit}
	 */
	public function migrate($table, array $fields, array $options = array())
	{
		return $this;
	}

	/**
	 * @{inherit}
	 */
	public function createDatabase($database)
	{
		$sql = 'CREATE DATABASE ' . $database;

		// Add query to log
		\Spot\Log::addQuery($this, $sql);

		return $this->connection()->exec($sql);
	}

	/**
	 * @{inherit}
	 */
	public function dropDatabase($database)
	{
		$sql = 'DROP DATABASE ' . $database;

		// Add query to log
		\Spot\Log::addQuery($this, $sql);

		return $this->connection()->exec($sql);
	}

	/**
	 * {@inherit}
	 */
	public function truncateDatasource($datasource)
	{
		return $this;
	}

	/**
	 * {@inherit}
	 */
	public function dropDatasource($datasource)
	{
		return $this;
	}

	/**
	 * DB2 doesnt support LIMIT or OFFSET
	 * @{inherit}
	 */
	public function read(\Spot\Query $query, array $options = array())
	{
		//$this->offset = null;
		return parent::read($query, $options);
	}

	/**
	 * Build Limit query from data source using given query object
	 */
	public function statementLimit(\Spot\Query $query, array $options = array())
	{
		return 'FETCH FIRST ' . $this->limit . 'ROWS ONLY ';

	}

	/**
	 *  Build Offset query from data source using given query object
	 */
	public function statementOffset(\Spot\Query $query, array $options = array())
	{
		return '';
	}
}
