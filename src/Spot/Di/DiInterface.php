<?php

namespace Spot\Di;

interface DiInterface
{
	/**
	 * Set an object into the container
	 *
	 * @param string $alias
	 * @param mixed $config
	 * @throws Exception
	 * @return DiInterface
	 */
	public function set($alias, $config);

	/**
	 * Set an object into the shared container
	 *
	 * @param string $alias
	 * @param mixed $config
	 * @throws Exception
	 * @return DiInterface
	 */
	public function setShared($alias, $config);

	/**
	 * Set an object/value into the parameter container
	 *
	 * @param string $alias
	 * @param mixed $value
	 * @return DiInterface
	 */
	public function setParam($alias, $value);

	/**
	 * Get an object from the container
	 *
	 * @param string $alias
	 * @throws Exception
	 * @return mixed
	 */
	public function get($alias);

	/**
	 * Get an object from the shared container
	 *
	 * @param string $alias
	 * @return mixed
	 */
	public function getShared($alias);

	/**
	 * Get an object from the param container
	 *
	 * @param string $alias
	 * @throws Exception
	 * @return mixed
	 */
	public function getParam($alias);

	/**
	 * Remove an object from the container
	 *
	 * @param string $alias
	 * @throws Exception
	 * @return DiInterface
	 */
	public function remove($alias);

	/**
	 * Set the default DI container to return by getDefault()
	 *
	 * @param DiInterface $di
	 * @return DiInterface
	 */
	public static function setDefault(DiInterface $di);

	/**
	 * Returns the default DI container instance, or if one was not created
	 * then created a new instance and set the default
	 *
	 * @return DiInterface
	 */
	public static function getDefault();

	/**
	 * Check if the container contains the index
	 *
	 * @param string $key
	 * @return bool
	 */
	public function has($key);

	/**
	 * Check if the shared container contains the index
	 *
	 * @param string $key
	 * @return bool
	 */
	public function hasShared($key);

	/**
	 * Check if the param container contains the index
	 *
	 * @param string $key
	 * @return bool
	 */
	public function hasParam($key);
}
