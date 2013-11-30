<?php

/**
 * Abstract class for relations
 *
 * @package Spot\Relation
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Relation;

use Spot\Mapper,
    Spot\Entity\EntityInterface,
    Spot\Entity\ResultSetInterface,
    Spot\Manager\EntityManager;

abstract class AbstractRelation
{
    /**
     * @var \Spot\Mapper
     */
    protected $mapper;

    /**
     * @var \Spot\Manager\EntityManager
     */
    protected $entityManager;

    /**
     * @var \Spot\Entity\EntityInterface, the source entity to find relation(s) for
     */
    protected $sourceEntity;

    /**
     * @var string, class name of relation entity
     */
    protected $entityName;

    /**
     * @var array, select columns when finding relations
     */
    protected $selects;

    /**
     * @var array, conditions to find relations
     */
    protected $conditions;

    /**
     * @var array, join conditions to find relations
     */
    protected $joins;

    /**
     * @var array
     */
    protected $relationData;

    /**
     * @var \Spot\Entity\ResultSetInterface
     */
    protected $collection;

    /**
     * @var int
     */
    #protected $relationRowCount;

    /**
     * Constructor function
     *
     * @param \Spot\Mapper $mapper object to query on for relationship data
     * @param \Spot\Entity\EntityInterface $entity
     * @param array $resultsIdentities Array of key values for given result set primary key
     * @throws \InvalidArgumentException
     */
    public function __construct(Mapper $mapper, EntityManager $entityManager, $entity, array $relationData = [])
    {
        if ($entity instanceof EntityInterface && $entity instanceof ResultSetInterface) {
            throw new \InvalidArgumentException("Entity or collection must be an instance of \\Spot\\Entity\\EntityInterface or \\Spot\\Entity\\Colletion");
        }

        $this->mapper = $mapper;
        $this->entityManager = $entityManager;
        $this->sourceEntity = $entity;
        $this->entityName = isset($relationData['entity']) ? $relationData['entity'] : null;
        $this->selects = isset($relationData['select']) ? $relationData['select'] : ['*'];
        $this->joins = isset($relationData['join']) ? $relationData['join'] : [];
        $this->conditions = isset($relationData['where']) ? $relationData['where'] : [];
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
        if ($this->entityName !== ':self') {
            return $this->entityName;
        }

        if ($this->sourceEntity() instanceof ResultSetInterface) {
            return $this->sourceEntity()->entityName();
        } else {
            return get_class($this->sourceEntity());
        }

#        return ($this->entityName === ':self') ? ($this->sourceEntity() instanceof \Spot\Entity\ResultSetInterface ? $this->sourceEntity()->entityName() : get_class($this->sourceEntity())) : $this->entityName;
    }

    /**
     * Get mapper instance
     * @return \Spot\Mapper
     */
    public function getMapper()
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
     * Get WHERE conditions
     * @return array
     */
    public function conditions()
    {
        return $this->resolveEntityConditions($this->sourceEntity(), $this->conditions);
    }

    /**
     * Replace entity value placeholders on relation definitions
     * Currently replaces ':entity.[col]' with the field value from the passed entity object
     * @param \Spot\Entity\EntityInterface|\Spot\Entity\ResultSetInterface $entity
     * @param array $selects
     * @param string $replace
     * @return array
     */
    public function resolveEntitySelects($entity, array $selects, $replace = ':entity.')
    {
        $sourceTable = $this->entityManager->getTable($this->sourceEntity());
        $relationTable = $this->entityManager->getTable($this->relationEntityName());

        // Load foreign keys with data from current row
        // Replace ':entity.[col]' with the field value from the passed entity object
        for ($i = 0, $c = count($selects); $i < $c; $i++) {
            $select = $selects[$i];

            // Replace :relation in table definition with $relationTable
            if (is_string($select)) {
                if (false !== strpos($select, ':relation.')) {
                    $select = str_replace(':relation.', $relationTable . '.', $select);
                }

                if (false !== strpos($select, ':entity.')) {
                    $select = str_replace(':entity.', $sourceTable . '.', $select);
                }
            }

            $selects[$i] = $select;
        }

        return $selects;
    }

    /**
     * Replace entity value placeholders on relation definitions
     * Currently replaces ':entity.[col]' with the field value from the passed entity object
     * @param \Spot\Entity\EntityInterface|\Spot\Entity\ResultSetInterface $entity
     * @param array $joins
     * @param string $replace
     * @return array
     */
    public function resolveEntityJoins($entity, array $joins, $replace = ':entity.')
    {
        $sourceTable = $this->entityManager->getTable($this->sourceEntity());
        $relationTable = $this->entityManager->getTable($this->relationEntityName());

        // Load foreign keys with data from current row
        // Replace ':entity.[col]' with the field value from the passed entity object
        for ($i = 0, $c = count($joins); $i < $c; $i++) {
            $definition = $joins[$i];

            if (!isset($definition[2])) {
                throw new \InvalidArgumentException(
                    __METHOD__ . ': Entity relation joins must include table, predicate and join type'
                );
            }

            // Replace :relation in table definition with $relationTable
            if (is_string($definition[0])) {
                if (false !== strpos($definition[0], ':relation')) {
                    $definition[0] = str_replace(':relation', $relationTable, $definition[0]);
                }
                if (false !== strpos($definition[0], ':entity')) {
                    $definition[0] = str_replace(':entity', $sourceTable, $definition[0]);
                }
            }

            // Replace :relation.column with $relationTable.column
            if (is_string($definition[1])) {
                if (false !== strpos($definition[1], ':relation.')) {
                    $definition[1] = str_replace(':relation.', $relationTable . '.', $definition[1]);
                }

                if (false !== strpos($definition[1], $replace)) {
                    $definition[1] = str_replace($replace, $sourceTable . '.', $definition[1]);
                    /*
                    if ($entity instanceof EntityInterface) {
                        $joins[$relationCol] = $entity->$col;
                    } else if($entity instanceof ResultSetInterface) {
                        $joins[$relationCol] = $entity->toArray($col);
                    }
                    */
                }
                $joins[$i] = $definition;
            }
        }

        return $joins;
    }

    /**
     * Replace entity value placeholders on relation definitions
     * Currently replaces ':entity.[col]' with the field value from the passed entity object
     * @param \Spot\Entity\EntityInterface|\Spot\Entity\ResultSetInterface $entity
     * @param array $conditions
     * @param string $replace
     * @return array
     */
    public function resolveEntityConditions($entity, array $conditions, $replace = ':entity.')
    {
        // Load foreign keys with data from current row
        // Replace ':entity.[col]' with the field value from the passed entity object
        if ($conditions) {
            $sourceTable = $this->entityManager->getTable($this->sourceEntity());
            $relationTable = $this->entityManager->getTable($this->relationEntityName());

            foreach ($conditions as $relationCol => $col) {
                if (is_string($relationCol) && false !== strpos($relationCol, ':relation.')) {
                    unset($conditions[$relationCol]);
                    $relationCol = str_replace(':relation.', $relationTable . '.', $relationCol);
                }

                if (is_string($col) && false !== strpos($col, $replace)) {
                    $col = str_replace($replace, '', $col);
                    if ($entity instanceof EntityInterface) {
                        $conditions[$relationCol] = $entity->$col;
                    } else if ($entity instanceof ResultSetInterface) {
                        $conditions[$relationCol] = array_unique($entity->toArray($col));
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
        return isset($this->relationData['order']) ? $this->relationData['order'] : [];
    }

    /**
     * Get entity name of relation class
     * @return array
     */
    public function relationEntityName()
    {
        return isset($this->relationData['entity']) ? $this->relationData['entity'] : null;
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
     * @param \Spot\Entity\ResultSetInterface
     */
    public function execute()
    {
        !$this->collection && $this->collection = $this->toQuery();
        return $this->collection;
    }

    /**
     * Manually assign a collection to prevent execute() from firing
     * @param array $collection
     * @return AbstractRelation
     */
    public function setCollection($collection)
    {
        $this->collection = $collection;
        return $this;
    }

    /**
     * Passthrough for missing methods on expected object result
     * @param string $func
     * @param array $args
     */
    public function __call($func, $args)
    {
        $obj = $this->execute();
        return (is_object($obj)) ? call_user_func_array([$obj, $func], $args) : $obj;
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
            $this->collection[] = $value;
        } else {
            $this->collection[$key] = $value;
        }
    }

    public function offsetUnset($key)
    {
        $this->execute();
        unset($this->collection[$key]);
    }
}
