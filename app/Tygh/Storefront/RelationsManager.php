<?php
/***************************************************************************
 *                                                                          *
 *   (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev    *
 *                                                                          *
 * This  is  commercial  software,  only  users  who have purchased a valid *
 * license  and  accept  to the terms of the  License Agreement can install *
 * and use this program.                                                    *
 *                                                                          *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT  OF THE SOFTWARE  LICENSE   AGREEMENT  IN  THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

namespace Tygh\Storefront;

use Tygh\Database\Connection;
use Tygh\Exceptions\DeveloperException;

/**
 * Class RelationsManager provides lazy-loading functionality for Storefronts.
 *
 * @package Tygh\Storefront
 */
class RelationsManager
{
    /**
     * @var \Tygh\Database\Connection
     */
    protected $db;

    /**
     * @var array
     */
    protected $schema;

    /**
     * @var callable
     */
    protected $name_resolver;

    /**
     * @var string[]
     */
    protected $names_cache;

    public function __construct(Connection $db, $name_resolver, array $schema = [])
    {
        $this->db = $db;
        $this->name_resolver = $name_resolver;
        $this->schema = $schema;
    }

    /**
     * Gets list of relation names.
     *
     * @return string[]
     */
    public function getRelations()
    {
        return array_keys($this->schema);
    }

    /**
     * Gets related entities value.
     *
     * @param int    $storefront_id
     * @param string $relation_name
     *
     * @return array
     */
    public function getRelationValue($storefront_id, $relation_name)
    {
        $relation_data = $this->getRelationSchema($relation_name);

        switch ($relation_data['type']) {
            case 'object':
                return $this->getRelatedObjects($storefront_id, $relation_data);
            case 'value':
                return $this->getRelatedValues($storefront_id, $relation_data);
            default:
                return [];
        }
    }

    /**
     * Gets list of related objects.
     *
     * @param int   $storefront_id
     * @param array $relation_schema
     *
     * @return array
     */
    protected function getRelatedObjects($storefront_id, array $relation_schema)
    {
        return $this->db->getArray(
            'SELECT ?p FROM ?p WHERE ?f = ?i',
            implode(',', $relation_schema['fields']),
            $relation_schema['table'],
            $relation_schema['storefront_id_field'],
            $storefront_id
        );
    }

    /**
     * Gets list of related values.
     *
     * @param int   $storefront_id
     * @param array $relation_schema
     *
     * @return array
     */
    protected function getRelatedValues($storefront_id, array $relation_schema)
    {
        return $this->db->getColumn(
            'SELECT ?f FROM ?p WHERE ?f = ?i',
            $relation_schema['id_field'],
            $relation_schema['table'],
            $relation_schema['storefront_id_field'],
            $storefront_id
        );
    }

    /**
     * Gets available entities value.
     *
     * @param int    $storefront_id Storefront ID
     * @param string $relation_name Relations name
     *
     * @return array
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     */
    public function getAvailableRelationValue($storefront_id, $relation_name)
    {
        $relation_data = $this->getRelationSchema($relation_name);

        switch ($relation_data['type']) {
            case 'object':
                return $this->getAvailableRelatedObjects($storefront_id, $relation_data);
            case 'value':
                return $this->getAvailableRelatedValues($storefront_id, $relation_data);
            default:
                return [];
        }
    }

    /**
     * Gets list of available objects.
     *
     * @param int   $storefront_id   Storefront ID
     * @param array $relation_schema Relations schema
     *
     * @return array
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    protected function getAvailableRelatedObjects($storefront_id, array $relation_schema)
    {
        $related_list = $this->db->getArray(
            'SELECT ?p FROM ?p',
            implode(',', $relation_schema['related_fields']),
            $relation_schema['related_table']
        );

        $storefronts = $this->db->getArray(
            'SELECT ?p FROM ?p',
            implode(',', [$relation_schema['filter_field'] ,$relation_schema['storefront_id_field']]),
            $relation_schema['table']
        );

        return array_filter(
            $related_list,
            static function ($related) use ($storefronts, $storefront_id, $relation_schema) {
                $is_available = true;

                foreach ($storefronts as $storefront) {
                    if (
                        $storefront[$relation_schema['filter_field']] === $related[$relation_schema['filter_field']]
                        && (int) $storefront[$relation_schema['storefront_id_field']] === $storefront_id
                    ) {
                        return true;
                    }
                    // phpcs:ignore
                    if (
                        $storefront[$relation_schema['filter_field']] === $related[$relation_schema['filter_field']]
                        && (int) $storefront[$relation_schema['storefront_id_field']] !== $storefront_id
                    ) {
                        $is_available = false;
                    }
                }

                return $is_available;
            }
        );
    }

    /**
     * Gets list of available values.
     *
     * @param int   $storefront_id   Storefront ID
     * @param array $relation_schema Relations schema
     *
     * @return array
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    protected function getAvailableRelatedValues($storefront_id, array $relation_schema)
    {
        $related_list = $this->db->getColumn(
            'SELECT ?f FROM ?p',
            $relation_schema['related_id_field'],
            $relation_schema['related_table']
        );

        $storefronts = $this->db->getArray(
            'SELECT ?p FROM ?p',
            implode(',', [$relation_schema['id_field'] ,$relation_schema['storefront_id_field']]),
            $relation_schema['table']
        );

        return array_filter(
            $related_list,
            static function ($related) use ($storefronts, $storefront_id, $relation_schema) {
                $is_available = true;

                foreach ($storefronts as $storefront) {
                    if (
                        $storefront[$relation_schema['id_field']] === $related
                        && (int) $storefront[$relation_schema['storefront_id_field']] === $storefront_id
                    ) {
                        return true;
                    }
                    // phpcs:ignore
                    if (
                        $storefront[$relation_schema['id_field']] === $related
                        && (int) $storefront[$relation_schema['storefront_id_field']] !== $storefront_id
                    ) {
                        $is_available = false;
                    }
                }

                return $is_available;
            }
        );
    }

    /**
     * Updates values of related entities.
     *
     * @param int    $storefront_id
     * @param string $relation_name
     * @param array  $values
     */
    public function updateRelations($storefront_id, $relation_name, array $values)
    {
        $relation_data = $this->getRelationSchema($relation_name);

        $this->deleteRelations($storefront_id, $relation_name);

        if (!$values) {
            return;
        }

        $values = array_map(function ($relation_id) use ($storefront_id, $relation_data) {
            return [
                $relation_data['storefront_id_field'] => $storefront_id,
                $relation_data['id_field']            => $relation_id,
            ];
        },
            $values);

        $this->db->query('INSERT INTO ?p ?m', $relation_data['table'], $values);
    }

    /**
     * Removes related entities.
     *
     * @param int    $storefront_id
     * @param string $relation_name
     */
    public function deleteRelations($storefront_id, $relation_name)
    {
        $relation_data = $this->getRelationSchema($relation_name);

        $this->db->query(
            'DELETE FROM ?p WHERE ?f = ?i',
            $relation_data['table'],
            $relation_data['storefront_id_field'],
            $storefront_id
        );
    }

    /**
     * Gets SQL join to fetch related entities.
     *
     * @param string $relation_name
     *
     * @return string
     */
    public function buildJoin($relation_name)
    {
        $relation_data = $this->getRelationSchema($relation_name);

        return $this->db->quote(
            'LEFT JOIN ?p AS ?f ON storefronts.storefront_id = ?f.?f',
            $relation_data['table'],
            $relation_data['table_alias'],
            $relation_data['table_alias'],
            $relation_data['storefront_id_field']
        );
    }

    /**
     * Gets SQL condition to fetch related entities.
     *
     * @param string $relation_name
     * @param array  $values
     * @param bool   $include_empty
     *
     * @return string
     */
    public function buildCondition($relation_name, array $values, $include_empty = false)
    {
        $relation_data = $this->getRelationSchema($relation_name);

        $all_relations_condition = '';
        if ($include_empty) {
            $all_relations_condition = $this->db->quote(
                'OR ?f.?f IS NULL',
                $relation_data['table_alias'],
                $relation_data['id_field']
            );
        }

        $column = $relation_data['table_alias'] . '.' . $relation_data['id_field'];

        $condition = empty($values) ? '0=1' : $this->db->quote('?p IN (?a)', $column, $values);

        return $this->db->quote(
            'AND (?p ?p)',
            $condition,
            $all_relations_condition
        );
    }

    /**
     * Extracts relation schema.
     *
     * @param string $relation_name
     *
     * @return array
     */
    protected function getRelationSchema($relation_name)
    {
        if (!isset($this->schema[$relation_name])) {
            throw new DeveloperException("`{$relation_name}` is not a valid storefront relation");
        }

        $relation_data = $this->schema[$relation_name];

        return $relation_data;
    }

    /**
     * Resolves a relation name passed in the method name to the relation name used in schema.
     *
     * @param string $src
     *
     * @return string
     */
    public function resolveName($src)
    {
        if (!isset($this->names_cache[$src])) {
            $this->names_cache[$src] = call_user_func($this->name_resolver, $src);
        }

        return $this->names_cache[$src];
    }
}
