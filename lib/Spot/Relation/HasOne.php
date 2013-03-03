<?php
namespace Spot\Relation;
/**
 * DataMapper class for 'has one' relations
 *
 * @package Spot
 * @link http://spot.os.ly
 */
class HasOne extends RelationAbstract
{
	/**
	 * Load query object with current relation data
	 *
	 * @return \Spot\Query
	 */
	protected function toQuery()
	{
		return $this->mapper()->all($this->entityName(), $this->conditions())->order($this->relationOrder())->first();
	}

	/**
	 * isset() functionality passthrough to entity
	 * @return bool
	 */
	public function __isset($key)
	{
		$entity = $this->execute();
		return ($entity) ? isset($entity->$key) : false;
	}

	/**
	 * Getter passthrough to entity
	 * @param string $key
	 * @return mixed
	 */
	public function __get($key)
	{
		$entity = $this->execute();
		return ($entity) ? $entity->$key null;
	}

	/**
	 * Setter passthrough to entity
	 * @param string $key
	 * @param mixed $value
	 */
	public function __set($key, $value)
	{
		$entity = $this->execute();
		if ($entity) {
			$entity->$key = $value;
		}
	}
}
