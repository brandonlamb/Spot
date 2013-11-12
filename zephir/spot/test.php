<?php

class Entity extends Spot\Entity\AbstractEntity
{
	public function getCrap()
	{
		return 'Hello';
	}
}

$entity = new Entity(['name' => 'Brandon']);
#echo $entity->offsetGet('test', 'blah') . "\n";
#var_dump($entity['test']);

#$entity['phone'] = '123';
$entity->offsetSet('test', 'xyz');
$entity->name = 'Bob';
$entity->name = 'Joe';

#print_r($entity);
unset($entity['name']);
print_r($entity);
