<?php

namespace Spot\Relation;

/**
 * DataMapper class for 'has one' relations
 *
 * @package Spot
 * @link http://spot.os.ly
 */
class HasOne extends AbstractRelation
{
    /**
     * Load query object with current relation data
     * @var Spot_Query
     */
    public $entity = null;

    /**
     * Load query object with current relation data
     *
     * @return \Spot\Query
     */
    protected function toQuery()
    {
        return $this->mapper()->all($this->entityName(), $this->conditions())->order($this->relationOrder())->limit(1);
    }

    public function entity()
    {
        if (!$this->entity) {
            $this->entity = $this->execute();
            if ($this->entity instanceof \Spot\Query) {
                $this->entity = $this->entity->first();
            }
        }
        return $this->entity;
    }

    /**
     * isset() functionality passthrough to entity
     * @return bool
     */
    public function __isset($key)
    {
        $entity = $this->execute();
        return ($entity) ? isset($entity->$key) : false;
    }

    /**
     * Getter passthrough to entity
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        $entity = $this->execute();
        return ($entity) ? $entity->$key : null;
    }

    /**
     * Setter passthrough to entity
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        $entity = $this->execute();
        if ($entity) {
            $entity->$key = $value;
        }
    }
}
