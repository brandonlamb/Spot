<?php
namespace Spot\Type;
use Spot\Entity;

interface TypeInterface
{
	/**
	 * Cast a value
	 * @param mixed $value
	 * @return mixed
	 */
	public static function cast($value);

	/**
	 * Get value
	 * @param \Spot\Entity $entity
	 * @param mixed $value
	 * @return mixed
	 */
	public static function get(Entity $entity, $value);

	/**
	 * Get value
	 * @param \Spot\Entity $entity
	 * @param mixed $value
	 * @return mixed
	 */
	public static function set(Entity $entity, $value);
}
