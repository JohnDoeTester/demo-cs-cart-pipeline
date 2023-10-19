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
use Tygh\Enum\OrderStatuses;
use Tygh\Notifications\EventIdProviders\OrderProvider;
use Tygh\Registry;
use Tygh\Storage;
use Tygh\Tools\SecurityHelper;
use Tygh\Tygh;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

fn_define('ORDER_MANAGEMENT', true); // Defines that the cart is in order management mode now

Tygh::$app['session']['cart'] = isset(Tygh::$app['session']['cart']) ? Tygh::$app['session']['cart'] : array();
$cart = & Tygh::$app['session']['cart'];

Tygh::$app['session']['customer_auth'] = isset(Tygh::$app['session']['customer_auth']) ? Tygh::$app['session']['customer_auth'] : array();
$customer_auth = & Tygh::$app['session']['customer_auth'];

Tygh::$app['session']['shipping_rates'] = isset(Tygh::$app['session']['shipping_rates']) ? Tygh::$app['session']['shipping_rates'] : array();
$shipping_rates = & Tygh::$app['session']['shipping_rates'];

if (empty($customer_auth)) {
    $customer_auth = fn_fill_auth(array(), array(), false, 'C');
}

$suffix = !empty($cart['order_id']) ? '.update' : '.add';

if (fn_allowed_for('ULTIMATE') && $mode != 'edit' && $mode != 'new') {
    if (
        (Registry::get('runtime.company_id') && !empty($cart['order_company_id']) && Registry::get('runtime.company_id') != $cart['order_company_id'])
        || (!Registry::get('runtime.company_id') && !empty($cart['order_company_id']))
    ) {
        if (Registry::get('runtime.company_id')) {
            fn_set_notification('W', __('warning'), __('orders_not_allow_to_change_company'));
        }

        if (fn_get_available_company_ids($cart['order_company_id'])) {
            return array(CONTROLLER_STATUS_REDIRECT, fn_link_attach(Registry::get('config.current_url'), 'switch_company_id=' . $cart['order_company_id']));
        } else {
            return array(CONTROLLER_STATUS_DENIED);
        }

    } elseif (empty($cart['order_company_id']) && Registry::get('runtime.company_id')) {
        $cart['order_company_id'] = Registry::get('runtime.company_id');
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if (
        !empty($_REQUEST['order_id']) && !empty($cart['order_id'])
        && $_REQUEST['order_id'] !== $cart['order_id']
    ) {
        fn_set_notification(NotificationSeverity::WARNING, __('warning'), __('only_one_order_can_be_edited_at_a_time'));
        return [CONTROLLER_STATUS_REDIRECT, fn_url('orders.details?order_id=' . $_REQUEST['order_id'])];
    }

    // Add product to the cart
    if ($mode == 'add') {
        // Cart is empty, create it
        if (empty($cart)) {
            fn_clear_cart($cart);
        }

        if (!empty($_REQUEST['product_data'])) {
            $product_id = empty($_REQUEST['product_id']) ? 0 : $_REQUEST['product_id'];

            // Remove products with empty amount
            foreach ($_REQUEST['product_data'] as $k => $v) {
                if ($k != 'custom_files' && (empty($v['amount']) || ($product_id && $k != $product_id))) {
                    unset($_REQUEST['product_data'][$k]);
                }
            }

            fn_add_product_to_cart($_REQUEST['product_data'], Tygh::$app['session']['cart'], $customer_auth);

            foreach ($cart['products'] as $id => $product) {
                if (!empty($product['extra']['promotions'])) {
                    unset($cart['products'][$id]['extra']['promotions']);
                }
            }

            $cart['recalculate_catalog_promotions'] = true;
            fn_calculate_cart_content($cart, $customer_auth);
        }
    }

    // Delete products from the cart
    if ($mode == 'delete') {
        if (!empty($_REQUEST['cart_ids'])) {
            foreach ($_REQUEST['cart_ids'] as $cart_id) {
                fn_delete_cart_product($cart, $cart_id);
            }
        }
    }

    // Select customer
    if ($mode == 'select_customer') {
        if (!empty($_REQUEST['selected_user_id'])) {
            $cart['user_id'] = $_REQUEST['selected_user_id'];
            $u_data = db_get_row("SELECT user_id, tax_exempt, user_type FROM ?:users WHERE user_id = ?i", $cart['user_id']);
            $customer_auth = fn_fill_auth($u_data, array(), false, 'C');
            $cart['user_data'] = array();
        }
    }

    // update products quantity and etc.
    if ($mode == 'update_totals') {
        fn_update_cart_by_data($cart, $_REQUEST, $customer_auth);
    }

    if ($mode == 'customer_info') {

        $profile_id = !empty($_REQUEST['profile_id']) ? $_REQUEST['profile_id'] : false;

        if ($profile_id !== false) {
            $cart['profile_id'] = $_REQUEST['profile_id'];
        }

        $profile_fields = fn_get_profile_fields('O', $customer_auth);

        // Clean up saved shipping rates
        unset(Tygh::$app['session']['shipping_rates']);

        if (!empty($_REQUEST['user_data'])
            && is_array($_REQUEST['user_data'])
        ) {
            $user_data = $_REQUEST['user_data'];

            fn_convert_profile_dates_to_timestamps($user_data, $profile_fields);

            // Fill shipping info with billing if needed
            if (empty($_REQUEST['ship_to_another'])) {
                fn_fill_address(
                    $user_data,
                    $profile_fields,
                    !fn_compare_shipping_billing($profile_fields)
                );
            }

            // try to update existent user, before descriptions for countries and states are filled
            if ((!empty($cart['user_id']) || !empty($customer_auth['user_id']))
                && empty($_REQUEST['skip_update_user_profile'])
            ) {
                $user_id = !empty($cart['user_id']) ? $cart['user_id'] : $customer_auth['user_id'];

                if ($profile_id !== false) {
                    $user = fn_get_user_info($user_id, true, $profile_id);
                } else {
                    $user = fn_get_user_info($user_id, false);
                }

                if (!empty($user['user_id'])) {
                    fn_update_user(
                        $user['user_id'],
                        array_merge($user_data, array(
                            'phone' => isset($user_data['phone']) ? $user_data['phone'] : $user['phone'],
                            'profile_id' => $profile_id ? $profile_id : null
                        )),
                        $customer_auth,
                        !empty($_REQUEST['ship_to_another']),
                        false
                    );
                }

            }

            // Add descriptions for countries and states
            fn_add_user_data_descriptions($user_data);
            $cart['user_data'] = $user_data;
            $cart['ship_to_another'] = !empty($_REQUEST['ship_to_another']);

            // try to register a new user
            if (empty($cart['order_id'])
                && (Registry::get('settings.Checkout.disable_anonymous_checkout') == 'Y'
                    && !empty($user_data['password1'])
                )
            ) {
                $cart['profile_registration_attempt'] = true;
                list($user_id) = fn_update_user(0, $cart['user_data'], $customer_auth, !empty($_REQUEST['ship_to_another']), true);

                if ($user_id == false) {
                    $action = '';
                } else {
                    $cart['user_id'] = $user_id;
                    $u_data = db_get_row('SELECT user_id, tax_exempt, user_type FROM ?:users WHERE user_id = ?i', $cart['user_id']);
                    $customer_auth = fn_fill_auth($u_data, array(), false, 'C');
                    $cart['user_data'] = array();
                }
            }
        }
    }

    if ($mode == 'place_order') {
        $force_notification = fn_get_notification_rules($_REQUEST);
        list($order_id, $action) = fn_place_order_manually($cart, $_REQUEST, $customer_auth, $action, $auth['user_id'], $force_notification);
        if ($order_id) {
            fn_order_placement_routines($action, $order_id, $force_notification, true);
        } else {
            return [CONTROLLER_STATUS_REDIRECT, 'order_management' . $suffix];
        }
    }

    // Delete discount coupon
    if ($mode == 'delete_coupon') {
        unset($cart['coupons'][$_REQUEST['c_id']], $cart['pending_coupon']);
    }

    //
    // Delete saved custom file
    //
    if ($mode == 'delete_file' && isset($_REQUEST['cart_id'])) {

        if (isset($cart['products'][$_REQUEST['cart_id']]['extra']['custom_files'][$_REQUEST['option_id']][$_REQUEST['file']])) {
            $file = $cart['products'][$_REQUEST['cart_id']]['extra']['custom_files'][$_REQUEST['option_id']][$_REQUEST['file']];

            Storage::instance('custom_files')->delete($file['path']);
            Storage::instance('custom_files')->delete($file['path'] . '_thumb');

            unset($cart['products'][$_REQUEST['cart_id']]['extra']['custom_files'][$_REQUEST['option_id']][$_REQUEST['file']]);
        }

        fn_save_cart_content($cart, $customer_auth['user_id']);

    }

    //
    // Update payment method
    //
    if ($mode == 'update_payment') {
        $cart['payment_id'] = (!empty($_REQUEST['payment_id'])) ? $_REQUEST['payment_id'] : 0;
    }

    //
    // Update shipping method
    //
    if ($mode == 'update_shipping' && isset($_REQUEST['shipping_ids'])) {
        fn_checkout_update_shipping($cart, $_REQUEST['shipping_ids']);
    }

    if ($mode == 'options') {
        define('GET_OPTIONS', true);
        $_auth = $auth;
        $params = $_REQUEST;

        // Apply the specific block template settings
        if (!empty($params['appearance'])) {
            foreach ($params['appearance'] as $setting => $value) {
                Tygh::$app['view']->assign($setting, $value);
            }
        }

        Tygh::$app['view']->assign('show_images', true);
        Tygh::$app['view']->assign('no_capture', true);

        if (!fn_get_data_of_changed_product($params, $_auth, $mode)) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        if (!empty($params['cart_products'])) {
            $products = & Tygh::$app['session']['cart']['products'];
            $cart_products = $params['cart_products'];

            foreach ($cart_products as $product_id => $product) {
                if (empty($product['product_options'])) {
                    return array(CONTROLLER_STATUS_DENIED);
                }

                if (empty($products[$product_id]['product_options'])) {
                    return array(CONTROLLER_STATUS_DENIED);
                }

                $product_options = $product['product_options'];

                if (!empty($params['cart']['products'][$product_id]['product_options'])) {
                    $product_options = $params['cart']['products'][$product_id]['product_options'];
                }

                $products[$product_id]['product_options'] = $product_options;

                if (!empty($products[$product_id]['extra']['product_options'])) {
                    $products[$product_id]['extra']['product_options'] = $product_options;
                }
            }
        }

        exit;
    }

    return array(CONTROLLER_STATUS_OK, 'order_management' . $suffix);
}

if ($mode == 'customer_info') {
    if (!empty($_REQUEST['profile_id'])) {
        $user_data = fn_get_user_info($customer_auth['user_id'], true, $_REQUEST['profile_id']);
        Tygh::$app['view']->assign('user_data', $user_data);
    }

    return array(CONTROLLER_STATUS_OK, 'order_management' . $suffix);
}

//
// Edit order
//
if ($mode == 'edit' && !empty($_REQUEST['order_id'])) {

    fn_clear_cart($cart, true);
    $customer_auth = fn_fill_auth(array(), array(), false, 'C');

    $cart_status = md5(serialize($cart));
    fn_form_cart($_REQUEST['order_id'], $cart, $customer_auth, !empty($_REQUEST['copy']));

    fn_store_shipping_rates($_REQUEST['order_id'], $cart, $customer_auth);

    if ($cart_status == md5(serialize($cart))) {
        // Order info was not found or customer does not have enought permissions
        return array(CONTROLLER_STATUS_DENIED, '');
    }

    if (empty($_REQUEST['copy'])) {
        $cart['order_id'] = $_REQUEST['order_id'];
    } elseif ($_REQUEST['copy'] !== '1') {
        return array(CONTROLLER_STATUS_DENIED, '');
    }

    return array(CONTROLLER_STATUS_REDIRECT, 'order_management.update');

//
// Create new order
//
} elseif ($mode == 'new') {

    fn_clear_cart($cart, true);
    $customer_auth = fn_fill_auth(array(), array(), false, 'C');

    return array(CONTROLLER_STATUS_REDIRECT, 'order_management.add');
//
// Update order page
//
} elseif ($mode == 'update' || $mode == 'add') {

    //
    // Prepare order status info
    //
    if (!empty($cart['order_id'])) {
        $order_info = fn_get_order_short_info($cart['order_id']);
        $cart['order_status'] = $order_info['status'];

        if (!empty($order_info['issuer_id'])) {
            $cart['issuer_data'] = fn_get_user_short_info($order_info['issuer_id']);
        }
    }
    $order_statuses = fn_get_simple_statuses(STATUSES_ORDER, true, true);
    Tygh::$app['view']->assign('order_statuses', $order_statuses);

    //
    // Prepare customer info
    //
    $profile_fields = fn_get_profile_fields('O', $customer_auth);

    $cart['profile_id'] = empty($cart['profile_id']) ? 0 : $cart['profile_id'];
    Tygh::$app['view']->assign('profile_fields', $profile_fields);

    //Get user profiles
    $user_profiles = fn_get_user_profiles($customer_auth['user_id']);
    Tygh::$app['view']->assign('user_profiles', $user_profiles);

    //Get countries and states
    Tygh::$app['view']->assign('countries', fn_get_simple_countries(true, CART_LANGUAGE));
    Tygh::$app['view']->assign('states', fn_get_all_states());
    Tygh::$app['view']->assign('usergroups', fn_get_usergroups(array('type' => 'C', 'status' => array('A', 'H')), DESCR_SL));

    if (!empty($customer_auth['user_id']) && (empty($cart['user_data']) || (!empty($_REQUEST['profile_id']) && $cart['profile_id'] != $_REQUEST['profile_id']))) {
        $cart['profile_id'] = !empty($_REQUEST['profile_id']) ? $_REQUEST['profile_id'] : 0;
        $cart['user_data'] = fn_get_user_info($customer_auth['user_id'], true, $cart['profile_id']);
    }

    if (!empty($cart['user_data'])) {
        fn_filter_hidden_profile_fields($cart['user_data'], 'O');
        $cart['ship_to_another'] = fn_check_shipping_billing($cart['user_data'], $profile_fields);
    }

    //
    // Get products info
    // and shipping rates
    //

    // Clean up saved shipping rates
    // unset(Tygh::$app['session']['shipping_rates']);

    if (!empty($shipping_rates)) {
        define('CACHED_SHIPPING_RATES', true);
    }

    $cart['calculate_shipping'] = true;

    // calculate cart - get products with options, full shipping rates info and promotions
    list ($cart_products, $product_groups) = fn_calculate_cart_content($cart, $customer_auth);
    Tygh::$app['view']->assign('product_groups', $product_groups);

    if (fn_allowed_for('MULTIVENDOR') && !empty($cart['order_id'])) {
        $order_info = fn_get_order_info($cart['order_id']);
        if (isset($order_info['company_id'])) {
            Tygh::$app['view']->assign('order_company_id', $order_info['company_id']);
        }
    }

    fn_gather_additional_products_data($cart_products, [
        'get_icon' => true,
        'get_detailed' => true,
        'get_active_options' => true,
        'get_discounts' => false
    ]);

    Tygh::$app['view']->assign('cart_products', $cart_products);

    Tygh::$app['view']->assign('update_options', true);

    //
    //Get payment methods
    //
    $payment_methods = fn_get_payments(array('usergroup_ids' => $customer_auth['usergroup_ids']));

    // Check if payment method has surcharge rates
    foreach ($payment_methods as $k => $v) {
        if (!isset($cart['payment_id'])) {
            $cart['payment_id'] = $v['payment_id'];
        }
        $payment_methods[$k]['surcharge_value'] = 0;
        if (floatval($v['a_surcharge'])) {
            $payment_methods[$k]['surcharge_value'] += $v['a_surcharge'];
        }
        if (floatval($v['p_surcharge'])) {
            $payment_methods[$k]['surcharge_value'] += fn_format_price($cart['total'] * $v['p_surcharge'] / 100);
        }
    }

    fn_update_payment_surcharge($cart, $auth);
    if (!empty($cart['payment_surcharge'])) {
        $payment_methods[$cart['payment_id']]['surcharge_value'] = $cart['payment_surcharge'];
    }

    //Get payment method info
    if (!empty($cart['payment_id']) && isset($payment_methods[$cart['payment_id']])) {
        $payment_data = fn_get_payment_method_data($cart['payment_id']);
        Tygh::$app['view']->assign('payment_method', $payment_data);
    } elseif (!empty($payment_methods)) {
        $payment_data = fn_get_payment_method_data(reset($payment_methods)['payment_id']);
        Tygh::$app['view']->assign('payment_method', $payment_data);
    }

    Tygh::$app['view']->assign('payment_methods', $payment_methods);

    //
    // Check if order information is complete
    //
    if (fn_cart_is_empty($cart)) {
        Tygh::$app['view']->assign('is_empty_cart', true);
    }

    if (empty($cart['user_data']) || !fn_check_profile_fields($cart['user_data'], 'O', $customer_auth)) {
        Tygh::$app['view']->assign('is_empty_user_data', true);
    }

    Tygh::$app['view']->assign('is_order_management', true);

    if (!empty($order_info['storefront_id'])) {
        Tygh::$app['view']->assign('selected_storefront_id', $order_info['storefront_id']);
    } elseif (!empty($_REQUEST['storefront_id'])) {
        Tygh::$app['view']->assign('selected_storefront_id', $_REQUEST['storefront_id']);
    } else {
        /** @var \Tygh\Storefront\Repository $repository */
        $repository = Tygh::$app['storefront.repository'];
        $runtime_company_id = fn_get_runtime_company_id();

        if ($runtime_company_id) {
            /** @var \Tygh\Storefront\Storefront $storefronts_available */
            $storefronts_available = $repository->findAvailableForCompanyId($runtime_company_id);
            $storefront_id = empty($storefronts_available) ? 0 : $storefronts_available->storefront_id;
        } else {
            $storefront_id = $repository->findDefault()->storefront_id;
        }
        Tygh::$app['view']->assign('selected_storefront_id', $storefront_id);
    }

} elseif ($mode == 'get_custom_file' && isset($_REQUEST['cart_id']) && isset($_REQUEST['option_id']) && isset($_REQUEST['file'])) {
    if (isset($cart['products'][$_REQUEST['cart_id']]['extra']['custom_files'][$_REQUEST['option_id']][$_REQUEST['file']])) {
        $file = $cart['products'][$_REQUEST['cart_id']]['extra']['custom_files'][$_REQUEST['option_id']][$_REQUEST['file']];

        Storage::instance('custom_files')->get($file['path'], $file['name']);
    }
}

Tygh::$app['view']->assign('cart', $cart);

if (!Tygh::$app['view']->getTemplateVars('user_data') && !empty($cart['user_data'])) {
    Tygh::$app['view']->assign('user_data', $cart['user_data']);
}

Tygh::$app['view']->assign('customer_auth', $customer_auth);

/**
 * Places an order from the administration panel.
 *
 * @param array  $cart               Order cart contents
 * @param array  $params             Request parameters
 * @param array  $customer_auth      Customer authentication data
 * @param string $action             Order placement action
 * @param int    $issuer_id          ID of a user who issues the order
 * @param array  $force_notification Notification rules
 *
 * @return array Order ID and the placement action
 *
 * @internal
 */
function fn_place_order_manually(&$cart, $params, $customer_auth, $action, $issuer_id, $force_notification)
{
    // Clean up saved shipping rates
    unset(Tygh::$app['session']['shipping_rates']);

    // update totals and etc.
    fn_update_cart_by_data($cart, $params, $customer_auth);

    if (!empty($params['shipping_ids'])) {
        fn_checkout_update_shipping($cart, $params['shipping_ids']);
    }

    if (empty($cart['stored_shipping'])) {
        $cart['calculate_shipping'] = true;
    }

    // fill out payment method by default if no one was chosen
    if (empty($cart['payment_id'])) {
        $cart['payment_id'] = (!empty($params['payment_id'])) ? $params['payment_id'] : 0;
    }

    if ((float) $cart['total'] == 0) {
        $cart['payment_id'] = 0;
    }

    // recalculate cart content after update
    list($cart_products, $product_groups) = fn_calculate_cart_content($cart, $customer_auth);
    fn_update_payment_surcharge($cart, $customer_auth);
    $cart['storefront_id'] = !empty($params['storefront_id']) ? $params['storefront_id'] : null;
    if ($cart['storefront_id'] === null) {
        /** @var \Tygh\Storefront\Repository $repository */
        $repository = Tygh::$app['storefront.repository'];
        if (fn_allowed_for('ULTIMATE')) {
            /** @var \Tygh\Storefront\Storefront $selected_storefront */
            $selected_storefront = $repository->findByCompanyId($cart['order_company_id'], true);
            if ($selected_storefront) {
                $cart['storefront_id'] = $selected_storefront->storefront_id;
            }
        } else {
            $default_storefront = $repository->findDefault();
            if ($default_storefront) {
                $cart['storefront_id'] = $default_storefront->storefront_id;
            }
        }
    }

    $cart['notes'] = !empty($params['customer_notes']) ? SecurityHelper::sanitizeHtml($params['customer_notes']) : '';
    $cart['payment_info'] = !empty($params['payment_info']) ? $params['payment_info'] : array();

    list($order_id, $process_payment) = fn_place_order($cart, $customer_auth, $action, $issuer_id);

    if ($order_id) {
        if ($action != 'save') {
            $action = 'route';
        }

        $is_status_set_by_payment = false;
        if ($process_payment) {
            $payment_info = !empty($cart['payment_info'])
                ? $cart['payment_info']
                : [];
            $is_status_set_by_payment = fn_start_payment($order_id, $force_notification, $payment_info);
        }

        if (!empty($params['update_order']['details'])) {
            db_query(
                'UPDATE ?:orders SET details = ?s WHERE order_id = ?i',
                SecurityHelper::sanitizeHtml($params['update_order']['details']),
                $order_id
            );
        }

        $requested_status = isset($params['order_status'])
            ? $params['order_status']
            : null;

        $current_status = fn_get_order_short_info($order_id)['status'];
        $is_status_change_required = $requested_status && $current_status !== $requested_status;

        // order.updated event will be triggered only when order notification wasn't set by any other means
        $is_order_placed_notification_required = true;

        // change status if it was provided
        if ($is_status_change_required) {
            if ($is_status_set_by_payment) {
                $is_order_placed_notification_required = false;
                fn_set_notification(
                    NotificationSeverity::WARNING,
                    __('warning'),
                    __('status_changed_after_process_payment')
                );
            } elseif (fn_check_permissions('orders', 'update_status', 'admin')) {
                if (fn_change_order_status($order_id, $requested_status, '', $force_notification)) {
                    $actual_status = fn_get_order_short_info($order_id)['status'];
                    if (
                        $actual_status !== OrderStatuses::PARENT
                        && $requested_status !== $actual_status
                    ) {
                        fn_set_notification(
                            NotificationSeverity::WARNING,
                            __('warning'),
                            __('status_changed')
                        );
                    }
                } else {
                    fn_set_notification(
                        NotificationSeverity::ERROR,
                        __('error'),
                        __('error_status_not_changed')
                    );
                }
            }
        }

        if ($is_order_placed_notification_required) {
            /** @var \Tygh\Notifications\EventDispatcher $event_dispatcher */
            $event_dispatcher = Tygh::$app['event.dispatcher'];
            /** @var \Tygh\Notifications\Settings\Factory $notification_settings_factory */
            $notification_settings_factory = Tygh::$app['event.notification_settings.factory'];
            $notification_rules = $notification_settings_factory->create($force_notification);

            $orders_data = [];
            $order_info = $orders_data[] = fn_get_order_info($order_id);

            if ($order_info['status'] === OrderStatuses::PARENT) {
                $orders_data = [];
                $child_ids = db_get_fields('SELECT order_id FROM ?:orders WHERE parent_order_id = ?i', $order_info['order_id']);
                foreach ($child_ids as $child_id) {
                    $orders_data[] = fn_get_order_info($child_id);
                }
            }

            foreach ($orders_data as $order_data) {
                if (empty($order_data)) {
                    continue;
                }

                $edp_data = fn_generate_ekeys_for_edp([
                    'status_from' => OrderStatuses::INCOMPLETED,
                    'status_to'   => $order_data['status'],
                ], $order_data);

                $event_dispatcher->dispatch(
                    'order.updated',
                    ['order_info' => $order_data],
                    $notification_rules,
                    new OrderProvider($order_data)
                );

                if ($edp_data) {
                    $notification_rules = fn_get_edp_notification_rules($force_notification, $edp_data);
                    $event_dispatcher->dispatch(
                        'order.edp',
                        [
                            'order_info' => $order_data,
                            'edp_data' => $edp_data
                        ],
                        $notification_rules,
                        new OrderProvider($order_data, $edp_data)
                    );
                }
            }

            $edp_data = fn_generate_ekeys_for_edp([
                'status_from' => OrderStatuses::INCOMPLETED,
                'status_to'   => $order_info['status'],
            ], $order_info);

            fn_order_notification($order_info, $edp_data, $force_notification, 'order.updated');
        }

        /**
         * Executes after an order was created in the administration panel, allows you to perform additional actions
         * and modify returned results.
         *
         * @param array  $cart                                  Order cart contents
         * @param array  $params                                Request parameters
         * @param array  $customer_auth                         Customer authentication data
         * @param string $action                                Order placement action
         * @param int    $issuer_id                             ID of a user who issues the order
         * @param array  $force_notification                    Notification rules
         * @param array  $order_info                            Order information
         * @param array  $edp_data                              Downloadable products information
         * @param bool   $is_order_placed_notification_required Whether a new order notification must be sent
         */
        fn_set_hook(
            'place_order_manually_post',
            $cart,
            $params,
            $customer_auth,
            $action,
            $issuer_id,
            $force_notification,
            $order_info,
            $edp_data,
            $is_order_placed_notification_required
        );
    }

    return [$order_id, $action];
}
