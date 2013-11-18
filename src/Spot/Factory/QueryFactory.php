<?php

/**
 * Query factory
 *
 * @package \Spot\Factory
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Factory;

use Spot\Mapper;

class QueryFactory
{
	/**
	 * @var string, name of query class to instantiate
	 */
	protected $className = '\\Spot\\Query';

	/**
	 * Constructor
	 * @param string $className
	 */
	public function __construct($className = null)
	{
		null !== $className && $this->setClassName($className);
	}

	/**
	 * Set the query class name
	 * @param string $className
	 * @return QueryFactory
	 */
	public function setClassName($className)
	{
		$this->className = (string) $className;
		return $this;
	}

	/**
	 * Get the query class name
	 * @return string
	 */
	public function getClassName()
	{
		return (string) $this->className;
	}

	/**
	 * Create a query class
	 * @param \Spot\Mapper $mapper
	 * @param string $entityName
	 * @return mixed
	 */
	public function create(Mapper $mapper, $entityName = null)
	{
		return new $this->className($mapper, $entityName);
	}
}
