/**
 * Spot\Entity\AbstractEntity
 * Represents a table entity
 *
 * @package Spot\Entity
 * @author Ben Longden <ben@nocarrier.co.uk>
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Entity;

abstract class AbstractEntity implements Serializable, ArrayAccess
{
    /**
     * @var string, the name of the entity's schema
     */
    protected schema;

    /**
     * @var string, the name of the sequence to use when insert new entitys
     */
    protected sequence;

    /**
     * @var string, the table name for the entity
     */
    protected table;

    /**
     * @var array
     */
    protected datasourceOptions;

    /**
     * @var array, Entity data storage
     */
    protected data;

    /**
     * @var array, Entity modified data storage
     */
    protected dataModified;

    /**
     * @var array, ignored getter properties. Add a field/column here to not
     * attempt calling its getter method. For example, given an entity with a
     * "name" property and a "getName()" method, where you do *not want to call
     * "getName()" when accessing entity->name, add "name" to this array
     */
    protected getterIgnore;

    /**
     * @var array, ignored setter properties. Add a field/column here to not
     * attempt calling its setter method. For example, given an entity with a
     * "name" property and a "setName()" method, where you do *not want to call
     * "setName()" when performing a entity->name = "value" operation,
     * add "name" to this array
     */
    protected setterIgnore;

    /**
     * @var array, Entity error messages (may be present after save attempt)
     */
    protected errors;

    /**
     * Constructor. Allows setting object properties with array on construct
     * @param array data
     */
    public function __construct(var data = null)
    {
        let this->datasourceOptions = [];
        let this->data = [];
        let this->dataModified = [];
        let this->getterIgnore = [];
        let this->setterIgnore = [];
        let this->errors = [];

//        this->initFields();
        if typeof data == "array" {
            this->setData(data, false);
        }
    }

    /**
     * Enable isset() for object properties
     * @param string offset
     * @return bool
     */
    public function __isset(string! offset) -> boolean
    {
        return this->offsetExists(offset);
    }

    /**
     * Getter for field properties
     * @param string offset
     * @return mixed
     */
    public function __get(string! offset)
    {
        return this->offsetGet(offset);
    }

    /**
     * Setter for field properties
     * @param string offset
     * @param mixed value
     * @return $this
     */
    public function __set(string! offset, var value) -> void
    {
        this->offsetSet(offset, value);
    }

    /**
     * String representation of the class
     * @return string
     */
    public function __toString() -> string
    {
        return get_called_class();
    }

    /**
     * {@inheritDoc}
     */
    public function serialize() -> string
    {
        return serialize(this->getData());

//        var data;
//        let data = this->getData();
//        return serialize(data);
    }

    /**
     * {@inheritDoc}
     */
    public function unserialize(string! serialized) -> void
    {
        var data;
        let data = unserialize(serialized);
        this->setData(data);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists(string! offset) -> boolean
    {
        return isset this->data[offset] || isset this->dataModified[offset];
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset(offset) -> void
    {
        var data;

        if isset this->data[offset] {
            echo "unset data\n";
            let data = this->data;
            unset data[offset];
            let this->data = data;
//            unset this->data[offset];
        }

        if isset this->dataModified[offset] {
            echo "unset modified data\n";
            let data = this->dataModified;
            unset data[offset];
            let this->dataModified = data;
//            unset this->dataModified[offset];
        }
    }

    /**
     * Getter for field properties. This method will attempt to call a
     * get$field() method if it exists, otherwise the field from the entity's
     * data storage array.
     * @param string offset
     * @param mixed data, value to return if field doesnt exist
     * @return mixed
     */
    public function offsetGet(string! offset, var data = null)
    {
        var getMethod;

        // Check for custom getter method (override)
        let getMethod = "get" . offset;

        // We can't use isset for dataModified because it returns false for NULL values
        if array_key_exists(offset, this->dataModified) {
            return this->dataModified[offset];
        }

        if isset this->data[offset] {
            return this->data[offset];
        }

        if method_exists(this, getMethod) && !array_key_exists(offset, this->getterIgnore) {
            // Tell this function to ignore the overload on further calls for this variable
            let this->getterIgnore[offset] = 1;

            // Call custom getter
            let data = this->{getMethod}();

            // Remove ignore rule
            unset(this->getterIgnore[offset]);

            return data;
        }

        return data;
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet(string! field, var value) -> void
    {
        var setMethod, fields, fieldData;

        // Check for custom setter method (override)
        let setMethod = "set" . field;

        // Fetch entity fields
        let fields = this->fields();

        // Run value through a filter call if set
        if fetch fieldData, fields[field] {
            if isset fieldData["filter"] {
                let value = call_user_func(fieldData["filter"], value);
            }
        } else {
            if method_exists(this, setMethod) && !array_key_exists(field, this->setterIgnore) {
                // Tell this function to ignore the overload on further calls for this variable
                let this->setterIgnore[field] = 1;

                // Call custom setter
                let value = this->{setMethod}(value);

                // Remove ignore rule
                unset this->setterIgnore[field];
            } else {
                if isset fields[field] {
                    // Ensure value is set with type handler
//                    typeHandler = Config::getTypeHandler(fields[field]["type"]);
//                    value = typeHandler::set(this, value);
                }
            }
        }

        // Set the data value
        let this->dataModified[field] = value;
    }




    /**
     * {@inheritDoc}
     * @throws \InvalidArgumentException
     * @todo - fix dependency on static Config
     */
    public function setData(var data, boolean modified = true) -> <\Spot\Entity\EntityInterface>
    {
        var fields, key, value;

        if typeof data != "object" && typeof data != "array" {
            throw new \InvalidArgumentException(get_called_class() . "::setData Expected array or object, " . gettype(data) . " given");
        }

        let fields = this->fields();
        for key, value in data {
            // Ensure value is set with type handler if Entity field type
            if array_key_exists(key, fields) {
//                $typeHandler = Config::getTypeHandler($fields[$k]["type"]);
//                $v = $typeHandler::set($this, $v);
            }

            if modified {
                let this->dataModified[key] = value;
            } else {
                let this->data[key] = value;
            }
        }

        return this;
    }

    /**
     * {@inheritDoc}
     */
    public function getData()
    {
        return array_merge(this->data, this->dataModified);
    }

    /**
     * Return defined fields of the entity
     * @return array
     */
    public function fields()
    {
        return [];
    }
}
