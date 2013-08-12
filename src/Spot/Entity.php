<?php

namespace Spot;

/**
 * Entity object
 *
 * @package Spot
 */
abstract class Entity implements \Serializable
{
    /**
     * @var string, the name of the entity's schema
     */
    protected static $schema;

    /**
     * @var string, the name of the sequence to use when insert new entitys
     */
    protected static $sequence;

    /**
     * @var string, the table name for the entity
     */
    protected static $datasource;

    /**
     * @var array
     */
    protected static $datasourceOptions = array();

    /**
     * @var string, specific named connection to use for this entity
     */
    protected static $connection;

    /**
     * @var array, Entity data storage
     */
    protected $data = array();

    /**
     * @var array, Entity modified data storage
     */
    protected $dataModified = array();

    /**
     * @var array, ignored getter properties. Add a field/column here to not
     * attempt calling its getter method. For example, given an entity with a
     * "name" property and a "getName()" method, where you do *not want to call
     * "getName()" when accessing entity->name, add "name" to this array
     */
    protected $getterIgnore = array();

    /**
     * @var array, ignored setter properties. Add a field/column here to not
     * attempt calling its setter method. For example, given an entity with a
     * "name" property and a "setName()" method, where you do *not want to call
     * "setName()" when performing a entity->name = 'value' operation,
     * add "name" to this array
     */
    protected $setterIgnore = array();

    /**
     * @var array, Entity error messages (may be present after save attempt)
     */
    protected $errors = array();

    /**
     * Constructor. Allows setting object properties with array on construct
     * @param array $data
     */
    public function __construct(array $data = array())
    {
        $this->initFields();
        $data && $this->data($data, false);
    }

    /**
     * Get/set the schema name for the entity.
     * @param string $schema, The name of the schema
     * @return string
     */
    public static function schema($schema = null)
    {
        null !== $schema && static::$schema = $schema;
        return static::$schema;
    }

    /**
     * Get/set the sequence name for the entity.
     * @param string $sequence, The name of the sequence, (ie posts_id_seq)
     * @return string
     */
    public static function sequence($sequence = null)
    {
        null !== $sequence && static::$sequence = $sequence;
        return static::$sequence;
    }

    /**
     * Get/set the datasource for the entity (table name)
     * @param string $datasource, The table name
     * @return string
     */
    public static function datasource($datasource = null)
    {
        null !== $datasource && static::$datasource = $datasource;
        return static::$datasource;
    }

    /**
     * Datasource options getter/setter
     * @param array $dsOpts
     * @return array
     */
    public static function datasourceOptions(array $dsOpts = null)
    {
        null !== $dsOpts && static::$datasourceOptions = $dsOpts;
        return static::$datasourceOptions;
    }

    /**
     * Named connection getter/setter. This allows defining a specific connection
     * name to use (when using multiple DB connections) for this entity. For example,
     * if you have two connections "read" and "readwrite" and you want this entity
     * to always use the "readwrite" connection, you can set it here.
     * @param string $connection
     * @return string
     */
    public static function connection($connection = null)
    {
        null !== $connection && static::$connection = $connection;
        return static::$connection;
    }

    /**
     * Return defined fields of the entity
     * @return array
     */
    public static function fields()
    {
        return array();
    }

    /**
     * Return defined hooks of the entity
     * @return array
     */
    public static function hooks()
    {
        return array();
    }

    /**
     * Return defined fields of the entity
     * @return array
     */
    public static function relations()
    {
        return array();
    }

    /**
     * Set all field values to their defualts or null
     * @return $this
     */
    protected function initFields()
    {
        $fields = static::fields();
        foreach ($fields as $field => $opts) {
            if (!isset($this->data[$field])) {
                $this->data[$field] = isset($opts['default']) ? $opts['default'] : null;
            }
        }
        return $this;
    }

    /**
     * Gets and sets data on the current entity
     * @param array $data
     * @param bool $modified
     */
    public function data(array $data = null, $modified = true)
    {
        // GET
        if (null === $data || !$data) {
            return array_merge($this->data, $this->dataModified);
        }

        // SET
        if (is_object($data) || is_array($data)) {
            $fields = $this->fields();
            foreach ($data as $k => $v) {
                // Ensure value is set with type handler if Entity field type
                if (array_key_exists($k, $fields)) {
                    $typeHandler = Config::getTypeHandler($fields[$k]['type']);
                    $v = $typeHandler::set($this, $v);
                }

                if (true === $modified) {
                    $this->dataModified[$k] = $v;
                } else {
                    $this->data[$k] = $v;
                }
            }
            return $this;
        } else {
            throw new \InvalidArgumentException(__METHOD__ . " Expected array or object input - " . gettype($data) . " given");
        }
    }

    /**
     * Return array of field data with data from the field names listed removed
     *
     * @param array $except, List of field names to exclude in data list returned
     * @return array
     */
    public function dataExcept(array $except)
    {
        return array_diff_key($this->data(), array_flip($except));
    }

    /**
     * Gets data that has been modified since object construct,
     * optionally allowing for selecting a single field
     * @param string $field
     * @return array
     */
    public function dataModified($field = null)
    {
        if (null !== $field) {
            return isset($this->dataModified[$field]) ? $this->dataModified[$field] : null;
        }
        return $this->dataModified;
    }

    /**
     * Gets data that has not been modified since object construct,
     * optionally allowing for selecting a single field
     * @param string $field
     * @return mixed
     */
    public function dataUnmodified($field = null)
    {
        if (null !== $field) {
            return isset($this->data[$field]) ? $this->data[$field] : null;
        }
        return $this->data;
    }

    /**
     * Returns true if a field has been modified.
     * If no field name is passed in, return whether any fields have been changed
     * @param string $field
     * @return bool
     */
    public function isModified($field = null)
    {
        if (null !== $field) {
            if (array_key_exists($field, $this->dataModified)) {
                if (is_null($this->dataModified[$field]) || is_null($this->data[$field])) {
                    // Use strict comparison for null values, non-strict otherwise
                    return $this->dataModified[$field] !== $this->data[$field];
                }
                return $this->dataModified[$field] != $this->data[$field];
            } else if (array_key_exists($field, $this->data)) {
                return false;
            } else {
                return null;
            }
        }
        return !!count($this->dataModified);
    }

    /**
     * Check if any errors exist
     *
     * @param string $field OPTIONAL field name
     * @return bool
     */
    public function hasErrors($field = null)
    {
        if (null !== $field) {
            return isset($this->errors[$field]) ? count($this->errors[$field]) > 0 : false;
        }
        return count($this->errors) > 0;
    }

    /**
     * Error message getter/setter
     *
     * @param $field string|array String return errors with field key, array sets errors
     * @return self|array|boolean Setter return self, getter returns array or boolean if key given and not found
     */
    public function errors($msgs = null)
    {
        // Return errors for given field
        if (is_string($msgs)) {
            return isset($this->errors[$msgs]) ? $this->errors[$msgs] : array();

        // Set error messages from given array
        } elseif (is_array($msgs)) {
            $this->errors = $msgs;
        }
        return $this->errors;
    }

    /**
     * Add an error to error messages array
     *
     * @param string $field Field name that error message relates to
     * @param mixed $msg Error message text - String or array of messages
     * @return $this
     */
    public function error($field, $msg)
    {
        if (is_array($msg)) {
            // Add array of error messages about field
            foreach ($msg as $msgx) {
                $this->errors[$field][] = $msgx;
            }
        } else {
            // Add to error array
            $this->errors[$field][] = $msg;
        }
        return $this;
    }

    /**
     * Getter for field properties
     * @param string $field
     * @param mixed $default, value to return if field doesnt exist
     * @return mixed
     */
    public function & get($field, $default = null)
    {
        // Check for custom getter method (override)
        $getMethod = 'get' . $field;

        $value = null;

        // We can't use isset for dataModified because it returns false for NULL values
        if (array_key_exists($field, $this->dataModified)) {
            $value =  $this->dataModified[$field];
        } elseif (isset($this->data[$field])) {
            $value = $this->data[$field];
        } else if (method_exists($this, $getMethod) && !array_key_exists($field, $this->getterIgnore)) {
            // Tell this function to ignore the overload on further calls for this variable
            $this->getterIgnore[$field] = 1;

            // Call custom getter
            $value = $this->$getMethod();

            // Remove ignore rule
            unset($this->getterIgnore[$field]);
        } else {
            $value = $default;
        }

        return $value;
    }

    /**
     * Setter for field properties
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    public function set($field, $value)
    {
        // Check for custom setter method (override)
        $setMethod = 'set' . $field;

        $fields = $this->fields();

        // Run value through a filter call if set
        if (isset($fields[$field]['filter'])) {
            $value = call_user_func($fields[$field]['filter'], $value);
        } else if (method_exists($this, $setMethod) && !array_key_exists($field, $this->setterIgnore)) {
            // Tell this function to ignore the overload on further calls for this variable
            $this->_setterIgnore[$field] = 1;

            // Call custom setter
            $value = $this->$setMethod($value);

            // Remove ignore rule
            unset($this->setterIgnore[$field]);
        } else if (isset($fields[$field])) {
            // Ensure value is set with type handler
            $typeHandler = Config::getTypeHandler($fields[$field]['type']);
            $value = $typeHandler::set($this, $value);
        }

        // Set the data value
        $this->dataModified[$field] = $value;

        return $this;
    }

    /**
     * Enable isset() for object properties
     * @return bool
     */
    public function __isset($key)
    {
        return isset($this->data[$key]) || isset($this->dataModified[$key]);
    }

    /**
     * Getter for field properties
     * @param string $field
     * @return mixed
     */
    public function & __get($field)
    {
        return $this->get($field);
    }

    /**
     * Setter for field properties
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    public function __set($field, $value)
    {
        return $this->set($field, $value);
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
     * String representation of the class
     * @return string
     */
    public function toString()
    {
        return get_called_class();
    }

    /**
     * Alias of self::data()
     * @return array
     */
    public function toArray()
    {
        return $this->data();
    }

    /**
     * json encoded representation of data
     * @return string
     */
    public function toJson()
    {
        return json_encode($this->data());
    }

    /**
     * Serialize data array
     * @return string
     */
    public function serialize()
    {
        return serialize($this->data());
    }

    /**
     * {@inherit}
     */
    public function unserialize($serialized)
    {
        $this->data(unserialize($serialized));
    }
}
