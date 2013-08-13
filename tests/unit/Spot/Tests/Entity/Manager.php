<?php
/**
 * @package Spot
 */

namespace Spot\Tests\Entity;

use Spot\Tests\SpotTestCase;

class Manager extends SpotTestCase
{
	protected $backupGlobals = false;

	public function testEntityFields()
	{
		$mapper = test_spot_mapper();
		$post = new \Spot\Entity\Post();

		$fields = $mapper->fields('\Spot\Entity\Post');
		$sortedFields = array_keys($fields);
		//ksort($sortedFields);

		// Assert $fields are correct
		$testFields = array('id', 'title', 'body', 'status', 'date_created');
		//ksort($testFields);
		$this->assertEquals($sortedFields, $testFields);
	}

	public function testEntityRelations()
	{
		$mapper = test_spot_mapper();
		$post = new \Spot\Entity\Post();

		$relations = $mapper->relations('\Spot\Entity\Post');
		$sortedRelations = array_keys($relations);
		sort($sortedRelations);

		// Assert $relations are correct
		$testRelations = array('comments');
		sort($testRelations);
		$this->assertEquals($sortedRelations, $testRelations);
	}
}