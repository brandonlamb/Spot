<?php

/**
 * Mysql Database Adapter
 *
 * @package Spot\Db\Adapter
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Db\Adapter;

class Mysql extends AbstractAdapter implements AdapterInterface
{
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
     * {@inheritdoc}
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
     * Get/set the database name
     * @param string $database
     * @return string
     */
    public function database($database = null)
    {
        null !== $database && $this->database = (string) $database;
        return $this->database;
    }
}
