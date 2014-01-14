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
    Spot\Entity\EntityInterface,
    Spot\Entity\ResultsetInterface;

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
     * @return \Spot\Db\AdapterInterface
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
     * @return \Spot\Entity\EntityInterface, Instance of $entityClass with $data set on it
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
     * Create collection of entities as a resultset.
     * @param string $entityName
     * @param \PDOStatement|array $stmt
     * @param array $with
     * @return \Spot\Entity\ResultsetInterface
     * @todo Move resultset hydration to resultset class
     */
    public function getResultset($entityName, $stmt, $with = [])
    {
        $results = [];
        $resultsIdentities = [];

        // Ensure PDO only gives key => value pairs, not index-based fields as well
        // Raw PDOStatement objects generally only come from running raw SQL queries or other custom stuff
        #if ($stmt instanceof \PDOStatement) {
        #    $stmt->setFetchMode(\PDO::FETCH_ASSOC);
        #}

        // Fetch all results into new entity class
        // @todo Move this to resultset class so entities will be lazy-loaded by Resultset iteration
        $entityFields = $this->entityManager->getColumns($entityName);
        foreach ($stmt as $data) {
            // Entity with data set
            $entity = $this->hydrateEntity($entityName, $data);

            // Load relation objects
            $this->relationManager->loadRelations($entity, $this);

            // Store in array for Resultset
            $results[] = $entity;

            // Store primary key of each unique record in set
            $primaryKeys = $this->entityManager->getPrimaryKeyValues($entity);
            $fingerprint = md5(json_encode($primaryKeys));

            // Entity may have composite key PK, loop through each to set a "PK"
            #if (!isset($resultsIdentities[$entityName][$fingerprint]) && !empty($primaryKeys)) {
            if (!isset($resultsIdentities[$fingerprint]) && !empty($primaryKeys)) {
                #$resultsIdentities[$entityName][$fingerprint] = $primaryKeys;
                $resultsIdentities[$fingerprint] = $primaryKeys;
            }
        }

        // Create ResultSet
        $resultset = $this->resultsetFactory->create($results, $resultsIdentities, $entityName);

        return $this->with($resultset, $entityName, $with);
    }

    /**
     * Pre-emtively load associations for an entire resultset
     * @param \Spot\Entity\ResultsetInterface $resultset
     * @param string $entityName
     * @param array $with
     * @return \Spot\Entity\ResultsetInterface
     */
    public function with($resultset, $entityName, $with = [])
    {
        $return = true;
        #$return = $this->eventsManager->triggerStaticHook($entityName, 'beforeWith', [$resultset, $with, $this]);
        if (false === $return || count($resultset) === 0) {
            return $resultset;
        }

        foreach ($with as $relationName) {
#            $return = $this->eventsManager->triggerStaticHook($entityName, 'loadWith', [$resultset, $relationName, $this]);
            $return = true;
            if (false === $return) {
                continue;
            }

            $relationObj = $this->relationManager->loadRelation($resultset, $relationName, $this);

            // double execute() to make sure we get the \Spot\Entity\ResultsetInterface back (and not just the \Spot\Query)
            $relatedEntities = $relationObj->execute()->limit(null)->execute();

            // Load all entities related to the resultset
            foreach ($resultset as $entity) {
                $collectedEntities = [];
                $collectedIdentities = [];

                foreach ($relatedEntities as $relatedEntity) {
                    $resolvedConditions = $relationObj->resolveEntityConditions($entity, $relationObj->unresolvedConditions());

                    // @todo this is awkward, but $resolvedConditions['where'] is returned as an array
                    foreach ($resolvedConditions as $key => $value) {
                        if ($relatedEntity->$key == $value) {
                            // Store primary key of each unique record in set
                            $primaryKeys = $this->entityManager->getPrimaryKeyValues($relatedEntity);
                            $fingerprint = md5(json_encode($primaryKeys));

                            // Entity may have composite key PK, loop through each to set a "PK"
                            if (!isset($collectedEntities[$fingerprint]) && !empty($primaryKeys)) {
                                $collectedEntities[$fingerprint] = $relatedEntity;
                            }
                        }
                    }
                }

                $relationResultset = $this->resultsetFactory->create(
                    $collectedEntities, $collectedIdentities, $entity->$relationName->entityName()
                );

                $entity->$relationName->setResultset($relationResultset);
            }
        }

#        $this->eventsManager->triggerStaticHook($entityName, 'afterWith', [$resultset, $with, $this]);

        return $resultset;
    }

/* ====================================================================================================== */

    /**
     * Find records with custom query. Essentially a raw sql method
     * @param string $entityName Name of the entity class
     * @param string $sql Raw query or SQL to run against the datastore
     * @param array Optional $conditions Array of binds in column => value pairs to use for prepared statement
     * @return \Spot\Entity\ResultsetInterface|bool
     */
    public function query($entityName, $sql, array $params = [])
    {
        return ($result = $this->getAdapter()->query($sql, $params)) ? $this->getResultset($entityName, $result) : false;
    }

    /**
     * Find first record matching given conditions
     * @param string $entityName Name of the entity class
     * @param array $conditions Array of conditions in column => value pairs
     * @return \Spot\Entity\EntityInterface|bool
     */
    public function first($entityName, array $conditions = [])
    {
        return ($resultset = $this->select($entityName)->where($conditions)->limit(1)->execute()) ? $resultset->first() : false;
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
        // Run beforeSave to know whether or not we can continue
#        if (false === $this->eventsManager->triggerInstanceHook($entity, 'beforeSave', $this)) {
#            return false;
#        }

        // Run validation
        if (!$this->validate($entity)) {
            return false;
        }

        // Get the entity class name
        $entityName = $entity->toString();

        // Get the primary key field for the entity class
        $primaryKeys = $this->entityManager->getPrimaryKeys($entityName);

        // Default to always update
        $isCreate = false;

        // Figure out if the PKs are empty and whether to do an insert or update
        // If there are 0 or >1 PKs defined, try doing an upsert as we cant really figure
        // out easily whether to insert or update
        $numPrimaryKeys = count($primaryKeys);
        if ($numPrimaryKeys == 0 || $numPrimaryKeys > 1) {
            return $this->upsert($entity);
        }

        // Get the primary key values
        $primaryKeyValues = $this->entityManager->getPrimaryKeyValues($entity);

        // If primary is not set then we are inserting
        foreach ($primaryKeyValues as $key => $value) {
            if (null === $value) {
                $isCreate = true;
                break;
            }
        }

        // If the pk value is empty and the pk is set to an autoincremented type (identity, sequence, serial)
        if ($isCreate) {
            // No primary key, insert
            $result = $this->insert($entity);
        } else {
            // Has primary key, update
            $result = $this->update($entity);
        }

        // Use return value from 'afterSave' method if not null
        $resultAfter = null;
        #$resultAfter = $this->eventsManager->triggerInstanceHook($entity, 'afterSave', [$this, $result]);
        return (null !== $resultAfter) ? $resultAfter : $result;
    }

    /**
     * Insert record using entity object
     * @param \Spot\Entity\EntityInterface $entity, Entity object already populated to be inserted
     * @return bool
     */
    public function insert(EntityInterface $entity)
    {
        return $this->saveEntity($entity, true);
    }

    /**
     * Handle writes, save the entity
     * @param \Spot\Entity\EntityInterface $entity
     * @param bool $insert, use insert if true, or use update
     * @return bool
     * @todo - UPDATE operation should only update modified data
     */
    public function saveEntity(EntityInterface $entity, $insert = true)
    {
        // Run beforeInsert to know whether or not we can continue
        $resultAfter = null;
        #if (false === $this->eventsManager->triggerInstanceHook($entity, 'beforeInsert', $this)) {
        #    return false;
        #}

        // Get the entity class name
        $entityName = $entity->toString();

        // Get field options for primary key, merge with overrides (if any) passed
        $columns = $this->entityManager->getColumns($entityName);

        // Get identity/sequence columns
        $exceptColumns = [];
        foreach ($columns as $column) {
            ($column->isIdentity() || $column->isSequence() || $column->isRelation()) && $exceptColumns[] = $column->getName();
        }

        // If the primary key is a sequence, serial or identity column, exclude the PK from the array of columns to insert
        $data = (!empty($exceptColumns)) ? $entity->getModifiedExcept($exceptColumns) : $entity->getModified();
        if (count($data) <= 0) {
            return false;
        }

        // Save only known, defined fields
        $data = array_intersect_key($data, $columns);

        // Initialize options array
        $options = ['identity' => false, 'sequence' => false, 'primaryKey' => false];

        // Get meta data
        $metaData = $entityName::getMetaData();

        // Loop through each $exceptColumns (which will be identity or sequence columns)
        foreach ($exceptColumns as $column) {
            if ($metaData->getColumn($column)->isSequence()) {
                $options['primaryKey'] = $column;
                $options['sequence'] = !empty($metaData->getSequence()) ? $metaData->getSequence() : $metaData->getTable() . "_{$column}_seq";
            }
        }

        // Create binds from data
        $binds = [];
        foreach ($data as $key => $value) {
            $binds[$key] = [
                'value' => $value,
                'bindType' => $metaData->getColumn($key)->getBindType(),
            ];
        }

        // Send to adapter
        if ($insert === true) {
            $result = $this->getAdapter()->createEntity(
                $this->entityManager->getTable($entityName),
                $binds,
                $options
            );

            // Update primary key on entity object
            if ($result !== false) {
                $primaryKeys = $this->entityManager->getPrimaryKeys($entityName);
                $entity->{$primaryKeys[0]} = $result;
            }

            // Load relations on new entity
            $this->relationManager->loadRelations($entity, $this);
        } else {
            // Build conditions using PK
            $conditions = [];
            foreach ($this->entityManager->getPrimaryKeyValues($entity) as $key => $value) {
                $conditions[] = ['conditions' => [$key . ' :eq' => $value]];
            }
#d(__METHOD__, $binds, $conditions);
            $result = $this->getAdapter()->updateEntity(
                $this->entityManager->getTable($entityName),
                $binds,
                $conditions,
                $options
            );
        }

        // Run afterInsert
#        $resultAfter = $this->eventsManager->triggerInstanceHook($entity, 'afterInsert', [$this, $result]);
        $resultAfter = null;

        return (null !== $resultAfter) ? $resultAfter : $result;
    }

    /**
     * Update record using entity object
     * You can override the entity's primary key options by passing the respective
     * option in the options array (second parameter)
     * @param mixed $entityName Name of the entity class or entity object
     * @param array $conditions
     * @param array $options
     * @return bool
     */
    public function update($entityName, array $conditions = [], array $options = [])
    {
        if ($entityName instanceof EntityInterface) {
            return $this->saveEntity($entityName, false);
        }

        if ($entityName instanceof ResultsetInterface) {
            return $this->updateResultset($entityName);
        }

        if (is_string($entityName) && is_array($conditions)) {
            $conditions = [['conditions' => $conditions]];
            return $this->getAdapter()->updateEntity($this->entityManager->getTable($entityName), $conditions, $options);
        } else {
            throw new \Exception(__METHOD__ . " conditions must be an array, given " . gettype($conditions) . "");
        }






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
     * Update an entity
     *
     * @param \Spot\Entity\EntityInterface $entity entity object
     * @param array $conditions Optional array of conditions in column => value pairs
     * @param array $options Optional array of adapter-specific options
     * @return bool
     * @todo Clear entity from identity map on delete, when implemented
     */
    public function updateEntity(EntityInterface $entity, array $conditions = [], array $options = [])
    {
        $entityName = $entity->toString();
        $conditions = $this->entityManager->getPrimaryKeyValues($entity);

        // Run beforeUpdate to know whether or not we can continue
        $resultAfter = null;
#            if (false === $this->eventsManager->triggerInstanceHook($entity, 'beforeDelete', $this)) {
#                return false;
#            }

        $result = $this->getAdapter()->updateEntity(
            $this->entityManager->getTable($entityName),
            [['conditions' => $conditions]],
            $options
        );

        // Run afterUpdate
#            $resultAfter = $this->eventsManager->triggerInstanceHook($entity, 'afterDelete', [$this, $result]);
        $resultAfter = null;
        return (null !== $resultAfter) ? $resultAfter : $result;
    }

    /**
     * Update a resultset
     *
     * @param \Spot\Entity\ResultsetInterface $resultset result set
     * @param array $conditions Optional array of conditions in column => value pairs
     * @param array $options Optional array of adapter-specific options
     * @return bool
     */
    public function updateResultset(ResultsetInterface $resultset, array $conditions = [], array $options = [])
    {
        $result = true;
        foreach ($resultset as $entity) {
            if ($this->updateEntity($entity)) {
                $result = false;
            }
        }
        return $result;
    }

    /**
     * Upsert save entity - insert or update on duplicate key
     * @param string $entityName, Name of the entity class
     * @param array $data, array of key/values to set on new Entity instance
     * @return \Spot\Entity\EntityInterface, Instance of $entityName with $data set on it
     * @todo - not really implemented yet
     */
    public function upsert($entityName, array $data)
    {
        $entity = new $entityName($data);

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
     * @return bool
     * @throws \Exception
     * @todo Clear entity from identity map on delete, when implemented
     */
    public function delete($entityName, array $conditions = [], array $options = [])
    {
        if ($entityName instanceof EntityInterface) {
            return $this->deleteEntity($entityName, $conditions, $options);
        }

        if ($entityName instanceof ResultsetInterface) {
            return $this->deleteResultset($entityName, $conditions, $options);
        }

        if (is_string($entityName) && is_array($conditions)) {
            $conditions = [['conditions' => $conditions]];
            return $this->getAdapter()->deleteEntity($this->entityManager->getTable($entityName), $conditions, $options);
        } else {
            throw new \Exception(__METHOD__ . " conditions must be an array, given " . gettype($conditions) . "");
        }
    }

    /**
     * Delete an entity
     *
     * @param \Spot\Entity\EntityInterface $entity entity object
     * @param array $conditions Optional array of conditions in column => value pairs
     * @param array $options Optional array of adapter-specific options
     * @return bool
     * @todo Clear entity from identity map on delete, when implemented
     */
    public function deleteEntity(EntityInterface $entity, array $conditions = [], array $options = [])
    {
        $entityName = $entity->toString();
        $conditions = $this->entityManager->getPrimaryKeyValues($entity);

        // Run beforeUpdate to know whether or not we can continue
        $resultAfter = null;
#            if (false === $this->eventsManager->triggerInstanceHook($entity, 'beforeDelete', $this)) {
#                return false;
#            }

        $result = $this->getAdapter()->deleteEntity(
            $this->entityManager->getTable($entityName),
            [['conditions' => $conditions]],
            $options
        );

        // Run afterUpdate
#            $resultAfter = $this->eventsManager->triggerInstanceHook($entity, 'afterDelete', [$this, $result]);
        $resultAfter = null;
        return (null !== $resultAfter) ? $resultAfter : $result;
    }

    /**
     * Delete a resultset
     *
     * @param \Spot\Entity\ResultsetInterface $resultset result set
     * @param array $conditions Optional array of conditions in column => value pairs
     * @param array $options Optional array of adapter-specific options
     * @return bool
     * @todo Figure out implementation. Happy path is that there is a single PK to do a delete where pk in (). But
     * if the entity has a composite key for pk we have to call delete one by one
     */
    public function deleteResultset(ResultsetInterface $resultset, array $conditions = [], array $options = [])
    {
        $result = true;
        foreach ($resultset as $entity) {
            if ($this->deleteEntity($entity)) {
                $result = false;
            }
        }
        return $result;
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
return true;
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
