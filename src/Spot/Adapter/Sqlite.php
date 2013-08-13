<?php

namespace Spot\Adapter;

/**
 * Sqlite Database Adapter
 */
class Sqlite extends AbstractAdapter implements AdapterInterface
{
    /**
     * {@inheritdoc}
     */
    public function escapeField($field)
    {
        return $field === '*' ? $field : '"' . $field . '"';
    }

    /**
     * {@inherit}
     */
    public function migrate($table, array $fields, array $options = array())
    {
        // Get current fields for table
        $result = $this->query('SELECT name, sql FROM sqlite_master WHERE type = ? ORDER BY name', array($table));
        $tableExists = count($result) ? true : false;

        if ($tableExists) {
            // Update table
            $this->migrateTableUpdate($table, $fields);
        } else {
            // Create table
            $this->migrateTableCreate($table, $fields);
        }

        return $this;
    }

    /**
     * Execute a CREATE TABLE command
     * @param String $table Table name
     * @param Array $fields Fields and their attributes as defined in the mapper
     * @param Array $options Options that may affect migrations or how tables are setup
     * @return self
     */
    public function migrateTableCreate($table, array $formattedFields, array $options = array())
    {
        // STEPS:
        // * Use fields to get column syntax
        // * Use column syntax array to get table syntax
        // * Run SQL

        // Prepare fields and get syntax for each
        $columnsSyntax = array();
        foreach ($formattedFields as $fieldName => $fieldInfo) {
            $columnsSyntax[$fieldName] = $this->migrateSyntaxFieldCreate($fieldName, $fieldInfo);
        }

        // Get syntax for table with fields/columns
        $sql = $this->migrateSyntaxTableCreate($table, $formattedFields, $columnsSyntax, $options);

        // Add query to log
        \Spot\Log::addQuery($this, $sql);

        $this->connection()->exec($sql);

        return $this;
    }

    /**
     * Syntax for each column in CREATE TABLE command
     * @param string $fieldName Field name
     * @param array $fieldInfo Array of field settings
     * @return string SQL syntax
     * @throws \Spot\Exception\Adapter
     */
    public function migrateSyntaxFieldCreate($fieldName, array $fieldInfo)
    {
        // Ensure field type exists
        if (!isset($this->fieldTypeMap[$fieldInfo['type']])) {
            throw new \Spot\Exception\Adapter("Field type '" . $fieldInfo['type'] . "' not supported");
        }

        // Ensure this class will choose adapter type
        unset($fieldInfo['adapter_type']);

        $fieldInfo = array_merge($this->fieldTypeMap[$fieldInfo['type']],$fieldInfo);
        $syntax = "`" . $fieldName . "` " . $fieldInfo['adapter_type'];

        // Column type and length
        $syntax .= ($fieldInfo['length']) ? '(' . $fieldInfo['length'] . ')' : '';

        // Nullable
        $isNullable = true;
        if ($fieldInfo['required'] || !$fieldInfo['null']) {
            $syntax .= ' NOT NULL';
            $isNullable = false;
        }

        // Default value
        if ($fieldInfo['default'] === null && $isNullable) {
            $syntax .= ' DEFAULT NULL';
        } elseif ($fieldInfo['default'] !== null) {
            $default = $fieldInfo['default'];
            // If it's a boolean and $default is boolean then it should be 1 or 0
            if ( is_bool($default) && $fieldInfo['type'] == "boolean" ) {
                $default = $default ? 1 : 0;
            }

            if (is_scalar($default)) {
                $syntax .= " DEFAULT '" . $default . "'";
            }
        }

        // Serial/autoincrement PK
        $syntax .= ($fieldInfo['primary'] && $fieldInfo['serial']) ? ' AUTO_INCREMENT' : '';

        return $syntax;
    }

    /**
     * @{inherit}
     */
    public function createDatabase($database)
    {
        $sql = 'CREATE DATABASE ' . $database;

        // Add query to log
        \Spot\Log::addQuery($this, $sql);

        return $this->connection()->exec($sql);
    }

    /**
     * @{inherit}
     */
    public function dropDatabase($database)
    {
        $sql = 'DROP DATABASE ' . $database;

        // Add query to log
        \Spot\Log::addQuery($this, $sql);

        return $this->connection()->exec($sql);
    }

    /**
     * {@inherit}
     */
    public function truncateDatasource($datasource)
    {
        return $this;
    }

    /**
     * {@inherit}
     */
    public function dropDatasource($datasource)
    {
        return $this;
    }
}
