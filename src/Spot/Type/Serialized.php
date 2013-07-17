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
		if(is_string($value)) {
			$value = @unserialize($value);
		} else {
			$value = null;
		}
		return $value;
	}

	public static function dump($value)
	{
		return serialize($value);
	}
}