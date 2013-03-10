<?php
namespace Spot\Adapter;

/**
 * Mysql Database Adapter
 *
 * @package Spot
 * @link http://spot.os.ly
 */
class Mysql extends PDO_Abstract implements AdapterInterface
{
	// Format for date columns, formatted for PHP's date() function
	protected $formatDate = "Y-m-d";
	protected $formatTime = " H:i:s";
	protected $formatDatetime = "Y-m-d H:i:s";

	// Driver-Specific settings
	protected $engine = 'InnoDB';
	protected $charset = 'utf8';
	protected $collate = 'utf8_unicode_ci';

	/**
	 * Set database engine (InnoDB, MyISAM, etc)
	 */
	public function engine($engine = null)
	{
		if (null !== $engine) {
			$this->engine = $engine;
		}
		return $this->engine;
	}

	/**
	 * Escape/quote direct user input
	 *
	 * @param string $string
	 */
	public function escapeField($field)
	{
		return $field == '*' ? $field : '`' . $field . '`';
	}

	/**
	 * Set character set and MySQL collate string
	 */
	public function characterSet($charset, $collate = 'utf8_unicode_ci')
	{
		$this->charset = $charset;
		$this->collate = $collate;
	}

	/**
	 * Get columns for current table
	 *
	 * @param String $table Table name
	 * @return Array
	 */
	protected function getColumnsForTable($table, $source)
	{
		$tableColumns = array();
		$tblCols = $this->connection()->query("SELECT * FROM information_schema.columns WHERE table_schema = '" . $source . "' AND table_name = '" . $table . "'");

		if ($tblCols) {
			while ($columnData = $tblCols->fetch(\PDO::FETCH_ASSOC)) {
				$tableColumns[$columnData['COLUMN_NAME']] = $columnData;
			}
			return $tableColumns;
		} else {
			return false;
		}
	}

	/**
	 * {@inherit}
	 */
	public function migrate($table, array $fields, array $options = array())
	{
		// Setup defaults for options that do not exist
		$options = $options + array(
			'engine' => $this->engine,
			'charset' => $this->charset,
			'collate' => $this->collate,
		);

		// Get current fields for table
		$tableExists = false;
		$tableColumns = $this->getColumnsForTable($table, $this->_database);

		if ($tableColumns) {
			$tableExists = true;
		}
		if ($tableExists) {
			// Update table
			$this->migrateTableUpdate($table, $fields, $options);
		} else {
			// Create table
			$this->migrateTableCreate($table, $fields, $options);
		}

		return $this;
	}

	/**
	 * Execute a CREATE TABLE command
	 *
	 * @param String $table Table name
	 * @param Array $fields Fields and their attributes as defined in the mapper
	 * @param Array $options Options that may affect migrations or how tables are setup
	 */
	public function migrateTableCreate($table, array $formattedFields, array $options = array())
	{
		/*
			STEPS:
			* Use fields to get column syntax
			* Use column syntax array to get table syntax
			* Run SQL
		*/

		// Prepare fields and get syntax for each
		$columnsSyntax = array();
		foreach ($formattedFields as $fieldName => $fieldInfo) {
			$columnsSyntax[$fieldName] = $this->migrateSyntaxFieldCreate($fieldName, $fieldInfo);
		}

		// Get syntax for table with fields/columns
		$sql = $this->migrateSyntaxTableCreate($table, $formattedFields, $columnsSyntax, $options);

		// Add query to log
		\Spot\Log::addQuery($this, $sql);

		$this->connection()->exec($sql);
		return true;
	}


	/**
	 * Execute an ALTER/UPDATE TABLE command
	 *
	 * @param String $table Table name
	 * @param Array $fields Fields and their attributes as defined in the mapper
	 * @param Array $options Options that may affect migrations or how tables are setup
	 */
	public function migrateTableUpdate($table, array $formattedFields, array $options = array())
	{
		/*
			STEPS:
			* Use fields to get column syntax
			* Use column syntax array to get table syntax
			* Run SQL
		*/

		// Prepare fields and get syntax for each
		$tableColumns = $this->getColumnsForTable($table, $this->_database);
		$updateFormattedFields = array();
		foreach ($tableColumns as $fieldName => $columnInfo) {
			if (isset($formattedFields[$fieldName])) {
				// TODO: Need to do a more exact comparison and make this non-mysql specific
				if (
						$this->_fieldTypeMap[$formattedFields[$fieldName]['type']] != $columnInfo['DATA_TYPE'] ||
						$formattedFields[$fieldName]['default'] !== $columnInfo['COLUMN_DEFAULT']
					) {
					$updateFormattedFields[$fieldName] = $formattedFields[$fieldName];
				}

				unset($formattedFields[$fieldName]);
			}
		}

		$columnsSyntax = array();
		// Update fields whose options have changed
		foreach ($updateFormattedFields as $fieldName => $fieldInfo) {
			$columnsSyntax[$fieldName] = $this->migrateSyntaxFieldUpdate($fieldName, $fieldInfo, false);
		}
		// Add fields that are missing from current ones
		foreach ($formattedFields as $fieldName => $fieldInfo) {
			$columnsSyntax[$fieldName] = $this->migrateSyntaxFieldUpdate($fieldName, $fieldInfo, true);
		}

		// Get syntax for table with fields/columns
		if ( !empty($columnsSyntax) ) {
			$sql = $this->migrateSyntaxTableUpdate($table, $formattedFields, $columnsSyntax, $options);

			// Add query to log
			\Spot\Log::addQuery($this, $sql);

			try {
				// Run SQL
				$this->connection()->exec($sql);
			} catch(\PDOException $e) {
				// Table does not exist - special Exception case
				if ($e->getCode() == "42S02") {
					throw new \Spot\Exception\Datasource\Missing("Table '" . $table . "' does not exist");
				}

				// Re-throw exception
				throw $e;
			}
		}
		return true;
	}

	/**
	 * Ensure migration options are full and have all keys required
	 */
	public function formatMigrateOptions(array $options)
	{
		return $options + array(
			'engine' => $this->engine,
			'charset' => $this->charset,
			'collate' => $this->collate,
		);
	}

	/**
	 * @{inherit}
	 */
	public function createDatabase($database)
	{
		$sql = "CREATE DATABASE " . $database;

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
		$sql = "TRUNCATE TABLE " . $datasource;

		// Add query to log
		\Spot\Log::addQuery($this, $sql);

		try {
			return $this->connection()->exec($sql);
		} catch(\PDOException $e) {
			// Table does not exist
			if($e->getCode() == "42S02") {
				throw new \Spot\Exception\Datasource\Missing("Table or datasource '" . $datasource . "' does not exist");
			}

			// Re-throw exception
			throw $e;
		}

		return $this;
	}

	/**
	 * {@inherit}
	 */
	public function dropDatasource($datasource)
	{
		$sql = 'DROP TABLE ' . $datasource;

		// Add query to log
		\Spot\Log::addQuery($this, $sql);

		try {
			return $this->connection()->exec($sql);
		} catch(\PDOException $e) {
			// Table does not exist
			if($e->getCode() == '42S02') {
				throw new \Spot\Exception\Datasource\Missing("Table or datasource '" . $datasource . "' does not exist");
			}

			// Re-throw exception
			throw $e;
		}

		return $this;
	}
}
