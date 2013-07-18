<?php
namespace Spot\Adapter;

/**
 * Adapter Interface
 *
 * @package Spot
 * @link http://spot.os.ly
 * @todo Add back in the migration methods
 */
interface AdapterInterface
{
	/**
	 * @param PDO|AdapterInterface $connection pre-existing raw connection object to be used
	 */
	public function __construct($connection);

	/**
	 * Get database connection
	 * @return \PDO
	 */
	public function connection();

	/**
	 * Get database DATE format for PHP date() function
	 * @return string
	 */
	public function dateFormat();

	/**
	 * Get database TIME format for PHP date() function
	 * @return string
	 */
	public function timeFormat();

	/**
	 * Get database full DATETIME for PHP date() function
	 * @return string
	 */
	public function dateTimeFormat();

	/**
	 * Get date in format that adapter understands for queries
	 * @return string
	 */
	public function date($format = null);

	/**
	 * Get time in format that adapter understands for queries
	 * @return string
	 */
	public function time($format = null);

	/**
	 * Get datetime in format that adapter understands for queries
	 * @return string
	 */
	public function dateTime($format = null);

	/**
	 * Escape/quote direct user input
	 * @param string $string
	 * @return string
	 */
	public function escape($string);

	/**
	 * Insert entity
	 */
	public function create($source, array $data, array $options = array());

	/**
	 * Read from data source using given query object
	 */
	public function read(\Spot\Query $query, array $options = array());

	/**
	 * Build Limit Query from data source using given query object
	 */
	public function statementLimit(\Spot\Query $query, array $options = array());

	/**
	 * Build Offset Query from data source using given query object
	 */
	public function statementOffset(\Spot\Query $query, array $options = array());

	/*
	 * Count number of rows in source based on conditions
	 */
	public function count(\Spot\Query $query, array $options = array());

	/**
	 * Update entity
	 */
	public function update($source, array $data, array $where = array(), array $options = array());

	/**
	 * Delete entity
	 */
	public function delete($source, array $where, array $options = array());

	/**
	 * Begin transaction
	 */
	public function beginTransaction();

	/**
	 * Commit transaction
	 */
	public function commit();

	/**
	 * Rollback transaction
	 */
	public function rollback();

	/**
	 * Migrate table structure changes to database
	 * @param string $table Table name
	 * @param array $fields Fields and their attributes as defined in the mapper
	 * @param array $options Options that may affect migrations or how tables are setup
	 * @return \Spot\Adapter\AbstractInterface
	 */
	public function migrate($table, array $fields, array $options = array());

	/**
	 * Create a database
	 * Will throw errors if user does not have proper permissions
	 */
	public function createDatabase($database);

	/**
	 * Drop an entire database
	 * Destructive and dangerous - drops entire table and all data
	 * Will throw errors if user does not have proper permissions
	 */
	public function dropDatabase($database);

	/**
	 * Truncate data source (table for SQL)
	 * Should delete all rows and reset serial/auto_increment keys to 0
	 * @param \Spot\Entity
	 * @return \Spot\Adapter\AdapterInterface
	 */
	public function truncateDatasource($source);

	/**
	 * Drop/delete data source (table for SQL)
	 * Destructive and dangerous - drops entire data source and all data
	 * @param \Spot\Entity
	 * @return \Spot\Adapter\AdapterInterface
	 */
	public function dropDatasource($source);
}
