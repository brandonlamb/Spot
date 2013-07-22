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
	 * {@inheritdoc}
	 */
	public function getLimitSql($limit, array $options = array())
	{
		$limit = (int) $limit;
		return $limit ? 'FETCH FIRST ' . $limit . ' ROWS ONLY' : '';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getOffsetSql($offset, array $options = array())
	{
		return '';
	}

	/**
	 * {@inheritdoc}
	 */
	public function lastInsertId($sequence = null)
	{
		// If a sequence was passed then pass it through to the PDO method
		if (null !== $sequence) {
			return $this->connection()->lastInsertId($sequence);
		}

		// Get last insert id from the identity_val_local() function
		$stmt = $this->connection()->query('SELECT IDENTITY_VAL_LOCAL() AS insert_id FROM SYSIBM.SYSDUMMY1 FETCH FIRST ROW ONLY');
#		$stmt = $this->connection()->query('SELECT SYSIBM.IDENTITY_VAL_LOCAL() FROM SYSIBM.DUAL FETCH FIRST ROW ONLY');
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);

		return $row['insert_id'];
	}
}
