<?php

/**
 * Abstract class for relations
 *
 * @package Spot\Entity
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Entity;

use Spot\Mapper,
    Spot\Entity\EntityInterface,
    Spot\Entity\ResultsetInterface,
    Spot\Entity\Manager as EntityManager;

interface RelationInterface
{
    /**
     * Get source entity object
     * @return \Spot\Entity\EntityInterface
     */
    public function sourceEntity();

    /**
     * Get related entity name
     * @return string
     */
    public function entityName();

    /**
     * Get mapper instance
     * @return \Spot\Mapper
     */
    public function getMapper();

    /**
     * Get unresolved foreign key relations
     * @return array
     */
    public function unresolvedConditions();

    /**
     * Get WHERE conditions
     * @return array
     */
    public function conditions();

    /**
     * Replace entity value placeholders on relation definitions
     * Currently replaces ':entity.[col]' with the field value from the passed entity object
     * @param \Spot\Entity\EntityInterface|\Spot\Entity\ResultsetInterface $entity
     * @param array $selects
     * @param string $replace
     * @return array
     */
    public function resolveEntitySelects($entity, array $selects, $replace = ':entity.');

    /**
     * Replace entity value placeholders on relation definitions
     * Currently replaces ':entity.[col]' with the field value from the passed entity object
     * @param \Spot\Entity\EntityInterface|\Spot\Entity\ResultsetInterface $entity
     * @param array $joins
     * @param string $replace
     * @return array
     */
    public function resolveEntityJoins($entity, array $joins, $replace = ':entity.');

    /**
     * Replace entity value placeholders on relation definitions
     * Currently replaces ':entity.[col]' with the field value from the passed entity object
     * @param \Spot\Entity\EntityInterface|\Spot\Entity\ResultsetInterface $entity
     * @param array $conditions
     * @param string $replace
     * @return array
     */
    public function resolveEntityConditions($entity, array $conditions, $replace = ':entity.');

    /**
     * Get sorting for relations
     * @return array
     */
    public function relationOrder();

    /**
     * Get entity name of relation class
     * @return array
     */
    public function relationEntityName();

    /**
     * Fetch and cache returned query object from internal toQuery() method
     * @param \Spot\Entity\ResultsetInterface
     */
    public function execute();

    /**
     * Manually assign a resultset to prevent execute() from firing
     * @param array $resultset
     * @return AbstractRelation
     */
    public function setResultset($resultset);
}
