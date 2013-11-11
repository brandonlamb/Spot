<?php

/**
 * Trait for classes that need relation manager and helper methods
 * @package Spot\Relation
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Relation;

trait HasRelationManagerTrait
{
    /**
     * @var \Spot\Relation\Manager
     */
    protected $relationManager;

    /**
     * Set the relation manager
     * @param \Spot\Relation\Manager
     * @return self
     */
    public function setRelationManager(Manager $relationManager)
    {
        $this->relationManager = $relationManager;
        return $this;
    }

    /**
     * Get the relation manager
     * @return \Spot\Relation\Manager
     */
    public function getRelationManager()
    {
        return $this->relationManager;
    }
}
