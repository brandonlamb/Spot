<?php

/**
 * Trait for classes that need entity manager and helper methods
 * @package Spot\Entity
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Entity;

trait HasEntityManagerTrait
{
    /**
     * @var \Spot\Entity\Manager
     */
    protected $entityManager;

    /**
     * Set the entity manager
     * @param \Spot\Entity\Manager
     * @return self
     */
    public function setEntityManager(Manager $entityManager)
    {
        $this->entityManager = $entityManager;
        return $this;
    }

    /**
     * Get the entity manager
     * @return \Spot\Entity\Manager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }
}
