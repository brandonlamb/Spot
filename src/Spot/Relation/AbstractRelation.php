<?php

namespace Spot\Relation;

use Spot\Mapper,
    Spot\Entity;

/**
 * Abstract class for relations
 *
 * @package Spot
 * @link http://spot.os.ly
 */
abstract class AbstractRelation
{
    protected $mapper;
    protected $sourceEntity;
    protected $entityName;
    protected $foreignKeys;
    protected $conditions;
    protected $relationData;
    protected $collection;
    protected $relationRowCount;

    /**
     * Constructor function
     *
     * @param \Spot\Mapper $mapper Spot_Mapper_Abstract object to query on for relationship data
     * @param \Spot\Entity $entity
     * @param array $resultsIdentities Array of key values for given result set primary key
     * @throws \InvalidArgumentException
     */
    public function __construct(Mapper $mapper, Entity $entity, array $relationData = array())
    {
        $entityType = null;
        if ($entity instanceof \Spot\Entity) {
            $entityType = get_class($entity_or_collection);
        } elseif ($entity instanceof \Spot\Entity\CollectionInterface) {
            $entityType = $entity->entityName();
        } else {
            throw new \InvalidArgumentException("Entity or collection must be an instance of \\Spot\\Entity or \\Spot\\Entity\\Colletion");
        }

        $this->mapper = $mapper;
        $this->sourceEntity = $entity;
        $this->entityName = isset($relationData['entity']) ? $relationData['entity'] : null;
        $this->conditions = isset($relationData['where']) ? $relationData['where'] : array();
        $this->relationData = $relationData;

        // Checks ...
        if (null === $this->entityName) {
            throw new \InvalidArgumentException("Relation description key 'entity' must be set to an Entity class name.");
        }
    }

    /**
     * Get source entity object
     * @return \Spot\Entity
     */
    public function sourceEntity()
    {
        return $this->sourceEntity;
    }

    /**
     * Get related entity name
     * @return string
     */
    public function entityName()
    {
        return ($this->entityName === ':self') ? ($this->sourceEntity() instanceof \Spot\Entity\CollectionInterface ? $this->sourceEntity()->entityName() : get_class($this->sourceEntity())) : $this->entityName;
    }

    /**
     * Get mapper instance
     * @return \Spot\Mapper
     */
    public function mapper()
    {
        return $this->mapper;
    }

    /**
     * Get unresolved foreign key relations
     * @return array
     */
    public function unresolvedConditions()
    {
        return $this->conditions;
    }

    /**
     * Get foreign key relations
     * @return array
     */
    public function conditions()
    {
        return $this->resolveEntityConditions($this->sourceEntity(), $this->conditions);
    }

    /**
     * Replace entity value placeholders on relation definitions
     * Currently replaces ':entity.[col]' with the field value from the passed entity object
     * @param \Spot\Entity $entity
     * @param array $conditions
     * @param string $replace
     * @return array
     */
    public function resolveEntityConditions(Entity $entity, array $conditions, $replace = ':entity.')
    {
        // Load foreign keys with data from current row
        // Replace ':entity.[col]' with the field value from the passed entity object
        if ($conditions) {
            foreach ($conditions as $relationCol => $col) {
                if (is_string($col) && false !== strpos($col, $replace)) {
                    $col = str_replace($replace, '', $col);
                    if ($entity instanceof \Spot\Entity) {
                        $conditions[$relationCol] = $entity->$col;
                    } else if($entity instanceof \Spot\Entity\CollectionInterface) {
                        $conditions[$relationCol] = $entity->toArray($col);
                    }
                }
            }
        }
        return $conditions;
    }

    /**
     * Get sorting for relations
     *
     * @return array
     */
    public function relationOrder()
    {
        $sorting = isset($this->relationData['order']) ? $this->relationData['order'] : array();
        return $sorting;
    }

    /**
     * Called automatically when attribute is printed
     * @return string
     */
    public function __toString()
    {
        // Load related records for current row
        $res = $this->execute();
        return ($res) ? '1' : '0';
    }

    /**
     * Fetch and cache returned query object from internal toQuery() method
     * @param \Spot\Entity\CollectionInterface
     */
    public function execute()
    {
        if (!$this->collection) {
            $this->collection = $this->toQuery();
        }
        return $this->collection;
    }

    /**
     * Passthrough for missing methods on expected object result
     * @param string $func
     * @param array $args
     */
    public function __call($func, $args)
    {
        $obj = $this->execute();
        if (is_object($obj)) {
            return call_user_func_array(array($obj, $func), $args);
        } else {
            return $obj;
        }
    }

    /**
     * Load query object with current relation data
     *
     * @return \Spot\Query
     */
    abstract protected function toQuery();
}
