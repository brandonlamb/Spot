<?php

/**
 * Postgresql Database Adapter
 * @package \Spot\Adapter
 * @author Brandon Lamb <brandon@brandonlamb.com>
 */

namespace Spot\Adapter;

use Spot\AbstractAdapter,
    Spot\AdapterInterface;

class Pgsql extends AbstractAdapter implements AdapterInterface
{
    protected $type = 'Pgsql';
    protected $dialectType = 'Pgsql';

    /**
     * @{inheritDoc}
     * @todo Not quoting the columns essentially by just returning $field
     */
    public function escapeIdentifier($identifier)
    {
        if (is_array($identifier)) {
            return '"' . $identifier[0] . '"."' . $identifier[1] . '"';
        }

        if (false !== strpos('.', $identifier)) {
            $identifier = ($identifier === '*') ?: '"' . $identifier . '"';
        }

        return $identifier;
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
