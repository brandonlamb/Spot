<?php

/**
 * Entity Manager for storing information about entities
 *
 * @package Spot\Manager
 */

namespace Spot\Manager;

use Spot\Di as DiContainer,
    Spot\Di\InjectableTrait,
    Spot\Entity\EntityInterface;

class EntityManager
{
    use InjectableTrait;

    /**
     * Constructor
     * @param \Spot\Di $di
     */
    public function __construct(DiContainer $di)
    {
        $this->setDi($di);
    }

    /**
     * @var array
     */
    protected $tables = [];

    /**
     * @var array, Field and relation info
     */
    protected $properties = [];
    protected $fields = [];
    protected $fieldsDefined = [];
    protected $fieldDefaultValues = [];
    protected $relations = [];
    protected $primaryKeyFields = [];

    /**
     * @var array, datasource info
     */
    protected $datasource = [];

    /**
     * Get name of table for given entity class
     * @param string $entityName Name of the entity class
     * @return string
     */
    public function getTable($entityName)
    {
        if (!isset($this->tables[$entityName])) {
            $this->fields($entityName);
        }
        return (string) $this->tables[$entityName];
    }

    /**
     * Get value of primary key for given row result
     *
     * @param string $entityName Name of the entity class
     * @return string
     */
    public function getPrimaryKeyField($entityName)
    {
        if (!isset($this->primaryKeyFields[$entityName])) {
            $this->fields($entityName);
        }
        return $this->primaryKeyFields[$entityName];
    }

    /**
     * Get value of primary key for given entity
     * @param \Spot\Entity\EntityInterface $entity Instance of an entity to find the primary key of
     * @return mixed
     */
    public function getPrimaryKey(EntityInterface $entity)
    {
        $field = $this->getPrimaryKeyField($entity->toString());
        return $entity->$field;
    }


    /**
     * Get formatted fields with all neccesary array keys and values.
     * Merges defaults with defined field values to ensure all options exist for each field.
     *
     * @param string $entityName Name of the entity class
     * @param string $field Name of the field to return attributes for
     * @return array Defined fields plus all defaults for full array of all possible options
     * @throws \Spot\Exception\Manager|\InvalidArgumentException
     */
    public function fields($entityName, $field = null)
    {
        if (!is_string($entityName)) {
            throw new \Spot\Exception\Manager(__METHOD__ . " only accepts a string. Given (" . gettype($entityName) . ")");
        }

        if (!is_subclass_of($entityName, '\\Spot\\Entity\\EntityInterface')) {
            throw new \Spot\Exception\Manager($entityName . " must be subclass of '\Spot\Entity\EntityInterface'.");
        }

        if (isset($this->fields[$entityName])) {
            $returnFields = $this->fields[$entityName];
            return null === $field ? $returnFields : $returnFields[$field];
        }

        // Datasource info
        $entityDatasource = null;
        $entityDatasource = $entityName->getTable();
        if (null === $entityDatasource || !is_string($entityDatasource)) {
            throw new \InvalidArgumentException("Entity must have a datasource defined. Please define a protected property named 'datasource' on your '" . $entityName . "' entity class.");
        }
        $this->tables[$entityName] = $entityDatasource;

        // Default settings for all fields
        $fieldDefaults = array(
            'type' => 'string',
            'default' => null,
            'length' => null,
            'required' => false,
            'null' => true,
            'unsigned' => false,
            'fulltext' => false,
            'primary' => false,
            'index' => false,
            'unique' => false,
            'serial' => false,
            'identity' => false,
            'sequence' => false,
            'relation' => false,
        );

        // Type default overrides for specific field types
        $fieldTypeDefaults = array(
            'string' => array('length' => 255),
            'float' => array('length' => array(10, 2)),
            'int' => array('length' => 10, 'unsigned' => true)
        );

        // Get entity fields from entity class
        $entityFields = $entityName::fields();

        if (!is_array($entityFields) || count($entityFields) < 1) {
            throw new \InvalidArgumentException($entityName . " Must have at least one field defined.");
        }

        $returnFields = [];
        $this->fieldDefaultValues[$entityName] = [];

        foreach ($entityFields as $fieldName => $fieldOpts) {
            // Store field definition exactly how it is defined before modifying it below
            if ($fieldOpts['type'] != 'relation') {
                $this->fieldsDefined[$entityName][$fieldName] = $fieldOpts;
            }

            // Format field will full set of default options
            if (isset($fieldOpts['type']) && isset($fieldTypeDefaults[$fieldOpts['type']])) {
                // Include type defaults
                $fieldOpts = array_merge($fieldDefaults, $fieldTypeDefaults[$fieldOpts['type']], $fieldOpts);
            } else {
                // Merge with defaults
                $fieldOpts = array_merge($fieldDefaults, $fieldOpts);
            }

            // Store primary key
            if (true === $fieldOpts['primary']) {
                $this->primaryKeyFields[$entityName] = $fieldName;
            }

            // Store default value
            if (null !== $fieldOpts['default']) {
                $this->fieldDefaultValues[$entityName][$fieldName] = $fieldOpts['default'];
            } else {
                $this->fieldDefaultValues[$entityName][$fieldName] = null;
            }

            $returnFields[$fieldName] = $fieldOpts;
            $this->fields[$entityName] = $returnFields;

            // Relations
            $entityRelations = [];
            $entityRelations = $entityName::relations();

            if (!is_array($entityRelations)) {
                throw new \InvalidArgumentException($entityName . " Relation definitons must be formatted as an array.");
            }

            foreach ($entityRelations as $relationAlias => $relationOpts) {
                $this->relations[$entityName][$relationAlias] = $relationOpts;
            }
        }
        return null === $field ? $returnFields : $returnFields[$field];
    }

    /**
     * Get field information exactly how it is defined in the class
     *
     * @param string $entityName Name of the entity class
     * @return array Array of field key => value pairs
     */
    public function fieldsDefined($entityName)
    {
        if (!isset($this->fieldsDefined[$entityName])) {
            $this->fields($entityName);
        }
        return $this->fieldsDefined[$entityName];
    }

    /**
     * Get field default values as defined in class field definitons
     *
     * @param string $entityName Name of the entity class
     * @return array Array of field key => value pairs
     */
    public function fieldDefaultValues($entityName)
    {
        if (!isset($this->fieldDefaultValues[$entityName])) {
            $this->fields($entityName);
        }
        return $this->fieldDefaultValues[$entityName];
    }

    /**
     * Get defined relations
     *
     * @param string $entityName Name of the entity class
     * @return array
     */
    public function relations($entityName)
    {
        $this->fields($entityName);
        if (!isset($this->relations[$entityName])) {
            return [];
        }
        return $this->relations[$entityName];
    }

    /**
     * Check if field exists in defined fields
     *
     * @param string $entityName Name of the entity class
     * @param string $field Field name to check for existence
     * @return bool
     */
    public function fieldExists($entityName, $field)
    {
        return array_key_exists($field, $this->fields($entityName));
    }

    /**
     * Return field type for given entity's field
     *
     * @param string $entityName Name of the entity class
     * @param string $field Field name
     * @return mixed Field type string or boolean false
     */
    public function fieldType($entityName, $field)
    {
        $fields = $this->fields($entityName);
        return $this->fieldExists($entityName, $field) ? $fields[$field]['type'] : false;
    }
}
