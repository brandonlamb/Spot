<?php

namespace Spot\Type;

class Date extends Type\Datetime
{
    /**
     * @var string
     */
    protected static $format = 'Y-m-d';
}
