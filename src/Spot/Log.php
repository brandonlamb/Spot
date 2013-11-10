<?php

namespace Spot;

/**
 * Logging class for all query activity
 *
 * @package Spot
 * @link http://spot.os.ly
 */
class Log
{
    /**
     * @var int, only store up to this many queries in the log
     */
    protected static $queryLimit = 200;

    /**
     * @var array, the array of stored queries that have ran
     */
    protected static $queries = array();

    /**
     * Add query to log
     * @param \Spot\Adpater\AdapterInterface Instance of adapter used to generate the query
     * @param mixed $query \Spot\QueryInterface run
     * @param mixed $data Data used in query - usually array, but can be scalar or null
     */
    public static function addQuery($adapter, $query, $data = null)
    {
        // Shift element off beginning of array if we're at the query limit
        if (static::queryCount() >= static::queryLimit()) {
            array_shift(static::$queries);
        }

        static::$queries[] = array(
            'adapter' => get_class($adapter),
            'query' => $query,
            'data' => $data
        );
    }

    /**
     * Get full query log
     * @return array Queries that have been executed and all data that has been passed with them
     */
    public static function queries()
    {
        return static::$queries;
    }

    /**
     * Get last query run from log
     * @return array Queries that have been executed and all data that has been passed with them
     */
    public static function lastQuery()
    {
        return end(static::$queries);
    }

    /**
     * Get a count of how many queries have been run
     * @return int Total number of queries that have been run
     */
    public static function queryCount()
    {
        return count(static::$queries);
    }

    /**
     * Get/set query limit
     * A limit should be set by default to prevent query log from consuming and exhausing available memory
     * @return int Query limit
     */
    public static function queryLimit($limit = null)
    {
        null !== $limit && static::$queryLimit = $limit;
        return static::$queryLimit;
    }
}
