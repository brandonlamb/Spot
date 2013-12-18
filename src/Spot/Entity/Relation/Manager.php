<?php

/**
 * Entity Relation Manager
 * @package \Spot\Entity\Relation
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Entity\Relation;

use Spot\Di\DiInterface,
    Spot\Di\InjectableTrait,
    Spot\Entity\ResultsetInterface,
    Spot\Mapper;

class Manager
{
	use InjectableTrait;

    /**
     * Constructor
     * @param \Spot\Di\DiInterface $di
     */
    public function __construct(DiInterface $di)
    {
        $this->setDi($di);
    }

    /**
     * Load defined relations
     * @param \Spot\Entity\EntityInterface|\Spot\Entity\ResultsetInterface
     * @param \Spot\Entity\EntityInterface|\Spot\Mapper $mapper
     * @param bool $reload
     * @return \Spot\Entity\EntityInterface
     * @throws \InvalidArgumentException
     */
    public function loadRelations($entity, Mapper $mapper, $reload = false)
    {
        $entityName = $entity instanceof ResultsetInterface ? $entity->getEntityName() : $entity->toString();
        if (empty($entityName)) {
            throw new \InvalidArgumentException("Cannot load relation with a null \$entityName");
        }

        foreach ($this->entityManager->getRelations($entityName) as $field => $relation) {
            $this->loadRelation($entity, $field, $mapper, $reload);
        }

        return $entity;
    }

    /**
     * Load defined relations
     * @param \Spot\Entity\EntityInterface $entity
     * @param string $name
     * @param bool $reload
     * @return \Spot\Entity\EntityInterface
     * @throws \InvalidArgumentException
     */
    public function loadRelation($entity, $name, Mapper $mapper, $reload = false)
    {
        $entityName = $entity instanceof ResultsetInterface ? $entity->getEntityName() : $entity->toString();
        if (empty($entityName)) {
            throw new \InvalidArgumentException("Cannot load relation with a null \$entityName");
        }

		$relations = $this->entityManager->getRelations($entityName);
        if (isset($relations[$name])) {
            return $this->loadRelationObject($entity, $name, $relations[$name], $mapper);
        }

        return $entity;
    }

    /**
     * Load an entity relation object into the entity object
     *
     * @param \Spot\Entity $entity
     * @param string $field
     * @param \Spot\Entity\Relation\AbstractRelation
     * @param \Spot\Mapper $mapper
     * @param bool $reload
     * @return \Spot\Entity\EntityInterface
     * @throws \InvalidArgumentException
     */
    protected function loadRelationObject($entity, $field, $relation, Mapper $mapper, $reload = false)
    {
        $entityName = $entity instanceof ResultsetInterface ? $entity->getEntityName() : $entity->toString();
        if (empty($entityName)) {
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
        $relationClass = '\\Spot\\Entity\\Relation\\' . $relation['type'];

        // Set field equal to relation class instance
        $relation = new $relationClass($mapper, $this->entityManager, $entity, $relation);

        // Inject relation object into entity property
        if (!$entity instanceof ResultsetInterface) {
            $entity->set($field, $relation);
        }

        return $relation;
    }
}
