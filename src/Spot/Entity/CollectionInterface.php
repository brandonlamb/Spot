<?php
namespace Spot\Entity;

interface CollectionInterface extends \Iterator, \Countable, \ArrayAccess
{
    /**
    * Add a single entity to the collection
    *
    * @param \Spot\Entity $entity to add
    */
    public function add(\Spot\Entity $entity);

    /**
    * Merge another collection into this collections set of entities
    * This will only add entitys that don't already exist in the current
    * collection
    *
    * @param \Spot\Entity\CollectionInterface $collection
    * @return \Spot\Entity\CollectionInterface
    * @todo Implement faster uniqueness checking by hash, entity manager, primary key field, etc.
    */
    public function merge(\Spot\Entity\CollectionInterface $collection, $onlyUnique = true);

    /**
     * Return an array representation of the Collection.
     *
     * @param mixed $keyColumn
     * @param mixed $valueColumn
     * @return array
     */
    public function toArray($keyColumn = null, $valueColumn = null);

    /**
    * Run a function on the set of entities
    *
    * @param string|array $function A callback of the function to run
    * @return mixed
    */
    public function run($callback);

    /**
     * Runs a function on every object in the query, returning the resulting array
     *
     * @param function The function to run
     * @return mixed An array containing the result of running the passed function
     *  on each member of the collect
     */
    public function map($func);

    /**
     * Runs a function on every object in the query, returning an array containing every
     *  object for which the function returns true.
     *
     * @param function The function to run
     * @return mixed An array of Entity objects
     */
    public function filter($func);
}
