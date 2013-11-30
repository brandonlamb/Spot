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
}
