<?php

namespace Spot\Adapter\Statement;

class Db2
{
	/**
	 * Return insert statement
	 * @param string $datasource
	 * @param array $data
	 * @param array $binds
	 * @return string
	 */
	protected function statementInsert($datasource, array $data, array $binds)
	{
		// build the statement
		return "INSERT INTO " . $datasource .
			" (" . implode(', ', array_map(array($this, 'escapeField'), array_keys($data))) . ")" .
			" VALUES (:" . implode(', :', array_keys($binds)) . ")";
	}

	/**
	 * Return fields as a string for a query statement
	 */
	protected function statementFields(array $fields = array())
	{
		$preparedFields = array();
		foreach ($fields as $field) {
			if (stripos($field, ' AS ') !== false || strpos($field, '.') !== false) {
				// Leave calculated fields and SQL fragements alone
				$preparedFields[] = $field;
			} else {
				// Escape field names
				$preparedFields[] = $this->escapeField($field);
			}
		}
		return count($fields) > 0 ? implode(', ', $preparedFields) : '*';
	}

	/**
	 * Add a table join (INNER, LEFT OUTER, RIGHT OUTER, FULL OUTER, CROSS)
	 * array('user.id', '=', 'profile.user_id') will compile to ON `user`.`id` = `profile`.`user_id`
	 *
	 * @param array $joins
	 * @return Query
	 */
	protected function statementJoins(array $joins = array())
	{
		$sqlJoins = array();

		foreach ($joins as $join) {
			$sqlJoins[] = trim($join[2]) . ' JOIN' . ' ' . $join[0] . ' ON (' . trim($join[1]) . ')';
		}

		return join(' ', $sqlJoins);
	}

	/**
	 * Builds an SQL string given conditions
	 * @param array $conditions
	 * @param int $ci
	 */
	protected function statementConditions(array $conditions = array(), $ci = 0)
	{
		if (count($conditions) === 0) { return; }

		$sqlStatement = '(';
		$loopOnce = false;

		// @todo - what is this
		$defaultColOperators = array(0 => '', 1 => '=');

		foreach ($conditions as $condition) {
			if (is_array($condition) && isset($condition['conditions'])) {
				$subConditions = $condition['conditions'];
			} else {
				$subConditions = $conditions;
				$loopOnce = true;
			}

			$sqlWhere = array();
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
					case 'IN':
						$whereClause = $this->escapeField($col) . ' IN (' . join(', ', array_fill(0, count($value), '?')) . ')';
						break;

					// column BETWEEN x AND y
#					case 'BETWEEN':
#						$sqlWhere = $condition['column'] . ' BETWEEN ' . join(' AND ', array_fill(0, count($condition['values']), '?'));
#						break;

					// FULLTEXT search
					// MATCH(col) AGAINST(search)
					case ':fulltext':
						$colParam = preg_replace('/\W+/', '_', $col) . $ci;
						$whereClause = 'MATCH(' . $this->escapeField($col) . ') AGAINST(:' . $colParam . ')';
					break;

					// ALL - Find ALL values in a set - Kind of like IN(), but seeking *all* the values
					case ':all':
						throw new \Spot\Exception("SQL adapters do not currently support the ':all' operator");
					break;

					// Not equal
					case '<>':
					case '!=':
					case ':ne':
					case ':not':
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
					default:
						$operator = '=';
						if (is_array($value)) {
							$operator = 'IN';
						} elseif (is_null($value)) {
							$operator = 'IS NULL';
						}
					break;
				}

				// If WHERE clause not already set by the code above...
				if (is_array($value)) {
					$valueIn = '';
					foreach ($value as $val) {
						$valueIn .= $this->escape($val) . ',';
					}
					$value = '(' . trim($valueIn, ',') . ')';
					$whereClause = $this->escapeField($col) . ' ' . $operator . ' ' . $value;
				} elseif (is_null($value)) {
					$whereClause = $this->escapeField($col) . ' ' . $operator;
				}

				if (empty($whereClause)) {
					// Add to binds array and add to WHERE clause
					$colParam = preg_replace('/\W+/', '_', $col) . $ci;

					// Dont escape calculated/aliased columns
					if (strpos($col, '.') !== false) {
						$sqlWhere[] = $col . ' ' . $operator . ' :' . $colParam . '';
					} else {
						$sqlWhere[] = $this->escapeField($col) . ' ' . $operator . ' :' . $colParam . '';
					}
				} else {
					$sqlWhere[] = $whereClause;
				}

				// Increment ensures column name distinction
				// We need to do this whether it was used or not
				// to maintain compatibility with statementConditions()
				$ci++;
			}
			if ($sqlStatement != '(') {
				$sqlStatement .= ' ' . (isset($condition['setType']) ? $condition['setType'] : 'AND') . ' (';
			}
			$sqlStatement .= implode(' ' . (isset($condition['type']) ? $condition['type'] : 'AND') . ' ', $sqlWhere );
			$sqlStatement .= ')';
			if ($loopOnce) { break; }
		}

		// Ensure we actually had conditions
		if (0 == $ci) {
			$sqlStatement = '';
		}

		return $sqlStatement;
	}

	/**
	 * Returns array of binds to pass to query function
	 * @param array $conditions
	 * @param bool $ci
	 */
	protected function statementBinds(array $conditions = array(), $ci = false)
	{
		if (count($conditions) === 0) { return; }

		$binds = array();
		$loopOnce = false;

		foreach ($conditions as $condition) {
			if (is_array($condition) && isset($condition['conditions'])) {
				$subConditions = $condition['conditions'];
			} else {
				$subConditions = $conditions;
				$loopOnce = true;
			}

			foreach ($subConditions as $column => $value) {
				$bindValue = false;

				// Handle binding depending on type
				if (is_object($value)) {
					if ($value instanceof \DateTime) {
						// @todo Need to take into account column type for date formatting
						$bindValue = (string) $value->format($this->dateTimeFormat());
					} else {
						$bindValue = (string) $value; // Attempt cast of object to string (calls object's __toString method)
					}
				} elseif (is_bool($value)) {
					$bindValue = (int) $value; // Cast boolean to integer (false = 0, true = 1)
				} elseif (!is_array($value)) {
					$bindValue = $value;
				}

				// Bind given value
				if (false !== $bindValue) {
					// Column name with comparison operator
					$colData = explode(' ', $column);
					$operator = '=';
					if (count($colData) > 2) {
						$operator = array_pop($colData);
						$colData = array(implode(' ', $colData), $operator);
					}
					$col = $colData[0];

					if (false !== $ci) {
						$col = $col . $ci;
					}

					$colParam = preg_replace('/\W+/', '_', $col);

					// Add to binds array and add to WHERE clause
					$binds[$colParam] = $bindValue;
				}
				// Increment ensures column name distinction
				// We need to do this whether it was used or not
				// to maintain compatibility with statementConditions()
				$ci++;
			}

			if ($loopOnce) {
				break;
			}
		}
		return $binds;
	}

	/**
	 * Build Limit query from data source using given query object
	 * @param int $limit
	 * @param array $options
	 * @return string
	 */
	protected function statementLimit($limit, array $options = array())
	{
		$limit = (int) $limit;
		return $limit ? 'LIMIT ' . $this->limit : '';
	}

	/**
	 * Build Offset query from data source using given query object
	 * @param int $offset
	 * @param array $options
	 * @return string
	 */
	protected function statementOffset($offset, array $options = array())
	{
		$offset = (int) $offset;
		return $offset ? 'OFFSET ' . $this->offset : '';
	}
}
