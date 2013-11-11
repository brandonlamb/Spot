<?php

/**
 * A quick and dirty di container
 *
 * @package Spot
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Di;

use Spot\Di as DiContainer,
	Spot\Config,
	Spot\Query\QueryFactory,
	Spot\Entity\Manager as EntityManager,
	Spot\Relation\Manager as RelationManager;

class FactoryDefault Extends DiContainer implements DiInterface
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->storage['config'] = function () {
			static $resource;
			null === $resource && $resource = new Config($this);
			return $resource;
		};

		$this->storage['entityManager'] = function () {
			static $resource;
			null === $resource && $resource = new EntityManager($this);
			return $resource;
		};

		$this->storage['relationManager'] = function () {
			static $resource;
			null === $resource && $resource = new RelationManager($this);
			return $resource;
		};

		$this->storage['queryFactory'] = function () {
			static $resource;
			null === $resource && $resource = new QueryFactory();
			return $resource;
		};
	}
}
