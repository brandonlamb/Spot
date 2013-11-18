<?php

/**
 * DB2 Dialect
 *
 * @package \Spot\Dialect
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Dialect;

use Spot\AbstractDialect,
	Spot\DialectInterface;

class Db2 extends AbstractDialect implements DialectInterface
{
	protected $escapeChar = "'";

	/**
	 * {@inheritDoc}
	 */
	public function limit($sqlQuery, $number)
	{
        $number = (int) $number;
        return $number ? $sqlQuery . ' FETCH FIRST ' . $number . ' ROWS ONLY' : $sqlQuery;
	}

	/**
	 * {@inheritDoc}
	 */
	public function offset($sqlQuery, $number)
	{
		return $sqlQuery;
	}
}
