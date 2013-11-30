<?php

/**
 * Configuration for entity field types
 *
 * @package \Spot
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Db\Column;

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
            'string'    => '\\Spot\\Db\\Column\\Type\\String',
            #'text'      => '\\Spot\\Db\\Column\\Type\\String',
            #'char'      => '\\Spot\\Db\\Column\\Type\\String',
            #'varchar'   => '\\Spot\\Db\\Column\\Type\\String',

            #'int'       => '\\Spot\\Db\\Column\\Type\\Integer',
            'integer'   => '\\Spot\\Db\\Column\\Type\\Integer',
            #'number'    => '\\Spot\\Db\\Column\\Type\\Integer',
            #'numeric'   => '\\Spot\\Db\\Column\\Type\\Integer',

            'float'     => '\\Spot\\Db\\Column\\Type\\Float',
            #'double'    => '\\Spot\\Db\\Column\\Type\\Float',
            #'decimal'   => '\\Spot\\Db\\Column\\Type\\Float',

            #'bool'      => '\\Spot\\Db\\Column\\Type\\Boolean',
            'boolean'   => '\\Spot\\Db\\Column\\Type\\Boolean',

            'datetime'  => '\\Spot\\Db\\Column\\Type\\Datetime',
            'date'      => '\\Spot\\Db\\Column\\Type\\Date',
            #'timestamp' => '\\Spot\\Db\\Column\\Type\\Integer',
            #'year'      => '\\Spot\\Db\\Column\\Type\\Integer',
            #'month'     => '\\Spot\\Db\\Column\\Type\\Integer',
            #'day'       => '\\Spot\\Db\\Column\\Type\\Integer',

            'db2.date'  => '\\Spot\\Db\\Column\\Type\\Db2Date',
            'db2.timestamp' => '\\Spot\\Db\\Column\\Type\\Db2Timestamp',
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
