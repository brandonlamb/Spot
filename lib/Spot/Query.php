<?php
namespace Spot;

/**
 * Query Object - Used to build adapter-independent queries PHP-style
 *
 * @package Spot
 * @author Vance Lucas <vance@vancelucas.com>
 * @link http://spot.os.ly
 */
class Query implements \Countable, \IteratorAggregate, QueryInterface
{
	/** @var \Spot\Mapper */
	protected $mapper;

	/** @var string */
	protected $entityName;

	/** @var array */
	protected $cache = array();

	/** @var array, Storage for query properties */
	public $fields = array();
	public $joins = array();
	public $conditions = array();
	public $search = array();
	public $order = array();
	public $group = array();
	public $having = array();

	/** @var string */
	public $datasource;

	/** @var int */
	public $limit;
	public $offset;

	/** @var array, Custom methods added by extensions or plugins */
	protected static $customMethods = array();

	/** @var array */
	protected static $resettable = array(
		'conditions', 'search', 'order', 'group', 'having', 'limit', 'offset'
	);

	/** @var array */
	protected $snapshot = array();

	/**
	 *	Constructor Method
	 *
	 *	@param Spot\Mapper
	 *	@param string $entityName Name of the entity to query on/for
	 */
	public function __construct(\Spot\Mapper $mapper, $entityName)
	{
		$this->mapper = $mapper;
		$this->entityName = $entityName;

		foreach (static::$resettable as $field) {
			$this->snapshot[$field] = $this->$field;
		}
	}

	/**
	 * Add a custom user method via closure or PHP callback
	 *
	 * @param string $method Method name to add
	 * @param callback $callback Callback or closure that will be executed when missing method call matching $method is made
	 * @throws InvalidArgumentException
	 */
	public static function addMethod($method, $callback)
	{
		if (!is_callable($callback)) {
			throw new \InvalidArgumentException("Second argument is expected to be a valid callback or closure.");
		}
		if (method_exists(__CLASS__, $method)) {
			throw new \InvalidArgumentException("Method '" . $method . "' already exists on " . __CLASS__);
		}
		self::$customMethods[$method] = $callback;
	}

	/**
	 * Run user-added callback
	 *
	 * @param string $method Method name called
	 * @param array $args Array of arguments used in missing method call
	 * @throws BadMethodCallException
	 */
	public function __call($method, $args)
	{
		if (isset(self::$customMethods[$method]) && is_callable(self::$customMethods[$method])) {
			$callback = self::$customMethods[$method];

			// Pass the current query object as the first parameter
			array_unshift($args, $this);

			return call_user_func_array($callback, $args);
		} else if (method_exists('\\Spot\\Entity\\Collection', $method)) {
			return $this->execute()->$method($args[0]);
		} else {
			throw new \BadMethodCallException("Method '" . __CLASS__ . "::" . $method . "' not found");
		}
	}

	/**
	 * Get current adapter object
	 * @return \Spot\Mapper
	 */
	public function mapper()
	{
		return $this->mapper;
	}

	/**
	 * Get current entity name query is to be performed on
	 * @return string
	 */
	public function entityName()
	{
		return $this->entityName;
	}

	/**
	 * Called from mapper's select() function
	 *
	 * @param mixed $fields (optional)
	 * @param string $source Data source name
	 * @return $this
	 */
	public function select($fields = '*', $datasource = null)
	{
		$this->fields = (is_string($fields) ? explode(',', $fields) : $fields);
		if (null !== $datasource) {
			$this->from($datasource);
		}
		return $this;
	}

	/**
	 * From
	 *
	 * @param string $datasource Name of the data source to perform a query on
	 * @return $this
	 */
	public function from($datasource = null)
	{
		$this->datasource = $datasource;
		return $this;
	}

	/**
	 * Find records with given conditions
	 * If all parameters are empty, find all records
	 *
	 * @param array $conditions Array of conditions in column => value pairs
	 * @return $this
	 */
	public function all(array $conditions = array())
	{
		return $this->where($conditions);
	}

	/**
	 * Add a table join (INNER, LEFT OUTER, RIGHT OUTER, FULL OUTER, CROSS)
	 * array('user.id', '=', 'profile.user_id') will compile to ON `user`.`id` = `profile`.`user_id`
	 *
	 * @param string $table, should be the name of the table to join to
	 * @param string|array $constraint, may be either a string or an array with three elements. If it
	 * is a string, it will be compiled into the query as-is, with no escaping. The
	 * recommended way to supply the constraint is as an array with three elements:
	 * array(column1, operator, column2)
	 * @param string $type, will be prepended to JOIN
	 * @param string $alias, table alias for the joined table
	 * @return $this
	 */
	public function join($table, $constraint, $type = 'INNER', $alias = null)
	{
		$type = strtoupper($type);
		switch ($type) {
			case 'INNER':
			case 'LEFT OUTER':
			case 'RIGHT OUTER':
			case 'FULL OUTER':
			case 'CROSS':
				break;
			default:
				$type = 'INNER';
		}

		// Add join array
		$this->joins[] = array(
			null === $alias ? array(trim($table)) : array(trim($table), trim($alias)),
			$constraints,
			$type,
		);

		return $this;
	}

	/**
	 * WHERE conditions
	 *
	 * @param array $conditions Array of conditions for this clause
	 * @param string $type Keyword that will separate each condition - 'AND', 'OR'
	 * @param string $setType Keyword that will separate the whole set of conditions - 'AND', 'OR'
	 * @return $this
	 */
	public function where(array $conditions = array(), $type = 'AND', $setType = 'AND')
	{
		// Don't add WHERE clause if array is empty (easy way to support dynamic request options that modify current query)
		if ($conditions) {
			$where = array();
			$where['conditions'] = $conditions;
			$where['type'] = $type;
			$where['setType'] = $setType;

			$this->conditions[] = $where;
		}
		return $this;
	}

	/**
	 * Convenience method for WHERE ... OR ...
	 * @param array $conditions
	 * @param string $type
	 * @return $this
	 */
	public function orWhere(array $conditions = array(), $type = 'AND')
	{
		return $this->where($conditions, $type, 'OR');
	}

	/**
	 * Convenience method for WHERE ... AND ...
	 * @param array $conditions
	 * @param string $type
	 * @return $this
	 */
	public function andWhere(array $conditions = array(), $type = 'AND')
	{
		return $this->where($conditions, $type, 'AND');
	}

	/**
	 * ORDER BY columns
	 *
	 * @param array $fields Array of field names to use for sorting
	 * @return $this
	 */
	public function order($fields = array())
	{
		$orderBy = array();
		$defaultSort = "ASC";

		if (is_array($fields)) {
			foreach ($fields as $field => $sort) {
				// Numeric index - field as array entry, not key/value pair
				if (is_numeric($field)) {
					$field = $sort;
					$sort = $defaultSort;
				}

				$this->order[$field] = strtoupper($sort);
			}
		} else {
			$this->order[$fields] = $defaultSort;
		}
		return $this;
	}

	/**
	 * GROUP BY clause
	 *
	 * @param array $fields Array of field names to use for grouping
	 * @return $this
	 */
	public function group(array $fields = array())
	{
		foreach ($fields as $field) {
			$this->group[] = $field;
		}
		return $this;
	}

	/**
	 * Having clause to filter results by a calculated value
	 *
	 * @param array $having Array (like where) for HAVING statement for filter records by
	 * @return $this
	 */
	public function having(array $having = array())
	{
		$this->having[] = array('conditions' => $having);
		return $this;
	}

	/**
	 * Limit executed query to specified amount of records
	 * Implemented at adapter-level for databases that support it
	 *
	 * @param int $limit Number of records to return
	 * @param int $offset Record to start at for limited result set
	 * @return $this
	 */
	public function limit($limit = 20, $offset = null)
	{
		$this->limit = $limit;
		$this->offset = $offset;
		return $this;
	}

	/**
	 * Offset executed query to skip specified amount of records
	 * Implemented at adapter-level for databases that support it
	 *
	 * @param int $offset Record to start at for limited result set
	 * @return $this
	 */
	public function offset($offset = 0)
	{
		$this->offset = $offset;
		return $this;
	}

	/**
	 * Return array of parameters in key => value format
	 *
	 * @return array Parameters in key => value format
	 */
	public function params()
	{
		$params = array();
		$ci = 0;

		// WHERE + HAVING
		$conditions = array_merge($this->conditions, $this->having);

		foreach ($conditions as $i => $data) {
			if (isset($data['conditions']) && is_array($data['conditions'])) {
				foreach ($data['conditions'] as $field => $value) {
					// Column name with comparison operator
					$colData = explode(' ', $field);
					$operator = '=';
					if (count($colData) > 2) {
						$operator = array_pop($colData);
						$colData = array(implode(' ', $colData), $operator);
					}
					$field = $colData[0];
					$params[$field . $ci] = $value;
					$ci++;
				}
			}
		}
		return $params;
	}

	/**
	 * SPL Countable function
	 * Called automatically when attribute is used in a 'count()' function call
	 * Caches results when there are no query changes
	 *
	 * @return int
	 */
	public function count()
	{
#		$obj = $this;

		// New scope with closure to get only PUBLIC properties of object instance (can't include cache property)
		$cacheKey = function() use($this) { return sha1(var_export(get_object_vars($this), true)) . "_count"; };
		$cacheResult = isset($this->cache[$cacheKey()]) ? $this->cache[$cacheKey()] : false;

		// Check cache
		if ($cacheResult) {
			$result = $cacheResult;
		} else {
			// Execute query
			$result = $this->mapper()->connection($this->entityName())->count($this);

			// Set cache
			$this->cache[$cacheKey()] = $result;
		}

		return is_numeric($result) ? $result : 0;
	}

	/**
	 * SPL IteratorAggregate function
	 * Called automatically when attribute is used in a 'foreach' loop
	 *
	 * @return Spot_Query_Set
	 */
	public function getIterator()
	{
		// Execute query and return result set for iteration
		$result = $this->execute();
		$this->reset();
		return ($result !== false) ? $result : array();
	}

	/**
	 * Reset the query back to its original state
	 * Called automatically after a 'foreach' loop
	 * @param $hardReset boolean Inidicate whether to reset the variables
	 *      to their initial state or just back to the snapshot() state
	 *
	 * @see getIterator
	 * @see snapshot
	 * @return Spot_Query_Set
	 */
	public function reset($hardReset = false)
	{
		foreach ($this->snapshot as $field => $value) {
			if ($hardReset) {
				// TODO: Look at an actual 'initialize' type
				// method that assigns all the defaults for
				// conditions, etc
				if (is_array($value)) {
					$this->$field = array();
				} else {
					$this->$field = null;
				}
			} else {
				$this->$field = $value;
			}
		}
		return $this;
	}

	/**
	 * Reset the query back to its original state
	 * Called automatically after a 'foreach' loop
	 *
	 * @see getIterator
	 * @return Spot_Query_Set
	 */
	public function snapshot()
	{
		foreach (static::$resettable as $field) {
			 $this->snapshot[$field] = $this->$field;
		}
		return $this;
	}

	/**
	 * Convenience function passthrough for Collection
	 *
	 * @param string $keyColumn
	 * @param string $valueColumn
	 * @return array
	 */
	public function toArray($keyColumn = null, $valueColumn = null)
	{
		$result = $this->execute();
		return ($result !== false) ? $result->toArray($keyColumn, $valueColumn) : array();
	}

	/**
	 * Return the first entity matched by the query
	 *
	 * @return mixed Spotentity on success, boolean false on failure
	 */
	public function first()
	{
		$result = $this->limit(1)->execute();
		return ($result !== false) ? $result->first() : false;
	}

	/**
	 * Execute and return query as a collection
	 *
	 * @return mixed Collection object on success, boolean false on failure
	 */
	public function execute()
	{
		return $this->mapper()->connection($this->entityName())->read($this);
	}
}
