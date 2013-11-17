<?php

/**
 * Adapter Interface
 *
 * @package Spot
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot;

interface AdapterInterface
{
    /**
     * Get internal PDO handle
     * @return \PDO
     */
    public function getInternalHandler();

    /**
     * Set the internal PDO handle
     * @param \PDO $pdo
     */
    public function setInternalHandler(\PDO $pdo);

    /**
     * Escape/quote direct user input
     * @param string $string
     * @return string
     */
    public function quote($string);

    /**
     * Escapes a column/table/schema name
     *
     *<code>
     *  $escapedTable = $connection->escapeIdentifier('blog_post');
     *  $escapedTable = $connection->escapeIdentifier(array('blog_post', 'id'));
     *</code>
     *
     * @param string|array $identifier
     * @return string
     */
    public function escapeIdentifier($identifier);

    /**
     * Prepare an SQL statement
     * @param string $sqlStatement
     * @return \PDOStatement
     * @throws \Spot\Exception\Adapter
     */
    public function prepare($sqlStatement);

    /**
     * Find records with custom SQL query
     * @param string $sqlStatement SQL query to execute
     * @param array $binds Array of bound parameters to use as values for query
     * @return \PDOStatement|bool
     * @throws \Spot\Exception\Adapter
     */
    public function query($sqlStatement, array $binds = []);

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
     * Create new row object with set properties
     * @param string $datasource
     * @param array $data
     * @param array $options
     * @return mixed
     * @throws \Spot\Exception\Datasource\Missing|\Spot\Exception\Adapter
     */
    public function create($datasource, array $data, array $options = []);

    /**
     * Build a select statement in SQL
     * Can be overridden by adapters for custom syntax
     *
     * @param \Sbux\QueryInterface $query
     * @param array $options
     * @throws \Spot\Exception\Adapter
     */
    public function read(QueryInterface $query, array $options = []);

    /**
     * Update entity
     * @param string $datasource
     * @param array $data
     * @param data $where
     * @param array $options
     * @throws \Spot\Exception\Adapter
     */
    public function update($datasource, array $data, array $where = [], array $options = []);

    /**
     * Delete entities matching given conditions
     * @param string $datasource Name of data source
     * @param array $data
     * @param array $options
     * @throws \Spot\Exception\Adapter
     */
    public function delete($datasource, array $data, array $options = []);

    /**
     * Count number of rows in source based on conditions
     * @param \Spot\QueryInterface $query
     * @param array $options
     * @throws \Spot\Exception\Adapter
     */
    public function count(QueryInterface $query, array $options = []);






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
     * Build SELECT statement from fields
     *
     * @param string $sqlQuery
     * @param array $fields
     * @return string
     */
    public function select($sqlQuery, array $fields = []);

    /**
     * Build FROM statement from table names
     *
     * @param string $sqlQuery
     * @param string $tableName
     * @return string
     */
    public function from($sqlQuery, $tableName);

    /**
     * Builds an SQL string given conditions
     *
     * @param string $sqlQuery
     * @param array $conditions
     * @param int $ci
     * @return string
     * @throws \Spot\Exception\Adapter
     */
    public function where($sqlQuery, array $conditions = []);

    /**
     * Add a table join (INNER, LEFT OUTER, RIGHT OUTER, FULL OUTER, CROSS)
     * array('user.id', '=', 'profile.user_id') will compile to ON `user`.`id` = `profile`.`user_id`
     *
     * @param array $joins
     * @return string
     */
    public function getJoinsSql(array $joins = []);


    /**
     * Appends GROUP BY clause to $sqlQuery argument
     *
     * <code>
     *  echo $connection->group("SELECT * FROM blog_post", ["title"]);
     * </code>
     *
     * @param string $sqlQuery
     * @param array $group
     * @return string
     */
    public function group($sqlQuery, array $group);

    /**
     * Appends ORDER BY clause to $sqlQuery argument
     *
     * <code>
     *  echo $connection->order("SELECT * FROM blog_post", ["created" => "asc"]);
     * </code>
     *
     * @param string $sqlQuery
     * @param array $order
     * @return string
     */
    public function order($sqlQuery, array $order);

    /**
     * Appends a LIMIT clause to $sqlQuery argument
     *
     * <code>
     *  echo $connection->limit("SELECT * FROM blog_post", 5);
     * </code>
     *
     * @param string $sqlQuery
     * @param int $number
     * @return string
     */
    public function limit($sqlQuery, $number);

    /**
     * Appends a OFFSET clause to $sqlQuery argument
     *
     * <code>
     *  echo $connection->offset("SELECT * FROM blog_post LIMIT 5", 10);
     * </code>
     *
     * @param string $sqlQuery
     * @param int $number
     * @return string
     */
    public function offset($sqlQuery, $number);

    /**
     * Returns array of binds to pass to query function
     * @param array $conditions
     * @param bool $ci
     */
    public function getBinds(array $conditions = [], $ci = false);

    /**
     * Fetch the last insert id
     * @param string $sequence
     * @return mixed
     */
    public function lastInsertId($sequence = null);
}
