<?php
namespace Spot\Adapter;

/**
 * Postgresql Database Adapter
 */
class Pgsql extends AbstractAdapter implements AdapterInterface
{
	// Format for date columns, formatted for PHP's date() function
	protected $formatDate = 'Y-m-d';
	protected $formatTime = ' H:i:s';
	protected $formatDatetime = 'Y-m-d H:i:s';

	/**
	 * Escape/quote direct user input
	 *
	 * @param string $string
	 */
	public function escapeField($field)
	{
		return $field === '*' ? $field : '"' . $field . '"';
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
	 * Return insert statement
	 * @param string $datasource
	 * @param array $data
	 * @param array $binds
	 * @return string
	 */
	protected function statementInsert($datasource, array $data, array $binds)
	{
		// build the statement
		return "INSERT INTO " . $datasource .
			" (" . implode(', ', array_map(array($this, 'escapeField'), array_keys($data))) . ")" .
			" VALUES (:" . implode(', :', array_keys($binds)) . ")";
	}
}
