<?php

namespace Spot\Type;

use Spot\Entity,
	Spot\Adapter\AdapterInterface;

interface TypeInterface
{
    /**
     * Cast given value to type required
     * @param mixed
     * @return mixed
     */
	public static function cast($value);

    /**
     * Geting value off Entity object
     * @param \Spot\Entity $entity
     * @param string $name
     * @return mixed
     */
	public static function get(Entity $entity, $name);

    /**
     * Setting value on Entity object
     * @param \Spot\Entity $entity
     * @param string $name
     * @return mixed
     */
	public static function set(Entity $entity, $name);

    /**
     * Load value as passed from the datasource
     * @param mixed $value
     * @return mixed
     */
    public static function load($value);

    /**
     * Load value as passed from the datasource
     * internal to allow for extending on a per-adapter basis
     * @param mixed $value
     * @param \Spot\Adapter\AdapterInterface $adapter
     * @return mixed
     */
    public static function loadInternal($value, AdapterInterface $adapter = null);

    /**
     * Dump value as passed to the datasource
     * @param mixed $value
     * @return mixed
     */
    public static function dump($value);

    /**
     * Dumps value as passed to the datasource
     * internal to allow for extending on a per-adapter basis
     * @param mixed $value
     * @param \Spot\Adapter\AdapterInterface $adapter
     * @return mixed
     */
    public static function dumpInternal($value, AdapterInterface $adapter = null);
}
