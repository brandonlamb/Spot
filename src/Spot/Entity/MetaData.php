<?php

/**
 * Spot Entity Meta Data
 *
 * @package \Spot\Entity
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Entity;

use Spot\Column;

class MetaData
{
	/**
	 * @var string
	 */
	private $tableName;

	/**
	 * @var string
	 */
	private $sequenceName;

	/**
	 * @var array
	 */
	private $columns;

	/**
	 * @var array
	 */
	private $columnMap;

	/**
	 * @var array
	 */
	private $relations;

	/**
	 * Constructor
	 * @param array $definition
	 */
	public function __construct(array $definition = [])
	{
		isset($definition['table']) && $this->setTable($definition['table']);
		isset($definition['sequence']) && $this->setSequence($definition['sequence']);
		isset($definition['columns']) && $this->setColumns($definition['columns']);
		isset($definition['relations']) && $this->setRelations($definition['relations']);
	}

	/**
	 * Get table name
	 * @return string
	 */
	public function getTable()
	{
		return (string) $this->tableName;
	}

	/**
	 * Set the table name
	 * @param string $tableName
	 * @return MetaData
	 */
	public function setTable($tableName)
	{
		$this->tableName = (string) $tableName;
		return $this;
	}

	/**
	 * Get sequence name
	 * @return string
	 */
	public function getSequence()
	{
		return (string) $this->sequenceName;
	}

	/**
	 * Set the sequence name
	 * @param string $sequenceName
	 * @return MetaData
	 */
	public function setSequence($sequenceName)
	{
		$this->sequenceName = (string) $sequenceName;
		return $this;
	}

	/**
	 * Get columns
	 * @return array
	 */
	public function getColumns()
	{
		return (array) $this->columns;
	}

	/**
	 * Get a single column
	 * @param string $offset
	 * @return \Spot\Column|bool
	 */
	public function getColumn($offset)
	{
		($alias = $this->getColumnMap($offset)) && $offset = $alias;
		return isset($this->columns[$offset]) ? $this->columns[$offset] : false;
	}

	/**
	 * Set the columns data
	 * @param array
	 * @return MetaData
	 */
	public function setColumns(array $columns)
	{
        if (!is_array($columns)) {
            throw new \InvalidArgumentException("Column definitons must be formatted as an array.");
        }

		!is_array($this->columns) && $this->columns = [];

        foreach ($columns as $column) {
            if (!isset($column[0], $column[1])) {
                continue;
            }
            $column = new Column($column[0], $column[1]);
            $this->columns[$column->getName()] = $column;
            $this->setColumnMap($column->getName(), $column->getAlias());
        }

        return $this;
	}

	/**
	 * Get column if a mapping exists
	 * @return string|bool
	 */
	public function getColumnMap($offset)
	{
		return isset($this->columnMap[$offset]) ? $this->columnMap[$offset] : false;
	}

	/**
	 * Set a column mapping
	 * @param string $offset
	 * @param string $alias
	 * @return MetaData
	 */
	public function setColumnMap($offset, $alias)
	{
    	!empty($alias) && $this->columnMap[(string) $alias] = (string) $offset;
		return $this;
	}

	/**
	 * Remove a column mapping
	 * @param string $offset
	 * @return MetaData
	 */
	public function removeColumnMap($offset)
	{
    	unset($this->columnMap[(string) $offset]);
		return $this;
	}

	/**
	 * Get relations definitions
	 * @return array
	 */
	public function getRelations()
	{
		return (array) $this->relations;
	}

	/**
	 * Set entity relations
	 * @param array $relations
	 * @return MetaData
	 */
	public function setRelations(array $relations)
	{
        if (!is_array($relations)) {
            throw new \InvalidArgumentException("Relation definitons must be formatted as an array.");
        }
		$this->relations = (array) $relations;
		return $this;
	}
}
