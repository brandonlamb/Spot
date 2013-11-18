<?php

/**
 * Entity collection factory
 *
 * @package \Spot\Factory
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Factory;

use Spot\Mapper;

class CollectionFactory
{
	/**
	 * @var string, name of query class to instantiate
	 */
	protected $className = '\\Spot\\Entity\\Collection';

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
	 * Create an entity collection
	 * @param array $results
	 * @param array $resultsIdentities
	 * @param string $entityName
	 * @return \Spot\Entity\CollectionInterface
	 */
	public function create($results, $resultsIdentities, $entityName)
	{
		return new $this->className($results, $resultsIdentities, $entityName);
	}
}
