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
	Spot\Entity\EntityFactory,
	Spot\Entity\Collection\Factory\CollectionFactory,
	Spot\Entity\Manager as EntityManager,
	Spot\Relation\Manager as RelationManager,
	Spot\Events\Manager as EventsManager;

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

		$this->storage['eventsManager'] = function () {
			static $resource;
			null === $resource && $resource = new EventsManager($this);
			return $resource;
		};

		$this->storage['queryFactory'] = function () {
			static $resource;
			null === $resource && $resource = new QueryFactory();
			return $resource;
		};

		$this->storage['entityFactory'] = function () {
			static $resource;
			null === $resource && $resource = new EntityFactory();
			return $resource;
		};

		$this->storage['collectionFactory'] = function () {
			static $resource;
			null === $resource && $resource = new CollectionFactory();
			return $resource;
		};
	}
}
