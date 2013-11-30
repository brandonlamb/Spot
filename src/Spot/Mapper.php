<?php

/**
 * Base Data Mapper
 *
 * Responsible for mediating communication between the enity, relation, events managers
 * and the database adapter and entity objects.
 *
 * @package Spot
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot;

use Spot\Di\DiInterface,
    Spot\Di\InjectableTrait,
    Spot\Entity\EntityInterface;

class Mapper
{
    use InjectableTrait;

    /**
     * @var string
     */
    protected $adapterName = 'db';

    /**
     * @var array, Array of error messages and types
     */
    protected $errors = [];

    /**
     * @var array, event hooks
     */
    protected $hooks = [];

    /**
     * Constructor Method
     * @param \Spot\Di $di
     */
    public function __construct(DiInterface $di)
    {
        $this->setDi($di);
    }

    /**
     * Set the adapter name
     * @param string $adapterName
     * @return \Spot\Mapper
     */
    public function setAdapterName($adapterName)
    {
        $this->adapterName = (string) $adapterName;
        return $this;
    }

    /**
     * Get the adapter name
     * @return string
     */
    public function getAdapterName()
    {
        return (string) $this->adapterName;
    }

    /**
     * Get the adapter based on mapper's adapter name
     *
     * @return \Spot\AdapterInterface
     */
    public function getAdapter()
    {
        return $this->di->getShared($this->adapterName);
    }

/* ====================================================================================================== */

    /**
     * Create and return a new query builder object
     * @return \Spot\Query
     * @todo Create query factory?
     */
    public function createSql($entityName = null)
    {
        return $this->queryFactory->create($this, $entityName);
    }

    /**
     * Get a new entity object and set given data on it
     * @param string $entityClass Name of the entity class
     * @param array $data array of key/values to set on new Entity instance
     * @return \Spot\Entity, Instance of $entityClass with $data set on it
     */
    public function createEntity($entityClass, array $data)
    {
        return $this->entityFactory->create($entityClass, $this, $data);
    }

    /**
     * Hydrate an entity from an array of data.
     * @param string|EntityInterface $entityName
     * @param array $data
     * @return EntityInterface
     */
    public function hydrateEntity($entityName, array $data)
    {
        return is_string($entityName) ? new $entityName($data) : $entityName->setData($data);
    }

/* ====================================================================================================== */

    /**
     * Create collection of entities.
     * @param string $entityName
     * @param \PDOStatement|array $stmt
     * @param array $with
     * @return \Spot\Entity\ResultSetInterface
     */
    public function collection($entityName, $stmt, $with = [])
    {
        $results = [];
        $resultsIdentities = [];

        // Ensure PDO only gives key => value pairs, not index-based fields as well
        // Raw PDOStatement objects generally only come from running raw SQL queries or other custom stuff
        if ($stmt instanceof \PDOStatement) {
            $stmt->setFetchMode(\PDO::FETCH_ASSOC);
        }

        // Fetch all results into new entity class
        // @todo Move this to collection class so entities will be lazy-loaded by Collection iteration
        $entityFields = $this->entityManager->getColumns($entityName);
        foreach ($stmt as $data) {
            // Entity with data set
            $data = array_intersect_key($data, $entityFields);

            // Entity with data set
            $entity = $this->hydrateEntity($entityName, $data);

            // Load relation objects
            $this->relationManager->loadRelations($entity, $this);

            // Store in array for Collection
            $results[] = $entity;

            // Store primary key of each unique record in set
            $primaryKeys = $this->entityManager->getPrimaryKeysValue($entity);
            $fingerprint = md5(json_encode($primaryKeys));

            // Entity may have composite key PK, loop through each to set a "PK"
            #if (!isset($resultsIdentities[$entityName][$fingerprint]) && !empty($primaryKeys)) {
            if (!isset($resultsIdentities[$fingerprint]) && !empty($primaryKeys)) {
                #$resultsIdentities[$entityName][$fingerprint] = $primaryKeys;
                $resultsIdentities[$fingerprint] = $primaryKeys;
            }
        }

        // Create ResultSet
        $collection = $this->resultSetFactory->create($results, $resultsIdentities, $entityName);

        return $this->with($collection, $entityName, $with);
    }

    /**
     * Pre-emtively load associations for an entire collection
     * @param \Spot\Entity\CollectionInterface $collection
     * @param string $entityName
     * @param array $with
     * @return \Spot\Entity\CollectionInterface
     */
    public function with($collection, $entityName, $with = [])
    {
        $return = true;
        #$return = $this->eventsManager->triggerStaticHook($entityName, 'beforeWith', [$collection, $with, $this]);
        if (false === $return) {
            return $collection;
        }

        foreach ($with as $relationName) {
#            $return = $this->eventsManager->triggerStaticHook($entityName, 'loadWith', [$collection, $relationName, $this]);
            $return = true;
            if (false === $return) {
                continue;
            }

            $relationObj = $this->relationManager->loadRelation($collection, $relationName, $this);

            // double execute() to make sure we get the \Spot\Entity\CollectionInterface back (and not just the \Spot\Query)
            $relatedEntities = $relationObj->execute()->limit(null)->execute();

            // Load all entities related to the collection
            foreach ($collection as $entity) {
                $collectedEntities = [];
                $collectedIdentities = [];

                foreach ($relatedEntities as $relatedEntity) {
                    $resolvedConditions = $relationObj->resolveEntityConditions($entity, $relationObj->unresolvedConditions());

                    // @todo this is awkward, but $resolvedConditions['where'] is returned as an array
                    foreach ($resolvedConditions as $key => $value) {
                        if ($relatedEntity->$key == $value) {
                            // Store primary key of each unique record in set
                            $primaryKeys = $this->entityManager->getPrimaryKeysValue($entity);
                            $fingerprint = md5(json_encode($primaryKeys));

                            // Entity may have composite key PK, loop through each to set a "PK"
                            if (!isset($collectedEntities[$fingerprint]) && !empty($primaryKeys)) {
                                #$resultsIdentities[$entityName][$fingerprint] = $primaryKeys;
                                $collectedEntities[$fingerprint] = $relatedEntity;
                            }
                        }
                    }
                }

                if ($relationObj instanceof \Spot\Relation\HasOne) {
                    $relationCollection = array_shift($collectedEntities);
                } else {
                    $relationCollection = new \Spot\Entity\Collection(
                        $collectedEntities, $collectedIdentities, $entity->$relationName->entityName()
                    );
                }

#d(__METHOD__, __LINE__, $entity);

                $entity->$relationName->setCollection($relationCollection);
#                d(__METHOD__, __LINE__, $entity);
            }
        }

#        $this->eventsManager->triggerStaticHook($entityName, 'afterWith', [$collection, $with, $this]);

        return $collection;
    }

/* ====================================================================================================== */

    /**
     * Find records with custom query. Essentially a raw sql method
     * @param string $entityName Name of the entity class
     * @param string $sql Raw query or SQL to run against the datastore
     * @param array Optional $conditions Array of binds in column => value pairs to use for prepared statement
     * @return \Spot\Entity\CollectionInterface|bool
     */
    public function query($entityName, $sql, array $params = [])
    {
        return ($result = $this->getAdapter()->query($sql, $params)) ? $this->collection($entityName, $result) : false;
    }

    /**
     * Find first record matching given conditions
     * @param string $entityName Name of the entity class
     * @param array $conditions Array of conditions in column => value pairs
     * @return \Spot\Entity|bool
     */
    public function first($entityName, array $conditions = [])
    {
        return ($collection = $this->select($entityName)->where($conditions)->limit(1)->execute()) ? $collection->first() : false;
    }

    /**
     * Find records with given conditions If all parameters are empty, find all records
     * @param string $entityName Name of the entity class
     * @param array $conditions Array of conditions in column => value pairs
     * @return \Spot\Query
     */
    public function all($entityName, array $conditions = [])
    {
        return $this
            ->select($entityName)
            ->where($conditions);
    }

    /**
     * Begin a new database query - get query builder
     * Acts as a kind of factory to get the current adapter's query builder object
     * @param string $entityName Name of the entity class
     * @param mixed $fields String for single field or array of fields
     * @return \Spot\Query
     */
    public function select($entityName, $fields = '*')
    {
        return $this->createSql($entityName)
            ->select($fields)
            ->from($this->entityManager->getTable($entityName));
    }

/* ====================================================================================================== */

    /**
     * Save record
     * Will update if primary key found, insert if not. Performs validation automatically before saving record
     * @param \Spot\Entity\EntityInterface $entity Entity object or array of field => value pairs
     * @param array $options Array of adapter-specific options
     * @return bool
     */
    public function save(EntityInterface $entity, array $options = [])
    {
        // Get the entity class name
        $entityName = $entity->toString();

        // Get the primary key field for the entity class
        $pkField = $this->entityManager->getPrimaryKeyField($entityName);

        // Get field options for primary key, merge with overrides (if any) passed
        $options = array_merge($this->entityManager->fields($entityName, $pkField), $options);

        // Run beforeSave to know whether or not we can continue
#        if (false === $this->eventsManager->triggerInstanceHook($entity, 'beforeSave', $this)) {
#            return false;
#        }

        // Run validation
        if ($this->validate($entity)) {
            $pkField = $this->entityManager->getPrimaryKeyField($entity->toString());
            $pk = $this->entityManager->getPrimaryKey($entity);
            $attributes = $this->entityManager->fields($entity->toString(), $pkField);

            // Do an update if pk is specified
            $isNew = empty($pkField) || (empty($pk) && ($attributes['identity'] | $attributes['serial'] | $attributes['sequence']));

            // If the pk value is empty and the pk is set to an autoincremented type (identity, sequence, serial)
            if ($isNew) {
                // Autogenerate sequence if sequence is empty
                $options['pk'] = $pkField;

                // Check if PK is using a sequence
                if ($options['sequence'] === true) {
                    // Try fetching sequence from the Entity defined getSequence() method
                    $options['sequence'] = $entityName::getMetaData()->getSequence();

                    // If the Entity did not define a sequence, automatically generate an assumed sequence name
                    if (empty($options['sequence'])) {
                        $options['sequence'] = $entityName::getMetaData()->getTable() . '_' . $pkField . '_seq';
                    }
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
#        $resultAfter = $this->eventsManager->triggerInstanceHook($entity, 'afterSave', [$this, $result]);
        $resultAfter = null;
        return (null !== $resultAfter) ? $resultAfter : $result;
    }

    /**
     * Insert record using entity object
     * You can override the entity's primary key options by passing the respective
     * option in the options array (second parameter)
     * @param \Spot\Entity\EntityInterface $entity, Entity object already populated to be inserted
     * @param array $options, override default PK field options
     * @return bool
     */
    public function insert(EntityInterface $entity, array $options = [])
    {
        // Get the entity class name
        $entityName = $entity->toString();

        // Get the primary key field for the entity class
        $pkField = $this->entityManager->getPrimaryKeyField($entityName);

        // Get field options for primary key, merge with overrides (if any) passed
        $options = array_merge($this->entityManager->fields($entityName, $pkField), $options);

        // Run beforeInsert to know whether or not we can continue
        $resultAfter = null;
#        if (false === $this->eventsManager->triggerInstanceHook($entity, 'beforeInsert', $this)) {
#            return false;
#        }

        // If the primary key is a sequence, serial or identity column, exclude the PK from the array of columns to insert
        $data = ($options['sequence'] | $options['serial'] | $options['identity'] === true) ? $entity->dataExcept([$pkField]) : $entity->data();
        if (count($data) <= 0) {
            return false;
        }

        // Save only known, defined fields
        $entityFields = $this->entityManager->fields($entityName);
        $data = array_intersect_key($data, $entityFields);

        $data = $this->dumpEntity($entityName, $data);

        // Send to adapter
        $result = $this->getAdapter()->createEntity($this->entityManager->getTable($entityName), $data, $options);

        // Update primary key on entity object
        $pkField = $this->entityManager->getPrimaryKeyField($entityName);
        $entity->$pkField = $result;

        // Load relations on new entity
        $this->relationManager->loadRelations($entity, $this);

        // Run afterInsert
#        $resultAfter = $this->eventsManager->triggerInstanceHook($entity, 'afterInsert', [$this, $result]);
        $resultAfter = null;

        return (null !== $resultAfter) ? $resultAfter : $result;
    }

    /**
     * Update record using entity object
     * You can override the entity's primary key options by passing the respective
     * option in the options array (second parameter)
     * @param \Spot\Entity\EntityInterface $entity, Entity object already populated to be updated
     * @return bool
     */
    public function update(EntityInterface $entity)
    {
        $entityName = $entity->toString();

        // Run beforeUpdate to know whether or not we can continue
        $resultAfter = null;
#        if (false === $this->eventsManager->triggerInstanceHook($entity, 'beforeUpdate', $this)) {
#            return false;
#        }

        // Prepare data
        $data = $entity->dataModified();

        // Save only known, defined fields
        $entityFields = $this->entityManager->fields($entityName);
        $data = array_intersect_key($data, $entityFields);

        // Handle with adapter
        if (count($data) > 0) {
            $data = $this->dumpEntity($entityName, $data);
            $result = $this->getAdapter()->updateEntity(
                $this->entityManager->getTable($entityName),
                $data,
                [$this->entityManager->getPrimaryKeyField($entityName) => $this->entityManager->getPrimaryKey($entity)]
            );

            // Run afterUpdate
#            $resultAfter = $this->eventsManager->triggerInstanceHook($entity, 'afterUpdate', [$this, $result]);
            $resultAfter = null;
        } else {
            $result = true;
        }

        return (null !== $resultAfter) ? $resultAfter : $result;
    }

    /**
     * Upsert save entity - insert or update on duplicate key
     * @param string $entityClass, Name of the entity class
     * @param array $data, array of key/values to set on new Entity instance
     * @return \Spot\Entity, Instance of $entityClass with $data set on it
     */
    public function upsert($entityClass, array $data)
    {
        $entity = new $entityClass($data);

        try {
            $this->insert($entity);
        } catch(\Exception $e) {
            $this->update($entity);
        }

        return $entity;
    }

    /**
     * Delete items matching given conditions
     * @param mixed $entityName Name of the entity class or entity object
     * @param array $conditions Optional array of conditions in column => value pairs
     * @param array $options Optional array of adapter-specific options
     * @todo Clear entity from identity map on delete, when implemented
     * @return bool
     */
    public function delete($entityName, array $conditions = [], array $options = [])
    {
        if (is_object($entityName)) {
            $entity = $entityName;
            $entityName = get_class($entityName);
            $conditions = [$this->entityManager->getPrimaryKeyField($entityName) => $this->entityManager->getPrimaryKey($entity)];

            // Run beforeUpdate to know whether or not we can continue
            $resultAfter = null;
#            if (false === $this->eventsManager->triggerInstanceHook($entity, 'beforeDelete', $this)) {
#                return false;
#            }

            $result = $this->getAdapter()->deleteEntity($this->entityManager->getTable($entityName), $conditions, $options);

            // Run afterUpdate
#            $resultAfter = $this->eventsManager->triggerInstanceHook($entity, 'afterDelete', [$this, $result]);
            $resultAfter = null;
            return (null !== $resultAfter) ? $resultAfter : $result;
        }

        if (is_array($conditions)) {
            $conditions = [0 => ['conditions' => $conditions]];
            return $this->getAdapter()->deleteEntity($this->entityManager->getTable($entityName), $conditions, $options);
        } else {
            throw new $this->exceptionClass(__METHOD__ . " conditions must be an array, given " . gettype($conditions) . "");
        }
    }

/* ====================================================================================================== */

    /**
     * Prepare data to be dumped to the data store
     * @param string $entityName
     * @param array $data
     * @return array
     */
    public function dumpEntity($entityName, array $data)
    {
        $dumpedData = [];
        $fields = $entityName::getMetaData();

        foreach ($data as $field => $value) {
            $typeHandler = $this->config->getTypeHandler($fields[$field]['type']);
            $dumpedData[$field] = $typeHandler::dumpInternal($value);
        }
        return $dumpedData;
    }

/* ====================================================================================================== */

    /**
     * Run set validation rules on fields
     * @param \Spot\Entity\EntitInterface $entity
     * @return bool
     * @todo A LOT more to do here... More validation, break up into classes with rules, etc.
     */
    public function validate(EntityInterface $entity)
    {
        $entityName = $entity->toString();

        $v = new \Valitron\Validator($entity->data());

        // Check validation rules on each feild
        foreach ($this->entityManager->fields($entityName) as $field => $fieldAttrs) {
            // Required field
            if (isset($fieldAttrs['required']) && true === $fieldAttrs['required']) {
                $v->rule('required', $field);
            }

            // Unique field
            if (isset($fieldAttrs['unique']) && true === $fieldAttrs['unique']) {
                if ($this->first($entityName, [$field => $entity->$field]) !== false) {
                    $entity->error($field, "" . ucwords(str_replace('_', ' ', $field)) . " '" . $entity->$field . "' is already taken.");
                }
            }

            // Valitron validation rules
            if (isset($fieldAttrs['validation']) && is_array($fieldAttrs['validation'])) {
                foreach ($fieldAttrs['validation'] as $rule => $ruleName) {
                    $params = [];
                    if (is_string($rule)) {
                        $params = $ruleName;
                        $ruleName = $rule;
                    }

                    $params = array_merge([$ruleName, $field], $params);
                    call_user_func_array([$v, 'rule'], $params);
                }
            }
        }

        !$v->validate() && $entity->errors($v->errors(), false);

        // Return error result
        return !$entity->hasErrors();
    }

/* ====================================================================================================== */

    /**
     * Transaction with closure
     *
     * @param \Closure $work
     * @param string $entityName
     * @return $this
     */
    public function transaction(\Closure $work, $entityName = null)
    {
        $adapter = $this->getAdapter();

        try {
            $adapter->beginTransaction();

            // Execute closure for work inside transaction
            $result = $work($this);

            // Rollback on boolean 'false' return
            if ($result === false) {
                $adapter->rollback();
            } else {
                $adapter->commit();
            }
        } catch(\Exception $e) {
            // Rollback on uncaught exception
            $adapter->rollback();

            // Re-throw exception so we don't bury it
            throw $e;
        }
        return $this;
    }
}
