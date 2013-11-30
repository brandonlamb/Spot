<?php

/**
 * DataMapper class for 'has many' relations
 *
 * @package Spot\Relation
 */

namespace Spot\Relation;

use Spot\Query,
    Spot\Entity\EntityInterface,
    Countable,
    IteratorAggregate,
    ArrayAccess;

class HasMany extends AbstractRelation implements Countable, IteratorAggregate, ArrayAccess
{
    /**
     * Load query object with current relation data
     *
     * @return \Spot\Query
     */
    protected function toQuery()
    {
        $query = $this->getMapper()
            ->all($this->entityName(), $this->conditions())
            ->order($this->relationOrder());

        // Add any defined selects to the query builder
        foreach ($this->resolveEntitySelects($this->sourceEntity(), $this->selects) as $select) {
            $query->select($select);
        }

        // Add any defined joins to the query builder
        foreach ($this->resolveEntityJoins($this->sourceEntity(), $this->joins) as $join) {
            $query->join($join[0], $join[1], $join[2]);
        }

        $query->snapshot();

        return $query;
    }

    /**
     * Find first entity in the set
     *
     * @return \Spot\Entity
     */
    public function first()
    {
        return $this->execute()->first();
    }

    /**
     * SPL Countable function
     * Called automatically when attribute is used in a 'count()' function call
     *
     * @return int
     */
    public function count()
    {
        $results = $this->execute();
        return $results ? count($results) : 0;
    }

    /**
     * SPL IteratorAggregate function
     * Called automatically when attribute is used in a 'foreach' loop
     *
     * @return \Spot\Entity\CollectionInterface
     */
    public function getIterator()
    {
        // Load related records for current row
        return ($data = $this->execute()) ? $data : [];
    }
}
