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
    /**
     * @var \Spot\Mapper
     */
    protected $mapper;

    /**
     * @var string, The entity's class name
     */
    protected $entityName;

    /**
     * @var array, cached data
     */
    protected $cache = array();

    /**
     * @var string, cache key name
     */
    protected $cacheKey;

    /**
     * @var int, cache ttl/timeout in seconds
     */
    protected $cacheTtl = 30;

    /**
     * @var array, Select fields
     */
    public $fields = array();

    /**
     * @var array, Table joins with clauses
     */
    public $joins = array();

    /**
     * @var array, where conditions
     */
    public $conditions = array();

    /**
     * @var array, fulltext search conditions
     */
    public $search = array();

    /**
     * @var array, order by fields
     */
    public $order = array();

    /**
     * @var array, group by fields
     */
    public $group = array();

    /**
     * @var array, having conditions
     */
    public $having = array();

    /**
     * @var array, with conditions
     */
    public $with = array();

    /**
     * @var string, name of the table
     */
    public $datasource;

    /**
     * @var int, limit number
     */
    public $limit;

    /**
     * @var int, offset number
     */
    public $offset;

    /**
     * @var array, Custom methods added by extensions or plugins
     */
    protected static $customMethods = array();

    /**
     * @var array, which arrays can be reset when query object is reset
     */
    protected static $resettable = array(
        'conditions', 'search', 'order', 'group', 'having', 'limit', 'offset', 'with'
    );

    /**
     * @var array, when doing a reset, a snapshot copy is stored here
     */
    protected $snapshot = array();

    /**
     *  Constructor Method
     *  @param Spot\Mapper
     *  @param string $entityName Name of the entity to query on/for
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
     * Get current mapper
     * @return \Spot\Mapper
     */
    public function mapper()
    {
        return $this->mapper;
    }

    /**
     * Get current entity class name that the query is to be performed on
     * @return string
     */
    public function entityName()
    {
        return $this->entityName;
    }

    /**
     * The mapper's select() method chains to this method. Used to select
     * fields during the query.
     * @param mixed $fields (optional)
     * @param string $source Data source name (table)
     * @return self
     */
    public function select($fields = '*', $datasource = null)
    {
        // If calling this method, and the first fields index is * then we should clear this out
        count($this->fields) === 1 && $this->fields[0] === '*' && $this->fields = array();

        if (null === $fields) {
            $this->fields = array();
        } elseif (is_string($fields)) {
            foreach (explode(',', $fields) as $field) {
                $this->fields[] = trim($field);
            }
        } elseif (is_array($fields)) {
            $this->fields = array_merge($this->fields, $fields);
        }

        // Set the datasource (FROM) table
        if (null !== $datasource) {
            $this->from($datasource);
        }

        return $this;
    }

    /**
     * Specify the FROM table/datasource
     * @param string $datasource Name of the data source to perform a query on
     * @return self
     */
    public function from($datasource = null)
    {
        $this->datasource = $datasource;
        return $this;
    }

    /**
     * Find records with given conditions
     * If all parameters are empty, find all records
     * @param array $conditions Array of conditions in column => value pairs
     * @return self
     */
    public function all(array $conditions = array())
    {
        return $this->where($conditions);
    }

    /**
     * Add a table join (INNER, LEFT OUTER, RIGHT OUTER, FULL OUTER, CROSS)
     * array('user.id', '=', 'profile.user_id') will compile to ON `user`.`id` = `profile`.`user_id`
     *
     * @param string|array $table, should be the name of the table to join to
     * @param string|array $constraint, may be either a string or an array with three elements. If it
     * is a string, it will be compiled into the query as-is, with no escaping. The
     * recommended way to supply the constraint is as an array with three elements:
     * array(column1, operator, column2)
     * @param string $type, will be prepended to JOIN
     * @return self
     */
    public function join($table, $constraint = null, $type = 'INNER')
    {
        // If $table is passed as an array then assume we are receiving multiple join statements
        // Loop through each one and pass the parms to $this->join()
        if (is_array($table)) {
            foreach ($table as $join) {
                $type = isset($join[2]) ? $join[2] : 'INNER';
                $this->join($join[0], $join[1], $type);
            }
            return $this;
        }

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
        $this->joins[] = array(trim($table), $constraint, $type);

        return $this;
    }

    /**
     * Add an INNER JOIN
     * @param string $table
     * @param string $constraint
     * @return self
     */
    public function innerJoin($table, $constraint)
    {
        return $this->join($table, $constraint, 'INNER');
    }

    /**
     * Add a LEFT OUTER JOIN
     * @param string $table
     * @param string $constraint
     * @return self
     */
    public function leftOuterJoin($table, $constraint)
    {
        return $this->join($table, $constraint, 'LEFT OUTER');
    }

    /**
     * Add an RIGHT OUTER JOIN
     * @param string $table
     * @param string $constraint
     * @return self
     */
    public function rightOuterJoin($table, $constraint)
    {
        return $this->join($table, $constraint, 'RIGHT OUTER');
    }

    /**
     * Add an FULL OUTER JOIN
     * @param string $table
     * @param string $constraint
     * @return self
     */
    public function fullOuterJoin($table, $constraint)
    {
        return $this->join($table, $constraint, 'FULL OUTER');
    }

    /**
     * Add an CROSS JOIN
     * @param string $table
     * @param string $constraint
     * @return self
     */
    public function crossJoin($table, $constraint)
    {
        return $this->join($table, $constraint, 'CROSS');
    }

    /**
     * WHERE conditions
     * @param array $conditions Array of conditions for this clause
     * @param string $type Keyword that will separate each condition - 'AND', 'OR'
     * @param string $setType Keyword that will separate the whole set of conditions - 'AND', 'OR'
     * @return self
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
     * @return self
     */
    public function orWhere(array $conditions = array(), $type = 'AND')
    {
        return $this->where($conditions, $type, 'OR');
    }

    /**
     * Convenience method for WHERE ... AND ...
     * @param array $conditions
     * @param string $type
     * @return self
     */
    public function andWhere(array $conditions = array(), $type = 'AND')
    {
        return $this->where($conditions, $type, 'AND');
    }

    /**
     * Relations to be loaded non-lazily
     * @param mixed $relations Array/string of relation(s) to be loaded.  False to erase all withs.  Null to return existing $with value
     */
    public function with($relations = null)
    {
        if (is_null($relations)) {
            return $this->with;
        } elseif (is_bool($relations) && !$relations) {
            $this->with = array();
        }

        $entityName = $this->entityName();
        $entityRelations = array_keys($entityName::relations());

        foreach ((array) $relations as $idx => $relation) {
            $add = true;
            if (!is_numeric($idx) && isset($this->with[$idx])) {
                $add = $relation;
                $relation = $idx;
            }

            if ($add && in_array($relation, $entityRelations)) {
                $this->with[] = $relation;
            } elseif (!$add) {
                foreach (array_keys($this->with, $relation, true) as $key) {
                    unset($this->with[$key]);
                }
            }
        }

        $this->with = array_unique($this->with);
        return $this;
    }

    /**
     * ORDER BY columns
     * @param array $fields Array of field names to use for sorting
     * @return self
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
     * @param array $fields Array of field names to use for grouping
     * @return self
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
     * @param array $having Array (like where) for HAVING statement for filter records by
     * @return self
     */
    public function having(array $having = array())
    {
        $this->having[] = array('conditions' => $having);
        return $this;
    }

    /**
     * Limit executed query to specified amount of records
     * Implemented at adapter-level for databases that support it
     * @param int $limit Number of records to return
     * @param int $offset Record to start at for limited result set
     * @return self
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
     * @param int $offset Record to start at for limited result set
     * @return self
     */
    public function offset($offset = 0)
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Return array of parameters in key => value format
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
        $that = $this;

        // New scope with closure to get only PUBLIC properties of object instance (can't include cache property)
        $cacheParams = function() use ($that) {
            // This trick doesn't seem to work by itself in PHP 5.4...
            $props = get_object_vars($that);

            // Depends on protected/private properties starting with underscore ('_')
            $publics = array_filter(array_keys($props), function($key) {
                return strpos($key, '_') !== 0;
            });

            return array_intersect_key($props, array_flip($publics));
        };

        $cacheKey = sha1(var_export($cacheParams(), true)) . "_count";
        $cacheResult = isset($this->_cache[$cacheKey]) ? $this->_cache[$cacheKey] : false;

        // Check cache
        if ($cacheResult) {
            $result = $cacheResult;
        } else {
            // Execute query
            $result = $this->mapper()->connection($this->entityName())->count($this);

            // Set cache
            $this->_cache[$cacheKey] = $result;
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
     * return query as a string
     *
     * @return string
     */
    public function toString()
    {
        return $this->mapper()->connection($this->entityName())->getQuerySql($this);
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

    /**
     * Get/set cache key
     * @param string $key
     * @return string|bool
     */
    public function cacheKey($cacheKey = null)
    {
        null !== $cacheKey && $this->cacheKey = (string) $cacheKey;
        return null !== $this->cacheKey ? $this->cacheKey : false;
    }

    /**
     * Get/set the cache ttl
     * @param int $cacheTtl
     * @return int
     */
    public function cacheTtl($cacheTtl = null)
    {
        null !== $cacheTtl && $this->cacheTtl = (int) $cacheTtl;
        return $this->cacheTtl;
    }
}
