<?php

/**
 * Entity Relation Manager
 * @package \Spot\Relation
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Relation;

use Spot\Mapper,
	Spot\Config,
	Spot\Entity\HasEntityManagerTrait,
	Spot\Entity\CollectionInterface;

class Manager
{
	use HasEntityManagerTrait;

	/**
	 * @var \Spot\Config
	 */
	protected $config;

	/**
	 * Set the config
	 * @param \Spot\Config
	 * @return \Spot\Relation\Manager
	 */
	public function setConfig(Config $config)
	{
		$this->config = $config;
		return $this;
	}

	/**
	 * Get config
	 * @return \Spot\Config
	 */
	public function getConfig()
	{
		return $this->config;
	}







    /**
     * Load defined relations
     * @param \Spot\Entity|\Spot\Entity\CollectionInterface
     * @param bool $reload
     * @return array
     * @throws \InvalidArgumentException
     */
    public function loadRelations($entity, $reload = false)
    {
        $entityName = $entity instanceof CollectionInterface ? $entity->entityName() : $entity->toString();
        if (!$entityName) {
            throw new \InvalidArgumentException("Cannot load relation with a null \$entityName");
        }

        $relations = [];
        $rels = $this->getEntityManager()->relations($entityName);
        foreach ($rels as $field => $relation) {
            $relations[$field] = $this->loadRelation($entity, $field, $reload);
        }

        return $relations;
    }

    /**
     * Load defined relations
     * @param \Spot\Entity
     * @param string $name
     * @param bool $reload
     * @return \Spot\Relation\AbstractRelation
     * @throws \InvalidArgumentException
     */
    public function loadRelation($entity, $name, $reload = false)
    {
        $entityName = $entity instanceof \Spot\Entity\CollectionInterface ? $entity->entityName() : $entity->toString();
        if (!$entityName) {
            throw new \InvalidArgumentException("Cannot load relation with a null \$entityName");
        }

		$rels = $this->getEntityManager()->relations($entityName);
        if (isset($rels[$name])) {
            return $this->getRelationObject($entity, $name, $rels[$name]);
        }
    }

    /**
     * @param \Spot\Entity $entity
     * @param string $field
     * @param \Spot\Relation\AbstractRelation
     * @param bool $reload
     * @return \Spot\Relation\AbstractRelation
     * @throws \InvalidArgumentException
     */
    protected function getRelationObject($entity, $field, $relation, $reload = false)
    {
        $entityName = $entity instanceof \Spot\Entity\CollectionInterface ? $entity->entityName() : $entity->toString();
        if (!$entityName) {
            throw new \InvalidArgumentException("Cannot load relation with a null \$entityName");
        }

        if (isset($entity->$field) && !$reload) {
            return $entity->$field;
        }

        $relationEntity = isset($relation['entity']) ? $relation['entity'] : false;
        if (!$relationEntity) {
            throw new \InvalidArgumentException("Entity for '" . $field . "' relation has not been defined.");
        }

        // Self-referencing entity relationship?
        $relationEntity == ':self' && $relationEntity = $entityName;

        // Load relation class to lazy-loading relations on demand
        $relationClass = '\\Spot\\Relation\\' . $relation['type'];

        // Set field equal to relation class instance
        $relationObj = new $relationClass($this, $entity, $relation);
        return $entity->$field = $relationObj;
    }
}
