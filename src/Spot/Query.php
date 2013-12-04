<?php

/**
 * Query Object - Used to build adapter-independent queries PHP-style
 *
 * @package Spot
 * @author Vance Lucas <vance@vancelucas.com>
 * @link http://spot.os.ly
 */

namespace Spot;

use Countable,
    IteratorAggregate;

class Query implements Countable, IteratorAggregate, QueryInterface
{
    /**
     * @var array, Custom methods added by extensions or plugins
     */
    protected static $customMethods = [];

    /**
     * @var array, which arrays can be reset when query object is reset
     */
    protected static $resettable = [
        'conditions',
        'search',
        'orderBy',
        'groupBy',
        'having',
        'limit',
        'offset',
        'with',
    ];

    /**
     * @var \Spot\Mapper
     */
    protected $mapper;

    /**
     * @var string, The entity's class name
     */
    protected $entityName;

    /**
     * @var string, name of the table
     */
    protected $tableName;

    /**
     * @var array, Select fields
     */
    protected $fields = [];

    /**
     * @var array, Table joins with clauses
     */
    protected $joins = [];

    /**
     * @var array, where conditions
     */
    protected $conditions = [];

    /**
     * @var array, fulltext search conditions
     */
    protected $search = [];

    /**
     * @var array, order by fields
     */
    protected $orderBy = [];

    /**
     * @var array, group by fields
     */
    protected $groupBy = [];

    /**
     * @var array, having conditions
     */
    protected $having = [];

    /**
     * @var array, with conditions
     */
    protected $with = [];

    /**
     * @var int, limit number
     */
    protected $limit;

    /**
     * @var int, offset number
     */
    protected $offset;

    /**
     * @var array, when doing a reset, a snapshot copy is stored here
     */
    protected $snapshot = [];

    /**
     * @var array
     */
    protected $cache = [];

    /**
     *  Constructor Method
     *  @param \Spot\Mapper
     *  @param string $entityName Name of the entity to query on/for
     */
    public function __construct(Mapper $mapper, $entityName = null)
    {
        $this->mapper = $mapper;
        $this->entityName = (string) $entityName;

        foreach (self::$resettable as $field) {
            $this->snapshot[$field] = $this->$field;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Run user-added callback
     * @param string $method Method name called
     * @param array $args Array of arguments used in missing method call
     * @throws \BadMethodCallException
     */
    public function __call($method, $args)
    {
        if (isset(self::$customMethods[$method]) && is_callable(self::$customMethods[$method])) {
            $callback = self::$customMethods[$method];

            // Pass the current query object as the first parameter
            array_unshift($args, $this);

            return call_user_func_array($callback, $args);
        } else if (method_exists('\\Spot\\Entity\\Resultset', $method)) {
            return $this->execute()->$method($args[0]);
        } else {
            throw new \BadMethodCallException("Method '" . __METHOD__ . "' not found");
        }
    }

    /**
     * Get data mapper
     * @return \Spot\Mapper
     */
    public function getMapper()
    {
        return $this->mapper;
    }

    /**
     * Get entity class name that the query is to be performed on
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * Get SELECT fields
     * @return array
     */
    public function getFields()
    {
        return (array) $this->fields;
    }

    /**
     * Get the table name
     * @return string
     */
    public function getTableName()
    {
        return (string) $this->tableName;
    }

    /**
     * Get JOINs
     * @return array
     */
    public function getJoins()
    {
        return (array) $this->joins;
    }

    /**
     * Get WHERE conditions
     * @return array
     */
    public function getConditions()
    {
        return (array) $this->conditions;
    }

    /**
     * Get HAVING
     * @return array
     */
    public function getHaving()
    {
        return (array) $this->having;
    }

    /**
     * Get GROUP BY
     * @return array
     */
    public function getGroupBy()
    {
        return (array) $this->groupBy;
    }

    /**
     * Get ORDER BY
     * @return array
     */
    public function getOrderBy()
    {
        return (array) $this->orderBy;
    }

    /**
     * Get LIMIT
     * @return int|null
     */
    public function getLimit()
    {
        return (!is_numeric($this->limit)) ? null : (int) $this->limit;
    }

    /**
     * Get OFFSET
     * @return int|null
     */
    public function getOffset()
    {
        return (!is_numeric($this->offset)) ? null : (int) $this->offset;
    }

    /**
     * Return array of parameters in key => value format
     * @return array Parameters in key => value format
     */
    public function getParameters()
    {
        // WHERE + HAVING
        return array_merge($this->conditions, $this->having);
    }

    /**
     * Get with array for relations
     * @return array
     */
    public function getWith()
    {
        return (array) $this->with;
    }

    /**
     * The mapper's select() method chains to this method. Used to select
     * fields during the query.
     * @param mixed $fields (optional)
     * @return \Spot\QueryInterface
     */
    public function select($fields = '*')
    {
        // If calling this method, and the first fields index is * then we should clear this out
        count($this->fields) === 1 && $this->fields[0] === '*' && $this->fields = [];

        if (null === $fields) {
            $this->fields = [];
        } elseif (is_string($fields)) {
            foreach (explode(',', $fields) as $field) {
                $this->fields[] = trim($field);
            }
        } elseif (is_array($fields)) {
            $this->fields = array_merge($this->fields, $fields);
        }

        return $this;
    }

    /**
     * Specify the FROM table
     * @param string $table Name of the table to perform a query on
     * @return \Spot\QueryInterface
     */
    public function from($table)
    {
        $this->tableName = (string) $table;
        return $this;
    }

    /**
     * Add a table join (INNER, LEFT OUTER, RIGHT OUTER, FULL OUTER, CROSS)
     * ['user.id', '=', 'profile.user_id'] will compile to ON `user`.`id` = `profile`.`user_id`
     *
     * @param string|array $table, should be the name of the table to join to
     * @param string|array $constraint, may be either a string or an array with three elements. If it
     * is a string, it will be compiled into the query as-is, with no escaping. The
     * recommended way to supply the constraint is as an array with three elements:
     * [column1, operator, column2]
     * @param string $type, will be prepended to JOIN
     * @return \Spot\QueryInterface
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
        $this->joins[] = [trim($table), $constraint, $type];

        return $this;
    }

    /**
     * Add an INNER JOIN
     * @param string $table
     * @param string $constraint
     * @return \Spot\QueryInterface
     */
    public function innerJoin($table, $constraint)
    {
        return $this->join($table, $constraint, 'INNER');
    }

    /**
     * Add a LEFT OUTER JOIN
     * @param string $table
     * @param string $constraint
     * @return \Spot\QueryInterface
     */
    public function leftOuterJoin($table, $constraint)
    {
        return $this->join($table, $constraint, 'LEFT OUTER');
    }

    /**
     * Add an RIGHT OUTER JOIN
     * @param string $table
     * @param string $constraint
     * @return \Spot\QueryInterface
     */
    public function rightOuterJoin($table, $constraint)
    {
        return $this->join($table, $constraint, 'RIGHT OUTER');
    }

    /**
     * Add an FULL OUTER JOIN
     * @param string $table
     * @param string $constraint
     * @return \Spot\QueryInterface
     */
    public function fullOuterJoin($table, $constraint)
    {
        return $this->join($table, $constraint, 'FULL OUTER');
    }

    /**
     * Add an CROSS JOIN
     * @param string $table
     * @param string $constraint
     * @return \Spot\QueryInterface
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
     * @return \Spot\QueryInterface
     */
    public function where(array $conditions = [], $type = 'AND', $setType = 'AND')
    {
        // Don't add WHERE clause if array is empty (easy way to support dynamic request options that modify current query)
        if (!empty($conditions)) {
            $where = [];
            $where['conditions'] = $conditions;
            $where['type'] = strtoupper($type);
            $where['setType'] = strtoupper($setType);

            $this->conditions[] = $where;
        }
        return $this;
    }

    /**
     * Convenience method for WHERE ... OR ...
     * @param array $conditions
     * @param string $type
     * @return \Spot\QueryInterface
     */
    public function orWhere(array $conditions = [], $type = 'AND')
    {
        return $this->where($conditions, $type, 'OR');
    }

    /**
     * Convenience method for WHERE ... AND ...
     * @param array $conditions
     * @param string $type
     * @return \Spot\QueryInterface
     */
    public function andWhere(array $conditions = [], $type = 'AND')
    {
        return $this->where($conditions, $type, 'AND');
    }

    /**
     * ORDER BY columns
     * @param array $fields Array of field names to use for sorting
     * @return \Spot\QueryInterface
     */
    public function order($fields = [])
    {
        $defaultSort = "ASC";

        if (is_array($fields)) {
            foreach ($fields as $field => $sort) {
                // Numeric index - field as array entry, not key/value pair
                if (is_numeric($field)) {
                    $field = $sort;
                    $sort = $defaultSort;
                }

                $this->orderBy[$field] = strtoupper($sort);
            }
        } else {
            $this->orderBy[$fields] = $defaultSort;
        }
        return $this;
    }

    /**
     * GROUP BY clause
     * @param array $fields Array of field names to use for grouping
     * @return \Spot\QueryInterface
     */
    public function group(array $fields = [])
    {
        foreach ($fields as $field) {
            $this->groupBy[] = $field;
        }
        return $this;
    }

    /**
     * Having clause to filter results by a calculated value
     * @param array $having Array (like where) for HAVING statement for filter records by
     * @return \Spot\QueryInterface
     */
    public function having(array $having = [])
    {
        $this->having[] = ['conditions' => $having];
        return $this;
    }

    /**
     * Limit executed query to specified amount of records
     * Implemented at adapter-level for databases that support it
     * @param int $limit Number of records to return
     * @param int $offset Record to start at for limited result set
     * @return \Spot\QueryInterface
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
     * @return \Spot\QueryInterface
     */
    public function offset($offset = 0)
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Relations to be loaded non-lazily
     * @param mixed $relations Array/string of relation(s) to be loaded.  False to erase all withs.  Null to return existing $with value
     */
    public function with($relations = null)
    {
        if (is_null($relations)) {
            return $this->with;
        } else if (is_bool($relations) && !$relations) {
            $this->with = [];
        } else if (is_string($relations)) {
            $relations = [$relations];
        }

        $entityName = $this->getEntityName();
        $entityRelations = array_keys($entityName::getMetaData()->getRelations());

        foreach ($relations as $idx => $relation) {
            $add = true;
            if (!is_numeric($idx) && isset($this->with[$idx])) {
                $add = $relation;
                $relation = $idx;
            }

            if ($add && in_array($relation, $entityRelations)) {
                $this->with[] = $relation;
            } else if (!$add) {
                foreach (array_keys($this->with, $relation, true) as $key) {
                    unset($this->with[$key]);
                }
            }
        }

        $this->with = array_unique($this->with);
        return $this;
    }

    /**
     * SPL IteratorAggregate function
     * Called automatically when attribute is used in a 'foreach' loop
     *
     * @return \Spot\Entity\ResultsetInterface
     */
    public function getIterator()
    {
        // Execute query and return result set for iteration
        $result = $this->execute();
        return ($result !== false) ? $result : [];
    }

    /**
     * Return the first entity matched by the query
     * @return mixed Spotentity on success, boolean false on failure
     */
    public function first(array $conditions = [])
    {
        $result = $this->where($conditions)->limit(1)->execute();
        return ($result !== false) ? $result->first() : false;
    }

    /**
     * Find records with given conditions
     * If all parameters are empty, find all records
     * @param array $conditions Array of conditions in column => value pairs
     * @return \Spot\QueryInterface
     */
    public function all(array $conditions = [])
    {
        return $this->where($conditions);
    }

    /**
     * Convenience function passthrough for ResultSet
     * @return array
     */
    public function toArray()
    {
        $result = $this->execute();
        return ($result !== false) ? $result->toArray() : [];
    }

    /**
     * return query as a string
     * @return string
     */
    public function toString()
    {
        return $this->mapper->getDi()->get($this->mapper->getAdapterName())->getQuerySql($this);
    }

    /**
     * Execute and return query as a resultset
     * @return mixed ResultSet object on success, boolean false on failure
     */
    public function execute()
    {
        return $this->mapper->getAdapter()->readEntity($this);
    }

    /**
     * SPL Countable function
     * Called automatically when attribute is used in a 'count()' function call
     * Caches results when there are no query changes
     *
     * @return int
     * @todo - this is broken
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
        $cacheResult = isset($this->cache[$cacheKey]) ? $this->cache[$cacheKey] : false;

        // Check cache
        if ($cacheResult) {
            $result = $cacheResult;
        } else {
            // Execute query
            $result = $this->mapper->getDi()->get($this->mapper->getAdapterName())->count($this);

            // Set cache
            $this->cache[$cacheKey] = $result;
        }

        return is_numeric($result) ? $result : 0;
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
     * Reset the query back to its original state
     * Called automatically after a 'foreach' loop
     *
     * @see getIterator
     * @return Spot_Query_Set
     */
    public function snapshot()
    {
        foreach (self::$resettable as $field) {
             $this->snapshot[$field] = $this->$field;
        }
        return $this;
    }
}
