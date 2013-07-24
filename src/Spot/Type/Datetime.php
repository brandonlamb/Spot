<?php

namespace Spot\Type;

class Datetime extends AbstractType implements TypeInterface
{
    /**
     * @var string
     */
    protected static $format = 'Y-m-d h:i:s';

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
                $value = new \DateTime();
            }
        }

        if ($value instanceof \DateTime) {
            return $value->format(static::$format);
        } else {
            $value = new \DateTime();
            return $value->format(static::$format);
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function dump($value)
    {
        return static::cast($value);
    }
}
