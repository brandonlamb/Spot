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
