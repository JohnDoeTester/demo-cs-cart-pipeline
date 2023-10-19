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
use Tygh\Enum\SiteArea;
use Tygh\Enum\YesNo;
use Tygh\Registry;
use Tygh\Storage;
use Tygh\Enum\ProductZeroPriceActions;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (!empty($_REQUEST['order_id']) && $mode != 'search') {
    // If user is not logged in and trying to see the order, redirect him to login form
    if (empty($auth['user_id']) && empty($auth['order_ids'])) {
        return array(
            CONTROLLER_STATUS_REDIRECT,
            'auth.login_form?return_url=' . urlencode(Registry::get('config.current_url'))
        );
    }

    if (!fn_is_order_allowed($_REQUEST['order_id'], $auth)) {
        return array(CONTROLLER_STATUS_DENIED);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode == 'repay') {
        $order_info = fn_get_order_info($_REQUEST['order_id']);

        $payment_info = empty($_REQUEST['payment_info']) ? array() : $_REQUEST['payment_info'];

        // Save payment information
        if (!empty($payment_info)) {

            // This should not be here, repay must be refactored to use fn_place_order
            if (!empty($payment_info['card_number'])) {
                $payment_info['card_number'] = str_replace(array(' ', '-'), '', $payment_info['card_number']);
            }
            $_data = array (
                'order_id' => $_REQUEST['order_id'],
                'type' => 'P', //payment information
                'data' => fn_encrypt_text(serialize($payment_info)),
            );

            db_query("REPLACE INTO ?:order_data ?e", $_data);
        } else {
            db_query("DELETE FROM ?:order_data WHERE type = 'P' AND order_id = ?i", $_REQUEST['order_id']);
        }

        // Change payment method
        $update_order = [
            'payment_id' => $_REQUEST['payment_id'],
            'repaid'     => ++$order_info['repaid'],
            'updated_at' => TIME
        ];

        // Add new customer notes
        if (!empty($_REQUEST['customer_notes'])) {
            $update_order['notes'] = (!empty($order_info['notes']) ? $order_info['notes'] . "\n" : '') . $_REQUEST['customer_notes'];
        }

        // Update total and surcharge amount
        $payment = fn_get_payment_method_data($_REQUEST['payment_id']);
        if (!empty($payment['p_surcharge']) || !empty($payment['a_surcharge'])) {
            $surcharge_value = 0;
            if (floatval($payment['a_surcharge'])) {
                $surcharge_value += $payment['a_surcharge'];
            }
            if (floatval($payment['p_surcharge'])) {
                $surcharge_value += fn_format_price(($order_info['total'] - $order_info['payment_surcharge']) * $payment['p_surcharge'] / 100);
            }
            $update_order['payment_surcharge'] = $surcharge_value;
            if (fn_allowed_for('MULTIVENDOR') && fn_take_payment_surcharge_from_vendor($order_info['products'])) {
                $update_order['total'] = fn_format_price($order_info['total']);
            } else {
                $update_order['total'] = fn_format_price($order_info['total'] - $order_info['payment_surcharge'] + $surcharge_value);
            }
        } else {
            if (fn_allowed_for('MULTIVENDOR') && fn_take_payment_surcharge_from_vendor($order_info['products'])) {
                $update_order['total'] = fn_format_price($order_info['total']);
            } else {
                $update_order['total'] = fn_format_price($order_info['total'] - $order_info['payment_surcharge']);
            }
            $update_order['payment_surcharge'] = 0;
        }

        //Default change order status back to Open
        $change_order_status = STATUSES_ORDER;

        /**
         * Data change for a repayed order
         * @param array     $order_info Order information
         * @param array     $update_order New order data
         * @param array     $payment  Payment information
         * @param array     $payment_info Payment information received from a user
         * @param string    $change_order_status New order status
         */
        fn_set_hook('repay_order', $order_info, $update_order, $payment, $payment_info, $change_order_status);

        db_query('UPDATE ?:orders SET ?u WHERE order_id = ?i', $update_order, $_REQUEST['order_id']);

        // Change order status and restore amount.
        fn_change_order_status($order_info['order_id'], $change_order_status, $order_info['status'], fn_get_notification_rules(array(), false));

        Tygh::$app['session']['cart']['placement_action'] = 'repay';

        // Process order (payment)
        fn_start_payment($order_info['order_id'], array(), $payment_info);

        fn_order_placement_routines('repay', $order_info['order_id'], array(), true);

    // Request for order tracking
    } elseif ($mode == 'track_request') {
        $condition = fn_get_company_condition('?:orders.company_id');

        if (!empty($auth['user_id'])) {

            $allowed_id = db_get_field(
                'SELECT user_id '
                . 'FROM ?:orders '
                . 'WHERE user_id = ?i AND order_id = ?i AND is_parent_order != ?s ?p',
                $auth['user_id'],
                $_REQUEST['track_data'],
                YesNo::YES,
                $condition
            );

            if (!empty($allowed_id)) {
                Tygh::$app['ajax']->assign('force_redirection',
                    fn_url('orders.details?order_id=' . $_REQUEST['track_data']));
                exit;
            } else {
                fn_set_notification(NotificationSeverity::ERROR, __('error'), __('warning_track_orders_not_allowed'));
            }
        } else {
            $email = '';

            if (!empty($_REQUEST['track_data'])) {
                $o_id = 0;
                // If track by email
                if (strpos($_REQUEST['track_data'], '@') !== false) {
                    $order_info = db_get_row(
                        'SELECT order_id, email, company_id, lang_code, storefront_id'
                        . ' FROM ?:orders'
                        . ' WHERE email = ?s ?p ORDER BY timestamp DESC LIMIT 1',
                        $_REQUEST['track_data'],
                        $condition
                    );
                    // Assume that this is order number
                } else {
                    $order_info = db_get_row(
                        'SELECT order_id, email, company_id, lang_code, storefront_id'
                        . ' FROM ?:orders'
                        . ' WHERE order_id = ?i ?p',
                        $_REQUEST['track_data'],
                        $condition
                    );
                }
            }

            if (!empty($order_info['email'])) {
                /** @var \Tygh\Mailer\Mailer $mailer */
                $mailer = Tygh::$app['mailer'];

                // Create access key
                $ekey = fn_generate_ekey($order_info['email'], 'T', SECONDS_IN_HOUR);

                $company_id = fn_get_company_id('orders', 'order_id', $order_info['order_id']);

                $result = $mailer->send(
                    [
                        'to'            => $order_info['email'],
                        'from'          => 'company_orders_department',
                        'data'          => [
                            'access_key'    => $ekey,
                            'order_id'      => $order_info['order_id'],
                            'url'           => fn_url(
                                'orders.track?ekey=' . $ekey . '&o_id=' . $order_info['order_id'] . '&storefront_id=' . $order_info['storefront_id'],
                                SiteArea::STOREFRONT,
                                'http'
                            ),
                            'track_all_url' => fn_url(
                                'orders.track?ekey=' . $ekey . '&storefront_id=' . $order_info['storefront_id'],
                                SiteArea::STOREFRONT,
                                'http'
                            ),
                        ],
                        'template_code' => 'track',
                        'tpl'           => 'orders/track.tpl', // this parameter is obsolete and is used for back compatibility
                        'company_id'    => $company_id,
                        'storefront_id' => $order_info['storefront_id'],
                    ],
                    SiteArea::STOREFRONT,
                    $order_info['lang_code']
                );

                if ($result) {
                    fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('text_track_instructions_sent'));
                }
            } else {
                fn_set_notification(NotificationSeverity::ERROR, __('error'), __('warning_track_orders_not_found'));
            }
        }

        return [CONTROLLER_STATUS_OK, $_REQUEST['return_url']];
    }

    return [CONTROLLER_STATUS_OK, 'orders.details?order_id=' . $_REQUEST['order_id']];
}

fn_add_breadcrumb(__('orders'), $mode === 'search' ? '' : 'orders.search');

//
// Show invoice
//
if ($mode == 'invoice') {
    fn_add_breadcrumb(__('order') . ' #' . $_REQUEST['order_id'], "orders.details?order_id=$_REQUEST[order_id]");
    fn_add_breadcrumb(__('invoice'));

    Tygh::$app['view']->assign('order_info', fn_get_order_info($_REQUEST['order_id']));

} elseif ($mode === 'print_invoice') {
    if (!empty($_REQUEST['order_id'])) {
        $print_params = [];
        if (fn_allowed_for('MULTIVENDOR')) {
            $print_params['use_i18n_company_fields'] = true;
        }
        $print_params = array_merge($_REQUEST, $print_params);
        echo(fn_print_order_invoices($_REQUEST['order_id'], $print_params));
    }

    return [CONTROLLER_STATUS_NO_CONTENT];
} elseif ($mode == 'track') {
    if (!empty($_REQUEST['ekey'])) {
        $email = fn_get_object_by_ekey($_REQUEST['ekey'], 'T');

        if (empty($email)) {
            return array(CONTROLLER_STATUS_DENIED);
        }

        $auth['order_ids'] = db_get_fields("SELECT order_id FROM ?:orders WHERE email = ?s", $email);

        if (!empty($_REQUEST['o_id']) && in_array($_REQUEST['o_id'], $auth['order_ids'])) {
            return array(CONTROLLER_STATUS_REDIRECT, 'orders.details?order_id=' . $_REQUEST['o_id']);
        } else {
            return array(CONTROLLER_STATUS_REDIRECT, 'orders.search');
        }
    } else {
        return array(CONTROLLER_STATUS_DENIED);
    }

    exit;

//
// Show order details
//
} elseif ($mode == 'details') {

    fn_add_breadcrumb(__('order_info'));

    $order_info = fn_get_order_info($_REQUEST['order_id']);

    if (empty($order_info)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    if ($order_info['is_parent_order'] == 'Y') {
        $child_ids = db_get_fields("SELECT order_id FROM ?:orders WHERE parent_order_id = ?i", $_REQUEST['order_id']);

        return array(CONTROLLER_STATUS_REDIRECT, 'orders.search?period=A&order_id=' . implode(',', $child_ids));
    }

    foreach ($order_info['products'] as $k => $item) {
        $order_info['products'][$k]['main_pair'] = fn_get_cart_product_icon($item['product_id'], $order_info['products'][$k]);
    }

    if (fn_allowed_for('MULTIVENDOR')) {
        Tygh::$app['view']->assign('take_surcharge_from_vendor', fn_take_payment_surcharge_from_vendor($order_info['products']));
    }
    // Repay functionality
    $statuses = fn_get_statuses(STATUSES_ORDER, array(), true);

    if (!empty($statuses[$order_info['status']]['params']['repay']) && $statuses[$order_info['status']]['params']['repay'] == 'Y') {
        fn_prepare_repay_data(empty($_REQUEST['payment_id']) ? 0 : $_REQUEST['payment_id'], $order_info, $auth);
    }

    $navigation_tabs = array(
        'general' => array(
            'title' => __('general'),
            'js' => true,
            'href' => 'orders.details?order_id=' . $_REQUEST['order_id'] . '&selected_section=general'
        ),
    );

    list($shipments) = fn_get_shipments_info(array('order_id' => $order_info['order_id'], 'advanced_info' => true));
    $use_shipments = !empty($shipments);

    if ($use_shipments) {
        $navigation_tabs['shipment_info'] = [
            'title' => __('shipment_info'),
            'js'    => true,
            'href'  => 'orders.details?order_id=' . $_REQUEST['order_id'] . '&selected_section=shipment_info'
        ];
    }

    if (fn_checkout_is_email_address_fake($order_info['email'])) {
        $order_info['email'] = '';
    }

    Tygh::$app['view']->assign('shipments', $shipments);
    Tygh::$app['view']->assign('use_shipments', $use_shipments);

    Registry::set('navigation.tabs', $navigation_tabs);
    Tygh::$app['view']->assign('order_info', $order_info);
    Tygh::$app['view']->assign('status_settings', $statuses[$order_info['status']]['params']);

    if (!empty($_REQUEST['selected_section'])) {
        Tygh::$app['view']->assign('selected_section', $_REQUEST['selected_section']);
    }

    if (!empty($_REQUEST['active_tab'])) {
        Tygh::$app['view']->assign('active_tab', $_REQUEST['active_tab']);
    }

//
// Search orders
//
} elseif ($mode == 'search') {

    $orders = $search = null;
    $params = $_REQUEST;

    $params['include_incompleted'] = true;

    if (!empty($auth['user_id'])) {
        $params['user_id'] = $auth['user_id'];
    } elseif (isset($params['order_id'])) {
        $order_ids = is_array($params['order_id'])
            ? $params['order_id']
            : explode(',', $params['order_id']);
        foreach ($order_ids as $order_id) {
            if (!fn_is_order_allowed($order_id, $auth)) {
                return [
                    CONTROLLER_STATUS_REDIRECT,
                    'auth.login_form?return_url=' . urlencode(Registry::get('config.current_url'))
                ];
            }
        }
        $params['order_id'] = array_intersect($order_ids, $auth['order_ids']);
    } elseif (!empty($auth['order_ids'])) {
        $params['order_id'] = $auth['order_ids'];
    } else {
        return [
            CONTROLLER_STATUS_REDIRECT,
            'auth.login_form?return_url=' . urlencode(Registry::get('config.current_url'))
        ];
    }

    if ($orders === null && $search === null) {
        list($orders, $search) = fn_get_orders($params, Registry::get('settings.Appearance.orders_per_page'));
    }

    array_walk($orders, function(&$order_info) {
        if (fn_checkout_is_email_address_fake($order_info['email'])) {
            $order_info['email'] = '';
        }
    });

    Tygh::$app['view']->assign('orders', $orders);
    Tygh::$app['view']->assign('search', $search);

//
// Reorder order
//
} elseif ($mode == 'reorder') {

    fn_reorder($_REQUEST['order_id'], Tygh::$app['session']['cart'], $auth);

    return array(CONTROLLER_STATUS_REDIRECT, 'checkout.cart');

} elseif ($mode == 'downloads') {

    if (empty($auth['user_id']) && empty($auth['order_ids'])) {
        return array(CONTROLLER_STATUS_REDIRECT, fn_url());
    }

    fn_add_breadcrumb(__('downloads'));

    $params = $_REQUEST;
    $params['user_id'] = $auth['user_id'];
    $params['order_ids'] = !empty($auth['order_ids']) ? $auth['order_ids'] : array();

    list($products, $search) = fn_get_user_edp($params, Registry::get('settings.Appearance.elements_per_page'));

    Tygh::$app['view']->assign('products', $products);
    Tygh::$app['view']->assign('search', $search);

} elseif ($mode == 'order_downloads') {

    if (empty($auth['user_id']) && empty($auth['order_ids'])) {
        return array(CONTROLLER_STATUS_REDIRECT, fn_url());
    }

    if (!empty($_REQUEST['order_id'])) {
        if (empty($auth['user_id']) && !in_array($_REQUEST['order_id'], $auth['order_ids'])) {
            return array(CONTROLLER_STATUS_DENIED);
        }
        $orders_company_condition = '';
        if (fn_allowed_for('ULTIMATE')) {
            $orders_company_condition = fn_get_company_condition('?:orders.company_id');
        }

        $order = db_get_row("SELECT user_id, order_id FROM ?:orders WHERE ?:orders.order_id = ?i AND is_parent_order != 'Y' $orders_company_condition", $_REQUEST['order_id']);

        if (empty($order)) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        fn_add_breadcrumb(__('order') . ' #' . $_REQUEST['order_id'], "orders.details?order_id=" . $_REQUEST['order_id']);
        fn_add_breadcrumb(__('downloads'));

        $params = array(
            'user_id' => $order['user_id'],
            'order_ids' => $order['order_id']
        );
        list($products) = fn_get_user_edp($params);

        Tygh::$app['view']->assign('products', $products);
    } else {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

} elseif ($mode == 'get_file') {

    if (empty($_REQUEST['file_id']) || (empty($_REQUEST['ekey']) && empty($_REQUEST['preview']))) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    $ekey = !empty($_REQUEST['ekey']) ? $_REQUEST['ekey'] : '';
    if (fn_get_product_file($_REQUEST['file_id'], !empty($_REQUEST['preview']), $ekey) == false) {
        return array(CONTROLLER_STATUS_DENIED);
    }
    exit;

//
// Display list of files for downloadable product
//
} elseif ($mode == 'download') {
    if (!empty($_REQUEST['ekey'])) {

        $ekey_info = fn_get_product_edp_info($_REQUEST['product_id'], $_REQUEST['ekey']);

        if (empty($ekey_info)) {
            return array(CONTROLLER_STATUS_DENIED);
        }

        $product = array(
            'ekey' => $_REQUEST['ekey'],
            'product_id' => $ekey_info['product_id'],
        );

        if (!empty($product['product_id'])) {
            $product['product'] = db_get_field("SELECT product FROM ?:product_descriptions WHERE product_id = ?i AND lang_code = ?s", $product['product_id'], CART_LANGUAGE);
            $params = array (
                'product_id' => $product['product_id'],
                'order_id' => $ekey_info['order_id']
            );
            $product['files'] = fn_get_product_files($params);
        }
    }

    if (!empty($auth['user_id'])) {
        fn_add_breadcrumb(__('downloads'), "profiles.downloads");
    }

    fn_add_breadcrumb($product['product'], "products.view?product_id=$product[product_id]");
    fn_add_breadcrumb(__('download'));

    if (!empty($product['files'])) {
        Tygh::$app['view']->assign('product', $product);
    } else {
        return array(CONTROLLER_STATUS_DENIED);
    }

} elseif ($mode == 'get_custom_file') {
    $filename = !empty($_REQUEST['filename']) ? $_REQUEST['filename'] : '';

    if (!empty($_REQUEST['file'])) {
        if (!empty($_REQUEST['order_id'])) {
            $order_id = (int) $_REQUEST['order_id'];
            $file_path = 'order_data/' . $order_id . '/' . fn_basename($_REQUEST['file']);
        } else {
            $file_path = 'sess_data/' . fn_basename($_REQUEST['file']);
        }

        if (Storage::instance('custom_files')->isExist($file_path)) {
            Storage::instance('custom_files')->get($file_path, $filename);
        }
    }
}

function fn_reorder($order_id, &$cart, &$auth)
{
    $order_info = fn_get_order_info($order_id, false, false, false, true);
    unset(Tygh::$app['session']['shipping_hash']);
    unset(Tygh::$app['session']['edit_step']);

    fn_set_hook('reorder', $order_info, $cart, $auth);

    foreach ($order_info['products'] as $k => $item) {
        // refresh company id
        $company_id = db_get_field("SELECT company_id FROM ?:products WHERE product_id = ?i", $item['product_id']);
        $order_info['products'][$k]['company_id'] = $company_id;

        unset($order_info['products'][$k]['extra']['ekey_info']);
        unset($order_info['products'][$k]['extra']['promotions']);
        unset($order_info['products'][$k]['promotions']);

        $order_info['products'][$k]['product_options'] = empty($order_info['products'][$k]['extra']['product_options']) ? array() : $order_info['products'][$k]['extra']['product_options'];
        $order_info['products'][$k]['main_pair'] = fn_get_cart_product_icon($item['product_id'], $order_info['products'][$k]);
    }

    if (!empty($cart) && !empty($cart['products'])) {
        $cart['products'] = fn_array_merge($cart['products'], $order_info['products']);
    } else {
        $cart['products'] = $order_info['products'];
    }

    foreach ($cart['products'] as $k => $product) {
        $_is_edp = db_get_field("SELECT is_edp FROM ?:products WHERE product_id = ?i", $product['product_id']);
        if ($amount = fn_check_amount_in_stock($product['product_id'], $product['amount'], $product['product_options'], $k, $_is_edp, 0, $cart)) {
            $cart['products'][$k]['amount'] = $amount;

            // Check if the product price with options modifiers equals to zero
            $price = fn_get_product_price($product['product_id'], $amount, $auth);
            $zero_price_action = db_get_field('SELECT zero_price_action FROM ?:products WHERE product_id = ?i', $product['product_id']);
            $zero_price_action = fn_normalize_product_overridable_field_value('zero_price_action', $zero_price_action);

            /**
             * Executed for each product when an order is re-ordered.
             * Allows you to modify the data of a product in the order.
             *
             * @param array     $order_info         Order info from fn_get_order_info()
             * @param array     $cart               Array of cart content and user information necessary for purchase
             * @param array     $auth               Array of user authentication data (e.g. uid, usergroup_ids, etc.)
             * @param array     $product            Product data
             * @param int       $amount             Product quantity
             * @param float     $price              Product price
             * @param string    $zero_price_action  Flag, determines the action when the price of the product is 0
             * @param string    $k                  Product cart ID
             */
            fn_set_hook('reorder_product', $order_info, $cart, $auth, $product, $amount, $price, $zero_price_action, $k);

            if (!(float) $price && $zero_price_action === ProductZeroPriceActions::ASK_TO_ENTER_PRICE) {
                if (isset($product['custom_user_price'])) {
                    $price = $product['custom_user_price'];
                }
            }

            $price = fn_apply_options_modifiers($product['product_options'], $price, 'P', array(), array('product_data' => $product));

            if (!floatval($price)) {
                $data['price'] = isset($data['price']) ? fn_parse_price($data['price']) : 0;

                if (AREA == 'C'
                    && ($zero_price_action == 'R'
                        ||
                        ($zero_price_action == 'A' && floatval($data['price']) < 0)
                    )
                ) {
                    if ($zero_price_action == 'A') {
                        fn_set_notification('E', __('error'), __('incorrect_price_warning'));
                    } else {
                        fn_set_notification('W', __('warning'), __('warning_zero_price_restricted_product', array(
                            '[product]' => $product['product']
                        )));
                    }

                    unset($cart['products'][$k]);

                    continue;
                }
            }

            // Change the path of custom files
            if (!empty($product['extra']['custom_files'])) {
                foreach ($product['extra']['custom_files'] as $option_id => $_data) {
                    if (!empty($_data)) {
                        foreach ($_data as $file_id => $file) {
                            $cart['products'][$k]['extra']['custom_files'][$option_id][$file_id]['path'] = 'sess_data/' . fn_basename($file['path']);
                        }
                    }
                }
            }
        } else {
            unset($cart['products'][$k]);
        }
    }

    // Restore custom files for editing
    $dir_path = 'order_data/' . $order_id;

    if (Storage::instance('custom_files')->isExist($dir_path)) {
        Storage::instance('custom_files')->copy($dir_path, 'sess_data');
    }

    // Redirect customer to step three after reordering
    $cart['payment_updated'] = true;

    fn_save_cart_content($cart, $auth['user_id']);
    unset($cart['product_groups']);
}

function fn_prepare_repay_data($payment_id, $order_info, $auth)
{
    if (empty($payment_id)) {
        $payment_id = $order_info['payment_id'];
    }

    //Get payment methods
    $payment_methods = fn_get_payments([
        'usergroup_ids' => $auth['usergroup_ids'],
        'extend' => ['images']
    ]);

    fn_set_hook('prepare_repay_data', $payment_id, $order_info, $auth, $payment_methods);

    if (!empty($payment_methods)) {
        // Get payment method info
        $payment_groups = fn_prepare_checkout_payment_methods($order_info, $auth);

        $payment_methods_list = [];
        foreach ($payment_groups as $payment_group_items) {
            $payment_methods_list += (array) $payment_group_items;
        }

        $available_payment_ids = array_column($payment_methods, 'payment_id');
        if (!empty($payment_id) && in_array($payment_id, $available_payment_ids)) {
            $order_payment_id = $payment_id;
        } else {
            $first = reset($payment_methods);
            $order_payment_id = $first['payment_id'];
        }

        $payment_data = fn_get_payment_method_data($order_payment_id);
        $payment_data['surcharge_value'] = 0;

        if (floatval($payment_data['a_surcharge'])) {
            $payment_data['surcharge_value'] += $payment_data['a_surcharge'];
        }

        if (floatval($payment_data['p_surcharge'])) {
            if (fn_allowed_for('MULTIVENDOR') && fn_take_payment_surcharge_from_vendor($order_info['products'])) {
                $payment_data['surcharge_value'] += fn_format_price($order_info['total']);
            } else {
                $payment_data['surcharge_value'] += fn_format_price(($order_info['total'] - $order_info['payment_surcharge']) * $payment_data['p_surcharge'] / 100);
            }
        }

        Tygh::$app['view']->assign('payment_methods', $payment_groups); // TODO: saved for backward compatibility, change $payment_group to $payment_methods in future
        Tygh::$app['view']->assign('payment_method', $payment_data);
        Tygh::$app['view']->assign('payment_methods_list', $payment_methods_list);
        Tygh::$app['view']->assign('order_payment_id', $order_payment_id);
    }
}
