<?php

/**
 * Postgresql Database Adapter
 * @package \Spot\Adapter
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Adapter;

use \Spot\Log;

class Pgsql extends AbstractAdapter implements AdapterInterface
{
    /**
     * {@inheritdoc}
     */
    public function escapeField($field)
    {
		if (false !== strpos('.', $field)) {
            return $field === '*' ? $field : '"' . $field . '"';
        } else {
			return $field;
		}
    }

    /**
     * {@inheritdoc}
     */
    public function create($datasource, array $data, array $options = array())
    {
        if ($options['serial'] === true && empty($options['sequence'])) {
            $options['sequence'] = $datasource . '_id_seq';
        }
        return parent::create($datasource, $data, $options);
    }
}
