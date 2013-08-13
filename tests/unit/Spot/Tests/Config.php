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
		$adapter = $cfg->addConnection('test_adapter', new \Spot\Adapter\Mock());
		$this->assertInstanceOf('\Spot\Adapter\Mock', $adapter);
	}

	public function testConfigCanSerialize()
	{
		$cfg = \Spot\Config::getInstance(true);
		$adapter = $cfg->addConnection('test_adapter', new \Spot\Adapter\Mock());
		$this->assertInternalType('string', serialize($cfg));
	}

	public function testConfigCanUnserialize()
	{
		$cfg = \Spot\Config::getInstance(true);
		$adapter = $cfg->addConnection('test_adapter', new \Spot\Adapter\Mock());
		$this->assertInstanceOf('\Spot\Config', unserialize(serialize($cfg)));
	}
}