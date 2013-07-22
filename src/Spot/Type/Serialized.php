<?php
namespace Spot\Type;

use Spot\Entity,
	Spot\Type;

class Serialized extends Type
{
	/**
	 * Cast given value to type required
	 */
	public static function load($value)
	{
		return is_string($value) ? @unserialize($value) : null;
	}

	public static function dump($value)
	{
		return serialize($value);
	}
}
