<?php

namespace Spot\Type;

class Boolean extends AbstractType implements TypeInterface
{
    /**
     * @{inherit}
     */
    public static function cast($value)
    {
        return (bool) $value;
    }

    /**
     * Boolean is generally persisted as an integer
     */
    public static function dump($value)
    {
        return (int) $value;
    }
}
