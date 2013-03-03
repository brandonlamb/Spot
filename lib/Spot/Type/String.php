<?php
namespace Spot\Type;
use Spot\Entity;

class String implements TypeInterface
{
	/**
	 * @{inherit}
	 */
	public static function cast($value)
	{
		return (null !== $value) ? (string) $value : $value;
<<<<<<< HEAD
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

=======
>>>>>>> f7165aec94ab39b60251b2c289a71b2102e02aee
	}

	/**
	 * @{inherit}
	 */
	public static function get(Entity $entity, $value)
	{
		return self::cast($value);
	}

	/**
<<<<<<< HEAD
>>>>>>> da1eacf628674b443d0628450409bff3477f011f
	 * Setting value on Entity object
=======
	 * @{inherit}
>>>>>>> f7165aec94ab39b60251b2c289a71b2102e02aee
	 */
	public static function set(Entity $entity, $value)
	{
		return self::cast($value);
	}
}
