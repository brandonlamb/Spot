<?php

/**
 * Spot Abstract Adapter that wraps a PDO resource
 *
 * @package Spot
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Db;

use Spot\QueryInterface;

abstract class AbstractAdapter
{
    /**
     * @var string Type of database system the adapter is used for
     */
    protected $type;

    /**
     * @var string Name of the dialect type used
     */
    protected $dialectType;

    /**
     * @var \Spot\Db\DialectInterface The dialect to use
     */
    protected $dialect;

    /**
     * @var PDO Internal database handler
     */
    protected $pdo;

    /**
     * {@inheritdoc}
     */
    public function __construct($pdo)
    {
        $this->pdo = $pdo;

        $dialectClass = '\\Spot\\Db\\Dialect\\' . $this->dialectType;
        $this->dialect = new $dialectClass($this);
    }

    /**
     * {@inheritDoc}
     */
    public function getInternalHandler()
    {
        return $this->pdo;
    }

    /**
     * {@inheritDoc}
     */
    public function setInternalHandler(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Gets the dialect used to produce the SQL
     * @return \Spot\Db\DialectInterface
     */
    public function getDialect()
    {
        return $this->dialect;
    }

    /**
     * Sets the dialect used to produce the SQL
     * @param \Spot\Db\DialectInterface
     */
    public function setDialect(DialectInterface $dialect)
    {
        $this->dialect = $dialect;
    }

    /**
     * {@inheritdoc}
     */
    public function quote($string)
    {
        return $this->pdo->quote($string);
    }

    /**
     * {@inheritDoc}
     */
    public function escapeIdentifier($identifier)
    {
        if (is_array($identifier)) {
            return '"' . $identifier[0] . '"."' . $identifier[1] . '"';
        }
        return '"' . $identifier . '"';
    }

    /**
     * {@inheritdoc}
     */
    public function prepare($sqlStatement)
    {
        return $this->pdo->prepare($sqlStatement);
    }

    /**
     * {@inheritdoc}
     */
    public function query($sqlStatement, array $binds = [])
    {
        // Prepare and execute query
        if ($stmt = $this->pdo->prepare($sqlStatement)) {
            $results = $stmt->execute($binds);
            return ($results === true) ? $stmt : false;
        } else {
            throw new \Exception(__METHOD__ . " Error: Unable to execute SQL query - failed to create prepared statement from given SQL");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * {@inheritdoc}
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * {@inheritdoc}
     */
    public function rollback()
    {
        return $this->pdo->rollBack();
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId($sequence = null)
    {
        return $this->pdo->lastInsertId($sequence);
    }

    /**
     * {@inheritdoc}
     */
    public function insert($tableName, array $columns, array $binds, array $options)
    {
        return $this->dialect->insert($tableName, $columns, $binds, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function update($tableName, array $placeholders, $conditions)
    {
        return $this->dialect->update($tableName, $placeholders, $conditions);
    }

    /**
     * {@inheritDoc}
     */
    public function getQuerySql(QueryInterface $query)
    {
        $sqlQuery = $this->select(null, $query->getFields());
        $sqlQuery = $this->from($sqlQuery, $query->getTableName());
        $sqlQuery = $this->join($sqlQuery, $query->getJoins());
        $sqlQuery = $this->where($sqlQuery, $query->getConditions());
        $sqlQuery = $this->group($sqlQuery, $query->getGroupBy());
        $sqlQuery = $this->order($sqlQuery, $query->getOrderBy());
        $sqlQuery = $this->limit($sqlQuery, $query->getLimit());
        $sqlQuery = $this->offset($sqlQuery, $query->getOffset());

        if ($query->getHaving()) {
            $sqlQuery = $this->where($sqlQuery, $query->getHaving());
        }

        return $sqlQuery;
    }

    /**
     * {@inheritDoc}
     */
    public function getDeleteSql(QueryInterface $query)
    {
        $sqlQuery = $this->select(null, $query->getFields());
        $sqlQuery = $this->from($sqlQuery, $query->getTableName());
        $sqlQuery = $this->join($sqlQuery, $query->getJoins());
        $sqlQuery = $this->where($sqlQuery, $query->getConditions());
        $sqlQuery = $this->group($sqlQuery, $query->getGroupBy());
        $sqlQuery = $this->order($sqlQuery, $query->getOrderBy());
        $sqlQuery = $this->limit($sqlQuery, $query->getLimit());
        $sqlQuery = $this->offset($sqlQuery, $query->getOffset());

        if ($query->getHaving()) {
            $sqlQuery = $this->where($sqlQuery, $query->getHaving());
        }

        return $sqlQuery;
    }

    /**
     * {@inheritdoc}
     */
    public function select($sqlQuery, array $fields = [])
    {
        return $this->dialect->select($sqlQuery, $fields);
    }

    /**
     * {@inheritDoc}
     */
    public function from($sqlQuery, $tableName)
    {
        return $this->dialect->from($sqlQuery, $tableName);
    }

    /**
     * {@inheritdoc}
     * @todo BETWEEN condition not filled in
     */
    public function where($sqlQuery, array $conditions = [])
    {
        return $this->dialect->where($sqlQuery, $conditions);
    }

    /**
     * {@inheritdoc}
     */
    public function join($sqlQuery, array $joins = [])
    {
        return $this->dialect->join($sqlQuery, $joins);
    }

    /**
     * {@inheritdoc}
     */
    public function group($sqlQuery, array $group)
    {
        return $this->dialect->group($sqlQuery, $group);
    }

    /**
     * {@inheritDoc}
     */
    public function order($sqlQuery, array $order)
    {
        return $this->dialect->order($sqlQuery, $order);
    }

    /**
     * {@inheritDoc}
     */
    public function limit($sqlQuery, $number)
    {
        return $this->dialect->limit($sqlQuery, $number);
    }

    /**
     * {@inheritDoc}
     */
    public function offset($sqlQuery, $number)
    {
        return $this->dialect->offset($sqlQuery, $number);
    }

    /**
     * {@inheritdoc}
     */
    public function createEntity($tableName, array $data, array $options = [])
    {
        $columns = [];
        $binds = [];

        foreach ($data as $key => $value) {
            $columns[] = $key;

            if ($value['bindType'] === Column::BIND_SKIP) {
                $binds[] = $value['value'];
            } else if ($value['bindType'] === Column::BIND_PARAM_NULL && null === $value['value']) {
                $binds[] = 'NULL';
            } else {
                $binds[] = ':' . $key;
            }
        }

        $sql = $this->insert($tableName, $columns, $binds, $options);

        try {
            // Prepare update query
            $stmt = $this->pdo->prepare($sql);

            if ($stmt) {
                // Bind each parameter
                foreach ($data as $key => $value) {
                    $param = ':' . $key;

                    switch ($value['bindType']) {
                        case Column::BIND_SKIP:
                            break;
                        case Column::BIND_PARAM_NULL:
                            if (null === $value['value']) {
                                $stmt->bindValue($param, null, \PDO::PARAM_NULL);
                                break;
                            }
                        case Column::BIND_PARAM_BOOL:
                            $stmt->bindValue($param, $value['value'], \PDO::PARAM_BOOL);
                            break;
                        case Column::BIND_PARAM_INT:
                            $stmt->bindValue($param, $value['value'], \PDO::PARAM_INT);
                            break;
                        case Column::BIND_PARAM_DECIMAL:
                        case Column::BIND_PARAM_STR:
                        default:
                            $stmt->bindValue($param, $value['value'], \PDO::PARAM_STR);
                            break;
                    }
                }

                // Execute
                if ($stmt->execute()) {
                    // Use 'id' if PK exists, otherwise returns true
                    $id = $this->lastInsertId($options['sequence']);
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
                throw new \Spot\Exception\Datasource\Missing("Table or datasource '" . $tableName . "' does not exist");
            }

            // Throw new Spot exception
            throw new \Spot\Exception\Adapter(__METHOD__ . ': ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function readEntity(QueryInterface $query, array $options = [])
    {
        $sqlQuery = $this->getQuerySql($query);
        $binds = $this->getQueryBinds($query);

        // Unset any NULL values in binds (compared as "IS NULL" and "IS NOT NULL" in SQL instead)
        if ($binds && count($binds) > 0) {
            foreach ($binds as $field => $value) {
                if (null === $value) {
                    unset($binds[$field]);
                }
            }
        }

        // Prepare update query
        if ($stmt = $this->pdo->prepare($sqlQuery)) {
            // Execute
            return ($stmt->execute($binds)) ? $this->getResultSet($query, $stmt) : false;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function updateEntity($tableName, array $data, array $where = [], array $options = [])
    {
        $dataBinds = $this->getBinds($data, 0);
        $whereBinds = $this->getBinds($where, count($dataBinds));
        $binds = array_merge($dataBinds, $whereBinds);
        $placeholders = [];
        $dataFields = array_combine(array_keys($data), array_keys($dataBinds));

        // Placeholders and passed data
        foreach ($dataFields as $field => $bindField) {
            $placeholders[] = $this->escapeIdentifier($field) . " = :" . $bindField . "";
        }

        $conditions = $this->getConditionsSql($where, count($dataBinds));

        // Ensure there are actually updated values on THIS table
        if (count($binds) > 0) {
            // Build the query
            $sql = $this->update($tableName, $placeholders, $conditions);

            try {
                // Prepare update query
#echo __LINE__ . ":$sql\n";
                $stmt = $this->pdo->prepare($sql);

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
                    throw new \Spot\Exception\Adapter("Table or datasource '" . $tableName . "' does not exist");
                }

                // Throw new Spot exception
                throw new \Spot\Exception\Adapter(__METHOD__ . ': ' . $e->getMessage());
            }
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteEntity($tableName, array $data, array $options = [])
    {
        $binds = $this->getBinds($data, 0);
        $conditions = $this->getConditionsSql($data);

        $sql = "DELETE FROM " . $tableName . "";
        $sql .= ($conditions ? ' WHERE ' . $conditions : '');

        try {
#echo __LINE__ . ": $sql\n";
            $stmt = $this->pdo->prepare($sql);
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
                throw new \Spot\Exception\Adapter("Table or datasource '" . $tableName . "' does not exist");
            }

            // Throw new Spot exception
            throw new \Spot\Exception\Adapter(__METHOD__ . ': ' . $e->getMessage());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function countEntity(QueryInterface $query, array $options = [])
    {
        $conditions = $this->getConditionsSql($query->getConditions());
        $binds = $this->getBinds($query->getparameters());

        $sql = "
            SELECT COUNT(*) AS count
            FROM " . $query->getTableName() . "
            " . ($conditions ? 'WHERE ' . $conditions : '') . "
            " . ($query->getGroupBy() ? 'GROUP BY ' . implode(', ', $query->getGroupBy()) : '');

        // Unset any NULL values in binds (compared as "IS NULL" and "IS NOT NULL" in SQL instead)
        if ($binds && count($binds) > 0) {
            foreach ($binds as $field => $value) {
                if (null === $value) {
                    unset($binds[$field]);
                }
            }
        }

        $result = false;
        try {
            // Prepare count query
#echo __LINE__ . ": $sql\n";
            $stmt = $this->pdo->prepare($sql);

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
                throw new \Spot\Exception\Adapter("Table or datasource '" . $query->getTableName() . "' does not exist");
            }

            // Throw new Spot exception
            throw new \Spot\Exception\Adapter(__METHOD__ . ': ' . $e->getMessage());
        }

        return $result;
    }











    /**
     * {@inheritDoc}
     */
    public function getResultSet(QueryInterface $query, \PDOStatement $stmt)
    {
        $mapper = $query->getMapper();
        $entityClass = $query->getEntityName();

        if ($stmt instanceof \PDOStatement) {
            // Set PDO fetch mode
            $stmt->setFetchMode(\PDO::FETCH_ASSOC);

            $results = $mapper->getResultset($entityClass, $stmt, $query->getWith());

            // Ensure statement is closed
            $stmt->closeCursor();

            return $results;
        }

        // Just return an empty result set
        return $mapper->getResultset($entityClass);
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryBinds(QueryInterface $query, $ci = true)
    {
        $params = [];
        $ci !== false && $ci = 0;

        // WHERE + HAVING
        $conditions = $query->getparameters();

        foreach ($conditions as $i => $data) {
            if (isset($data['conditions']) && is_array($data['conditions'])) {
                foreach ($data['conditions'] as $field => $value) {
                    // Column name with comparison operator
                    $columnData = explode(' ', $field);
                    $operator = '=';
                    if (count($columnData) > 2) {
                        $operator = array_pop($columnData);
                        $columnData = [implode(' ', $columnData), $operator];
                    }
                    $field = $columnData[0];

                    if (!is_array($value)) {
                        $params[$field . $ci] = $value;
                    } else {
                        $x = 0;
                        foreach ($value as $subValue) {
                            $params[$field . $ci . $x] = $subValue;
                            $x++;
                        }
                    }
                    $ci !== false && $ci++;
                }
            }
        }
        unset($x, $field, $ci, $subValue, $value, $i, $data);

        if (count($params) === 0) {
            return;
        }

        $conditions = $params;
        $ci = false;
        $binds = [];
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
                        // Attempt cast of object to string (calls object's __toString method)
                        $bindValue = (string) $value;
                    }
                } else if (is_bool($value)) {
                    $bindValue = (int) $value; // Cast boolean to integer (false = 0, true = 1)
                } else if (!is_array($value)) {
                    $bindValue = $value;
                }

                // Bind given value
                if (false !== $bindValue) {
                    // Column name with comparison operator
                    $colData = explode(' ', $column);
                    $operator = '=';
                    if (count($colData) > 2) {
                        $operator = array_pop($colData);
                        $colData = [implode(' ', $colData), $operator];
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
                // We need to do this whether it was used or not to maintain compatibility with getConditionsSql()
                $ci++;
            }

            if ($loopOnce) {
                break;
            }
        }

        return $binds;
    }




    /**
     * Bind array of field/value data to given statement
     *
     * @param PDOStatement $stmt
     * @param array $binds
     * @return bool
     */
    /*protected function bindValues($stmt, array $binds)
    {
        // Bind each value to the given prepared statement
        foreach ($binds as $field => $value) {
            $stmt->bindValue($field, $value);
        }
        return true;
    }*/
}
