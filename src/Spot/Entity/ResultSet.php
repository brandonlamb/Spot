<?php

/**
 * Collection of Spot\Entity objects
 *
 * @package Spot\Entity
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Entity;

use Spot\Entity\ResultSetInterface,
    Spot\Entity\EntityInterface;

class ResultSet implements ResultSetInterface
{
    /**
     * @var array
     */
    protected $results = [];

    /**
     * @var array
     */
    protected $resultsIdentities = [];

    /**
     * @var string, class name of the entity to create instances for
     */
    protected $entityName;

    /**
     * Constructor function
     * @param array $results Array of pre-loaded Spot_Entity objects or Iterator that will fetch them lazily
     * @param array $resultsIdentities Array of key values for given result set primary key
     * @param string $entityName
     */
    public function __construct(array $results = [], array $resultsIdentities = [], $entityName = null)
    {
        $this->results = $results;
        $this->resultsIdentities = $resultsIdentities;
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
    public function add(EntityInterface $entity)
    {
        $this->results[] = $entity;
    }

    /**
     * {@inherit}
     * @todo Implement faster uniqueness checking by hash, entity manager, primary key field, etc.
     */
    public function merge(ResultSetInterface $collection, $onlyUnique = true)
    {
        foreach ($collection as $entity) {
            if ($onlyUnique && in_array($entity, $this->results)) {
                // Skip - entity already exists in collection
                continue;
            }
            $this->add($entity);
        }
        return $this;
    }

    /**
     * Return an array representation of the Collection.
     *
     * {@inherit}
     * @return array If $keyColumn and $valueColumn are not set, or are both null
     * then this will return the array of entity objects
     * @return array If $keyColumn is not null, and the value column is null or undefined
     * then this will return an array of the values of the entities in the column defined
     * @return array If $keyColumn and $valueColumn are both defined and not null
     * then this will return an array where the key is defined by each entities value in $keyColumn
     * and the value will be the value of the each entity in $valueColumn
     * @todo Write unit tests for this function
     */
    public function toArray($keyColumn = null, $valueColumn = null)
    {
        // Both empty
        if (null === $keyColumn && null === $valueColumn) {
            $return = [];
            foreach ($this->results as $row) {
                $return[] = $row->toArray();
            }

        // Key column name
        } else if (null !== $keyColumn && null === $valueColumn) {
            $return = [];
            foreach ($this->results as $row) {
                $return[] = $row->$keyColumn;
            }

        // Both key and valud columns filled in
        } else {
            $return = [];
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
         return call_user_func_array($callback, [$this->results]);
    }

    /**
     * {@inherit}
     */
    public function map($func)
    {
        $ret = [];
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
     * String representation of the class
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

    /**
     * Provides a string representation of the class
     * Brackets contain the number of elements contained
     * in the collection
     * @return string
     */
    public function toString()
    {
        return get_called_class() . '[' . $this->count() . ']';
    }

    /**
     * Get entity name of collection
     * @return string
     */
    public function getEntityName()
    {
        return (string) $this->entityName;
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
        return (current($this->results) !== false);
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
            $this->results[] = $value;
        } else {
            $this->results[$key] = $value;
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
