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

use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\ProductOptionTypes;
use Tygh\Enum\ProductTracking;
use Tygh\Enum\SiteArea;
use Tygh\Enum\YesNo;
use Tygh\Languages\Languages;
use Tygh\Navigation\LastView;
use Tygh\Registry;
use Tygh\Tools\SecurityHelper;
use Tygh\Enum\ProductOptionsExceptionsTypes;
use Tygh\Enum\ProductOptionsApplyOrder;

defined('BOOTSTRAP') or die('Access denied');

const OPTION_EXCEPTION_VARIANT_ANY = -1;
const OPTION_EXCEPTION_VARIANT_NOTHING = -2;

/**
 * Removes options and their variants by option identifier
 *
 * @param int $option_id Option identifier
 * @param int $pid Identifier of the product from which the option should be removed (for global options)
 *
 * @return bool True on success, false otherwise
 */
function fn_delete_product_option($option_id, $pid = 0)
{
    /**
     * Adds additional actions before product option deleting
     *
     * @param int $option_id Option identifier
     * @param int $pid       Product identifier
     */
    fn_set_hook('delete_product_option_pre', $option_id, $pid);

    $can_continue = true;
    $option_deleted = false;
    $product_id = 0;

    if (!empty($option_id)) {
        $product_link = db_get_fields('SELECT product_id FROM ?:product_global_option_links WHERE option_id = ?i AND product_id = ?i', $option_id, $pid);
        if (!empty($product_link)) {
            $_otps = db_get_row('SELECT product_id FROM ?:product_options WHERE option_id = ?i', $option_id);
        } else {
            $condition = fn_get_company_condition('?:product_options.company_id');
            $_otps = db_get_row('SELECT product_id FROM ?:product_options WHERE option_id = ?i ?p', $option_id, $condition);
        }

        if (empty($_otps)) {
            return false;
        }

        $product_id = (int) $_otps['product_id'];

        /**
         * Adds additional actions before executing delete queries
         *
         * @param int   $option_id    Option identifier
         * @param int   $pid          Product identifier for linked option
         * @param int   $product_id   Product identifier for products own option
         * @param array $product_link Product ids for linked options
         * @param bool  $can_continue Flag that allows to proceed deleting
         */
        fn_set_hook('delete_product_option_before_delete', $option_id, $pid, $product_id, $product_link, $can_continue);

        if (!$can_continue) {
            return false;
        }

        if ($pid) {
            $exceptions = db_get_hash_array('SELECT exception_id, combination FROM ?:product_options_exceptions WHERE product_id = ?i', 'exception_id', $pid);
        } else {
            $exceptions = db_get_hash_array(
                'SELECT exception_id, combination FROM ?:product_options_exceptions'
                . ' LEFT JOIN ?:product_global_option_links'
                . ' ON ?:product_options_exceptions.product_id = ?:product_global_option_links.product_id'
                . ' WHERE option_id = ?i',
                'exception_id',
                $option_id
            );
        }

        foreach ($exceptions as $exception_id => $exception) {
            $combination = unserialize($exception['combination']);

            if (isset($combination[$option_id])) {
                db_query('DELETE FROM ?:product_options_exceptions WHERE exception_id = ?i', $exception_id);
            }
        }

        if (empty($product_id) && !empty($product_link)) {
            // Linked option
            $option_description = db_get_field('SELECT option_name FROM ?:product_options_descriptions WHERE option_id = ?i AND lang_code = ?s', $option_id, CART_LANGUAGE);

            fn_delete_global_option_link($pid, $option_id);

            fn_set_notification('W', __('warning'), __('option_unlinked', array(
                '[option_name]' => $option_description
            )));
        } else {
            // Product option
            db_query('DELETE FROM ?:product_options_descriptions WHERE option_id = ?i', $option_id);
            db_query('DELETE FROM ?:product_options WHERE option_id = ?i', $option_id);
            db_query('DELETE FROM ?:product_global_option_links WHERE option_id = ?i', $option_id);
            fn_delete_product_option_variants($option_id);
        }

        $option_deleted = true;
    }

    /**
     * Adds additional actions after product option deleting
     *
     * @param int  $option_id      Option identifier
     * @param int  $pid            Product identifier
     * @param bool $option_deleted True if option was successfully deleted, false otherwise
     * @param int  $product_id     Identifier of the product from which the option should be
     *                             removed (for not global options)
     */
    fn_set_hook('delete_product_option_post', $option_id, $pid, $option_deleted, $product_id);

    return $option_deleted;
}

/**
 * Removes option variants
 *
 * @param int $option_id Option identifier: if given, all the option variants are deleted
 * @param int $variant_ids Variants identifiers: used if option_id is empty
 * @return bool Always true
 */
function fn_delete_product_option_variants($option_id = 0, $variant_ids = array())
{
    /**
     * Adds additional actions before product option variants deleting
     *
     * @param int $option_id   Option identifier: if given, all the option variants are deleted
     * @param int $variant_ids Variants identifiers: used if option_id is empty
     */
    fn_set_hook('delete_product_option_variants_pre', $option_id, $variant_ids);

    if (!empty($option_id)) {
        $_vars = db_get_fields("SELECT variant_id FROM ?:product_option_variants WHERE option_id = ?i", $option_id);
    } elseif (!empty($variant_ids)) {
        $_vars = db_get_fields("SELECT variant_id FROM ?:product_option_variants WHERE variant_id IN (?n)", $variant_ids);
    }

    if (!empty($_vars)) {
        foreach ($_vars as $v_id) {
            db_query("DELETE FROM ?:product_option_variants_descriptions WHERE variant_id = ?i", $v_id);
            fn_delete_image_pairs($v_id, 'variant_image');
        }

        db_query("DELETE FROM ?:product_option_variants WHERE variant_id IN (?n)", $_vars);
    }

    /**
     * Adds additional actions after product option variants deleting
     *
     * @param int $option_id   Option identifier: if given, all the option variants are deleted
     * @param int $variant_ids Variants identifiers: used if option_id is empty
     */
    fn_set_hook('delete_product_option_variants_post', $option_id, $variant_ids);

    return true;
}

/**
 * Gets product options
 *
 * @param array|int $product_ids     Product ID or Product IDs
 * @param string    $lang_code       2-letters language code
 * @param bool      $only_selectable Flag that forces to retreive the options with certain types (default: select, radio or checkbox)
 * @param bool      $inventory       Get only options with the inventory tracking
 * @param bool      $only_avail      Get only available options
 * @param bool      $skip_global     Get only general options, not global options, applied as link
 *
 * @return array List of product options data
 */
function fn_get_product_options($product_ids, $lang_code = CART_LANGUAGE, $only_selectable = false, $inventory = false, $only_avail = false, $skip_global = false)
{
    $condition = $_status = $join = '';
    $extra_variant_fields = '';
    $option_ids = $variants_ids = $options = [];
    $selectable_option_types = [
        ProductOptionTypes::SELECTBOX,
        ProductOptionTypes::RADIO_GROUP,
        ProductOptionTypes::CHECKBOX
    ];

    /**
     * Get product options ( at the beggining of fn_get_product_options() )
     *
     * @param array|int $product_ids             Product ID or Product IDs
     * @param string    $lang_code               2-letters language code
     * @param bool      $only_selectable         This flag forces to retreive the options with the certain types (default: select, radio or checkbox)
     * @param bool      $inventory               Get only options with the inventory tracking
     * @param bool      $only_avail              Get only available options
     * @param array     $selectable_option_types Selectable option types
     * @param bool      $skip_global             Get only general options, not global options, applied as link
     */
    fn_set_hook('get_product_options_pre', $product_ids, $lang_code, $only_selectable, $inventory, $only_avail, $selectable_option_types, $skip_global);

    if (AREA == 'C' || $only_avail == true) {
        $_status .= " AND a.status = 'A'";
    }
    if ($only_selectable == true) {
        $condition .= db_quote(" AND a.option_type IN(?a)", $selectable_option_types);
    }

    $join = db_quote(" LEFT JOIN ?:product_options_descriptions as b ON a.option_id = b.option_id AND b.lang_code = ?s ", $lang_code);
    $fields = "a.*, b.option_name, b.internal_option_name, b.option_text, b.description, b.inner_hint, b.incorrect_message, b.comment";

    /**
     * Changes request params before product options selecting
     *
     * @param string    $fields               Fields to be selected
     * @param string    $condition            String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string    $join                 String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string    $extra_variant_fields Additional variant fields to be selected
     * @param array|int $product_ids          Product ID or Product IDs
     * @param string    $lang_code            2-letters language code
     */
    fn_set_hook('get_product_options', $fields, $condition, $join, $extra_variant_fields, $product_ids, $lang_code);

    if (!empty($product_ids)) {
        $_options = db_get_hash_multi_array(
            "SELECT " . $fields
            . " FROM ?:product_options as a "
            . $join
            . " WHERE a.product_id IN (?n)" . $condition . $_status
            . " ORDER BY a.position",
            ['product_id', 'option_id'], $product_ids
        );

        if (!$skip_global) {
            $global_options = db_get_hash_multi_array(
                "SELECT c.product_id AS cur_product_id, a.*, b.option_name, b.internal_option_name, b.option_text, b.description, b.inner_hint, b.incorrect_message, b.comment"
                . " FROM ?:product_options as a"
                . " LEFT JOIN ?:product_options_descriptions as b ON a.option_id = b.option_id AND b.lang_code = ?s"
                . " LEFT JOIN ?:product_global_option_links as c ON c.option_id = a.option_id"
                . " WHERE c.product_id IN (?n) AND a.product_id = 0" . $condition . $_status
                . " ORDER BY a.position",
                ['cur_product_id', 'option_id'], $lang_code, $product_ids
            );
        }
        foreach ((array) $product_ids as $product_id) {
            $_opts = (empty($_options[$product_id]) ? [] : $_options[$product_id]) + (empty($global_options[$product_id]) ? [] : $global_options[$product_id]);
            $options[$product_id] = fn_sort_array_by_key($_opts, 'position');
        }
    } else {
        //we need a separate query for global options
        $options = db_get_hash_multi_array(
            "SELECT a.*, b.option_name, b.internal_option_name, b.option_text, b.description, b.inner_hint, b.incorrect_message, b.comment"
            . " FROM ?:product_options as a"
            . $join
            . " WHERE a.product_id = 0" . $condition . $_status
            . " ORDER BY a.position",
            ['product_id', 'option_id']
        );
    }

    foreach ($options as $product_id => $_options) {
        $option_ids = array_merge($option_ids, array_keys($_options));
    }

    if (empty($option_ids)) {
        if (is_array($product_ids)) {
            return $options;
        } else {
            return !empty($options[$product_ids]) ? $options[$product_ids] : [];
        }
    }

    $_status = ! SiteArea::isAdmin(AREA) || $only_avail ? ObjectStatuses::ACTIVE : '';

    $variant_params = [
        'get_images' => true,
        'option_id' => array_unique($option_ids),
        'status' => $_status,
    ];

    $variants = fn_get_product_option_variants($variant_params, 0, $lang_code);

    foreach ($options as $product_id => &$_options) {
        foreach ($_options as $option_id => &$_option) {
            $_option['variants'] = !empty($variants[$option_id]) ? $variants[$option_id] : [];
        }
    }

    /**
     * Get product options ( at the end of fn_get_product_options() )
     *
     * @param array|int $product_ids     Product ID or Product IDs
     * @param string    $lang_code       Language code
     * @param bool      $only_selectable This flag forces to retreive the options with the certain types (default: select, radio or checkbox)
     * @param bool      $inventory       Get only options with the inventory tracking
     * @param bool      $only_avail      Get only available options
     * @param array     $options         The resulting array of the retrieved options
     */
    fn_set_hook('get_product_options_post', $product_ids, $lang_code, $only_selectable, $inventory, $only_avail, $options);

    return is_array($product_ids) ? $options : $options[$product_ids];
}

/**
 * Returns a array of product options using some params
 *
 * @param array $params - array of params
 * @param int $items_per_page - items per page
 * @param $lang_code - language code
 * @return array ($product_options, $params, $product_options_count)
 */
function fn_get_product_global_options($params = array(), $items_per_page = 0, $lang_code = CART_LANGUAGE)
{

    /**
     * Changes params for getting product global options
     *
     * @param array  $params         Array of search params
     * @param int    $items_per_page Items per page
     * @param string $lang_code      2-letters language code
     */
    fn_set_hook('get_product_global_options_pre', $params, $items_per_page, $lang_code);

    $params = LastView::instance()->update('product_global_options', $params);

    $default_params = [
        'product_id'     => 0,
        'page'           => 1,
        'items_per_page' => $items_per_page,
        'q'              => null,
        'excluded_ids'   => null,
        'get_variants'   => false,
        'option_id'      => null,
    ];

    $params = array_merge($default_params, $params);

    $fields = [
        '?:product_options.*',
        '?:product_options_descriptions.*',
    ];

    $condition = $join = '';

    $join .= db_quote('LEFT JOIN ?:product_options_descriptions ON ?:product_options_descriptions.option_id = ?:product_options.option_id AND ?:product_options_descriptions.lang_code = ?s ', $lang_code);

    $sortings = [
        'option_name'          => 'option_name',
        'internal_option_name' => 'internal_option_name',
        'position'             => 'position',
        'status'               => 'status',
        'null'                 => 'NULL',
    ];

    $order = db_sort($params, $sortings, 'internal_option_name', 'asc');

    $params['product_id'] = !empty($params['product_id']) ? $params['product_id'] : 0;
    $condition .= db_quote(' AND ?:product_options.product_id = ?i', $params['product_id']);

    if (!empty($params['q'])) {
        $condition .= db_quote(' AND (?:product_options_descriptions.option_name LIKE ?l OR ?:product_options_descriptions.internal_option_name LIKE ?l)',
            '%' . trim($params['q']) . '%',
            '%' . trim($params['q']) . '%'
        );
    }

    if (!empty($params['excluded_ids'])) {
        $condition .= db_quote(' AND ?:product_options.option_id NOT IN (?n)', $params['excluded_ids']);
    }

    if (!empty($params['company_ids'])) {
        $condition .= db_quote(' AND ?:product_options.company_id IN (?n)', (array) $params['company_ids']);
    }

    if (!empty($params['option_id'])) {
        $condition .= db_quote(' AND ?:product_options.option_id = ?i', $params['option_id']);
    }

    /**
     * Changes SQL params before select product global options
     *
     * @param array  $params    Array of search params
     * @param array  $fields    Fields to be selected
     * @param string $condition String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string $join      String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     */
    fn_set_hook('get_product_global_options_before_select', $params, $fields, $condition, $join);

    $limit = '';
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:product_options $join WHERE 1 $condition");
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $data = db_get_array("SELECT " . implode(', ', $fields) . " FROM ?:product_options $join WHERE 1 $condition $order $limit ");

    /**
     * Changes product global options
     *
     * @param array $data   Product global options
     * @param array $params Array of search params
     */
    fn_set_hook('get_product_global_options_post', $data, $params);

    LastView::instance()->processResults('product_global_options', $data, $params);

    if (YesNo::toBool($params['get_variants'])) {
        $variants_params = [
            'get_images' => true,
        ];

        $variants = fn_get_product_option_variants($variants_params, 0, $lang_code);

        foreach ($data as &$_option) {
            $_option['variants'] = !empty($variants[$_option['option_id']]) ? $variants[$_option['option_id']] : [];
        }
    }

    return array($data, $params);
}

/**
 * Gets product option variants
 *
 * @param array<string,string> $params         Array with search parameters
 * @param int                  $items_per_page Items per page
 * @param string               $lang_code      Two-letters language code
 *
 * @return array<string,string> Product option variants
 */
function fn_get_product_option_variants(array $params, $items_per_page = 0, $lang_code = CART_LANGUAGE)
{
    /**
     * Executes before setting parameters, changes parameters for getting product option variants
     *
     * @param array  $params         Array with search parameters
     * @param int    $items_per_page Items per page
     * @param string $lang_code      Two-letters language code
     */
    fn_set_hook('get_product_option_variants_pre', $params, $items_per_page, $lang_code);

    // Set default values to input params
    $default_params = [
        'page' => 1,
        'option_id' => 0,
        'option_type' => '',
        'get_images' => false,
        'items_per_page' => $items_per_page,
        'search_query' => null,

        // An ID or list of IDs of variants that should be loaded.
        'variant_id' => null,
    ];

    $params = array_merge($default_params, $params);

    $fields = 'b.variant_name, a.*';

    $condition = $group_by = $sorting = '';

    if (!empty($params['option_id'])) {
        $option_id = (array) $params['option_id'];
        $condition .= db_quote(' AND a.option_id IN (?n)', $option_id);
    }

    if (isset($params['status']) && !empty($params['status'])) {
        $condition .= db_quote(' AND a.status=?s', $params['status']);
    }

    $join = db_quote(
        ' LEFT JOIN ?:product_option_variants_descriptions AS b ON b.variant_id = a.variant_id AND b.lang_code = ?s',
        $lang_code
    );

    $sorting = db_quote('a.position, a.variant_id');

    if (!empty($params['variant_id'])) {
        $condition .= db_quote(' AND a.variant_id IN (?n)', (array) $params['variant_id']);
    }

    if (!empty($params['search_query'])) {
        $condition .= db_quote(
            ' AND b.variant LIKE ?l',
            '%' . trim($params['search_query']) . '%'
        );
    }

    $limit = '';

    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field('SELECT COUNT(*) FROM a ?p WHERE 1 ?p', $join, $condition);

        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    /**
     * Executes before SQL query, changes SQL parameters for getting product option variants.
     *
     * @param string $fields    List of fields for retrieving
     * @param string $join      String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $condition String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string $group_by  String containing the SQL-query GROUP BY field
     * @param string $sorting   String containing the SQL-query ORDER BY clause
     * @param string $lang_code Two-letters language code
     * @param string $limit     String containing the SQL-query LIMIT clause
     * @param array  $params    Array with search parameters
     */
    fn_set_hook('get_product_option_variants', $fields, $join, $condition, $group_by, $sorting, $lang_code, $limit, $params);

    $variants = db_get_hash_multi_array(
        'SELECT ?p FROM ?:product_option_variants AS a ?p WHERE 1 ?p ORDER BY ?p ?p ?p',
        ['option_id', 'variant_id'],
        $fields,
        $join,
        $condition,
        $group_by,
        $sorting,
        $limit
    );

    if ($params['get_images']) {
        $variants_ids = [];
        foreach ($variants as $_variants) {
            $variants_ids = array_merge($variants_ids, array_keys($_variants));
        }

        $image_pairs = fn_get_image_pairs(array_unique($variants_ids), 'variant_image', 'V', true, true, $lang_code);

        foreach ($variants as &$_variants) {
            foreach ($_variants as $variant_id => &$_variant) {
                $_variant['image_pair'] = !empty($image_pairs[$variant_id]) ? reset($image_pairs[$variant_id]) : [];
            }
        }
    }

    $option_ids = array_keys($variants);
    $option_types = db_get_array(
        'SELECT option_id, option_type FROM ?:product_options WHERE option_id IN (?n)',
        $option_ids
    );

    foreach ($option_types as $_option) {
        if ($_option['option_type'] !== ProductOptionTypes::CHECKBOX || empty($variants[$_option['option_id']])) {
            continue;
        }
        foreach ($variants[$_option['option_id']] as $variant_id => $variant) {
            $variants[$_option['option_id']][$variant_id]['variant_name'] =
                (int) $variant['position'] === 0 ? __('no', [], $lang_code) : __('yes', [], $lang_code);
        }
    }

    return $variants;
}

/**
 * Returns an array of product options with values by combination
 *
 * @param string $combination Options combination code
 * @return array Options decoded from combination
 */
function fn_get_product_options_by_combination($combination)
{
    $options = array();

    /**
     * Changes product options (running before fn_get_product_options_by_combination function)
     *
     * @param string $combination Options combination code
     * @param array  $options     Array for options decoded from combination
     */
    fn_set_hook('get_product_options_by_combination_pre', $combination, $options);

    $_comb = explode('_', $combination);
    if (!empty($_comb) && is_array($_comb)) {
        $iterations = count($_comb);
        for ($i = 0; $i < $iterations; $i += 2) {
            $options[$_comb[$i]] = isset($_comb[$i + 1]) ? $_comb[$i + 1] : '';
        }
    }

    /**
     * Changes product options (running after fn_get_product_options_by_combination function)
     *
     * @param string $combination Options combination code
     * @param array  $options     options decoded from combination
     */
    fn_set_hook('get_product_options_by_combination_post', $combination, $options);

    return $options;
}

/**
 * Removes all product options from the product
 * @param int $product_id Product identifier
 */
function fn_poptions_delete_product($product_id)
{
    /**
     * Adds additional actions before delete all product option
     *
     * @param int $product_id Product identifier
     */
    fn_set_hook('poptions_delete_product_pre', $product_id);

    $option_ids = db_get_fields('SELECT option_id FROM ?:product_options WHERE product_id = ?i', $product_id);
    if (!empty($option_ids)) {
        foreach ($option_ids as $option_id) {
            fn_delete_product_option($option_id, $product_id);
        }
    }

    db_query('DELETE FROM ?:product_options_exceptions WHERE product_id = ?i', $product_id);
    db_query('DELETE FROM ?:product_global_option_links WHERE product_id = ?i', $product_id);

    /**
     * Adds additional actions after delete all product option
     *
     * @param int $product_id Product identifier
     */
    fn_set_hook('poptions_delete_product_post', $product_id);
}

/**
 * Gets product options with the selected values data
 *
 * @param int $product_id Product identifier
 * @param array $selected_options Selected opotions values
 * @param string $lang_code 2-letters language code
 * @return array List of product options with selected values
 */
function fn_get_selected_product_options($product_id, $selected_options, $lang_code = CART_LANGUAGE)
{
    /**
     * Changes params for selecting product options with selected values
     *
     * @param int    $product_id       Product identifier
     * @param array  $selected_options Selected opotions values
     * @param string $lang_code        2-letters language code
     */
    fn_set_hook('get_selected_product_options_pre', $product_id, $selected_options, $lang_code);

    $extra_variant_fields = '';
    $fields = db_quote("a.*, b.option_name, b.option_text, b.description, b.inner_hint, b.incorrect_message, b.comment, a.status");
    $condition = db_quote("(a.product_id = ?i OR c.product_id = ?i) AND a.status = 'A'", $product_id, $product_id);
    $join = db_quote("LEFT JOIN ?:product_options_descriptions as b ON a.option_id = b.option_id AND b.lang_code = ?s LEFT JOIN ?:product_global_option_links as c ON c.option_id = a.option_id", $lang_code);

    /**
     * Changes params before selecting product options
     *
     * @param string $fields               Fields to be selected
     * @param string $condition            String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string $join                 String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $extra_variant_fields Additional variant fields to be selected
     */
    fn_set_hook('get_selected_product_options_before_select', $fields, $condition, $join, $extra_variant_fields);

    $_opts = db_get_array("SELECT $fields FROM ?:product_options as a $join WHERE $condition ORDER BY a.position");
    if (is_array($_opts)) {
        $_status = (AREA == 'A') ? '' : " AND a.status = 'A'";
        foreach ($_opts as $k => $v) {
            $_vars = db_get_hash_array("SELECT a.variant_id, a.position, a.modifier, a.modifier_type, a.weight_modifier, a.weight_modifier_type, $extra_variant_fields  b.variant_name FROM ?:product_option_variants as a LEFT JOIN ?:product_option_variants_descriptions as b ON a.variant_id = b.variant_id AND b.lang_code = ?s WHERE a.option_id = ?i $_status ORDER BY a.position", 'variant_id', $lang_code, $v['option_id']);

            // Add variant names manually, if this option is "checkbox"
            if ($v['option_type'] == 'C' && !empty($_vars)) {
                foreach ($_vars as $variant_id => $variant) {
                    $_vars[$variant_id]['variant_name'] = $variant['position'] == 0 ? __('no') : __('yes');
                }
            }

            $_opts[$k]['value'] = (isset($selected_options[$v['option_id']])) ? $selected_options[$v['option_id']] : '';
            $_opts[$k]['variants'] = $_vars;
        }

    }

    /**
     * Changes selected product options
     *
     * @param array  $_opts            Selected product options
     * @param int    $product_id       Product identifier
     * @param array  $selected_options Selected opotions values
     * @param string $lang_code        2-letters language code
     */
    fn_set_hook('get_selected_product_options_post', $_opts, $product_id, $selected_options, $lang_code);

    return $_opts;
}

/**
 * Applies option modifiers to product price or weight.
 *
 * @param array     $selected_options   The list of selected option variants as option_id => variant_id
 * @param float|int $base_value         Base price or weight value
 * @param string    $type               Calculation type (P - price or W - weight)
 * @param array     $stored_options     The list of product options stored in the order. This list is used for order management.
 * @param array     $extra              Extra data

 * @return float|int New base value after applying modifiers
 */
function fn_apply_options_modifiers($selected_options, $base_value, $type, $stored_options = array(), $extra = array())
{
    $selected_options = (array) $selected_options;
    $modifiers = array();

    if ($type === 'P') {
        $fields = 'a.modifier, a.modifier_type';
    } else {
        $fields = 'a.weight_modifier as modifier, a.weight_modifier_type as modifier_type';
    }

    /**
     * Apply option modifiers (at the beginning of the fn_apply_options_modifiers())
     *
     * @param array  $selected_options The list of selected option variants as option_id => variant_id
     * @param mixed  $base_value       Base value
     * @param array  $stored_options   The list of product options stored in the order.
     * @param array  $extra            Extra data
     * @param string $fields           String of comma-separated SQL fields to be selected in an SQL-query
     * @param string $type             Calculation type (price or weight)
     */
    fn_set_hook('apply_option_modifiers_pre', $selected_options, $base_value, $stored_options, $extra, $fields, $type);

    $orig_value = $base_value;

    if (!empty($stored_options)) {
        foreach ($stored_options as $key => $item) {
            // Exclude disabled (Forbidden) options
            if (empty($item['value'])) {
                unset($stored_options[$key]);
                continue;
            }

            if (ProductOptionTypes::isSelectable($item['option_type'])
                && isset($selected_options[$item['option_id']])
                && $selected_options[$item['option_id']] == $item['value']
            ) {
                $modifiers[] = array(
                    'value' => $item['modifier'],
                    'type' => $item['modifier_type']
                );
            }
        }
    } else {
        $modifiers = fn_get_option_modifiers_by_selected_options($selected_options, $type, $fields);
    }

    foreach ($modifiers as $modifier) {
        if ($modifier['type'] === 'A') { // Absolute
            $base_value += floatval($modifier['value']);
        } else { // Percentage
            $base_value += floatval($modifier['value']) * $orig_value / 100;
        }
    }

    $base_value = ($base_value > 0) ? $base_value : 0;

    /**
     * Apply option modifiers (at the end of the fn_apply_options_modifiers())
     *
     * @param array  $selected_options The list of selected option variants as option_id => variant_id
     * @param mixed  $base_value       Base value
     * @param string $type             Calculation type (price or weight)
     * @param array  $stored_options   The list of product options stored in the order.
     * @param mixed  $orig_value       Original base value
     * @param string $fields           String of comma-separated SQL fields to be selected in an SQL-query
     * @param array  $extra            Extra data
     */
    fn_set_hook('apply_option_modifiers_post', $selected_options, $base_value, $type, $stored_options, $orig_value, $fields, $extra);

    return $base_value;
}

/**
 * Returns selected product options.
 * For options wich type is checkbox function gets translation from langvars 'no' and 'yes' and return it as variant_name.
 *
 * @param array  $selected_options Options as option_id => selected_variant_id.
 * @param string $lang_code        2digits language code.
 *
 * @return array Array of associative arrays wich contain options data.
 */
function fn_get_selected_product_options_info($selected_options, $lang_code = CART_LANGUAGE)
{
    /**
     * Get selected product options info (at the beginning of the fn_get_selected_product_options_info())
     *
     * @param array  $selected_options Selected options
     * @param string $lang_code        Language code
     */
    fn_set_hook('get_selected_product_options_info_pre', $selected_options, $lang_code);

    if (empty($selected_options) || !is_array($selected_options)) {
        return array();
    }
    $result = array();
    foreach ($selected_options as $option_id => $variant_id) {
        $_opts = db_get_row(
            "SELECT a.*, b.option_name, b.option_text, b.description, b.inner_hint, b.incorrect_message, b.internal_option_name " .
            "FROM ?:product_options as a LEFT JOIN ?:product_options_descriptions as b ON a.option_id = b.option_id AND b.lang_code = ?s " .
            "WHERE a.option_id = ?i ORDER BY a.position",
            $lang_code, $option_id
        );

        if (empty($_opts)) {
            continue;
        }
        $_vars = array();
        if (strpos('SRC', $_opts['option_type']) !== false) {
            $_vars = db_get_row(
                "SELECT a.modifier, a.modifier_type, a.position, b.variant_name FROM ?:product_option_variants as a " .
                "LEFT JOIN ?:product_option_variants_descriptions as b ON a.variant_id = b.variant_id AND b.lang_code = ?s " .
                "WHERE a.variant_id = ?i ORDER BY a.position",
                $lang_code, $variant_id
            );
        }

        if ($_opts['option_type'] == 'C') {
            $_vars['variant_name'] = (empty($_vars['position'])) ? __('no', '', $lang_code) : __('yes', '', $lang_code);
        } elseif ($_opts['option_type'] == 'I' || $_opts['option_type'] == 'T') {
            $_vars['variant_name'] = $variant_id;
        } elseif (!isset($_vars['variant_name'])) {
            $_vars['variant_name'] = '';
        }

        $_vars['value'] = $variant_id;

        $result[] = fn_array_merge($_opts ,$_vars);
    }

    /**
     * Get selected product options info (at the end of the fn_get_selected_product_options_info())
     *
     * @param array  $selected_options Selected options
     * @param string $lang_code        Language code
     * @param array  $result           List of the option info arrays
     */
    fn_set_hook('get_selected_product_options_info_post', $selected_options, $lang_code, $result);

    return $result;
}

/**
 * Gets default product options
 *
 * @param int                                $product_id Product identifier
 * @param bool                               $get_all    Whether to get all the default options or not
 * @param array<array-key, array|int|string> $product    Product data
 * @param bool                               $only_avail Only available options
 *
 * @return array The resulting array
 */
function fn_get_default_product_options($product_id, $get_all = false, array $product = [], $only_avail = false)
{
    $result = $default = $exceptions = $product_options = array();
    $selectable_option_types = array('S', 'R', 'C');

    /**
     * Get default product options ( at the beginning of fn_get_default_product_options() )
     *
     * @param integer $product_id Product id
     * @param bool $get_all Whether to get all the default options or not
     * @param array $product Product data
     * @param array $selectable_option_types Selectable option types
     */
    fn_set_hook('get_default_product_options_pre', $product_id, $get_all, $product, $selectable_option_types);

    $exceptions = fn_get_product_exceptions($product_id, true);

    $exceptions_type = empty($product['exceptions_type'])
        ? db_get_field('SELECT exceptions_type FROM ?:products WHERE product_id = ?i', $product_id)
        : $product['exceptions_type'];

    $exceptions_type = fn_normalize_product_overridable_field_value('exceptions_type', $exceptions_type);

    if (!empty($product['product_options'])) {
        //filter out only selectable options
        foreach ($product['product_options'] as $option_id => $option) {
            if (in_array($option['option_type'], $selectable_option_types)) {
                $product_options[$option_id] = $option;
            }
        }
    } else {
        $product_options = fn_get_product_options($product_id, CART_LANGUAGE, true, false, $only_avail);
    }

    if (!empty($product_options)) {
        foreach ($product_options as $option_id => $option) {
            if (!empty($option['variants'])) {
                $default[$option_id] = key($option['variants']);
                foreach ($option['variants'] as $variant_id => $variant) {
                    $options[$option_id][$variant_id] = true;
                }
            }
        }
    } else {
        return array();
    }

    unset($product_options);

    if (empty($exceptions)) {
        return $default;
    }

    if ($exceptions_type === ProductOptionsExceptionsTypes::FORBIDDEN) {
        if (!empty($options)) {
            // Forbidden combinations
            $_options = array_keys($options);
            $_variants = array_values($options);
            if (!empty($_variants)) {
                foreach ($_variants as $key => $variants) {
                    $_variants[$key] = array_keys($variants);
                }
            }

            list($result) = fn_get_allowed_options_combination($_options, $_variants, [], 0, $exceptions, []);
        }
    } else {
        // Allowed combinations
        $result = array();
        $exception = reset($exceptions);
        foreach ($exception as $option_id => $variant_id) {
            if (isset($options, $options[$option_id][$variant_id])) {
                $result[$option_id] = $variant_id;
            } elseif ($variant_id == OPTION_EXCEPTION_VARIANT_ANY) {
                $result[$option_id] = isset($options, $options[$option_id]) ? key($options[$option_id]) : '';
            }
        }

        if (isset($options)) {
            $_opt = array_diff_key($options, $result);
            foreach ($_opt as $option_id => $variants) {
                $result[$option_id] = key($variants);
            }
        }
    }

    /**
     * Get default product options ( at the end of fn_get_default_product_options() )
     *
     * @param integer $product_id Product id
     * @param bool $get_all Whether to get all the default options or not
     * @param array $product Product data
     * @param array $result The resulting array
     */
    fn_set_hook('get_default_product_options_post', $product_id, $get_all, $product, $result);

    return empty($result) ? $default : $result;
}

/**
 * Gets all possible options combinations
 *
 * @param array $options Options identifiers
 * @param array $variants Options variants identifiers in the order according to the $options parameter
 * @return array Combinations
 */
function fn_get_options_combinations($options, $variants)
{
    $combinations = array();

    // Take first option
    $options_key = array_keys($options);
    $variant_number = reset($options_key);
    $option_id = $options[$variant_number];

    // Remove current option
    unset($options[$variant_number]);

    // Get combinations for other options
    $sub_combinations = !empty($options) ? fn_get_options_combinations($options, $variants) : array();

    if (!empty($variants[$variant_number])) {
        // run through variants
        foreach ($variants[$variant_number] as $variant) {
            if (!empty($sub_combinations)) {
                // add current variant to each subcombination
                foreach ($sub_combinations as $sub_combination) {
                    $sub_combination[$option_id] = $variant;
                    $combinations[] = $sub_combination;
                }
            } else {
                $combinations[] = array(
                    $option_id => $variant
                );
            }
        }
    } else {
        $combinations = $sub_combinations;
    }

    return  $combinations;
}

/**
 * Gets all combinations of options stored in exceptions
 *
 * @param int  $product_id Product ID
 * @param bool $short_list Flag determines if exceptions list should be returned in short format
 *
 * @return array
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
 * @phpcsSuppress SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
 */
function fn_get_product_exceptions($product_id, $short_list = false)
{
    static $products_exceptions = [];

    if (!isset($products_exceptions[$product_id]) || !SiteArea::isStorefront(AREA)) {
        $products_exceptions[$product_id] = db_get_array(
            'SELECT * FROM ?:product_options_exceptions WHERE product_id = ?i ORDER BY exception_id',
            $product_id
        );
    }

    $exceptions = $products_exceptions[$product_id];

    /**
     * Changes params before getting product exceptions
     *
     * @param int  $product_id Product identifier
     * @param bool $short_list Flag determines if exceptions list should be returned in short format
     */
    fn_set_hook('get_product_exceptions_pre', $product_id, $short_list);

    foreach ($exceptions as $k => $v) {
        $exceptions[$k]['combination'] = unserialize($v['combination']);

        if ($short_list) {
            $exceptions[$k] = $exceptions[$k]['combination'];
        }
    }

    /**
     * Changes product exceptions data
     *
     * @param int   $product_id Product identifier
     * @param array $exceptions Exceptions data
     * @param bool  $short_list Flag determines if exceptions list should be returned in short format
     */
    fn_set_hook('get_product_exceptions_post', $product_id, $exceptions, $short_list);

    return $exceptions;
}

/**
 * Gets product options exception data
 * @param int $exception_id Exception ID
 * @return array Exception data
 */
function fn_get_product_exception_data($exception_id)
{
    /**
     * Changes params before getting product exception data
     *
     * @param int $exception_id Exception ID
     */
    fn_set_hook('get_product_exception_data_pre', $product_id);

    $exception_data = db_get_row('SELECT * FROM ?:product_options_exceptions WHERE exception_id = ?i', $exception_id);
    $exception_data['combination'] = unserialize($exception_data['combination']);

    /**
     * Changes product exception data
     *
     * @param int   $exception_id   Exception ID
     * @param array $exception_data Exception data
     */
    fn_set_hook('get_product_exception_data_pre', $product_id, $exception_data);

    return $exception_data;
}

//
// Returns exception_id if such combination already exists
//
function fn_check_combination($combinations, $product_id)
{
    /**
     * Changes params before checking combination
     *
     * @param array $combinations Combinations data
     * @param int   $product_id   Product identifier
     */
    fn_set_hook('check_combination_pre', $combinations, $product_id);

    $exceptions = fn_get_product_exceptions($product_id);

    $exception_id = 0;

    if (!empty($exceptions)) {
        foreach ($exceptions as $k => $v) {
            $temp = $v['combination'];
            foreach ($combinations as $key => $value) {
                if ((in_array($value, $temp)) && ($temp[$key] == $value)) {
                    unset($temp[$key]);
                }
            }
            if (empty($temp)) {
                $exception_id = $v['exception_id'];
                break;
            }
        }
    }

    /**
     * Changes params after checking combination
     *
     * @param boolean $exception_id Flag determines if combination exists
     * @param array   $combinations Combinations data
     * @param int     $product_id   Product identifier
     */
    fn_set_hook('check_combination_post', $exception_id, $combinations, $product_id);

    return $exception_id;
}

//
// Updates options exceptions using product_id;
//
function fn_recalculate_exceptions($product_id)
{
    $result = false;
    if ($product_id) {
        $exceptions = fn_get_product_exceptions($product_id);
        /**
         * Adds additional actions before product exceptions update
         *
         * @param int $product_id Product identifier
         * @param array $exceptions
         */
        fn_set_hook('update_exceptions_pre', $product_id, $exceptions);
        if (!empty($exceptions)) {
            db_query("DELETE FROM ?:product_options_exceptions WHERE product_id = ?i", $product_id);
            foreach ($exceptions as $k => $v) {
                $_options_order = db_get_fields("SELECT a.option_id FROM ?:product_options as a LEFT JOIN ?:product_global_option_links as b ON a.option_id = b.option_id WHERE a.product_id = ?i OR b.product_id = ?i ORDER BY position", $product_id, $product_id);
                if (empty($_options_order)) {
                    return false;
                }
                $combination  = array();
                foreach ($_options_order as $option) {
                    if (!empty($v['combination'][$option])) {
                        $combination[$option] = $v['combination'][$option];
                    } else {
                        $combination[$option] = OPTION_EXCEPTION_VARIANT_ANY;
                    }
                }
                $_data = array(
                    'product_id' => $product_id,
                    'exception_id' => $v['exception_id'],
                    'combination' => serialize($combination),
                );
                db_query("INSERT INTO ?:product_options_exceptions ?e", $_data);
            }
            $result = true;
        }
        /**
         * Adds additional actions after product exceptions update
         *
         * @param int $product_id Product identifier
         * @param array $exceptions
         */
        fn_set_hook('update_exceptions_post', $product_id, $exceptions);
    }

    return $result;
}

/**
 * Updates exception data
 *
 * @param array $exception_data Exception data
 * @param int $exception_id Exception ID
 * @return bool true if updated
 */
function fn_update_exception($exception_data, $exception_id = 0)
{
    /**
     * Changes params before updating exception
     *
     * @param array $exception_data Exception data
     * @param int   $exception_id   Exception ID
     */
    fn_set_hook('update_exception_pre', $exception_data, $exception_id);

    if (empty($exception_id)) {
        $exception_id = fn_check_combination($exception_data['combination'], $exception_data['product_id']);

        if (empty($exception_id)) {
            $exception_id = db_query('INSERT INTO ?:product_options_exceptions ?e', array(
                'product_id' => $exception_data['product_id'],
                'combination' => serialize($exception_data['combination']),
            ));
        } else {
            fn_set_notification('W', __('warning'), __('exception_exist'), 'K', 'exception_exist');
        }
    } else {
        $exception_data['combination'] = serialize($exception_data['combination']);
        db_query("UPDATE ?:product_options_exceptions SET ?u WHERE exception_id = ?i", $exception_data, $exception_id);
    }

    /**
     * Adds additional actions afrer updating exception
     *
     * @param array $exception_data Exception data
     * @param int   $exception_id   Exception ID
     */
    fn_set_hook('update_exception_post', $exception_data, $exception_id);

    return $exception_id;
}

//
// Clone exceptions
//
function fn_clone_options_exceptions(&$exceptions, $old_opt_id, $old_var_id, $new_opt_id, $new_var_id)
{
    /**
     * Adds additional actions before options exceptions clone
     *
     * @param array $exceptions Exceptions array
     * @param int   $old_opt_id Old option identifier
     * @param int   $old_var_id Old variant identifier
     * @param int   $new_opt_id New option identifier
     * @param int   $new_var_id New variant identifier
     */
    fn_set_hook('clone_options_exceptions_pre', $exceptions, $old_opt_id, $old_var_id, $new_opt_id, $new_var_id);

    foreach ($exceptions as $key => $value) {
        foreach ($value['combination'] as $option => $variant) {
            if ($option == $old_opt_id) {
                $exceptions[$key]['combination'][$new_opt_id] = $variant;
                unset($exceptions[$key]['combination'][$option]);

                if ($variant == $old_var_id) {
                    $exceptions[$key]['combination'][$new_opt_id] = $new_var_id;
                }
            }
            if ($variant == $old_var_id) {
                $exceptions[$key]['combination'][$option] = $new_var_id;
            }
        }
    }

    /**
     * Adds additional actions after options exceptions clone
     *
     * @param array $exceptions Exceptions array
     * @param int   $old_opt_id Old option identifier
     * @param int   $old_var_id Old variant identifier
     * @param int   $new_opt_id New option identifier
     * @param int   $new_var_id New variant identifier
     */
    fn_set_hook('clone_options_exceptions_post', $exceptions, $old_opt_id, $old_var_id, $new_opt_id, $new_var_id);
}

/**
 * Deletes options exception
 *
 * @param int $exception_id Exception ID
 * @return bool true
 */
function fn_delete_exception($exception_id)
{
    /**
     * Makes additional actions before deleting exception
     *
     * @param int $exception_id Exception ID
     */
    fn_set_hook('delete_exception_pre', $exception_id);

    db_query("DELETE FROM ?:product_options_exceptions WHERE exception_id = ?i", $exception_id);

    return true;
}

/**
 * This function clones options to product from a product or from a global option
 *
 * @param int         $from_product_id       Identifier of product from that options are copied
 * @param int         $to_product_id         Identifier of product to that options are copied
 * @param int|boolean $from_global_option_id Identifier of the global option or false (if options are copied from product)
 */
function fn_clone_product_options($from_product_id, $to_product_id, $from_global_option_id = false)
{
    /**
     * Adds additional actions before product options clone
     *
     * @param int         $from_product_id       Identifier of product from that options are copied
     * @param int         $to_product_id         Identifier of product to that options are copied
     * @param int/boolean $from_global_option_id Identifier of the global option or false (if options are copied from product)
     */
    fn_set_hook('clone_product_options_pre', $from_product_id, $to_product_id, $from_global_option_id);

    // Get all product options assigned to the product
    $id_condition = (empty($from_global_option_id))
        ? db_quote('product_id = ?i', $from_product_id)
        : db_quote('option_id = ?i', $from_global_option_id);
    $data = db_get_array('SELECT * FROM ?:product_options WHERE ?p', $id_condition);
    $linked = db_get_field('SELECT COUNT(option_id) FROM ?:product_global_option_links WHERE product_id = ?i', $from_product_id);

    if (!empty($data) || !empty($linked)) {
        // Get all exceptions for the product
        if (!empty($from_product_id)) {
            $exceptions = fn_get_product_exceptions($from_product_id);
        }

        // Fill array of options for linked global options options
        $change_options = $change_variants = array();

        // If global option are linked then ids will be the same
        $change_options = db_get_hash_single_array("SELECT option_id FROM ?:product_global_option_links WHERE product_id = ?i", array('option_id', 'option_id'), $from_product_id);
        if (!empty($change_options)) {
            foreach ($change_options as $value) {
                $change_variants = fn_array_merge(db_get_hash_single_array("SELECT variant_id FROM ?:product_option_variants WHERE option_id = ?i", array('variant_id', 'variant_id'), $value), $change_variants, true);
            }
        }

        foreach ($data as $option_data) {
            // Clone main data
            $option_id = $option_data['option_id'];
            $option_data['product_id'] = $to_product_id;

            if (fn_allowed_for('ULTIMATE') || fn_allowed_for('MULTIVENDOR')) {
                $product_company_id = db_get_field("SELECT company_id FROM ?:products WHERE product_id = ?i", $to_product_id);
                $option_data['company_id'] = Registry::ifGet('runtime.company_id', $product_company_id);
            } else {
                $option_data['company_id'] = Registry::get('runtime.company_id');
            }

            unset($option_data['option_id']);
            $new_option_id = db_query("INSERT INTO ?:product_options ?e", $option_data);

            if (fn_allowed_for('ULTIMATE')) {
                fn_ult_share_product_option($new_option_id, $to_product_id);
            }

            // Clone descriptions
            $_data = db_get_array("SELECT * FROM ?:product_options_descriptions WHERE option_id = ?i", $option_id);
            foreach ($_data as $option_description) {
                $option_description['option_id'] = $new_option_id;
                db_query("INSERT INTO ?:product_options_descriptions ?e", $option_description);
            }

            $change_options[$option_id] = $new_option_id;
            // Clone variants if exists
            if ($option_data['option_type'] == 'S' || $option_data['option_type'] == 'R' || $option_data['option_type'] == 'C') {
                $_data = db_get_array("SELECT * FROM ?:product_option_variants WHERE option_id = ?i", $option_id);

                foreach ($_data as $option_description) {
                    $variant_id = $option_description['variant_id'];
                    $option_description['option_id'] = $new_option_id;
                    unset($option_description['variant_id']);
                    $new_variant_id = db_query("INSERT INTO ?:product_option_variants ?e", $option_description);

                    // Clone Exceptions
                    if (!empty($exceptions)) {
                        fn_clone_options_exceptions($exceptions, $option_id, $variant_id, $new_option_id, $new_variant_id);
                    }

                    $change_variants[$variant_id] = $new_variant_id;

                    // Clone descriptions
                    $__data = db_get_array("SELECT * FROM ?:product_option_variants_descriptions WHERE variant_id = ?i", $variant_id);
                    foreach ($__data as $option_variant_description) {
                        $option_variant_description['variant_id'] = $new_variant_id;
                        db_query("INSERT INTO ?:product_option_variants_descriptions ?e", $option_variant_description);
                    }

                    // Clone variant images
                    fn_clone_image_pairs($new_variant_id, $variant_id, 'variant_image');
                }
                unset($_data, $__data);
            }
            /**
             * Adds additional actions after cloning each product option
             *
             * @param int         $from_product_id       Identifier of product from that options are copied
             * @param int         $to_product_id         Identifier of product to that options are copied
             * @param int|boolean $from_global_option_id Identifier of the global option or false (if options are copied from product)
             * @param array       $option_data           Product option data
             * @param array       $change_options        Links old options to the new ones via ids
             * @param array       $change_variants       Links old variants to the new ones via ids
             */
            fn_set_hook('clone_product_option_post', $from_product_id, $to_product_id, $from_global_option_id, $option_data, $change_options, $change_variants);
        }

        if (!empty($exceptions)) {
            foreach ($exceptions as $option_data) {
                $_data = [
                    'product_id'  => $to_product_id,
                    'combination' => serialize($option_data['combination']),
                ];
                db_query('INSERT INTO ?:product_options_exceptions ?e', $_data);
            }
        }
    }

    /**
     * Adds additional actions after product options clone
     *
     * @param int         $from_product_id       Identifier of product from that options are copied
     * @param int         $to_product_id         Identifier of product to that options are copied
     * @param int/boolean $from_global_option_id Identifier of the global option or false (if options are copied from product)
     * @param array       $change_options        Links old options to the new ones via ids
     * @param array       $change_variants       Links old variants to the new ones via ids
     */
    fn_set_hook('clone_product_options_post', $from_product_id, $to_product_id, $from_global_option_id, $change_options, $change_variants);
}

/**
 * Constructs a string in format option1_variant1_option2_variant2...
 *
 * @param array $product_options
 * @return string
 */
function fn_get_options_combination($product_options)
{
    /**
     * Changes params for generating options combination
     *
     * @param array $product_options Array with selected options values
     */
    fn_set_hook('get_options_combination_pre', $product_options);

    if (empty($product_options) && !is_array($product_options)) {
        return '';
    }

    $combination = '';
    foreach ($product_options as $option => $variant) {
        $combination .= $option . '_' . $variant . '_';
    }
    $combination = trim($combination, '_');

    /**
     * Changes options combination
     *
     * @param array  $product_options Array with selected options values
     * @param string $combination     Generated combination
     */
    fn_set_hook('get_options_combination_post', $product_options, $combination);

    return $combination;
}

/**
 * Updates product option
 *
 * @param array  $option_data Option data array
 * @param int    $option_id   Option ID (empty if we're adding the option)
 * @param string $lang_code   Language code to add/update option for
 *
 * @return int ID of the added/updated option
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_update_product_option(array $option_data, $option_id = 0, $lang_code = DESCR_SL)
{
    /**
     * Changes parameters before update option data
     *
     * @param array  $option_data Option data
     * @param int    $option_id   Option identifier
     * @param string $lang_code   Two-letter language code (e.g. 'en', 'ru', etc.)
     */
    fn_set_hook('update_product_option_pre', $option_data, $option_id, $lang_code);

    SecurityHelper::sanitizeObjectData('product_option', $option_data);

    if (empty($option_id)) {
        $action = 'create';

        if (empty($option_data['internal_option_name']) && !empty($option_data['option_name'])) {
            $option_data['internal_option_name'] = $option_data['option_name'];
        }

        if (empty($option_data['option_name']) && !empty($option_data['internal_option_name'])) {
            $option_data['option_name'] = $option_data['internal_option_name'];
        }

        if (!empty($option_data['is_global'])) {
            $product_id = $option_data['product_id'];
            $option_data['product_id'] = 0;
        }
        $option_data['option_id'] = $option_id = db_query('INSERT INTO ?:product_options ?e', $option_data);

        foreach (Languages::getAll() as $option_data['lang_code'] => $_v) {
            db_query("INSERT INTO ?:product_options_descriptions ?e", $option_data);
        }

        $create = true;
        if (!empty($option_data['is_global']) && !empty($product_id)) {
            fn_add_global_option_link($product_id, $option_data['option_id']);
        }
        // Update option
    } else {
        $action = 'update';

        if (fn_allowed_for('ULTIMATE') && !empty($option_data['product_id']) && fn_ult_is_shared_product($option_data['product_id']) == 'Y') {
            $product_company_id = db_get_field('SELECT company_id FROM ?:products WHERE product_id = ?i', $option_data['product_id']);
            $option_id = fn_ult_update_shared_product_option($option_data, $option_id, Registry::ifGet('runtime.company_id', $product_company_id), $lang_code);

            if (Registry::get('runtime.company_id') && Registry::get('runtime.company_id') != $product_company_id) {
                $deleted_variants = array();
                fn_set_hook('update_product_option_post', $option_data, $option_id, $deleted_variants, $lang_code);

                return $option_id;
            }
        }
        db_query("UPDATE ?:product_options SET ?u WHERE option_id = ?i", $option_data, $option_id);
        db_query("UPDATE ?:product_options_descriptions SET ?u WHERE option_id = ?i AND lang_code = ?s", $option_data, $option_id, $lang_code);
    }

    if (fn_allowed_for('ULTIMATE')) {
        // options of shared product under the shared store hasn't a company_id. No necessary for updating.
        if (!empty($option_data['company_id'])) {
            fn_ult_update_share_object($option_id, 'product_options', $option_data['company_id']);
        }

        if (!empty($option_data['product_id'])) {
            fn_ult_share_product_option($option_id, $option_data['product_id']);
        }
    }

    if (!empty($option_data['variants'])) {
        $var_ids = [];
        foreach ($option_data['variants'] as &$variant) {
            SecurityHelper::sanitizeObjectData('product_option_variant', $variant);
        }
        unset($variant);

        // Generate special variants structure for checkbox (2 variants, 1 hidden)
        if ($option_data['option_type'] == 'C') {
            $option_data['variants'] = array_slice($option_data['variants'], 0, 1); // only 1 variant should be here
            reset($option_data['variants']);
            $_k = key($option_data['variants']);
            $option_data['variants'][$_k]['position'] = 1; // checked variant
            $v_id = db_get_field("SELECT variant_id FROM ?:product_option_variants WHERE option_id = ?i AND position = 0", $option_id);
            $option_data['variants'][] = array ( // unchecked variant
                'position' => 0,
                'variant_id' => $v_id
            );
        }

        $variant_images = array();
        foreach ($option_data['variants'] as $k => $v) {
            if ((!isset($v['variant_name']) || $v['variant_name'] == '') && $option_data['option_type'] != 'C') {
                continue;
            }

            if ($action == 'create') {
                unset($v['variant_id']);
            }

            // Update product options variants
            if (isset($v['modifier'])) {
                $v['modifier'] = floatval($v['modifier']);
                if (floatval($v['modifier']) > 0) {
                    $v['modifier'] = '+' . $v['modifier'];
                }
            }

            if (isset($v['weight_modifier'])) {
                $v['weight_modifier'] = floatval($v['weight_modifier']);
                if (floatval($v['weight_modifier']) > 0) {
                    $v['weight_modifier'] = '+' . $v['weight_modifier'];
                }
            }

            $v['option_id'] = $option_id;

            if (empty($v['variant_id']) || (!empty($v['variant_id']) && !db_get_field("SELECT variant_id FROM ?:product_option_variants WHERE variant_id = ?i", $v['variant_id']))) {
                $v['variant_id'] = db_query("INSERT INTO ?:product_option_variants ?e", $v);
                foreach (Languages::getAll() as $v['lang_code'] => $_v) {
                    db_query("INSERT INTO ?:product_option_variants_descriptions ?e", $v);
                }
            } else {
                db_query("UPDATE ?:product_option_variants SET ?u WHERE variant_id = ?i", $v, $v['variant_id']);
                db_query("UPDATE ?:product_option_variants_descriptions SET ?u WHERE variant_id = ?i AND lang_code = ?s", $v, $v['variant_id'], $lang_code);
            }

            $var_ids[] = $v['variant_id'];

            if ($option_data['option_type'] == 'C') {
                fn_delete_image_pairs($v['variant_id'], 'variant_image'); // force deletion of variant image for "checkbox" option
            } else {
                $variant_images[$k] = $v['variant_id'];
            }
        }

        if ($option_data['option_type'] != 'C' && !empty($variant_images)) {
            fn_attach_image_pairs('variant_image', 'variant_image', 0, $lang_code, $variant_images);
        }

        // Delete obsolete variants
        $condition = !empty($var_ids) ? db_quote('AND variant_id NOT IN (?n)', $var_ids) : '';
        $deleted_variants = db_get_fields("SELECT variant_id FROM ?:product_option_variants WHERE option_id = ?i $condition", $option_id, $var_ids);
        if (!empty($deleted_variants)) {
            db_query("DELETE FROM ?:product_option_variants WHERE variant_id IN (?n)", $deleted_variants);
            db_query("DELETE FROM ?:product_option_variants_descriptions WHERE variant_id IN (?n)", $deleted_variants);
            foreach ($deleted_variants as $v_id) {
                fn_delete_image_pairs($v_id, 'variant_image');
            }
        }
    }

    // Rebuild exceptions
    if (!empty($create) && !empty($option_data['product_id'])) {
        fn_recalculate_exceptions($option_data['product_id']);
    }

    /**
     * Update product option (running after fn_update_product_option() function)
     *
     * @param array  $option_data      Array with option data
     * @param int    $option_id        Option identifier
     * @param array  $deleted_variants Array with deleted variants ids
     * @param string $lang_code        Language code to add/update option for
     */
    fn_set_hook('update_product_option_post', $option_data, $option_id, $deleted_variants, $lang_code);

    return $option_id;
}

/**
 * Gets first allowed options combination for a product.
 *
 * @param array $options                Product options
 * @param array $variants               Options variants
 * @param array $string                 Array of combinations values
 * @param int   $iteration              Iteration level
 * @param array $exceptions             Options exceptions
 * @param array $inventory_combinations Inventory combinations
 *
 * @return array Options combination: keys are option IDs, values are variants
 */
function fn_get_allowed_options_combination($options, $variants, $string, $iteration, $exceptions, $inventory_combinations)
{
    /**
     * Changes parameters for getting allowed options combination
     *
     * @param array $options                Product options
     * @param array $variants               Options variants
     * @param array $string                 Array of combinations values
     * @param int   $iteration              Iteration level
     * @param array $exceptions             Options exceptions
     * @param array $inventory_combinations Inventory combinations
     */
    fn_set_hook('get_allowed_options_combination_pre', $options, $variants, $string, $iteration, $exceptions, $inventory_combinations);

    static $result = array();
    $combinations = array();
    foreach ($variants[$iteration] as $variant_id) {
        if (count($options) - 1 > $iteration) {
            $string[$iteration][$options[$iteration]] = $variant_id;
            list($_c, $is_result) = fn_get_allowed_options_combination($options, $variants, $string, $iteration + 1, $exceptions, $inventory_combinations);
            if ($is_result) {
                return array($_c, $is_result);
            }

            $combinations = array_merge($combinations, $_c);
            unset($string[$iteration]);
        } else {
            $_combination = array();
            if (!empty($string)) {
                foreach ($string as $val) {
                    foreach ($val as $opt => $var) {
                        $_combination[$opt] = $var;
                    }
                }
            }
            $_combination[$options[$iteration]] = $variant_id;
            $combinations[] = $_combination;

            foreach ($combinations as $combination) {
                $allowed = true;
                foreach ($exceptions as $exception) {
                    $res = array_diff($exception, $combination);

                    if (empty($res)) {
                        $allowed = false;
                        break;

                    } else {
                        foreach ($res as $option_id => $variant_id) {
                            if ((int) $variant_id === OPTION_EXCEPTION_VARIANT_ANY) {
                                unset($res[$option_id]);
                            } elseif ((int) $variant_id === OPTION_EXCEPTION_VARIANT_NOTHING) {
                                $res = array_intersect($exception, $combination);
                            }
                        }

                        if (empty($res)) {
                            $allowed = false;
                            break;
                        }
                    }
                }

                if ($allowed) {
                    $result = $combination;

                    if (empty($inventory_combinations)) {
                        return array($result, true);
                    } else {
                        foreach ($inventory_combinations as $_icombination) {
                            $_res = array_diff($_icombination, $combination);
                            if (empty($_res)) {
                                return array($result, true);
                            }
                        }
                    }
                }
            }

            $combinations = array();
        }
    }

    if ($iteration == 0) {
        return array($result, true);
    } else {
        return array($combinations, false);
    }
}

function fn_apply_options_rules($product)
{
    /**
     * Changes product data before applying product options rules
     *
     * @param array $product Product data
     */
    fn_set_hook('apply_options_rules_pre', $product);

    /*  Options type:
            P - simultaneous/parallel
            S - sequential
    */
    // Check for the options and exceptions types
    if (!isset($product['options_type']) || !isset($product['exceptions_type'])) {
        $product = array_merge($product, db_get_row('SELECT options_type, exceptions_type FROM ?:products WHERE product_id = ?i', $product['product_id']));
        $product = fn_normalize_product_overridable_fields($product);
    }

    // Get the selected options or get the default options
    $product['selected_options'] = empty($product['selected_options']) ? array() : $product['selected_options'];
    $product['options_update'] = ($product['options_type'] == 'S') ? true : false;

    // Conver the selected options text to the utf8 format
    if (!empty($product['product_options'])) {
        foreach ($product['product_options'] as $id => $option) {
            if (!empty($option['value'])) {
                $product['product_options'][$id]['value'] = fn_unicode_to_utf8($option['value']);
            }
            if (!empty($product['selected_options'][$option['option_id']])) {
                $product['selected_options'][$option['option_id']] = fn_unicode_to_utf8($product['selected_options'][$option['option_id']]);
            }
        }
    }

    $selected_options = &$product['selected_options'];
    $changed_option = empty($product['changed_option']) ? true : false;

    $simultaneous = array();
    $next = 0;

    foreach ($product['product_options'] as $_id => $option) {
        if (!in_array($option['option_type'], array('I', 'T', 'F'))) {
            $simultaneous[$next] = $option['option_id'];
            $next = $option['option_id'];
        }

        if (!empty($option['value'])) {
            $selected_options[$option['option_id']] = $option['value'];
        }

        if (!$changed_option && $product['changed_option'] == $option['option_id']) {
            $changed_option = true;
        }

        if (!empty($selected_options[$option['option_id']]) && ($selected_options[$option['option_id']] == 'checked' || $selected_options[$option['option_id']] == 'unchecked') && $option['option_type'] == 'C') {
            foreach ($option['variants'] as $variant) {
                if (($variant['position'] == 0 && $selected_options[$option['option_id']] == 'unchecked') || ($variant['position'] == 1 && $selected_options[$option['option_id']] == 'checked')) {
                    $selected_options[$option['option_id']] = $variant['variant_id'];
                    if ($changed_option) {
                        $product['changed_option'] = $option['option_id'];
                    }
                }
            }
        }

        // Check, if the product has any options modifiers
        if (!empty($product['product_options'][$_id]['variants'])) {
            foreach ($product['product_options'][$_id]['variants'] as $variant) {
                if (!empty($variant['modifier']) && floatval($variant['modifier'])) {
                    $product['options_update'] = true;
                }
            }
        }
    }

    if (!empty($product['changed_option']) && empty($selected_options[$product['changed_option']]) && $product['options_type'] == 'S') {
        $product['changed_option'] = array_search($product['changed_option'], $simultaneous);
        if ($product['changed_option'] == 0) {
            unset($product['changed_option']);
            $reset = true;
            if (!empty($selected_options)) {
                foreach ($selected_options as $option_id => $variant_id) {
                    if (!isset($product['product_options'][$option_id]) || !in_array($product['product_options'][$option_id]['option_type'], array('I', 'T', 'F'))) {
                        unset($selected_options[$option_id]);
                    }
                }
            }
        }
    }

    if (empty($selected_options) && $product['options_type'] == 'P') {
        $selected_options = $default_selected_options = fn_get_default_product_options($product['product_id'], true, $product);
    }

    if (empty($product['changed_option']) && isset($reset)) {
        $product['changed_option'] = '';

    } elseif (empty($product['changed_option'])) {
        end($selected_options);
        $product['changed_option'] = key($selected_options);
    }

    if ($product['options_type'] == 'S') {
        empty($product['changed_option']) ? $allow = 1 : $allow = 0;

        foreach ($product['product_options'] as $_id => $option) {
            $product['product_options'][$_id]['disabled'] = false;

            if (in_array($option['option_type'], array('I', 'T', 'F'))) {
                continue;
            }

            $option_id = $option['option_id'];

            if ($allow >= 1) {
                unset($selected_options[$option_id]);
                $product['product_options'][$_id]['value'] = '';
            }

            if ($allow >= 2) {
                $product['product_options'][$_id]['disabled'] = true;
                continue;
            }

            if (empty($product['changed_option']) || (!empty($product['changed_option']) && $product['changed_option'] == $option_id) || $allow > 0) {
                $allow++;
            }
        }

        $product['simultaneous'] = $simultaneous;
    }

    // Restore selected values
    if (!empty($selected_options)) {
        foreach ($product['product_options'] as $_id => $option) {
            if (isset($selected_options[$option['option_id']])) {
                $product['product_options'][$_id]['value'] = $selected_options[$option['option_id']];
            }
        }
    }

    // Generate combination hash to get images. (Also, if the tracking with options, get amount and product code)
    $combination_hash = fn_generate_cart_id($product['product_id'], array('product_options' => $selected_options), true);
    $product['combination_hash'] = $combination_hash;

    // Enable AJAX form for product with required options
    if (!$product['options_update'] && count($product['product_options'])) {
        $product['options_update'] = 0;
        foreach ($product['product_options'] as $product_option) {
            if ($product_option['required'] == 'Y') {
                $product['options_update'] += 1;
            }
        }
    }

    /**
     * Changes product data after applying product options rules
     *
     * @param array $product Product data
     */
    fn_set_hook('apply_options_rules_post', $product);

    return $product;
}

/**
 * Applying options exceptions rules for product
 *
 * @param array $product Product data
 * @param array $exceptions Options exceptions rules
 * @return array Product data with the corrected exceptions rules
 */
function fn_apply_exceptions_rules($product, $exceptions = array())
{
    /**
     * Exceptions type:
     *   A - Allowed
     *   F - Forbidden
     */

    /**
     * Changes product data before applying options exceptions rules
     *
     * @param array $product Product data
     */
    fn_set_hook('apply_exceptions_rules_pre', $product);

    if (empty($product['selected_options']) && $product['options_type'] == 'S') {
        return $product;
    }

    if (empty($exceptions)) {
        // Deprecated, but preserved for BC
        $exceptions = fn_get_product_exceptions($product['product_id'], true);

        if (empty($exceptions)) {
            return $product;
        }
    }

    $product['options_update'] = true;

    if (Registry::get('settings.General.exception_style') == 'warning') {
        $result = fn_is_allowed_options_exceptions($exceptions, $product['selected_options'], $product['options_type'], $product['exceptions_type']);

        if (!$result) {
            $product['show_exception_warning'] = 'Y';
        }

        return $product;
    }

    $options = array();
    $disabled = array();

    foreach ($exceptions as $exception_id => $exception) {
        if ($product['options_type'] === ProductOptionsApplyOrder::SEQUENTIAL) {
            if ($product['exceptions_type'] === ProductOptionsExceptionsTypes::ALLOWED) {
                // Allowed sequential exceptions type
                $_selected = array();

                // Sorting the array with exceptions relatively the array with product options
                $sorted_exception = array();
                foreach ($product['product_options'] as $option) {
                    if (isset($exception[$option['option_id']])) {
                        $sorted_exception[$option['option_id']] = $exception[$option['option_id']];
                    }
                }
                $exception = $sorted_exception;

                // Selection of the correct selected options variants
                foreach ($product['selected_options'] as $option_id => $variant_id) {
                    if ($exception[$option_id] == OPTION_EXCEPTION_VARIANT_ANY) {
                        $exception[$option_id] = $variant_id;
                    }

                    $_selected[$option_id] = $variant_id;

                    // Current options in $exception[] must intersect with selected
                    $intersect_elems = array_intersect_assoc($exception, $_selected);
                    // Options that have been selected by the user at this stage
                    $different_elems = array_diff($exception, $_selected);

                    if ($intersect_elems == $_selected && $different_elems) {
                        // Selecting the suitable variants for next the option after selected
                        $var_id = reset($different_elems);
                        $opt_id = key($different_elems);

                        if ($var_id == OPTION_EXCEPTION_VARIANT_ANY) {
                            $options[$opt_id]['any'] = true;
                        } elseif ($var_id == OPTION_EXCEPTION_VARIANT_NOTHING) {
                            unset($options[$opt_id]);
                        } else {
                            // Correct option variant
                            $options[$opt_id][$var_id] = true;
                        }
                    }
                }
            } else {
                // Forbidden sequential exceptions type
                $_selected = array();

                foreach ($product['selected_options'] as $option_id => $variant_id) {
                    $disable = true;
                    $full = array();

                    $_selected[$option_id] = $variant_id;
                    $elms = array_diff($exception, $_selected);
                    $_exception = $exception;

                    if (!empty($elms)) {
                        foreach ($elms as $opt_id => $var_id) {
                            if ($var_id == OPTION_EXCEPTION_VARIANT_ANY) { // Any
                                $full[$opt_id] = $var_id;
                                if ($product['exceptions_type'] !== ProductOptionsExceptionsTypes::ALLOWED || isset($_selected[$opt_id])) {
                                    unset($elms[$opt_id]);
                                    if ($product['exceptions_type'] !== ProductOptionsExceptionsTypes::ALLOWED) {
                                        unset($_exception[$opt_id]);
                                    }
                                }
                            } if ($var_id == OPTION_EXCEPTION_VARIANT_NOTHING) { // No
                                if ($product['exceptions_type'] === ProductOptionsExceptionsTypes::ALLOWED && count($elms) > 1) {
                                    unset($elms[$opt_id]);
                                }
                            } else {
                                $disable = false;
                            }
                        }
                    }

                    if ($disable && !empty($elms) && count($elms) != count($full)) {
                        $vars = array_diff($elms, $full);
                        $disable = false;
                        foreach ($vars as $var) {
                            if ($var != OPTION_EXCEPTION_VARIANT_ANY) {
                                $disable = true;
                            }
                        }
                    }

                    if ($disable && !empty($elms) && count($elms) != count($full)) {
                        foreach ($elms as $opt_id => $var_id) {
                            $disabled[$opt_id] = true;
                        }
                    } elseif ($disable && !empty($full)) {
                        foreach ($full as $opt_id => $var_id) {
                            $options[$opt_id]['any'] = true;
                        }
                    } elseif (count($elms) == 1 && reset($elms) == OPTION_EXCEPTION_VARIANT_NOTHING) {
                        $disabled[key($elms)] = true;
                    } elseif (($product['exceptions_type'] === ProductOptionsExceptionsTypes::ALLOWED && count($elms) + count($_selected) !== count($_exception)) || ($product['exceptions_type'] === ProductOptionsExceptionsTypes::FORBIDDEN && count($elms) !== 1)) {
                        continue;
                    }

                    if (
                        !isset($product['simultaneous'][$option_id]) || !isset($elms[$product['simultaneous'][$option_id]])
                    ) {
                        continue;
                    }

                    $elms[$product['simultaneous'][$option_id]] = ($elms[$product['simultaneous'][$option_id]] == OPTION_EXCEPTION_VARIANT_ANY) ? 'any' : $elms[$product['simultaneous'][$option_id]];
                    if (isset($product['simultaneous'][$option_id]) && !empty($elms) && isset($elms[$product['simultaneous'][$option_id]])) {
                        $options[$product['simultaneous'][$option_id]][$elms[$product['simultaneous'][$option_id]]] = true;
                    }
                }
            }
        } else {
            // Parallel exceptions type
            $disable = true;
            $full = array();

            $elms = array_diff($exception, $product['selected_options']);

            if (!empty($elms)) {
                $elms_no_variants = array();
                foreach ($elms as $opt_id => $var_id) {
                    if ($var_id == OPTION_EXCEPTION_VARIANT_ANY) { // Any
                        $full[$opt_id] = $var_id;
                        unset($elms[$opt_id]);
                    } elseif ($var_id == OPTION_EXCEPTION_VARIANT_NOTHING) { // No
                        if ($product['exceptions_type'] === ProductOptionsExceptionsTypes::ALLOWED) {
                            $elms_no_variants[] = $opt_id;
                        }
                    } else {
                        $disable = false;
                    }
                }
                if (count(array_unique($elms)) > 1) {
                    foreach ($elms_no_variants as $opt_id) {
                        unset($elms[$opt_id]);
                    }
                }
            }

            if ($disable) {
                if ($elms) {
                    foreach ($elms as $opt_id => $var_id) {
                        $disabled[$opt_id] = true;
                    }
                }
                if ($full && (!$elms || $product['exceptions_type'] === ProductOptionsExceptionsTypes::ALLOWED)) {
                    foreach ($full as $opt_id => $var_id) {
                        $options[$opt_id]['any'] = true;
                    }
                }
            } elseif (count($elms) == 1) {
                $variant_id = reset($elms);
                $option_id = key($elms);
                if ($variant_id == OPTION_EXCEPTION_VARIANT_NOTHING) {
                    $disabled[$option_id] = true;
                } else {
                    $options[$option_id][$variant_id] = true;
                }
            }
        }
    }

    if ($product['exceptions_type'] === ProductOptionsExceptionsTypes::ALLOWED && $product['options_type'] === ProductOptionsApplyOrder::SIMULTANEOUS) {
        foreach ($product['selected_options'] as $option_id => $variant_id) {
            $options[$option_id][$variant_id] = true;
        }
    }

    $first_elm = array();

    foreach ($product['product_options'] as $_id => &$option) {
        $option_id = $option['option_id'];
        $clear_variants = ($option['missing_variants_handling'] == 'H');

        if (!in_array($option['option_type'], array('I', 'T', 'F')) && empty($first_elm)) {
            $first_elm = $product['product_options'][$_id];
        }

        if (isset($disabled[$option_id])) {
            $option['disabled'] = true;
            $option['not_required'] = true;
        }

        if (($product['options_type'] == 'S' && $option['option_id'] == $first_elm['option_id']) || (in_array($option['option_type'], array('I', 'T', 'F')))) {
            continue;
        }

        if ($product['options_type'] == 'S' && $option['disabled']) {
            if ($clear_variants) {
                $option['variants'] = array();
            }

            continue;
        }

        // Exclude checkboxes
        if (!empty($option['variants'])) {
            foreach ($option['variants'] as $variant_id => $variant) {
                if ($product['exceptions_type'] === ProductOptionsExceptionsTypes::ALLOWED) {
                    // Allowed combinations
                    if (empty($options[$option_id][$variant_id]) && !isset($options[$option_id]['any'])) {
                        if ($option['option_type'] != 'C') {
                            unset($option['variants'][$variant_id]);
                        } else {
                            $option['variants'][$variant_id]['disabled'] = true;
                        }
                    }
                } else {
                    // Forbidden combinations
                    if (!empty($options[$option_id][$variant_id]) || isset($options[$option_id]['any'])) {
                        if ($option['option_type'] != 'C') {
                            unset($option['variants'][$variant_id]);
                        } else {
                            $option['variants'][$variant_id]['disabled'] = true;
                        }
                    }
                }
            }

            if (!in_array($option['value'], array_keys($option['variants']))) {
                $option['value'] = '';
            }
        }
    }

    // Correct selected options
    foreach ($product['product_options'] as $_id => &$option) {
        if (
            $product['options_type'] == 'P'
            && !in_array($option['option_type'], array('I', 'T', 'F'))
            && empty($option['value'])
            && empty($option['disabled'])
            && !empty($option['variants'])
        ) {
            $variant = reset($option['variants']);
            $option['value'] = $variant['variant_id'];
            $product['selected_options'][$option['option_id']] = $variant['variant_id'];
        }
    }

    /**
     * Changes product data after applying options exceptions rules
     *
     * @param array $product    Product data
     * @param array $exceptions Options exceptions
     */
    fn_set_hook('apply_exceptions_post', $product, $exceptions);

    return $product;
}

function fn_is_allowed_options_exceptions($exceptions, $options, $o_type = 'P', $e_type = 'F')
{
    /**
     * Changes parameters before checking allowed options exceptions
     *
     * @param array  $exceptions Options exceptions
     * @param array  $options    Product options
     * @param string $o_type     Option type
     * @param string $e_type     Exception type
     */
    fn_set_hook('is_allowed_options_exceptions_pre', $exceptions, $options, $o_type, $e_type);

    $result = null;

    foreach ($options as $option_id => $variant_id) {
        if (empty($variant_id)) {
            unset($options[$option_id]);
        }
    }

    if ($e_type != 'A' || !empty($options)) {
        $in_exception = false;
        foreach ($exceptions as $exception) {
            foreach ($options as $option_id => $variant_id) {
                if (!isset($exception[$option_id])) {
                    unset($options[$option_id]);
                }
            }

            if (count($exception) != count($options)) {
                continue;
            }

            $in_exception = true;
            $diff = array_diff($exception, $options);

            if (!empty($diff)) {
                foreach ($diff as $option_id => $variant_id) {
                    if ($variant_id == OPTION_EXCEPTION_VARIANT_ANY || ($e_type != 'A' && $variant_id == OPTION_EXCEPTION_VARIANT_NOTHING)) {
                        unset($diff[$option_id]);
                    }
                }
            }

            if (empty($diff) && $e_type == 'A') {
                $result = true;
                break;
            } elseif (empty($diff)) {
                $result = false;
                break;
            }
        }

        if (is_null($result) && $in_exception && $e_type == 'A') {
            $result = false;
        }
    }

    if (is_null($result)) {
        $result = true;
    }

    /**
     * Changes result of checking allowed options exceptions
     *
     * @param boolean $result     Result of checking options exceptions
     * @param array   $exceptions Options exceptions
     * @param array   $options    Product options
     * @param string  $o_type     Option type
     * @param string  $e_type     Exception type
     */
    fn_set_hook('is_allowed_options_exceptions_post', $result, $exceptions, $options, $o_type, $e_type);

    return $result;
}

/**
 * Checks if all selected product options are available now
 *
 * @param array<string, int|string|bool|array> $product    Product data
 * @param bool                                 $only_avail Get only available options
 *
 * @return bool true if all options are available, false otherwise
 */
function fn_is_allowed_options(array $product, $only_avail = false)
{
    if (empty($product['product_options'])) {
        return true;
    }

    /** @var array<array-key, int|string> $product_options */
    $product_options = $product['product_options'];

    $options = fn_get_product_options((int) $product['product_id'], CART_LANGUAGE, false, false, $only_avail);
    foreach ($product_options as $option_id => $variant_id) {
        if (empty($variant_id)) {
            // Forbidden combination in action
            continue;
        }

        if (!isset($options[$option_id]) || (!empty($options[$option_id]['variants']) && !isset($options[$option_id]['variants'][$variant_id]))) {
            return false;
        }
    }

    return true;
}

function fn_get_product_option_data($option_id, $product_id, $lang_code = DESCR_SL)
{
    $extra_variant_fields = '';

    $fields = "a.*, b.option_name, b.internal_option_name, b.option_text, b.description, b.inner_hint, b.incorrect_message, b.comment";
    $join = db_quote(" LEFT JOIN ?:product_options_descriptions as b ON a.option_id = b.option_id AND b.lang_code = ?s"
        . " LEFT JOIN ?:product_global_option_links as c ON c.option_id = a.option_id", $lang_code);
    $condition = db_quote("a.option_id = ?i AND a.product_id = ?i", $option_id, $product_id);

    /**
     * Changes params before option data selecting
     *
     * @param int    $option_id            Option identifier
     * @param int    $product_id           Product identifier
     * @param string $fields               Fields to be selected
     * @param string $condition            String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string $join                 String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $extra_variant_fields Additional variant fields to be selected
     * @param string $lang_code            2-letters language code
     */
    fn_set_hook('get_product_option_data_pre', $option_id, $product_id, $fields, $condition, $join, $extra_variant_fields, $lang_code);

    $opt = db_get_row(
        "SELECT " . $fields
        . " FROM ?:product_options as a" . $join
        . " WHERE " . $condition
        . " ORDER BY a.position"
    );

    if (!empty($opt)) {
        $_cond = ($opt['option_type'] == 'C') ? ' AND a.position = 1' : '';

        $join = '';
        if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id')) {
            $extra_variant_fields .= 'IF(shared_option_variants.variant_id IS NOT NULL, shared_option_variants.modifier, a.modifier) as modifier, ';
            $extra_variant_fields .= 'IF(shared_option_variants.variant_id IS NOT NULL, shared_option_variants.modifier_type, a.modifier_type) as modifier_type, ';
            $join .= db_quote(' LEFT JOIN ?:ult_product_option_variants shared_option_variants ON shared_option_variants.variant_id = a.variant_id AND shared_option_variants.company_id = ?i', Registry::get('runtime.company_id'));
        }

        $opt['variants'] = db_get_hash_array("SELECT a.variant_id, a.position, a.modifier, a.modifier_type, a.weight_modifier, a.weight_modifier_type, a.status, $extra_variant_fields b.variant_name FROM ?:product_option_variants as a LEFT JOIN ?:product_option_variants_descriptions as b ON a.variant_id = b.variant_id AND b.lang_code = ?s $join WHERE a.option_id = ?i $_cond ORDER BY a.position", 'variant_id', $lang_code, $option_id);

        if (!empty($opt['variants'])) {
            foreach ($opt['variants'] as $k => $v) {
                $opt['variants'][$k]['image_pair'] = fn_get_image_pairs($v['variant_id'], 'variant_image', 'V', true, true, $lang_code);
            }
        }
    }

    /**
     * Changes option data
     *
     * @param array  $opt        Option data
     * @param int    $product_id Product identifier
     * @param string $lang_code  2-letters language code
     */
    fn_set_hook('get_product_option_data_post', $opt, $product_id, $lang_code);

    return $opt;
}

/**
 * Gets list of the options modifiers by selected options.
 * This is an internal function, it should not be used directly. See fn_apply_options_modifiers.
 *
 * @param array     $selected_options   The list of selected option variants as option_id => variant_id
 * @param string    $type               Calculation type (P - price or W - weight)
 * @param string    $fields             String of comma-separated SQL fields to be selected in an SQL-query
 *
 * @return array
 * @internal
 * @see fn_apply_options_modifiers
 */
function fn_get_option_modifiers_by_selected_options(array $selected_options, $type, $fields)
{
    static $option_types = array();
    static $variants = array();

    if (empty($fields)) {
        if ($type === 'P') {
            $fields = 'a.modifier, a.modifier_type';
        } else {
            $fields = 'a.weight_modifier as modifier, a.weight_modifier_type as modifier_type';
        }
    }

    /** @var \Tygh\Database\Connection $db */
    $db = Tygh::$app['db'];

    $modifiers = array();
    $cache_key = Registry::get('runtime.company_id') . md5($fields);

    if (!isset($variants[$cache_key])) {
        $variants[$cache_key] = array();
    }

    foreach ($selected_options as $option_id => $variant_id) {
        if (!array_key_exists($option_id, $option_types)) {
            $option_ids = array_keys($selected_options);

            $types = $db->getSingleHash(
                'SELECT option_type as type, option_id'
                . ' FROM ?:product_options WHERE option_id IN (?n)',
                array('option_id', 'type'),
                $option_ids
            );

            foreach ($option_ids as $id) {
                $option_types[$id] = isset($types[$id]) ? $types[$id] : null;
            }
        }

        $option_type = $option_types[$option_id];

        if (!ProductOptionTypes::isSelectable($option_type)) {
            continue;
        }

        if (!array_key_exists($variant_id, $variants[$cache_key])) {
            $variant_ids = array_values($selected_options);

            $om_join = "";
            $om_condition = $db->quote("a.variant_id IN (?n)", $variant_ids);

            /**
             * Changes SQL-query params before option modifiers selecting
             *
             * @param string $type              Calculation type (price or weight)
             * @param string $fields            Fields to be selected
             * @param string $om_condition      String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
             * @param string $om_join           String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
             * @param array  $variant_ids       Variant identifiers
             * @param array  $selected_options  The list of selected option variants as option_id => variant_id
             */
            fn_set_hook('apply_option_modifiers_get_option_modifiers', $type, $fields, $om_join, $om_condition, $variant_ids, $selected_options);

            $items = $db->getHash(
                'SELECT ?p, a.variant_id FROM ?:product_option_variants a ?p WHERE 1 AND ?p',
                'variant_id', $fields, $om_join, $om_condition
            );

            foreach ($variant_ids as $id) {
                $variants[$cache_key][$id] = isset($items[$id]) ? $items[$id] : null;
            }
        }

        if (isset($variants[$cache_key][$variant_id])) {
            $variant = $variants[$cache_key][$variant_id];

            $modifiers[] = array(
                'type' => $variant['modifier_type'],
                'value' => $variant['modifier'],
            );
        }
    }

    return $modifiers;
}

/**
 * Adds global option link for product
 *
 * @param int $product_id   Product identifier
 * @param int $option_id    Option identifier
 */
function fn_add_global_option_link($product_id, $option_id)
{
    $product_company_id = fn_get_company_id('products', 'product_id', $product_id);
    $option_company_id = fn_get_company_id('product_options', 'option_id', $option_id);

    if ($product_company_id !== $option_company_id && $option_company_id) {
        return;
    }

    db_replace_into('product_global_option_links', [
        'product_id' => $product_id,
        'option_id' => $option_id,
    ]);

    if (fn_allowed_for('ULTIMATE')) {
        fn_ult_share_product_option($option_id, $product_id);
    }

    /**
     * Executes after a global option has been linked to a product
     *
     * @param int $product_id Product identifier
     * @param int $option_id  Option identifier
     */
    fn_set_hook('add_global_option_link_post', $product_id, $option_id);
}

/**
 * Deletes global option link for product
 *
 * @param int $product_id   Product identifier
 * @param int $option_id    Option identifier
 */
function fn_delete_global_option_link($product_id, $option_id)
{
    db_query('DELETE FROM ?:product_global_option_links WHERE product_id = ?i AND option_id = ?i', $product_id, $option_id);

    /**
     * Executes after a global option has been unlinked from a product
     *
     * @param int $product_id Product identifier
     * @param int $option_id  Option identifier
     */
    fn_set_hook('delete_global_option_link_post', $product_id, $option_id);
}
