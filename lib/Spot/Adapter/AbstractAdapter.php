<?php
namespace Spot\Adapter;
use Spot\Query;

/**
 * Abstract Adapter
 *
 * @package Spot
 * @link http://spot.os.ly
 */
abstract class AbstractAdapter
{
	/** @var string, Format for date columns, formatted for PHP's date() function */
	protected $formatDate;
	protected $formatTime;
	protected $formatDatetime;

	/** @var PDO, database connection */
	protected $connection;

	/**
	 * @var array, Map datamapper field types to actual database adapter types
	 * @todo Have to improve this to allow custom types, callbacks, and validation
	 */
	protected $fieldMapType;

	/**
	 * @param PDO $connection DSN string or pre-existing Mongo object
	 * @throws \InvalidArgumentException
	 */
	public function __construct($connection)
	{
		if ($connection instanceof \PDO || $connection instanceof PdoInterface) {
			$this->connection = $connection;
		} else {
			throw new \InvalidArgumentException('Connection is not a PDO object or PdoInterface');
		}

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

	/**
	 * Get database format
	 * @return string Date format for PHP's date() function
	 */
	public function dateFormat()
	{
		return $this->formatDate;
	}

	/**
	 * Get database time format
	 * @return string Time format for PHP's date() function
	 */
	public function timeFormat()
	{
		return $this->formatTime;
	}

	/**
	 * Get database format
	 * @return string DateTime format for PHP's date() function
	 */
	public function dateTimeFormat()
	{
		return $this->formatDatetime;
	}

	/**
	 * Get date
	 * @return object DateTime
	 */
	public function date($format = null)
	{
		if (null === $format) {
			$format = $this->dateFormat();
		}
		return $this->dateTimeObject($format . ' ' . $this->timeFormat());
	}

	/**
	 * Get database time format
	 * @return object DateTime
	 */
	public function time($format = null)
	{
		if (null === $format) {
			$format = $this->timeFormat();
		}
		return $this->dateTimeObject($this->dateFormat() . ' ' . $format);
	}

	/**
	 * Get datetime
	 * @return object DateTIme
	 */
	public function dateTime($format = null)
	{
		if (null === $format) {
			$format = $this->dateTimeFormat();
		}
		return $this->dateTimeObject($format);
	}

	/**
	 * Turn formstted date into timestamp
	 * Also handles input timestamps
	 * @return DateTime object
	 */
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

	/**
	 * Get database connection
	 * @return PDO
	 */
	public function connection()
	{
		return $this->connection;
	}

	/**
	 * Escape/quote direct user input
	 * @param string $string
	 * @return string
	 */
	public function escape($string)
	{
		return $this->connection()->quote($string);
	}

	/**
	 * Escape/quote direct user input
	 * @param string $field
	 * @return string
	 */
	public function escapeField($field)
	{
		return $field;
	}

	/**
	 * Prepare an SQL statement
	 * @param string $sql
	 * @return \PDOStatement
	 */
	public function prepare($sql)
	{
		return $this->connection()->prepare($sql);
	}

	/**
	 * Find records with custom SQL query
	 * @param string $sql SQL query to execute
	 * @param array $binds Array of bound parameters to use as values for query
	 * @return \PDOStatement|bool
	 * @throws \Spot\Exception
	 */
	public function query($sql, array $binds = array())
	{
		// Add query to log
		\Spot\Log::addQuery($this, $sql, $binds);

		// Prepare and execute query
		if ($stmt = $this->connection()->prepare($sql)) {
			$results = $stmt->execute($binds);
			return ($results === true) ? $stmt : false;
		} else {
			throw new \Spot\Exception(__METHOD__ . " Error: Unable to execute SQL query - failed to create prepared statement from given SQL");
		}
	}

	/**
	 * Create new row object with set properties
	 * @param string $datasource
	 * @param array $data
	 * @param array $options
	 * @return mixed
	 * @throws \Spot\Exception
	 */
	public function create($datasource, array $data, array $options = array())
	{
		$binds = $this->statementBinds($data);

		// build the statement
		$sql = "INSERT INTO " . $datasource .
			" (" . implode(', ', array_map(array($this, 'escapeField'), array_keys($data))) . ")" .
			" VALUES(:" . implode(', :', array_keys($binds)) . ")";

		// Add query to log
		\Spot\Log::addQuery($this, $sql, $binds);

		try {
			// Prepare update query
			$stmt = $this->connection()->prepare($sql);

			if ($stmt) {
				// Execute
				if ($stmt->execute($binds)) {
					// Use 'id' if PK exists, otherwise returns true
					$id = $this->connection()->lastInsertId();
					$result = $id ? $id : true;
				} else {
					$result = false;
				}
			} else {
				$result = false;
			}
		} catch(\PDOException $e) {
			// Table does not exist
			if ($e->getCode() == '42S02') {
				throw new \Spot\Exception("Table or datasource '" . $datasource . "' does not exist");
			}

			// Re-throw exception
			throw $e;
		}

		return $result;
	}

	/**
	 * Build a select statement in SQL
	 * Can be overridden by adapters for custom syntax
	 *
	 * @param \Spot\Entity $entity
	 * @param array $options
	 * @throws \Spot\Exception
	 * @todo Add support for JOINs
	 */
	public function read(Query $query, array $options = array())
	{
		$conditions = $this->statementConditions($query->conditions);
		$joins = $this->statementJoins($query->joins);
		$binds = $this->statementBinds($query->params());

		$order = array();
		if ($query->order) {
			foreach ($query->order as $oField => $oSort) {
				$order[] = $this->escapeField($oField) . " " . $oSort;
			}
		}

		if ($query->having) {
			$havingConditions = $this->statementConditions($query->having);
		}

		$sql = "
			SELECT " . $this->statementFields($query->fields) . "
			FROM " . $query->datasource . "
			" . ($joins ? $joins : '') . "
			" . ($conditions ? 'WHERE ' . $conditions : '') . "
			" . ($query->group ? 'GROUP BY ' . implode(', ', $query->group) : '') . "
			" . ($query->having ? 'HAVING' . $havingConditions : '') . "
			" . ($order ? 'ORDER BY ' . implode(', ', $order) : '') . "
			" . ($query->limit ? 'LIMIT ' . $query->limit : '') . "
			" . ($query->limit && $query->offset ? 'OFFSET ' . $query->offset: '') . "
			";

		// Unset any NULL values in binds (compared as "IS NULL" and "IS NOT NULL" in SQL instead)
		if ($binds && count($binds) > 0) {
			foreach ($binds as $field => $value) {
				if (null === $value) {
					unset($binds[$field]);
				}
			}
		}
print_r($sql);

		// Add query to log
		\Spot\Log::addQuery($this, $sql, $binds);

		$result = false;
		try {
			// Prepare update query
			$stmt = $this->connection()->prepare($sql);

			if ($stmt) {
				// Execute
				$result = ($stmt->execute($binds)) ? $this->toCollection($query, $stmt) : false;
			} else {
				$result = false;
			}
		} catch (\PDOException $e) {
			// Table does not exist
			if ($e->getCode() == '42S02') {
				throw new \Spot\Exception("Table or datasource '" . $query->datasource . "' does not exist");
			}

			// Re-throw exception
			throw $e;
		}

		return $result;
	}

	/**
	 * Count number of rows in source based on conditions
	 * @param \Spot\Query $query
	 * @param array $options
	 * @throws \Spot\Exception
	 */
	public function count(Query $query, array $options = array())
	{
		$conditions = $this->statementConditions($query->conditions);
		$binds = $this->statementBinds($query->params());

		$sql = "
			SELECT COUNT(*) as count
			FROM " . $query->datasource . "
			" . ($conditions ? 'WHERE ' . $conditions : '') . "
			" . ($query->group ? 'GROUP BY ' . implode(', ', $query->group) : '');

		// Unset any NULL values in binds (compared as "IS NULL" and "IS NOT NULL" in SQL instead)
		if ($binds && count($binds) > 0) {
			foreach ($binds as $field => $value) {
				if (null === $value) {
					unset($binds[$field]);
				}
			}
		}

		// Add query to log
		\Spot\Log::addQuery($this, $sql,$binds);

		$result = false;
		try {
			// Prepare count query
			$stmt = $this->connection()->prepare($sql);

			// if prepared, execute
			if ($stmt && $stmt->execute($binds)) {
				//the count is returned in the first column
				$result = (int) $stmt->fetchColumn();
			} else {
				$result = false;
			}
		} catch(\PDOException $e) {
			// Table does not exist
			if ($e->getCode() == '42S02') {
				throw new \Spot\Exception("Table or datasource '" . $query->datasource . "' does not exist");
			}

			// Re-throw exception
			throw $e;
		}

		return $result;
	}

	/**
	 * Update entity
	 * @param string $datasource
	 * @param array $data
	 * @param data $where
	 * @param array $options
	 * @throws \Spot\Exception
	 */
	public function update($datasource, array $data, array $where = array(), array $options = array())
	{
		$dataBinds = $this->statementBinds($data, 0);
		$whereBinds = $this->statementBinds($where, count($dataBinds));
		$binds = array_merge($dataBinds, $whereBinds);
		$placeholders = array();
		$dataFields = array_combine(array_keys($data), array_keys($dataBinds));

		// Placeholders and passed data
		foreach ($dataFields as $field => $bindField) {
			$placeholders[] = $this->escapeField($field) . " = :" . $bindField . "";
		}

		$conditions = $this->statementConditions($where, count($dataBinds));

		// Ensure there are actually updated values on THIS table
		if (count($binds) > 0) {
			// Build the query
			$sql = "UPDATE " . $datasource .
				" SET " . implode(', ', $placeholders) .
				" WHERE " . $conditions;

			// Add query to log
			\Spot\Log::addQuery($this, $sql, $binds);

			try {
				// Prepare update query
				$stmt = $this->connection()->prepare($sql);

				if ($stmt) {
					// Execute
					if ($stmt->execute($binds)) {
						$result = true;
					} else {
						$result = false;
					}
				} else {
					$result = false;
				}
			} catch(\PDOException $e) {
				// Table does not exist
				if ($e->getCode() == '42S02') {
					throw new \Spot\Exception("Table or datasource '" . $datasource . "' does not exist");
				}

				// Re-throw exception
				throw $e;
			}
		} else {
			$result = false;
		}

		return $result;
	}

	/**
	 * Delete entities matching given conditions
	 *
	 * @param string $datasource Name of data source
	 * @param array $data
	 * @param array $options
	 * @throws \Spot\Exception
	 */
	public function delete($datasource, array $data, array $options = array())
	{
		$binds = $this->statementBinds($data, 0);
		$conditions = $this->statementConditions($data);

		$sql = "DELETE FROM " . $datasource . "";
		$sql .= ($conditions ? ' WHERE ' . $conditions : '');

		// Add query to log
		\Spot\Log::addQuery($this, $sql, $binds);
		try {
			$stmt = $this->connection()->prepare($sql);
			if ($stmt) {
				// Execute
				if ($stmt->execute($binds)) {
					$result = $stmt->rowCount();
				} else {
					$result = false;
				}
			} else {
				$result = false;
			}
			return $result;
		} catch(\PDOException $e) {
			// Table does not exist
			if ($e->getCode() == '42S02') {
				throw new \Spot\Exception("Table or datasource '" . $datasource . "' does not exist");
			}

			// Re-throw exception
			throw $e;
		}
	}

	/**
	 * Begin transaction
	 */
	public function beginTransaction()
	{
		$sql = 'BEGIN';

		// Add query to log
		\Spot\Log::addQuery($this, $sql);

		return $this->connection()->exec($sql);
	}

	/**
	 * Commit transaction
	 */
	public function commit()
	{
		$sql = 'COMMIT';

		// Add query to log
		\Spot\Log::addQuery($this, $sql);

		return $this->connection()->exec($sql);
	}

	/**
	 * Rollback transaction
	 */
	public function rollback()
	{
		$sql = 'ROLLBACK';

		// Add query to log
		\Spot\Log::addQuery($this, $sql);

		return $this->connection()->exec($sql);
	}

	/**
	 * Return fields as a string for a query statement
	 */
	public function statementFields(array $fields = array())
	{
		$preparedFields = array();
		foreach ($fields as $field) {
			if (stripos($field, ' AS ') !== false || strpos($field, '.') !== false) {
				// Leave calculated fields and SQL fragements alone
				$preparedFields[] = $field;
			} else {
				// Escape field names
				$preparedFields[] = $this->escapeField($field);
			}
		}
		return count($fields) > 0 ? implode(', ', $preparedFields) : '*';
	}

	/**
	 * Add a table join (INNER, LEFT OUTER, RIGHT OUTER, FULL OUTER, CROSS)
	 * array('user.id', '=', 'profile.user_id') will compile to ON `user`.`id` = `profile`.`user_id`
	 *
	 * @param array $joins
	 * @return Query
	 */
	public function statementJoins(array $joins = array())
	{
		$sqlJoins = array();

		foreach ($joins as $join) {
			$sqlJoins[] = trim($join[2]) . ' JOIN' . ' ' . $join[0] . ' ON (' . trim($join[1]) . ')';
		}

		return join(' ', $sqlJoins);
	}

	/**
	 * Builds an SQL string given conditions
	 * @param array $conditions
	 * @param int $ci
	 */
	public function statementConditions(array $conditions = array(), $ci = 0)
	{
		if (count($conditions) === 0) { return; }

		$sqlStatement = '(';
		$loopOnce = false;

		// @todo - what is this
		$defaultColOperators = array(0 => '', 1 => '=');

		foreach ($conditions as $condition) {
			if (is_array($condition) && isset($condition['conditions'])) {
				$subConditions = $condition['conditions'];
			} else {
				$subConditions = $conditions;
				$loopOnce = true;
			}

			$sqlWhere = array();
			foreach ($subConditions as $column => $value) {
				$whereClause = '';

				// Column name with comparison operator
				$colData = explode(' ', $column);
				$operator = isset($colData[1]) ? $colData[1] : '=';
				if (count($colData) > 2) {
					$operator = array_pop($colData);
					$colData = array(implode(' ', $colData), $operator);
				}
				$col = $colData[0];

				// Determine which operator to use based on custom and standard syntax
				switch ($operator) {
					case '<':
					case ':lt':
						$operator = '<';
						break;
					case '<=':
					case ':lte':
						$operator = '<=';
						break;
					case '>':
					case ':gt':
						$operator = '>';
						break;
					case '>=':
					case ':gte':
						$operator = '>=';
						break;

					// REGEX matching
					case '~=':
					case '=~':
					case ':regex':
						$operator = 'REGEX';
						break;

					// LIKE
					case ':like':
						$operator = 'LIKE';
						break;

					// column IN ()
					case 'IN':
						$whereClause = $this->escapeField($col) . ' IN (' . join(', ', array_fill(0, count($value), '?')) . ')';
						break;

					// column BETWEEN x AND y
#					case 'BETWEEN':
#						$sqlWhere = $condition['column'] . ' BETWEEN ' . join(' AND ', array_fill(0, count($condition['values']), '?'));
#						break;

					// FULLTEXT search
					// MATCH(col) AGAINST(search)
					case ':fulltext':
						$colParam = preg_replace('/\W+/', '_', $col) . $ci;
						$whereClause = 'MATCH(' . $this->escapeField($col) . ') AGAINST(:' . $colParam . ')';
					break;

					// ALL - Find ALL values in a set - Kind of like IN(), but seeking *all* the values
					case ':all':
						throw new \Spot\Exception("SQL adapters do not currently support the ':all' operator");
					break;

					// Not equal
					case '<>':
					case '!=':
					case ':ne':
					case ':not':
						$operator = '!=';
						if (is_array($value)) {
							$operator = 'NOT IN';
						} elseif (is_null($value)) {
							$operator = 'IS NOT NULL';
						}
					break;

					// Equals
					case '=':
					case ':eq':
					default:
						$operator = '=';
						if (is_array($value)) {
							$operator = 'IN';
						} elseif (is_null($value)) {
							$operator = 'IS NULL';
						}
					break;
				}

				// If WHERE clause not already set by the code above...
				if (is_array($value)) {
					$valueIn = '';
					foreach ($value as $val) {
						$valueIn .= $this->escape($val) . ',';
					}
					$value = '(' . trim($valueIn, ',') . ')';
					$whereClause = $this->escapeField($col) . ' ' . $operator . ' ' . $value;
				} elseif (is_null($value)) {
					$whereClause = $this->escapeField($col) . ' ' . $operator;
				}

				if (empty($whereClause)) {
					// Add to binds array and add to WHERE clause
					$colParam = preg_replace('/\W+/', '_', $col) . $ci;

					// Dont escape calculated/aliased columns
					if (strpos($col, '.') !== false) {
						$sqlWhere[] = $col . ' ' . $operator . ' :' . $colParam . '';
					} else {
						$sqlWhere[] = $this->escapeField($col) . ' ' . $operator . ' :' . $colParam . '';
					}
				} else {
					$sqlWhere[] = $whereClause;
				}

				// Increment ensures column name distinction
				// We need to do this whether it was used or not
				// to maintain compatibility with statementConditions()
				$ci++;
			}
			if ($sqlStatement != '(') {
				$sqlStatement .= ' ' . (isset($condition['setType']) ? $condition['setType'] : 'AND') . ' (';
			}
			$sqlStatement .= implode(' ' . (isset($condition['type']) ? $condition['type'] : 'AND') . ' ', $sqlWhere );
			$sqlStatement .= ')';
			if ($loopOnce) { break; }
		}

		// Ensure we actually had conditions
		if (0 == $ci) {
			$sqlStatement = '';
		}

		return $sqlStatement;
	}

	/**
	 * Returns array of binds to pass to query function
	 * @param array $conditions
	 * @param bool $ci
	 */
	public function statementBinds(array $conditions = array(), $ci = false)
	{
		if (count($conditions) === 0) { return; }

		$binds = array();
		$loopOnce = false;

		foreach ($conditions as $condition) {
			if (is_array($condition) && isset($condition['conditions'])) {
				$subConditions = $condition['conditions'];
			} else {
				$subConditions = $conditions;
				$loopOnce = true;
			}

			foreach ($subConditions as $column => $value) {
				$bindValue = false;

				// Handle binding depending on type
				if (is_object($value)) {
					if ($value instanceof \DateTime) {
						// @todo Need to take into account column type for date formatting
						$bindValue = (string) $value->format($this->dateTimeFormat());
					} else {
						$bindValue = (string) $value; // Attempt cast of object to string (calls object's __toString method)
					}
				} elseif (is_bool($value)) {
					$bindValue = (int) $value; // Cast boolean to integer (false = 0, true = 1)
				} elseif (!is_array($value)) {
					$bindValue = $value;
				}

				// Bind given value
				if (false !== $bindValue) {
					// Column name with comparison operator
					$colData = explode(' ', $column);
					$operator = '=';
					if (count($colData) > 2) {
						$operator = array_pop($colData);
						$colData = array(implode(' ', $colData), $operator);
					}
					$col = $colData[0];

					if (false !== $ci) {
						$col = $col . $ci;
					}

					$colParam = preg_replace('/\W+/', '_', $col);

					// Add to binds array and add to WHERE clause
					$binds[$colParam] = $bindValue;
				}
				// Increment ensures column name distinction
				// We need to do this whether it was used or not
				// to maintain compatibility with statementConditions()
				$ci++;
			}

			if ($loopOnce) {
				break;
			}
		}
		return $binds;
	}

	/**
	 * Return result set for current query
	 * @param \Spot\Query $query
	 * @param \PDOStatement $stmt
	 * @return array
	 */
	public function toCollection(Query $query, $stmt)
	{
		$mapper = $query->mapper();
		$entityClass = $query->entityName();

		if ($stmt instanceof \PDOStatement) {
			// Set PDO fetch mode
			$stmt->setFetchMode(\PDO::FETCH_ASSOC);

			$collection = $mapper->collection($entityClass, $stmt);

			// Ensure statement is closed
			$stmt->closeCursor();

			return $collection;
		} else {
			$mapper->addError(__METHOD__ . " - Unable to execute query " . implode(' | ', $this->connection()->errorInfo()));
			return array();
		}
	}

	/**
	 * Bind array of field/value data to given statement
	 *
	 * @param PDOStatement $stmt
	 * @param array $binds
	 * @return bool
	 */
	protected function bindValues($stmt, array $binds)
	{
		// Bind each value to the given prepared statement
		foreach ($binds as $field => $value) {
			$stmt->bindValue($field, $value);
		}
		return true;
	}
}
