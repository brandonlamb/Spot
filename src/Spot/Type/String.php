<?php

namespace Spot\Type;

class String extends AbstractType implements TypeInterface
{
    /**
     * {@inherit}
     */
    public static function cast($value)
    {
        return (null !== $value) ? (string) $value : $value;
    }
}
