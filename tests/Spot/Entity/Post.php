<?php
/**
 * Post
 *
 * @package Spot
 */

namespace Spot\Entity;

use Spot\Entity;

class Post extends Entity
{
	protected static $datasource = 'test_posts';

	public static function fields()
	{
		return array(
			'id' => array('type' => 'int', 'primary' => true, 'serial' => true),
			'title' => array('type' => 'string', 'required' => true),
			'body' => array('type' => 'text', 'required' => true),
			'status' => array('type' => 'int', 'default' => 0, 'index' => true),
			'date_created' => array('type' => 'datetime')
		);
	}

	public static function relations()
	{
		return array(
			// Each post entity 'hasMany' comment entites
			'comments' => array(
				'type' => 'HasMany',
				'entity' => 'Spot\\Entity\\Post\\Comment',
				'where' => array('post_id' => ':entity.id'),
				'order' => array('date_created' => 'ASC')
			)
		);
	}
}
