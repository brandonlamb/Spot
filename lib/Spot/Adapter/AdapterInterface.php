<?php
namespace Spot\Adapter;

/**
 * Adapter Interface
 *
 * @package Spot
 * @link http://spot.os.ly
 */
interface AdapterInterface
{
	/**
	 * @param PDO|PdoInterface $connection pre-existing raw connection object to be used
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
}
