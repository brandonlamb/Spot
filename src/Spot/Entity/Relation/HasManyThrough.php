<?php

/**
 * DataMapper class for 'has many through' relations
 *
 * @package Spot\Entity\Relation
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Entity\Relation;

use Spot\Query,
    Spot\Entity\AbstractRelation,
    Spot\Entity\RelationInterface,
    Spot\Entity\EntityInterface,
    Countable,
    IteratorAggregate,
    ArrayAccess;

class HasManyThrough
    extends AbstractRelation
    implements Countable, IteratorAggregate, ArrayAccess, RelationInterface
{
    /**
     * Load query object with current relation data
     *
     * @return \Spot\Query
     */
    protected function toQuery()
    {
        // "Through" Entity
        $throughEntity = isset($this->relationData['throughEntity']) ? $this->relationData['throughEntity'] : null;
        if (null === $throughEntity) {
            throw new \InvalidArgumentException("Relation description key 'throughEntity' not set.");
        }
#d(__METHOD__, $throughEntity);

        // "Through" WHERE conditions
        $throughWhere = isset($this->relationData['throughWhere']) ? $this->relationData['throughWhere'] : [];
        if (!$throughWhere || !is_array($throughWhere)) {
            throw new \InvalidArgumentException("Relation description key 'throughWhere' not set or is not a valid array.");
        }
        $throughWhereResolved = $this->resolveEntityConditions($this->sourceEntity(), $throughWhere);
#d(__METHOD__, $throughEntity, $throughWhereResolved);

        // Get IDs of "Through" entites
        $throughEntities = $this->mapper()->all($throughEntity, $throughWhereResolved);
d(__METHOD__, $throughEntities);

        $twe = false;
        if (count($throughEntities) > 0) {
            // Resolve "where" conditions with current entity object and all returned "Through" entities
            $twe = [];
            foreach ($throughEntities as $tEntity) {
                $twe = array_merge_recursive($twe, $this->resolveEntityConditions($tEntity, $this->conditions(), ':throughEntity.'));
            }

            // Remove dupes from values if present (array of IDs or other identical values from array_merge_recursive)
            foreach ($twe as $k => $v) {
                if (is_array($v)) {
                    $twe[$k] = array_unique($v);
                }
            }
        }

        if (false !== $twe) {
            // Get actual entites user wants
            $entities = $this->mapper()
                ->all($this->entityName(), $twe)
                ->order($this->relationOrder());
            $entities->snapshot();
            return $entities;
        }

        return false;
    }

    /**
     * Find first entity in the set
     *
     * @return \Spot\Entity
     */
    public function first()
    {
        return $this->toQuery()->first();
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
     * @return \Spot\Entity\ResultsetInterface
     */
    public function getIterator()
    {
        // Load related records for current row
        return ($data = $this->execute()) ? $data : [];
    }
}
