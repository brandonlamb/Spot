<?php

/**
 * A quick and dirty trait for classes that need di
 */

namespace Spot\Di;

use Spot\Di as DiContainer;

trait InjectableTrait
{
	/**
	 * @var \Spot\Di
	 */
	protected $di;

	/**
	 * Magic method to get a resource from di container
	 * @param string $offset
	 * @return mixed
	 */
	public function __get($offset)
	{
		return $this->di->get($offset);
	}

	/**
	 * Set the Di object
	 * @param \Spot\Di $di
	 */
	public function setDi(DiContainer $di)
	{
		$this->di = $di;
	}

	/**
	 * Get the Di container
	 * @return \Spot\Di
	 */
	public function getDi()
	{
		return $this->di;
	}
}
