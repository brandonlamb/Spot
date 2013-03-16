<?php
namespace Spot\Adapter;

/**
 * Mysql Database Adapter
 *
 * @package Spot
 * @link http://spot.os.ly
 */
class Mysql extends AbstractAdapter implements AdapterInterface
{
	// Format for date columns, formatted for PHP's date() function
	protected $formatDate = 'Y-m-d';
	protected $formatTime = ' H:i:s';
	protected $formatDatetime = 'Y-m-d H:i:s';

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
		$tableColumns = $this->getColumnsForTable($table, $this->database);

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
		$tableColumns = $this->getColumnsForTable($table, $this->database);
		$updateFormattedFields = array();
		foreach ($tableColumns as $fieldName => $columnInfo) {
			if (isset($formattedFields[$fieldName])) {
				// TODO: Need to do a more exact comparison and make this non-mysql specific
				if (
						$this->fieldTypeMap[$formattedFields[$fieldName]['type']] != $columnInfo['DATA_TYPE'] ||
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
			} catch (\PDOException $e) {
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
	 * Syntax for each column in CREATE TABLE command
	 *
	 * @param string $fieldName Field name
	 * @param array $fieldInfo Array of field settings
	 * @return string SQL syntax
	 */
	public function migrateSyntaxFieldCreate($fieldName, array $fieldInfo)
	{
		// Ensure field type exists
		if (!isset($this->fieldTypeMap[$fieldInfo['type']])) {
			throw new \Spot\Exception("Field type '" . $fieldInfo['type'] . "' not supported");
		}

		//Ensure this class will choose adapter type
		unset($fieldInfo['adapter_type']);

		$fieldInfo = array_merge($this->fieldTypeMap[$fieldInfo['type']],$fieldInfo);
		$syntax = "`" . $fieldName . "` " . $fieldInfo['adapter_type'];

		// Column type and length
		$syntax .= ($fieldInfo['length']) ? '(' . $fieldInfo['length'] . ')' : '';

		// Unsigned
		$syntax .= ($fieldInfo['unsigned']) ? ' unsigned' : '';

		// Collate
		$syntax .= ($fieldInfo['type'] == 'string' || $fieldInfo['type'] == 'text') ? ' COLLATE ' . $this->collate : '';

		// Nullable
		$isNullable = true;
		if ($fieldInfo['required'] || !$fieldInfo['null']) {
			$syntax .= ' NOT NULL';
			$isNullable = false;
		}

		// Default value
		if ($fieldInfo['default'] === null && $isNullable) {
			$syntax .= " DEFAULT NULL";
		} elseif ($fieldInfo['default'] !== null) {
			$default = $fieldInfo['default'];
			// If it's a boolean and $default is boolean then it should be 1 or 0
			if ( is_bool($default) && $fieldInfo['type'] == "boolean" ) {
				$default = $default ? 1 : 0;
			}

			if (is_scalar($default)) {
				$syntax .= " DEFAULT '" . $default . "'";
			}
		}

		// Extra
		$syntax .= ($fieldInfo['primary'] && $fieldInfo['serial']) ? ' AUTO_INCREMENT' : '';
		return $syntax;
	}

	/**
	 * Syntax for CREATE TABLE with given fields and column syntax
	 *
	 * @param string $table Table name
	 * @param array $formattedFields Array of fields with all settings
	 * @param array $columnsSyntax Array of SQL syntax of columns produced by 'migrateSyntaxFieldCreate' function
	 * @param Array $options Options that may affect migrations or how tables are setup
	 * @return string SQL syntax
	 */
	public function migrateSyntaxTableCreate($table, array $formattedFields, array $columnsSyntax, array $options)
	{
		$options = $this->formatMigrateOptions($options);

		// Begin syntax soup
		$syntax = "CREATE TABLE IF NOT EXISTS `" . $table . "` (\n";

		// Columns
		$syntax .= implode(",\n", $columnsSyntax);

		// Keys...
		$ki = 0;
		$tableKeys = array(
			'primary' => array(),
			'unique' => array(),
			'index' => array()
		);
		$fulltextFields = array();
		$usedKeyNames = array();

		foreach ($formattedFields as $fieldName => $fieldInfo) {
			// Determine key field name (can't use same key name twice, so we have to append a number)
			$fieldKeyName = $fieldName;
			while (in_array($fieldKeyName, $usedKeyNames)) {
				$fieldKeyName = $fieldName . '_' . $ki;
			}

			// Key type
			if ($fieldInfo['primary']) {
				$tableKeys['primary'][] = $fieldName;
			}

			if ($fieldInfo['unique']) {
				if (is_string($fieldInfo['unique'])) {
					// Named group
					$fieldKeyName = $fieldInfo['unique'];
				}
				$tableKeys['unique'][$fieldKeyName][] = $fieldName;
				$usedKeyNames[] = $fieldKeyName;
			}

			if ($fieldInfo['index']) {
				$fieldKeyName = $fieldName;
				if (is_string($fieldInfo['index'])) {
					// Named group
					$fieldKeyName = $fieldInfo['index'];
				}
				$tableKeys['index'][$fieldKeyName][] = $fieldName;
				$usedKeyNames[] = $fieldKeyName;
			}

			// FULLTEXT search
			if ($fieldInfo['fulltext']) {
				$fulltextFields[] = $fieldName;
			}
		}

		// FULLTEXT
		if ($fulltextFields) {
			// Ensure table type is MyISAM if FULLTEXT columns have been specified
			if ('myisam' !== strtolower($options['engine'])) {
				$options['engine'] = 'MyISAM';
			}
			$syntax .= "\n, FULLTEXT(`" . implode('`, `', $fulltextFields) . "`)";
		}

		// PRIMARY
		if ($tableKeys['primary']) {
			$syntax .= "\n, PRIMARY KEY(`" . implode('`, `', $tableKeys['primary']) . "`)";
		}

		// UNIQUE
		foreach ($tableKeys['unique'] as $keyName => $keyFields) {
			$syntax .= "\n, UNIQUE KEY `" . $keyName . "` (`" . implode('`, `', $keyFields) . "`)";
		}

		// INDEX
		foreach ($tableKeys['index'] as $keyName => $keyFields) {
			$syntax .= "\n, KEY `" . $keyName . "` (`" . implode('`, `', $keyFields) . "`)";
		}

		// Extra
		$syntax .= "\n) ENGINE=" . $options['engine'] . " DEFAULT CHARSET=" . $options['charset'] . " COLLATE=" . $options['collate'] . ";";

		return $syntax;
	}

	/**
	 * Syntax for each column in CREATE TABLE command
	 *
	 * @param string $fieldName Field name
	 * @param array $fieldInfo Array of field settings
	 * @return string SQL syntax
	 */
	public function migrateSyntaxFieldUpdate($fieldName, array $fieldInfo, $add = false)
	{
		return ( $add ? "ADD COLUMN " : "MODIFY " ) . $this->migrateSyntaxFieldCreate($fieldName, $fieldInfo);
	}

	/**
	 * Syntax for ALTER TABLE with given fields and column syntax
	 *
	 * @param string $table Table name
	 * @param array $formattedFields Array of fields with all settings
	 * @param array $columnsSyntax Array of SQL syntax of columns produced by 'migrateSyntaxFieldUpdate' function
	 * @return string SQL syntax
	 */
	public function migrateSyntaxTableUpdate($table, array $formattedFields, array $columnsSyntax, array $options)
	{
		/*
		  Example:

			ALTER TABLE `posts`
			CHANGE `title` `title` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL ,
			CHANGE `status` `status` VARCHAR( 40 ) CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT 'draft'
		*/

		$options = $this->formatMigrateOptions($options);

		// Begin syntax soup
		$syntax = "ALTER TABLE `" . $table . "` \n";

		// Columns
		$syntax .= implode(",\n", $columnsSyntax);

		// Keys...
		$ki = 0;
		$tableKeys = array(
			'primary' => array(),
			'unique' => array(),
			'index' => array()
		);
		$fulltextFields = array();
		$usedKeyNames = array();
		foreach ($formattedFields as $fieldName => $fieldInfo) {
			// Determine key field name (can't use same key name twice, so we have to append a number)
			$fieldKeyName = $fieldName;
			while (in_array($fieldKeyName, $usedKeyNames)) {
				$fieldKeyName = $fieldName . '_' . $ki;
			}

			// Key type
			if ($fieldInfo['primary']) {
				$tableKeys['primary'][] = $fieldName;
			}

			if ($fieldInfo['unique']) {
				if (is_string($fieldInfo['unique'])) {
					// Named group
					$fieldKeyName = $fieldInfo['unique'];
				}
				$tableKeys['unique'][$fieldKeyName][] = $fieldName;
				$usedKeyNames[] = $fieldKeyName;
			}

			if ($fieldInfo['index']) {
				if (is_string($fieldInfo['index'])) {
					// Named group
					$fieldKeyName = $fieldInfo['index'];
				}
				$tableKeys['index'][$fieldKeyName][] = $fieldName;
				$usedKeyNames[] = $fieldKeyName;
			}

			// FULLTEXT search
			if ($fieldInfo['fulltext']) {
				$fulltextFields[] = $fieldName;
			}
		}

		// FULLTEXT
		if ($fulltextFields) {
			// Ensure table type is MyISAM if FULLTEXT columns have been specified
			if ('myisam' !== strtolower($options['engine'])) {
				$options['engine'] = 'MyISAM';
			}
			$syntax .= "\n, FULLTEXT(`" . implode('`, `', $fulltextFields) . "`)";
		}

		// PRIMARY
		if ($tableKeys['primary']) {
			$syntax .= "\n, PRIMARY KEY(`" . implode('`, `', $tableKeys['primary']) . "`)";
		}

		// UNIQUE
		foreach ($tableKeys['unique'] as $keyName => $keyFields) {
			$syntax .= "\n, UNIQUE KEY `" . $keyName . "` (`" . implode('`, `', $keyFields) . "`)";
		}

		// INDEX
		foreach ($tableKeys['index'] as $keyName => $keyFields) {
			$syntax .= "\n, KEY `" . $keyName . "` (`" . implode('`, `', $keyFields) . "`)";
		}

		// Extra
		$syntax .= ",\n ENGINE=" . $options['engine'] . " DEFAULT CHARSET=" . $options['charset'] . " COLLATE=" . $options['collate'] . ";";

		return $syntax;
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
		} catch (\PDOException $e) {
			// Table does not exist
			if ($e->getCode() == "42S02") {
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
		} catch (\PDOException $e) {
			// Table does not exist
			if ($e->getCode() == '42S02') {
				throw new \Spot\Exception\Datasource\Missing("Table or datasource '" . $datasource . "' does not exist");
			}

			// Re-throw exception
			throw $e;
		}

		return $this;
	}

	/**
	 * Get/set the database name
	 * @param string $database
	 * @return string
	 */
	public function database($database = null)
	{
		null !== $database && $this->database = (string) $database;
		return $this->database;
	}
}
