<?php
namespace Spot\Type;
use Spot\Entity;

class String implements TypeInterface
{
	/**
	 * Cast given value to type required
	 */
	public static function cast($value)
	{
		return (null !== $value) ? (string) $value : $value;
<<<<<<< HEAD
	}

	/**
	 * Geting value off Entity object
	 */
	public static function get(Entity $entity, $value)
	{
		return self::cast($value);
	}

	/**
=======

	}

	/**
	 * Geting value off Entity object
	 */
	public static function get(Entity $entity, $value)
	{
		return self::cast($value);
	}

	/**
>>>>>>> da1eacf628674b443d0628450409bff3477f011f
	 * Setting value on Entity object
	 */
	public static function set(Entity $entity, $value)
	{
		return self::cast($value);
	}
}
