<?php
namespace Spot\Type;

use Spot\Entity;

class Time extends Datetime
{
	/** @var string */
	public static $format = 'H:i:s';
}