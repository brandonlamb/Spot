<?php

/**
 * PostgreSQL Dialect
 *
 * @package \Spot\Dialect
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Dialect;

use Spot\AbstractDialect,
	Spot\DialectInterface;

class Pgsql extends AbstractDialect implements DialectInterface
{
	protected $escapeChar = '"';
}
