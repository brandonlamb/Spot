<?php

/**
 * Base Data Mapper
 *
 * @package Spot
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot;

use Spot\Di as DiContainer,
    Spot\Di\InjectableTrait,
    Spot\Adapter\AdapterInterface,
    Spot\Query;

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
    public function __construct(DiContainer $di)
    {
        $this->setDi($di);
    }

    /**
     * Set the adapter object
     * @param string $adapterName
     * @return \Spot\Mapper
     */
    public function setAdapterName($adapterName)
    {
        $this->adapterName = (string) $adapterName;
        return $this;
    }

    /**
     * Get the adapter
     * @return string
     */
    public function getAdapterName()
    {
        return $this->adapterName;
    }








    /**
     * Create and return a new query builder object
     * @return \Spot\Query
     * @todo Create query factory?
     */
    public function createSql()
    {
        return $this->queryFactory->create($this, $entityName);
    }

    /**
     * Get a new entity object and set given data on it, and save it
     * @param string $entityClass Name of the entity class
     * @param array $data array of key/values to set on new Entity instance
     * @return \Spot\Entity, Instance of $entityClass with $data set on it
     */
    public function create($entityClass, array $data)
    {
        $entity = $this->entityFactory->create($entityClass, $data);
        $this->save($entity);
        return $entity;
    }

    /**
     * Hydrate an entity from an array of data.
     * @param string $entityName
     * @param array $data
     * @return array
     */
    public function hydrateEntity($entityName, array $data)
    {
        $loadedData = [];
        $fields = $entityName::fields();

        foreach ($data as $field => $value) {
            // Skip type checking if dynamic field
            if (isset($fields[$field])) {
                $typeHandler = $this->config->getTypeHandler($fields[$field]['type']);
                $loadedData[$field] = $typeHandler::loadInternal($value);
            } else {
                $loadedData[$field] = $value;
            }
        }

        return $loadedData;
    }





    /**
     * Create collection of entities.
     * @param string $entityName
     * @param \PDOStatement|array $stmt
     * @param array $with
     * @return \Spot\Entity\CollectionInterface
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
        foreach ($stmt as $data) {
            // Entity with data set
            $entity = $this->relationManager->hydrateEntity(new $entityName($data), $data);

            // Entity with data set
            $entity = new $entityName($data);

            // Load relation objects
            $this->relationManager->loadRelations($entity);

            // Store in array for Collection
            $results[] = $entity;

            // Store primary key of each unique record in set
            $pk = $this->entityManager->getPrimaryKey($entity);
            if (!in_array($pk, $resultsIdentities) && !empty($pk)) {
                $resultsIdentities[] = $pk;
            }
        }

        $collection = $this->collectionFactory->create($results, $resultsIdentities, $entityName);
        return $this->with($collection, $entityName, $with);
    }

    /**
     * Pre-emtively load associations for an entire collection
     * @param \Spot\Entity\CollectionInterface $collection
     * @param string $entityName
     * @param array $with
     * @return \Spot\Entity\CollectionInterface
     */
    public function with($collection, $entityName, $with = []) {
        $return = $this->triggerStaticHook($entityName, 'beforeWith', [$collection, $with, $this]);
        if (false === $return) {
            return $collection;
        }

        foreach ($with as $relationName) {
            $return = $this->triggerStaticHook($entityName, 'loadWith', [$collection, $relationName, $this]);
            if (false === $return) {
                continue;
            }

            $relationObj = $this->relationManager->loadRelation($collection, $relationName);

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
                            $pk = $this->entityManager->getPrimaryKey($relatedEntity);
                            if (!in_array($pk, $collectedIdentities) && !empty($pk)) {
                                $collectedIdentities[] = $pk;
                            }
                            $collectedEntities[] = $relatedEntity;
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

                $entity->$relationName->assignCollection($relationCollection);
            }
        }

        $this->triggerStaticHook($entityName, 'afterWith', [$collection, $with, $this]);

        return $collection;
    }


















    /**
     * Find records with custom query. Essentially a raw sql method
     * @param string $entityName Name of the entity class
     * @param string $sql Raw query or SQL to run against the datastore
     * @param array Optional $conditions Array of binds in column => value pairs to use for prepared statement
     * @return \Spot\Entity\CollectionInterface|bool
     */
    public function query($entityName, $sql, array $params = [])
    {
        $result = $this->db->query($sql, $params);
        if ($result) {
            return $this->collection($entityName, $result);
        }
        return false;
    }

    /**
     * Find records with given conditions If all parameters are empty, find all records
     * @param string $entityName Name of the entity class
     * @param array $conditions Array of conditions in column => value pairs
     * @return \Spot\Query
     */
    public function all($entityName, array $conditions = [])
    {
        return $this->select($entityName)->where($conditions);
    }

    /**
     * Find first record matching given conditions
     * @param string $entityName Name of the entity class
     * @param array $conditions Array of conditions in column => value pairs
     * @return \Spot\Entity|bool
     */
    public function first($entityName, array $conditions = [])
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
     * @param string $entityName Name of the entity class
     * @param mixed $fields String for single field or array of fields
     * @return \Spot\Query
     */
    public function select($entityName, $fields = '*')
    {
        return $this->createSql()->select($fields, $this->entityManager->getTable($entityName));
    }








    /**
     * Save record
     * Will update if primary key found, insert if not. Performs validation automatically before saving record
     * @param Entity $entity Entity object or array of field => value pairs
     * @param array $options Array of adapter-specific options
     * @return bool
     */
    public function save(Entity $entity, array $options = [])
    {
        // Get the entity class name
        $entityName = $entity->toString();

        // Get the primary key field for the entity class
        $pkField = $this->entityManager->getPrimaryKeyField($entityName);

        // Get field options for primary key, merge with overrides (if any) passed
        $options = array_merge($this->entityManager->fields($entityName, $pkField), $options);

        // Run beforeSave to know whether or not we can continue
        if (false === $this->triggerInstanceHook($entity, 'beforeSave', $this)) {
            return false;
        }

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
                    $options['sequence'] = $entityName::sequence();

                    // If the Entity did not define a sequence, automatically generate an assumed sequence name
                    if (empty($options['sequence'])) {
                        $options['sequence'] = $entityName->getTable() . '_' . $pkField . '_seq';
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
        $resultAfter = $this->triggerInstanceHook($entity, 'afterSave', [$this, $result]);
        return (null !== $resultAfter) ? $resultAfter : $result;
    }

    /**
     * Insert record using entity object
     * You can override the entity's primary key options by passing the respective
     * option in the options array (second parameter)
     * @param \Spot\Entity $entity, Entity object already populated to be inserted
     * @param array $options, override default PK field options
     * @return bool
     */
    public function insert(Entity $entity, array $options = [])
    {
        // Get the entity class name
        $entityName = $entity->toString();

        // Get the primary key field for the entity class
        $pkField = $this->entityManager->getPrimaryKeyField($entityName);

        // Get field options for primary key, merge with overrides (if any) passed
        $options = array_merge($this->entityManager->fields($entityName, $pkField), $options);

        // Run beforeInsert to know whether or not we can continue
        $resultAfter = null;
        if (false === $this->triggerInstanceHook($entity, 'beforeInsert', $this)) {
            return false;
        }

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
        $result = $this->db->create($this->entityManager->getTable($entityName), $data, $options);

        // Update primary key on entity object
        $pkField = $this->entityManager->getPrimaryKeyField($entityName);
        $entity->$pkField = $result;

        // Load relations on new entity
        $this->relationManager->loadRelations($entity);

        // Run afterInsert
        $resultAfter = $this->triggerInstanceHook($entity, 'afterInsert', [$this, $result]);

        return (null !== $resultAfter) ? $resultAfter : $result;
    }

    /**
     * Update record using entity object
     * You can override the entity's primary key options by passing the respective
     * option in the options array (second parameter)
     * @param \Spot\Entity $entity, Entity object already populated to be updated
     * @return bool
     */
    public function update(Entity $entity)
    {
        $entityName = $entity->toString();

        // Run beforeUpdate to know whether or not we can continue
        $resultAfter = null;
        if (false === $this->triggerInstanceHook($entity, 'beforeUpdate', $this)) {
            return false;
        }

        // Prepare data
        $data = $entity->dataModified();

        // Save only known, defined fields
        $entityFields = $this->entityManager->fields($entityName);
        $data = array_intersect_key($data, $entityFields);

        // Handle with adapter
        if (count($data) > 0) {
            $data = $this->dumpEntity($entityName, $data);
            $result = $this->db->update(
                $this->entityManager->getTable($entityName),
                $data,
                [$this->entityManager->getPrimaryKeyField($entityName) => $this->entityManager->getPrimaryKey($entity)]
            );

            // Run afterUpdate
            $resultAfter = $this->triggerInstanceHook($entity, 'afterUpdate', [$this, $result]);
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
            if (false === $this->triggerInstanceHook($entity, 'beforeDelete', $this)) {
                return false;
            }

            $result = $this->db->delete($this->entityManager->getTable($entityName), $conditions, $options);

            // Run afterUpdate
            $resultAfter = $this->triggerInstanceHook($entity, 'afterDelete', [$this, $result]);
            return (null !== $resultAfter) ? $resultAfter : $result;
        }

        if (is_array($conditions)) {
            $conditions = [0 => ['conditions' => $conditions]];
            return $this->db->delete($this->entityManager->getTable($entityName), $conditions, $options);
        } else {
            throw new $this->exceptionClass(__METHOD__ . " conditions must be an array, given " . gettype($conditions) . "");
        }
    }
















    /**
     * Prepare data to be dumped to the data store
     * @param string $entityName
     * @param array $data
     * @return array
     */
    public function dumpEntity($entityName, array $data)
    {
        $dumpedData = [];
        $fields = $entityName::fields();

        foreach ($data as $field => $value) {
            $typeHandler = $this->config->getTypeHandler($fields[$field]['type']);
            $dumpedData[$field] = $typeHandler::dumpInternal($value);
        }
        return $dumpedData;
    }















    /**
     * Run set validation rules on fields
     * @param \Spot\Entity $entity
     * @return bool
     * @todo A LOT more to do here... More validation, break up into classes with rules, etc.
     */
    public function validate(Entity $entity)
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









    /**
     * Add event listener
     * @param string $entityName
     * @param string $hook
     * @param \Closure $callable
     * @return self
     * @throws \InvalidArgumentException
     */
    public function on($entityName, $hook, $callable)
    {
        if (!is_callable($callable)) {
            throw new \InvalidArgumentException(__METHOD__ . " for {$entityName}->{$hook} requires a valid callable, given " . gettype($callable) . "");
        }
        $this->hooks[$entityName][$hook][] = $callable;

        return $this;
    }

    /**
     * Remove event listener
     * @param string $entityName
     * @param array $hooks
     * @param \Closure $callback
     * @return self
     */
    public function off($entityName, $hooks, $callable = null)
    {
        if (isset($this->hooks[$entityName])) {
            foreach ((array) $hooks as $hook) {
                if (true === $hook) {
                    unset($this->hooks[$entityName]);
                } else if (isset($this->hooks[$entityName][$hook])) {
                    if (null !== $callable) {
                        if ($key = array_search($this->hooks[$entityName][$hook], $callable, true)) {
                            unset($this->hooks[$entityName][$hook][$key]);
                        }
                    } else {
                        unset($this->hooks[$entityName][$hook]);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Get all hooks added on a model
     * @param string $entityName
     * @param string $hook
     * @return array
     */
    public function getHooks($entityName, $hook)
    {
        $hooks = [];
        if (isset($this->hooks[$entityName]) && isset($this->hooks[$entityName][$hook])) {
            $hooks = $this->hooks[$entityName][$hook];
        }

        if (is_callable([$entityName, 'hooks'])) {
            $entityHooks = $entityName::hooks();
            if (isset($entityHooks[$hook])) {
                // If you pass an object/method combination
                if (is_callable($entityHooks[$hook])) {
                    $hooks[] = $entityHooks[$hook];
                } else {
                    $hooks = array_merge($hooks, $entityHooks[$hook]);
                }
            }
        }

        return $hooks;
    }

    /**
     * Trigger an instance hook on the passed object.
     * @param \Sbux\Entity $object
     * @param string $hook
     * @param mixed $arguments
     * @return bool
     */
    protected function triggerInstanceHook($object, $hook, $arguments = [])
    {
        if (is_object($arguments) || !is_array($arguments)) {
            $arguments = [$arguments];
        }

        $ret = null;
        foreach($this->getHooks(get_class($object), $hook) as $callable) {
            if (is_callable([$object, $callable])) {
                $ret = call_user_func_array([$object, $callable], $arguments);
            } else {
                $args = array_merge([$object], $arguments);
                $ret = call_user_func_array($callable, $args);
            }

            if (false === $ret) {
                return false;
            }
        }

        return $ret;
    }

    /**
     * Trigger a static hook.  These pass the $object as the first argument
     * to the hook, and expect that as the return value.
     * @param string $objectClass
     * @param string $hook
     * @param mixed $arguments
     * @return bool
     */
    protected function triggerStaticHook($objectClass, $hook, $arguments)
    {
        if (is_object($arguments) || !is_array($arguments)) {
            $arguments = [$arguments];
        }

        array_unshift($arguments, $objectClass);
        foreach ($this->getHooks($objectClass, $hook) as $callable) {
            $return = call_user_func_array($callable, $arguments);
            if (false === $return) {
                return false;
            }
        }
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
        $adapter = $this->db;

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
