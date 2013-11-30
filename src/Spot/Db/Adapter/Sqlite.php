<?php

/**
 * Sqlite Database Adapter
 */

namespace Spot\Db\Adapter;

use Spot\Db\AbstractAdapter,
	Spot\Db\AdapterInterface;

class Sqlite extends AbstractAdapter implements AdapterInterface
{
    /**
     * {@inheritdoc}
     */
    public function escapeField($field)
    {
        return $field === '*' ? $field : '"' . $field . '"';
    }
}
