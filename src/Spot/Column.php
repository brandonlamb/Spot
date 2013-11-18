<?php

/**
 * Table column class
 *
 * @package Spot
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot;

class Column
{
	const TYPE_DATE		= 1;
	const TYPE_DATETIME	= 2;
	const TYPE_VARCHAR	= 3;
	const TYPE_CHAR		= 4;
	const TYPE_TEXT		= 5;
	const TYPE_INTEGER	= 6;
	const TYPE_DECIMAL	= 7;
	const TYPE_FLOAT	= 8;
	const TYPE_BOOLEAN	= 9;
	const TYPE_DOUBLE	= 10;

	const BIND_PARAM_NULL		= 1;
	const BIND_PARAM_INT		= 2;
	const BIND_PARAM_STR	 	= 3;
	const BIND_PARAM_BOOL		= 4;
	const BIND_PARAM_DECIMAL	= 5;
	const BIND_SKIP				= 6;
}
