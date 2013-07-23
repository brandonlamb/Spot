<?php
namespace Spot\Type;
use Spot\Entity;

class Boolean implements TypeInterface
{
	/**
	 * @{inherit}
	 */
	public static function cast($value)
	{
		return (bool) $value;
	}

	/**
	 * @{inherit}
	 */
	public static function get(Entity $entity, $value)
	{
		return static::cast($value);
	}

	/**
	 * @{inherit}
	 */
	public static function set(Entity $entity, $value)
	{
		return static::cast($value);
	}
}
