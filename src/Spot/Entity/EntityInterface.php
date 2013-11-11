<?php

/**
 * Interface for Entity classes
 *
 * @package \Spot\Entity
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Entity;

interface EntityInterface
{
    /**
     * Getter for field properties
     * @param string $offset
     * @param mixed $default
     * @return mixed
     */
    public function get($offset, $default = null);

    /**
     * Setter for field properties
     * @param string $offset
     * @param mixed $value
     * @return \Spot\Entity\EntityInterface
     */
    public function set($offset, $value);

    /**
     * Set the schema name for the entity.
     * @param string $schema, The name of the schema
     * @return \Spot\Entity\EntityInterface
     */
    public function setSchema($schema);

    /**
     * Get the schema name for the entity.
     * @return string
     */
    public function getSchema();

    /**
     * Set the table for the entity
     * @param string $table, The name of the table
     * @return \Spot\Entity\EntityInterface
     */
    public function setTable($table);

    /**
     * Get the table name for the entity.
     * @return string
     */
    public function getTable();

    /**
     * Set the sequence name for the entity.
     * @param string $sequence, The name of the sequence, (ie posts_id_seq)
     * @return \Spot\Entity\EntityInterface
     */
    public function setSequence($sequence);

    /**
     * Get the sequence name for the entity.
     * @return string
     */
    public function getSequence();

    /**
     * Set the entity data
     * @param array $data
     * @param bool $modified
     * @return \Spot\Entity\EntityInterface
     */
    public function setData(array $data, $modified = true);

    /**
     * Get the entity data
     * @return array
     */
    public function getData();

    /**
     * Gets data that has been modified since object creation,
     * optionally allowing for selecting a single field
     * @param string $field
     * @return array
     */
    public function getModified($field = null);

    /**
     * Gets data that has not been modified since object creation,
     * optionally allowing for selecting a single field
     * @param string $field
     * @return mixed
     */
    public function getUnmodified($field = null);

    /**
     * Check if a field or entire entity has been modified. If no field name
     * is passed in then return whether any fields have been changed
     * @param string $offset
     * @return bool
     */
    public function isModified($offset = null);
}
