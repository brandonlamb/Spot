<?php

/**
 * Spot\DialectInterface
 *
 * Interface for Spot\Adapter dialects
 *
 * @package Spot\Adapter
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot;

interface DialectInterface
{
	/**
	 * Get the Spot Adapter
	 * @return \Spot\AdapterInterface
	 */
	public function getAdapter();
	/**
	 * Set the Spot Adapter
	 * @param \Spot\AdapterInterface
	 */
	public function setAdapter(AdapterInterface $adapter);

	/**
	 * Creates a SELECT statement
	 *
	 * <code>
	 *  echo $dialect->select('', ['id', 'username']);
	 * </code>
	 *
	 * @param string $sqlQuery May be possible to have a cte or other leading expression
	 * @param array $fields
	 * @return string
	 */
	public function select($sqlQuery, array $fields);

	/**
	 * Creates FROM statement
	 *
	 * <code>
	 *  echo $dialect->from('SELECT *', 'blog_post');
	 * </code>
	 *
	 * @param string $sqlQuery
	 * @param string $tableName
	 * @return string
	 */
	public function from($sqlQuery, $tableName);

	/**
	 * Creates WHERE clause
	 *
	 * <code>
	 *  echo $dialect->where(
	 * 	    "SELECT * FROM blog_post",
	 *      [
	 *          ["conditions" => ["id :eq" => 1], "type" => "AND", "setType" => "AND"]
	 *      ]
	 *  );
	 * </code>
	 *
	 * Currently only supporting passing a single "(column = value)" or a set
	 * (column = value AND column = value). Multiple sets are supported via multiple where() calls.
	 * @param string $sqlQuery
	 * @param array $conditions
	 * @return string
	 */
	public function where($sqlQuery, array $conditions);

	/**
	 * Create a JOIN predicate
	 *
	 * <code>
	 *  echo $dialect->join(
	 *      "SELECT * FROM blog_post",
	 * 		["blog_comment", "blog_comment.post_id = blog_post.id"]
	 *  );
	 * </code>
	 *
	 * @param string $sqlQuery
	 * @param array $joins
	 * @return string
	 */
	public function join($sqlQuery, array $joins);

	/**
	 * Generates the SQL for GROUP BY clause
	 *
	 *<code>
	 * $sql = $dialect->order('SELECT * FROM blog', ['title', 'created']);
	 * echo $sql; // SELECT * FROM blog GROUP BY title, created
	 *</code>
	 *
	 * @param string $sqlQuery
	 * @param array $group
	 * @return string
	 */
	public function group($sqlQuery, array $group);

	/**
	 * Generates the SQL for ORDER BY clause
	 *
	 *<code>
	 * $sql = $dialect->order('SELECT * FROM blog', ['id' => 'asc']);
	 * echo $sql; // SELECT * FROM blog ORDER BY id ASC
	 *</code>
	 *
	 * @param string $sqlQuery
	 * @param array $order
	 * @return string
	 */
	public function order($sqlQuery, array $order);

	/**
	 * Generates the SQL for LIMIT clause
	 *
	 *<code>
	 * $sql = $dialect->limit('SELECT * FROM blog', 10);
	 * echo $sql; // SELECT * FROM blog LIMIT 10
	 *</code>
	 *
	 * @param string $sqlQuery
	 * @param int $number
	 * @return string
	 */
	public function limit($sqlQuery, $number);

	/**
	 * Generates the SQL for OFFSET clause
	 *
	 *<code>
	 * $sql = $dialect->offset('SELECT * FROM blog LIMIT 5', 10);
	 * echo $sql; // SELECT * FROM blog LIMIT 5 OFFSET 10
	 *</code>
	 *
	 * @param string $sqlQuery
	 * @param int $number
	 * @return string
	 */
	public function offset($sqlQuery, $number);
}
