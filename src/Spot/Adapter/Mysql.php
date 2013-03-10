<?php
namespace Spot\Adapter;

/**
 * Mysql Database Adapter
 *
 * @package Spot
 * @link http://spot.os.ly
 */
class Mysql extends PDO_Abstract implements AdapterInterface
{
	// Format for date columns, formatted for PHP's date() function
	protected $formatDate = "Y-m-d";
	protected $formatTime = " H:i:s";
	protected $formatDatetime = "Y-m-d H:i:s";

	// Driver-Specific settings
	protected $engine = 'InnoDB';
	protected $charset = 'utf8';
	protected $collate = 'utf8_unicode_ci';

	/**
	 * Set database engine (InnoDB, MyISAM, etc)
	 */
	public function engine($engine = null)
	{
		if (null !== $engine) {
			$this->engine = $engine;
		}
		return $this->engine;
	}

	/**
	 * Escape/quote direct user input
	 *
	 * @param string $string
	 */
	public function escapeField($field)
	{
		return $field == '*' ? $field : '`' . $field . '`';
	}

	/**
	 * Set character set and MySQL collate string
	 */
	public function characterSet($charset, $collate = 'utf8_unicode_ci')
	{
		$this->charset = $charset;
		$this->collate = $collate;
	}

	/**
	 * Get columns for current table
	 *
	 * @param String $table Table name
	 * @return Array
	 */
	protected function getColumnsForTable($table, $source)
	{
		$tableColumns = array();
		$tblCols = $this->connection()->query("SELECT * FROM information_schema.columns WHERE table_schema = '" . $source . "' AND table_name = '" . $table . "'");

		if ($tblCols) {
			while ($columnData = $tblCols->fetch(\PDO::FETCH_ASSOC)) {
				$tableColumns[$columnData['COLUMN_NAME']] = $columnData;
			}
			return $tableColumns;
		} else {
			return false;
		}
	}

	/**
	 * Ensure migration options are full and have all keys required
	 */
	public function formatMigrateOptions(array $options)
	{
		return $options + array(
			'engine' => $this->engine,
			'charset' => $this->charset,
			'collate' => $this->collate,
		);
	}
}
