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
     * @param string offset, entity field to return
     * @param mixed data, default data to return if field doesnt exist
     * @return mixed
     */
    public function get(string! offset, var data = null);

    /**
     * Setter for field properties
     * @param string offset
     * @param mixed value
     * @return \Spot\Entity\EntityInterface
     */
    public function set(string! offset, var value) -> <Spot\Entity\EntityInterface>;

    /**
     * Set the schema name for the entity.
     * @param string schema, The name of the schema
     * @return \Spot\Entity\EntityInterface
     */
    public function setSchema(string! schema) -> <Spot\Entity\EntityInterface>;

    /**
     * Get the schema name for the entity.
     * @return string
     */
    public function getSchema() -> string;

    /**
     * Set the table for the entity
     * @param string table, The name of the table
     * @return \Spot\Entity\EntityInterface
     */
    public function setTable(string! table) -> <Spot\Entity\EntityInterface>;

    /**
     * Get the table name for the entity.
     * @return string
     */
    public function getTable() -> string;

    /**
     * Set the sequence name for the entity.
     * @param string sequence, The name of the sequence, (ie posts_id_seq)
     * @return \Spot\Entity\EntityInterface
     */
    public function setSequence(string! sequence) -> <Spot\Entity\EntityInterface>;

    /**
     * Get the sequence name for the entity.
     * @return string
     */
    public function getSequence() -> string;

    /**
     * Set the entity data
     * @param array data
     * @param bool modified
     * @return \Spot\Entity\EntityInterface
     */
    public function setData(var data, boolean modified = true) -> <Spot\Entity\EntityInterface>;

    /**
     * Get the entity data
     * @return array
     */
    public function getData();

    /**
     * Gets data that has been modified since object creation,
     * optionally allowing for selecting a single field
     * @param string field
     * @return array
     */
    public function getModified(string field = null);

    /**
     * Gets data that has not been modified since object creation,
     * optionally allowing for selecting a single field
     * @param string field
     * @return mixed
     */
    public function getUnmodified(string field = null);

    /**
     * Check if a field or entire entity has been modified. If no field name
     * is passed in then return whether any fields have been changed
     * @param string $offset
     * @return bool
     */
    public function isModified(string offset = null) -> boolean;
}
