<?php

namespace Spot;

use Spot\Adapter\AdapterInterface;

class Config
{
    /**
     * @var string, The name of the named connection to use by default
     */
    protected $defaultConnection;

    /**
     * @var array, Named connections, indexed by name
     */
    protected $connections;

    /**
     * @var array, Maps type to a type class to be used when filtering
     * entity column data
     */
    protected $typeHandlers;

    public function __construct()
    {
        $this->connections = [];

        $this->typeHandlers = [
            'string'    => '\\Spot\\Type\\String',
            'text'      => '\\Spot\\Type\\String',
            'char'      => '\\Spot\\Type\\String',
            'varchar'   => '\\Spot\\Type\\String',

            'int'       => '\\Spot\\Type\\Integer',
            'integer'   => '\\Spot\\Type\\Integer',
            'number'    => '\\Spot\\Type\\Integer',
            'numeric'   => '\\Spot\\Type\\Integer',

            'float'     => '\\Spot\\Type\\Float',
            'double'    => '\\Spot\\Type\\Float',
            'decimal'   => '\\Spot\\Type\\Float',

            'bool'      => '\\Spot\\Type\\Boolean',
            'boolean'   => '\\Spot\\Type\\Boolean',

            'datetime'  => '\\Spot\\Type\\Datetime',
            'date'      => '\\Spot\\Type\\Datetime',
            'timestamp' => '\\Spot\\Type\\Integer',
            'year'      => '\\Spot\\Type\\Integer',
            'month'     => '\\Spot\\Type\\Integer',
            'day'       => '\\Spot\\Type\\Integer',

            'db2.date'  => '\\Spot\\Type\\Db2Date',
            'db2.timestamp' => '\\Spot\\Type\\Db2Timestamp',
        ];
    }

    /**
     * Set type handler class by type
     * @param string $type Field type (i.e. 'string' or 'int', etc.)
     * @param string $class
     * @return \Spot\Config
     */
    public function setTypeHandler($type, $class)
    {
        $this->typeHandlers[(string) $type] = (string) $class;
        return $this;
    }

    /**
     * Get type handler class by type
     * @param string $offset
     * @return string
     * @throws \InvalidArgumentException
     */
    public function getTypeHandler($offset)
    {
        if (!isset($this->typeHandlers[$offset])) {
            throw new \InvalidArgumentException("Type '$offset' not registered. Register the type class handler with \Spot\Config::typeHandler('$type', '\Namespaced\Path\Class').");
        }
        return $this->typeHandlers[$offset];
    }

    /**
     * Add database connection. If passing $default = true, Spot will
     * use the passed connection as the default connection.
     * @param string $offset Unique name for the connection
     * @param \Spot\Adapter\AdapterInterface $adapter
     * @param boolean $defaut Use this connection as the default? The first connection added is automatically set as the default, even if this flag is false.
     * @return \Spot\Config
     * @throws \InvalidArgumentException
     */
    public function addConnection($offset, AdapterInterface $adapter, $default = false)
    {
        // Connection name must be unique
        if (isset($this->connections[$offset])) {
            throw new \InvalidArgumentException("Connection for '" . $offset . "' already exists. Connection name must be unique.");
        }

        // Set as default connection?
        if (true === $default || null === $this->defaultConnection) {
            $this->defaultConnection = $offset;
        }

        // Store connection and return adapter instance
        $this->connections[$offset] = $adapter;

        return $this;
    }

    /**
     * Get connection by name. Passing null will return default connection
     * @param string $offset Unique name of the connection to be returned
     * @return \Spot\Adapter\AdapterInterface
     * @throws \InvalidArgumentException
     */
    public function getConnection($offset = null)
    {
        null === $offset && $offset = $this->defaultConnection;
        if (!isset($this->connections[$offset])) {
            throw new \InvalidArgumentException("'$offset' is not a configured connection");
        }
        return  $this->connections[$offset];
    }
}
