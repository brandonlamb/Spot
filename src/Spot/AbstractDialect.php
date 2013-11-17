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
	protected $escapeChar;

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

        $sqlStatement = '(';
        $loopOnce = false;
        foreach ($conditions as $condition) {
            if (isset($condition['conditions'])) {
#            	echo "\nhasSubConditions\n";
                $subConditions = $condition['conditions'];
            } else {
#            	echo "\nnoSubConditions\n";
                $subConditions = $conditions;
                $loopOnce = true;
            }

#print_r($conditions);

#continue;

            $sqlWhere = [];

            foreach ($subConditions as $column => $value) {
                $whereClause = '';

                // Column name with comparison operator
                $colData = explode(' ', $column);
                $operator = isset($colData[1]) ? $colData[1] : '=';
                if (count($colData) > 2) {
                    $operator = array_pop($colData);
                    $colData = array(implode(' ', $colData), $operator);
                }
                $col = $colData[0];


$operator = $this->getOperator($operator);
d($column, $value, $operator, $col);
continue;








                // If WHERE clause not already set by the code above...
                if (is_array($value)) {
#                    $value = '(' . join(', ', array_fill(0, count($value), '?')) . ')'
                    $valueIn = '';
                    foreach ($value as $val) {
                        $valueIn .= $this->quote($val) . ',';
                    }
                    $value = '(' . trim($valueIn, ',') . ')';
                    $whereClause = $this->escapeIdentifier($col) . ' ' . $operator . ' ' . $value;
                } elseif (is_null($value)) {
                    $whereClause = $this->escapeIdentifier($col) . ' ' . $operator;
                }

                if (empty($whereClause)) {
                    // Add to binds array and add to WHERE clause
                    $colParam = preg_replace('/\W+/', '_', $col) . $ci;

                    // Dont escape calculated/aliased columns
                    if (strpos($col, '.') !== false) {
                        $sqlWhere[] = $col . ' ' . $operator . ' :' . $colParam . '';
                    } else {
                        $sqlWhere[] = $this->escapeIdentifier($col) . ' ' . $operator . ' :' . $colParam . '';
                    }
                } else {
                    $sqlWhere[] = $whereClause;
                }

                // Increment ensures column name distinction
                // We need to do this whether it was used or not
                // to maintain compatibility with getConditionsSql()
                $ci++;
            }
            if ($sqlStatement != '(') {
                $sqlStatement .= ' ' . (isset($condition['setType']) ? $condition['setType'] : 'AND') . ' (';
            }
            $sqlStatement .= implode(' ' . (isset($condition['type']) ? $condition['type'] : 'AND') . ' ', $sqlWhere );
            $sqlStatement .= ')';
            if ($loopOnce) { break; }
        }
exit;
        // Ensure we actually had conditions
        if (0 == $ci) {
            $sqlStatement = '';
        }

        return $sqlStatement;
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
                $operator = '<';
                break;

            case '<=':
            case ':lte':
                $operator = '<=';
                break;

            case '>':
            case ':gt':
                $operator = '>';
                break;

            case '>=':
            case ':gte':
                $operator = '>=';
                break;

            // REGEX matching
            case '~=':
            case '=~':
            case ':regex':
                $operator = 'REGEX';
                break;

            // LIKE
            case ':like':
                $operator = 'LIKE';
                break;

            // column IN ()
#                    case ':in':
#                    case 'in':
#                        $whereClause = $this->escapeIdentifier($col) . ' IN (' . join(', ', array_fill(0, count($value), '?')) . ')';
#                        break;

            // column NOT IN ()
#                    case ':notin':
#                    case 'notin':
#                        $whereClause = $this->escapeIdentifier($col) . ' NOT IN (' . join(', ', array_fill(0, count($value), '?')) . ')';
#                        break;

            // column BETWEEN x AND y
#                   case 'BETWEEN':
#                       $sqlWhere = $condition['column'] . ' BETWEEN ' . join(' AND ', array_fill(0, count($condition['values']), '?'));
#                       break;

            // FULLTEXT search
            // MATCH(col) AGAINST(search)
            case ':fulltext':
                $colParam = preg_replace('/\W+/', '_', $col) . $ci;
                $whereClause = 'MATCH(' . $this->escapeIdentifier($col) . ') AGAINST(:' . $colParam . ')';
                break;

            // ALL - Find ALL values in a set - Kind of like IN(), but seeking *all* the values
            case ':all':
                throw new \Spot\Exception\Adapter("SQL adapters do not currently support the ':all' operator");
                break;

            // Not equal
            case '<>':
            case '!=':
            case ':ne':
            case ':not':
            case ':notin':
            case ':isnot':
                $operator = '!=';
                if (is_array($value)) {
                    $operator = 'NOT IN';
                } elseif (is_null($value)) {
                    $operator = 'IS NOT NULL';
                }
                break;

            // Equals
            case '=':
            case ':eq':
            case ':in':
            case ':is':
            default:
                $operator = '=';
                if (is_array($value)) {
                    $operator = 'IN';
                } elseif (is_null($value)) {
                    $operator = 'IS NULL';
                }
        }
	}
}
