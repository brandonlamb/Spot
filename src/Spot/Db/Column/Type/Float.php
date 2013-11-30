<?php

namespace Spot\Type;

class Float extends AbstractType implements TypeInterface
{
    /**
     * @var array
     */
    public static $defaultOptions = array('precision' => 14, 'scale' => 10);

    /**
     * @{inherit}
     */
    public static function cast($value)
    {
        return (strlen($value)) ? (float) $value : null;
    }
}
