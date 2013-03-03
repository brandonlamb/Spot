<?php
namespace Spot\Type;
use Spot\Entity;

class Integer implements TypeInterface
{
	/**
	 * {@inherit}
	 */
	public static function cast($value)
	{
		return (strlen($value)) ? (int) $value : null;
	}

	/**
	 * {@inherit}
	 */
	public static function get(Entity $entity, $value)
	{
		return self::cast($value);
	}

	/**
	 * {@inherit}
	 */
	public static function set(Entity $entity, $value)
	{
		return self::cast($value);
	}
}