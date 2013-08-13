<?php
/**
 * User
 *
 * @package Spot
 */

namespace Spot\Entity;

use Spot\Entity;

class User extends Entity
{
	protected static $datasource = 'test_users';

	public static function fields()
	{
		return array(
			'id' => array('type' => 'int', 'primary' => true, 'serial' => true),
			'email' => array('type' => 'string', 'required' => true, 'unique' => true), // Unique
			'password' => array('type' => 'text', 'required' => true),
			'is_admin' => array('type' => 'boolean', 'default' => false),
			'date_created' => array('type' => 'datetime', 'default' => new \DateTime())
		);
	}
}

