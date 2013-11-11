<?php

/**
 * A quick and dirty di container
 *
 * @package Spot
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot;

use Spot\Di\DiInterface;

class Di implements DiInterface
{
	/**
	 * @var array
	 */
	protected $storage;

	/**
	 * {@inheritDoc}
	 * @return Di
	 */
	public function set($offset, $value)
	{
		$this->storage[(string) $offset] = $value;
		return $this;
	}

	/**
	 * {@inheritDoc}
	 * @throws \InvalidArgumentException
	 */
	public function get($offset)
	{
		$offset = (string) $offset;
		if (!isset($this->storage[$offset])) {
			throw new \InvalidArgumentException("$offset is not in the DI container");
		}
		return $this->storage[$offset];
	}
}
