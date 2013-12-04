<?php

/**
 * PostgreSQL Dialect
 *
 * @package \Spot\Dialect
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Db\Dialect;

use Spot\Db\AbstractDialect,
	Spot\Db\DialectInterface;

class Pgsql extends AbstractDialect implements DialectInterface
{
	protected $escapeChar = '"';

    /**
     * {@inheritdoc}
     */
    public function insert($tableName, array $columns, array $binds, array $options)
    {
        // If calling insert directly, we probably were not passed pk or sequence
        !isset($options['sequence']) && $options['sequence'] = false;

        // build the statement
        $sqlQuery = "INSERT INTO $tableName (";

        // If PK uses a sequence, add the PK column
        $options['sequence'] && $sqlQuery .= $options['primaryKey'] . ', ';

        // Add the fields to list of columns to insert into
        $sqlQuery .= implode(', ', array_map(array($this->adapter, 'escapeIdentifier'), $columns)) . ') VALUES (';

        // If PK uses a sequence, use NEXT VALUE FOR $sequence for the value
        $options['sequence'] && $sqlQuery .= "NEXTVAL('{$options['sequence']}'), ";

        // Add the other values
        return $sqlQuery . implode(', ', $binds) . ')';
    }
}
