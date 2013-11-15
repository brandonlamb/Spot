<?php

namespace Spot\Relation;

use Spot\Mapper,
    Spot\Entity\EntityInterface;

/**
 * Abstract class for relations
 *
 * @package Spot
 * @link http://spot.os.ly
 */
abstract class AbstractRelation
{
    /**
     * @var \Spot\Mapper
     */
    protected $mapper;

    /**
     * @var \Spot\Entity\EntityInterface, the source entity to find relation(s) for
     */
    protected $sourceEntity;

    /**
     * @var string, class name of relation entity
     */
    protected $entityName;

    /**
     * @var array, the foreign keys
     */
    protected $foreignKeys;

    /**
     * @var array, conditions to find relations
     */
    protected $conditions;

    /**
     * @var array
     */
    protected $relationData;

    /**
     * @var \Spot\Entity\CollectionInterface
     */
    protected $collection;

    /**
     * @var int
     */
    protected $relationRowCount;

    /**
     * Constructor function
     *
     * @param \Spot\Mapper $mapper object to query on for relationship data
     * @param \Spot\Entity\EntityInterface $entity
     * @param array $resultsIdentities Array of key values for given result set primary key
     * @throws \InvalidArgumentException
     */
    public function __construct(Mapper $mapper, EntityInterface $entity, array $relationData = array())
    {
        $entityType = null;
        if ($entity instanceof \Spot\Entity\EntityInterface) {
            $entityType = $entity->toString();
        } elseif ($entity instanceof \Spot\Entity\CollectionInterface) {
            $entityType = $entity->entityName();
        } else {
            throw new \InvalidArgumentException("Entity or collection must be an instance of \\Spot\\Entity\\EntityInterface or \\Spot\\Entity\\Colletion");
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
     * @return \Spot\Entity\EntityInterface
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
        d($this->entityName);
        if ($this->entityName !== ':self') {
            return $this->entityName;
        }

        if ($this->sourceEntity() instanceof \Spot\Entity\CollectionInterface) {
            return $this->sourceEntity()->entityName();
        } else {
            return get_class($this->sourceEntity());
        }

#        return ($this->entityName === ':self') ? ($this->sourceEntity() instanceof \Spot\Entity\CollectionInterface ? $this->sourceEntity()->entityName() : get_class($this->sourceEntity())) : $this->entityName;
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
     * @param \Spot\Entity\EntityInterface $entity
     * @param array $conditions
     * @param string $replace
     * @return array
     */
    public function resolveEntityConditions(EntityInterface $entity, array $conditions, $replace = ':entity.')
    {
        // Load foreign keys with data from current row
        // Replace ':entity.[col]' with the field value from the passed entity object
        if ($conditions) {
            foreach ($conditions as $relationCol => $col) {
                if (is_string($col) && false !== strpos($col, $replace)) {
                    $col = str_replace($replace, '', $col);
                    if ($entity instanceof \Spot\Entity\EntityInterface) {
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
        !$this->collection && $this->collection = $this->toQuery();
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
        return (is_object($obj)) ? call_user_func_array(array($obj, $func), $args) : $obj;
    }

    /**
     * Load query object with current relation data
     * @return \Spot\Query
     */
    abstract protected function toQuery();

    // SPL - ArrayAccess functions

    public function offsetExists($key)
    {
        $this->execute();
        return isset($this->collection[$key]);
    }

    public function offsetGet($key)
    {
        $this->execute();
        return $this->collection[$key];
    }

    public function offsetSet($key, $value)
    {
        $this->execute();

        if ($key === null) {
            return $this->collection[] = $value;
        } else {
            return $this->collection[$key] = $value;
        }
    }

    public function offsetUnset($key)
    {
        $this->execute();
        unset($this->collection[$key]);
    }
}
