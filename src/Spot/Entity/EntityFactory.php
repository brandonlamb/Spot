<?php

/**
 * Spot Entity Factory
 *
 * Creates entities, optionally hydrating their data
 * @package \Spot\Entity
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Entity;

use Spot\Relation\HasRelationManagerTrait;

class EntityFactory
{
	use HasEntityManagerTrait, HasRelationManagerTrait;

    /**
     * Get the field name of the primary key for given entity
     * @param string $entityName Name of the entity class
     * @return string
     */
    public function getPrimaryKeyField($entityName)
    {
        return $this->getEntityManager()->getPrimaryKeyField($entityName);
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
            $entity->data(array($this->getPrimaryKeyField($entityClass) => null));
        } elseif (is_array($identifier)) {
            // An array was passed, create a new entity with that data
            $entity = new $entityClass($identifier);
            $entity->data(array($this->getPrimaryKeyField($entityClass) => null));
        } else {
            // Scalar, find record by primary key
            $entity = $this->first($entityClass, array($this->getPrimaryKeyField($entityClass) => $identifier));
            if (!$entity) {
                return false;
            }
            $this->getRelationManager()->loadRelations($entity);
        }

        // Set default values if entity not loaded
        if (!$this->getPrimaryKey($entity)) {
            $entityDefaultValues = $this->getEntityManager()->fieldDefaultValues($entityClass);
            if (count($entityDefaultValues) > 0) {
                $entity->data($entityDefaultValues);
            }
        }

        return $entity;
    }
}
