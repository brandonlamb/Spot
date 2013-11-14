<?php

namespace Spot\Di;

class Container implements DiInterface
{
	/**
	 * @var Di
	 */
	protected static $defaultInstance;

	/**
	 * @var array
	 */
	protected $container;

	/**
	 * @var array, array to hold shared objects for classes that should get
	 * instantiated only once such as a database object
	 */
	protected $sharedContainer;

	/**
	 * @var array, hold parameters
	 */
	protected $paramContainer;

	/**
	 * Accept an array as a container to populate
	 *
	 * @param array $container
	 * @param array $sharedContainer
	 * @param array $paramContainer
	 * @return Di
	 */
	public function __construct($container = null, $sharedContainer = null, $paramContainer = null)
	{
		if (null === static::$defaultInstance) {
			self::$defaultInstance = $this;
		}

		$this->container = is_array($container) ? $container : array();
		$this->sharedContainer = is_array($sharedContainer) ? $sharedContainer : array();
		$this->paramContainer = is_array($paramContainer) ? $paramContainer : array();
	}

	/**
	 * Magic getter to access container objects
	 *
	 * @param string $alias
	 * @return mixed
	 */
	public function __get($alias)
	{
		return $this->get($alias);
	}

	/**
	 * Magic getter to set container objects
	 *
	 * @param string $alias
	 * @param mixed $config
	 */
	public function __set($alias, $config)
	{
		return $this->set($alias, $config);
	}

	/**
	 * Set an object into the container
	 *
	 * @param string $alias
	 * @param mixed $config
	 * @throws Di\Exception
	 * @return DiInterface
	 */
	public function set($alias, $config)
	{
		// If config is an array, verify it has a required className key
		if (is_array($config) && !isset($config['className'])) {
			throw new Exception('Must contain a className key.');
		}

		$this->container[$alias] = $config;

		return $this;
	}

	/**
	 * Set an object into the shared container
	 *
	 * @param string $alias
	 * @param mixed $config
	 * @throws Exception
	 * @return DiInterface
	 */
	public function setShared($alias, $config)
	{
		// If config is an array, verify it has a required className key
		if (is_array($config) && !isset($config['className'])) {
			throw new Exception('Must contain a className key.');
		}

		$this->sharedContainer[$alias] = $config;
		return $this;
	}

	/**
	 * Set an object/value into the parameter container
	 *
	 * @param string $alias
	 * @param mixed $value
	 * @return DiInterface
	 */
	public function setParam($alias, $value)
	{
		$this->paramContainer[$alias] = $value;
		return $this;
	}

	/**
	 * Get an object from the container
	 *
	 * @param string $alias
	 * @throws Exception
	 * @return mixed
	 */
	public function get($alias)
	{
		if (!isset($this->container[$alias])) {
			throw new Exception($alias . ' is not defined.');
		}

		// If the object is a string, return new object using the value as the class name
		if (is_string($this->container[$alias])) {
			return new $this->container[$alias]();
		}

		// If the object is an array, return a new object using the defined class name
		// and pass in the object as the constructor parameter. If the class is an instance
		// of a InjectionAwareInterface then set the Di container for the object
		if (is_array($this->container[$alias])) {
			$className = $this->container[$alias]['className'];
			$instance = new $className($this->container[$alias]);
			if ($instance instanceof InjectionAwareInterface) {
				$instance->setDi($this);
			}

			return $instance;
		}

		// If the object is a Closure, just return it
		if ($this->container[$alias] instanceof \Closure) {
			return $this->container[$alias]();
		}

		// Object is an already instantiated object, just return it
		return $this->container[$alias];
	}

	/**
	 * Get an object from the shared container
	 *
	 * @param string $alias
	 * @return mixed
	 */
	public function getShared($alias)
	{
		// If the shared object is already set then just return if
		if (isset($this->sharedContainer[$alias])) {
			return $this->sharedContainer[$alias];
		}

		// Pull in a new instance of the requested alias from the container.
		// Equivalent of $obj = $di->get('abc'), $di->setShared('abc', $obj)
		$this->sharedContainer[$alias] = $this->get($alias);
		return $this->sharedContainer[$alias];
	}

	/**
	 * Get an object from the param container
	 *
	 * @param string $alias
	 * @throws Exception
	 * @return mixed
	 */
	public function getParam($alias)
	{
		if (!isset($this->paramContainer[$alias])) {
			throw new Exception('Parameter not set: ' . $alias);
		}

		// Return the parameter
		return $this->paramContainer[$alias];
	}

	/**
	 * Remove an object from the container
	 *
	 * @param string $alias
	 * @throws Exception
	 * @return DiInterface
	 */
	public function remove($alias)
	{
		if (!isset($this->container[$alias])) {
			throw new Exception($alias . ' is not defined.');
		}

		unset($this->container[$alias]);

		return $this;
	}

	/**
	 * Set the default Di container to return by getDefault()
	 *
	 * @param DiInterface $di
	 * @return DiInterface
	 */
	public static function setDefault(DiInterface $di)
	{
		self::$defaultInstance = $di;
		return $di;
	}

	/**
	 * Returns the default Di container instance, or if one was not created
	 * then created a new instance and set the default
	 *
	 * @return DiInterface
	 */
	public static function getDefault()
	{
		if (null === self::$defaultInstance) {
			self::$defaultInstance = new self();
		}

		return self::$defaultInstance;
	}

	/**
	 * Check if the container contains the index
	 *
	 * @param string $key
	 * @return bool
	 */
	public function has($key)
	{
		return isset($this->container[$key]);
	}

	/**
	 * Check if the shared container contains the index
	 *
	 * @param string $key
	 * @return bool
	 */
	public function hasShared($key)
	{
		return isset($this->sharedContainer[$key]);
	}

	/**
	 * Check if the param container contains the index
	 *
	 * @param string $key
	 * @return bool
	 */
	public function hasParam($key)
	{
		return isset($this->paramContainer[$key]);
	}
}
