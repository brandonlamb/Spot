<?php

/**
 * A quick and dirty di container
 *
 * @package Spot
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Di;

use Spot\Db\Column\Config,
	Spot\Factory\EntityFactory,
	Spot\Factory\QueryFactory,
	Spot\Events\Manager as EventsManager,
	Spot\Entity\Manager as EntityManager,
	Spot\Entity\Relation\Manager as RelationManager;

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
		$this->set('resultsetFactory', '\\Spot\\Factory\\ResultsetFactory');
		$this->set('queryFactory', '\\Spot\\Factory\\QueryFactory');
	}
}
