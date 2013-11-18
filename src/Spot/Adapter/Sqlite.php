<?php

namespace Spot\Adapter;

/**
 * Sqlite Database Adapter
 */
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
