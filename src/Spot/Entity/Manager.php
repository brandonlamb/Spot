<?php

namespace Spot\Entity;

/**
 * Entity Manager for storing information about entities
 *
 * @package Spot
 */
class Manager
{
    /**
     * @var array, Field and relation info
     */
    protected static $properties = array();
    protected static $fields = array();
    protected static $fieldsDefined = array();
    protected static $fieldDefaultValues = array();
    protected static $relations = array();
    protected static $primaryKeyField = array();

    /**
     * @var array, Connection and datasource info
     */
    protected static $connection = array();
    protected static $datasource = array();
    protected static $datasourceOptions = array();

    /**
     * Get formatted fields with all neccesary array keys and values.
     * Merges defaults with defined field values to ensure all options exist for each field.
     *
     * @param string $entityName Name of the entity class
     * @param string $field Name of the field to return attributes for
     * @return array Defined fields plus all defaults for full array of all possible options
     */
    public function fields($entityName, $field = null)
    {
        if (!is_string($entityName)) {
            throw new \Spot\Exception(__METHOD__ . " only accepts a string. Given (" . gettype($entityName) . ")");
        }

        if (!is_subclass_of($entityName, '\Spot\Entity')) {
            throw new \Spot\Exception($entityName . " must be subclass of '\Spot\Entity'.");
        }

        if (isset(static::$fields[$entityName])) {
            $returnFields = static::$fields[$entityName];
        } else {
            // Datasource info
            $entityDatasource = null;
            $entityDatasource = $entityName::datasource();
            if (null === $entityDatasource || !is_string($entityDatasource)) {
                echo "\n\n" . $entityName . "::datasource() = " . var_export($entityName::datasource(), true) . "\n\n";
                throw new \InvalidArgumentException("Entity must have a datasource defined. Please define a protected property named 'datasource' on your '" . $entityName . "' entity class.");
            }
            static::$datasource[$entityName] = $entityDatasource;

            // Datasource Options
            $entityDatasourceOptions = $entityName::datasourceOptions();
            static::$datasourceOptions[$entityName] = $entityDatasourceOptions;

            // Connection info
            $entityConnection = $entityName::connection();

            // If no adapter specified, Spot will use default one from config object (or first one set if default is not explicitly set)
            static::$connection[$entityName] = ($entityConnection) ? $entityConnection : false;

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
                'relation' => false
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

            $returnFields = array();
            static::$fieldDefaultValues[$entityName] = array();

            foreach ($entityFields as $fieldName => $fieldOpts) {
                // Store field definition exactly how it is defined before modifying it below
                if ($fieldOpts['type'] != 'relation') {
                    static::$fieldsDefined[$entityName][$fieldName] = $fieldOpts;
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
                    static::$primaryKeyField[$entityName] = $fieldName;
                }

                // Store default value
                if (null !== $fieldOpts['default']) {
                    static::$fieldDefaultValues[$entityName][$fieldName] = $fieldOpts['default'];
                } else {
                    static::$fieldDefaultValues[$entityName][$fieldName] = null;
                }

                $returnFields[$fieldName] = $fieldOpts;
            }
            static::$fields[$entityName] = $returnFields;

            // Relations
            $entityRelations = array();
            $entityRelations = $entityName::relations();

            if (!is_array($entityRelations)) {
                throw new \InvalidArgumentException($entityName . " Relation definitons must be formatted as an array.");
            }

            foreach ($entityRelations as $relationAlias => $relationOpts) {
                static::$relations[$entityName][$relationAlias] = $relationOpts;
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
        if (!isset(static::$fieldsDefined[$entityName])) {
            $this->fields($entityName);
        }
        return static::$fieldsDefined[$entityName];
    }

    /**
     * Get field default values as defined in class field definitons
     *
     * @param string $entityName Name of the entity class
     * @return array Array of field key => value pairs
     */
    public function fieldDefaultValues($entityName)
    {
        if (!isset(static::$fieldDefaultValues[$entityName])) {
            $this->fields($entityName);
        }
        return static::$fieldDefaultValues[$entityName];
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
        if (!isset(static::$relations[$entityName])) {
            return array();
        }
        return static::$relations[$entityName];
    }

    /**
     * Get value of primary key for given row result
     *
     * @param string $entityName Name of the entity class
     * @return string
     */
    public function primaryKeyField($entityName)
    {
        if (!isset(static::$primaryKeyField[$entityName])) {
            $this->fields($entityName);
        }
        return static::$primaryKeyField[$entityName];
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
     * Return field type
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

    /**
     * Get defined connection to use for entity
     *
     * @param string $entityName Name of the entity class
     * @return string
     */
    public function connection($entityName)
    {
        $this->fields($entityName);
        if (!isset(static::$connection[$entityName])) {
            return false;
        }
        return static::$connection[$entityName];
    }

    /**
     * Get name of datasource for given entity class
     *
     * @param string $entityName Name of the entity class
     * @return string
     */
    public function datasource($entityName)
    {
        if (!isset(static::$datasource[$entityName])) {
            $this->fields($entityName);
        }
        return static::$datasource[$entityName];
    }

    /**
     * Get datasource options for given entity class
     *
     * @param array Options to pass
     * @return string
     */
    public function datasourceOptions($entityName)
    {
        if (!isset(static::$datasourceOptions[$entityName])) {
            $this->fields($entityName);
        }
        return static::$datasourceOptions[$entityName];
    }
}
