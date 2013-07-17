<?php
namespace Spot\Type;

use Spot\Entity;

class Date extends Type\Datetime
{
	/** @var string */
	protected static $format = 'Y-m-d';
}
