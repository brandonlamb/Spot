<?php

/**
 * Entity Manager for storing information about entities
 *
 * @package Spot\Entity
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Entity;

use Spot\Di\DiInterface,
    Spot\Di\InjectableTrait;

class Manager
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
        $entityName instanceof ResultsetInterface && $entityName = $entityName->getEntityName();
        return $entityName::getMetaData()->getTable();
    }

    /**
     * Get name of sequence for given entity class
     * @param string $entityName Name of the entity class
     * @return string
     */
    public function getSequence($entityName)
    {
        $entityName instanceof ResultsetInterface && $entityName = $entityName->getEntityName();
        return $entityName::getMetaData()->getSequence();
    }

    /**
     * Get value of primary key for given row result
     *
     * @param string $entityName Name of the entity class
     * @return string
     */
    public function getPrimaryKeys($entityName)
    {
        #$entityName instanceof EntityInterface && $entityName = $entityName->toString();
        $entityName instanceof ResultsetInterface && $entityName = $entityName->getEntityName();

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
    public function getPrimaryKeyValues(EntityInterface $entity)
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
     */
    public function getColumns($entityName, $column = null)
    {
        $entityName instanceof ResultsetInterface && $entityName = $entityName->getEntityName();
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
        $entityName instanceof ResultsetInterface && $entityName = $entityName->getEntityName();
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
        $entityName instanceof ResultsetInterface && $entityName = $entityName->getEntityName();
        return false !== $entityName::getMetaData()->getColumn($column);
    }

    /**
     * Return column type for given entity's column
     *
     * @param string $entityName Name of the entity class
     * @param string $column Column name
     * @return int Column type string or boolean false
     * @throws \InvalidArgumentException
     */
    public function getColumnType($entityName, $column)
    {
        $entityName instanceof ResultsetInterface && $entityName = $entityName->getEntityName();
        if ($column = $entityName::getMetaData()->getColumn($column)) {
            return $column->getType();
        }
        throw new \InvalidArgumentException("'$column' is not a properly configured column");
    }

    /**
     * Get defined relations
     *
     * @param string $entityName Name of the entity class
     * @return array
     */
    public function getRelations($entityName)
    {
        $entityName instanceof ResultsetInterface && $entityName = $entityName->getEntityName();
        return $entityName::getMetaData()->getRelations();
    }
}
