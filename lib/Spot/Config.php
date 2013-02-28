<?php
namespace Spot;
use Spot\Adapter\AdapterInterface;

/**
 * @package Spot
 */
class Config implements \Serializable
{
	/** @var string */
	protected $defaultConnection;

	/** @var array */
	protected $connections = array();

	/** @var \Model\Config */
	protected static $instance;

	/** @var array */
	protected static $typeHandlers = array();

	protected function __construct()
	{
		static::$typeHandlers = array(
			'string' => '\\Model\\Type\\String',
			'text' => '\\Model\\Type\\String',

			'int' => '\\Model\\Type\\Integer',
			'integer' => '\\Model\\Type\\Integer',

			'float' => '\\Model\\Type\\Float',
			'double' => '\\Model\\Type\\Float',
			'decimal' => '\\Model\\Type\\Float',

			'bool' => '\\Model\\Type\\Boolean',
			'boolean' => '\\Model\\Type\\Boolean',

			'datetime' => '\\Model\\Type\\Datetime',
			'date' => '\\Model\\Type\\Datetime',
			'timestamp' => '\\Model\\Type\\Integer',
			'year' => '\\Model\\Type\\Integer',
			'month' => '\\Model\\Type\\Integer',
			'day' => '\\Model\\Type\\Integer',
		);
	}

	/**
	 * Dont allow cloning
	 */
	protected function __clone() {}

	/**
	 * Singleton method
	 * @return \Spot\Config
	 */
	public static function getInstance()
	{
		if (!isset(static::$instance)) {
			static::$instance = new static;
		}
		return static::$instance;
	}

	/**
	 * Set type handler class by type
	 * @param string $type Field type (i.e. 'string' or 'int', etc.)
	 * @param string $class
	 */
	public static function setTypeHandler($type, $class)
	{
		static::$typeHandlers[(string) $type] = (string) $class;
	}

	/**
	 * Get type handler class by type
	 * @param string $type
	 * @return string
	 */
	public static function getTypeHandler($type)
	{
		if (!isset(static::$typeHandlers[$type])) {
			throw new \InvalidArgumentException("Type '$type' not registered. Register the type class handler with \Spot\Config::typeHanlder('$type', '\Namespaced\Path\Class').");
		}
		return static::$typeHandlers[$type];
	}

	/**
	 * Add database connection
	 * @param string $name Unique name for the connection
	 * @param PDO $conn PDO connection, managed outside
	 * @param array $options Array of key => value options for adapter
	 * @param boolean $defaut Use this connection as the default? The first connection added is automatically set as the default, even if this flag is false.
	 * @return Model\Adapter\AdapterInterface
	 * @throws Model\Exception
	 */
	public function addConnection($name, AdapterInterface $adapter, $default = false)
	{
		// Connection name must be unique
		if (isset($this->connections[$name])) {
			throw new Exception("Connection for '" . $name . "' already exists. Connection name must be unique.");
		}

		// Set as default connection?
		if (true === $default || null === $this->defaultConnection) {
			$this->defaultConnection = $name;
		}

		// Store connection and return adapter instance
		$this->connections[$name] = $adapter;
		return $adapter;
	}

	/**
	 * Get connection by name
	 * @param string $name Unique name of the connection to be returned
	 * @return Model\Adapter\AdapterInterface
	 * @throws Model\Exception
	 */
	public function connection($name = null)
	{
		null === $name && $name = $this->defaultConnection;
		return (isset($this->connections[$name])) ? $this->connections[$name] : false;
	}

	/**
	 * Get default connection
	 * @return Model\Adapter\AdapterInterface
	 */
	public function defaultConnection()
	{
		return $this->connection($this->defaultConnection);
	}

	/**
	 * Prevent adapter connections from being serialized
	 * @return string
	 */
	public function serialize()
	{
		return serialize(array());
	}

	/**
	 * {@inherit}
	 */
	public function unserialize($serialized) {}

	/**
	 * Class loader
	 *
	 * @param string $className Name of class to load
	 * @return bool
	 */
	public static function loadClass($className)
	{
		$loaded = false;

		// Require Spot namespaced files by assumed folder structure (naming convention)
		if (false !== strpos($className, 'Spot\\')) {
			$classFile = trim(str_replace('\\', '/', str_replace('_', '/', str_replace('Spot\\', '', $className))), '\\');
			$loaded = require_once(__DIR__ . '/' . $classFile . '.php');
		}

		return $loaded;
	}
}

/**
 * Register 'spot_load_class' function as an autoloader for files prefixed with 'Spot_'
 */
spl_autoload_register(array('\\Spot\\Config', 'loadClass'));
