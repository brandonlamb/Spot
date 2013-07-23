<?php

namespace Spot\Type;

class Serialized extends AbstractType implements TypeInterface
{
    /**
     * {@inheritdoc}
     */
    public static function load($value)
    {
        return is_string($value) ? @unserialize($value) : null;
    }

    /**
     * {@inheritdoc}
     */
    public static function dump($value)
    {
        return serialize($value);
    }
}
