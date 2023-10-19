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
use Tygh\Enum\ProductFeaturesDisplayOn;
use Tygh\Enum\SiteArea;
use Tygh\Enum\YesNo;
use Tygh\Languages\Languages;
use Tygh\Navigation\LastView;
use Tygh\Registry;
use Tygh\Tools\SecurityHelper;

defined('BOOTSTRAP') or die('Access denied');

/**
 * Gets feature name by id
 *
 * @param mixed   $feature_id Integer feature id, or array of feature ids
 * @param string  $lang_code  2-letter language code
 * @param boolean $as_array   Flag: if set, result will be returned as array <i>(feature_id => feature)</i>; otherwise only feature name will be returned
 *
 * @return mixed In case 1 <i>feature_id</i> is passed and <i>as_array</i> is not set, a feature name string is returned;
 * Array <i>(feature_id => feature)</i> for all given <i>feature_ids</i>;
 * <i>False</i> if <i>$feature_id</i> is not defined
 */
function fn_get_feature_name($feature_id, $lang_code = CART_LANGUAGE, $as_array = false)
{
    /**
     * Change parameters for getting feature name
     *
     * @param int/array $feature_id Feature integer identifier
     * @param string    $lang_code  Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param boolean   $as_array   Flag determines if even one feature name should be returned as array
     */
    fn_set_hook('get_feature_name_pre', $feature_id, $lang_code, $as_array);

    $result = false;
    if (!empty($feature_id)) {
        if (!is_array($feature_id) && strpos($feature_id, ',') !== false) {
            $feature = explode(',', $feature_id);
        }

        $field_list = 'fd.feature_id as feature_id, fd.internal_name as feature';
        $join = '';
        if (is_array($feature_id) || $as_array == true) {
            $condition = db_quote(' AND fd.feature_id IN (?n) AND fd.lang_code = ?s', $feature_id, $lang_code);
        } else {
            $condition = db_quote(' AND fd.feature_id = ?i AND fd.lang_code = ?s', $feature_id, $lang_code);
        }

        /**
         * Change SQL parameters for getting feature name
         *
         * @param int/array $feature_id Feature integer identifier
         * @param string $lang_code Two-letter language code (e.g. 'en', 'ru', etc.)
         * @param boolean $as_array Flag determines if even one feature name should be returned as array
         * @param string $field_list List of fields for retrieving
         * @param string $join String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
         * @param string $condition Condition for selecting feature name
         */
        fn_set_hook('get_feature_name', $feature_id, $lang_code, $as_array, $field_list, $join, $condition);

        $result = db_get_hash_single_array("SELECT $field_list FROM ?:product_features_descriptions fd $join WHERE 1 $condition", array('feature_id', 'feature'));
        if (!(is_array($feature_id) || $as_array == true)) {
            if (isset($result[$feature_id])) {
                $result = $result[$feature_id];
            } else {
                $result = null;
            }
        }
    }

    /**
     * Change feature name selected by $feature_id & $lang_code params
     *
     * @param int/array    $feature_id Feature integer identifier
     * @param string       $lang_code  Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param boolean      $as_array   Flag determines if even one feature name should be returned as array
     * @param string/array $result     String containig feature name or array with features names depending on $feature_id param
     */
    fn_set_hook('get_feature_name_post', $feature_id, $lang_code, $as_array, $result);

    return $result;
}

/**
 * Updates product features values.
 *
 * @param int       $product_id         Product identifier
 * @param array     $product_features   List of feature values
 * @param array     $add_new_variant    List of new variants that will be added when the features of a product are saved
 * @param string    $lang_code          Two-letter language code (e.g. 'en', 'ru', etc.)
 * @param array     $params             List of additional parameters
 *
 * @return bool
 */
function fn_update_product_features_value($product_id, $product_features, $add_new_variant, $lang_code, $params = array())
{
    if (empty($product_features)) {
        return false;
    }

    $product_categories_paths = db_get_fields(
        'SELECT ?:categories.id_path FROM ?:products_categories '
        . 'LEFT JOIN ?:categories ON ?:categories.category_id = ?:products_categories.category_id '
        . 'WHERE product_id = ?i',
        $product_id
    );

    $product_categories_ids = array_unique(explode('/', implode('/', $product_categories_paths)));

    /**
     * Executed before saving the values of the features of a product.
     * It allows you to change the values of features before saving them.
     *
     * @param int       $product_id               Product identifier
     * @param array     $product_features         List of feature values
     * @param array     $add_new_variant          List of new variants that will be added when the features of a product are saved
     * @param string    $lang_code                Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param array     $params                   List of additional parameters
     * @param array     $product_categories_ids   List of the category identifiers
     * @param array     $product_categories_paths Categories paths (e.g. ['1/2/3', '1/2/4'])
     */
    fn_set_hook(
        'update_product_features_value_pre',
        $product_id,
        $product_features,
        $add_new_variant,
        $lang_code,
        $params,
        $product_categories_ids,
        $product_categories_paths
    );

    $i_data = [
        'product_id' => $product_id,
        'lang_code'  => $lang_code,
    ];

    foreach ($product_features as $feature_id => $value) {
        // Check if feature is applicable for this product
        $_params = [
            'category_ids'  => $product_categories_ids,
            'feature_id'    => $feature_id,
            'exclude_group' => true,
        ];
        list($_feature) = fn_get_product_features($_params);

        if (empty($_feature) && !empty($value)) {
            $_feature = db_get_field(
                'SELECT internal_name FROM ?:product_features_descriptions WHERE feature_id = ?i AND lang_code = ?s',
                $feature_id,
                $lang_code
            );
            $_product = db_get_field('SELECT product FROM ?:product_descriptions WHERE product_id = ?i AND lang_code = ?s', $product_id, $lang_code);
            fn_set_notification(NotificationSeverity::ERROR, __('error'), __('product_feature_cannot_assigned', [
                '[feature_name]' => $_feature,
                '[product_name]' => $_product
            ]));

            $value = '';
        }

        $i_data['feature_id'] = $feature_id;
        unset($i_data['value']);
        unset($i_data['variant_id']);
        unset($i_data['value_int']);
        $feature_type = fn_get_product_feature_type_by_feature_id($feature_id);

        // Delete variants in current language
        if ($feature_type === ProductFeatures::TEXT_FIELD) {
            db_query('DELETE FROM ?:product_features_values WHERE feature_id = ?i AND product_id = ?i AND lang_code = ?s', $feature_id, $product_id, $lang_code);
        } else {
            db_query('DELETE FROM ?:product_features_values WHERE feature_id = ?i AND product_id = ?i', $feature_id, $product_id);
        }

        if ($feature_type === ProductFeatures::DATE) {
            if (empty($value)) {
                continue;
            } else {
                $i_data['value_int'] = fn_parse_date($value);
            }
        } elseif ($feature_type === ProductFeatures::MULTIPLE_CHECKBOX) {
            if (!empty($add_new_variant[$feature_id]['variant'])
                || (
                    isset($add_new_variant[$feature_id]['variant'])
                    && $add_new_variant[$feature_id]['variant'] === '0'
                )
            ) {
                $value = empty($value) ? [] : $value;
                $variant = $add_new_variant[$feature_id]['variant'];
                if (is_array($variant)) {
                    $variants = $variant;
                } else {
                    $variants = [$variant];
                }

                $value = array_diff($value, $variants);
                foreach ($variants as $variant) {
                    $variant_id = fn_update_product_feature_variant($feature_id, $feature_type, ['variant' => $variant], $lang_code);
                    if (!$variant_id) {
                        continue;
                    }
                    $value[] = $variant_id;
                }
            }
            if (!empty($value)) {
                foreach ($value as $variant_id) {
                    foreach (Languages::getAll() as $i_data['lang_code'] => $_d) { // insert for all languages
                        $i_data['variant_id'] = $variant_id;
                        db_replace_into('product_features_values', $i_data);
                    }
                }
            }

            continue;
        } elseif (in_array($feature_type, array(ProductFeatures::TEXT_SELECTBOX, ProductFeatures::NUMBER_SELECTBOX, ProductFeatures::EXTENDED))) {
            if (
                !empty($add_new_variant[$feature_id]['variant'])
                || (
                    isset($add_new_variant[$feature_id]['variant'])
                    && $add_new_variant[$feature_id]['variant'] === '0'
                )
            ) {
                $i_data['variant_id'] = fn_update_product_feature_variant($feature_id, $feature_type, $add_new_variant[$feature_id], $lang_code);
                $i_data['value_int'] = $add_new_variant[$feature_id]['variant'];
            } elseif (
                !empty($value)
                && $value !== 'disable_select'
            ) {
                if ($feature_type === ProductFeatures::NUMBER_SELECTBOX) {
                    $i_data['value_int'] = db_get_field('SELECT variant FROM ?:product_feature_variant_descriptions WHERE variant_id = ?i AND lang_code = ?s', $value, $lang_code);
                }
                $i_data['variant_id'] = $value;
            } else {
                continue;
            }
        } else {
            if ($value === '') {
                continue;
            }
            if ($feature_type === ProductFeatures::NUMBER_FIELD) {
                $i_data['value_int'] = $value;
            } else {
                $i_data['value'] = $value;
            }
        }

        if ($feature_type !== ProductFeatures::TEXT_FIELD) { // feature values are common for all languages, except text (T)
            foreach (Languages::getAll() as $i_data['lang_code'] => $_d) {
                db_replace_into('product_features_values', $i_data);
            }
        } else { // for text feature, update current language only
            $i_data['lang_code'] = $lang_code;
            db_query('INSERT INTO ?:product_features_values ?e', $i_data);
        }
    }

    /**
     * Executed after saving the values of the features of a product.
     *
     * @param int       $product_id             Product identifier
     * @param array     $product_features       List of feature values
     * @param array     $add_new_variant        List of new variants that will be added when the features of a product are saved
     * @param string    $lang_code              Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param array     $params                 List of additional parameters
     * @param array     $product_categories_ids List of the category identifiers
     */
    fn_set_hook('update_product_features_value_post', $product_id, $product_features, $add_new_variant, $lang_code, $params, $product_categories_ids);

    return true;
}

/**
 * Gets array of product features
 *
 * @param array $params Products features search params
 * @param int $items_per_page Items per page
 * @param string $lang_code 2-letters language code
 * @return array Array with 3 params
 *              array $data Products features data
 *              array $params Products features search params
 *              boolean $has_ungroupped Flag determines if there are features without group
 */
function fn_get_product_features($params = array(), $items_per_page = 0, $lang_code = CART_LANGUAGE)
{
    /**
     * Changes params before getting products features
     *
     * @param array  $params         Products features search params
     * @param int    $items_per_page Items per page
     * @param string $lang_code      2-letters language code
     */
    fn_set_hook('get_product_features_pre', $params, $items_per_page, $lang_code);

    // Init filter
    $params = LastView::instance()->update('product_features', $params);

    $default_params = [
        'product_id'         => 0,
        'category_ids'       => [],
        'statuses'           => AREA === 'C' ? [ObjectStatuses::ACTIVE] : [],
        'plain'              => false,
        'feature_types'      => [],
        'feature_id'         => 0,
        'exclude_feature_id' => 0,
        'display_on'         => '',
        'exclude_group'      => false,
        'exclude_empty_groups' => false,
        'exclude_filters'    => false,
        'page'               => 1,
        'items_per_page'     => $items_per_page,

        'get_top_features'   => false,
        'top_features_limit' => 10,

        // Whether to load only features that have variants assigned or value applied to given product.
        // Parameter is only used if "product_id" is given.
        'existent_only'     => false,

        // Whether to load variants for loaded features.
        'variants'                => false,
        'variant_images'          => true,
        'variants_items_per_page' => null,
        'variants_page'           => null,

        // Whether to load only variants that are assigned for given product.
        // Parameter is only used if "product_id" is given and "variants" is set to true.
        'variants_selected_only' => false,

        // Whether to skip restriction on maximal count of variants to be loaded.
        'skip_variants_threshould' => false,

        // List of variant IDs that should be loaded in case of count of variants to be loaded is more
        // than specified variants threshold. Format: [feature_id => [variant_id, ...], ...].
        // Parameter is only used if "variants" param is set to true and "skip_variants_threshould" is set to false.
        'variants_only' => null,
    ];

    $params = array_merge($default_params, $params);

    $params['feature_types'] = $params['feature_types'] ?
        (array) $params['feature_types']
        : [];

    $base_fields = $fields = [
        'pf.feature_id',
        'pf.company_id',
        'pf.feature_type',
        'pf.parent_id',
        'pf.display_on_product',
        'pf.display_on_catalog',
        'pf.display_on_header',
        '?:product_features_descriptions.description',
        '?:product_features_descriptions.internal_name',
        '?:product_features_descriptions.lang_code',
        '?:product_features_descriptions.prefix',
        '?:product_features_descriptions.suffix',
        'pf.categories_path',
        '?:product_features_descriptions.full_description',
        'pf.status',
        'pf.comparison',
        'pf.position',
        'pf.purpose',
        'pf.feature_style',
        'pf.filter_style',
        'pf.feature_code',
        'pf.timestamp',
        'pf.updated_timestamp',
    ];

    $sortings = [
        'position' => [
            'group_position',
            'pf_groups_description.description',
            'pf_groups.feature_id',
            'pf.position',
            '?:product_features_descriptions.description',
            'pf.feature_id'
        ],
        'name' => '?:product_features_descriptions.description',
        'storefront_name' => '?:product_features_descriptions.internal_name',
        'group'  => 'pf_groups_description.description',
        'categories' => 'pf.categories_path',
        'status' => 'pf.status'
    ];

    $condition = $join = $group = '';
    $group_condition = '';

    $fields[] = 'pf_groups.position AS group_position';
    $join .= db_quote(" LEFT JOIN ?:product_features AS pf_groups ON pf.parent_id = pf_groups.feature_id");
    $join .= db_quote(" LEFT JOIN ?:product_features_descriptions AS pf_groups_description ON pf_groups_description.feature_id = pf.parent_id AND pf_groups_description.lang_code = ?s", $lang_code);
    $join .= db_quote(" LEFT JOIN ?:product_features_descriptions ON ?:product_features_descriptions.feature_id = pf.feature_id AND ?:product_features_descriptions.lang_code = ?s", $lang_code);

    if (!$params['feature_id'] && !in_array(ProductFeatures::GROUP, $params['feature_types'])) {
        $condition .= db_quote(" AND pf.feature_type != ?s", ProductFeatures::GROUP);
    }

    if (!empty($params['product_id'])) {
        $feature_values_join_type = empty($params['existent_only']) ? 'LEFT' : 'INNER';
        $join .= db_quote(
            " {$feature_values_join_type} JOIN ?:product_features_values"
            . " ON ?:product_features_values.feature_id = pf.feature_id"
            . " AND ?:product_features_values.product_id = ?i"
            . " AND ?:product_features_values.lang_code = ?s",
            $params['product_id'],
            $lang_code
        );

        $fields[] = '?:product_features_values.value';
        $fields[] = '?:product_features_values.variant_id';
        $fields[] = '?:product_features_values.value_int';

        $group = ' GROUP BY pf.feature_id';
    }

    if (!empty($params['feature_id'])) {
        $condition .= db_quote(" AND pf.feature_id IN (?n)", $params['feature_id']);
    }

    if (!empty($params['exclude_feature_id'])) {
        $condition .= db_quote(' AND pf.feature_id NOT IN (?n)', $params['exclude_feature_id']);
    }

    if (isset($params['description']) && fn_string_not_empty($params['description'])) {
        $condition .= db_quote(" AND ?:product_features_descriptions.description LIKE ?l", "%" . trim($params['description']) . "%");
    }

    if (isset($params['internal_name']) && fn_string_not_empty($params['internal_name'])) {
        $condition .= db_quote(' AND ?:product_features_descriptions.internal_name LIKE ?l', '%' . trim($params['internal_name']) . '%');
    }

    if (!empty($params['statuses'])) {
        $condition .= db_quote(" AND pf.status IN (?a) AND (pf_groups.status IN (?a) OR pf_groups.status IS NULL)", $params['statuses'], $params['statuses']);
    }

    if (!empty($params['updated_in_days'])) {
        $days_ago = TIME - $params['updated_in_days'] * SECONDS_IN_DAY;
        $condition .= db_quote(' AND pf.updated_timestamp >= ?i', $days_ago);
    }

    if (!empty($params['company_id'])) {
        $condition .= db_quote(' AND pf.company_id IN (?n)', (array) $params['company_id']);
    }

    if (isset($params['parent_id']) && $params['parent_id'] !== '') {
        $condition .= db_quote(" AND pf.parent_id = ?i", $params['parent_id']);
        $group_condition .= db_quote(" AND pf.feature_id = ?i", $params['parent_id']);
    }

    if (!empty($params['display_on']) && in_array($params['display_on'], array('product', 'catalog', 'header'))) {
        $condition .= " AND pf.display_on_$params[display_on] = 'Y'";
        $group_condition .= " AND pf.display_on_$params[display_on] = 'Y'";
    }

    if (!empty($params['feature_types'])) {
        $condition .= db_quote(" AND pf.feature_type IN (?a)", $params['feature_types']);
    }

    if (!empty($params['feature_code'])) {
        $condition .= db_quote(' AND pf.feature_code IN (?a)', $params['feature_code']);
    }

    if (!empty($params['purpose'])) {
        if (is_array($params['purpose'])) {
            $condition .= db_quote(' AND pf.purpose IN (?a)', $params['purpose']);
        } else {
            $condition .= db_quote(' AND pf.purpose = ?s', $params['purpose']);
        }
    }

    if (!empty($params['category_ids'])) {
        $c_ids = is_array($params['category_ids']) ? $params['category_ids'] : fn_explode(',', $params['category_ids']);
        $find_set = array(
            " pf.categories_path = '' OR ISNULL(pf.categories_path)"
        );

        if (!empty($params['search_in_subcats'])) {
            $child_ids = db_get_fields("SELECT a.category_id FROM ?:categories as a LEFT JOIN ?:categories as b ON b.category_id IN (?n) WHERE a.id_path LIKE CONCAT(b.id_path, '/%')", $c_ids);
            $c_ids = fn_array_merge($c_ids, $child_ids, false);
        }

        foreach ($c_ids as $k => $v) {
            $find_set[] = db_quote(" FIND_IN_SET(?i, pf.categories_path) ", $v);
        }

        $find_in_set = db_quote(' AND (?p)', implode('OR', $find_set));
        $condition .= $find_in_set;
        $group_condition .= $find_in_set;
    }

    if (!empty($params['exclude_filters'])) {
        $_condition = ' WHERE 1 ';

        if (fn_allowed_for('ULTIMATE')) {
            $_condition .= fn_get_company_condition('?:product_filters.company_id');
        }

        $exclude_feature_id = db_get_fields('SELECT ?:product_filters.feature_id FROM ?:product_filters ?p GROUP BY ?:product_filters.feature_id', $_condition);
        if (!empty($exclude_feature_id)) {
            $condition .= db_quote(' AND pf.feature_id NOT IN (?n)', $exclude_feature_id);
            unset($exclude_feature_id);
        }
    }

    /**
     * Change SQL parameters before product features selection
     *
     * @param array  $fields    List of fields for retrieving
     * @param string $join      String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $condition String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param array  $params    Products features search params
     */
    fn_set_hook('get_product_features', $fields, $join, $condition, $params);

    $sorting = db_sort($params, $sortings, 'position', 'asc');

    $limit = '';
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field(
            "SELECT COUNT(DISTINCT pf.feature_id) FROM ?:product_features AS pf $join WHERE 1 $condition"
        );
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $data = db_get_hash_array(
        'SELECT ?p FROM ?:product_features AS pf'
        . ' ?p WHERE 1=1 ?p ?p ?p ?p',
        'feature_id',
        implode(', ', $fields),
        $join,
        $condition,
        $group,
        $sorting,
        $limit
    );

    $has_ungroupped = false;

    // Fetch variants for loaded features
    if (!empty($data) && $params['variants']) {

        // Only fetch variants for selectable features
        $feature_ids = array();
        foreach ($data as $feature_id => $feature_data) {
            if (strpos(ProductFeatures::getSelectable(), $feature_data['feature_type']) !== false) {
                $feature_ids[] = $feature_id;
                $data[$feature_id]['variants'] = array(); // initialize variants
            }
        }

        // Variants to load if count of variants to be loaded is more than threshold
        // [feature_id => [variant_id, ...], ...]
        $variant_ids_to_load = isset($params['variants_only']) ? (array) $params['variants_only'] : array();

        if (
            (AREA === SiteArea::ADMIN_PANEL && empty($params['skip_variants_threshould']))
            || $params['variants_page']
            || $params['variants_items_per_page']
        ) {
            foreach ($feature_ids as $feature_id) {
                $variants_params = [
                    'feature_id'     => $feature_id,
                    'product_id'     => $params['product_id'],
                    'get_images'     => $params['variant_images'],
                    'selected_only'  => $params['variants_selected_only'],
                    'page'           => $params['variants_page'],
                    'items_per_page' => $params['variants_items_per_page'],
                ];

                if (AREA === SiteArea::ADMIN_PANEL && empty($params['skip_variants_threshould'])) {
                    // Fetch count of variants to be loaded
                    $variants_params['fetch_total_count_only'] = true;
                    // @TODO Request in bulk
                    $total_variants_count = fn_get_product_feature_variants($variants_params, 0, $lang_code);
                    $variants_params['fetch_total_count_only'] = false;

                    if ($total_variants_count > PRODUCT_FEATURE_VARIANTS_THRESHOLD) {
                        // AJAX variants loader will be used
                        $data[$feature_id]['use_variant_picker'] = true;

                        // Fetch only selected variants for given product (if it is given).
                        // These variants would be used for displaying preselection at AJAX variants loader.
                        if (!empty($params['product_id'])) {
                            $variants_params['selected_only'] = true;
                        } elseif (!empty($variant_ids_to_load[$feature_id])) {
                            // Load specific variants (for example for preselection at AJAX loader at search form)
                            // Restrict selection to specified variant IDs
                            $variants_params['variant_id'] = $variant_ids_to_load[$feature_id];
                        } else {
                            // Skip loading variants.
                            continue;
                        }
                    }
                }

                list($variants) = fn_get_product_feature_variants($variants_params, 0, $lang_code);

                foreach ($variants as $variant) {
                    $data[$variant['feature_id']]['variants'][$variant['variant_id']] = $variant;
                }
            }
        } else {
            $variants_params = [
                'feature_id'     => $feature_ids,
                'product_id'     => $params['product_id'],
                'get_images'     => $params['variant_images'],
                'selected_only'  => $params['variants_selected_only'],
            ];

            list($variants) = fn_get_product_feature_variants($variants_params, 0, $lang_code);

            foreach ($variants as $variant) {
                $data[$variant['feature_id']]['variants'][$variant['variant_id']] = $variant;
            }
        }
    }

    foreach ($data as $feature_data) {
        if (empty($feature_data['parent_id'])) {
            $has_ungroupped = true;
            break;
        }
    }

    // Get groups
    if (empty($params['exclude_group'])) {

        $group_ids = [];
        foreach ($data as $feature_data) {
            if (!empty($feature_data['parent_id'])) {
                $group_ids[$feature_data['parent_id']] = true;
            }
        }

        $additional_condition = db_quote('pf.feature_id IN (?n)', array_keys($group_ids));

        if (empty($params['exclude_empty_groups'])) {
            $additional_condition .= ' OR pf.feature_id NOT IN (SELECT parent_id FROM ?:product_features)';
        }

        $groups = db_get_hash_array('SELECT ?p FROM ?:product_features AS pf'
            . ' LEFT JOIN ?:product_features_descriptions'
                . ' ON ?:product_features_descriptions.feature_id = pf.feature_id'
                    . ' AND ?:product_features_descriptions.lang_code = ?s'
            . ' WHERE pf.feature_type = ?s AND (?p) ?p'
            . ' ORDER BY pf.position, ?:product_features_descriptions.description',
            'feature_id',
            implode(', ', $base_fields),
            $lang_code,
            ProductFeatures::GROUP,
            $additional_condition,
            $group_condition
        );

        // Insert groups before appropriate features
        $new_data = $groups;
        foreach ($data as $feature_id => $feature_data) {
            if (!empty($feature_data['parent_id']) && !empty($groups[$feature_data['parent_id']])) {
                $new_data[$feature_data['parent_id']] = $groups[$feature_data['parent_id']];
                unset($groups[$feature_data['parent_id']]);
            }
            $new_data[$feature_id] = $feature_data;
        }
        $data = $new_data;
    }

    if (isset($params['get_top_features']) && $params['get_top_features'] === true) {
        foreach ($data as $key => $value) {
            if (!isset($value['feature_type']) || $value['feature_type'] !== ProductFeatures::GROUP) {
                continue;
            }

            $top_features_params = [
                'exclude_group' => true,
                'parent_id'     => $value['feature_id'],
            ];

            list($top_features, $top_features_search_param) = fn_get_product_features($top_features_params, $params['top_features_limit']);

            $data[$key]['top_features'] = $top_features;
            $data[$key]['features_count'] = $top_features_search_param['total_items'];
        }
    }

    if ($params['plain'] == false) {
        $delete_keys = array();
        foreach ($data as $key => $value) {
            if (!empty($value['parent_id']) && !empty($data[$value['parent_id']])) {
                $data[$value['parent_id']]['subfeatures'][$value['feature_id']] = $value;
                $data[$key] = & $data[$value['parent_id']]['subfeatures'][$value['feature_id']];
                $delete_keys[] = $key;
            }

            if (!empty($params['get_descriptions']) && empty($value['parent_id'])) {
                $category_list = fn_get_categories_list($value['categories_path'], $lang_code);
                $data[$key]['feature_description'] = '<span>' . implode(', ', $category_list) . '</span>';
            }
        }

        foreach ($delete_keys as $key) {
            unset($data[$key]);
        }
    }

    /**
     * Change products features data
     *
     * @param array   $data           Products features data
     * @param array   $params         Products features search params
     * @param boolean $has_ungroupped Flag determines if there are features without group
     */
    fn_set_hook('get_product_features_post', $data, $params, $has_ungroupped);

    LastView::instance()->processResults('product_features', $data, $params);

    return array($data, $params, $has_ungroupped);
}

/**
 * Gets single product feature data.
 *
 * @param int    $feature_id         Feature identifier
 * @param bool   $get_variants       Flag determines if product variants should be fetched
 * @param bool   $get_variant_images Flag determines if variant images should be fetched
 * @param string $lang_code          Two-letter language code
 *
 * @return array<string, string|int> Product feature data
 *
 * @psalm-return array{
 *   description: string,
 *   categories_path: string,
 *   feature_type: string,
 *   feature_id: int,
 *   company_id: int
 * }
 */
function fn_get_product_feature_data($feature_id, $get_variants = false, $get_variant_images = false, $lang_code = CART_LANGUAGE)
{
    /**
     * Changes params before getting product feature data
     *
     * @param int     $feature_id         Feature identifier
     * @param boolean $get_variants       Flag determines if product variants should be fetched
     * @param boolean $get_variant_images Flag determines if variant images should be fetched
     * @param string  $lang_code          2-letters language code
     */
    fn_set_hook('get_product_feature_data_pre', $feature_id, $get_variants, $get_variant_images, $lang_code);

    $fields = array(
        '?:product_features.feature_id',
        '?:product_features.feature_code',
        '?:product_features.company_id',
        '?:product_features.feature_type',
        '?:product_features.parent_id',
        '?:product_features.display_on_product',
        '?:product_features.display_on_catalog',
        '?:product_features.display_on_header',
        '?:product_features_descriptions.description',
        '?:product_features_descriptions.internal_name',
        '?:product_features_descriptions.lang_code',
        '?:product_features_descriptions.prefix',
        '?:product_features_descriptions.suffix',
        '?:product_features.categories_path',
        '?:product_features_descriptions.full_description',
        '?:product_features.status',
        '?:product_features.comparison',
        '?:product_features.position',
        '?:product_features.purpose',
        '?:product_features.feature_style',
        '?:product_features.filter_style'
    );

    $join = db_quote("LEFT JOIN ?:product_features_descriptions ON ?:product_features_descriptions.feature_id = ?:product_features.feature_id AND ?:product_features_descriptions.lang_code = ?s", $lang_code);

    $condition = db_quote("?:product_features.feature_id = ?i", $feature_id);

    /**
     * Change SQL parameters before fetching product feature data
     *
     * @param array   $fields             Array SQL fields to be selected in an SQL-query
     * @param string  $join               String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string  $condition          String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param int     $feature_id         Feature identifier
     * @param boolean $get_variants       Flag determines if product variants should be fetched
     * @param boolean $get_variant_images Flag determines if variant images should be fetched
     * @param string  $lang_code          2-letters language code
     */
    fn_set_hook('get_product_feature_data_before_select', $fields, $join, $condition, $feature_id, $get_variants, $get_variant_images, $lang_code);

    $feature_data = db_get_row("SELECT " . implode(",", $fields) . " FROM ?:product_features $join WHERE $condition");

    if ($get_variants == true && $feature_data) {
        list($feature_data['variants']) = fn_get_product_feature_variants(array(
            'feature_id' => $feature_id,
            'feature_type' => $feature_data['feature_type'],
            'get_images' => $get_variant_images
        ), 0, $lang_code);
    }

    /**
     * Change product feature data
     *
     * @param array $feature_data Product feature data
     */
    fn_set_hook('get_product_feature_data_post', $feature_data);

    return $feature_data;
}

/**
 * Gets product features list
 *
 * @TODO Merge with {fn_get_product_features()}
 *
 * @param array  $product    Array with product data
 * @param string $display_on Code determines zone (product/catalog page) for that features are selected
 * @param string $lang_code  2-letters language code
 *
 * @return array Product features
 */
function fn_get_product_features_list(array $product, $display_on = ProductFeaturesDisplayOn::CATALOG, $lang_code = CART_LANGUAGE)
{
    static $filters = null;

    /**
     * Changes params before getting product features list
     *
     * @param array  $product    Array with product data
     * @param string $display_on Code determines zone (product/catalog page) for that features are selected
     * @param string $lang_code  2-letters language code
     */
    fn_set_hook('get_product_features_list_pre', $product, $display_on, $lang_code);

    $product_id = $product['product_id'];

    $features_list = [];

    if ($display_on === ProductFeaturesDisplayOn::HEADER) {
        $condition = db_quote(' AND f.display_on_header = ?s', YesNo::YES);
    } elseif ($display_on === ProductFeaturesDisplayOn::CATALOG) {
        $condition = db_quote(' AND f.display_on_catalog = ?s', YesNo::YES);
    } elseif ($display_on === ProductFeaturesDisplayOn::CATALOG . ProductFeaturesDisplayOn::PRODUCT) {
        $condition = db_quote(' AND (f.display_on_catalog = ?s OR f.display_on_product = ?s)', YesNo::YES, YesNo::YES);
    } elseif ($display_on === ProductFeaturesDisplayOn::ALL || $display_on === ProductFeaturesDisplayOn::EXIM) {
        $condition = '';
    } elseif ($display_on === ProductFeaturesDisplayOn::PRODUCT) {
        $condition = db_quote(' AND f.display_on_product = ?s', YesNo::YES);
    } else {
        $condition = db_quote(' AND f.display_on_product = ?s', YesNo::YES);
    }

    $category_ids = [];

    if (!empty($product['category_ids'])) {
        $category_ids = $product['category_ids'];
    } elseif (!empty($product['main_category'])) {
        $category_ids = (array) $product['main_category'];
    }

    $path = fn_get_category_ids_with_parent($category_ids);

    $find_set = array(
        " f.categories_path = '' "
    );
    foreach ($path as $k => $v) {
        $find_set[] = db_quote(" FIND_IN_SET(?i, f.categories_path) ", $v);
    }
    $find_in_set = db_quote(" AND (?p)", implode('OR', $find_set));
    $condition .= $find_in_set;

    $fields = db_quote(
        'v.feature_id,
        v.value,
        v.value_int,
        v.variant_id,
        f.feature_type,
        fd.internal_name,
        fd.description,
        fd.prefix,
        fd.suffix,
        vd.variant,
        f.parent_id,
        f.position,
        gf.position as gposition,
        f.display_on_header,
        f.display_on_catalog,
        f.display_on_product,
        f.feature_code,
        f.purpose'
    );
    $join = db_quote(
        "LEFT JOIN ?:product_features_values as v ON v.feature_id = f.feature_id "
        . " LEFT JOIN ?:product_features_descriptions as fd ON fd.feature_id = v.feature_id AND fd.lang_code = ?s"
        . " LEFT JOIN ?:product_feature_variants fv ON fv.variant_id = v.variant_id"
        . " LEFT JOIN ?:product_feature_variant_descriptions as vd ON vd.variant_id = fv.variant_id AND vd.lang_code = ?s"
        . " LEFT JOIN ?:product_features as gf ON gf.feature_id = f.parent_id AND gf.feature_type = ?s ",
        $lang_code, $lang_code, ProductFeatures::GROUP);

    // Features should be active and be assigned to given product
    $allowed_feature_statuses = ['A'];
    if ($display_on === ProductFeaturesDisplayOn::EXIM) {
        $allowed_feature_statuses[] = 'H';
    }
    $condition = db_quote("f.status IN (?a) AND v.product_id = ?i ?p", $allowed_feature_statuses, $product_id, $condition);

    // Parent group of feature (if any) status condition
    $allowed_parent_group_statuses = ['A'];
    if ($display_on === ProductFeaturesDisplayOn::EXIM) {
        $allowed_parent_group_statuses[] = 'H';
    }
    $condition .= db_quote(
        " AND IF(f.parent_id,"
        . " (SELECT status FROM ?:product_features as df WHERE df.feature_id = f.parent_id), 'A') IN (?a)",
        $allowed_parent_group_statuses
    );

    $condition .= db_quote(
        " AND ("
        . " v.variant_id != 0"
        . " OR (f.feature_type != ?s AND v.value != '')"
        . " OR (f.feature_type = ?s)"
        . " OR v.value_int != ''"
        . ")"
        . " AND v.lang_code = ?s",
        ProductFeatures::SINGLE_CHECKBOX, ProductFeatures::SINGLE_CHECKBOX, $lang_code
    );

    /**
     * Change SQL parameters before fetching product feature data
     *
     * @param string $fields    String of comma-separated SQL fields to be selected in an SQL-query
     * @param string $join      String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $condition String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param array  $product   Array with product data
     * @param string $lang_code 2-letters language code
     */
    fn_set_hook('get_product_features_list_before_select', $fields, $join, $condition, $product, $display_on, $lang_code);

    $_data = db_get_array(
        'SELECT ?p FROM ?:product_features as f ?p WHERE ?p ORDER BY fd.internal_name, fv.position',
        $fields,
        $join,
        $condition
    );
    $_variant_ids = array();

    if (!empty($_data)) {
        if ($filters === null) {
            $filter_condition = "status = 'A'";

            if (fn_allowed_for('ULTIMATE')) {
                $filter_condition .= fn_get_company_condition('?:product_filters.company_id');
            }

            $filters = db_get_hash_array("SELECT filter_id, feature_id FROM ?:product_filters WHERE {$filter_condition}", 'feature_id');
        }

        foreach ($_data as $k => $feature) {
            if ($feature['feature_type'] == ProductFeatures::SINGLE_CHECKBOX) {
                if ($feature['value'] != 'Y' && $display_on != 'A') {
                    unset($_data[$k]);
                    continue;
                }
            }

            if (empty($features_list[$feature['feature_id']])) {
                $features_list[$feature['feature_id']] = $feature;
            }

            if (!empty($feature['variant_id'])) { // feature has several variants
                if (isset($filters[$feature['feature_id']])) {
                    $features_list[$feature['feature_id']]['features_hash'] = fn_add_filter_to_hash(
                        '',
                        $filters[$feature['feature_id']]['filter_id'],
                        $feature['variant_id']
                    );
                }

                $features_list[$feature['feature_id']]['variants'][$feature['variant_id']] = array(
                    'value' => $feature['value'],
                    'value_int' => $feature['value_int'],
                    'variant_id' => $feature['variant_id'],
                    'variant' => $feature['variant'],
                );
                $_variant_ids[] = $feature['variant_id'];
            }
        }

        if (!empty($_variant_ids)) {
            $images = fn_get_image_pairs($_variant_ids, 'feature_variant', 'V', true, true, $lang_code);

            foreach ($features_list as $feature_id => $feature) {
                if (isset($images[$feature['variant_id']])) {
                    $features_list[$feature_id]['variants'][$feature['variant_id']]['image_pairs'] = reset($images[$feature['variant_id']]);
                }
            }
        }
    }

    $groups = array();
    foreach ($features_list as $f_id => $data) {
        $groups[$data['parent_id']]['features'][$f_id] = $data;
        $groups[$data['parent_id']]['position'] = empty($data['parent_id']) ? $data['position'] : $data['gposition'];
    }

    $features_list = array();
    if (!empty($groups)) {
        $groups = fn_sort_array_by_key($groups, 'position');
        foreach ($groups as $g) {
            $g['features'] = fn_sort_array_by_key($g['features'], 'position');
            $features_list = fn_array_merge($features_list, $g['features']);
        }
    }

    unset($groups);
    foreach ($features_list as $f_id => $data) {
        unset($features_list[$f_id]['position']);
        unset($features_list[$f_id]['gposition']);
    }

    /**
     * Changes product features list data
     *
     * @param array  $features_list Product features
     * @param array  $product       Array with product data
     * @param string $display_on    Code determines zone (product/catalog page) for that features are selected
     * @param string $lang_code     2-letters language code
     */
    fn_set_hook('get_product_features_list_post', $features_list, $product, $display_on, $lang_code);

    return $features_list;
}

/**
 * Gets products features
 *
 * @deprecated since 4.11.1. Use fn_get_product_features instead
 *
 * @see \fn_get_product_features()
 *
 * @param string $lang_code 2-letters language code
 * @param boolean $simple Flag determines if only feature names(true) or all properties(false) should be selected
 * @param boolean $get_hidden Flag determines if all feature fields should be selected
 * @return array Product features
 */
function fn_get_avail_product_features($lang_code = CART_LANGUAGE, $simple = false, $get_hidden = true)
{
    /**
     * Changes parameters for getting available product features
     *
     * @deprecated since 4.11.1. Use `get_product_features_pre` instead.
     *
     * @param string  $lang_code  2-letters language code
     * @param boolean $simple     Flag determines if only feature names(true) or all properties(false) should be selected
     * @param boolean $get_hidden Flag determines if all feature fields should be selected
     */
    fn_set_hook('get_avail_product_features_pre', $lang_code,  $simple, $get_hidden);

    $statuses = array('A');

    if ($get_hidden == false) {
        $statuses[] = 'D';
    }

    if ($simple == true) {
        $fields = db_quote("?:product_features.feature_id, ?:product_features_descriptions.description");
    } else {
        $fields = db_quote("?:product_features.*, ?:product_features_descriptions.*");
    }

    $join = db_quote("LEFT JOIN ?:product_features_descriptions ON ?:product_features_descriptions.feature_id = ?:product_features.feature_id AND ?:product_features_descriptions.lang_code = ?s", $lang_code);

    $condition = db_quote("?:product_features.status IN (?a) AND ?:product_features.feature_type != ?s", $statuses, ProductFeatures::GROUP);

    /**
     * Change SQL parameters before fetching available product features
     *
     * @deprecated since 4.11.1. Use
     *
     * @param string  $fields     String of comma-separated SQL fields to be selected in an SQL-query
     * @param string  $join       String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string  $condition  String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string  $lang_code  2-letters language code
     * @param boolean $simple     Flag determines if only feature names(true) or all properties(false) should be selected
     * @param boolean $get_hidden Flag determines if all feature fields should be selected
     */
    fn_set_hook('get_avail_product_features_before_select', $fields, $join, $condition, $lang_code,  $simple, $get_hidden);

    if ($simple == true) {
        $result = db_get_hash_single_array("SELECT $fields FROM ?:product_features $join WHERE $condition ORDER BY ?:product_features.position", array('feature_id', 'description'));
    } else {
        $result = db_get_hash_array("SELECT $fields FROM ?:product_features $join WHERE $condition ORDER BY ?:product_features.position", 'feature_id');
    }

    /**
     * Changes  available product features data
     *
     * @deprecated since 4.11.1. Use `get_product_features_post` instead.
     *
     * @param array   $result     Product features
     * @param string  $lang_code  2-letters language code
     * @param boolean $simple     Flag determines if only feature names(true) or all properties(false) should be selected
     * @param boolean $get_hidden Flag determines if all feature fields should be selected
     */
    fn_set_hook('get_avail_product_features_post', $result, $lang_code,  $simple, $get_hidden);

    return $result;
}

/**
 * Gets product feature variants
 *
 * @param array $params array with search parameters
 * @param int $items_per_page Items per page
 * @param string $lang_code 2-letters language code
 * @return array Product feature variants
 */
function fn_get_product_feature_variants($params, $items_per_page = 0, $lang_code = CART_LANGUAGE)
{
    /**
     * Changes parameters for getting product feature variants
     *
     * @param array  $params         array with search parameters
     * @param int    $items_per_page Items per page
     * @param string $lang_code      2-letters language code
     */
    fn_set_hook('get_product_feature_variants_pre', $params, $items_per_page, $lang_code);

    // Set default values to input params
    $default_params = array (
        'page' => 1,
        'product_id' => 0,
        'feature_id' => 0,
        'feature_type' => '',
        'get_images' => false,
        'items_per_page' => $items_per_page,
        'selected_only' => false,
        'fetch_total_count_only' => false,
        'search_query' => null,

        // An ID or list of IDs of variants that should be loaded.
        'variant_id' => null,
    );

    $params = array_merge($default_params, $params);

    if (is_array($params['feature_id'])) {
        $fields = array(
            '?:product_feature_variant_descriptions.variant',
            '?:product_feature_variants.variant_id',
            '?:product_feature_variants.feature_id',
        );
    } else {
        $fields = array(
            '?:product_feature_variant_descriptions.*',
            '?:product_feature_variants.*',
        );
    }

    $condition = $group_by = $sorting = '';

    if (isset($params['feature_id'])) {
        $feature_id = is_array($params['feature_id']) ? $params['feature_id'] : [$params['feature_id']];
        $condition .= db_quote(' AND ?:product_feature_variants.feature_id IN (?n)', $feature_id);
    }

    $join = db_quote(" LEFT JOIN ?:product_feature_variant_descriptions ON ?:product_feature_variant_descriptions.variant_id = ?:product_feature_variants.variant_id AND ?:product_feature_variant_descriptions.lang_code = ?s", $lang_code);
    $sorting = db_quote("?:product_feature_variants.position, ?:product_feature_variant_descriptions.variant");

    if (!empty($params['variant_id'])) {
        $condition .= db_quote(' AND ?:product_feature_variants.variant_id IN (?n)', (array)$params['variant_id']);
    }

    if (!empty($params['product_id'])) {
        $fields[] = '?:product_features_values.variant_id as selected';
        $fields[] = '?:product_features.feature_type';

        if (!empty($params['selected_only'])) {
            $join .= db_quote(' INNER JOIN ?:product_features_values ON ?:product_features_values.variant_id = ?:product_feature_variants.variant_id AND ?:product_features_values.lang_code = ?s AND ?:product_features_values.product_id = ?i AND ?:product_features_values.feature_id = ?:product_feature_variants.feature_id', $lang_code, $params['product_id']);
        } else {
            $join .= db_quote(' LEFT JOIN ?:product_features_values ON ?:product_features_values.variant_id = ?:product_feature_variants.variant_id AND ?:product_features_values.lang_code = ?s AND ?:product_features_values.product_id = ?i AND ?:product_features_values.feature_id = ?:product_feature_variants.feature_id', $lang_code, $params['product_id']);
        }

        $join .= db_quote(" LEFT JOIN ?:product_features ON ?:product_features.feature_id = ?:product_feature_variants.feature_id");
        $group_by = db_quote(" GROUP BY ?:product_feature_variants.variant_id");
    }

    if (!empty($params['search_query'])) {
        $condition .= db_quote(' AND ?:product_feature_variant_descriptions.variant LIKE ?l',
            '%' . trim($params['search_query']) . '%'
        );
    }

    $limit = '';

    if ($params['fetch_total_count_only'] || !empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:product_feature_variants $join WHERE 1 $condition");

        if ($params['fetch_total_count_only']) {
            return $params['total_items'];
        } elseif ($params['items_per_page']) {
            $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
        }
    }

    /**
     * Changes  SQL parameters for getting product feature variants
     *
     * @param array  $fields    List of fields for retrieving
     * @param string $join      String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $condition String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string $group_by  String containing the SQL-query GROUP BY field
     * @param string $sorting   String containing the SQL-query ORDER BY clause
     * @param string $lang_code 2-letters language code
     * @param string $limit     String containing the SQL-query LIMIT clause
     * @param array  $params    Array with search parameters
     */
    fn_set_hook('get_product_feature_variants', $fields, $join, $condition, $group_by, $sorting, $lang_code, $limit, $params);

    $vars = db_get_hash_array('SELECT ' . implode(', ', $fields) . " FROM ?:product_feature_variants $join WHERE 1 $condition $group_by ORDER BY $sorting $limit", 'variant_id');

    if ($params['get_images'] == true) {
        $image_pairs = $vars
            ? fn_get_image_pairs(array_keys($vars), 'feature_variant', 'V', true, true, $lang_code)
            : array();

        foreach ($image_pairs as $variant_id => $image_pair) {
            $vars[$variant_id]['image_pair'] = array_pop($image_pair);
        }
    }

    /**
     * Changes feature variants data
     *
     * @param array  $vars      Product feature variants
     * @param array  $params    array with search params
     * @param string $lang_code 2-letters language code
     */
    fn_set_hook('get_product_feature_variants_post', $vars, $params, $lang_code);

    return array($vars, $params);
}

/**
 * Gets product feature variant data
 *
 * @param int $variant_id Variant identifier
 * @param string $lang_code 2-letters language code
 * @return array Variant data
 */
function fn_get_product_feature_variant($variant_id, $lang_code = CART_LANGUAGE)
{
    $fields = "*";
    $join = db_quote("LEFT JOIN ?:product_feature_variant_descriptions ON ?:product_feature_variant_descriptions.variant_id = ?:product_feature_variants.variant_id AND ?:product_feature_variant_descriptions.lang_code = ?s", $lang_code);
    $condition = db_quote("?:product_feature_variants.variant_id = ?i", $variant_id);

    /**
     * Changes SQL parameters before select product feature variant data
     *
     * @param string $fields     String of comma-separated SQL fields to be selected in an SQL-query
     * @param string $join       String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $condition  String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param int    $variant_id Variant identifier
     * @param string $lang_code  2-letters language code
     */
    fn_set_hook('get_product_feature_variant_before_select', $fields, $join, $condition, $variant_id, $lang_code);

    $var = db_get_row("SELECT $fields FROM ?:product_feature_variants $join WHERE $condition");

    if (empty($var)) {
        return false;
    }

    $var['image_pair'] = fn_get_image_pairs($variant_id, 'feature_variant', 'V', true, true, $lang_code);

    if (empty($var['meta_description']) && defined('AUTO_META_DESCRIPTION') && AREA != 'A') {
        $var['meta_description'] = fn_generate_meta_description($var['description']);
    }

    /**
     * Changes product feature variant data
     *
     * @param array  $var        Variant data
     * @param int    $feature_id Feature identifier
     * @param string $lang_code  2-letters language code
     */
    fn_set_hook('get_product_feature_variant_post', $var, $variant_id, $lang_code);

    return $var;
}

/**
 * Filters feature group data, leaves only settings that should be upllied to feature
 *
 * @param array $group_data Group data
 * @return array Filtered group data
 */
function fn_filter_feature_group_data($group_data)
{
    $display_settings = array('display_on_product', 'display_on_catalog', 'display_on_header');
    foreach ($display_settings as $setting) {
        if ($group_data[$setting] != 'Y') {
            unset($group_data[$setting]);
        }
    }

    return $group_data;
}

/**
 * Updates product feature
 *
 * @param array $feature_data Feature data
 * @param int $feature_id Feature identifier
 * @param string $lang_code 2-letters language code
 *
 * @return int|boolean Feature identifier if product feature was updated, false otherwise
 */
function fn_update_product_feature($feature_data, $feature_id, $lang_code = DESCR_SL)
{
    /**
     * Changes before product feature updating
     *
     * @param array  $feature_data Feature data
     * @param int    $feature_id   Feature identifier
     * @param string $lang_code    2-letters language code
     */
    fn_set_hook('update_product_feature_pre', $feature_data, $feature_id, $lang_code);

    SecurityHelper::sanitizeObjectData('product_feature', $feature_data);

    if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id')) {
        if (!empty($feature_id) && $feature_id != NEW_FEATURE_GROUP_ID) {
            if (!fn_check_company_id('product_features', 'feature_id', $feature_id)) {
                fn_company_access_denied_notification();

                return false;
            }
            unset($feature_data['company_id']);
        }
    }

    $deleted_variants = [];
    $old_feature_data = [];
    $selectable_types = ProductFeatures::getSelectable();

    // If this feature belongs to the group, get categories assignment from this group
    if (!empty($feature_data['parent_id'])) {
        $feature_group_data = db_get_row(
            'SELECT categories_path, display_on_product, display_on_catalog, display_on_header FROM ?:product_features WHERE feature_id = ?i',
            $feature_data['parent_id']
        );
        if ($feature_group_data) {
            $feature_group_data = fn_filter_feature_group_data($feature_group_data);
            $feature_data = fn_array_merge($feature_data, $feature_group_data);
        }
    }

    // Add feature
    if (empty($feature_data['internal_name']) && !empty($feature_data['description'])) {
        $feature_data['internal_name'] = $feature_data['description'];
    }

    if (empty($feature_data['description']) && !empty($feature_data['internal_name'])) {
        $feature_data['description'] = $feature_data['internal_name'];
    }

    $action = null;
    if (!intval($feature_id)) { // check for intval as we use "0G" for new group
        $action = 'create';

        if (!empty($feature_data['feature_type']) && empty($feature_data['purpose'])) {
            $feature_data['purpose'] = (string) fn_get_product_feature_purpose_by_type($feature_data['feature_type']);
            $feature_data['feature_style'] = '';
            $feature_data['filter_style'] = '';
        }

        if (empty($feature_data['feature_style'])) {
            $feature_data['feature_style'] = (string) fn_get_product_feature_style($feature_data['feature_type'], $feature_data['purpose']);
            $feature_data['filter_style'] = '';
        }

        if (empty($feature_data['filter_style'])) {
            $feature_data['filter_style'] = (string) fn_get_product_feature_filter_style($feature_data['feature_type'], $feature_data['feature_style'], $feature_data['purpose']);
        }

        $feature_data['timestamp'] = $feature_data['updated_timestamp'] = time();

        $feature_data['feature_id'] = $feature_id = db_query('INSERT INTO ?:product_features ?e', $feature_data);
        foreach (array_keys(Languages::getAll()) as $feature_data['lang_code']) {
            db_query('INSERT INTO ?:product_features_descriptions ?e', $feature_data);
        }

    } else {
        $action = 'update';

        $feature_data['updated_timestamp'] = time();

        $old_feature_data = fn_get_feature_data_with_subfeatures($feature_id, $lang_code, ['statuses' => []]);

        if (!$old_feature_data) {
            fn_set_notification(
                NotificationSeverity::ERROR,
                __('error'),
                __('object_not_found', [
                    '[object]' => __('feature'),
                ]),
                '',
                '404'
            );
            $feature_id = false;
        }

        if (
            !isset($feature_data['feature_type'])
            && isset($old_feature_data['feature_type'])
        ) {
            $feature_data['feature_type'] = $old_feature_data['feature_type'];
        }

        if (!isset($feature_data['categories_path'])
            && empty($old_feature_data['categories_path'])
        ) {
            $feature_data['categories_path'] = '';
        }

        if (!empty($feature_data['feature_type']) && empty($feature_data['purpose'])) {
            if (!empty($old_feature_data['feature_type']) && $old_feature_data['feature_type'] === $feature_data['feature_type'] && !empty($old_feature_data['purpose'])) {
                $feature_data['purpose'] = $old_feature_data['purpose'];
            } else {
                $feature_data['purpose'] = (string) fn_get_product_feature_purpose_by_type($feature_data['feature_type']);
            }
        }

        if (empty($feature_data['feature_style']) && !empty($old_feature_data['feature_style'])) {
            $feature_data['feature_style'] = $old_feature_data['feature_style'];
        } elseif (empty($feature_data['feature_style'])) {
            $feature_data['feature_style'] = (string) fn_get_product_feature_style($feature_data['feature_type'], $feature_data['purpose']);
        }

        if (empty($feature_data['filter_style']) && !empty($old_feature_data['filter_style'])) {
            $feature_data['filter_style'] = $old_feature_data['filter_style'];
        } elseif (empty($feature_data['filter_style'])) {
            $feature_data['filter_style'] = (string) fn_get_product_feature_filter_style($feature_data['feature_type'], $feature_data['feature_style'], $feature_data['purpose']);
        }
    }

    if ($feature_id && strpos($selectable_types, $feature_data['feature_type']) !== false) {
        fn_update_product_feature_variants($feature_id, $feature_data, $lang_code);

        // remove unnecessary variants from all products
        if (
            isset($old_feature_data['feature_type'])
            && $old_feature_data['feature_type'] === ProductFeatures::MULTIPLE_CHECKBOX
            && isset($feature_data['feature_type'])
            && $feature_data['feature_type'] === ProductFeatures::TEXT_SELECTBOX
        ) {
            $lang_codes = db_get_fields('SELECT DISTINCT lang_code FROM ?:product_features_values WHERE feature_id = ?i', $feature_id);

            foreach ($lang_codes as $_lang_code) {
                $product_variants = db_get_hash_multi_array(
                    'SELECT ?:product_features_values.product_id, ?:product_features_values.variant_id'
                    . ' FROM ?:product_features_values'
                    . ' LEFT JOIN ?:product_feature_variants'
                    . ' ON ?:product_features_values.variant_id = ?:product_feature_variants.variant_id'
                    . ' WHERE ?:product_features_values.feature_id = ?i AND ?:product_features_values.lang_code = ?s'
                    . ' ORDER BY ?:product_feature_variants.position',
                    ['product_id', 'variant_id'],
                    $feature_id,
                    $_lang_code
                );

                foreach ($product_variants as $product_id => $variants) {
                    if (count($variants) <= 1) {
                        continue;
                    }

                    array_shift($variants);
                    $variant_ids = array_column($variants, 'variant_id');

                    if (!$variant_ids) {
                        continue;
                    }

                    db_query(
                        'DELETE FROM ?:product_features_values WHERE product_id = ?i AND lang_code = ?s AND variant_id IN (?n)',
                        $product_id,
                        $_lang_code,
                        $variant_ids
                    );
                }
            }
        }

        // in some cases we need to update all variants,
        // not just those visible within pagination
        if (
            isset($old_feature_data['feature_type'])
            && $feature_data['feature_type'] !== $old_feature_data['feature_type']
            && $feature_data['feature_type'] === ProductFeatures::NUMBER_SELECTBOX
        ) {
            db_query(
                'UPDATE ?:product_features_values AS product_features_values '
                . 'LEFT JOIN ?:product_feature_variants AS product_feature_variants '
                . 'ON product_features_values.variant_id = product_feature_variants.variant_id '
                . 'LEFT JOIN ?:product_feature_variant_descriptions AS product_feature_variant_descriptions '
                . 'ON product_feature_variant_descriptions.variant_id = product_feature_variants.variant_id '
                . 'SET product_features_values.value_int = CAST(product_feature_variant_descriptions.variant AS SIGNED) '
                . 'WHERE product_features_values.feature_id = ?i '
                . 'AND product_features_values.lang_code = product_feature_variant_descriptions.lang_code',
                $feature_id
            );
        }

        if (
            isset($old_feature_data['feature_type'])
            && $feature_data['feature_type'] !== $old_feature_data['feature_type']
            && $old_feature_data['feature_type'] === ProductFeatures::NUMBER_SELECTBOX
        ) {
            db_query(
                'UPDATE ?:product_features_values AS product_features_values '
                . 'LEFT JOIN ?:product_feature_variants AS product_feature_variants '
                . 'ON product_features_values.variant_id = product_feature_variants.variant_id '
                . 'SET product_features_values.value_int = null '
                . 'WHERE product_features_values.feature_id = ?i',
                $feature_id
            );
        }
    }

    if ($action === 'create' || !$feature_id) {
        return $feature_id;
    }

    // Delete variants for simple features
    $old_categories = $old_feature_data
        ? fn_explode(',', $old_feature_data['categories_path'])
        : [];

    // Get sub-categories for OLD categories
    if ($old_categories) {
        $subcategories_condition = array_map(function($category_id) {
            return db_quote(
                'id_path LIKE ?l OR id_path LIKE ?l',
                $category_id . '/%',
                '%/' . $category_id . '/%'
            );
        }, $old_categories);

        $sub_cat_ids = db_get_fields(
            'SELECT category_id FROM ?:categories WHERE ?p',
            implode(' OR ', $subcategories_condition)
        );
        $old_categories = array_merge($old_categories, $sub_cat_ids);
    }

    $new_categories = isset($feature_data['categories_path'])
        ? fn_explode(',', $feature_data['categories_path'])
        : [];

    // Get sub-categories for NEW categories
    if ($new_categories) {
        $subcategories_condition = array_map(function($category_id) {
            return db_quote(
                'id_path LIKE ?l OR id_path LIKE ?l',
                $category_id . '/%',
                '%/' . $category_id . '/%'
            );
        }, $new_categories);

        $sub_cat_ids = db_get_fields(
            'SELECT category_id FROM ?:categories WHERE ?p',
            implode(' OR ', $subcategories_condition)
        );
        $new_categories = array_merge($new_categories, $sub_cat_ids);
    }

    if ($old_feature_data
        && $feature_data['feature_type'] !== $old_feature_data['feature_type']
        && (strpos($selectable_types, $feature_data['feature_type']) === false
            || strpos($selectable_types, $old_feature_data['feature_type']) === false
        )
    ) {
        $deleted_variants = fn_delete_product_feature_variants($feature_id);
    }

    // Remove features values/variants if we changed categories list
    $old_categories = array_filter($old_categories);
    sort($old_categories);
    $new_categories = array_filter($new_categories);
    sort($new_categories);

    /**
     * Executes before updating product feature right before removing feature values from products that are not present in
     * the new feature categories.
     * Allows you to prevent product feature values removal or to modify the feature data stored in the database
     *
     * @param array  $feature_data     Feature data
     * @param int    $feature_id       Feature identifier
     * @param string $lang_code        2-letters language code
     * @param array  $old_feature_data Current feature data
     * @param int[]  $old_categories   Old feature categories with all their subcategories
     * @param int[]  $new_categories   New feature categories with all their subcategories
     */
    fn_set_hook(
        'update_product_feature',
        $feature_data,
        $feature_id,
        $lang_code,
        $old_feature_data,
        $old_categories,
        $new_categories
    );

    db_query(
        'UPDATE ?:product_features SET ?u WHERE feature_id = ?i',
        $feature_data,
        $feature_id
    );
    db_query(
        'UPDATE ?:product_features_descriptions SET ?u WHERE feature_id = ?i AND lang_code = ?s',
        $feature_data,
        $feature_id,
        $lang_code
    );

    // If this feature is group, set its categories to all children
    if ($feature_data['feature_type'] === ProductFeatures::GROUP) {
        $feature_group_data = [
            'categories_path'    => !empty($feature_data['categories_path'])
                ? $feature_data['categories_path']
                : '',
            'display_on_product' => !empty($feature_data['display_on_product'])
                ? $feature_data['display_on_product']
                : '',
            'display_on_catalog' => !empty($feature_data['display_on_catalog'])
                ? $feature_data['display_on_catalog']
                : '',
            'display_on_header'  => !empty($feature_data['display_on_header'])
                ? $feature_data['display_on_header']
                : '',
        ];
        $feature_group_data = fn_filter_feature_group_data($feature_group_data);

        db_query(
            'UPDATE ?:product_features SET ?u WHERE parent_id = ?i',
            $feature_group_data,
            $feature_id
        );
    }

    if ($new_categories && $old_categories != $new_categories) {
        db_query(
            'DELETE FROM ?:product_features_values'
            . ' WHERE feature_id = ?i'
            . ' AND product_id NOT IN ('
            . 'SELECT product_id'
            . ' FROM ?:products_categories'
            . ' WHERE category_id IN (?n)'
            . ')',
            $feature_id,
            $new_categories
        );
    }

    // Disable related filters if feature status not active
    if ($feature_data['feature_type'] !== ProductFeatures::GROUP
        && isset($feature_data['status'])
        && $feature_data['status'] !== 'A'
    ) {
        fn_disable_product_feature_filters($feature_id);
    }

    /**
     * Adds additional actions after product feature updating
     *
     * @param array  $feature_data     Feature data
     * @param int    $feature_id       Feature identifier
     * @param array  $deleted_variants Deleted product feature variants identifiers
     * @param string $lang_code        2-letters language code
     */
    fn_set_hook('update_product_feature_post', $feature_data, $feature_id, $deleted_variants, $lang_code);

    return $feature_id;
}

/**
 * Updates product feature variants
 *
 * @param int $feature_id Feature identifier
 * @param array $feature_data Feature data
 * @param string $lang_code 2-letters language code
 *
 * @return array $variant_ids Feature variants identifier
 */
function fn_update_product_feature_variants($feature_id, &$feature_data, $lang_code = DESCR_SL)
{
    $variant_ids = [];

    if (!empty($feature_data['variants'])) {

        foreach ($feature_data['variants'] as $key => $variant) {
            $variant_id = fn_update_product_feature_variant($feature_id, $feature_data['feature_type'], $variant, $lang_code);

            if ($variant_id === false) {
                continue;
            }

            $variant_id = (int) $variant_id;
            $variant_ids[$key] = $variant_id;
            $feature_data['variants'][$key]['variant_id'] = $variant_id; // for addons
        }

        if (!empty($variant_ids)) {
            fn_attach_image_pairs('variant_image', 'feature_variant', 0, $lang_code, $variant_ids);
        }

        if (!empty($feature_data['original_var_ids'])) {
            $original_variant_ids = explode(',', $feature_data['original_var_ids']);
            $deleted_variants = array_diff($original_variant_ids, $variant_ids);

            fn_delete_product_feature_variants(0, $deleted_variants);
        }
    }
}

/**
 * Updates product feature variant
 *
 * @param int    $feature_id   Feature identifier
 * @param string $feature_type Feature type
 * @param array  $variant      Feature variant data
 * @param string $lang_code    Two letters language code
 *
 * @return int|bool $variant_id Feature variant identifier
 */
function fn_update_product_feature_variant($feature_id, $feature_type, $variant, $lang_code = DESCR_SL)
{
    if (empty($variant['variant']) && (!isset($variant['variant']) || $variant['variant'] !== '0')) {
        return false;
    }
    SecurityHelper::sanitizeObjectData('product_feature_variant', $variant);

    $variant['feature_id'] = $feature_id;

    /**
     * Executes at the beginning of the function, allowing you to modify the arguments passed to the function.
     *
     * @param int                       $feature_id   Feature identifier
     * @param string                    $feature_type Feature type
     * @param array<string, int|string> $variant      Feature variant data
     * @param string                    $lang_code    Two letters language code
     */
    fn_set_hook('update_product_feature_variant_pre', $feature_id, $feature_type, $variant, $lang_code);

    $feature_style = db_get_field('SELECT filter_style FROM ?:product_features WHERE feature_id = ?i', $feature_id);
    if ($feature_style === 'color' && !isset($variant['color']) && strpos($variant['variant'], '#') !== false) {
        $variant = [
            'variant'    => trim(substr($variant['variant'], 0, strpos($variant['variant'], '#'))),
            'color'      => substr($variant['variant'], strpos($variant['variant'], '#')),
            'feature_id' => $feature_id
        ];
    }

    if (isset($variant['variant_id'])) {
        $variant_id = db_get_field('SELECT variant_id FROM ?:product_feature_variants WHERE variant_id = ?i', $variant['variant_id']);
        unset($variant['variant_id']);
    }

    $fields = ['fvd.variant_id'];
    $joins = [
        'product_feature_variants' => db_quote('INNER JOIN ?:product_feature_variants fv ON fv.variant_id = fvd.variant_id')
    ];
    $conditions = [
        'feature_id' => db_quote('feature_id = ?i', $feature_id),
        'lang_code'  => db_quote('lang_code = ?s', $lang_code),
        'variant'    => db_quote('LOWER(variant) = ?s', fn_strtolower($variant['variant']))
    ];
    if (!empty($variant_id)) {
        $conditions['variant_id'] = db_quote('fvd.variant_id <> ?i', $variant_id);
    }
    $limit = db_quote('LIMIT 1');

    /**
     * Executes before select existent product feature variant by name.
     *
     * @param int                       $feature_id   Feature identifier
     * @param string                    $feature_type Feature type
     * @param array<string, int|string> $variant      Feature variant data
     * @param string                    $lang_code    Two letters language code
     * @param int                       $variant_id   Variant identifier
     * @param array<string, string>     $fields       Fields which will be got from database
     * @param array<string, string>     $joins        Prepared query for joined tables
     * @param array<string, string>     $conditions   Prepared condition which will be add to query divided by AND
     * @param string                    $limit        Limit statement
     */
    fn_set_hook('update_product_feature_variant_before_select', $feature_id, $feature_type, $variant, $variant_id, $lang_code, $fields, $joins, $conditions, $limit);

    $exists_variant = db_get_hash_single_array(
        'SELECT ?p FROM ?:product_feature_variant_descriptions AS fvd ?p WHERE ?p ?p',
        ['variant_id', 'variant_id'],
        implode(', ', $fields),
        implode(' ', $joins),
        implode(' AND ', $conditions),
        $limit
    );

    $is_duplicated = false;
    if (!empty($exists_variant)) {
        $is_duplicated = (bool) !empty($variant_id);
        $variant_id = empty($variant_id) ? reset($exists_variant) : $variant_id;
    }

    if ($is_duplicated) {
        $original_variant_name = fn_get_product_feature_variant_name($variant_id, $lang_code);
        fn_set_notification(NotificationSeverity::WARNING, __('warning'), __('variant_with_name_already_exist', [
            '[variant]' => $original_variant_name
        ]));

        return $variant_id;
    }

    /**
     * Executes after identifier of the variant was checked.
     *
     * @param int                       $feature_id      Feature identifier
     * @param string                    $feature_type    Feature type
     * @param array<string, int|string> $variant         Feature variant data
     * @param string                    $lang_code       Two letters language code
     * @param int                       $variant_id      Variant identifier
     */
    fn_set_hook('update_product_feature_variant', $feature_id, $feature_type, $variant, $lang_code, $variant_id);

    if (empty($variant_id)) {
        $variant_id = fn_add_feature_variant($feature_id, $variant);
    } else {
        db_query("UPDATE ?:product_feature_variants SET ?u WHERE variant_id = ?i", $variant, $variant_id);
        db_query("UPDATE ?:product_feature_variant_descriptions SET ?u WHERE variant_id = ?i AND lang_code = ?s", $variant, $variant_id, $lang_code);
    }

    if ($feature_type === ProductFeatures::NUMBER_SELECTBOX) {
        db_query(
            'UPDATE ?:product_features_values SET ?u WHERE variant_id = ?i AND lang_code = ?s',
            ['value_int' => $variant['variant']],
            $variant_id,
            $lang_code
        );
    }

    /**
     * Executes after variant was updated/inserted.
     *
     * @param int                       $feature_id   Feature identifier
     * @param string                    $feature_type Feature type
     * @param array<string, int|string> $variant      Feature variant data
     * @param string                    $lang_code    Two letters language code
     * @param int                       $variant_id   Variant identifier
     */
    fn_set_hook('update_product_feature_variant_post', $feature_id, $feature_type, $variant, $lang_code, $variant_id);

    return $variant_id;
}

/**
 * Add product feature variant
 *
 * @param int       $feature_id     Feature identifier
 * @param array     $variant        Feature variant data
 *
 * @return int $variant_id Feature variant identifier
 */
function fn_add_feature_variant($feature_id, $variant)
{
    /**
     * Changes variant data before adding
     *
     * @param int   $feature_id Feature identifier
     * @param array $variant    Variant data
     */
    fn_set_hook('add_feature_variant_pre', $feature_id, $variant);

    if (empty($variant['variant']) && (!isset($variant['variant']) || $variant['variant'] !== '0')) {
        return false;
    }

    SecurityHelper::sanitizeObjectData('product_feature_variant', $variant);

    $variant['feature_id'] = $feature_id;
    $variant['variant_id'] = db_query("INSERT INTO ?:product_feature_variants ?e", $variant);

    foreach (Languages::getAll() as $variant['lang_code'] => $_v) {
        db_query("INSERT INTO ?:product_feature_variant_descriptions ?e", $variant);
    }

    /**
     * Adds additional actions before category parent updating
     *
     * @param int   $feature_id Feature identifier
     * @param array $variant    Variant data
     */
    fn_set_hook('add_feature_variant_post', $feature_id, $variant);

    return $variant['variant_id'];
}

/**
 * Removes product feature
 *
 * @param int $feature_id Feature identifier
 *
 * @return boolean True if feature was successfully deleted, otherwise false
 */
function fn_delete_feature($feature_id)
{
    $feature_deleted = true;
    $can_delete = true;

    if (!fn_check_company_id('product_features', 'feature_id', $feature_id)) {
        fn_company_access_denied_notification();

        return false;
    }

    /**
     * Adds additional actions before product feature deleting
     *
     * @param int $feature_id Feature identifier
     */
    fn_set_hook('delete_feature_pre', $feature_id);

    $feature_type = fn_get_product_feature_type_by_feature_id($feature_id);

    /**
     * Adds additional actions before product feature deleting
     *
     * @param int    $feature_id   Feature identifier
     * @param string $feature_type One letter feature type
     * @param bool   $can_delete   Check permissions
     */
    fn_set_hook('delete_product_feature', $feature_id, $feature_type, $can_delete);

    if ($feature_type == ProductFeatures::GROUP) {
        $fids = db_get_fields("SELECT feature_id FROM ?:product_features WHERE parent_id = ?i", $feature_id);
        if (!empty($fids)) {
            foreach ($fids as $fid) {
                if (!fn_delete_feature($fid)) {
                    $can_delete = false;
                };
            }
        }
    }

    if (!$can_delete) {
        return false;
    }

    $affected_rows = db_query("DELETE FROM ?:product_features WHERE feature_id = ?i", $feature_id);
    db_query("DELETE FROM ?:product_features_descriptions WHERE feature_id = ?i", $feature_id);

    if ($affected_rows == 0) {
        fn_set_notification(NotificationSeverity::ERROR, __('error'), __('object_not_found', array('[object]' => __('feature'))),'','404');
        $feature_deleted = false;
    }

    $variant_ids = fn_delete_product_feature_variants($feature_id);

    $filter_ids = db_get_fields('SELECT filter_id FROM ?:product_filters WHERE feature_id = ?i', $feature_id);
    foreach ($filter_ids as $_filter_id) {
        fn_delete_product_filter($_filter_id);
    }

    /**
     * Adds additional actions after product feature deleting
     *
     * @param int   $feature_id  Deleted feature identifier
     * @param array $variant_ids Deleted feature variants
     */
    fn_set_hook('delete_feature_post', $feature_id, $variant_ids);

    return $feature_deleted;
}

/**
 * Removes feature variants
 *
 * @param int $feature_id Feature identifier
 * @param array $variant_ids Variants identifier
 * @return array $variant_ids Deleted feature variants
 */
function fn_delete_product_feature_variants($feature_id = 0, $variant_ids = array())
{
    /**
     * Adds additional actions before product feature variants deleting
     *
     * @param int   $feature_id  Deleted feature identifier
     * @param array $variant_ids Deleted feature variants
     */
    fn_set_hook('delete_product_feature_variants_pre', $feature_id, $variant_ids);

    if (!empty($feature_id)) {
        $variant_ids = db_get_fields("SELECT variant_id FROM ?:product_feature_variants WHERE feature_id = ?i", $feature_id);
        db_query("DELETE FROM ?:product_features_values WHERE feature_id = ?i", $feature_id);
    }

    if (!empty($variant_ids)) {
        db_query("DELETE FROM ?:product_features_values WHERE variant_id IN (?n)", $variant_ids);
        db_query("DELETE FROM ?:product_feature_variants WHERE variant_id IN (?n)", $variant_ids);
        db_query("DELETE FROM ?:product_feature_variant_descriptions WHERE variant_id IN (?n)", $variant_ids);
        foreach ($variant_ids as $variant_id) {
            fn_delete_image_pairs($variant_id, 'feature_variant');
        }
    }

    /**
     * Adds additional actions after product feature variants deleting
     *
     * @param int   $feature_id  Deleted feature identifier
     * @param array $variant_ids Deleted feature variants
     */
    fn_set_hook('delete_product_feature_variants_post', $feature_id, $variant_ids);

    return $variant_ids;
}

/**
 * Gets all available brands.
 *
 * @return array Found brands
 */
function fn_get_all_brands()
{
    $params = array(
        'exclude_group' => true,
        'get_descriptions' => true,
        'feature_types' => array(ProductFeatures::EXTENDED),
        'variants' => true,
        'plain' => true,
    );

    list($features) = fn_get_product_features($params, 0);

    $variants = array();

    foreach ($features as $feature) {
        if (!empty($feature['variants'])) {
            $variants = array_merge($variants, $feature['variants']);
        }
    }

    return $variants;
}

/**
 *  Gets product feature purposes sorted by position
 *
 * @return array
 */
function fn_get_product_feature_purposes()
{
    static $purposes = null;

    if ($purposes === null) {
        $purposes = (array) fn_get_schema('product_features', 'purposes');
        $purposes = fn_sort_array_by_key($purposes, 'position');

        foreach ($purposes as &$purpose) {
            $purpose['types'] = [];

            foreach ($purpose['styles_map'] as $key => $item) {
                $purpose['types'][$item['feature_type']][$key] = $item;
            }
        }
        unset($purpose);
    }

    return $purposes;
}

/**
 * Gets product feature purpose by feature type
 *
 * @param string $feature_type
 *
 * @return string|null
 */
function fn_get_product_feature_purpose_by_type($feature_type)
{
    $purposes = fn_get_product_feature_purposes();

    foreach ($purposes as $purpose => $data) {
        if (empty($data['is_core'])) {
            continue;
        }

        if (isset($data['types'][$feature_type])) {
            return $purpose;
        }
    }

    return null;
}

/**
 * Gets default product feature purpose
 *
 * @return string
 */
function fn_get_default_product_feature_purpose()
{
    $purposes = fn_get_product_feature_purposes();

    foreach ($purposes as $purpose => $data) {
        if (!empty($data['is_default'])) {
            return $purpose;
        }
    }

    $keys = array_keys($purposes);

    return reset($keys);
}

/**
 * Gets current feature data when updating it.
 *
 * @param int    $feature_id Feature identifier
 * @param string $lang_code  Two-letter language code
 * @param array  $params     Additional param for searching features
 *
 * @return array|null
 *
 * @internal
 */
function fn_get_feature_data_with_subfeatures($feature_id, $lang_code, array $params = [])
{
    list($feature_data,) = fn_get_product_features(array_merge($params, [
        'feature_id'    => $feature_id,
        'plain'         => true,
        'exclude_group' => true,
    ]), 0, $lang_code);

    if (!$feature_data) {
        return null;
    }

    $feature_data = reset($feature_data);

    if ($feature_data['feature_type'] === ProductFeatures::GROUP) {
        list($feature_data,) = fn_get_product_features(array_merge($params, [
            'parent_id' => $feature_id,
        ]), 0, $lang_code);
        $feature_data = reset($feature_data);
    }

    $feature_data['subfeatures'] = isset($feature_data['subfeatures'])
        ? $feature_data['subfeatures']
        : [];

    return $feature_data;
}

/**
 * Returns feature style by feature's purpose.
 *
 * @param string $feature_type    Feature type of current feature.
 * @param string $feature_purpose Feature purpose of current feature.
 *
 * @return string|null Feature style or null.
 */
function fn_get_product_feature_style($feature_type, $feature_purpose)
{
    $feature_style = '';
    $purposes = (array) fn_get_schema('product_features', 'purposes');

    if (empty($feature_purpose) || !isset($purposes[$feature_purpose])) {
        return $feature_style;
    }

    $styles_map = $purposes[$feature_purpose]['styles_map'];

    foreach ($styles_map as $style) {
        if ($feature_type == $style['feature_type']) {
            $feature_style = $style['feature_style'];
            break;
        }
    }

    return $feature_style;
}

/**
 * Return feature filter style by feature's purpose and feature style.
 *
 * @param string $feature_type    Feature type of current feature.
 * @param string $feature_style   Feature style of current feature.
 * @param string $feature_purpose Feature purpose of current feature.
 *
 * @return string|null Feature filter style or null.
 */
function fn_get_product_feature_filter_style($feature_type, $feature_style, $feature_purpose)
{
    $filter_style = '';
    $purposes = (array) fn_get_schema('product_features', 'purposes');

    if (empty($feature_purpose) || !isset($purposes[$feature_purpose])) {
        return $filter_style;
    }

    $styles_map = $purposes[$feature_purpose]['styles_map'];

    foreach ($styles_map as $style) {
        if ($feature_type == $style['feature_type'] && $feature_style == $style['feature_style']) {
            $filter_style = $style['filter_style'];
            break;
        }
    }
    return $filter_style;
}

/**
 * Gets feature variant name by variant identifier
 *
 * @param int    $variant_id Variant identifier
 * @param string $lang_code  Two letters language code
 *
 * @return string
 */
function fn_get_product_feature_variant_name($variant_id, $lang_code)
{
    $variant_name = db_get_field('SELECT variant FROM ?:product_feature_variant_descriptions WHERE variant_id = ?i AND lang_code = ?s', $variant_id, $lang_code);

    /**
     * Executes after getting feature variant name.
     *
     * @param int    $variant_id   Product identifier
     * @param string $lang_code    Two letters language code
     * @param string $variant_name Feature variant name
     */
    fn_set_hook('get_product_feature_variant_name_post', $variant_id, $lang_code, $variant_name);

    return $variant_name;
}

/**
 * Gets feature purposes by feature IDs.
 *
 * @param array<int> $feature_ids Feature IDs
 *
 * @return array<int, string>
 */
function fn_get_product_feature_purposes_by_feature_ids(array $feature_ids)
{
    return db_get_hash_single_array('SELECT feature_id, purpose FROM ?:product_features WHERE feature_id IN (?a)', ['feature_id', 'purpose'], $feature_ids);
}

/**
 * Gets feature types by feature IDs.
 *
 * @param array<int> $feature_ids Feature IDs
 *
 * @return array<int, string>
 */
function fn_get_product_feature_types_by_feature_ids(array $feature_ids)
{
    return db_get_hash_single_array(
        'SELECT feature_id, feature_type FROM ?:product_features WHERE feature_id IN (?a)',
        ['feature_id', 'feature_type'],
        $feature_ids
    );
}

/**
 * Gets feature type by feature ID.
 *
 * @param int $feature_id Feature ID
 *
 * @return string
 */
function fn_get_product_feature_type_by_feature_id($feature_id)
{
    $feature_type = fn_get_product_feature_types_by_feature_ids((array) $feature_id);
    $feature_type = reset($feature_type);
    return $feature_type;
}

/**
 * Checks the existence of feature values by feature's parameters.
 *
 * @param string $feature_type    Feature type of current feature.
 * @param string $feature_purpose Feature purpose of current feature.
 * @param string $feature_style   Feature style of current feature.
 * @param string $filter_style    Filter style of current feature.
 *
 * @return bool The passed values are valid.
 */
function fn_check_product_feature_by_feature_params($feature_type, $feature_purpose, $feature_style, $filter_style)
{
    $is_exist = false;
    $purposes = fn_get_schema('product_features', 'purposes');
    if (empty($feature_purpose) || !isset($purposes[$feature_purpose])) {
        return $is_exist;
    }

    $styles_map = $purposes[$feature_purpose]['styles_map'];
    foreach ($styles_map as $style) {
        $is_exist = true;

        if (
            (!empty($feature_style) && $feature_style !== $style['feature_style'])
            || (!empty($filter_style) && $filter_style !== $style['filter_style'])
            || (!empty($feature_type) && $feature_type !== $style['feature_type'])
        ) {
            $is_exist = false;
        }

        if ($is_exist) {
            break;
        }
    }

    return $is_exist;
}
