<?php

/**
 * Collection of Spot Event objects
 *
 * @package Spot\Events
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Events;

#use Spot\Entity\CollectionInterface;

#class Collection implements CollectionInterface
class Collection
{
    /**
     * @var array
     */
    protected $storage = [];

    /**
     * @var string, class name of the entity to create instances for
     */
    protected $entityName;

    /**
     * Constructor function
     * @param string $entityName
     */
    public function __construct($entityName = null)
    {
        $this->entityName = $entityName;
    }

    /**
     * Returns first result in set
     * @return The first result in the set
     */
    public function first()
    {
        $this->rewind();
        return $this->current();
    }

    /**
    * {@inherit}
    */
    public function addEvent($event, \Spot\Entity $entity)
    {
        if (!isset($this->storage[$event])) {
            $this->storage[$event] = [$callable];
        } else {
            $this->storage[$event][] = $callable;
        }
    }

    /**
     * SPL Countable, Iterator, ArrayAccess functions
     */

    public function count()
    {
        return count($this->storage);
    }

    public function current()
    {
        return current($this->storage);
    }

    public function key()
    {
        return key($this->storage);
    }

    public function next()
    {
        next($this->storage);
    }

    public function rewind()
    {
        reset($this->storage);
    }

    public function valid()
    {
        return false !== current($this->storage);
    }

    public function offsetExists($key)
    {
        return isset($this->storage[$key]);
    }

    public function & offsetGet($key)
    {
        return $this->storage[$key];
    }

    public function offsetSet($key, $value)
    {
        if (null === $key) {
            $this->storage[] = $value;
        } else {
            $this->storage[$key] = $value;
        }
    }

    public function offsetUnset($key)
    {
        if (is_int($key)) {
            array_splice($this->storage, $key, 1);
        } else {
            unset($this->storage[$key]);
        }
    }
}
