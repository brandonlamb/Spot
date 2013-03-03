<?php
use Spot\Adapter\PdoInterface;

class Adapter_Mock implements PdoInterface
{
	public function __construct($connection = null)
	{

	}
}