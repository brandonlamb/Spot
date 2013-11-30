<?php

/**
 * Entity Manager for storing information about entities
 *
 * @package Spot\Manager
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Manager;

use Spot\Di\DiInterface,
    Spot\Di\InjectableTrait,
    Spot\Entity\EntityInterface,
    Spot\Entity\ResultSetInterface;

class EntityManager
{
    use InjectableTrait;

    /**
     * @var array
     */
    protected $primaryKeyColumns = [];

    /**
     * Constructor
     * @param \Spot\Di\DiInterface $di
     */
    public function __construct(DiInterface $di)
    {
        $this->setDi($di);
    }

    /**
     * Get name of table for given entity class
     * @param string $entityName Name of the entity class
     * @return string
     */
    public function getTable($entityName)
    {
        $entityName instanceof ResultSetInterface && $entityName = $entityName->getEntityName();
        return $entityName::getMetaData()->getTable();
    }

    /**
     * Get value of primary key for given row result
     *
     * @param string $entityName Name of the entity class
     * @return string
     */
    public function getPrimaryKeys($entityName)
    {
        $entityName instanceof EntityInterface && $entityName = $entityName->toString();

        // Store primary key(s)
        if (!isset($this->primaryKeyColumns[$entityName])) {
            $this->primaryKeyColumns[$entityName] = [];
            foreach ($entityName::getMetaData()->getColumns() as $column) {
                $column->isPrimary() && $this->primaryKeyColumns[$entityName][] = $column->getName();
            }
        }

        return $this->primaryKeyColumns[$entityName];
    }

    /**
     * Get value of primary key for given entity
     * @param \Spot\Entity\EntityInterface $entity Instance of an entity to find the primary key of
     * @return mixed
     */
    public function getPrimaryKeysValue(EntityInterface $entity)
    {
        $values = [];
        foreach ($this->getPrimaryKeys($entity->toString()) as $pk) {
            $values[$pk] = $entity->get($pk);
        }
        return $values;
    }

    /**
     * Get formatted columns with all neccesary array keys and values.
     * Merges defaults with defined field values to ensure all options exist for each field.
     *
     * @param string $entityName Name of the entity class
     * @param string $column Name of the field to return attributes for
     * @return array Defined columns plus all defaults for full array of all possible options
     * @throws \Spot\Exception\Manager|\InvalidArgumentException
     */
    public function getColumns($entityName, $column = null)
    {
        if (!is_string($entityName)) {
            throw new \Spot\Exception\Manager(__METHOD__ . " only accepts a string. Given (" . gettype($entityName) . ")");
        }

        if (!is_subclass_of($entityName, '\\Spot\\Entity\\EntityInterface')) {
            throw new \Spot\Exception\Manager(__METHOD__ . ": $entityName must be subclass of '\Spot\Entity\EntityInterface'.");
        }

        $metaData = $entityName::getMetaData();
        return null === $column ? $metaData->getColumns() : $metaData->getColumn($column);
    }

    /**
     * Get column default values as defined in class column definitons
     *
     * @param string $entityName Name of the entity class
     * @return array Array of field key => value pairs
     */
    public function getDefaultColumnValues($entityName)
    {
        return $entityName::getMetaData()->getDefault();
    }

    /**
     * Check if column exists in defined columns
     *
     * @param string $entityName Name of the entity class
     * @param string $column Column name to check for existence
     * @return bool
     */
    public function hasColumn($entityName, $column)
    {
        return false !== $entityName::getMetaData()->getColumn($column);
    }

    /**
     * Return column type for given entity's column
     *
     * @param string $entityName Name of the entity class
     * @param string $column Column name
     * @return int|bool Column type string or boolean false
     */
    public function getColumnType($entityName, $column)
    {
        if ($column = $entityName::getMetaData()->getColumn($column)) {
            return $column->getType();
        }
        return false;
    }

    /**
     * Get defined relations
     *
     * @param string $entityName Name of the entity class
     * @return array
     */
    public function getRelations($entityName)
    {
        return $entityName::getMetaData()->getRelations();
    }
}
