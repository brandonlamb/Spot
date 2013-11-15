<?php

/**
 * A quick and dirty di container
 *
 * @package Spot
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Di;

use Spot\Config,
	Spot\Factory\EntityFactory,
	Spot\Factory\CollectionFactory,
	Spot\Factory\QueryFactory,
	Spot\Manager\EntityManager,
	Spot\Manager\EventsManager,
	Spot\Manager\RelationManager;

class FactoryDefault Extends Container implements DiInterface
{
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->set('config', function () {
			$resource = new Config($this);
			return $resource;
		});

		$this->set('entityManager', function () {
			$resource = new EntityManager($this);
			return $resource;
		});

		$this->set('relationManager', function () {
			$resource = new RelationManager($this);
			return $resource;
		});

		$this->set('eventsManager', function () {
			$resource = new EventsManager($this);
			return $resource;
		});

		$this->set('entityFactory', '\\Spot\\Factory\\EntityFactory');
		$this->set('collectionFactory', '\\Spot\\Factory\\CollectionFactory');
		$this->set('queryFactory', '\\Spot\\Factory\\QueryFactory');
	}
}
