<?php
/**
 * @package Spot
 */

namespace Spot\Tests;

class Insert extends SpotTestCase
{
	protected $backupGlobals = false;

	public static function setupBeforeClass()
	{
		$mapper = test_spot_mapper();
#		$mapper->migrate('\Spot\Entity\Post');
	}

	public function testInsertPostEntity()
	{
		$post = new \Spot\Entity\Post();
		$mapper = test_spot_mapper();
		$post->title = "Test Post";
		$post->body = "<p>This is a really awesome super-duper post.</p><p>It's really quite lovely.</p>";
		$post->date_created = $mapper->connection('\Spot\Entity\Post')->dateTime();

		$result = $mapper->insert($post); // returns inserted id

		$this->assertTrue($result !== false);
	}

	public function testInsertPostArray()
	{
		$mapper = test_spot_mapper();
		$post = array(
			'title' => "Test Post",
			'body' => "<p>This is a really awesome super-duper post.</p><p>It's really quite lovely.</p>",
			'date_created' => $mapper->connection('\Spot\Entity\Post')->dateTime()
		);

		$result = $mapper->insert('\Spot\Entity\Post', $post); // returns inserted id

		$this->assertTrue($result !== false);
	}
}
