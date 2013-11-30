<?php

namespace Spot\Type;

use Spot\Entity,
    Spot\Adapter\AdapterInterface;

class AbstractType implements TypeInterface
{
    /**
     * @var array
     */
    public static $loadHandlers = array();

    /**
     * @var array
     */
    public static $dumpHandlers = array();

    /**
     * @var array
     */
    public static $defaultOptions = array();

    /**
     * {@inheritdoc}
     */
    public static function cast($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public static function get(Entity $entity, $value)
    {
        return static::cast($value);
    }

    /**
     * {@inheritdoc}
     */
    public static function set(Entity $entity, $value)
    {
        return static::cast($value);
    }

    /**
     * {@inheritdoc}
     */
    public static function load($value)
    {
        return static::cast($value);
    }

    /**
     * {@inheritdoc}
     */
    public static function dump($value)
    {
        return static::cast($value);
    }

    /**
     * {@inheritdoc}
     */
    public static function loadInternal($value, AdapterInterface $adapter = null)
    {
        if (isset(static::$loadHandlers[$adapter]) && is_callable(static::$loadHandlers[$adapter])) {
            return call_user_func(static::$loadHandlers[$adapter], $value);
        }
        return static::load($value);
    }

    /**
     * {@inheritdoc}
     */
    public static function dumpInternal($value, AdapterInterface $adapter = null)
    {
        if (isset(static::$dumpHandlers[$adapter]) && is_callable(static::$dumpHandlers[$adapter])) {
            return call_user_func(static::$dumpHandlers[$adapter], $value);
        }
        return static::dump($value);
    }
}
