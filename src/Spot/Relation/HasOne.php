<?php

/**
 * DataMapper class for 'has one' relations
 *
 * @package Spot\Relation
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Relation;

use Spot\Query,
    Spot\Entity\EntityInterface;

class HasOne extends AbstractRelation
{
    /**
     * Load query object with current relation data
     * @var Spot\Query
     */
    public $entity;

    /**
     * isset() functionality passthrough to entity
     * @return bool
     */
    public function __isset($offset)
    {
        return ($entity = $this->execute()) ? $entity->has($offset) : false;
    }

    /**
     * Getter passthrough to entity
     * @param string $offset
     * @return mixed
     */
    public function __get($offset)
    {
        return $this->entity()->get($offset);
    }

    /**
     * Setter passthrough to entity
     * @param string $offset
     * @param mixed $value
     */
    public function __set($offset, $value)
    {
        $this->entity() && $this->entity()->set($offset, $value);
    }

    /**
     * Load query object with current relation data
     *
     * @return \Spot\Query
     */
    public function toQuery()
    {
        $query = $this->getMapper()
            ->all($this->entityName(), $this->conditions())
            ->order($this->relationOrder())
            ->limit(1);

        // Add any defined selects to the query builder
        foreach ($this->resolveEntitySelects($this->sourceEntity(), $this->selects) as $select) {
            $query->select($select);
        }

        // Add any defined joins to the query builder
        foreach ($this->resolveEntityJoins($this->sourceEntity(), $this->joins) as $join) {
            $query->join($join[0], $join[1], $join[2]);
        }

        return $query;
    }

    /**
     * Get the relation entity
     * @return \Spot\Entity\EntityInterface
     */
    public function entity()
    {
        if (null === $this->entity) {
            $this->entity = $this->execute();
            if ($this->entity instanceof Query) {
                $this->entity = $this->entity->first();
            }
        }
        return $this->entity;
    }
}
