<?php
/**
 * @package Spot
 */

// Setup autoloader from composer
require_once __DIR__ . '/../vendor/autoload.php';

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
$cache = new \CacheCache\Cache(new \CacheCache\Backends\Dummy());
$cacheManager = new \CacheCache\CacheManager();
$cacheManager->set('cacheDummy', $cache);

// Setup available adapters for testing
$options = array(
	\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
	\PDO::ATTR_CASE => \PDO::CASE_LOWER,
	\PDO::ATTR_PERSISTENT => true,
);

// Setup config
$cfg = \Spot\Config::getInstance(true);
$cfg->addConnection('db', new \Spot\Adapter\Mock());

// Return Spot mapper for use
$mapper = new \Spot\Mapper($cfg);
function test_spot_mapper()
{
	global $mapper;
	return $mapper;
}