<?php

/**
 * DataMapper class for 'has many' relations
 *
 * @package Spot\Relation
 */

namespace Spot\Relation;

use Countable,
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
        $query = $this->mapper()->all($this->entityName(), $this->conditions())->order($this->relationOrder());
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
        $data = $this->execute();
        return $data ? $data : [];
    }
}
