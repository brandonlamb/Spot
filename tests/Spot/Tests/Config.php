<?php
/**
 * @package Spot
 */

namespace Spot\Tests;

class Config extends SpotTestCase
{
	protected $backupGlobals = false;

	public function testAddConnectionWithDSNString()
	{
		$cfg = \Spot\Config::getInstance(true);
		$adapter = $cfg->addConnection('test_mysql', 'mysql://test:password@localhost/test');
		$this->assertInstanceOf('\Spot\Adapter\Mysql', $adapter);
	}

	public function testConfigCanSerialize()
	{
		$cfg = \Spot\Config::getInstance(true);
		$adapter = $cfg->addConnection('test_mysql', 'mysql://test:password@localhost/test');

		$this->assertInternalType('string', serialize($cfg));
	}

	public function testConfigCanUnserialize()
	{
		$cfg = \Spot\Config::getInstance(true);
		$adapter = $cfg->addConnection('test_mysql', 'mysql://test:password@localhost/test');

		$this->assertInstanceOf('\Spot\Config', unserialize(serialize($cfg)));
	}
}