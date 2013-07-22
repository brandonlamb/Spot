<?php
namespace Spot\Type;
use Spot\Entity;

class String implements TypeInterface
{
	/**
	 * {@inherit}
	 */
	public static function cast($value)
	{
		return (null !== $value) ? (string) $value : $value;
	}

	/**
	 * {@inherit}
	 */
	public static function get(Entity $entity, $value)
	{
		return static::cast($value);
	}

	/**
	 * {@inherit}
	 */
	public static function set(Entity $entity, $value)
	{
		return static::cast($value);
	}
}
