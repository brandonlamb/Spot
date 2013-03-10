<?php
namespace Spot\Type;
use Spot\Entity;

class Datetime implements TypeInterface
{
	/**
	 * @{inherit}
	 */
	public static function cast($value)
	{
		if (is_string($value) || is_numeric($value)) {
			// Create new \DateTime instance from string value
			if (is_numeric($value)) {
				$value = new \DateTime('@' . $value);
			} else if ($value) {
				$value = new \DateTime($value);
			} else {
				$value = null;
			}
		}
		return null === $value ? $value : $value->format('Y-m-d');
	}

	/**
	 * @{inherit}
	 */
	public static function get(Entity $entity, $value)
	{
		return self::cast($value);
	}

	/**
	 * @{inherit}
	 */
	public static function set(Entity $entity, $value)
	{
		return self::cast($value);
	}
}
