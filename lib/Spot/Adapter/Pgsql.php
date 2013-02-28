<?php
namespace Spot\Adapter;

/**
 * Postgresql Database Adapter
 */
class Pgsql extends AbstractAdapter
{
	// Format for date columns, formatted for PHP's date() function
	protected $formatDate = 'Y-m-d';
	protected $formatTime = ' H:i:s';
	protected $formatDatetime = 'Y-m-d H:i:s';

	/**
	 * Escape/quote direct user input
	 *
	 * @param string $string
	 */
	public function escapeField($field)
	{
		return $field === '*' ? $field : '"' . $field . '"';
	}
}
