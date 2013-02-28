<?php
namespace Spot;

/**
 * Query interface
 *
 * @package Spot
 * @link http://spot.os.ly
 * @link http://github.com/actridge/Spot
 */
interface QueryInterface
{
	/**
	 * Constructor
	 *
	 * @param object $adapter
	 * @return string
	 */
	public function __construct(\Spot\Mapper $mapper, $entityName);

	/**
	 * Called from mapper's select() function
	 *
	 * @param mixed $fields (optional)
	 * @param string $table Table name
	 * @return string
	 */
	public function select($fields = '*', $table);

	/**
	 * From
	 *
	 * @param string $table Name of the table to perform the SELECT query on
	 */
	public function from($table = null);

	/**
	 * Add a table join (INNER, LEFT OUTER, RIGHT OUTER, FULL OUTER, CROSS)
	 * array('user.id', '=', 'profile.user_id') will compile to ON `user`.`id` = `profile`.`user_id`
	 *
	 * @param string $table, should be the name of the table to join to
	 * @param string $constraint, may be either a string or an array with three elements. If it
	 * is a string, it will be compiled into the query as-is, with no escaping. The
	 * recommended way to supply the constraint is as an array with three elements:
	 * array(column1, operator, column2)
	 * @param string $type, will be prepended to JOIN
	 * @param string $alias, table alias for the joined table
	 * @return Query
	 */
	public function join($table, $constraint, $type = 'INNER')

	/**
	 * WHERE conditions
	 *
	 * @param array $conditions Array of conditions for this clause
	 * @param string $type Keyword that will separate each condition - 'AND', 'OR'
	 * @param string $setType Keyword that will separate the whole set of conditions - 'AND', 'OR'
	 * @return $this
	 */
	public function where(array $conditions = array(), $type = 'AND', $setType = 'AND');

	/**
	 * Convenience methods for WHERE conditions
	 *
	 * @param array $conditions Array of conditions for this clause
	 * @param string $type Keyword that will separate each condition - 'AND', 'OR'
	 * @return $this
	 */
	public function orWhere(array $conditions = array(), $type = 'AND');
	public function andWhere(array $conditions = array(), $type = 'AND');

	/**
	 * ORDER BY columns
	 * @param array $fields
	 */
	public function order($fields = array());

	/**
	 * GROUP BY columns
	 * @param array $fields
	 */
	public function group(array $fields = array());

	/**
	 * LIMIT query or result set
	 * @param int $limit
	 * @param int $offset
	 */
	public function limit($limit = 20, $offset = null);

	/**
	 * HAVING query or result set
	 * @param array $having
	 */
	public function having(array $having = array());
}
