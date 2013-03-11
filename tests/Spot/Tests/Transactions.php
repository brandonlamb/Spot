<?php
/**
 * @package Spot
 */

namespace Spot\Tests;

class Transactions extends SpotTestCase
{
	protected $backupGlobals = false;

	public static function setupBeforeClass()
	{
		$mapper = test_spot_mapper();
		$mapper->migrate('\Spot\Entity\Post');
	}

	public function testInsertWithTransaction()
	{
		$post = new \Spot\Entity\Post();
		$mapper = test_spot_mapper();
		$post->title = "Test Post with Transaction";
		$post->body = "<p>This is a really awesome super-duper post -- in a TRANSACTION!.</p>";
		$post->date_created = $mapper->connection('\Spot\Entity\Post')->dateTime();

		// Save in transation
		$phpunit = $this;
		$mapper->transaction(function($mapper) use($post, $phpunit) {
			$result = $mapper->insert($post);
		});

		// Ensure save was successful
		$this->assertInstanceOf('\Spot\Entity\Post', $mapper->first('\Spot\Entity\Post', array('title' => $post->title)));
	}

	public function testInsertWithTransactionRollbackOnException()
	{
		$post = new \Spot\Entity\Post();
		$mapper = test_spot_mapper();
		$post->title = "Rolledback";
		$post->body = "<p>This is a really awesome super-duper post -- in a TRANSACTION!.</p>";
		$post->date_created = $mapper->connection('\Spot\Entity\Post')->dateTime();

		// Save in transation
		$phpunit = $this;

		try {
			$mapper->transaction(function($mapper) use($post, $phpunit) {
				$result = $mapper->insert($post);

				// Throw exception AFTER save to trigger rollback
				throw new \LogicException("Exceptions should trigger auto-rollback");
			});
		} catch(\LogicException $e) {
			// Ensure record was NOT saved
			$this->assertFalse($mapper->first('\Spot\Entity\Post', array('title' => $post->title)));
		}
	}

	public function testInsertWithTransactionRollbackOnReturnFalse()
	{
		$post = new \Spot\Entity\Post();
		$mapper = test_spot_mapper();
		$post->title = "Rolledback";
		$post->body = "<p>This is a really awesome super-duper post -- in a TRANSACTION!.</p>";
		$post->date_created = $mapper->connection('\Spot\Entity\Post')->dateTime();

		// Save in transation
		$phpunit = $this;

		$mapper->transaction(function($mapper) use($post, $phpunit) {
			$result = $mapper->insert($post);

			// Return false AFTER save to trigger rollback
			return false;
		});

		// Ensure record was NOT saved
		$this->assertFalse($mapper->first('\Spot\Entity\Post', array('title' => $post->title)));
	}
}
