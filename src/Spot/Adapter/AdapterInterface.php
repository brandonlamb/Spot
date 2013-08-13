<?php

namespace Spot\Adapter;

use Spot\QueryInterface;

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
     * Pass a pre-existing PDO connection
     * @param \PDO $connection
     */
    public function __construct($connection);

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
     * Get database connection
     * @return \PDO
     */
    public function connection();

    /**
     * Escape/quote direct user input
     * @param string $string
     * @return string
     */
    public function escape($string);

    /**
     * Escape/quote direct user input
     * @param string $field
     * @return string
     */
    public function escapeField($field);

    /**
     * Prepare an SQL statement
     * @param string $sql
     * @return \PDOStatement
     * @throws \Spot\Exception\Adapter
     */
    public function prepare($sql);

    /**
     * Find records with custom SQL query
     * @param string $sql SQL query to execute
     * @param array $binds Array of bound parameters to use as values for query
     * @return \PDOStatement|bool
     * @throws \Spot\Exception\Adapter
     */
    public function query($sql, array $binds = array());

    /**
     * Create new row object with set properties
     * @param string $datasource
     * @param array $data
     * @param array $options
     * @return mixed
     * @throws \Spot\Exception\Datasource\Missing|\Spot\Exception\Adapter
     */
    public function create($datasource, array $data, array $options = array());

    /**
     * Build a select statement in SQL
     * Can be overridden by adapters for custom syntax
     *
     * @param \Sbux\QueryInterface $query
     * @param array $options
     * @throws \Spot\Exception\Adapter
     */
    public function read(QueryInterface $query, array $options = array());

    /**
     * Count number of rows in source based on conditions
     * @param \Spot\QueryInterface $query
     * @param array $options
     * @throws \Spot\Exception\Adapter
     */
    public function count(QueryInterface $query, array $options = array());

    /**
     * Update entity
     * @param string $datasource
     * @param array $data
     * @param data $where
     * @param array $options
     * @throws \Spot\Exception\Adapter
     */
    public function update($datasource, array $data, array $where = array(), array $options = array());

    /**
     * Delete entities matching given conditions
     * @param string $datasource Name of data source
     * @param array $data
     * @param array $options
     * @throws \Spot\Exception\Adapter
     */
    public function delete($datasource, array $data, array $options = array());

    /**
     * Begin transaction
     * @return bool
     */
    public function beginTransaction();

    /**
     * Commit transaction
     * @return bool
     */
    public function commit();

    /**
     * Rollback transaction
     * @return bool
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

    /**
     * Using the query object passed, return the parsed sql query string.
     * This will only return the sql for the select building. Separate methods
     * exist for insert
     * @param \Sbux\QueryInterface $query
     * @return string
     */
    public function getQuerySql(QueryInterface $query);

    /**
     * Return insert statement
     * @param string $datasource
     * @param array $data
     * @param array $binds
     * @return string
     */
    public function getInsertSql($datasource, array $data, array $binds, array $options);

    /**
     * Return update statement
     * @param string $datasource
     * @param array $placeholders
     * @param string $conditions
     * @return string
     */
    public function getUpdateSql($datasource, array $placeholders, $conditions);

    /**
     * Return fields as a string for a query statement
     * @param array $fields
     * @return string
     */
    public function getFieldsSql(array $fields = array());

    /**
     * Add a table join (INNER, LEFT OUTER, RIGHT OUTER, FULL OUTER, CROSS)
     * array('user.id', '=', 'profile.user_id') will compile to ON `user`.`id` = `profile`.`user_id`
     *
     * @param array $joins
     * @return string
     */
    public function getJoinsSql(array $joins = array());

    /**
     * Builds an SQL string given conditions
     * @param array $conditions
     * @param int $ci
     * @return string
     * @throws \Spot\Exception\Adapter
     */
    public function getConditionsSql(array $conditions = array(), $ci = 0);

    /**
     * Returns array of binds to pass to query function
     * @param array $conditions
     * @param bool $ci
     */
    public function getBinds(array $conditions = array(), $ci = false);

    /**
     * Return sql statement for GROUP BY
     * @param array $group
     * @return string
     */
    public function getGroupSql(array $group);

    /**
     * Build ORDER BY string
     * @param array $order
     */
    public function getOrderSql(array $order);

    /**
     * Build Limit query from data source using given query object
     * @param int $limit
     * @return string
     */
    public function getLimitSql($limit);

    /**
     *  Build Offset query from data source using integer passed
     * @param int $offset
     * @return string
     */
    public function getOffsetSql($offset);

    /**
     * Fetch the last insert id
     * @param string $sequence
     * @return mixed
     */
    public function lastInsertId($sequence = null);
}
