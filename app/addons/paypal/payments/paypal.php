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

/**
 * @var array $processor_data
 * @var array $order_info
 * @var string $mode
 */

use Tygh\Enum\OrderStatuses;
use Tygh\Languages\Languages;

defined('BOOTSTRAP') or die('Access denied');

// Return from paypal website
if (defined('PAYMENT_NOTIFICATION')) {
    $order_id = isset($_REQUEST['order_id'])
        ? $_REQUEST['order_id']
        : null;

    if (!fn_check_payment_script('paypal.php', $order_id)) {
        die('Access denied');
    }

    if ($mode == 'return') {
        // wait for the IPN to be processed
        $time_to_wait = 10; // time to wait for IPN to be processed, seconds
        while (fn_pp_is_order_locked($order_id) && $time_to_wait) {
            sleep(1);
            $time_to_wait--;
        }

        // set order's status to Open and wait for the IPN to arrive
        if (!fn_is_paypal_ipn_received($order_id)) {
            fn_change_order_status($order_id, OrderStatuses::OPEN);
        }

        if (!empty($_REQUEST['txn_id'])) {
            fn_update_order_payment_info($order_id, ['transaction_id' => $_REQUEST['txn_id']]);
        }
    } elseif ($mode == 'cancel') {
        $order_info = fn_get_order_info($order_id);
        fn_pp_save_mode($order_info);

        if (!fn_is_paypal_ipn_received($order_info['order_id'])) {
            $pp_response = [];
            $pp_response['order_status'] = OrderStatuses::INCOMPLETED;
            $pp_response['reason_text'] = __('text_transaction_cancelled');

            if (!empty($_REQUEST['payer_email'])) {
                $pp_response['customer_email'] = $_REQUEST['payer_email'];
            }
            if (!empty($_REQUEST['payer_id'])) {
                $pp_response['client_id'] = $_REQUEST['payer_id'];
            }
            if (!empty($_REQUEST['memo'])) {
                $pp_response['customer_notes'] = $_REQUEST['memo'];
            }
            fn_finish_payment($order_id, $pp_response);
        }
    }

    fn_order_placement_routines('route', $order_id);

} else {
    /** @var int $order_id */

    $currency = fn_paypal_get_valid_currency($processor_data['processor_params']['currency']);

    $paypal_item_name = $processor_data['processor_params']['item_name'];
    $paypal_account = $processor_data['processor_params']['account'];
    $paypal_order_id = $processor_data['processor_params']['order_prefix'] . $order_id . ($order_info['repaid']
            ? $order_info['repaid']
            : ''
        );
    $paypal_currency = $currency['code'];

    if ($processor_data['processor_params']['mode'] === 'test') {
        $paypal_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
    } else {
        $paypal_url = 'https://www.paypal.com/cgi-bin/webscr';
    }

    if ($paypal_currency == CART_PRIMARY_CURRENCY) {
        //Order Total
        $paypal_shipping = fn_order_shipping_cost($order_info);
        $paypal_total = fn_format_price($order_info['total'] - $paypal_shipping, $paypal_currency);
        $paypal_shipping = fn_format_price($paypal_shipping, $paypal_currency);
    } else {
        $paypal_shipping = 0;
        $paypal_total = fn_format_price_by_currency($order_info['total'], CART_PRIMARY_CURRENCY, $paypal_currency);
    }

    /** @var \Tygh\Location\Manager $location_manager */
    $location_manager = Tygh::$app['location'];
    $b_firstname = $location_manager->getLocationField($order_info, 'firstname', '', BILLING_ADDRESS_PREFIX);
    $b_lastname = $location_manager->getLocationField($order_info, 'lastname', '', BILLING_ADDRESS_PREFIX);
    $b_address = $location_manager->getLocationField($order_info, 'address', '', BILLING_ADDRESS_PREFIX);
    $b_address_2 = $location_manager->getLocationField($order_info, 'address_2', '', BILLING_ADDRESS_PREFIX);
    $b_country = $location_manager->getLocationField($order_info, 'country', '', BILLING_ADDRESS_PREFIX);
    $b_state = $location_manager->getLocationField($order_info, 'state', '', BILLING_ADDRESS_PREFIX);
    $b_city = $location_manager->getLocationField($order_info, 'city', '', BILLING_ADDRESS_PREFIX);
    $b_zipcode = $location_manager->getLocationField($order_info, 'zipcode', '', BILLING_ADDRESS_PREFIX);

    list($phone_part_a, $phone_part_b, $phone_part_c) = fn_pp_format_phone_number(
        $order_info['phone'],
        $b_country
    );

    // State code must be passed as-is for the United States. State name must be passed for other countries
    if ($b_country !== 'US') {
        $b_state = fn_get_state_name($b_state, $b_country);
    }

    $return_url = fn_url("payment_notification.return?payment=paypal&order_id={$order_id}", AREA, 'current');
    $cancel_url = fn_url("payment_notification.cancel?payment=paypal&order_id={$order_id}", AREA, 'current');
    $notify_url = fn_url("payment_notification.paypal_ipn", AREA, 'current');

    /** @var \Tygh\Web\Session $session */
    $session = Tygh::$app['session'];
    $return_url = fn_link_attach($return_url, $session->getName() . '=' . $session->getID());
    $cancel_url = fn_link_attach($cancel_url, $session->getName() . '=' . $session->getID());

    $post_data = [
        'charset'       => 'utf-8',
        'cmd'           => '_cart',
        'custom'        => $order_id,
        'invoice'       => $paypal_order_id,
        'redirect_cmd'  => '_xclick',
        'rm'            => 2,
        'email'         => $order_info['email'],
        'first_name'    => $b_firstname,
        'last_name'     => $b_lastname,
        'address1'      => $b_address,
        'address2'      => $b_address_2,
        'country'       => $b_country,
        'city'          => $b_city,
        'state'         => $b_state,
        'zip'           => $b_zipcode,
        'day_phone_a'   => $phone_part_a,
        'day_phone_b'   => $phone_part_b,
        'day_phone_c'   => $phone_part_c,
        'night_phone_a' => $phone_part_a,
        'night_phone_b' => $phone_part_b,
        'night_phone_c' => $phone_part_c,
        'business'      => $paypal_account,
        'item_name'     => $paypal_item_name,
        'amount'        => $paypal_total,
        'upload'        => '1',
        'currency_code' => $paypal_currency,
        'return'        => $return_url,
        'cancel_return' => $cancel_url,
        'notify_url'    => $notify_url,
        'shipping_1'    => $paypal_shipping,
        'bn'            => 'ST_ShoppingCart_Upload_US',
        'lc'            => Languages::getLocaleByLanguageCode(CART_LANGUAGE),
    ];

    list($products, $product_count) = fn_pp_standart_prepare_products($order_info, $paypal_currency);
    $post_data = array_merge($post_data, $products);

    if ($processor_data['processor_params']['new_order_status'] === OrderStatuses::OPEN) {
        if ($order_info['status'] === OrderStatuses::INCOMPLETED) {
            fn_change_order_status($order_id, OrderStatuses::OPEN);
        }
        if (fn_allowed_for('MULTIVENDOR')) {
            if ($order_info['status'] === OrderStatuses::PARENT) {
                $child_orders = db_get_hash_single_array('SELECT order_id, status FROM ?:orders WHERE parent_order_id = ?i', ['order_id', 'status'], $order_id);

                foreach ($child_orders as $order_id => $order_status) {
                    if ($order_status !== OrderStatuses::INCOMPLETED) {
                        continue;
                    }
                    fn_change_order_status($order_id, OrderStatuses::OPEN);
                }
            }
        }
    }

    fn_pp_save_mode($order_info);
    fn_create_payment_form($paypal_url, $post_data, 'PayPal server', false);
}
exit;
