<?php
namespace Spot\Type;

class Time extends Type\Datetime
{
    /**
     * @var string
     */
    public static $format = 'H:i:s';
}
