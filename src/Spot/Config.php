<?php

/**
 * Configuration for entity field types
 *
 * @package \Spot
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot;

use Spot\Di\DiInterface,
    Spot\Di\InjectableTrait;

class Config
{
    use InjectableTrait;

    /**
     * @var array, Maps type to a type class to be used when filtering
     * entity column data
     */
    protected $typeHandlers;

    /**
     * Constructor
     * @param \Spot\Di\DiInterface $di
     */
    public function __construct(DiInterface $di)
    {
        $this->setDI($di);

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
            throw new \InvalidArgumentException(
                "'$offset' not a registered type. Register the type class handler with "
                . "\Spot\Config->setTypeHandler('$type', '\Namespaced\Path\Class')."
            );
        }
        return $this->typeHandlers[$offset];
    }
}
