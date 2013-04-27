<?php
namespace Spot\Type;

use Spot\Entity;

class Date extends Datetime
{
	/** @var string */
	protected static $format = 'Y-m-d';
}
