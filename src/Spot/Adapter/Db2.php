<?php

/**
 * Spot DB2 Database Adapter
 *
 * @package Spot\Adapter
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Adapter;

use Spot\AbstractAdapter,
    Spot\AdapterInterface;

class Db2 extends AbstractAdapter implements AdapterInterface
{
    protected $type = 'Db2';
    protected $dialectType = 'Db2';

    /**
     * @{inheritDoc}
     * @todo Not quoting the columns essentially by just returning $field
     */
    public function escapeIdentifier($identifier)
    {
        if (is_array($identifier)) {
            return $identifier[0] . '.' . $identifier[1];
        }
        return $identifier;
    }

    /**
     * {@inheritdoc}
     */
    /*public function getLimitSql($limit, array $options = array())
    {
        $limit = (int) $limit;
        return $limit ? 'FETCH FIRST ' . $limit . ' ROWS ONLY' : '';
    }*/

    /**
     * {@inheritdoc}
     */
    /*public function getOffsetSql($offset, array $options = array())
    {
        return '';
    }*/

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
        $sql .= implode(', ', array_map(array($this, 'escapeIdentifier'), array_keys($data))) . ') VALUES (';

        // If PK uses a sequence, use NEXT VALUE FOR $sequence for the value
        $options['sequence'] && $sql .= 'NEXT VALUE FOR ' . $options['sequence'] . ', ';

        // Add the other values
        $sql .= ':' . implode(', :', array_keys($binds)) . ')';

        return $sql;
    }

    /**
     * {@inheritdoc}
     */
    public function lastInsertId($sequence = null)
    {
        // If a sequence was passed then pass it through to the PDO method
        if ($sequence !== null) {
            // Get previous value from sequence
            $stmt = $this->pdo->query('VALUES PREVIOUS VALUE FOR ' . $sequence);
        } else {
            // Get last insert id from the identity_val_local() function
            $stmt = $this->pdo->query('VALUES IDENTITY_VAL_LOCAL()');
        }

        $row = $stmt->fetch(\PDO::FETCH_NUM);
        return isset($row[0]) ? $row[0] : 0;
    }
}
