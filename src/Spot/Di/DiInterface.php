<?php

/**
 * An interface for a DI container to swap out for a framework
 * specific adapter version if needed
 *
 * @package \Spot\Di
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Di;

use Spot\Di as DiContainer;

interface DiInterface
{
	/**
	 * Store a resource into the container
	 * @param string $offset
	 * @param mixed $value
	 */
	public function set($offset, $value);

	/**
	 * Get a resource from the container
	 * @param string $offset
	 * @return mixed
	 */
	public function get($offset);
}
