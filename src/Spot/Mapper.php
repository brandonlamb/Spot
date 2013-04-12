<?php
/**
 * Base DataMapper
 *
 * @package Spot
 * @link http://spot.os.ly
 */

namespace Spot;

use CacheCache\Cache,
	CacheCache\BackendInterface;

class Mapper
{
	/** @var \Spot\Config */
	protected $config;

	/** @var string, Class Names for required classes - Here so they can be easily overridden */
	protected $collectionClass = '\\Spot\\Entity\\Collection';
	protected $queryClass = '\\Spot\\Query';
	protected $exceptionClass = '\\Spot\\Exception';

	/** @var \CacheCache\BackendInterface */
	protected $cache;

	/** @vary array, Array of error messages and types */
	protected $errors = array();

	/** @var \Spot\Entity\Manager */
	protected static $entityManager;

	/**
	 * Constructor Method
	 *
	 * @param Config $config
	 */
	public function __construct(Config $config)
	{
		$this->config = $config;

		// Ensure at least the exception class is loaded
		$config::loadClass($this->exceptionClass);
		if (!class_exists($this->exceptionClass)) {
			throw new Exception("The exception class of '".$this->exceptionClass."' defined in '".get_class($this)."' does not exist.");
		}
	}

	/**
	 * Get config class mapper was instantiated with. Optionally set config
	 *
	 * @param \Spot\Config $config
	 * @return \Spot\Config
	 */
	public function config(Config $config = null)
	{
		$config instanceof Config && $this->config = $config;
		return $this->config;
	}

	/**
	 * Get query class name to use. Optionally set the class name
	 *
	 * @param string $queryClass
	 * @return string
	 */
	public function queryClass($queryClass = null)
	{
		null !== $queryClass && $this->queryClass = (string) $queryClass;
		return $this->queryClass;
	}

	/**
	 * Get collection class name to use. Optionally set the class name
	 *
	 * @param string $collectionClass
	 * @return string
	 */
	public function collectionClass($collectionClass = null)
	{
		null !== $collectionClass && $this->collectionClass = (string) $collectionClass;
		return $this->collectionClass;
	}

	/**
	 * Entity manager class for storing information and meta-data about entities
	 *
	 * @return \Spot\Entity\Manager
	 */
	public function entityManager()
	{
		if (null === static::$entityManager) {
			static::$entityManager = new Entity\Manager();
		}
		return static::$entityManager;
	}

	/**
	 * Get datasource name
	 *
	 * @param string $entityName Name of the entity class
	 * @return string Name of datasource defined on entity class
	 */
	public function datasource($entityName)
	{
		return $this->entityManager()->datasource($entityName);
	}

	/**
	 * Get formatted fields with all neccesary array keys and values.
	 * Merges defaults with defined field values to ensure all options exist for each field.
	 *
	 * @param string $entityName Name of the entity class
	 * @return array Defined fields plus all defaults for full array of all possible options
	 */
	public function fields($entityName)
	{
		return $this->entityManager()->fields($entityName);
	}

	/**
	 * Get field information exactly how it is defined in the class
	 *
	 * @param string $entityName Name of the entity class
	 * @return array Defined fields plus all defaults for full array of all possible options
	 */
	public function fieldsDefined($entityName)
	{
		return $this->entityManager()->fieldsDefined($entityName);
	}

	/**
	 * Get defined relations
	 *
	 * @param string $entityName Name of the entity class
	 */
	public function relations($entityName)
	{
		return $this->entityManager()->relations($entityName);
	}

	/**
	 * Get value of primary key for given row result
	 *
	 * @param object $entity Instance of an entity to find the primary key of
	 */
	public function primaryKey($entity)
	{
		$pkField = $this->entityManager()->primaryKeyField($entity->toString());
		return $entity->$pkField;
	}

	/**
	 * Get value of primary key for given row result
	 *
	 * @param string $entityName Name of the entity class
	 */
	public function primaryKeyField($entityName)
	{
		return $this->entityManager()->primaryKeyField($entityName);
	}

	/**
	 * Check if field exists in defined fields
	 *
	 * @param string $entityName Name of the entity class
	 * @param string $field Field name to check for existence
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
	 * Get connection to use
	 *
	 * @param string $connectionName Named connection or entity class name
	 * @return Spot\Adapter\AdapterInterrace
	 * @throws Spot\Exception
	 */
	public function connection($connectionName = null)
	{
		// Try getting connection based on given name
		if ($connectionName === null) {
			return $this->config()->defaultConnection();
		} elseif ($connection = $this->config()->connection($connectionName)) {
			return $connection;
		} elseif ($connection = $this->entityManager()->connection($connectionName)) {
			return $connection;
		} elseif ($connection = $this->config()->defaultConnection()) {
			return $connection;
		}

		throw new Exception("Connection '" . $connectionName . "' does not exist. Please setup connection using Spot\\Config::addConnection().");
	}

	/**
	 * Create collection
	 *
	 * @param string $entityName
	 * @param \PDOStatement|array $stmt
	 * @return \Spot\Entity\CollectionInterface
	 */
	public function collection($entityName, $stmt)
	{
		$results = array();
		$resultsIdentities = array();

		// Ensure PDO only gives key => value pairs, not index-based fields as well
		// Raw PDOStatement objects generally only come from running raw SQL queries or other custom stuff
		if ($stmt instanceof \PDOStatement) {
			$stmt->setFetchMode(\PDO::FETCH_ASSOC);
		}

		// Fetch all results into new entity class
		// @todo Move this to collection class so entities will be lazy-loaded by Collection iteration
		foreach ($stmt as $data) {
			// Entity with data set
			$entity = new $entityName($data);

			// Load relation objects
			$this->loadRelations($entity);

			// Store in array for Collection
			$results[] = $entity;

			// Store primary key of each unique record in set
			$pk = $this->primaryKey($entity);
			if (!in_array($pk, $resultsIdentities) && !empty($pk)) {
				$resultsIdentities[] = $pk;
			}
		}

		$collectionClass = $this->collectionClass();
		return new $collectionClass($results, $resultsIdentities);
	}

	/**
	 * Get array of entity data
	 *
	 * @param \Spot\Entity @entity
	 * @param array $data
	 * @return array
	 */
	public function data($entity, array $data = array())
	{
		if (!is_object($entity)) {
			throw new $this->exceptionClass("Entity must be an object, type '" . gettype($entity) . "' given");
		}

		// SET data
		if (count($data) > 0) {
			return $entity->data($data);
		}

		// GET data
		return $entity->data();
	}

	/**
	 * Get a new entity object, or an existing
	 * entity from identifiers
	 *
	 * @param string $entityClass Name of the entity class
	 * @param mixed $identifier Primary key or array of key/values
	 * @return mixed Depends on input
	 * 			false If $identifier is scalar and no entity exists
	 */
	public function get($entityClass, $identifier = false)
	{
		if (false === $identifier) {
			// No parameter passed, create a new empty entity object
			$entity = new $entityClass();
			$entity->data(array($this->primaryKeyField($entityClass) => null));
		} else if (is_array($identifier)) {
			// An array was passed, create a new entity with that data
			$entity = new $entityClass($identifier);
			$entity->data(array($this->primaryKeyField($entityClass) => null));
		} else {
			// Scalar, find record by primary key
			$entity = $this->first($entityClass, array($this->primaryKeyField($entityClass) => $identifier));
			if (!$entity) {
				return false;
			}
			$this->loadRelations($entity);
		}

		// Set default values if entity not loaded
		if (!$this->primaryKey($entity)) {
			$entityDefaultValues = $this->entityManager()->fieldDefaultValues($entityClass);
			if (count($entityDefaultValues) > 0) {
				$entity->data($entityDefaultValues);
			}
		}

		return $entity;
	}

	/**
	 * Get a new entity object and set given data on it
	 *
	 * @param string $entityClass Name of the entity class
	 * @param array $data array of key/values to set on new Entity instance
	 * @return \Spot\Entity, Instance of $entityClass with $data set on it
	 */
	public function create($entityClass, array $data)
	{
		return $this->get($entityClass)->data($data);
	}

	/**
	 * Find records with custom query
	 *
	 * @param string $entityName Name of the entity class
	 * @param string $sql Raw query or SQL to run against the datastore
	 * @param array Optional $conditions Array of binds in column => value pairs to use for prepared statement
	 * @return \Spot\Entity\CollectionInterface|bool
	 */
	public function query($entityName, $sql, array $params = array())
	{
		$result = $this->connection($entityName)->query($sql, $params);
		if ($result) {
			return $this->collection($entityName, $result);
		}
		return false;
	}

	/**
	 * Find records with given conditions
	 * If all parameters are empty, find all records
	 *
	 * @param string $entityName Name of the entity class
	 * @param array $conditions Array of conditions in column => value pairs
	 * @return \Spot\Query
	 */
	public function all($entityName, array $conditions = array())
	{
		return $this->select($entityName)->where($conditions);
	}

	/**
	 * Find first record matching given conditions
	 *
	 * @param string $entityName Name of the entity class
	 * @param array $conditions Array of conditions in column => value pairs
	 * @return \Spot\Entity|bool
	 */
	public function first($entityName, array $conditions = array())
	{
		$query = $this->select($entityName)->where($conditions)->limit(1);
		$collection = $query->execute();
		if ($collection) {
			return $collection->first();
		} else {
			return false;
		}
	}

	/**
	 * Begin a new database query - get query builder
	 * Acts as a kind of factory to get the current adapter's query builder object
	 *
	 * @param string $entityName Name of the entity class
	 * @param mixed $fields String for single field or array of fields
	 * @return \Spot\Query
	 */
	public function select($entityName, $fields = '*')
	{
		$query = new $this->queryClass($this, $entityName);
		$query->select($fields, $this->datasource($entityName));
		return $query;
	}

	/**
	 * Save record
	 * Will update if primary key found, insert if not
	 * Performs validation automatically before saving record
	 *
	 * @param mixed $entity Entity object or array of field => value pairs
	 * @param array $options Array of adapter-specific options
	 * @return bool
	 */
	public function save($entity, array $options = array())
	{
		if (!is_object($entity)) {
			throw new $this->exceptionClass(__METHOD__ . " Requires an entity object as the first parameter");
		}

		// Run beforeSave to know whether or not we can continue
		if (is_callable(array($entity, 'beforeSave'))) {
			if (false === $entity->beforeSave($this)) {
				return false;
			}
		}

		// Run validation
		if ($this->validate($entity)) {
			$pk = $this->primaryKey($entity);
			$pkField = $this->primaryKeyField($entity->toString());
			$attributes = $this->entityManager()->fields($entity->toString(), $pkField);

			// If the pk value is empty and the pk is set to serial type
			if (empty($pk) && $attributes['serial'] === true) {
				// Autogenerate sequence if sequence is empty
				$options['pk'] = $pkField;
				$options['sequence'] = $entity->getSequence();

				if (empty($options['sequence'])) {
					$options['sequence'] = $entity->getSource() . '_' . $pkField . '_seq';
				}

				// No primary key, insert
				$result = $this->insert($entity, $options);
			} else {
				// Has primary key, update
				$result = $this->update($entity);
			}
		} else {
			$result = false;
		}

		// Use return value from 'afterSave' method if not null
		$resultAfter = null;
		if (is_callable(array($entity, 'afterSave'))) {
			$resultAfter = $entity->afterSave($this, $result);
		}
		return (null !== $resultAfter) ? $resultAfter : $result;
	}

	/**
	 * Insert record
	 *
	 * @param mixed $entity Entity object or array of field => value pairs
	 * @param array $options Array of adapter-specific options
	 * @return bool
	 */
	public function insert($entity, array $options = array())
	{
		if (is_object($entity)) {
			$entityName = $entity->toString();
			$data = !isset($options['sequence']) ? $entity->data() : $entity->dataExcept(array($options['pk']));
		} elseif (is_string($entity)) {
			$entityName = $entity;
			$entity = $this->get($entityName);
			$data = $options;
		} else {
			throw new $this->exceptionClass(__METHOD__ . " Accepts either an entity object or entity name + data array");
		}

		// Run beforeInsert to know whether or not we can continue
		$resultAfter = null;
		if (is_callable(array($entity, 'beforeInsert'))) {
			if (false === $entity->beforeInsert($this)) {
				return false;
			}
		}

		// Ensure there is actually data to update
		if (count($data) <= 0) {
			return false;
		}

		// Save only known, defined fields
		$entityFields = $this->fields($entityName);
		$data = array_intersect_key($data, $entityFields);

		// Send to adapter via named connection
		$result = $this->connection($entityName)->create($this->datasource($entityName), $data, $options);

		// Update primary key on entity object
		$pkField = $this->primaryKeyField($entityName);
		$entity->$pkField = $result;

		// Load relations on new entity
		$this->loadRelations($entity);

		// Run afterInsert
		if (is_callable(array($entity, 'afterInsert'))) {
			$resultAfter = $entity->afterInsert($this, $result);
		}

		return (null !== $resultAfter) ? $resultAfter : $result;
	}

	/**
	 * Update given entity object
	 *
	 * @param object $entity Entity object
	 * @param array $options Array of adapter-specific options
	 * @return bool
	 */
	public function update($entity, array $options = array())
	{
		if (is_object($entity)) {
			$entityName = get_class($entity);
			$data = $entity->dataModified();

			// Save only known, defined fields
			$entityFields = $this->fields($entityName);
			$data = array_intersect_key($data, $entityFields);
		} else {
			throw new $this->exceptionClass(__METHOD__ . " Requires an entity object as the first parameter");
		}

		// Run beforeUpdate to know whether or not we can continue
		$resultAfter = null;
		if (is_callable(array($entity, 'beforeUpdate'))) {
			if (false === $entity->beforeUpdate($this)) {
				return false;
			}
		}

		// Handle with adapter
		if (count($data) > 0) {
			$result = $this->connection($entityName)->update($this->datasource($entityName), $data, array($this->primaryKeyField($entityName) => $this->primaryKey($entity)));

			// Run afterUpdate
			if (is_callable(array($entity, 'afterUpdate'))) {
				$resultAfter = $entity->afterUpdate($this, $result);
			}
		} else {
			$result = true;
		}

		return (null !== $resultAfter) ? $resultAfter : $result;
	}

	/**
	 * Delete items matching given conditions
	 *
	 * @param mixed $entityName Name of the entity class or entity object
	 * @param array $conditions Optional array of conditions in column => value pairs
	 * @param array $options Optional array of adapter-specific options
	 * @return bool
	 */
	public function delete($entityName, array $conditions = array(), array $options = array())
	{
		if (is_object($entityName)) {
			$entity = $entityName;
			$entityName = get_class($entityName);
			$conditions = array($this->primaryKeyField($entityName) => $this->primaryKey($entity));
			// @todo Clear entity from identity map on delete, when implemented

			// Run beforeUpdate to know whether or not we can continue
			$resultAfter = null;
			if (is_callable(array($entity, 'beforeDelete'))) {
				if (false === $entity->beforeDelete($this)) {
					return false;
				}
			}

			$result = $this->connection($entityName)->delete($this->datasource($entityName), $conditions, $options);

			// Run afterUpdate
			if (is_callable(array($entity, 'afterDelete'))) {
				$resultAfter = $entity->afterDelete($this, $result);
			}

			return (null !== $resultAfter) ? $resultAfter : $result;
		}

		if (is_array($conditions)) {
			$conditions = array(0 => array('conditions' => $conditions));
			return $this->connection($entityName)->delete($this->datasource($entityName), $conditions, $options);
		} else {
			throw new $this->exceptionClass(__METHOD__ . " conditions must be an array, given " . gettype($conditions) . "");
		}
	}

	/**
	 * Load defined relations
	 *
	 * @param \Spot\Entity
	 * @return \Spot\Relation\RelationAbstract
	 */
	public function loadRelations($entity)
	{
		$entityName = get_class($entity);
		$relations = array();
		$rels = $this->relations($entityName);
		if (count($rels) > 0) {
			foreach ($rels as $field => $relation) {
				$relationEntity = isset($relation['entity']) ? $relation['entity'] : false;
				if (!$relationEntity) {
					throw new $this->exceptionClass("Entity for '" . $field . "' relation has not been defined.");
				}

				// Self-referencing entity relationship?
				if ($relationEntity == ':self') {
					$relationEntity = $entityName;
				}

				// Load relation class to lazy-loading relations on demand
				$relationClass = '\\Spot\\Relation\\' . $relation['type'];

				// Set field equal to relation class instance
				$relationObj = new $relationClass($this, $entity, $relation);
				$relations[$field] = $relationObj;
				$entity->$field = $relationObj;
			}
		}
		return $relations;
	}

	/**
	 * Run set validation rules on fields
	 *
	 * @todo A LOT more to do here... More validation, break up into classes with rules, etc.
	 */
	public function validate($entity)
	{
		$entityName = get_class($entity);

		// Check validation rules on each feild
		foreach ($this->fields($entityName) as $field => $fieldAttrs) {
			// Required field
			if (isset($fieldAttrs['required']) && true === $fieldAttrs['required']) {
				if ($this->isEmpty($entity->$field)) {
					$entity->error($field, "Required field '" . $field . "' was left blank");
				}
			}

			// Unique field
			if (isset($fieldAttrs['unique']) && true === $fieldAttrs['unique']) {
				if ($this->first($entityName, array($field => $entity->$field)) !== false) {
					$entity->error($field, "" . ucwords(str_replace('_', ' ', $field)) . " '" . $entity->$field . "' is already taken.");
				}
			}
		}

		// Return error result
		return !$entity->hasErrors();
	}

	/**
	 * Check if a value is empty, excluding 0 (annoying PHP issue)
	 *
	 * @param mixed $value
	 * @return boolean
	 */
	public function isEmpty($value)
	{
		return empty($value) && !is_numeric($value);
	}

	/**
	 * Transaction with closure
	 *
	 * @param \Closure $work
	 * @param string $entityName
	 * @return $this
	 */
	public function transaction(\Closure $work, $entityName = null)
	{
		$connection = $this->connection($entityName);

		try {
			$connection->beginTransaction();

			// Execute closure for work inside transaction
			$result = $work($this);

			// Rollback on boolean 'false' return
			if ($result === false) {
				$connection->rollback();
			} else {
				$connection->commit();
			}
		} catch(\Exception $e) {
			// Rollback on uncaught exception
			$connection->rollback();

			// Re-throw exception so we don't bury it
			throw $e;
		}
		return $this;
	}

	/**
	 * Truncate data source
	 * Should delete all rows and reset serial/auto_increment keys to 0
	 *
	 * @param string $entityName Name of the entity class
	 */
	public function truncateDatasource($entityName)
	{
		return $this->connection($entityName)->truncateDatasource($this->datasource($entityName));
	}

	/**
	 * Drop/delete data source
	 * Destructive and dangerous - drops entire data source and all data
	 *
	 * @param string $entityName Name of the entity class
	 */
	public function dropDatasource($entityName)
	{
		return $this->connection($entityName)->dropDatasource($this->datasource($entityName));
	}

	/**
	 * Migrate table structure changes from model to database
	 *
	 * @param string $entityName Name of the entity class
	 */
	public function migrate($entityName)
	{
		return $this->connection($entityName)
			->migrate(
				$this->datasource($entityName),
				$this->fields($entityName),
				$this->entityManager()->datasourceOptions($entityName)
			);
	}

	/**
	 * Set the mapper's cache object
	 * @param \CacheCache\BackendInterface $cache
	 * @return $this
	 */
	public function setCache(Cache $cache)
	{
		$this->cache = $cache;
		return $this;
	}

	/**
	 * Retrieve the cache object, or false if none is set
	 * @return \CacheCache\BackendInterface|bool
	 */
	public function getCache()
	{
		if (null === $this->cache) {
			return false;
		}
		return $this->cache;
	}
}
