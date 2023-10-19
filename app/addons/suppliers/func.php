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

use Tygh\Addons\ProductVariations\ServiceProvider as ProductVariationsServiceProvider;
use Tygh\Enum\ObjectStatuses;
use Tygh\Navigation\LastView;
use Tygh\Registry;
use Tygh\Tygh;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Update supplier data
 *
 * @param int $supplier_id
 * @param array $supplier_data
 * @return int Supplier id
 */
function fn_update_supplier($supplier_id, $supplier_data)
{
    $old_supplier_data = fn_get_supplier_data($supplier_id);

    if (empty($supplier_id)) {
        $supplier_data['timestamp'] = TIME;

        $supplier_id = db_query('INSERT INTO ?:suppliers ?e', $supplier_data);
    } else {
        db_query('UPDATE ?:suppliers SET ?u WHERE supplier_id = ?i', $supplier_data, $supplier_id);
    }

    // Update supplier shipping methods
    $shippings = empty($supplier_data['shippings']) ? array() : $supplier_data['shippings'];
    fn_update_supplier_shippings($supplier_id, $shippings);

    $hidden_products = array();
    if (!empty($old_supplier_data['products'])) {
        $all_products = fn_get_all_supplier_products($supplier_id);
        $hidden_products = array_diff($all_products, $old_supplier_data['products']);

        if ($hidden_products) {
            $supplier_data['products'] .= ',' . implode(',', $hidden_products);
        }
    }

    // Update supplier products
    $products = empty($supplier_data['products']) ? array() : explode(',', $supplier_data['products']);
    fn_update_supplier_products($supplier_id, $products);

    return $supplier_id;
}

/**
 * Update supplier shippings links
 *
 * @param int   $supplier_id Supplier ID
 * @param int[] $shippings   Shipping method IDs
 *
 * @return bool Always true
 */
function fn_update_supplier_shippings($supplier_id, $shippings)
{
    $current_supplier_data = fn_get_supplier_data($supplier_id);
    $deleted_shippings = array_diff($current_supplier_data['shippings'], $shippings);

    /**
     * Executes when updating a supplier's shipping methods, before removing shipping methods links.
     * Allows you to modify the list of shipping methods that would be removed
     *
     * @param int   $supplier_id           Supplier ID
     * @param int[] $shippings             Shipping method IDs
     * @param array $current_supplier_data Current supplier data
     * @param int[] $deleted_shippings     Deleted shipping method IDs
     */
    fn_set_hook('suppliers_update_supplier_shippings_before_delete_shippings', $supplier_id, $shippings, $current_supplier_data, $deleted_shippings);

    if ($deleted_shippings) {
        db_query(
            'DELETE FROM ?:supplier_links WHERE object_type = ?s AND supplier_id = ?i AND object_id IN (?n)',
            'S',
            $supplier_id,
            $deleted_shippings
        );
    }

    foreach ($shippings as $shipping_id) {
        db_replace_into('supplier_links', [
            'supplier_id' => $supplier_id,
            'object_id'   => $shipping_id,
            'object_type' => 'S',
        ]);
    }

    return true;
}

/**
 * Update supplier products links
 *
 * @param int $supplier_id
 * @param array $products
 *
 * @return bool Always true
 */
function fn_update_supplier_products($supplier_id, $products)
{
    db_query('DELETE FROM ?:supplier_links WHERE object_type = ?s AND supplier_id = ?i', 'P', $supplier_id);

    if (!empty($products)) {

        foreach ($products as $product_id) {
            fn_suppliers_link_product($supplier_id, $product_id);
        }
    }
    /**
     * Action after updating supplier
     *
     * @param int $supplier_id
     * @param array $products
     */
    fn_set_hook('update_supplier_products_post', $supplier_id, $products);

    return true;
}

/**
 * Get supplier data
 *
 * @param array $params
 * @return array Found suppliers data
 */
function fn_get_suppliers($params = array(), $items_per_page = 0)
{
    // Init filter
    $params = LastView::instance()->update('suppliers', $params);

    $condition = fn_get_company_condition('?:suppliers.company_id');
    $join = db_quote(" JOIN ?:companies ON ?:suppliers.company_id = ?:companies.company_id");

    // Set default values to input params
    $default_params = array (
        'page' => 1,
        'items_per_page' => $items_per_page
    );

    $params = array_merge($default_params, $params);

    // Define fields that should be retrieved
    $fields = array (
        "?:suppliers.supplier_id",
        "?:suppliers.timestamp",
        "?:suppliers.status",
        "?:suppliers.name",
        "?:suppliers.email",
        "?:suppliers.company_id",
        "?:companies.company as company_name",
    );

    // Define sort fields
    $sortings = array (
        'id' => "?:suppliers.supplier_id",
        'email' => "?:suppliers.email",
        'name' => "?:suppliers.name",
        'date' => "?:suppliers.timestamp",
        'type' => "?:suppliers.supplier_type",
        'status' => "?:suppliers.status",
        'company' => "company_name",
    );

    $filters = array(
        'name' => "?:suppliers.name",
        'email' => "?:suppliers.email",
        'address' => "?:suppliers.address",
        'zipcode' => "?:suppliers.zipcode",
        'country' => "?:suppliers.country",
        'state' => "?:suppliers.state",
        'city' => "?:suppliers.city",
        'status' => "?:suppliers.status",
        'company' => "?:companies.company",
    );

    foreach ($filters as $filter => $field) {
        if (!empty($params[$filter])) {
            $condition .= db_quote(" AND " . $field . " LIKE ?l", "%" . trim($params[$filter]) . "%");
        }
    }

    if (!empty($params['supplier_id'])) {
        $condition .= db_quote(' AND ?:suppliers.supplier_id IN (?n)', $params['supplier_id']);
    }

    $sorting = db_sort($params, $sortings, 'name', 'asc');

    // Paginate search results
    $limit = '';
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(DISTINCT(?:suppliers.supplier_id)) FROM ?:suppliers ?p WHERE 1 ?p", $join, $condition);
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $suppliers = db_get_array("SELECT ?p FROM ?:suppliers ?p WHERE 1 ?p GROUP BY ?:suppliers.supplier_id ?p ?p", implode(', ', $fields), $join, $condition, $sorting, $limit);

    LastView::instance()->processResults('suppliers', $suppliers, $params);

    return array($suppliers, $params);
}

/**
 * Get supplier data
 *
 * @param int $supplier_id
 * @return array Found supplier data and shippings links and products links
 */
function fn_get_supplier_data($supplier_id)
{
    $supplier = db_get_row(
        'SELECT ?:suppliers.*, ?:country_descriptions.country as country_descr, ?:state_descriptions.state as state_descr FROM ?:suppliers'
        . ' LEFT JOIN ?:country_descriptions ON ?:country_descriptions.code = ?:suppliers.country AND ?:country_descriptions.lang_code = ?s'
        . ' LEFT JOIN ?:states ON ?:states.country_code = ?:suppliers.country AND ?:states.code = ?:suppliers.state'
        . ' LEFT JOIN ?:state_descriptions ON ?:state_descriptions.state_id = ?:states.state_id AND ?:state_descriptions.lang_code = ?s'
        . ' WHERE ?:suppliers.supplier_id = ?i',
        CART_LANGUAGE,
        CART_LANGUAGE,
        $supplier_id
    );
    if (!empty($supplier)) {
        $supplier['shippings'] = db_get_fields('SELECT object_id FROM ?:supplier_links WHERE object_type = ?s AND supplier_id = ?i', 'S', $supplier_id);

        $condition = $join =  $group = "";
        if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id')) {
            $join .= db_quote(" INNER JOIN ?:products_categories ON ?:supplier_links.object_id = ?:products_categories.product_id");
            $join .= db_quote(" INNER JOIN ?:categories ON ?:products_categories.category_id = ?:categories.category_id ");
            $condition .= db_quote(" AND ?:categories.company_id = ?i ", Registry::get('runtime.company_id'));
            $group .= ' GROUP BY product_id';
        }
        $supplier['products'] = db_get_fields('SELECT object_id FROM ?:supplier_links ?p WHERE ?:supplier_links.object_type = ?s AND ?:supplier_links.supplier_id = ?i ?p ?p', $join, 'P', $supplier_id, $condition, $group);
    }

    return !empty($supplier) ? $supplier : false;
}

/**
 * Get all supplier products
 *
 * @param int $supplier_id
 * @return array
 */
function fn_get_all_supplier_products($supplier_id)
{
    return db_get_fields('SELECT object_id FROM ?:supplier_links WHERE ?:supplier_links.object_type = \'P\' AND ?:supplier_links.supplier_id = ?i', $supplier_id);
}

/**
 * Get supplier name
 *
 * @param int $supplier_id
 * @return string Found supplier name
 */
function fn_get_supplier_name($supplier_id)
{
    if (!empty($supplier_id)) {
        $supplier_name = db_get_field("SELECT ?:suppliers.name FROM ?:suppliers WHERE ?:suppliers.supplier_id = ?i", $supplier_id);
    }

    return !empty($supplier_name) ? $supplier_name : __('none');
}


/**
 * Fetches supplier ID from product ID
 *
 * @param int $product_id Product identifier
 *
 * @return bool|int
 */
function fn_get_product_supplier_id($product_id)
{
    static $suppliers;
    $product_id = (int) $product_id;

    if (!isset($suppliers[$product_id])) {
        $suppliers[$product_id] = false;

        if ($product_id) {
            $join = db_quote('LEFT JOIN ?:supplier_links ON ?:supplier_links.supplier_id = ?:suppliers.supplier_id AND ?:supplier_links.object_type = ?s', 'P');
            $suppliers[$product_id] = (int) db_get_field('SELECT ?:suppliers.supplier_id FROM ?:suppliers ?p WHERE ?:supplier_links.object_id = ?i', $join, $product_id);
        }
    }

    return $suppliers[$product_id];
}

/**
 * Get supplier shippings
 *
 * @param int $supplier_id
 * @return array Found supplier shipping ids
 */
function fn_get_supplier_shippings($supplier_id)
{
    if (!empty($supplier_id)) {
        $shippings = db_get_fields('SELECT object_id FROM ?:supplier_links WHERE object_type = ?s AND supplier_id = ?i', 'S', $supplier_id);
    } else {
        $shippings = db_get_fields('SELECT object_id FROM ?:supplier_links WHERE object_type = ?s AND supplier_id = 0', 'S');
    }

    return !empty($shippings) ? $shippings : array();
}

/**
 * Gets list of linked suppliers
 *
 * @param int $shipping_id Shipping identifier
 * @return array List of linked suppliers
 */
function fn_get_shippings_suppliers($shipping_id)
{
    $supplier_ids = db_get_fields('SELECT supplier_id FROM ?:supplier_links WHERE object_type = ?s AND object_id = ?i', 'S', $shipping_id);

    return $supplier_ids;
}

/**
 * Sets links to suppliers
 *
 * @param int $shipping_id Shipping identifier
 * @param array $suppliers List of suppliers
 * @return bool always true
 */
function fn_set_shippings_suppliers($shipping_id, $suppliers)
{
    db_query('DELETE FROM ?:supplier_links WHERE object_id = ?i AND object_type = ?s', $shipping_id, 'S');

    foreach ($suppliers as $supplier_id => $enabled) {
        if ($enabled == 'Y') {
            db_query('INSERT INTO ?:supplier_links VALUES (?i, ?i, ?s)', $supplier_id, $shipping_id, 'S');
        }
    }

    return true;
}

/**
 * Delete supplier data
 *
 * @param int $supplier_id
 * @return bool
 */
function fn_delete_supplier($supplier_id)
{
    if (!empty($supplier_id)) {
        $result = db_query('DELETE FROM ?:suppliers WHERE supplier_id = ?i', $supplier_id);
        if ($result) {
            $result = db_query('DELETE FROM ?:supplier_links WHERE supplier_id = ?i', $supplier_id);
        }
    }

    return !empty($result) ? true : false;
}

/**
 * Update supplier status
 *
 * @param int $supplier_id
 * @param string $new_status
 * @return boolean
 */
function fn_update_status_supplier($supplier_id, $new_status)
{
    if (!empty($supplier_id)) {
        $result = db_query("UPDATE ?:suppliers SET status = ?s WHERE supplier_id = ?i", $new_status, $supplier_id);
    }

    return !empty($result) ? true : false;
}

/**
 * Get default supplier id
 *
 * @param int $company_id Supplier company_id
 * @return int Default supplier id
 */
function fn_get_default_supplier_id($company_id = 0)
{

    if (empty($company_id)) {
        $company_id = Registry::ifGet('runtime.company_id', fn_get_default_company_id());
    }

    return db_get_field("SELECT supplier_id FROM ?:suppliers WHERE status = 'A' AND company_id = ?i ORDER BY supplier_id LIMIT 1", $company_id);

}

/**
 * Get supplier data for supplier ID and company ID or get default supplier data for company ID
 *
 * @param int $supplier_id
 * @param int $company_id
 * @return array Found supplier data and shippings links and products links
 */
function fn_if_get_supplier($supplier_id, $company_id)
{
    if (fn_allowed_for('ULTIMATE')) {
        $condition = ''; // Use sharing instead
    } else {
        $condition = db_quote(' AND ?:suppliers.company_id = ?i', $company_id);
    }

    $supplier = db_get_row("SELECT * FROM ?:suppliers WHERE ?:suppliers.supplier_id = ?i ?p", $supplier_id, $condition);

    if (empty($supplier)) {
        if (fn_allowed_for('ULTIMATE')) {
            $condition = '';
        } else {
            $condition = db_quote('AND ?:suppliers.company_id = ?i', $company_id);
        }

        $count = db_get_field("SELECT COUNT(*) FROM ?:suppliers WHERE status = ?s ?p", 'A', $condition);
        if (!empty($count)) {
            $supplier = array('supplier_id' => 0, 'name' => '-' . __('none') . '-');
        }
    }

    return !empty($supplier) ? $supplier : false;
}

/**
 * Hook update product for update supplier_id
 *
 * @param array $product_data Product data
 * @param int $product_id Product id
 * @param string $lang_code Language code
 * @param bool $create Create or update
 * @return int Default supplier id
 */
function fn_suppliers_update_product_post(&$product_data, &$product_id, &$lang_code, &$create)
{
    if (isset($product_data['supplier_id']) && $product_data['supplier_id'] >= 0) {
        fn_suppliers_link_product($product_data['supplier_id'], $product_id);
    }
}

/**
 * Hook get product data for get supplier_id
 *
 * @param int $product_id Product ID
 * @param string $field_list List of fields for retrieving
 * @param string $join String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
 * @param mixed $auth Array with authorization data
 * @param string $lang_code Two-letter language code (e.g. 'en', 'ru', etc.)
 * @param string $condition Condition for selecting product data
 * @return int Default supplier id
 */
function fn_suppliers_get_product_data(&$product_id, &$field_list, &$join, &$auth, &$lang_code, &$condition)
{
    $field_list .= ", ?:supplier_links.supplier_id";
    $join .= " LEFT JOIN ?:supplier_links ON ?:supplier_links.object_id = ?:products.product_id AND ?:supplier_links.object_type = 'P' ";
}

function fn_suppliers_clone_product(&$product_id, &$pid)
{
    $clone_supplier = db_get_row('SELECT * FROM ?:supplier_links WHERE object_id = ?i', $product_id);

    $clone_supplier['object_id'] = $pid;
    if (!empty($clone_supplier['supplier_id'])) {
        fn_suppliers_link_product($clone_supplier['supplier_id'], $clone_supplier['object_id']);
    }
}

/**
 * Hook get products for get supplier_id
 *
 * @param array  $params    Product search params
 * @param array  $fields    List of fields for retrieving
 * @param array  $sortings  Sorting fields
 * @param string $condition String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
 * @param string $join String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
 * @param string $sorting   String containing the SQL-query ORDER BY clause
 * @param string $group_by  String containing the SQL-query GROUP BY field
 * @param string $lang_code Two-letter language code (e.g. 'en', 'ru', etc.)
 */
function fn_suppliers_get_products(&$params, &$fields, &$sortings, &$condition, &$join, &$sorting, &$group_by, &$lang_code)
{
    $fields[] = "?:supplier_links.supplier_id";
    $join .= " LEFT JOIN ?:supplier_links ON ?:supplier_links.object_id = products.product_id AND ?:supplier_links.object_type = 'P' ";
    if (!empty($params['supplier_id'])) {
        $condition .= db_quote(" AND ?:supplier_links.supplier_id = ?i", $params['supplier_id']);
    }
}

/**
 * Hook for add field to product array
 *
 * @param array  $fields     Product fields
 */
function fn_suppliers_get_product_fields(&$fields)
{
    $fields[] = array(
        'name' => '[data][supplier_id]',
        'text' => __('supplier')
    );
}

/**
 * Hook get shipping info for get supplier id
 *
 * @param int $shipping_id Shipping ID
 * @param array $fields Fields array
 * @param string $join Join string
 * @param string $conditions Conditions string
 */
function fn_suppliers_get_shipping_info(&$shipping_id, &$fields, &$join, &$conditions)
{
    $fields[] = "?:supplier_links.supplier_id";
    $join .= " LEFT JOIN ?:supplier_links ON ?:supplier_links.object_id = ?:shippings.shipping_id AND ?:supplier_links.object_type = 'S' ";
}

/**
 * Hook update shipping for update supplier_id
 *
 * @param array $shipping_data Shipping data
 * @param int $shipping_id Shipping id
 * @param string $lang_code Language code
 */
function fn_suppliers_update_shipping_post(&$shipping_data, &$shipping_id, &$lang_code, &$action)
{
    if (!empty($shipping_data['supplier_id'])) {
        db_query("DELETE FROM ?:supplier_links WHERE object_type = ?s AND object_id = ?i", 'S', $shipping_id);
        db_query("INSERT INTO ?:supplier_links (supplier_id, object_id, object_type) VALUES (?i, ?i, ?s)", $shipping_data['supplier_id'], $shipping_id, 'S');
    }

    if (isset($shipping_data['suppliers'])) {
        fn_set_shippings_suppliers($shipping_id, $shipping_data['suppliers']);

    } elseif ($action == 'add') {
        db_query("INSERT INTO ?:supplier_links (supplier_id, object_id, object_type) VALUES (?i, ?i, ?s)", 0, $shipping_id, 'S');
    }
}

/**
 * Hook for modify shippings groups
 *
 * @param array $cart Cart array
 * @param array $cart_products Products from cart
 * @param array $auth Auth array
 * @param array $shipping_rates Shipping rates
 */
function fn_suppliers_shippings_group_products_list(&$products, &$groups)
{
    $separated_shippings_disabled = Registry::get('addons.suppliers.display_shipping_methods_separately') == 'N';

    $stored_supplier_ids = array();
    $common_supplier_shippings = array();

    $suppliers = array();
    $suppliers_groups = array();
    foreach ($groups as $group) {
        foreach ($group['products'] as $cart_id => $product) {
            $supplier_id = fn_get_product_supplier_id($product['product_id']);
            // check if products in group are dedicated to different suppliers that don't have common shippings
            if ($separated_shippings_disabled) {
                if (array_search($supplier_id, $stored_supplier_ids) === false) {
                    $stored_supplier_ids[] = $supplier_id;
                    $supplier_shippings = fn_get_supplier_shippings($supplier_id);
                    if (empty($common_supplier_shippings)) {
                        $common_supplier_shippings = $supplier_shippings;
                    } else {
                        $common_supplier_shippings = array_intersect($common_supplier_shippings, $supplier_shippings);
                        if (empty($common_supplier_shippings)) {
                            return;
                        }
                    }
                }

                $suppliers_group_key = $group['company_id'];
            } else {
                $suppliers_group_key = $supplier_id ? $group['company_id'] . "_" . $supplier_id : $group['company_id'];
            }

            if (empty($suppliers_groups[$suppliers_group_key]) && $supplier_id) {
                $supplier_data = fn_get_supplier_data($supplier_id);
                $origination_data = array(
                    'name' => $supplier_data['name'],
                    'address' => $supplier_data['address'],
                    'city' => $supplier_data['city'],
                    'country' => $supplier_data['country'],
                    'state' => $supplier_data['state'],
                    'zipcode' => $supplier_data['zipcode'],
                    'phone' => $supplier_data['phone'],
                    'fax' => $supplier_data['fax'],
                );

                $suppliers_groups[$suppliers_group_key] = $group;
                $suppliers_groups[$suppliers_group_key]['supplier_id'] = $supplier_id;
                $suppliers_groups[$suppliers_group_key]['origination'] = $origination_data;

                if ($separated_shippings_disabled) {
                    $suppliers_groups[$suppliers_group_key]['name'] = $group['name'];
                } else {
                    $suppliers_groups[$suppliers_group_key]['name'] = $group['name'] . ' (' . $supplier_data['name'] . ')';
                }

                if (fn_allowed_for('ULTIMATE')) {
                    $suppliers_groups[$suppliers_group_key]['name'] = $supplier_data['name'];
                }

                $suppliers_groups[$suppliers_group_key]['products'] = array();
            }

            if (empty($suppliers_groups[$suppliers_group_key]) && !$supplier_id) {
                $suppliers_groups[$suppliers_group_key] = $group;
                $suppliers_groups[$suppliers_group_key]['products'] = array();
            }

            $suppliers_groups[$suppliers_group_key]['products'][$cart_id] = $product;
            $suppliers_groups[$suppliers_group_key]['group_key'] = $suppliers_group_key;
        }
    }

    ksort($suppliers_groups);
    $groups = array_values($suppliers_groups);
}

/**
 * Hook for modify shippings list
 *
 * @param array $cart Cart array
 * @param array $cart_products Products from cart
 * @param array $auth Auth array
 * @param array $shipping_rates Shipping rates
 */
function fn_suppliers_shippings_get_shippings_list(&$group, &$shippings)
{
    $supplier_id = isset($group['supplier_id']) ? $group['supplier_id'] : 0;

    $supplier_shippings = fn_get_supplier_shippings($supplier_id);
    $supplier_shippings = array_unique($supplier_shippings);

    $shippings = array_intersect($shippings, $supplier_shippings);

    if (Registry::get('addons.suppliers.display_shipping_methods_separately') == 'N') {
        foreach ($group['products'] as $cart_id => $product) {
            $supplier_id = fn_get_product_supplier_id($product['product_id']);
            $supplier_shippings = fn_get_supplier_shippings($supplier_id);
            $shippings = array_intersect($shippings, $supplier_shippings);
        }
    }

}

/**
 * Hook for modify shippings groups
 *
 * @param array $cart Cart array
 * @param array $allow
 * @param array $product_groups Products groups from cart
 */
function fn_suppliers_pre_place_order(&$cart, &$allow, &$product_groups)
{
    if (Registry::get('addons.suppliers.display_shipping_methods_separately') == 'N') {
        return;
    }

    $new_product_groups = array();
    foreach ($product_groups as $key_group => $group) {
        if (empty($new_product_groups[$group['company_id']])) {
            $new_product_groups[$group['company_id']] = $group;
            if (isset($group['supplier_id'])) {
                $new_product_groups[$group['company_id']]['name'] = fn_get_supplier_name($group['supplier_id']);
            } else {
                $new_product_groups[$group['company_id']]['name'] = fn_get_company_name($group['company_id']);
            }
            $new_product_groups[$group['company_id']]['products'] = array();
            $new_product_groups[$group['company_id']]['chosen_shippings'] = array();
            if (!empty($group['supplier_id'])) {
                unset($new_product_groups[$group['company_id']]['supplier_id']);
            }
        }

        if (!empty($group['supplier_id'])) {
            foreach ($group['products'] as $cart_id => $product) {
                $group['products'][$cart_id]['extra']['supplier_id'] = $group['supplier_id'];
                $cart['products'][$cart_id]['extra']['supplier_id'] = $group['supplier_id'];
            }
        }

        $supplier_groups = array();
        foreach ($group['products'] as $cart_id => $product) {
            // products from different suppliers must have different group keys when placing suborders
            if (!empty($cart['parent_order_id']) && isset($product['extra']['supplier_id'])) {
                $supplier_id = $product['extra']['supplier_id'];
                if (!isset($supplier_groups[$supplier_id])) {
                    $supplier_groups[$supplier_id] = count($supplier_groups);
                }
                $group['products'][$cart_id]['extra']['group_key'] = $supplier_groups[$supplier_id];
                $cart['products'][$cart_id]['extra']['group_key'] = $supplier_groups[$supplier_id];
            } else {
                $group['products'][$cart_id]['extra']['group_key'] = $key_group;
                $cart['products'][$cart_id]['extra']['group_key'] = $key_group;
            }
        }

        if (!empty($group['chosen_shippings'])) {
            if (!empty($cart['parent_order_id'])) {
                $group['chosen_shippings'][0]['group_key'] = $key_group;
            }
            if (empty($group['chosen_shippings'][0]['group_name'])) {
                $group['chosen_shippings'][0]['group_name'] = $group['name'];
            }
            $new_product_groups[$group['company_id']]['shippings'][$group['chosen_shippings'][0]['shipping_id']] = $group['chosen_shippings'][0];
            $new_product_groups[$group['company_id']]['chosen_shippings'] = array_merge($new_product_groups[$group['company_id']]['chosen_shippings'], $group['chosen_shippings']);
        }
        $new_product_groups[$group['company_id']]['products'] = $new_product_groups[$group['company_id']]['products'] + $group['products'];
    }

    $product_groups = array_values($new_product_groups);
}

/**
 * Adds supplier info to order shipments
 *
 * @param array $shipments Shipments
 * @param array $params Array of various parameters used for element selection
 */
function fn_suppliers_get_shipments_info_post(&$shipments, $params)
{
    // prevent triggering where advanced into not required
    if (empty($params['advanced_info'])) {
        return;
    }

    if (!empty($shipments)) {
        $shipment = reset($shipments);
        $order_id = $shipment['order_id'];
        $order_info = fn_get_order_info($order_id);
        $group_supplier = array();

        if (!empty($order_info['products'])) {
            foreach ($order_info['products'] as $product_key => $product) {
                if (!empty($product['extra']['supplier_id'])) {
                    $group_supplier[$product['extra']['supplier_id']][$product_key] = $product['amount'];
                    foreach ($shipments as $id => $shipment) {
                        if (!empty($shipment['products'][$product_key])) {
                            $shipments[$id]['supplier_id'] = $product['extra']['supplier_id'];
                        }
                    }
                } else {
                    $group_supplier[0][$product_key] = $product['amount'];
                }
            }

            foreach ($shipments as $id => $shipment) {
                $shipments[$id]['one_full'] = true;

                $group_id = isset($shipment['supplier_id']) ? $shipment['supplier_id'] : 0;

                foreach ($group_supplier[$group_id] as $product_key => $product_amount) {
                    if (empty($shipment['products'][$product_key]) || $shipment['products'][$product_key] < $product_amount) {
                        $shipments[$id]['one_full'] = false;
                        break;
                    }
                }
            }
        }
    }
}

/**
 * Hook handler: sends supplier notification when changing an order status.
 */
function fn_suppliers_change_order_status_post(
    $order_id,
    $status_to,
    $status_from,
    $force_notification,
    $place_order,
    $order_info,
    $edp_data
) {
    fn_suppliers_order_notification($order_info, $force_notification);
}

/**
 * Hook handler: sends supplier notification when creating an order in the administration panel.
 */
function fn_suppliers_place_order_manually_post(
    $cart,
    $params,
    $customer_auth,
    $action,
    $issuer_id,
    $force_notification,
    $order_info,
    $edp_data,
    $is_order_placed_notification_required
) {
    if (!$is_order_placed_notification_required) {
        return;
    }

    fn_suppliers_order_notification($order_info, $force_notification);
}

/**
 * Hook handler: sends supplier notification when updating order details in the administration panel.
 */
function fn_suppliers_update_order_details_post($params, $order_info, $edp_data, $force_notification)
{
    fn_suppliers_order_notification($order_info, $force_notification);
}

/**
 * Sends supplier notification when creating or updating an order or changing its status.
 *
 * @param array $order_info         Order information
 * @param array $force_notification Notification rules
 *
 * @internal
 */
function fn_suppliers_order_notification(array $order_info, $force_notification)
{
    $suppliers = array();

    if (!is_array($force_notification)) {
        $force_notification = fn_get_notification_rules($force_notification, !$force_notification);
    }

    if (!empty($order_info['product_groups'])) {
        foreach ($order_info['product_groups'] as $key_group => $group) {
            foreach ($group['products'] as $cart_id => $product) {
                $supplier_id = fn_get_product_supplier_id($product['product_id']);
                if (!empty($supplier_id) && empty($suppliers[$supplier_id])) {
                    $rate = 0;
                    foreach ($group['chosen_shippings'] as $shipping) {
                        $rate += $shipping['rate'];
                    }
                    $suppliers[$supplier_id] = array(
                        'name' => fn_get_supplier_name($supplier_id),
                        'company_id' => $group['company_id'],
                        'cost' => $rate,
                        'shippings' => $group['chosen_shippings'],
                        'supplier_id' => $supplier_id
                    );
                }
                if (!empty($supplier_id)) {
                    $suppliers[$supplier_id]['products'][$cart_id] = $product;
                }
            }
        }
    }

    $status_id = strtolower($order_info['status']);

    /** @var \Tygh\Notifications\EventDispatcher $event_dispatcher */
    $event_dispatcher = Tygh::$app['event.dispatcher'];

    /** @var \Tygh\Notifications\Settings\Factory $notification_settings_factory */
    $notification_settings_factory = Tygh::$app['event.notification_settings.factory'];
    $notification_rules = $notification_settings_factory->create($force_notification);

    foreach ($suppliers as $supplier_id => $supplier) {

        $order = $order_info;
        $order['products'] = $supplier['products'];

        $supplier['data'] = fn_get_supplier_data($supplier_id);
        if ($supplier['data']['status'] !== ObjectStatuses::ACTIVE) {
            continue;
        }

        if (!empty($supplier['shippings'])) {
            if (!empty($supplier['data']['shippings'])) {
                $shippings = array();
                foreach ($supplier['shippings'] as $shipping) {
                    if (!isset($shippings[$shipping['group_name']])) {
                        $shippings[$shipping['group_name']] = $shipping;
                    }
                }

                foreach ($shippings as $key => $shipping) {
                    if ($key != $supplier['name']) {
                        unset($shippings[$key]);
                        if ($supplier['cost'] > $shipping['rate']) {
                            $supplier['cost'] -= $shipping['rate'];
                        } else {
                            $supplier['cost'] = 0;
                        }
                    }
                }

                $supplier['shippings'] = array_values($shippings);
            } else {
                $supplier['shippings'] = array();
            }
        }
        $event_dispatcher->dispatch(
            "suppliers.order.supplier_notified.{$status_id}",
            ['order_info' => $order, 'supplier_id' => $supplier_id, 'supplier' => $supplier],
            $notification_rules
        );
    }
}

function fn_suppliers_get_notification_rules(&$force_notification, &$params, &$disable_notification)
{
    if ($disable_notification) {
        $force_notification['S'] = false;
    } else {
        if (!empty($params['notify_supplier']) || $params === true) {
            $force_notification['S'] = true;
        } else {
            if (AREA == 'A') {
                $force_notification['S'] = false;
            }
        }
    }
}

/**
 * Hook handler: for adding the flag whether products in order have supplier
 *
 * @param array $params Additional parameters
 * @param array $orders Orders list
 */
function fn_suppliers_get_orders_post($params, &$orders)
{
    $order_ids = array();

    foreach ($orders as $key => $order) {
        $orders[$key]['have_suppliers'] = false;
        $order_ids[$key] = $order['order_id'];
    }

    $order_ids = array_chunk($order_ids, 1000, true);

    foreach ($order_ids as $chunk_ids) {
        $orders_with_suppliers = db_get_fields(
            'SELECT DISTINCT(?:order_details.order_id) FROM ?:order_details'
            . ' LEFT JOIN ?:supplier_links ON ?:supplier_links.object_id = ?:order_details.product_id'
            . ' WHERE ?:supplier_links.object_type = ?s AND ?:supplier_links.supplier_id > 0 AND ?:order_details.order_id IN (?n)',
            'P',
            $chunk_ids
        );

        $chunk_ids = array_intersect($chunk_ids, (array) $orders_with_suppliers);

        foreach ($chunk_ids as $index => $order_id) {
            $orders[$index]['have_suppliers'] = true;
        }
    }
}

function fn_suppliers_get_order_info(&$order, &$additional_data)
{
    if (!empty($order['products'])) {
        $order['have_suppliers'] = false;
        foreach ($order['products'] as $product) {
            if (fn_get_product_supplier_id($product['product_id'])) {
                $order['have_suppliers'] = true;
                break;
            }
        }
    }
}

/**
 * Executes actions when installing add-on
 */
function fn_suppliers_install()
{
    // Activate "None" supplier for all shippings
    $query_parts = array();
    $shippings = fn_get_shippings(true);

    foreach ($shippings as $shipping_id => $shipping_name) {
        $query_parts[] = db_quote('(?i, ?i, ?s)', 0, $shipping_id, 'S');
    }

    if (!empty($query_parts)) {
        db_query('INSERT INTO ?:supplier_links VALUES ' . implode(', ', $query_parts));
    }


    /** @var \Tygh\Template\Mail\Repository $repository */
    $repository = Tygh::$app['template.mail.repository'];
    /** @var \Tygh\Template\Mail\Service $service */
    $service = Tygh::$app['template.mail.service'];

    $email_template = $repository->findByCodeAndArea('suppliers_notification_default', 'A');

    if (!$email_template) {
        return;
    }

    $statuses = array_keys(fn_get_simple_statuses());
    foreach ($statuses as $status_to) {
        $service->cloneTemplate(
            $email_template,
            [
                'code' => 'suppliers_notification.' . strtolower($status_to),
                'area' => 'A'
            ]
        );
    }
}

/**
 * Links product with supplier
 *
 * @param int $supplier_id Supplier ID
 * @param int $product_id Product ID
 * @return bool Always true
 */
function fn_suppliers_link_product($supplier_id, $product_id)
{
    db_query('DELETE FROM ?:supplier_links WHERE object_type = ?s AND object_id = ?i', 'P', $product_id);

    if (!empty($supplier_id)) {
        db_query('INSERT INTO ?:supplier_links (supplier_id, object_id, object_type) VALUES (?i, ?i, ?s)', $supplier_id, $product_id, 'P');
    }

    /**
     * Action after linking supplier to product
     *
     * @param int $supplier_id Supplier ID
     * @param int $product_id Product ID
     */
    fn_set_hook('suppliers_link_product_post', $supplier_id, $product_id);

    return true;
}

/**
 * Processes export field
 *
 * @param int $supplier_id
 * @return string Supplier name
 */
function fn_exim_get_supplier($product_id)
{
    $supplier_id = fn_get_product_supplier_id($product_id);

    return fn_get_supplier_name($supplier_id);
}

/**
 * Processes import field
 *
 * @param int $product_id Product ID
 * @param string $supplier_name Supplier name
 */
function fn_exim_put_supplier($product_id, $supplier_name)
{
    $supplier_id = db_get_field("SELECT supplier_id FROM ?:suppliers WHERE name = ?s", $supplier_name);

    fn_suppliers_link_product($supplier_id, $product_id);
}

/**
 * Modifies stored shipping rates
 *
 * @param int $order_id Order number
 * @param array $cart Cart content
 * @param array $customer_auth Authentication data
 */
function fn_suppliers_store_shipping_rates_pre($order_id, &$cart, $customer_auth)
{
    foreach($cart['shipping'] as $shipping_key => $shipping) {
        $cart['stored_shipping'][$shipping['group_key']][] = $shipping['rate'];
    }
}

/**
 * Hook handler: removes a link between the product and the supplier upon the product removal.
 */
function fn_suppliers_delete_product_post($product_id, $product_deleted)
{
    if ($product_deleted) {
        db_query('DELETE FROM ?:supplier_links WHERE object_type = ?s AND object_id = ?i', 'P', $product_id);
    }
}

/**
 * Hook handler: removes or adds variation to the supplier together with their parent on supplier details.
 */
function fn_product_variations_update_supplier_products_post($supplier_id, $product_ids)
{
    if (empty($supplier_id) || empty($product_ids)) {
        return;
    }

    $sync_service = ProductVariationsServiceProvider::getSyncService();
    $sync_service->onTableChanged('supplier_links', $product_ids, ['supplier_id' => $supplier_id]);
}

/**
 * Hook handler: removes or adds variation to the supplier together with their parent on product details.
 */
function fn_product_variations_suppliers_link_product_post($supplier_id, $product_id)
{
    if (empty($product_id)) {
        return;
    }

    $sync_service = ProductVariationsServiceProvider::getSyncService();
    $sync_service->onTableChanged('supplier_links', $product_id, ['supplier_id' => $supplier_id]);
}

/**
 * Hook handler: prevents removal of shared shipping methods from supplier when editing a supplier in the shared store.
 */
function fn_ult_suppliers_update_supplier_shippings_before_delete_shippings($supplier_id, $shippings, $current_supplier_data, &$deleted_shippings)
{
    $runtime_company_id = fn_get_runtime_company_id();
    if (!$runtime_company_id) {
        return;
    }

    $sharing_backup = Registry::get('runtime.skip_sharing_selection');
    Registry::set('runtime.skip_sharing_selection', true);

    $deleted_shippings_owners = db_get_hash_single_array(
        'SELECT shipping_id, company_id FROM ?:shippings WHERE shipping_id IN (?n)',
        ['shipping_id', 'company_id'],
        $deleted_shippings
    );

    Registry::set('runtime.skip_sharing_selection', $sharing_backup);

    $deleted_shippings = array_filter(
        $deleted_shippings,
        function ($shipping_id) use ($runtime_company_id, $deleted_shippings_owners) {
            return isset($deleted_shippings_owners[$shipping_id])
                && $deleted_shippings_owners[$shipping_id] == $runtime_company_id;
        }
    );
}

/**
 * Filters source entities list by its availability for the shared entitiy's companies.
 *
 * @param array  $objects_list     Source entities list
 * @param string $source_type      Source entities' sharing object_type
 * @param string $source_id_field  Field of source entity which stores its ID
 * @param string $shared_type      Shared entity's sharing object_type
 * @param int    $shared_object_id Shared entity ID
 *
 * @return array
 */
function fn_suppliers_filter_objects_by_sharing(
    array $objects_list,
    $source_type,
    $source_id_field,
    $shared_type,
    $shared_object_id
) {
    if ($shared_type === 'shippings') {
        /** @var \Tygh\Storefront\Repository $repository */
        $repository = Tygh::$app['storefront.repository'];
        list($shipping_storefronts, ) = $repository->find(['shipping_ids' => $shared_object_id]);
        $shared_object_storefront_ids = array_keys($shipping_storefronts);
    } else {
        $shared_object_storefront_ids = fn_suppliers_get_object_storefront_ids($shared_type, $shared_object_id);
    }

    if (!$shared_object_storefront_ids) {
        return [];
    }

    if ($source_type === 'shippings') {
        $shippings_ids = array_column($objects_list, 'shipping_id');

        /** @var \Tygh\Storefront\Repository $repository */
        $repository = Tygh::$app['storefront.repository'];
        list($available_storefronts, ) = $repository->find(['shipping_ids' => $shippings_ids]);
        foreach ($available_storefronts as $storefront_id => $storefront) {
            foreach ($objects_list as &$object) {
                if (in_array($object['shipping_id'], $storefront->getShippingIds())) {
                        $object['storefront_ids'][] = $storefront_id;
                }
            }
            unset($object);
        }
    } else {
        $object_company_ids = [];
        foreach ($objects_list as $object) {
            $object_company_ids = array_merge(
                $object_company_ids,
                fn_ult_get_object_shared_companies($source_type, $object[$source_id_field])
            );
        }
        /** @var \Tygh\Storefront\Repository $repository */
        $repository = Tygh::$app['storefront.repository'];
        list($available_storefronts, ) = $repository->find(['company_ids' => $object_company_ids]);
        foreach ($available_storefronts as $storefront_id => $storefront) {
            foreach ($objects_list as &$object) {
                if (in_array($object['company_id'], $storefront->getCompanyIds())) {
                    $object['storefront_ids'][] = $storefront_id;
                }
            }
            unset($object);
        }
    }

    $filtered_list = fn_suppliers_filter_objects_by_storefronts($objects_list, $shared_object_storefront_ids);

    return $filtered_list;
}

/**
 * Gets object shared companies and searches storefronts based on them
 *
 * @param string $object_type Type for searching shared companies
 * @param int    $object_id   Object identifier
 *
 * @return array Storefronts identifies
 */
function fn_suppliers_get_object_storefront_ids($object_type, $object_id)
{
    $shared_companies = fn_ult_get_object_shared_companies($object_type, $object_id);
    if (empty($shared_companies)) {
        return [];
    }

    /** @var \Tygh\Storefront\Repository $repository */
    $repository = Tygh::$app['storefront.repository'];
    list($storefronts, ) = $repository->find(['company_ids' => $shared_companies]);

    return array_keys($storefronts);
};

/**
 * Filters objects list by its availability for getting storefronts.
 *
 * @param array $objects        List of prepared objects each of them contains 'storefront_ids' key for filtering
 * @param array $storefront_ids List of storefronts identifiers
 *
 * @return array List of objects with are fit the condition
 */
function fn_suppliers_filter_objects_by_storefronts($objects, $storefront_ids)
{
    return array_filter($objects, function($object) use ($storefront_ids) {
        if (empty($object['storefront_ids'])) {
            return false;
        }
        foreach ($object['storefront_ids'] as $object_storefront_id) {
            if (in_array($object_storefront_id, $storefront_ids)) {
                return true;
            }
        }

        return false;
    });
}

/**
 * The "template_email_get_name" hook handler.
 *
 * Actions performed:
 *  - Forms template name for different order statuses
 *
 * @param \Tygh\Template\Mail\Template $template Instance of email template.
 * @param string                       $name     Current template name.
 *
 * @see \Tygh\Template\Mail\Template::getName()
 */
function fn_suppliers_template_email_get_name($template, &$name)
{
    static $statuses = null;

    if (strpos($template->getCode(), 'suppliers_notification.') === false) {
        return;
    }

    if ($statuses === null) {
        $statuses = fn_get_statuses(STATUSES_ORDER, [], true, true);
    }

    list(, $status) = explode('.', $template->getCode());
    $status = strtoupper($status);

    if (!isset($statuses[$status])) {
        return;
    }
    $name = __('email_template.supplier_notification_with_status', ['[name]' => $statuses[$status]['description']]);
}


/**
 * The "update_status_pre" hook handler.
 *
 * Actions performed:
 *  - Creates supplier notification template for new order status
 *
 * @param string $status       One-letter status code
 * @param array  $status_data  Status description and properties
 * @param string $type         One-letter status type
 * @param string $lang_code    Two-letter language code
 * @param bool   $can_continue If true, status description and data will be updated
 *
 * @see fn_update_status()
 */
function fn_suppliers_update_status_pre($status, $status_data, $type, $lang_code, $can_continue)
{
    if (!$can_continue || ($type !== STATUSES_ORDER) || empty($status_data['status_id']) || empty($status_data['status'])) {
        return;
    }

    /** @var \Tygh\Template\Mail\Repository $repository */
    $repository = Tygh::$app['template.mail.repository'];
    /** @var \Tygh\Template\Mail\Service $service */
    $service = Tygh::$app['template.mail.service'];

    $email_template = $repository->findByCodeAndArea('suppliers_notification_default', 'A');

    if (!$email_template) {
        return;
    }

    $service->cloneTemplate(
        $email_template,
        [
            'code' => 'suppliers_notification.' . strtolower($status_data['status']),
            'area' => 'A'
        ]
    );
}

/**
 * The "delete_status_post" hook handler.
 *
 * Actions performed:
 *  - Deletes supplier notification template when order status is deleting
 *
 * @param string    $status     One-letter status code
 * @param string    $type       One-letter status type
 * @param string    $can_delete One-letter status code if status can be deleted
 * @param bool|null $is_default True if status is default, false if status is not default, null otherwise
 * @param int|null  $status_id  Status identifier
 *
 * @see fn_delete_status()
 */
function fn_suppliers_delete_status_post($status, $type, $can_delete, $is_default, $status_id)
{
    if (!$can_delete || ($type !== STATUSES_ORDER)) {
        return;
    }

    /** @var \Tygh\Template\Mail\Service $service */
    $service = Tygh::$app['template.mail.service'];
    $service->removeTemplateByCode('suppliers_notification.' . strtolower($status));
}
