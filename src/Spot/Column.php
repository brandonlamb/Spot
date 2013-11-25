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
	/**
	 * @var int Entity column types
	 */
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

	/**
	 * @var int Entity column bind data types
	 */
	const BIND_PARAM_NULL		= 1;
	const BIND_PARAM_INT		= 2;
	const BIND_PARAM_STR	 	= 3;
	const BIND_PARAM_BOOL		= 4;
	const BIND_PARAM_DECIMAL	= 5;
	const BIND_SKIP				= 6;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $alias;

	/**
	 * @var int
	 */
	protected $type = 3;

	/**
	 * @var int
	 */
	protected $size = 4096;

	/**
	 * @var bool
	 */
	protected $isNotNull = false;

	/**
	 * @var bool
	 */
	protected $isPrimary = false;

	/**
	 * @var int
	 */
	protected $bindType = 3;

	/**
	 * @var mixed
	 */
	protected $default;

	/**
	 * @var Closure
	 */
	protected $filter;

	/**
	 * Constructor
	 * @param string $columnName
	 * @param array $definition
	 */
	public function __construct($columnName, $definition)
	{
		$this->name = (string) $columnName;
		isset($definition['alias']) && $this->alias = (string) $definition['alias'];
		isset($definition['notNull']) && $this->isNotNull = (bool) $definition['notNull'];
		isset($definition['primary']) && $this->isPrimary = (bool) $definition['primary'];
		isset($definition['bindType']) && $this->bindType = (int) $definition['bindType'];
		isset($definition['default']) && $this->default = $definition['default'];
		isset($definition['filter']) && $this->filter = $definition['filter'];

		// Set type, if integer set size to 10
		if (isset($definition['type'])) {
			$definition['type'] > 1 || $definition['type'] < 10 && $this->type = (int) $definition['type'];
			$this->type === self::TYPE_INTEGER && $this->size = 10;
		}

		// Set any explicit size
		isset($definition['size']) && $this->size = (int) $definition['size'];
	}

	/**
	 * Get name
	 * @return string
	 */
	public function getName()
	{
		return (string) $this->name;
	}

	/**
	 * Get alias name
	 * @return string
	 */
	public function getAlias()
	{
		return (string) $this->alias;
	}

	/**
	 * Get type
	 * @return int
	 */
	public function getType()
	{
		return (int) $this->type;
	}

	/**
	 * Get Size
	 * @return int
	 */
	public function getSize()
	{
		return (int) $this->size;
	}

	/**
	 * Is column not null?
	 * @return bool
	 */
	public function isNotNull()
	{
		return (bool) $this->isNotNull;
	}

	/**
	 * Is column a primary key?
	 * @return bool
	 */
	public function isPrimary()
	{
		return (bool) $this->isPrimary;
	}

	/**
	 * Get the bind type
	 * @return int
	 */
	public function getBindType()
	{
		return (int) $this->bindType;
	}

	/**
	 * Get the default value
	 * @return mixed
	 */
	public function getDefault()
	{
		return $this->default;
	}

	/**
	 * Get filter callback if set
	 * @return Closure
	 */
	public function getFilter()
	{
		return $this->filter;
	}
}
