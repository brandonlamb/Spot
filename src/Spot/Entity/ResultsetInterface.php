<?php

/**
 * Interface for ResultSets
 *
 * @package \Spot\Entity
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Entity;

use Spot\Entity\EntityInterface,
    Iterator,
    Countable,
    ArrayAccess;

interface ResultsetInterface extends Iterator, Countable, ArrayAccess
{
    /**
    * Add a single entity to the resultset
    * @param \Spot\Entity $entity to add
    */
    public function add(EntityInterface $entity);

    /**
    * Merge another resultset into this resultsets set of entities
    * This will only add entitys that don't already exist in the current resultset
    * @param \Spot\Entity\ResultsetInterface $resultset
    * @return \Spot\Entity\ResultsetInterface
    * @todo Implement faster uniqueness checking by hash, entity manager, primary key field, etc.
    */
    public function merge(ResultsetInterface $resultset, $onlyUnique = true);

    /**
     * Return an array representation of the ResultSet.
     * @param mixed $keyColumn
     * @param mixed $valueColumn
     * @return array
     */
    public function toArray($keyColumn = null, $valueColumn = null);

    /**
    * Run a function on the set of entities
    * @param string|array $function A callback of the function to run
    * @return mixed
    */
    public function run($callback);

    /**
     * Runs a function on every object in the query, returning the resulting array
     * @param function The function to run
     * @return mixed An array containing the result of running the passed function
     *  on each member of the collect
     */
    public function map($func);

    /**
     * Runs a function on every object in the query, returning an array containing every
     *  object for which the function returns true.
     * @param function The function to run
     * @return mixed An array of Entity objects
     */
    public function filter($func);
}
