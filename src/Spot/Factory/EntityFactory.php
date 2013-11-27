<?php

/**
 * Spot Entity Factory
 *
 * Creates entities, optionally hydrating their data
 * @package \Spot\Factory
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Factory;

use Spot\Di as DiContainer,
    Spot\Di\InjectableTrait;

class EntityFactory
{
	use InjectableTrait;

    /**
     * Constructor
     * @param \Spot\Di $di
     */
    public function __construct(DiContainer $di)
    {
        $this->setDi($di);
    }

    /**
     * Get a new entity object, or an existing entity from identifiers
     * @param string $entityClass Name of the entity class
     * @param mixed $identifier Primary key or array of key/values
     * @return mixed Depends on input false If $identifier is scalar and no entity exists
     */
    public function create($entityClass, $identifier = false)
    {
        if (false === $identifier) {
            // No parameter passed, create a new empty entity object
            $entity = new $entityClass();
            $entity->data([$this->entityManager->getPrimaryKeyField($entityClass) => null]);
        } else if (is_array($identifier)) {
            // An array was passed, create a new entity with that data
            $entity = new $entityClass($identifier);
            $entity->data([$this->entityManager->getPrimaryKeyField($entityClass) => null]);
        } else {
            // Scalar, find record by primary key
            $entity = $this->first($entityClass, [$this->entityManager->getPrimaryKeyField($entityClass) => $identifier]);
            if (!$entity) {
                return false;
            }
            $this->relationManager->loadRelations($entity);
        }

        return $entity;
    }
}
