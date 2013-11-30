<?php

namespace Spot\Type;

class Integer extends AbstractType implements TypeInterface
{
    /**
     * @{inherit}
     */
    public static function cast($value)
    {
        return (strlen($value)) ? (int) $value : null;
    }
}
