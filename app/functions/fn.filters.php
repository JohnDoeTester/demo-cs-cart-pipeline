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

use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\ProductFeatures;
use Tygh\Enum\ProductFilterProductFieldTypes;
use Tygh\Enum\YesNo;
use Tygh\Exceptions\DatabaseException;
use Tygh\Languages\Languages;
use Tygh\Navigation\LastView;
use Tygh\Registry;
use Tygh\Tools\ErrorHandler;
use Tygh\Tools\Math;

defined('BOOTSTRAP') or die('Access denied');

const FILTERS_HASH_SEPARATOR = '_';
const FILTERS_HASH_FEATURE_SEPARATOR = '-';

/**
 * Update or create product filter
 *
 * @param array<string, int|string> $filter_data Filter data
 * @param int                       $filter_id   Filter id
 * @param string                    $lang_code   Language code
 *
 * @psalm-param array{
 *   filter: string,
 *   filter_type: string,
 *   categories_path: string,
 *   company_id?: int,
 * } $filter_data
 *
 * @return int|false
 */
function fn_update_product_filter(array $filter_data, $filter_id, $lang_code = DESCR_SL)
{
    if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id')) {
        if (!empty($filter_id) && !fn_check_company_id('product_filters', 'filter_id', $filter_id)) {
            fn_company_access_denied_notification();

            return false;
        }
        if (!empty($filter_id)) {
            unset($filter_data['company_id']);
        }
    }

    $filter = array();

    if ($filter_id) {
        $filter = db_get_row('SELECT * FROM ?:product_filters WHERE filter_id = ?i', $filter_id);

        if (empty($filter)) {
            return false;
        }
    }

    // Parse filter type
    if (strpos($filter_data['filter_type'], 'FF-') === 0
        || strpos($filter_data['filter_type'], 'RF-') === 0
        || strpos($filter_data['filter_type'], 'DF-') === 0
    ) {
        $filter_data['feature_id'] = str_replace(array('RF-', 'FF-', 'DF-'), '', $filter_data['filter_type']);
        $filter_data['feature_type'] = fn_get_product_feature_type_by_feature_id((int) $filter_data['feature_id']);
        $filter_data['field_type'] = '';
    } else {
        $filter_data['field_type'] = str_replace(array('R-', 'B-'), '', $filter_data['filter_type']);
        $filter_data['feature_id'] = 0;
        $filter_fields = fn_get_product_filter_fields();
    }

    // Check exists filter
    if (empty($filter_id)
        || $filter['field_type'] != $filter_data['field_type']
        || $filter['feature_id'] != $filter_data['feature_id']
    ) {
        $runtime_company_id = Registry::get('runtime.company_id');
        $check_conditions = db_quote(
            'filter_id != ?i AND feature_id = ?i AND field_type = ?s',
            $filter_id,
            $filter_data['feature_id'],
            $filter_data['field_type']
        );

        if (fn_allowed_for('ULTIMATE')) {
            $company_id = isset($filter_data['company_id'])
                ? $filter_data['company_id']
                : Registry::get('runtime.company_id');
            Registry::set('runtime.company_id', $company_id);
            $check_conditions .= fn_get_company_condition('?:product_filters.company_id', true, $company_id);
        }

        $check_result = db_get_field('SELECT filter_id FROM ?:product_filters WHERE ?p', $check_conditions);

        if (fn_allowed_for('ULTIMATE')) {
            Registry::set('runtime.company_id', $runtime_company_id);
        }

        if ($check_result) {
            if (!empty($filter_data['feature_id'])) {
                $feature_name = fn_get_feature_name($filter_data['feature_id']);

                fn_set_notification(
                    NotificationSeverity::ERROR,
                    __('error'),
                    __(
                        'error_filter_by_feature_exists',
                        ['[name]' => $feature_name]
                    )
                );
            } elseif (!empty($filter_fields[$filter_data['field_type']])) {
                $field_name = __($filter_fields[$filter_data['field_type']]['description']);

                fn_set_notification(
                    NotificationSeverity::ERROR,
                    __('error'),
                    __(
                        'error_filter_by_product_field_exists',
                        ['[name]' => $field_name]
                    )
                );
            }

            return false;
        }
    }

    $create = (bool) $filter_id;

    if (!empty($filter_id)) {
        db_query('UPDATE ?:product_filters SET ?u WHERE filter_id = ?i', $filter_data, $filter_id);

        db_query(
            'UPDATE ?:product_filter_descriptions SET ?u WHERE filter_id = ?i AND lang_code = ?s',
            $filter_data,
            $filter_id,
            $lang_code
        );
    } else {
        $filter_data['filter_id'] = $filter_id = db_query('INSERT INTO ?:product_filters ?e', $filter_data);
        foreach (Languages::getAll() as $filter_data['lang_code'] => $_d) {
            db_query('INSERT INTO ?:product_filter_descriptions ?e', $filter_data);
        }
    }

    /**
     * Update product filter post hook
     *
     * @deprecated since v4.12.1. Use "update_product_filter_post" instead.
     *
     * @param array  $filter_data
     * @param int    $filter_id
     * @param string $lang_code
     */
    fn_set_hook('update_product_filter', $filter_data, $filter_id, $lang_code);

    /**
     * Update product filter post hook
     *
     * @param array  $filter_data The resulting filter data after updating
     * @param int    $filter_id   The updated filter od
     * @param string $lang_code   The current language code
     * @param bool   $create      Whether the filter was created or not
     */
    fn_set_hook('update_product_filter_post', $filter_data, $filter_id, $lang_code, $create);

    return $filter_id;
}

/**
 * Gets product filter name
 *
 * @param array $filter_id Filter identifier
 * @param string $lang_code 2 letters language code
 * @return string|bool Filter name on success, false otherwise
 */
function fn_get_product_filter_name($filter_id, $lang_code = CART_LANGUAGE)
{
    if (!empty($filter_id)) {
        if (is_array($filter_id)) {
            return db_get_hash_single_array("SELECT filter_id, filter FROM ?:product_filter_descriptions WHERE filter_id IN (?n) AND lang_code = ?s", array('filter_id', 'filter'), $filter_id, $lang_code);
        } else {
            return db_get_field("SELECT filter FROM ?:product_filter_descriptions WHERE filter_id = ?i AND lang_code = ?s", $filter_id, $lang_code);
        }
    }

    return false;
}

/**
 * Gets product filters by search params
 *
 * @param array $params Products filter search params
 * @param int $items_per_page Items per page
 * @param string $lang_code 2-letter language code (e.g. 'en', 'ru', etc.)
 * @return array Product filters
 */
function fn_get_product_filters($params = array(), $items_per_page = 0, $lang_code = DESCR_SL)
{
    /**
     * Changes product filters search params
     *
     * @param array  $params         Products filter search params
     * @param int    $items_per_page Items per page
     * @param string $lang_code      2-letter language code (e.g. 'en', 'ru', etc.)
     */
    fn_set_hook('get_product_filters_pre', $params, $items_per_page, $lang_code);

    // Init filter
    $params = LastView::instance()->update('product_filters', $params);

    // Set default values to input params
    $default_params = array (
        'page' => 1,
        'items_per_page' => $items_per_page
    );

    $params = array_merge($default_params, $params);

    $condition = $group = '';

    if (!empty($params['item_ids'])) {
        $params['filter_id'] = is_array($params['item_ids']) ? $params['item_ids'] : fn_explode(',', $params['item_ids']);
    }

    if (!empty($params['filter_id'])) {
        $condition .= db_quote(" AND ?:product_filters.filter_id IN (?n)", (array) $params['filter_id']);
    }

    if (!empty($params['field_type'])) {
        $condition .= db_quote(" AND ?:product_filters.field_type IN (?a)", (array) $params['field_type']);
    }

    if (isset($params['filter_name']) && fn_string_not_empty($params['filter_name'])) {
        $condition .= db_quote(" AND ?:product_filter_descriptions.filter LIKE ?l", "%".trim($params['filter_name'])."%");
    }

    if (!empty($params['status'])) {
        $condition .= db_quote(" AND ?:product_filters.status = ?s", $params['status']);
    }

    if (!empty($params['feature_type'])) {
        $condition .= db_quote(" AND ?:product_features.feature_type IN (?a)", $params['feature_type']);
    }

    if (isset($params['feature_name']) && fn_string_not_empty($params['feature_name'])) {
        $condition .= db_quote(' AND ?:product_features_descriptions.internal_name LIKE ?l', '%' . trim($params['feature_name']) . '%');
    }

    if (isset($params['feature_id'])) {
        $condition .= db_quote(' AND ?:product_features.feature_id IN (?n)', (array) $params['feature_id']);
    }

    if (!empty($params['category_ids'])) {
        $c_ids = is_array($params['category_ids']) ? $params['category_ids'] : fn_explode(',', $params['category_ids']);
        $find_set = array(
            " ?:product_filters.categories_path = '' "
        );
        foreach ($c_ids as $k => $v) {
            $find_set[] = db_quote(" FIND_IN_SET(?i, ?:product_filters.categories_path) ", $v);
        }
        $find_in_set = db_quote(" AND (?p)", implode('OR', $find_set));
        $condition .= $find_in_set;
    }

    if (fn_allowed_for('ULTIMATE')) {
        $condition .= fn_get_company_condition('?:product_filters.company_id');
    }

    $limit = '';
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:product_filters LEFT JOIN ?:product_filter_descriptions ON ?:product_filter_descriptions.lang_code = ?s AND ?:product_filter_descriptions.filter_id = ?:product_filters.filter_id LEFT JOIN ?:product_features_descriptions ON ?:product_features_descriptions.feature_id = ?:product_filters.feature_id AND ?:product_features_descriptions.lang_code = ?s LEFT JOIN ?:product_features ON ?:product_features.feature_id = ?:product_filters.feature_id WHERE 1 ?p", $lang_code, $lang_code, $condition);
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $fields = "";
    if (!empty($params['short'])) {
        $fields .= db_quote("?:product_filters.filter_id, ?:product_filters.feature_id, ?:product_filters.field_type, ?:product_filters.status, ");
        if (fn_allowed_for('ULTIMATE')) {
            $fields .= db_quote("?:product_filters.company_id, ");
        }
    } else {
        $fields .= db_quote("?:product_filters.*, ?:product_features_descriptions.internal_name as feature, ");
    }

    $fields .= db_quote("?:product_filter_descriptions.filter, ?:product_features.feature_type, ?:product_features.parent_id, ?:product_features_descriptions.prefix, ?:product_features_descriptions.suffix");
    $join = db_quote("LEFT JOIN ?:product_filter_descriptions ON ?:product_filter_descriptions.lang_code = ?s AND ?:product_filter_descriptions.filter_id = ?:product_filters.filter_id LEFT JOIN ?:product_features_descriptions ON ?:product_features_descriptions.feature_id = ?:product_filters.feature_id AND ?:product_features_descriptions.lang_code = ?s LEFT JOIN ?:product_features ON ?:product_features.feature_id = ?:product_filters.feature_id", $lang_code, $lang_code);
    $sorting = db_quote("?:product_filters.position, ?:product_filter_descriptions.filter");
    $group_by = db_quote("GROUP BY ?:product_filters.filter_id");

    /**
     * Changes SQL parameters for product filters select
     *
     * @param string $fields    String of comma-separated SQL fields to be selected in an SQL-query
     * @param string $join      String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $condition String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string $group_by  String containing the SQL-query GROUP BY field
     * @param string $sorting   String containing the SQL-query ORDER BY clause
     * @param string $limit     String containing the SQL-query LIMIT clause
     * @param array  $params    Products filter search params
     * @param string $lang_code 2-letter language code (e.g. 'en', 'ru', etc.)
     */
    fn_set_hook('get_product_filters_before_select', $fields, $join, $condition, $group_by, $sorting, $limit, $params, $lang_code);

    $filters = db_get_hash_array("SELECT $fields FROM ?:product_filters $join WHERE 1 ?p $group_by ORDER BY $sorting $limit", 'filter_id', $condition);

    if (!empty($filters)) {
        $fields = fn_get_product_filter_fields();

        // Get feature group if exist
        $parent_ids = array();
        foreach ($filters as $k => $v) {
            if (!empty($v['parent_id'])) {
                $parent_ids[] = $v['parent_id'];
            }
        }
        $groups = db_get_hash_array("SELECT feature_id, internal_name FROM ?:product_features_descriptions WHERE feature_id IN (?n) AND lang_code = ?s", 'feature_id', $parent_ids, $lang_code);

        foreach ($filters as $k => $filter) {

            if (!empty($filter['parent_id']) && !empty($groups[$filter['parent_id']])) {
                $filters[$k]['feature_group'] = $groups[$filter['parent_id']]['internal_name'];
            }

            if (isset($fields[$filter['field_type']]['description'])) {
                $filters[$k]['feature'] = __($fields[$filter['field_type']]['description']);
            }
            if (empty($filter['feature_id']) && isset($fields[$filter['field_type']]['condition_type'])) {
                $filters[$k]['condition_type'] = $fields[$filter['field_type']]['condition_type'];
            }

            if (!empty($params['get_descriptions'])) {
                $d = array();
                $filters[$k]['filter_description'] = __('filter_by') . ': <span>' . $filters[$k]['feature'] . (!empty($filters[$k]['feature_group']) ? ' (' . $filters[$k]['feature_group'] . ' )' : '') . '</span>';
                $d = fn_array_merge($d, fn_get_categories_list($filter['categories_path'], $lang_code), false);
                $filters[$k]['filter_description'] .= ' | ' . __('display_on') . ': <span>' . implode(', ', $d) . '</span>';
            }

            if ($filter['feature_type'] != ProductFeatures::NUMBER_SELECTBOX) {
                $_ids[$filter['filter_id']] = $filter['feature_id'];
            }
        }

        if (!empty($params['get_variants']) && !empty($_ids)) {

            list($variants) = fn_get_product_feature_variants(array(
                'feature_id' => array_values($_ids)
            ));

            $_ids_revert = array();
            foreach ($_ids as $filter_id => $feature_id) {
                if (!empty($feature_id)) {
                    $_ids_revert[$feature_id][] = $filter_id;
                }
            }

            foreach ($variants as $variant_id => $variant) {
                if (!empty($_ids_revert[$variant['feature_id']])) {
                    foreach ($_ids_revert[$variant['feature_id']] as $filter_id) {
                        if (!empty($params['short'])) {
                            $filters[$filter_id]['variants'][$variant_id] = array('variant_id' => $variant['variant_id'], 'variant' => $variant['variant']);
                        } else {
                            $filters[$filter_id]['variants'][$variant_id] = $variant;
                        }
                    }
                }
                unset($variants[$variant_id]);
            }

            unset($variants);
        }

        if (!empty($params['get_product_features']) && !empty($_ids)) {

            $variants_ids_to_load = [];
            if (isset($params['variants_only'])) {
                foreach ($params['variants_only'] as $filter_id => $feature_variants) {
                    if (!empty($_ids[$filter_id])) {
                        $variants_ids_to_load[$_ids[$filter_id]] = $feature_variants;
                    }
                }
            }

            $features_params = [
                'variants'      => true,
                'plain'         => true,
                'feature_id'    => array_values($_ids),
                'variants_only' => !empty($variants_ids_to_load) ? (array) $variants_ids_to_load : null
            ];

            list($features) = fn_get_product_features($features_params);

            foreach ($_ids as $filter_id => $feature_id) {
                if (!empty($features[$feature_id]['use_variant_picker'])) {
                    $filters[$filter_id]['use_variant_picker'] = true;
                }
                if (!empty($features[$feature_id]['variants'])) {
                    foreach ($features[$feature_id]['variants'] as $variant_id => $variant) {
                        if (!empty($params['short'])) {
                            $filters[$filter_id]['variants'][$variant_id] = ['variant_id' => $variant_id, 'variant' => $variant['variant']];
                        } else {
                            $filters[$filter_id]['variants'][$variant_id] = $variant;
                        }
                    }
                }
            }
        }
    }

    /**
     * Changes product filters data
     *
     * @param array  $filters   Product filters
     * @param array  $params    Products filter search params
     * @param string $lang_code 2-letter language code (e.g. 'en', 'ru', etc.)
     */
    fn_set_hook('get_product_filters_post', $filters, $params, $lang_code);

    return array($filters, $params);
}

function fn_delete_product_filter($filter_id)
{
    /**
     * Adds additional actions before deleting product filter
     *
     * @param int $filter_id Filter identifier
     */
    fn_set_hook('delete_product_filter_pre', $filter_id);

    db_query("DELETE FROM ?:product_filters WHERE filter_id = ?i", $filter_id);
    db_query("DELETE FROM ?:product_filter_descriptions WHERE filter_id = ?i", $filter_id);

    /**
     * Adds additional actions after deleting product filter
     *
     * @param int $filter_id Filter identifier
     */
    fn_set_hook('delete_product_filter_post', $filter_id);

    return true;
}

/**
 * Filters: gets available filters according to current products set
 *
 * @param array  $params    request params
 * @param string $lang_code language code
 *
 * @return array available filters list
 * @deprecated
 * @see fn_product_filters_get_filters_products_count()
 */
function fn_get_filters_products_count($params = array(), $lang_code = CART_LANGUAGE)
{
    return fn_product_filters_get_filters_products_count($params, $lang_code);
}

/**
 * Filters: removes variant or filter from selected filters list
 *
 * @param string  $features_hash selected filters list
 * @param integer $filter_id     filter ID
 * @param mixed   $variant       filter variant
 *
 * @return string updated filters list
 */
function fn_delete_filter_from_hash($features_hash, $filter_id, $variant = '')
{
    $filters = fn_parse_filters_hash($features_hash);

    if (!empty($filters[$filter_id])) {
        if (!empty($variant) && in_array($variant, $filters[$filter_id])) {
            $values = array_flip($filters[$filter_id]);
            unset($values[$variant]);
            if (!empty($values)) {
                $filters[$filter_id] = array_keys($values);
            } else {
                unset($filters[$filter_id]);
            }
        } elseif (empty($variant)) {
            unset($filters[$filter_id]);
        }
    }

    return fn_generate_filter_hash($filters);
}

/**
 * Filters: adds variant to selected filters list
 *
 * @param string $features_hash selected filters list
 * @param integer $filter_id filter ID
 * @param mixed $variant filter variant
 * @return string updated filters list
 */
function fn_add_filter_to_hash($features_hash, $filter_id, $variant = '')
{
    $filters = fn_parse_filters_hash($features_hash);

    if (!isset($filters[$filter_id]) || !in_array($variant, $filters[$filter_id])) {
        $filters[$filter_id][] = $variant;
    }

    return fn_generate_filter_hash($filters);
}

/**
 * Filters: generates filter hash
 * @param array $filters selected filters list
 * @return string filter hash
 */
function fn_generate_filter_hash($filters)
{
    $res = array();
    foreach ($filters as $filter_id => $variants) {
        if (is_array($variants)) {
            $res[] = $filter_id . FILTERS_HASH_FEATURE_SEPARATOR . implode(FILTERS_HASH_FEATURE_SEPARATOR, $variants);
        } else {
            $res[] = $filter_id . FILTERS_HASH_FEATURE_SEPARATOR . $variants;
        }
    }

    return implode(FILTERS_HASH_SEPARATOR, $res);
}

/**
 * Filters: parses selected filters list
 * @param string $features_hash selected filters list
 * @return array parsed filters list
 */
function fn_parse_filters_hash($features_hash = '')
{
    $result = array();

    if (!empty($features_hash)) {
        $values = explode(FILTERS_HASH_SEPARATOR, $features_hash);
        foreach ($values as $value) {
            $variants = explode(FILTERS_HASH_FEATURE_SEPARATOR, $value);
            $filter_id = array_shift($variants);
            $result[$filter_id] = $variants;
        }
    }

    return $result;
}

/**
 * Filters: splits selected filter/feature variants by type
 *
 * @param array   $items          filters or features list
 * @param array   $selected_items selected filter or feature variants
 * @param boolean $key_is_feature use filter_id or feature_id as array key
 *
 * @return array selected filter/feature variants, split by type
 */
function fn_split_selected_feature_variants($items, $selected_items, $key_is_feature = true)
{
    $variant_features = array();
    $value_features = array();
    $valueint_features = array();
    $key = $key_is_feature ? 'feature_id' : 'filter_id';

    foreach ($items as $item) {
        $id = !empty($item['filter_id']) ? $item['filter_id'] : $item['feature_id'];

        if (!empty($item['feature_id']) && isset($selected_items[$id])) {
            if (in_array($item['feature_type'], array(ProductFeatures::TEXT_SELECTBOX, ProductFeatures::MULTIPLE_CHECKBOX, ProductFeatures::EXTENDED))) {
                $variant_features[$item[$key]] = $selected_items[$id];

            } elseif (in_array($item['feature_type'], array(ProductFeatures::SINGLE_CHECKBOX, ProductFeatures::TEXT_FIELD))) {
                if (!empty($selected_items[$id][0])) {
                    $value_features[$item[$key]] = $selected_items[$id][0];
                }
            } elseif (in_array($item['feature_type'], array(ProductFeatures::NUMBER_SELECTBOX, ProductFeatures::NUMBER_FIELD, ProductFeatures::DATE))) {

                $min = 0;
                $max = 0;
                if (isset($selected_items[$id][0])) {
                    if ($item['feature_type'] == ProductFeatures::DATE) {
                        $selected_items[$id][0] = fn_parse_date($selected_items[$id][0]);
                    } elseif (isset($item['round_to'])) {
                        $selected_items[$id][0] = Math::floorToPrecision($selected_items[$id][0], $item['round_to']);
                    }
                    $min = $selected_items[$id][0];
                }
                if (isset($selected_items[$id][1])) {
                    if ($item['feature_type'] == ProductFeatures::DATE) {
                        $selected_items[$id][1] = fn_parse_date($selected_items[$id][1]);
                    } elseif (isset($item['round_to'])) {
                        $selected_items[$id][1] = Math::ceilToPrecision($selected_items[$id][1], $item['round_to']);
                    }
                    $max = $selected_items[$id][1];
                }

                if (!empty($min) || !empty($max)) {
                    $valueint_features[$item[$key]] = array($min, $max);
                }
            }
        }
    }

    return array($variant_features, $value_features, $valueint_features);
}

/**
 * Filters: generates conditions to search products by selected filter/feature variant
 * @param array $items filters or features list
 * @param array $selected_items selected filter or feature variants
 * @param string $join "join" conditions
 * @param string $condition "where" conditions
 * @param string $lang_code language code
 * @param array $params additional params
 * @return array "join" and "where" conditions
 */
function fn_generate_feature_conditions($items, $selected_items, $join, $condition, $lang_code, $params = array())
{
    list($variant_features, $value_features, $valueint_features) = fn_split_selected_feature_variants($items, $selected_items);

    // find selected variants for features with variants
    if (!empty($variant_features)) {

        $conditions = [];
        $_join = '';

        foreach ($variant_features as $fid => $variants) {
            $_join .= db_quote(" LEFT JOIN ?:product_features_values as var_val_$fid ON var_val_$fid.product_id = products.product_id AND var_val_$fid.lang_code = ?s AND var_val_$fid.feature_id = ?i", $lang_code, $fid);
            $conditions[$fid] = db_quote("var_val_$fid.variant_id IN (?n)", $variants);
        }

        /**
         * Executes right before the concatenation of the original query and the query with variants of the features, allowing you to modify the filter SQL-query.
         *
         * @param array  $items             Filters or features list
         * @param array  $selected_items    Selected filter or feature variants
         * @param string $join              Original "join" conditions
         * @param string $condition         Original "where" conditions
         * @param string $lang_code         The 2-letter language code (e.g. 'en', 'ru', etc.)
         * @param array  $params            Additional params
         * @param array  $variant_features  Selected variants features
         * @param array  $value_features    Selected values features
         * @param array  $valueint_features Selected integer values features
         * @param string $_join             "Join" conditions generated by the variants of the features
         * @param array  $conditions        "Where" conditions generated by the variants of the features
         */
        fn_set_hook(
            'generate_feature_conditions_with_variants',
            $items,
            $selected_items,
            $join,
            $condition,
            $lang_code,
            $params,
            $variant_features,
            $value_features,
            $valueint_features,
            $_join,
            $conditions
        );

        $join .= $_join;

        // This is used to get all available filter variants for current conditions (magic becomes here :))
        if (!empty($params['split_filters']) && sizeof($variant_features) > 1) {

            // This condition gets available variants for all not selected filters
            $combined_conditions = array(
                '(' . implode(' AND ', $conditions) . db_quote(' AND ?:product_features_values.feature_id NOT IN (?n))', array_keys($conditions))
            );

            foreach ($variant_features as $fid => $variants) {
                $tmp = $conditions;
                unset($tmp[$fid]);
                // This condition gets available variants for certain filter with ID == $fid
                $combined_conditions[] = '(' . implode(' AND ', $tmp) . db_quote(' AND ?:product_features_values.feature_id = ?i)', $fid);
            }
            $condition .= ' AND (' . implode(' OR ', $combined_conditions) . ')';
        } else {
            if (!empty($params['variant_filter']) && sizeof($variant_features) == 1) {
                $feature_ids = array_keys($variant_features);
                $fid = reset($feature_ids);
                $condition .= ' AND (' . implode(' AND ', $conditions) . db_quote(' OR ?:product_features_values.feature_id = ?i', $fid) . ')';
            } else {
                $condition .= ' AND (' . implode(' AND ', $conditions) . ')';
            }
        }
    }

    // find selected variants for features with custom values
    if (!empty($valueint_features)) {
        foreach ($valueint_features as $fid => $ranges) {
            $join .= db_quote(" LEFT JOIN ?:product_features_values as var_val_$fid ON var_val_$fid.product_id = products.product_id AND var_val_$fid.lang_code = ?s AND var_val_$fid.feature_id = ?i", $lang_code, $fid);
            $condition .= db_quote(" AND (var_val_$fid.value_int >= ?d AND var_val_$fid.value_int <= ?d AND var_val_$fid.value = '')", $ranges[0], $ranges[1]);
        }
    }

    // find selected variants for checkbox and text features
    if (!empty($value_features)) {
        foreach ($value_features as $fid => $value) {
            $join .= db_quote(" LEFT JOIN ?:product_features_values as ch_features_$fid ON ch_features_$fid.product_id = products.product_id AND ch_features_$fid.lang_code = ?s AND ch_features_$fid.feature_id = ?i", $lang_code, $fid);
            $condition .= db_quote(" AND ch_features_$fid.value = ?s", $value);
        }
    }

    return array($join, $condition);
}

/**
 * Filters: generates search params to search products by product fields
 * @param array $params request params
 * @param array $filters filters list
 * @param array $selected_filters selected filter variants
 * @return array search params
 */
function fn_generate_filter_field_params($params, $filters, $selected_filters)
{
    $filter_fields = fn_get_product_filter_fields();

    foreach ($filters as $filter) {
        if (!empty($filter['field_type'])) {
            $structure = $filter_fields[$filter['field_type']];

            if ($structure['condition_type'] == 'F') {
                if (!empty($selected_filters[$filter['filter_id']])) {
                    $params['filter_params'][$structure['db_field']] = $selected_filters[$filter['filter_id']];
                }

            } elseif ($structure['condition_type'] == 'C') {
                if (!empty($selected_filters[$filter['filter_id']][0])) {
                    foreach ($structure['map'] as $_param => $_value) {
                        $params[$_param] = $_value;
                    }
                }
            } elseif ($structure['condition_type'] == 'D') {

                $min = 0;
                $max = 0;
                $extra = '';
                if (isset($selected_filters[$filter['filter_id']][0])) {
                    if (isset($filter['round_to'])) {
                        $min = Math::floorToPrecision($selected_filters[$filter['filter_id']][0], $filter['round_to']);
                    } else {
                        $min = intval($selected_filters[$filter['filter_id']][0]);
                    }
                }
                if (isset($selected_filters[$filter['filter_id']][1])) {
                    if (isset($filter['round_to'])) {
                        $max = Math::floorToPrecision($selected_filters[$filter['filter_id']][1], $filter['round_to']);
                    } else {
                        $max = intval($selected_filters[$filter['filter_id']][1]);
                    }
                }
                if (isset($selected_filters[$filter['filter_id']][2])) {
                    $extra = $selected_filters[$filter['filter_id']][2];
                }

                if (!empty($structure['convert'])) {
                    list($min, $max) = $structure['convert']($min, $max, $extra);
                }

                $params[$structure['db_field'] . '_from'] = $min;
                $params[$structure['db_field'] . '_to'] = $max;
            }

            /**
             * This hook allows to extend products filtering params
             * @param array $params           request params
             * @param array $filters          filters list
             * @param array $selected_filters selected filter variants
             * @param array $filter_fields    filter by product's field type of filter schema
             * @param array $filter           current filter's data
             * @param array $structure        current filter's schema
             */
            fn_set_hook('generate_filter_field_params', $params, $filters, $selected_filters, $filter_fields, $filter, $structure);
        }
    }

    return $params;
}

/**
 * Filters: gets all available filter variants
 *
 * @param array  $params           request params
 * @param array  $filters          filters list
 * @param array  $selected_filters selected filter variants
 * @param string $area             current working area
 * @param string $lang_code        language code
 *
 * @return array available filter variants, filter range values, product field variants and product field range values
 * @deprecated
 * @see fn_product_filters_get_current_filters();
 */
function fn_get_current_filters($params, $filters, $selected_filters, $area = AREA, $lang_code = CART_LANGUAGE)
{
    return fn_product_filters_get_current_filters($params, $filters, $selected_filters, $area, $lang_code);
}

/**
 * Filters: corrects min/max and left/right values for range filter
 *
 * @param array $range_values     range filter values
 * @param array $filters          filters list
 * @param array $selected_filters selected filter variants
 *
 * @return array corrected values
 */
function fn_filter_process_ranges($range_values, $filters, $selected_filters)
{
    if (!empty($range_values)) {
        $fields = fn_get_product_filter_fields();

        foreach ($range_values as $filter_id => $values) {
            if (!empty($values)) {

                if (!empty($values['field_type'])) { // standard field
                    $structure = $fields[$values['field_type']];
                    if (!empty($structure['convert'])) {
                        list($values['min'], $values['max']) = $structure['convert']($values['min'], $values['max']);
                    }
                    $values['extra'] = !empty($structure['extra']) ? $structure['extra'] : '';
                }

                // Counting min and max with more accuracy than required by round_to
                // Needs for check to disabling slider.
                $max = Math::floorToPrecision($values['max'], $filters[$filter_id]['round_to'] * 0.1);
                $min = Math::floorToPrecision($values['min'], $filters[$filter_id]['round_to'] * 0.1);

                $values['slider'] = true;
                $values['disable'] = round(abs($max - $min), 2) < $filters[$filter_id]['round_to'];
                $values['min'] = Math::floorToPrecision($values['min'], $filters[$filter_id]['round_to']);
                $values['max'] = Math::ceilToPrecision($values['max'], $filters[$filter_id]['round_to']);

                if (!empty($selected_filters[$filter_id])) {
                    $slider_vals = $selected_filters[$filter_id];

                    // convert to base values
                    if (!empty($values['field_type']) && !empty($structure['convert'])) {
                        list($slider_vals[0], $slider_vals[1]) = $structure['convert']($slider_vals[0], $slider_vals[1], $slider_vals[2]);
                    }
                    // zeke: TODO - do not convert twice
                    // convert back to current values
                    if (!empty($values['field_type']) && !empty($structure['convert'])) {
                        list($slider_vals[0], $slider_vals[1]) = $structure['convert']($slider_vals[0], $slider_vals[1]);
                    }

                    $values['current_left'] = $values['left'] = $slider_vals[0];
                    $values['current_right'] = $values['right'] = $slider_vals[1];

                    if ($values['left'] < $values['min']) {
                        $values['left'] = $values['min'];
                    }
                    if ($values['left'] > $values['max']) {
                        $values['left'] = $values['max'];
                    }
                    if ($values['right'] > $values['max']) {
                        $values['right'] = $values['max'];
                    }
                    if ($values['right'] < $values['min']) {
                        $values['right'] = $values['min'];
                    }
                    if ($values['right'] < $values['left']) {
                        $tmp = $values['right'];
                        $values['right'] = $values['left'];
                        $values['left'] = $tmp;
                    }

                    $values['left'] = Math::floorToPrecision($values['left'], $filters[$filter_id]['round_to']);
                    $values['right'] = Math::ceilToPrecision($values['right'], $filters[$filter_id]['round_to']);
                }

                $range_values[$filter_id] = $values;
            }
        }
    }

    return $range_values;
}

/**
 * Filters: gets list of product fields available for filtering
 * @return array filter product fields list
 */
function fn_get_product_filter_fields()
{
    $filters = array (
        // price filter
        ProductFilterProductFieldTypes::PRICE => array (
            'db_field' => 'price',
            'table' => 'product_prices',
            'description' => 'price',
            'condition_type' => 'D',
            'slider' => true,
            'convert' => function($min, $max, $extra = '') {

                if (!empty($extra) && $extra != CART_PRIMARY_CURRENCY && Registry::get('currencies.' . $extra)) {
                    $currency = Registry::get('currencies.' . $extra);

                    $min = round(floatval($min) * floatval($currency['coefficient']), $currency['decimals']);
                    $max = round(floatval($max) * floatval($currency['coefficient']), $currency['decimals']);
                } elseif (empty($extra) && CART_PRIMARY_CURRENCY != CART_SECONDARY_CURRENCY) {
                    $currency = Registry::get('currencies.' . CART_SECONDARY_CURRENCY);

                    $min = round(floatval($min) / floatval($currency['coefficient']), $currency['decimals']);
                    $max = round(floatval($max) / floatval($currency['coefficient']), $currency['decimals']);
                }

                return array($min, $max);
            },
            'conditions' => function($db_field, $join, $condition) {

                $join .= db_quote("
                    LEFT JOIN ?:product_prices as prices_2 ON ?:product_prices.product_id = prices_2.product_id AND ?:product_prices.price > prices_2.price AND prices_2.lower_limit = 1 AND prices_2.usergroup_id IN (?n)",
                    array_merge(array(USERGROUP_ALL), Tygh::$app['session']['auth']['usergroup_ids'])
                );

                $condition .= db_quote("
                    AND ?:product_prices.lower_limit = 1 AND ?:product_prices.usergroup_id IN (?n) AND prices_2.price IS NULL",
                    array_merge(array(USERGROUP_ALL), Tygh::$app['session']['auth']['usergroup_ids'])
                );

                if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id')) {
                    $db_field = "IF(shared_prices.product_id IS NOT NULL, shared_prices.price, ?:product_prices.price)";
                    $join .= db_quote(" LEFT JOIN ?:ult_product_prices AS shared_prices ON shared_prices.product_id = products.product_id"
                        . " AND shared_prices.lower_limit = 1"
                        . " AND shared_prices.usergroup_id IN (?n)"
                        . " AND shared_prices.company_id = ?i",
                        array_merge(array(USERGROUP_ALL), Tygh::$app['session']['auth']['usergroup_ids']),
                        Registry::get('runtime.company_id')
                    );
                }

                return array($db_field, $join, $condition);
            },
            'extra' => CART_SECONDARY_CURRENCY,
            'prefix' => (Registry::get('currencies.' . CART_SECONDARY_CURRENCY . '.after') == 'Y' ? '' : Registry::get('currencies.' . CART_SECONDARY_CURRENCY . '.symbol')),
            'suffix' => (Registry::get('currencies.' . CART_SECONDARY_CURRENCY . '.after') != 'Y' ? '' : Registry::get('currencies.' . CART_SECONDARY_CURRENCY . '.symbol'))
        ),
        // amount filter
        ProductFilterProductFieldTypes::IN_STOCK => array (
            'db_field' => 'amount',
            'table' => 'products',
            'description' => 'in_stock',
            'condition_type' => 'C',
            'map' => array(
                'amount_from' => 1,
            )
        ),
        // filter by free shipping
        ProductFilterProductFieldTypes::FREE_SHIPPING => array (
            'db_field' => 'free_shipping',
            'table' => 'products',
            'description' => 'free_shipping',
            'condition_type' => 'C',
            'map' => array(
                'free_shipping' => 'Y',
            )
        )
    );

    /**
     * Changes product filter fields data
     *
     * @param array $filters Product filter fields
     */
    fn_set_hook('get_product_filter_fields', $filters);

    return $filters;
}

/**
 * Filters: displays notifications when products were not found using current filters combination
 *
 * @deprecated since 4.12.1, will be removed in 4.13.1
 */
function fn_filters_not_found_notification()
{
    Tygh::$app['view']->assign('product_info', __('text_no_products_found'));
    fn_set_notification('I', __('notice'), Tygh::$app['view']->fetch('views/products/components/notification.tpl'));

    Tygh::$app['ajax']->assign('no_products', true);
}

/**
 * Handles products search result
 *
 * @param array $params     Request params
 * @param array $products   List of product
 * @param array $search     Search result params
 */
function fn_filters_handle_search_result(array $params, array $products, array $search)
{
    if (!defined('AJAX_REQUEST') || !isset($params['features_hash'])) {
        return;
    }

    /** @var \Tygh\Ajax $ajax */
    $ajax = Tygh::$app['ajax'];

    if (empty($products)) {
        $ajax->assign('no_products', true);
        $ajax->assign('products_count', 0);
        $ajax->assign('products_found_message', __('n_products_found', [0]));
    } else {
        $total_items_count = isset($search['total_items']) ? (int) $search['total_items'] : count($products);

        $ajax->assign('products_count', $total_items_count);
        $ajax->assign('products_found_message', __('n_products_found', [$total_items_count]));
    }
}

/**
 * Checks whether given filter appears as a numeric slider.
 *
 * @param array $filter_data Filter data returned by fn_get_product_filters() function
 *
 * @return bool Whether given filter accepts ranged numeric values.
 */
function fn_get_filter_is_numeric_slider($filter_data)
{
    $is_ranged = false;

    if (!empty($filter_data['field_type'])) {
        $filter_fields = fn_get_product_filter_fields();
        if (isset($filter_fields[$filter_data['field_type']])) {
            $is_ranged = !empty($filter_fields[$filter_data['field_type']]['slider']);
        }
    } elseif (!empty($filter_data['feature_type'])) {
        $is_ranged = in_array(
            $filter_data['feature_type'],
            array(ProductFeatures::NUMBER_FIELD, ProductFeatures::NUMBER_SELECTBOX)
        );
    }

    return $is_ranged;
}

/**
 * Sets the disabled status for filters related with product feature.
 *
 * @param int $product_feature_id Product feature identifier
 * @return boolean
 */
function fn_disable_product_feature_filters($product_feature_id)
{
    $filter_ids = db_get_fields("SELECT filter_id FROM ?:product_filters WHERE feature_id = ?i AND status = 'A'", $product_feature_id);

    if (!empty($filter_ids)) {
        db_query("UPDATE ?:product_filters SET status = 'D' WHERE filter_id IN (?n)", $filter_ids);
        $filter_names_array = db_get_fields("SELECT filter FROM ?:product_filter_descriptions WHERE filter_id IN (?n) AND lang_code = ?s", $filter_ids, DESCR_SL);

        fn_set_notification('W', __('warning'), __('text_product_filters_were_disabled', array(
            '[url]' => fn_url('product_filters.manage'),
            '[filters_list]' => implode(', ', $filter_names_array)
        )));

        return true;
    }

    return false;
}

/**
 * Gets feature type to feature id map
 *
 * @param array<int, int> $feature_ids
 *
 * @return array
 * @internal
 */
function fn_product_filters_get_feature_type_map(array $feature_ids)
{
    $features = db_get_array(
        'SELECT feature_id, feature_type FROM ?:product_features'
        . ' WHERE feature_id IN (?n)',
        $feature_ids
    );

    $feature_type_map = [
        'selectable' => [],
        'numerical'  => [],
        'checkbox'   => [],
    ];

    foreach ($features as $feature) {
        if (in_array($feature['feature_type'], [ProductFeatures::TEXT_SELECTBOX, ProductFeatures::MULTIPLE_CHECKBOX, ProductFeatures::EXTENDED])) {
            $feature_type_map['selectable'][] = $feature['feature_id'];
        } elseif (in_array($feature['feature_type'], [ProductFeatures::NUMBER_SELECTBOX, ProductFeatures::NUMBER_FIELD, ProductFeatures::DATE])) {
            $feature_type_map['numerical'][] = $feature['feature_id'];
        } elseif (in_array($feature['feature_type'], [ProductFeatures::SINGLE_CHECKBOX])) {
            $feature_type_map['checkbox'][] = $feature['feature_id'];
        }
    }

    return $feature_type_map;
}

/**
 * Gets product conditions for filters by params
 *
 * @param array  $params              Params
 * @param string $lang_code           Language code
 * @param bool   $use_temporary_table Whether to create temporary table
 *
 * @return array [$table_name, $join, $where, $is_temporary_table]
 * @internal
 */
function fn_product_filters_get_products_conditions(array $params, $lang_code, $use_temporary_table = true)
{
    list(, $join, $where) = fn_get_products($params, 0, $lang_code);
    $base_join = $join;
    $base_where = $where;

    if ($use_temporary_table && Registry::ifGet('config.tweaks.allow_product_filters_to_create_temporary_table', true) === true) {
        try {
            $table_name = fn_product_filters_create_temporary_products_table($join, $where);
            $join = $where = '';
            $is_temporary_table = true;
        } catch (DatabaseException $database_exception) {
            ErrorHandler::logException($database_exception);

            $table_name = db_quote('?:products');
            $is_temporary_table = false;
        }
    } else {
        $table_name = db_quote('?:products');
        $is_temporary_table = false;
    }

    return [$table_name, $join, $where, $is_temporary_table, $base_join, $base_where];
}

/**
 * Gets products temporary table name
 *
 * @param int $table_index Temporary table index
 *
 * @return string
 * @internal
 */
function fn_product_filters_get_products_temporary_table_name($table_index)
{
    return sprintf('_product_filters_products_%s', $table_index);
}

/**
 * Creates products temporary table
 *
 * @param string $join
 * @param string $where
 *
 * @return string
 * @internal
 */
function fn_product_filters_create_temporary_products_table($join, $where)
{
    $table_data_hash = md5($join . $where);
    $registry_key = sprintf('runtime.product_filters.temporary_tables.%s', $table_data_hash);

    if (Registry::ifGet($registry_key, false)) {
        return Registry::get($registry_key);
    }

    $table_index = ((int) Registry::ifGet('runtime.product_filters.temporary_table_last_index', 0)) + 1;
    $table_name = fn_product_filters_get_products_temporary_table_name($table_index);

    db_query(
        'CREATE TEMPORARY TABLE ?p'
        . ' (PRIMARY KEY product_id (product_id))'
        . ' ENGINE = MEMORY'
        . ' IGNORE'
        . ' SELECT products.product_id FROM ?:products AS products ?p WHERE 1=1 ?p'
        . ' ORDER BY NULL',
        $table_name,
        $join,
        $where
    );

    Registry::set($registry_key, $table_name);
    Registry::set('runtime.product_filters.temporary_table_last_index', $table_index);

    return $table_name;
}

/**
 * Drops created products temporary tables
 * @internal
 */
function fn_product_filters_drop_temporary_products_tables()
{
    $last_table_index = (int) Registry::ifGet('runtime.product_filters.temporary_table_last_index', 0);

    if (!$last_table_index) {
        return;
    }

    for ($i = 1; $i <= $last_table_index; $i++) {
        try {
            db_query(
                'DROP TEMPORARY TABLE ?p',
                fn_product_filters_get_products_temporary_table_name($i)
            );
        } catch (DatabaseException $database_exception) {
            ErrorHandler::logException($database_exception);
        }
    }

    Registry::set('runtime.product_filters.temporary_table_last_index', 0);
    Registry::del('runtime.product_filters.temporary_tables');
}

/**
 * Gets available product filters with available variants/values according to current products set.
 *
 * @param array  $params    Request params
 * @param string $lang_code Language code
 *
 * @return array
 */
function fn_product_filters_get_filters_products_count(array $params = [], $lang_code = CART_LANGUAGE)
{
    static $inner_cache = [];

    $inner_cache_key = md5(serialize(array_filter(array_merge($params, ['block_data' => null])))) . '_' . $lang_code;

    if (isset($inner_cache[$inner_cache_key])) {
        return $inner_cache[$inner_cache_key];
    }

    $cache_params = [
        'category_id',
        'company_id',
        'dispatch',
        'search_performed',
        'q',
        'filter_id',
        'item_ids',
        'variant_id',
        'cid',
        'subcats',
        'pid',
        'order_ids',
        'block_data.storefront_id',
        'match',
        'storefront_id'
    ];

    $cache_tables = [
        'products',
        'product_descriptions',
        'product_features',
        'product_filters',
        'product_features_values',
        'products_categories',
        'categories',
        'product_filter_descriptions',
        'product_features_descriptions',
        'product_feature_variants',
        'product_feature_variant_descriptions',
        'ult_objects_sharing' // FIXME: this should not be here
    ];

    if (fn_allowed_for('MULTIVENDOR')) {
        $cache_tables[] = 'companies';
        $cache_tables[] = 'storefronts_companies';
    }

    if (!isset($params['block_data']['storefont_id'])) {
        $storefront = Tygh::$app['storefront'];
        $params['storefront_id'] = $storefront->storefront_id;
    }

    /**
     * Change parameters for getting product filters count
     *
     * @param array $params       Products filter search params
     * @param array $cache_params Parameters that affect the cache
     * @param array $cache_tables Tables that affect cache
     */
    fn_set_hook('get_filters_products_count_pre', $params, $cache_params, $cache_tables);

    $key = [];

    foreach ($cache_params as $prop) {
        $prop_value = fn_dot_syntax_get($prop, $params);
        if ($prop_value !== null) {
            $key[] = serialize($prop_value);
        }
    }

    $params = fn_product_filters_validate_filter_params($params);

    if (!fn_product_filters_validate_location($params)) {
        return [[]];
    }

    $filters = fn_product_filters_get_filters($params, $lang_code);

    if (empty($filters)) {
        return [[]];
    }

    $key = 'product_filters_products_count_' . md5(implode('|', $key));

    //TODO Cache level not optimal
    Registry::registerCache(['product_filters', $key], $cache_tables, Registry::cacheLevel('user'));

    if (Registry::isExist($key)) {
        list($variant_values, $range_values, $field_variant_values, $field_range_values) = Registry::get($key);
    } else {
        list($variant_values, $range_values, $field_variant_values, $field_range_values) = fn_product_filters_get_current_filters($params, $filters, [], AREA, $lang_code);
        Registry::set($key, [$variant_values, $range_values, $field_variant_values, $field_range_values]);
    }

    $selected_filters = [];
    $range_values = fn_filter_process_ranges($range_values, $filters, $selected_filters);
    $field_range_values = fn_filter_process_ranges($field_range_values, $filters, $selected_filters);
    $merged = fn_array_merge($variant_values, $range_values, $field_variant_values, $field_range_values);
    $available_variants = $merged;

    if (!empty($params['features_hash']) && empty($params['skip_advanced_variants'])) {
        $selected_filters = fn_parse_filters_hash($params['features_hash']);
    }

    if ($selected_filters) {
        list($variant_filter_ids, $value_features, $valueint_features) = fn_split_selected_feature_variants(
            $filters,
            $selected_filters,
            false
        );

        $key .= md5(json_encode($variant_filter_ids));
        $exists_cache = false;
        $should_be_cached = empty($value_features)
            && empty($valueint_features)
            && ((count($variant_filter_ids) === 1 && count(reset($variant_filter_ids)) === 1));

        $available_variants = $available_ranges = $available_field_values = $available_field_ranges = [];

        if ($should_be_cached) {
            Registry::registerCache(['product_filters', $key], $cache_tables, Registry::cacheLevel('user'));

            if (Registry::isExist($key)) {
                $exists_cache = true;
                list($available_variants, $available_ranges, $available_field_values, $available_field_ranges) = Registry::get($key);
            }
        }

        if (!$exists_cache) {
            $_params = $params;

            if ($selected_filters) {
                // Get available variants for current selection
                $_params['split_filters'] = true;
            }

            list($available_variants, $available_ranges, $available_field_values, $available_field_ranges) = fn_product_filters_get_current_filters($_params, $filters, $selected_filters, AREA, $lang_code);

            if ($should_be_cached) {
                Registry::set($key, [$available_variants, $available_ranges, $available_field_values, $available_field_ranges]);
            }
        }

        if (count($variant_filter_ids) == 1 && count($selected_filters) == 1) {
            $filter_id = key($variant_filter_ids);
            $available_variants[$filter_id] = $variant_values[$filter_id];
        }

        $available_ranges = fn_filter_process_ranges($available_ranges, $filters, $selected_filters);
        $available_field_ranges = fn_filter_process_ranges($available_field_ranges, $filters, $selected_filters);

        $available_variants = fn_array_merge($available_variants, $available_ranges, $available_field_values, $available_field_ranges);
        $merged = fn_array_merge($merged, $available_variants);
    }

    foreach ($filters as $filter_id => $filter) {
        if (
            empty($merged[$filter_id])
            || (
                !empty($filter['feature_type'])
                && empty($available_variants[$filter_id])
                && empty($filter['show_empty_filter'])
                && !in_array($filter_id, array_keys($selected_filters))
            )
        ) {
            unset($filters[$filter_id]);
            continue;
        }

        $filters[$filter_id] = fn_array_merge($filters[$filter_id], $merged[$filter_id]);

        if (!empty($filters[$filter_id]['variants'])) {
            // Select variants
            if (!empty($selected_filters[$filter_id])) {
                foreach ($selected_filters[$filter_id] as $variant_id) {
                    if (!empty($filters[$filter_id]['variants'][$variant_id])) {
                        $filters[$filter_id]['variants'][$variant_id]['selected'] = true;
                        $filters[$filter_id]['selected_variants'][$variant_id] = $filters[$filter_id]['variants'][$variant_id];
                    }
                }
            }

            // If we selected any variants in filter, disabled unavailable variants
            foreach (array_keys($filters[$filter_id]['variants']) as $variant_id) {
                if (
                    !empty($available_variants)
                    && isset($available_variants[$filter_id])
                    && (
                        empty($available_variants)
                        || empty($available_variants[$filter_id])
                        || !empty($available_variants[$filter_id]['variants'][$variant_id])
                    )
                ) {
                    continue;
                }

                $filters[$filter_id]['variants'][$variant_id]['disabled'] = true;
            }
        }

        // If range is selected, mark this filter
        if (!empty($filters[$filter_id]['slider']) && !empty($selected_filters[$filter_id])) {
            if (!empty($filters[$filter_id]['slider']['left']) || !empty($filters[$filter_id]['right'])) {
                $filters[$filter_id]['selected_range'] = true;
            }
        }
    }

    /**
     * Modifies filters
     *
     * @param array  $params            Parameters of filters selection
     * @param string $lang_code         Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param array  $filters           Filters array
     * @param array  $selected_filters  Selected filters array
     */
    fn_set_hook('get_filters_products_count_post', $params, $lang_code, $filters, $selected_filters);

    $inner_cache[$inner_cache_key] = [$filters];

    return [$filters];
}

/**
 * Validates filter patams
 *
 * @param array $params
 *
 * @return array
 * @inhernal
 */
function fn_product_filters_validate_filter_params(array $params = [])
{
    if (!empty($params['item_ids'])) {
        $params['filter_item_ids'] = $params['item_ids'];
        unset($params['item_ids']); // unset item_ids because $params array is passed to fn_get_products, etc later
    }

    return $params;
}

/**
 * Validates current location
 *
 * @param array $params
 *
 * @return bool
 * @inhernal
 */
function fn_product_filters_validate_location(array $params = [])
{
    if (!empty($params['check_location'])) { // FIXME: this is bad style, should be refactored
        $valid_locations = [
            'categories.view',
            'product_features.view',
            'companies.products',
            'products.search'
        ];

        if (!in_array($params['dispatch'], $valid_locations)) {
            return false;
        }
    }

    return true;
}

/**
 * Gets filters
 *
 * @param array  $params
 * @param string $lang_code
 *
 * @return array
 * @internal
 */
function fn_product_filters_get_filters(array $params = [], $lang_code = CART_LANGUAGE)
{
    $cache_params = [
        'category_id',
        'filter_id',
        'filter_item_ids',
        'variant_id',
    ];

    $key = [$lang_code];

    foreach ($cache_params as $prop) {
        if (isset($params[$prop])) {
            $key[] = serialize($params[$prop]);
        }
    }

    $cache_tables = [
        'categories',
        'product_filters',
        'product_features_descriptions',
        'ult_objects_sharing'
    ];

    $key = 'product_filters_filters_' . md5(implode('|', $key));

    Registry::registerCache(['product_filters_filters', $key], $cache_tables, Registry::cacheLevel('static'));

    if (Registry::isExist($key)) {
        return Registry::get($key);
    }

    $condition = $join = '';

    if (!empty($params['category_id'])) {
        if (Registry::get('settings.General.show_products_from_subcategories') === YesNo::YES) {
            $id_path = db_get_field('SELECT id_path FROM ?:categories WHERE category_id = ?i', $params['category_id']);
            $category_ids = db_get_fields('SELECT category_id FROM ?:categories WHERE id_path LIKE ?l', $id_path . '/%');
        } else {
            $category_ids = [];
        }
        $category_ids[] = $params['category_id'];

        $condition .= db_quote(
            " AND (?:product_filters.categories_path = '' OR FIND_IN_SET(?i, ?:product_filters.categories_path))",
            $params['category_id']
        );
    }

    if (!empty($params['filter_id'])) {
        $condition .= db_quote(' AND ?:product_filters.filter_id = ?i', $params['filter_id']);
    }

    if (!empty($params['filter_item_ids'])) {
        $condition .= db_quote(' AND ?:product_filters.filter_id IN (?n)', explode(',', $params['filter_item_ids']));
    }

    if (!empty($params['variant_id'])) {
        $exclude_feature_id = db_get_field('SELECT feature_id FROM ?:product_features_values WHERE variant_id = ?i', $params['variant_id']);
        $condition .= db_quote(" AND ?:product_filters.feature_id NOT IN (?n)", $exclude_feature_id);
    }

    if (fn_allowed_for('ULTIMATE')) {
        $condition .= fn_get_company_condition('?:product_filters.company_id');
    }

    $sf_fields = db_quote(implode(', ', [
        '?:product_filters.feature_id',
        '?:product_filters.filter_id',
        '?:product_filters.field_type',
        '?:product_filters.round_to',
        '?:product_filters.display',
        '?:product_filters.display_count',
        '?:product_filter_descriptions.filter',
        '?:product_features.feature_type',
        '?:product_features.filter_style',
        '?:product_features_descriptions.prefix',
        '?:product_features_descriptions.suffix'
    ]));

    $sf_join =  db_quote(
        ' LEFT JOIN ?:product_filter_descriptions ON ?:product_filter_descriptions.filter_id = ?:product_filters.filter_id AND ?:product_filter_descriptions.lang_code = ?s'
        . ' LEFT JOIN ?:product_features ON ?:product_features.feature_id = ?:product_filters.feature_id'
        . ' LEFT JOIN ?:product_features_descriptions ON ?:product_features_descriptions.feature_id = ?:product_filters.feature_id AND ?:product_features_descriptions.lang_code = ?s',
        $lang_code,
        $lang_code
    );

    $sf_sorting = db_quote('?:product_filters.position, ?:product_filter_descriptions.filter');

    /**
     * Change SQL parameters before select product filters
     *
     * @param array  $sf_fields  String of comma-separated SQL fields to be selected in an SQL-query
     * @param string $sf_join    String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $condition  String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string $sf_sorting String containing the SQL-query ORDER BY clause
     * @param array  $params     Products filter search params
     */
    fn_set_hook('get_filters_products_count_before_select_filters', $sf_fields, $sf_join, $condition, $sf_sorting, $params);

    $filters = db_get_hash_array(
        "SELECT ?p FROM ?:product_filters ?p WHERE ?:product_filters.status = ?s ?p ORDER BY ?p",
        'filter_id',
        $sf_fields,
        $sf_join,
        ObjectStatuses::ACTIVE,
        $condition,
        $sf_sorting
    );

    Registry::set($key, $filters);

    return $filters;
}

/**
 * Gets all available filters variants/values by params
 *
 * @param array  $params           Request params
 * @param array  $filters          Filters list
 * @param array  $selected_filters Selected filter variants
 * @param string $area             Current working area
 * @param string $lang_code        Language code
 *
 * @return array Available filter variants, filter range values, product field variants and product field range values
 */
function fn_product_filters_get_current_filters(array $params, array $filters, array $selected_filters, $area = AREA, $lang_code = CART_LANGUAGE)
{
    $variant_values = [];
    $range_values = [];
    $field_variant_values = [];
    $field_range_values = [];

    $filter_ids = $feature_ids = [];
    $standard_fields = [];
    $features_filter_map = [];

    $params['variant_filter'] = false;

    foreach ($filters as $filter) {
        $filter_ids[] = $filter['filter_id'];

        if (!empty($filter['feature_id'])) {
            $feature_ids[] = $filter['feature_id'];
            $features_filter_map[$filter['feature_id']][$filter['filter_id']] = $filter['filter_id'];
        } elseif (!empty($filter['field_type'])) {
            $standard_fields[$filter['filter_id']] = $filter;
        }

        if (
            !empty($selected_filters[$filter['filter_id']])
            && in_array($filter['feature_type'], [
                ProductFeatures::TEXT_SELECTBOX,
                ProductFeatures::NUMBER_SELECTBOX,
                ProductFeatures::EXTENDED,
                ProductFeatures::MULTIPLE_CHECKBOX
            ])
        ) {
            $params['variant_filter'] = true;
        }
    }

    $_params = $params;
    $_params['features_hash'] = '';
    $_params['get_conditions'] = true;
    $_params['custom_extend'] = ['categories'];

    if (!empty($params['category_id'])) {
        $_params['cid'] = $params['category_id'];

        if (!isset($_params['subcats'])) {
            $_params['subcats'] = Registry::get('settings.General.show_products_from_subcategories') === YesNo::YES ? YesNo::YES : '';
        } elseif ($_params['subcats'] !== YesNo::YES) {
            $_params['subcats'] = '';
        }
    }

    if (!empty($params['pid'])) {
        $_params['pid'] = $params['pid'];
    }

    if (!empty($params['order_ids'])) {
        $_params['order_ids'] = $params['order_ids'];
    }

    if (!empty($_params['split_filters'])) {
        list($variant_features, $value_features, $valueint_features) = fn_split_selected_feature_variants($filters, $selected_filters, false);
    } else {
        $variant_features = $value_features = $valueint_features = [];
    }

    if (!empty($feature_ids)) {
        $feature_type_map = fn_product_filters_get_feature_type_map($feature_ids);
        // swap array
        $converted = [];

        if (!empty($_params['split_filters'])) {
            $other_filters = array_diff_key(
                $selected_filters,
                fn_array_merge($variant_features, $value_features, $valueint_features)
            );

            if (!empty($other_filters)) {
                $_params['features_hash'] = fn_generate_filter_hash($other_filters);
            }
        }

        list($products_table_name, $products_table_joins, $products_table_conditions) = fn_product_filters_get_products_conditions($_params, $lang_code);

        if (!empty($feature_type_map['selectable'])) {
            $where = $join = '';
            $selected_filters_variants = $selected_filters;

            if (!empty($_params['split_filters'])) {
                list($join, $where) = fn_generate_feature_conditions($filters, fn_array_merge($value_features, $valueint_features), $join, $where, $lang_code);
                $selected_filters_variants = $variant_features;
            }

            list($join, $where) = fn_generate_feature_conditions($filters, $selected_filters_variants, $join, $where, $lang_code, $params);

            // Get all available variants
            $variant_values = db_get_hash_single_array(
                'SELECT ?:product_features_values.feature_id, ?:product_features_values.variant_id'
                . ' FROM ?:product_features_values'
                . ' INNER JOIN ?p AS products ON products.product_id = ?:product_features_values.product_id'
                . ' ?p ?p'
                . ' WHERE ?:product_features_values.feature_id IN (?n) AND ?:product_features_values.lang_code = ?s ?p ?p'
                . ' GROUP BY ?:product_features_values.variant_id ORDER BY NULL',
                ['variant_id', 'feature_id'],
                $products_table_name,
                $join,
                $products_table_joins,
                $feature_type_map['selectable'],
                $lang_code,
                $where,
                $products_table_conditions
            );

            // Get descriptions and position
            if (!empty($variant_values)) {
                $variant_descriptions = db_get_hash_array(
                    'SELECT ?:product_feature_variants.variant_id, ?:product_feature_variants.position, ?:product_feature_variants.color, ?:product_feature_variant_descriptions.variant'
                    . ' FROM ?:product_feature_variants '
                    . ' LEFT JOIN ?:product_feature_variant_descriptions ON ?:product_feature_variants.variant_id = ?:product_feature_variant_descriptions.variant_id AND ?:product_feature_variant_descriptions.lang_code = ?s'
                    . ' WHERE ?:product_feature_variants.variant_id IN (?n) '
                    . ' ORDER BY ?:product_feature_variants.position ASC, ?:product_feature_variant_descriptions.variant ASC',
                    'variant_id',
                    $lang_code,
                    array_keys($variant_values)
                );

                foreach ($variant_descriptions as $variant_id => $variant_data) {
                    $feature_id = $variant_values[$variant_id];

                    if (empty($features_filter_map[$feature_id])) {
                        continue;
                    }

                    foreach ($features_filter_map[$feature_id] as $feature_filter_id) {
                        $converted[$feature_filter_id]['variants'][$variant_id] = [
                            'variant_id' => $variant_id,
                            'variant'    => $variant_data['variant'],
                            'position'   => $variant_data['position'],
                            'color'      => $variant_data['color'],
                        ];
                    }
                }
            }
        }


        if (!empty($feature_type_map['checkbox'])) {
            $where = $join = '';

            if (!empty($_params['split_filters'])) {
                list($join, $where) = fn_generate_feature_conditions($filters, fn_array_merge($variant_features, $valueint_features), $join, $where, $lang_code);
            }

            // Get all available variants
            $checkbox_values = db_get_fields(
                'SELECT ?:product_features_values.feature_id'
                . ' FROM ?:product_features_values'
                . ' INNER JOIN ?p AS products ON products.product_id = ?:product_features_values.product_id'
                . ' ?p ?p'
                . ' WHERE ?:product_features_values.feature_id IN (?n) AND ?:product_features_values.lang_code = ?s'
                . ' AND ?:product_features_values.value = ?s ?p ?p'
                . ' GROUP BY ?:product_features_values.feature_id ORDER BY NULL',
                $products_table_name,
                $join,
                $products_table_joins,
                $feature_type_map['checkbox'],
                $lang_code,
                YesNo::YES,
                $where,
                $products_table_conditions
            );

            if (!empty($checkbox_values)) {
                foreach ($checkbox_values as $feature_id) {
                    if (empty($features_filter_map[$feature_id])) {
                        continue;
                    }

                    foreach ($features_filter_map[$feature_id] as $feature_filter_id) {
                        $converted[$feature_filter_id]['variants'][YesNo::YES] = [
                            'variant_id' => YesNo::YES,
                            'variant'    => __('yes')
                        ];
                    }
                }
            }
        }

        if (!empty($feature_type_map['numerical'])) {
            $where = $join = '';
            $range_values = [];

            if (!empty($_params['split_filters'])) {
                list($join, $where) = fn_generate_feature_conditions($filters, fn_array_merge($variant_features, $value_features), $join, $where, $lang_code);
            }

            // Get all available variants
            $feature_range_values = db_get_hash_array(
                'SELECT ?:product_features_values.feature_id, MIN(?:product_features_values.value_int) as min, MAX(?:product_features_values.value_int) as max'
                . ' FROM ?:product_features_values'
                . ' INNER JOIN ?p AS products ON products.product_id = ?:product_features_values.product_id'
                . ' ?p ?p'
                . ' WHERE ?:product_features_values.feature_id IN (?n) AND ?:product_features_values.lang_code = ?s ?p ?p'
                . ' GROUP BY ?:product_features_values.feature_id ORDER BY NULL',
                'feature_id',
                $products_table_name,
                $join,
                $products_table_joins,
                $feature_type_map['numerical'],
                $lang_code,
                $where,
                $products_table_conditions
            );

            foreach ($feature_range_values as $feature_id => $range_value) {
                if (empty($features_filter_map[$feature_id])) {
                    continue;
                }

                foreach ($features_filter_map[$feature_id] as $feature_filter_id) {
                    $range_values[$feature_filter_id] = [
                        'min' => $range_value['min'],
                        'max' => $range_value['max'],
                    ];
                }
            }
        } else {
            $range_values = [];
        }

        $variant_values = $converted;
    }

    // Get range limits for standard fields
    if (!empty($standard_fields)) {
        $_params['features_hash'] = '';
        if (!empty($_params['split_filters'])) {
            $_params['features_hash'] = fn_generate_filter_hash(fn_array_merge($variant_features, $value_features, $valueint_features));
        }

        list(
            $products_table_name,
            $products_table_joins,
            $products_table_conditions,
            $is_products_table_temporary,
            $products_table_base_joins,
            $products_table_base_conditions
        ) = fn_product_filters_get_products_conditions($_params, $lang_code);

        $fields = fn_get_product_filter_fields();

        // Moves standard fields with condition type "Checkbox" to the end of the array
        uasort($standard_fields, static function ($a) use ($fields) {
            $condition = $fields[$a['field_type']]['condition_type'];
            return $condition === 'C' ? 1 : -1;
        });

        $is_exist_products = null;
        $is_exist_standard_fields_except_checkbox = false;

        foreach ($standard_fields as $filter_id => $filter) {
            $structure = $fields[$filter['field_type']];
            $fields_join = $fields_where = $table_alias = '';

            if ($structure['table'] == 'products') {
                $table_alias = ' as products ';
                $db_field = "products.{$structure['db_field']}";
            } else {
                $db_field = "?:{$structure['table']}.{$structure['db_field']}";
                $fields_join .= " LEFT JOIN ?:products as products ON products.product_id = ?:{$structure['table']}.product_id";
            }

            if (!empty($structure['conditions']) && is_callable($structure['conditions'])) {
                list($db_field, $fields_join, $fields_where) = $structure['conditions']($db_field, $fields_join, $fields_where);
            }

            if ($structure['condition_type'] !== 'F' && $is_products_table_temporary) {
                $fields_join .= db_quote(
                    ' INNER JOIN ?p ON products.product_id = ?p.product_id',
                    $products_table_name,
                    $products_table_name
                );
            }

            if ($structure['condition_type'] !== 'C') {
                $is_exist_standard_fields_except_checkbox = true;
            }

            if ($structure['condition_type'] === 'C') {
                if ($is_exist_products === null) {
                    $is_exist_products =
                        !empty($variant_values)
                        || !empty($range_values)
                        || !empty($field_variant_values)
                        || !empty($field_range_values);

                    if (!$is_exist_products && !$is_exist_standard_fields_except_checkbox) {
                        $is_exist_products = (bool) db_get_field(
                            'SELECT COUNT(*) as cnt'
                            . " FROM ?:{$structure['table']} {$table_alias} ?p ?p"
                            . ' WHERE 1=1 ?p ?p',
                            $fields_join,
                            $products_table_base_joins,
                            $fields_where,
                            $products_table_base_conditions
                        );
                    }
                }

                if ($is_exist_products) {
                    $field_variant_values[$filter_id] = [
                        'variants' => [
                            YesNo::YES => [
                                'variant_id' => YesNo::YES,
                                'variant' => __($structure['description'])
                            ]
                        ]
                    ];
                }
            } elseif ($structure['condition_type'] === 'D') { // Dynamic ranges (price, etc)
                $range = db_get_row(
                    "SELECT MIN({$db_field}) as min, MAX({$db_field}) as max FROM ?:{$structure['table']} {$table_alias}"
                    . ' ?p ?p WHERE products.status IN (?s) ?p ?p',
                    $fields_join,
                    $products_table_joins,
                    ObjectStatuses::ACTIVE,
                    $fields_where,
                    $products_table_conditions
                );

                if (!fn_is_empty($range)) {
                    $range['field_type'] = $filter['field_type'];
                    $field_range_values[$filter_id] = $range;
                }
                // Variants (vendors, etc)
            } elseif ($structure['condition_type'] === 'F') {
                $result = $field_variant_values[$filter_id]['variants'] = db_get_hash_array(
                    "SELECT {$db_field} as variant_id, {$structure['variant_name_field']} as variant"
                    . " FROM ?:{$structure['table']} {$table_alias} ?p ?p"
                    . ' WHERE 1=1 ?p ?p'
                    . ' GROUP BY ?p'
                    . " ORDER BY {$structure['variant_name_field']} ASC",
                    'variant_id',
                    $fields_join,
                    $products_table_base_joins,
                    $fields_where,
                    $products_table_base_conditions,
                    $db_field
                );

                /**
                 * Executes after variants selection query, allows to modify the result of it.
                 *
                 * @param array  $params                         Request params
                 * @param array  $filters                        Filters list
                 * @param array  $selected_filters               Selected filter variants
                 * @param string $area                           Current working area
                 * @param string $lang_code                      Language code
                 * @param array  $variant_values                 Feature filters variants values
                 * @param array  $field_variant_values           Product field filters variants values
                 * @param int    $filter_id                      Filter id
                 * @param array  $filter                         Filter data
                 * @param array  $result                         Variants query result
                 * @param string $fields_join                    Extra joins
                 * @param string $products_table_base_joins      Products table base joins
                 * @param string $fields_where                   Extra conditions
                 * @param string $products_table_base_conditions Products table base conditions
                 */
                fn_set_hook(
                    'get_current_filters_after_variants_select_query',
                    $params,
                    $filters,
                    $selected_filters,
                    $area,
                    $lang_code,
                    $variant_values,
                    $field_variant_values,
                    $filter_id,
                    $filter,
                    $result,
                    $fields_join,
                    $products_table_base_joins,
                    $fields_where,
                    $products_table_base_conditions
                );

                if (fn_is_empty($result)) {
                    unset($field_variant_values[$filter_id]);
                }
            }

            foreach (['prefix', 'suffix'] as $key) {
                if (!empty($structure[$key])) {
                    if (!empty($field_variant_values[$filter_id])) {
                        $field_variant_values[$filter_id][$key] = $structure[$key];
                    } elseif (!empty($field_range_values[$filter_id])) {
                        $field_range_values[$filter_id][$key] = $structure[$key];
                    }
                }
            }
        }
    }

    fn_product_filters_drop_temporary_products_tables();

    /**
     * Allows to change of $variant_values, $range_values, $field_variant_values, $field_range_values
     * to extend standard filters functionality.
     *
     * @param array  $params               request params
     * @param array  $filters              filters list
     * @param array  $selected_filters     selected filter variants
     * @param string $area                 current working area
     * @param string $lang_code            language code
     * @param array  $variant_values       feature filters variants values
     * @param array  $range_values         feature filters range values
     * @param array  $field_variant_values product field filters variants values
     * @param array  $field_range_values   product field filters range values
     */
    fn_set_hook('get_current_filters_post', $params, $filters, $selected_filters, $area, $lang_code, $variant_values, $range_values, $field_variant_values, $field_range_values);

    return [$variant_values, $range_values, $field_variant_values, $field_range_values];
}
