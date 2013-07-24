<?php

namespace Spot\Entity;

/**
 * Collection of Spot_Entity objects
 *
 * @package Spot
 * @link http://spot.os.ly
 */
class Collection implements CollectionInterface, \Iterator, \Countable, \ArrayAccess
{
    /**
     * @var array
     */
    protected $results = array();

    /**
     * @var array
     */
    protected $resultsIdentities = array();

    /**
     * @var string
     */
    protected $entityName;

    /**
     * Constructor function
     *
     * @param array $results Array of pre-loaded Spot_Entity objects or Iterator that will fetch them lazily
     * @param array $resultsIdentities Array of key values for given result set primary key
     * @param string $entityName
     */
    public function __construct(array $results = array(), array $resultsIdentities = array(), $entityName = null)
    {
        $this->results = $results;
        $this->resultsIdentities = $resultsIdentities;
        $this->entityName = $entityName;
    }

    /**
     * Returns first result in set
     *
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
    public function add(\Spot\Entity $entity)
    {
        $this->results[] = $entity;
    }

    /**
     * {@inherit}
     * @todo Implement faster uniqueness checking by hash, entity manager, primary key field, etc.
     */
    public function merge(\Spot\Entity\CollectionInterface $collection, $onlyUnique = true)
    {
        foreach ($collection as $entity) {
            if ($onlyUnique && in_array($entity, $this->results)) {
                continue; // Skip - entity already exists in collection
            }
            $this->add($entity);
        }
        return $this;
    }

    /**
     * Return an array representation of the Collection.
     *
     * {@inherit}
     * @return array    If $keyColumn and $valueColumn are not set, or are both null
     *                      then this will return the array of entity objects
     * @return array    If $keyColumn is not null, and the value column is null or undefined
     *                      then this will return an array of the values of the entities in the column defined
     * @return array   If $keyColumn and $valueColumn are both defined and not null
     *                      then this will return an array where the key is defined by each entities value in $keyColumn
     *                      and the value will be the value of the each entity in $valueColumn
     *
     * @todo Write unit tests for this function
     */
    public function toArray($keyColumn = null, $valueColumn = null)
    {
        // Both empty
        if (null === $keyColumn && null === $valueColumn) {
            $return = array();
            foreach ($this->results as $row) {
                $return[] = $row->toArray();
            }

        // Key column name
        } elseif (null !== $keyColumn && null === $valueColumn) {
            $return = array();
            foreach ($this->results as $row) {
                $return[] = $row->$keyColumn;
            }

        // Both key and valud columns filled in
        } else {
            $return = array();
            foreach ($this->results as $row) {
                $return[$row->$keyColumn] = $row->$valueColumn;
            }
        }

        return $return;
    }

    /**
     * {@inherit}
     */
    public function run($callback)
    {
         return call_user_func_array($callback, array($this->results));
    }

    /**
     * {@inherit}
     */
    public function map($func)
    {
        $ret = array();
        foreach ($this as $obj) {
            $ret[] = $func($obj);
        }
        return $ret;
    }

    /**
     * {@inherit}
     */
    public function filter($func)
    {
        $ret = new static();
        foreach ($this as $obj) {
            if ($func($obj)) {
                $ret->add($obj);
            }
        }
        return $ret;
    }

    /**
    * Provides a string representation of the class
    * Brackets contain the number of elements contained
    * in the collection
    * @return string
    */
    public function __toString()
    {
        return __CLASS__ . '[' . $this->count() . ']';
    }

    /**
     * SPL Countable, Iterator, ArrayAccess functions
     */

    public function count()
    {
        return count($this->results);
    }

    public function current()
    {
        return current($this->results);
    }

    public function key()
    {
        return key($this->results);
    }

    public function next()
    {
        next($this->results);
    }

    public function rewind()
    {
        reset($this->results);
    }

    public function valid()
    {
        return (current($this->results) !== FALSE);
    }

    public function offsetExists($key)
    {
        return isset($this->results[$key]);
    }

    public function & offsetGet($key)
    {
        return $this->results[$key];
    }

    public function offsetSet($key, $value)
    {
        if ($key === null) {
            return $this->results[] = $value;
        } else {
            return $this->results[$key] = $value;
        }
    }

    public function offsetUnset($key)
    {
        if (is_int($key)) {
            array_splice($this->results, $key, 1);
        } else {
            unset($this->results[$key]);
        }
    }
}
