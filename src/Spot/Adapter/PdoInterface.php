<?php
namespace Spot\Adapter;

interface PdoInterface
{
	public function beginTransaction();
	public function inTransaction();
	public function commit();
	public function rollback();
	public function errorCode();
	public function errorInfo();
	public function getAttribute($attribute);
	public function setAttribute($attribute, $value);
	public function lastInsertId($name);
	public function prepare($statement, array $options);
	public function exec($statement);
	public function query($statement);
	public function quote($string);
	public static function getAvailableDrivers();
}