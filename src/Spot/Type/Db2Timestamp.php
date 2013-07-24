<?php

namespace Spot\Type;

class Db2Timestamp extends Datetime
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
                // 2013-07-10-15.16.22.684600 - Capture date and time, pass to DateTime
                $matches = array();
                preg_match('/(\d{4}-\d{1,2}-\d{1,2})-(\d{1,2}\.\d{1,2}\.\d{1,2}).*/', $value, $matches);
                count($matches) === 3 && $value = new \DateTime($matches[1] . ' ' . $matches[2]);
            } else {
                $value = null;
            }
        }
        return $value instanceof \DateTime ? $value->format(static::$format) : $value;
    }
}
