-<?php
/**
 * @package Spot
 */

// Require Spot_Config
require_once dirname(__DIR__) . '/lib/Spot/Config.php';

// Date setup
date_default_timezone_set('America/Los_Angeles');

// Setup available adapters for testing

$options = array(
	\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
	\PDO::ATTR_CASE => \PDO::CASE_LOWER,
	\PDO::ATTR_PERSISTENT => true,
);
$db = new Adapter\Mock();
$db->query("SET SCHEMA 'test'");

$cfg = \Spot\Config::getInstance();
$cfg->addConnection('db', new \Spot\Adapter\Pgsql($db));

/**
 * Return Spot mapper for use
 */
$mapper = new \Spot\Mapper($cfg);
function test_spot_mapper() {
	global $mapper;
	return $mapper;
}


/**
 * Autoload test fixtures
 */
function test_spot_autoloader($className) {
	// Only autoload classes that start with "Test_" and "Entity_"
	$prefixes = array('Test', 'Entity', 'Adapter');

	$valid = false;
	foreach ($prefixes as $prefix) {
		if (true === strpos($className, $prefix)) {
			$valid = true;
			break;
		}
	}
	if (!$valid) { return false; }

	$classFile = str_replace(array('\\', '_'), DIRECTORY_SEPARATOR, $className) . '.php';
	require __DIR__ . '/' . $classFile;
}
spl_autoload_register('test_spot_autoloader');
