<?php

namespace Spot\Adapter;

/**
 * DB2 Database Adapter
 */
class Db2 extends AbstractAdapter implements AdapterInterface
{
    // Format for date columns, formatted for PHP's date() function
    protected $formatDate = 'Y-m-d';
    protected $formatTime = ' H:i:s';
    protected $formatDatetime = 'Y-m-d H:i:s';

    /**
     * Escape/quote direct user input
     *
     * @param string $string
     * @todo Not quoting the columns essentially by just returning $field
     */
    public function escapeField($field)
    {
        return $field;
    }

    /**
     * {@inherit}
     */
    public function migrate($table, array $fields, array $options = array())
    {
        return $this;
    }

    /**
     * @{inherit}
     */
    public function createDatabase($database)
    {
        $sql = 'CREATE DATABASE ' . $database;

        // Add query to log
        \Spot\Log::addQuery($this, $sql);

        return $this->connection()->exec($sql);
    }

    /**
     * @{inherit}
     */
    public function dropDatabase($database)
    {
        $sql = 'DROP DATABASE ' . $database;

        // Add query to log
        \Spot\Log::addQuery($this, $sql);

        return $this->connection()->exec($sql);
    }

    /**
     * {@inherit}
     */
    public function truncateDatasource($datasource)
    {
        return $this;
    }

    /**
     * {@inherit}
     */
    public function dropDatasource($datasource)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getLimitSql($limit, array $options = array())
    {
        $limit = (int) $limit;
        return $limit ? 'FETCH FIRST ' . $limit . ' ROWS ONLY' : '';
    }

    /**
     * {@inheritdoc}
     */
    public function getOffsetSql($offset, array $options = array())
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getInsertSql($datasource, array $data, array $binds, array $options)
    {
        // If calling insert directly, we probably were not passed pk or sequence
        !isset($options['sequence']) && $options['sequence'] = false;

        // build the statement
        $sql = 'INSERT INTO ' . $datasource . ' (';

        // If PK uses a sequence, add the PK column
        $options['sequence'] && $sql .= $options['pk'] . ', ';

        // Add the fields to list of columns to insert into
        $sql .= implode(', ', array_map(array($this, 'escapeField'), array_keys($data))) . ') VALUES (';

        // If PK uses a sequence, use NEXT VALUE FOR $sequence for the value
        $options['sequence'] && $sql .= 'NEXT VALUE FOR ' . $options['sequence'] . ', ';

        // Add the other values
        $sql .= ':' . implode(', :', array_keys($binds)) . ')';

        return $sql;
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId($sequence = false)
    {
        // If a sequence was passed then pass it through to the PDO method
        if ($sequence !== false) {
            // Get previous value from sequence
            $stmt = $this->connection()->query('VALUES PREVIOUS VALUE FOR ' . $sequence);
        } else {
            // Get last insert id from the identity_val_local() function
            $stmt = $this->connection()->query('VALUES IDENTITY_VAL_LOCAL()');
        }

        $row = $stmt->fetch(\PDO::FETCH_NUM);
        return isset($row[0]) ? $row[0] : 0;
    }
}
