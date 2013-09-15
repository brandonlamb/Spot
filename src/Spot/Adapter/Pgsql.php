<?php

namespace Spot\Adapter;

/**
 * Postgresql Database Adapter
 */
class Pgsql extends AbstractAdapter implements AdapterInterface
{
    /**
     * {@inheritdoc}
     */
    public function escapeField($field)
    {
		if (false !== strpos('.', $field)) {
            return $field === '*' ? $field : '"' . $field . '"';
        } else {
			return $field;
		}
    }

    /**
     * {@inherit}
     */
    public function migrate($table, array $fields, array $options = array())
    {
        return $this;
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

    /**
     * {@inheritdoc}
     */
    public function create($datasource, array $data, array $options = array())
    {
        if ($options['serial'] === true && empty($options['sequence'])) {
            $options['sequence'] = $datasource . '_id_seq';
        }
        return parent::create($datasource, $data, $options);
    }
}
