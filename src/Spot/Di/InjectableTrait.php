<?php

/**
 * A quick and dirty trait for classes that need di
 * @package \Spot\Di
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Di;

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
		return $this->di->getShared($offset);
	}

	/**
	 * Set the Di object
	 * @param \Spot\Di\DiInterface $di
	 */
	public function setDi(DiInterface $di)
	{
		$this->di = $di;
	}

	/**
	 * Get the Di container
	 * @return \Spot\Di\DiInterface
	 */
	public function getDi()
	{
		return $this->di;
	}
}
