<?php
/**
 * @package Spot
 */

// Setup autoloader from composer
#require_once __DIR__ . '/../vendor/autoload.php';
$autoloaders = array(
	dirname(__DIR__) . '/src/autoload.php',
	dirname(__DIR__) . '/vendor/autoload.php',
	__DIR__ . '/autoload.php',
);

foreach ($autoloaders as $autoloader) {
	if (file_exists($autoloader)) {
		include_once $autoloader;
	}
}

// Date setup
date_default_timezone_set('America/Los_Angeles');

// Set our include path by prepending paths
set_include_path(implode(PATH_SEPARATOR, array(
	__DIR__,
	__DIR__ . '/../src',
	get_include_path()
)));

// Autoload Spot test classes/unit tests
spl_autoload_register(function($className) {
	if (substr($className, 0, 4) === 'Spot') {
		$filename = str_replace(array('\\', '_'), DIRECTORY_SEPARATOR, trim($className, '\\_')) . '.php';
		require_once $filename;
	}
});

// Setup cache manager
#$cache = new \CacheCache\Cache(new \CacheCache\Backends\Dummy());
#$cacheManager = new \CacheCache\CacheManager();
#$cacheManager->set('cacheDummy', $cache);

// Setup available adapters for testing
$options = array(
	\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
	\PDO::ATTR_CASE => \PDO::CASE_LOWER,
	\PDO::ATTR_PERSISTENT => true,
);

$db = new \Pdo('mysql:host=localhost;dbname=test', 'test', 'test', $options);
#$db = new \Pdo('sqlite::memory:', $options);

// Setup config
$cfg = \Spot\Config::getInstance(true);
$adapter = new \Spot\Adapter\Mysql($db);
$adapter->database('test');
$cfg->addConnection('db', $adapter);

// Return Spot mapper for use
$mapper = new \Spot\Mapper($cfg);
function test_spot_mapper()
{
	global $mapper;
	return $mapper;
}

/**
 * Debug function, dont die after output
 */
function d()
{
	$string = '';
	foreach(func_get_args() as $value)
	{
		$string .= '<pre>';
		$string .= $value === NULL ? 'NULL' : (is_scalar($value) ? $value : print_r($value, TRUE));
		$string .= "</pre>\n";
	}
	exit($string);
}
