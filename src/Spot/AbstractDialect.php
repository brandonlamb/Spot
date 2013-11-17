<?php

/**
 * Spot Dialect
 *
 * This is the base class to each database dialect. This implements
 * common methods to transform intermediate code into its RDBM related syntax
 *
 * @package Spot
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot;

abstract class AbstractDialect
{
	/**
	 * @var \Spot\AdapterInterface
	 */
	protected $adapter;

	/**
	 * @var string
	 */
	protected $escapeChar;

	public function __construct(\Spot\AdapterInterface $adapter)
	{
		$this->setAdapter($adapter);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getAdapter()
	{
		return $this->adapter;
	}

	/**
	 * {@inheritDoc}
	 */
	public function setAdapter(AdapterInterface $adapter)
	{
		$this->adapter = $adapter;
	}

	/**
	 * {@inheritDoc}
	 */
	public function select($sqlQuery, array $fields)
	{
		if (!empty($fields)) {
	        $preparedFields = [];

	        foreach ($fields as $field) {
                $preparedFields[] = $field;
	        }
	        return $sqlQuery . ' SELECT ' . implode(', ', $preparedFields);
		}
		return $sqlQuery . ' SELECT *';
	}

	/**
	 * {@inheritDoc}
	 */
	public function from($sqlQuery, $tableName)
	{
		return $sqlQuery . ' FROM ' . $tableName;
	}

	/**
	 * {@inheritDoc}
	 */
	public function where($sqlQuery, array $conditions)
	{
		if (empty($conditions)) {
			return $sqlQuery;
		}

		$ci = 0;
        $sqlStatement = '(';
        $loopOnce = false;

        foreach ($conditions as $condition) {
            if (isset($condition['conditions'])) {
                $subConditions = $condition['conditions'];
            } else {
                $subConditions = $conditions;
                $loopOnce = true;
            }

            $sqlWhere = [];

            foreach ($subConditions as $column => $value) {
                $whereClause = '';

                // Column name with comparison operator
                $columnData = (strpos($column, ' ') !== false) ? explode(' ', $column) : [$column];
                $operator = isset($columnData[1]) ? $columnData[1] : '=';

                if (count($columnData) > 2) {
                    $operator = array_pop($columnData);
                    $columnData = array(implode(' ', $columnData), $operator);
                }

                $columnName = $columnData[0];
				$operator = $this->getOperator($operator, $value);



                if (is_array($value)) {
#                    $value = '(' . join(', ', array_fill(0, count($value), '?')) . ')'
                    $valueIn = '';
                    foreach ($value as $val) {
                        $valueIn .= $this->adapter->quote($val) . ',';
                    }

                    $sqlWhere[] = "$columnName $operator (" . trim($valueIn, ',') . ')';
                } else if (is_null($value)) {
                    $sqlWhere[] = $columnName . ' ' . $operator;
                } else {
                    // Add to binds array and add to WHERE clause
                    $colParam = preg_replace('/\W+/', '_', $columnName) . $ci;

                    // Dont escape calculated/aliased columns
                    if (strpos($columnName, '.') !== false) {
                        $sqlWhere[] = $columnName . ' ' . $operator . ' :' . $colParam . '';
                    } else {
                        #$sqlWhere[] = $this->escapeIdentifier($columnName) . ' ' . $operator . ' :' . $colParam . '';
                        $sqlWhere[] = $columnName . ' ' . $operator . ' :' . $colParam . '';
                    }
                }

                // Increment ensures column name distinction
                // We need to do this whether it was used or not
                // to maintain compatibility with getConditionsSql()
                $ci++;
            }

            if ($sqlStatement != '(') {
                $sqlStatement .= ' ' . (isset($condition['setType']) ? $condition['setType'] : 'AND') . ' (';
            }
            $sqlStatement .= implode(' ' . (isset($condition['type']) ? $condition['type'] : 'AND') . ' ', $sqlWhere);
            $sqlStatement .= ')';

            if ($loopOnce) {
            	break;
            }
        }

		return (empty($sqlStatement)) ? $sqlQuery : $sqlQuery . ' WHERE ' . $sqlStatement;
	}

	/**
	 * {@inheritDoc}
	 */
	public function group($sqlQuery, array $group)
	{
		if (!empty($group)) {
        	$columns = [];
            foreach ($group as $column) {
                $columns[] = (string) $column;
            }
			return $sqlQuery . ' GROUP BY ' . implode(', ', $columns);
		}
		return $sqlQuery;
	}
    /**
     * {@inheritdoc}
     */
    public function order($sqlQuery, array $order)
    {
        if (!empty($order)) {
        	$columns = [];
            foreach ($order as $column => $sort) {
                $columns[] = (string) $column . ' ' . strtoupper($sort);
            }
        	return $sqlQuery . ' ORDER BY ' . implode(', ', $columns);
        }
        return $sqlQuery;
    }

	/**
	 * {@inheritDoc}
	 */
	public function limit($sqlQuery, $number = null)
	{
		if (is_numeric(number)) {
			return $sqlQuery . ' LIMIT ' . number;
		}
		return $sqlQuery;
	}

	/**
	 * {@inheritDoc}
	 */
	public function offset($sqlQuery, $number = null)
	{
		if (is_numeric(number)) {
			return $sqlQuery . ' OFFSET ' . number;
		}
		return $sqlQuery;
	}

	/**
	 * Parse the operator
	 *
	 * @param string $operator
	 * @param mixed $value
	 * @return string
	 */
	protected function getOperator($operator, $value = null)
	{
		$operator = strtolower($operator);
        // Determine which operator to use based on custom and standard syntax
        switch ($operator) {
            case '<':
            case ':lt':
                return '<';

            case '<=':
            case ':lte':
                return '<=';

            case '>':
            case ':gt':
                return '>';

            case '>=':
            case ':gte':
                return '>=';

            // REGEX matching
            case '~=':
            case '=~':
            case ':regex':
                return 'REGEX';

            // LIKE
            case ':like':
                return 'LIKE';

            // column IN ()
            case 'in':
            case ':in':
#                $whereClause = $this->escapeIdentifier($col) . ' IN (' . join(', ', array_fill(0, count($value), '?')) . ')';
                return 'IN';

            // column NOT IN ()
            case 'not in':
            case ':notin':
#                $whereClause = $this->escapeIdentifier($col) . ' NOT IN (' . join(', ', array_fill(0, count($value), '?')) . ')';
            	return 'NOT IN';

            // column BETWEEN x AND y
           case 'between':
           case ':between':
#               $sqlWhere = $condition['column'] . ' BETWEEN ' . join(' AND ', array_fill(0, count($condition['values']), '?'));
           		return 'BETWEEN';

            // FULLTEXT search
            // MATCH(col) AGAINST(search)
            case 'match':
            case ':fulltext':
#                $colParam = preg_replace('/\W+/', '_', $col) . $ci;
#                $whereClause = 'MATCH(' . $this->escapeIdentifier($col) . ') AGAINST(:' . $colParam . ')';
                return 'MATCH';

            // Not equal
            case '<>':
            case '!=':
            case ':ne':
            case ':not':
            case ':isnot':
                if (is_array($value)) {
                    return 'NOT IN';
                } else if (is_null($value)) {
                    return 'IS NOT NULL';
                } else {
                	return '!=';
                }

            // Equals
            case '=':
            case ':eq':
            case ':is':
            default:
                if (is_array($value)) {
                    return 'IN';
                } else if (is_null($value)) {
                    return 'IS NULL';
                } else {
                	return '=';
                }
        }
	}
}
