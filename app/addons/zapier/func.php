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

use Tygh\Http;

defined('BOOTSTRAP') or die('Access denied');

/**
 * Gets zapier hooks.
 *
 * @param array<string, string> $params Request parameters
 *
 * @return array
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_zapier_get_hooks(array $params = [])
{
    $default_params = [
        'items_per_page' => 0,
        'page' => 1,
    ];

    $params = array_merge($default_params, $params);

    $fields = [
        '?:zapier_hooks.hook_id',
        '?:zapier_hooks.hook_url',
        '?:zapier_hooks.trigger_name',
    ];

    $sorting = $condition = '';

    if (isset($params['id']) && fn_string_not_empty($params['id'])) {
        $params['id'] = trim($params['id']);
        $condition .= db_quote('AND ?:zapier_hooks.hook_id = ?i', $params['id']);
    }

    if (isset($params['trigger_name']) && fn_string_not_empty($params['trigger_name'])) {
        $params['trigger_name'] = trim($params['trigger_name']);
        $condition .= db_quote('AND ?:zapier_hooks.trigger_name LIKE ?l', '%' . $params['trigger_name'] . '%');
    }

    $limit = $join = $group_by = '';
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field(
            'SELECT COUNT(?:zapier_hooks.hook_id) FROM ?:zapier_hooks ?p WHERE 1=1 ?p ?p ?p',
            $join,
            $condition,
            $group_by,
            $sorting
        );
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $items = db_get_array(
        'SELECT ?p FROM ?:zapier_hooks ?p WHERE 1=1 ?p ?p ?p ?p',
        implode(',', $fields),
        $join,
        $condition,
        $group_by,
        $sorting,
        $limit
    );

    return [$items, $params];
}

/**
 * Updates zapier hook.
 *
 * @param array<string, string> $data    Update parameters
 * @param int                   $hook_id Zapier hook identifier
 *
 * @return int
 */
function fn_zapier_update_hook(array $data, $hook_id = 0)
{
    if ($hook_id) {
        db_query('UPDATE ?:zapier_hooks SET ?u WHERE hook_id = ?i', $data, $hook_id);
    } else {
        if (empty($data['timestamp'])) {
            $data['timestamp'] = TIME;
        }
        $hook_id = db_query('INSERT INTO ?:zapier_hooks ?e', $data);
    }

    return $hook_id;
}

/**
 * Deletes zapier hook.
 *
 * @param int $hook_id Zapier hook identifier
 *
 * @return int
 */
function fn_zapier_delete_hook($hook_id)
{
    return db_query('DELETE FROM ?:zapier_hooks WHERE hook_id = ?i', $hook_id);
}

/**
 * The "place_order_post" hook handler.
 *
 * Actions performed:
 * - Sends request with order info to zapier hook url.
 *
 * @param array<string, string> $cart            Cart data
 * @param array<string, string> $auth            Authentication data
 * @param string                $action          Current action. Can be empty or "save"
 * @param int                   $issuer_id       Issuer identifier
 * @param int                   $parent_order_id Parent order identifier
 * @param int                   $order_id        Order identifier
 *
 * @return void
 *
 * @see \fn_place_order()
 */
function fn_zapier_place_order_post(array $cart, array $auth, $action, $issuer_id, $parent_order_id, $order_id)
{
    list($zapier_hooks,) = fn_zapier_get_hooks();
    foreach ($zapier_hooks as $hook) {
        if ($hook['trigger_name'] !== 'new_order') {
            continue;
        }
        $order_data = fn_get_order_info($order_id, false, false);
        //The processor_params removed by security reason.
        unset($order_data['payment_method']['processor_params']);

        unset($order_data['product_groups']);
        // For Line-Items support in Zapier
        $order_data['products'] = array_values($order_data['products']);

        Http::post($hook['hook_url'], json_encode($order_data));
    }
}

/**
 * The "change_order_status_post" hook handler.
 *
 * Actions performed:
 * - Sends request to zapier hook url with info that order is paid.
 *
 * @param int    $order_id    Order identifier
 * @param string $status_to   New order status (one char)
 * @param string $status_from Old order status (one char)
 *
 * @return void
 *
 * @see \fn_change_order_status()
 */
function fn_zapier_change_order_status_post($order_id, $status_to, $status_from)
{
    list($zapier_hooks,) = fn_zapier_get_hooks();
    $paid_statuses = fn_get_settled_order_statuses();
    foreach ($zapier_hooks as $hook) {
        if ($hook['trigger_name'] !== 'order_paid' || !in_array($status_to, $paid_statuses) || in_array($status_from, $paid_statuses)) {
            continue;
        }
        $order_data = fn_get_order_info($order_id, false, false);
        //The processor_params removed by security reason.
        unset($order_data['payment_method']['processor_params']);

        Http::post($hook['hook_url'], json_encode($order_data));
    }
}

/**
 * The "update_product_post" hook handler.
 *
 * Actions performed:
 * - Sends request to zapier hook url with info that new product was created.
 *
 * @param array<string, string> $product_data Product data
 * @param int                   $product_id   Product integer identifier
 * @param string                $lang_code    Two-letter language code (e.g. 'en', 'ru', etc.)
 * @param bool                  $create       Flag determines if product was created (true) or just updated (false).
 *
 * @return void
 *
 * @see \fn_update_product()
 */
function fn_zapier_update_product_post(array $product_data, $product_id, $lang_code, $create)
{
    list($zapier_hooks,) = fn_zapier_get_hooks();
    $auth = [];
    foreach ($zapier_hooks as $hook) {
        if ($hook['trigger_name'] !== 'new_product' || !$create) {
            continue;
        }

        $data = fn_get_product_data($product_id, $auth, $lang_code, '', true, true, true, true, false, false);
        if (!empty($data['price'])) {
            $data['price'] = round($data['price'], 2);
        }
        Http::post($hook['hook_url'], json_encode($data));
    }
}

/**
 * The "create_shipment_post" hook handler.
 *
 * Actions performed:
 * - Sends request to zapier hook url with info that new shipment was created.
 *
 * @param array<string, string> $shipment_data Array of shipment data
 * @param array<string, string> $order_info    Shipment order info
 * @param int                   $group_key     Group number
 * @param bool                  $all_products  All products or not
 * @param int                   $shipment_id   Created shipment identifier
 *
 * @return void
 *
 * @see \fn_update_shipment()
 */
function fn_zapier_create_shipment_post(array $shipment_data, array $order_info, $group_key, $all_products, $shipment_id)
{
    list($zapier_hooks,) = fn_zapier_get_hooks();

    foreach ($zapier_hooks as $hook) {
        if ($hook['trigger_name'] !== 'new_shipment') {
            continue;
        }

        $params = [];
        $params['shipment_id'] = $shipment_id;
        $params['advanced_info'] = true;
        list($data, ) = fn_get_shipments_info($params);
        $data[0]['email'] = $order_info['email'];
        Http::post($hook['hook_url'], json_encode($data[0]));
    }
}

/**
 * The "create_call_request_post" hook handler.
 *
 * Actions performed:
 * - Sends request to zapier hook url with info that new call_request was created.
 *
 * @param array<string, string> $data       Array of shipment data
 * @param int                   $request_id Created call request identifier
 *
 * @return void
 *
 * @see \fn_update_call_request()
 */
function fn_zapier_create_call_request_post(array $data, $request_id)
{
    list($zapier_hooks,) = fn_zapier_get_hooks();

    foreach ($zapier_hooks as $hook) {
        if ($hook['trigger_name'] !== 'new_call_request') {
            continue;
        }

        $params = [];
        $params['id'] = $request_id;
        $params['company_id'] = $data['company_id'];
        list($request_data, ) = fn_get_call_requests($params);
        Http::post($hook['hook_url'], json_encode($request_data[0]));
    }
}

/**
 * The "update_profile" hook handler.
 *
 * Actions performed:
 * - Sends request to zapier hook url with info that new user was created.
 *
 * @param string                $action    Action for user: 'add' or 'update'
 * @param array<string, string> $user_data Created user data
 *
 * @return void
 *
 * @see \fn_update_user()
 */
function fn_zapier_update_profile($action, array $user_data)
{
    list($zapier_hooks,) = fn_zapier_get_hooks();

    foreach ($zapier_hooks as $hook) {
        if ($hook['trigger_name'] !== 'new_user' || $action !== 'add') {
            continue;
        }

        $params = [];
        $auth = [];
        $params['user_id'] = $user_data['user_id'];
        list($data, ) = fn_get_users($params, $auth);
        Http::post($hook['hook_url'], json_encode($data[0]));
    }
}

/**
 * The "update_company" hook handler.
 *
 * Actions performed:
 * - Sends request to zapier hook url with info that new vendor was created.
 *
 * @param array<string, string> $company_data Company data
 * @param int                   $company_id   Company integer identifier
 * @param string                $lang_code    Two-letter language code (e.g. 'en', 'ru', etc.)
 * @param string                $action       Flag determines if company was created (add) or just updated (update)
 *
 * @return void
 *
 * @see \fn_update_company()
 */
function fn_zapier_update_company(array $company_data, $company_id, $lang_code, $action)
{
    if (!fn_allowed_for('MULTIVENDOR')) {
        return;
    }
    list($zapier_hooks,) = fn_zapier_get_hooks();

    foreach ($zapier_hooks as $hook) {
        if ($hook['trigger_name'] !== 'new_vendor' || $action !== 'add') {
            continue;
        }

        $data = fn_get_company_data($company_id);
        Http::post($hook['hook_url'], json_encode($data));
    }
}

/**
 * The "api_orders_create_after_get_user_data" hook handler.
 *
 * Actions performed:
 * - Sends request to zapier hook url with info that new product was created.
 *
 * @param array<string|array> $params Request parameters
 *
 * @return void
 *
 * @see \Tygh\Api\Entities\Orders::create()
 */
function fn_zapier_api_orders_create_after_get_user_data(array &$params)
{
    if (!isset($params['products'])) {
        return;
    }
    $keys = array_keys($params['products']);
    if ($keys[0] !== 0) {
        return;
    }
    array_unshift($params['products'], null);
    unset($params['products'][0]);
}
