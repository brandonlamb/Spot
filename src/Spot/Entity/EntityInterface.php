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
     * Get the parsed meta data
     * @return \Spot\Entity\MetaData
     */
    public static function getMetaData();

    /**
     * Get entity meta data, containing information on columns
     * @return array
     */
    public static function metaData();

    /**
     * Get the table name for the entity.
     * @return string
     */
    #public static function getTable();

    /**
     * Get the sequence name for the entity.
     * @return string
     */
    #public static function getSequence();

    /**
     * Return defined hooks of the entity
     * @return array
     */
    public static function getHooks();

    /**
     * Return defined fields of the entity
     * @return array
     */
    #public static function getRelations();

    /**
     * Return the columns definitions
     * @return array
     */
    #public static function getColumns();

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
     * Check if the entire entity has been modified
     * @return bool
     */
    public function isEntityModified();

    /**
     * Check if a field has been modified
     * @param string $offset
     * @return bool
     */
    public function isFieldModified($offset = null);
}
