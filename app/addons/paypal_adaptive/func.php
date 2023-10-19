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

use Tygh\Enum\VendorPayoutApprovalStatuses;
use Tygh\Enum\VendorPayoutTypes;
use Tygh\Http;
use Tygh\Registry;
use Tygh\Embedded;
use Tygh\Settings;
use Tygh\VendorPayouts;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Installs payment processors, sets cronjob key and modifies table structure.
 *
 * @TODO: add TLS v1.2 support check
 */
function fn_pp_adaptive_payments_install()
{
    $key = fn_generate_password(SD_PAYPAL_ADAPTIVE_CRON_KEY_LENGTH);
    Registry::set('addons.paypal_adaptive.cron_key', $key);
    Settings::instance()->updateValue('cron_key', $key, 'paypal_adaptive');

    db_query("DELETE FROM ?:payment_processors WHERE processor_script = ?s", "paypal_adaptive.php");

    $_data = array(
        'processor' => 'PayPal Adaptive',
        'processor_script' => 'paypal_adaptive.php',
        'processor_template' => 'addons/paypal_adaptive/views/orders/components/payments/paypal_adaptive.tpl',
        'admin_template' => 'paypal_adaptive.tpl',
        'callback' => 'Y',
        'type' => 'P',
        'addon' => 'paypal_adaptive',
    );

    db_query("INSERT INTO ?:payment_processors ?e", $_data);
}

function fn_pp_adaptive_payments_uninstall()
{
    db_query("DELETE FROM ?:payment_processors WHERE processor_script = ?s", "paypal_adaptive.php");
}

function fn_paypal_adaptive_update_company_pre(&$company_data, $company_id, $lang_code, &$can_update)
{
    $company_data['company_id'] = $company_id;
    fn_paypal_adaptive_get_verified_status($company_data);
    unset($company_data['company_id']);
    if (!empty($company_data['paypal_email'])) {
        $company_ids_with_same_email = db_get_fields('SELECT company_id FROM ?:companies WHERE paypal_email = ?s AND company_id != ?i', $company_data['paypal_email'], $company_id);
    }
    if (!empty($company_ids_with_same_email)) {
        fn_set_notification('E', __('error'), __('paypal_emails_must_be_unique'));
        $can_update = false;
        db_query('UPDATE ?:companies SET paypal_verification = ?s WHERE company_id = ?i', 'not_checked', $company_id);
    }
}

function fn_paypal_clear_queue($parent_order_id)
{
    if (!empty($parent_order_id)) {
        db_query("DELETE FROM ?:order_data WHERE type = ?s AND order_id = ?i", QUEUE_PAYMENT_ORDERS, $parent_order_id);
    }
}

/**
 * Create queue
 * 
 * @param  array $params Params
 * @return array
 */
function fn_paypal_create_queue($params)
{
    $data_orders = array();
    fn_paypal_clear_queue($params['order_id']);

    $child_order_ids = fn_paypal_adaptive_get_childs_orders($params['order_id']);

    while (count($child_order_ids)) {
        $order = array(
            'status' => QUEUE_PAYMENT_ORDERS,
        );

        for ($order_num = 1; $order_num <= MAX_AMOUNT_VENDORS_IN_ORDER; $order_num++) {
            $child_order_id = array_shift($child_order_ids);

            if (empty($child_order_id)) {
                break;
            }

            $order['order_ids'][] = $child_order_id;
        }

        $data_orders[] = $order;
    }

    $data = array (
        'order_id' => $params['order_id'],
        'type' => QUEUE_PAYMENT_ORDERS,
        'data' => serialize($data_orders)
    );

    db_query("REPLACE INTO ?:order_data ?e", $data);

    return $data_orders;
}

function fn_paypal_get_queue($order_id)
{
    return unserialize(db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, QUEUE_PAYMENT_ORDERS));
}

function fn_paypal_adaptive_set_summa_data(&$queue_orders, $orders_data)
{
    foreach ($queue_orders as $queue_key => $queue) {

        $queue_orders[$queue_key]['total'] = 0;
        foreach ($queue['order_ids'] as $order_id) {
            $queue_orders[$queue_key]['total'] += $orders_data[$order_id]['total'];
        }
    }
}

function fn_paypal_adaptive_get_childs_orders($order_id)
{
    static $child_orders;

    if (!isset($child_orders)) {
        $child_orders = db_get_fields("SELECT order_id FROM ?:orders WHERE is_parent_order != 'Y' AND parent_order_id = ?i ORDER BY order_id", $order_id);
    }

    return $child_orders;
}

function fn_paypal_get_orders_data($queue_orders)
{
    $orders_data = array();

    foreach ($queue_orders as $queue) {
        foreach ($queue['order_ids'] as $order_id) {
            $orders_data[$order_id] = fn_get_order_info($order_id);
        }
    }

    return $orders_data;
}

function fn_paypal_get_parent_order_id($order_ids)
{
    static $parent_order_id;

    if (!isset($parent_order_id)) {
        if (is_array($order_ids)) {
            $order_id = reset($order_ids);
        } else {
            $order_id = $order_ids;
        }

        $parent_order_id = db_get_field("SELECT parent_order_id FROM ?:orders WHERE order_id = ?i", $order_id);

        if (empty($parent_order_id)) {
            $parent_order_id = $order_id;
        }
    }

    return $parent_order_id;
}

function fn_paypal_adaptive_check_finish($parent_order_id)
{
    $queue_orders = fn_paypal_get_queue($parent_order_id);

    if (!empty($queue_orders)) {
        foreach ($queue_orders as $queue) {
            if ($queue['status'] == QUEUE_PAYMENT_ORDERS) {
                return false;
            }
        }
    }

    return true;
}

function fn_paypal_adaptive_set_status($order_id, $status)
{
    $parent_order_id = fn_paypal_get_parent_order_id($order_id);
    $queue_orders = fn_paypal_get_queue($parent_order_id);

    if (!empty($queue_orders)) {
        foreach ($queue_orders as $key => $queue) {
            if (in_array($order_id, $queue['order_ids'])) {
                $queue_orders[$key]['status'] = $status;
                break;
            }
        }

        $data = array (
            'order_id' => $parent_order_id,
            'type' => QUEUE_PAYMENT_ORDERS,
            'data' => serialize($queue_orders)
        );

        db_query('REPLACE INTO ?:order_data ?e', $data);
    }
}

function fn_paypal_adaptive_any_paid($queue_orders)
{
    foreach ($queue_orders as $queue) {
        if ($queue['status'] == PAID_PAYMENT_ORDERS) {
            return true;
        }
    }

    return false;
}

function fn_paypal_adaptive_build_headers($processor_data)
{
    $headers = array();
    $headers[] = 'X-PAYPAL-SECURITY-USERID: ' . $processor_data['processor_params']['username'];
    $headers[] = 'X-PAYPAL-SECURITY-PASSWORD: ' . $processor_data['processor_params']['password'];

    if (!empty($processor_data['processor_params']['authentication_method']) && $processor_data['processor_params']['authentication_method'] == 'signature') {
        $headers[] = "X-PAYPAL-SECURITY-SIGNATURE: " . $processor_data['processor_params']['signature'];
    }

    if (!empty($processor_data['processor_params']['app_id'])) {
        $headers[] = 'X-PAYPAL-APPLICATION-ID: ' . $processor_data['processor_params']['app_id'];
    } else {
        $headers[] = 'X-PAYPAL-APPLICATION-ID: APP-80W284485P519543T';
    }

    $headers[] = 'X-PAYPAL-REQUEST-DATA-FORMAT: NV';
    $headers[] = 'X-PAYPAL-RESPONSE-DATA-FORMAT: NV';
    $headers[] = 'Content-type: application/x-www-form-urlencoded';
    $headers[] = 'Connection: close';

    return $headers;
}

function fn_paypal_adaptive_payment_refund($transaction_id, $processor_data)
{
    $paypal_sslcertpath = '';
    if (!empty($processor_data['processor_params']['authentication_method']) && $processor_data['processor_params']['authentication_method'] == 'cert') {
        $paypal_sslcertpath = Registry::get('config.dir.certificates') . $processor_data['processor_params']['certificate_filename'];
    }

    if ($processor_data['processor_params']['mode'] == 'test') {
        $paypal_url = "https://svcs.sandbox.paypal.com/AdaptivePayments/Refund";
    } else {
        $paypal_url = "https://svcs.paypal.com/AdaptivePayments/Refund";
    }

    $headers = fn_paypal_adaptive_build_headers($processor_data);
    $post_data = array(
        'transactionId' => $transaction_id,
        "requestEnvelope.errorLanguage" => "en_US",
    );

    $response = Http::post($paypal_url, $post_data, array('headers' => $headers, 'ssl_cert' => $paypal_sslcertpath));

    parse_str($response, $pp_response);

    return $pp_response;
}

function fn_paypal_adaptive_payment_details($pay_key, $processor_data, $type_key = 'payKey')
{
    $paypal_sslcertpath = '';
    if (!empty($processor_data['processor_params']['authentication_method']) && $processor_data['processor_params']['authentication_method'] == 'cert') {
        $paypal_sslcertpath = Registry::get('config.dir.certificates') . $processor_data['processor_params']['certificate_filename'];
    }

    if ($processor_data['processor_params']['mode'] == 'test') {
        $paypal_url = "https://svcs.sandbox.paypal.com/AdaptivePayments/PaymentDetails";
    } else {
        $paypal_url = "https://svcs.paypal.com/AdaptivePayments/PaymentDetails";
    }

    $headers = fn_paypal_adaptive_build_headers($processor_data);
    $post_data = array(
        $type_key => $pay_key,
        'requestEnvelope.errorLanguage' => 'en_US',
    );



    $response = Http::post($paypal_url, $post_data, array('headers' => $headers, 'ssl_cert' => $paypal_sslcertpath));

    parse_str($response, $pp_response);

    return $pp_response;
}

function fn_paypal_adaptive_refund_status_message($refund_status)
{
    $statuses = array(
        'REFUNDED' => 'Refund successfully completed',
        'REFUNDED_PENDING' => 'Refund awaiting transfer of funds; for example, a refund paid by eCheck.',
        'NOT_PAID' => 'Payment was never made; therefore, it cannot be refunded.',
        'ALREADY_REVERSED_OR_REFUNDED' => 'Request rejected because the refund was already made, or the payment was reversed prior to this request.',
        'NO_API_ACCESS_TO_RECEIVER' => 'Request cannot be completed because you do not have third-party access from the receiver to make the refund.',
        'REFUND_NOT_ALLOWED' => 'Refund is not allowed.',
        'INSUFFICIENT_BALANCE' => 'Request rejected because the receiver from which the refund is to be paid does not have sufficient funds or the funding source cannot be used to make a refund.',
        'AMOUNT_EXCEEDS_REFUNDABLE' => 'Request rejected because you attempted to refund more than the remaining amount of the payment; call the PaymentDetails API operation to determine the amount already refunded.',
        'PREVIOUS_REFUND_PENDING' => 'Request rejected because a refund is currently pending for this part of the payment',
        'NOT_PROCESSED' => 'Request rejected because it cannot be processed at this time',
        'REFUND_ERROR' => 'Request rejected because of an internal error',
        'PREVIOUS_REFUND_ERROR' => 'Request rejected because another part of this refund caused an internal error.'
    );

    return !empty($statuses[$refund_status]) ? $statuses[$refund_status] : 'Un';
}

function fn_paypal_adaptive_get_processor_data($payment_id = 0)
{
    if (empty($payment_id)) {
        $processor_id = db_get_field("SELECT processor_id FROM ?:payment_processors WHERE processor_script = 'paypal_adaptive.php'");
        $payment_id = db_get_field("SELECT payment_id FROM ?:payments WHERE processor_id = ?i AND status = 'A'", $processor_id);
    }
    $processor_data = fn_get_processor_data($payment_id);

    return $processor_data;
}

function fn_paypal_adaptive_get_currencies()
{
    $paypal_currencies = array(
        'CAD' => array(
            'name' => __("currency_code_cad"),
            'code' => 'CAD',
            'id' => 124,
            'active' => true
        ),
        'EUR' => array(
            'name' => __("currency_code_eur"),
            'code' => 'EUR',
            'id' => 978,
            'active' => true
        ),
        'GBP' => array(
            'name' => __("currency_code_gbp"),
            'code' => 'GBP',
            'id' => 826,
            'active' => true
        ),
        'USD' => array(
            'name' => __("currency_code_usd"),
            'code' => 'USD',
            'id' => 840,
            'active' => true
        ),
        'JPY' => array(
            'name' => __("currency_code_jpy"),
            'code' => 'JPY',
            'id' => 392,
            'active' => true
        ),
        'RUB' => array(
            'name' => __("currency_code_rur"),
            'code' => 'RUB',
            'id' => 643,
            'active' => true
        ),
        'AUD' => array(
            'name' => __("currency_code_aud"),
            'code' => 'AUD',
            'id' => 36,
            'active' => true
        ),
        'NZD' => array(
            'name' => __("currency_code_nzd"),
            'code' => 'NZD',
            'id' => 554,
            'active' => true
        ),
        'CHF' => array(
            'name' => __("currency_code_chf"),
            'code' => 'CHF',
            'id' => 756,
            'active' => true
        ),
        'HKD' => array(
            'name' => __("currency_code_hkd"),
            'code' => 'HKD',
            'id' => 344,
            'active' => true
        ),
        'SGD' => array(
            'name' => __("currency_code_sgd"),
            'code' => 'SGD',
            'id' => 702,
            'active' => true
        ),
        'SEK' => array(
            'name' => __("currency_code_sek"),
            'code' => 'SEK',
            'id' => 752,
            'active' => true
        ),
        'DKK' => array(
            'name' => __("currency_code_dkk"),
            'code' => 'DKK',
            'id' => 208,
            'active' => true
        ),
        'PLN' => array(
            'name' => __("currency_code_pln"),
            'code' => 'PLN',
            'id' => 985,
            'active' => true
        ),
        'NOK' => array(
            'name' => __("currency_code_nok"),
            'code' => 'NOK',
            'id' => 578,
            'active' => true
        ),
        'HUF' => array(
            'name' => __("currency_code_huf"),
            'code' => 'HUF',
            'id' => 348,
            'active' => true
        ),
        'CZK' => array(
            'name' => __("currency_code_czk"),
            'code' => 'CZK',
            'id' => 203,
            'active' => true
        ),
        'ILS' => array(
            'name' => __("currency_code_ils"),
            'code' => 'ILS',
            'id' => 376,
            'active' => true
        ),
        'MXN' => array(
            'name' => __("currency_code_mxn"),
            'code' => 'MXN',
            'id' => 484,
            'active' => true
        ),
        'BRL' => array(
            'name' => __("currency_code_brl"),
            'code' => 'BRL',
            'id' => 986,
            'active' => true
        ),
        'PHP' => array(
            'name' => __("currency_code_php"),
            'code' => 'PHP',
            'id' => 608,
            'active' => true
        ),
        'TWD' => array(
            'name' => __("currency_code_twd"),
            'code' => 'TWD',
            'id' => 901,
            'active' => true
        ),
        'THB' => array(
            'name' => __("currency_code_thb"),
            'code' => 'THB',
            'id' => 764,
            'active' => true
        ),
        'TRY' => array(
            'name' => __("currency_code_try"),
            'code' => 'TRY',
            'id' => 949,
            'active' => true
        ),
        'MYR' => array(
            'name' => __("currency_code_myr"),
            'code' => 'MYR',
            'id' => 458,
            'active' => true
        ),
        'INR' => array(
            'name'   => __("currency_code_inr"),
            'code'   => 'INR',
            'id'     => 356,
            'active' => true,
        ),
    );

    $currencies = fn_get_currencies();
    $result = array();

    foreach ($paypal_currencies as $key => &$item) {
        $item['active'] = isset($currencies[$key]);
    }

    return $paypal_currencies;
}

/**
 * Request
 *
 * @param array $order_ids
 * @param array $processor_data
 * @param array $params
 * @return array
 */
function fn_paypal_adaptive_request($order_ids = array(), $processor_data = array(), $params = array())
{
    $paypal_sslcertpath = '';
    if (!empty($processor_data['processor_params']['authentication_method']) && $processor_data['processor_params']['authentication_method'] == 'cert') {
        $paypal_sslcertpath = Registry::get('config.dir.certificates') . $processor_data['processor_params']['certificate_filename'];
    }

    if ($processor_data['processor_params']['mode'] == 'test') {
        $paypal_url = PAYPAL_ADAPTIVE_TEST_URL;
        $paypal_set_options_url = PAYPAL_ADAPTIVE_SET_PAYMENT_OPTIONS_TEST_URL;
    } else {
        $paypal_url = PAYPAL_ADAPTIVE_LIVE_URL;
        $paypal_set_options_url = PAYPAL_ADAPTIVE_SET_PAYMENT_OPTIONS_LIVE_URL;
    }

    $order_ids = array_diff($order_ids, array(''));

    $headers = fn_paypal_adaptive_build_headers($processor_data);

    $post_data = fn_paypal_adaptive_build_post($order_ids, $processor_data);

    $response = Http::post($paypal_url, $post_data, array('headers' => $headers, 'ssl_cert' => $paypal_sslcertpath));

    parse_str($response, $pp_response);

    if (empty($params['get_payKey'])) {

        $result = array(
            'message' => !empty($pp_response['error(0)_message']) ? $pp_response['error(0)_message'] : $pp_response['responseEnvelope_ack'],
            'trackingId' => $pp_response['responseEnvelope_correlationId']
        );

        if (!empty($pp_response['payKey'])) {

            $params = array(
                'pay_key' => $pp_response['payKey'],
                'headers' => $headers,
                'paypal_sslcertpath' => $paypal_sslcertpath,
                'processor_params' => $processor_data['processor_params'],
                'paypal_set_options_url' => $paypal_set_options_url
            );

            fn_paypal_adaptive_set_options($params, $processor_data);

            if ($processor_data['processor_params']['mode'] == 'test') {
                $url = "https://www.sandbox.paypal.com/webscr";
            } else {
                $url = "https://www.paypal.com/webscr";
            }

            $post_data = array(
                'cmd' => '_ap-payment',
                'paykey' => $pp_response['payKey'],
            );

            if ($processor_data['processor_params']['in_context'] == 'Y' && AREA == 'C' && defined('AJAX_REQUEST')) {
                Registry::get('ajax')->assign('paykey', $pp_response['payKey']);
                exit;
            } else {
                Embedded::leave();
                fn_create_payment_form($url, $post_data, 'PayPal adaptive server', false);
            }

        } else {
            $parent_order_id = fn_paypal_get_parent_order_id($order_ids);
            fn_set_notification('E', __('error'), $result['message'], 'S');
            fn_order_placement_routines('route', $parent_order_id);
        }
    } else {
        $result = $pp_response;
    }

    return $result;
}

/**
 * Build post data
 *
 * @param $order_ids
 * @param $processor_data
 * @return array
 */
function fn_paypal_adaptive_build_post($order_ids, $processor_data)
{
    $ip = fn_get_ip();
    $paypal_currency = $processor_data['processor_params']['currency'];

    if ($processor_data['processor_params']['user_currency'] == 'Y') {
        $paypal_currency = CART_SECONDARY_CURRENCY;
    }

    $is_chained_payment = $processor_data['processor_params']['payment_type'] == 'chained';
    $collect_payouts = Registry::get('addons.paypal_adaptive.collect_payouts') == 'Y';

    if (!is_array($order_ids)) {
        $order_ids = array($order_ids);
    }

    $parent_order_id = fn_paypal_get_parent_order_id($order_ids);
    $queue_orders = fn_paypal_get_queue($parent_order_id);
    $str_order_ids = implode(',', $order_ids);

    if (empty($queue_orders)) {
        $cancel_url = fn_url("payment_notification.cancel?payment=paypal_adaptive&order_ids=$parent_order_id");
    } else {
        $cancel_url = fn_url("payment_notification.skip?payment=paypal_adaptive&order_ids=$str_order_ids");
    }

    $fees_payer = 'EACHRECEIVER';
    $primary_email = $processor_data['processor_params']['primary_email'];
    if ($processor_data['processor_params']['payment_type'] == 'chained') {
        $fees_payer = $processor_data['processor_params']['fees_payer'];
    }

    $post_data = array(
        "actionType" => "PAY",
        "clientDetails.ipAddress" => $ip['host'],
        "currencyCode" => $paypal_currency,
        "feesPayer" => $fees_payer,
        "memo" => "Order_id #$str_order_ids",
        "requestEnvelope.errorLanguage" => "en_US",
        "returnUrl" => fn_url("payment_notification.return?payment=paypal_adaptive&order_ids=$str_order_ids") . '&payKey=${payKey}',
        "cancelUrl" => $cancel_url,
    );

    $order_index = 0;
    $total = 0;
    $total_admin_fee = 0;

    if ($is_chained_payment && !$collect_payouts) {
        $post_data["receiverList.receiver(0).amount"] = $total;
        $post_data["receiverList.receiver(0).email"] = $primary_email;
        $post_data["receiverList.receiver(0).primary"] = true;

        $order_index++;
    }

    foreach ($order_ids as $order_id) {

        $order_data = db_get_row("SELECT total, subtotal, company_id FROM ?:orders WHERE ?:orders.order_id = ?i", $order_id);
        $secondaries_email = db_get_row("SELECT email, paypal_email FROM ?:companies WHERE company_id = ?i", $order_data['company_id']);
        $secondary_email = empty($secondaries_email['paypal_email']) ? $secondaries_email['email'] : $secondaries_email['paypal_email'];
        $order_data['order_id'] = $order_id;
        $admin_fee = fn_paypal_adaptive_calc_admin_fee($order_data);

        if ($is_chained_payment && !$collect_payouts) {
            $total += fn_format_price_by_currency($order_data['total'], CART_PRIMARY_CURRENCY, $paypal_currency);
        }

        $vendor_fee = $order_data['total'] - $admin_fee;
        $total_admin_fee += $admin_fee;

        if ($vendor_fee) {
            $post_data["receiverList.receiver($order_index).amount"] = fn_format_price_by_currency($vendor_fee, CART_PRIMARY_CURRENCY, $paypal_currency);
            $post_data["receiverList.receiver($order_index).email"] = $secondary_email;

            $order_index++;
        }
    }

    if ($is_chained_payment && !$collect_payouts) {
        $post_data["receiverList.receiver(0).amount"] = $total;

    } elseif (!empty($total_admin_fee)) {
        $post_data["receiverList.receiver($order_index).amount"] = fn_format_price_by_currency($total_admin_fee, CART_PRIMARY_CURRENCY, $paypal_currency);
        $post_data["receiverList.receiver($order_index).email"] = $primary_email;
    }

    return $post_data;
}

/**
 * Calculate admin fee
 *
 * @param $order_data
 * @return float|int
 */
function fn_paypal_adaptive_calc_admin_fee($order_data)
{
    if (fn_paypal_adaptive_get_company_base_for_commission($order_data['company_id']) == 'O') {
        $total_to_fee = $order_data['total'];
    } else {
        $total_to_fee = $order_data['subtotal'];
    }

    $commission = db_get_row(
        'SELECT commission_amount, commission, commission_type'
        . ' FROM ?:vendor_payouts'
        . ' WHERE company_id = ?i'
        . ' AND order_id = ?i', $order_data['company_id'], $order_data['order_id']
    );
    $admin_fee = isset($commission['commission_amount']) ? $commission['commission_amount'] : 0;

    // hold back vendor payouts
    if (Registry::get('addons.paypal_adaptive.collect_payouts') == 'Y') {
        $vendor_payouts = VendorPayouts::instance(array('vendor' => $order_data['company_id']));
        $pending_payouts = $vendor_payouts->getSimple(array(
            'payout_type' => VendorPayoutTypes::PAYOUT,
            'approval_status' => VendorPayoutApprovalStatuses::PENDING
        ));
        list($balance, ) = $vendor_payouts->getBalance();
        if ($pending_payouts) {
            if ($balance < 0) {
                $admin_fee += abs($balance);
            } else {
                $admin_fee += abs(array_sum(array_column($pending_payouts, 'payout_amount')));
            }
        }
    }

    if ($admin_fee > $total_to_fee) {
        $admin_fee = $total_to_fee;
    }

    return !empty($admin_fee) ? $admin_fee : 0;
}

/**
 * Get company base for commission
 *
 * @param $company_id
 * @return array|mixed|null
 */
function fn_paypal_adaptive_get_company_base_for_commission($company_id)
{
    $base_for_commission = db_get_field("SELECT paypal_base_for_commission FROM ?:companies WHERE company_id = ?i", $company_id);

    if (empty($base_for_commission)) {
        $base_for_commission = Registry::get('addons.paypal_adaptive.count_vendor_commission_on_basis');
    }

    return $base_for_commission;
}

/**
 * Hook. Changes params to get payment processors
 *
 * @param array $params    Array of flags/data which determines which data should be gathered
 * @param array $fields    List of fields for retrieving
 * @param array $join      Array with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
 * @param array $order     Array containing SQL-query with sorting fields
 * @param array $condition Array containing SQL-query condition possibly prepended with a logical operator AND
 * @param array $having    Array containing SQL-query condition to HAVING group
 */
function fn_paypal_adaptive_get_payments($params, $fields, $join, $order, &$condition, $having)
{
    $mode = Registry::get('runtime.mode');

    if (($mode == 'checkout' || $mode == 'add') && array_key_exists('product_groups', Tygh::$app['session']['cart'])) {

        $product_groups = Tygh::$app['session']['cart']['product_groups'];

        $company_ids = array();
        foreach ($product_groups as $group_key => $group_val) {
            $company_ids[$group_key] = $group_val['company_id'];
        }

        $check_company_ids = db_get_fields('SELECT company_id FROM ?:companies WHERE company_id IN (?n) AND paypal_email != ?s AND paypal_verification NOT IN (?a)', $company_ids, '', array('not_checked', 'not_verified'));

        foreach ($company_ids as $key => $val) {
            if (!in_array($val, $check_company_ids)) {
                $condition[] = db_quote(
                    ' IF(STRCMP(?:payment_processors.processor, ?s) = 0, 0, 1)',
                    PAYPAL_ADAPTIVE_PROCESSOR
                );
                break;
            }
        }
    }
}

/**
 * Update PayPal adaptive settings
 *
 * @param array $settings
 */
function fn_update_paypal_adaptive_settings($settings)
{
    foreach ($settings as $setting_name => $setting_value) {
        Settings::instance()->updateValue($setting_name, $setting_value);
    }

    $company_id = Registry::get('runtime.company_id');

    fn_attach_image_pairs('paypal_adaptive_logo', 'paypal_adaptive_logo', $company_id);
    fn_attach_image_pairs('paypal_ver_image', 'paypal_ver_image');
}

/**
 * Get PayPal adaptive settings
 *
 * @param string $lang_code
 * @return array mixed
 */
function fn_get_paypal_adaptive_settings($lang_code = DESCR_SL)
{
    $ppa_settings = Settings::instance()->getValues('paypal_adaptive', 'ADDON');

    $company_id = Registry::get('runtime.company_id');

    $ppa_settings['general']['main_pair'] = fn_get_image_pairs($company_id, 'paypal_adaptive_logo', 'M', false, true, $lang_code);

    return $ppa_settings['general'];
}

/**
 * Set payment options
 *
 * @param array $params
 *                payKey                 string
 *                headers                array
 *                paypal_sslcertpath     string
 *                processor_params       array
 *                paypal_set_options_url string
 */
function fn_paypal_adaptive_set_options($params)
{
    if (!empty($params['pay_key'])
        && !empty($params['headers'])
        && isset($params['paypal_sslcertpath'])
        && !empty($params['processor_params'])
        && !empty($params['paypal_set_options_url'])
    ) {

        $post_data = fn_paypal_adaptive_set_options_build_post($params);

        Http::post($params['paypal_set_options_url'], $post_data, array('headers' => $params['headers'], 'ssl_cert' => $params['paypal_sslcertpath']));
    }
}

/**
 * Set payment options build post
 *
 * @param  array $params
 * @return array
 */
function fn_paypal_adaptive_set_options_build_post($params)
{
    $post_data = array();

    if (!empty($params['pay_key'])) {

        $post_data['payKey'] = $params['pay_key'];
        $post_data['requestEnvelope.errorLanguage'] = 'en_US';

        $company_id = Registry::get('runtime.company_id');
        $header_image = fn_get_image_pairs($company_id, 'paypal_adaptive_logo', 'M', false, true);

        if (!empty($header_image['detailed']['image_path'])) {
            $post_data['displayOptions.headerImageUrl'] = $header_image['detailed']['image_path'];
        }

        $post_data['senderOptions.referrerCode'] = 'ST_ShoppingCart_EC_US';
    }

    return $post_data;
}

function fn_paypal_adaptive_get_companies($params, &$fields, $sortings, $condition, $join, $auth, $lang_code, $group)
{
    $fields[] = '?:companies.paypal_verification';
    $fields[] = '?:companies.paypal_email';
    $fields[] = '?:companies.ppa_first_name';
    $fields[] = '?:companies.ppa_last_name';
}

function fn_paypal_adaptive_get_verified_status($company_data)
{
    $current_controller = Registry::get('runtime.controller');
    if (!empty($company_data['paypal_email'])) {
        if (
            is_string($company_data['ppa_first_name'])
            && fn_string_not_empty($company_data['ppa_first_name'])
            && is_string($company_data['ppa_last_name'])
            && fn_string_not_empty($company_data['ppa_last_name'])
        ) {
            $post_data = array(
                'emailAddress' => $company_data['paypal_email'],
                'firstName' => $company_data['ppa_first_name'],
                'lastName' => $company_data['ppa_last_name'],
                'matchCriteria' => 'NAME',
                'requestEnvelope.errorLanguage' => 'en_US',
            );
        } else {
            $post_data = array(
                'emailAddress' => $company_data['paypal_email'],
                'firstName' => $company_data['company'],
                'lastName' => ' ',
                'matchCriteria' => 'NONE',
                'requestEnvelope.errorLanguage' => 'en_US',
            );
        }

        $payment_id = db_get_field('SELECT payment_id FROM ?:payments WHERE processor_id = (SELECT processor_id FROM ?:payment_processors WHERE processor = ?s)', 'PayPal Adaptive');
        if (!empty($payment_id)) {
            $processor_data = fn_paypal_adaptive_get_processor_data($payment_id);
            $headers = fn_paypal_adaptive_build_headers($processor_data);
            $paypal_sslcertpath = '';
            if (!empty($processor_data['processor_params']['authentication_method'])
                && $processor_data['processor_params']['authentication_method'] == 'cert'
                && !empty($processor_data['processor_params']['certificate_filename'])) {
                $paypal_sslcertpath = Registry::get('config.dir.certificates') . $processor_data['processor_params']['certificate_filename'];
            }
            if ($processor_data['processor_params']['mode'] == 'test') {
                $url = 'https://svcs.sandbox.paypal.com/AdaptiveAccounts/GetVerifiedStatus';
            } else {
                $url = 'https://svcs.paypal.com/AdaptiveAccounts/GetVerifiedStatus';
            }
            $response = Http::post($url, $post_data, array('headers' => $headers, 'ssl_cert' => $paypal_sslcertpath));
            parse_str($response, $pp_response);
            if (!empty($pp_response['accountStatus']) && $pp_response['accountStatus'] == 'VERIFIED') {
                db_query('UPDATE ?:companies SET paypal_verification = ?s WHERE company_id = ?i', 'verified', $company_data['company_id']);
            } elseif (!empty($pp_response['responseEnvelope_ack']) && $pp_response['responseEnvelope_ack'] == 'Failure') {
                db_query('UPDATE ?:companies SET paypal_verification = ?s WHERE company_id = ?i', 'not_verified', $company_data['company_id']);
                if (!empty($pp_response['error(0)_message'])) {
                    if ($current_controller == 'paypal_verification') {
                        fn_print_r($company_data['company'] . ' : ' . $pp_response['error(0)_message']);
                    } else {
                        fn_set_notification('W', __('warning'), $pp_response['error(0)_message']);
                    }
                }
            }
        }
    } else {
        db_query('UPDATE ?:companies SET paypal_verification = ?s WHERE company_id = ?i', 'not_checked', $company_data['company_id']);
    }
}

function fn_sd_paypal_adaptive_cs_info_url_auto_paypal_verification()
{
    $key = Registry::get('addons.paypal_adaptive.cron_key');
    $admin_index = Registry::get('config.admin_index');
    
    $args = array(
	'dispatch' => 'paypal_verification.cron_get_verified_status',
	'magic_key' => $key,
    );
    
    $command = fn_get_console_command('php /path/to/cart/', $admin_index, $args);
    $text = __('paypal_adaptive_auto_info_data_verification') . '<br/ >' . $command;

    return $text;
}

function fn_paypal_adaptive_gather_additional_product_data_before_options(&$product, $auth, $params)
{
    if (empty($product['company_id'])) {
        return;
    }

    $company_verification = db_get_field('SELECT paypal_verification FROM ?:companies WHERE company_id = ?i', $product['company_id']);
    $show_vendor_verification = Registry::get('addons.paypal_adaptive.display_verification_status_on_storefront');
    if ($show_vendor_verification == 'Y' && $company_verification == 'verified') {
        $product['paypal_verification']['main_pair'] = fn_get_image_pairs(0, 'paypal_ver_image', 'M', false, true, DESCR_SL);
        if (!empty($product['paypal_verification']['main_pair'])) {
            $width = Registry::get('addons.paypal_adaptive.paypal_verified_image_width');
            $height = Registry::get('addons.paypal_adaptive.paypal_verified_image_height');
            $product['paypal_verification']['width'] = $width ?: 60;
            $product['paypal_verification']['height'] = $height ?: 60;
        } else {
            $product['paypal_verification']['verified'] = 'verified';
        }
    }
}

function fn_paypal_adaptive_calculate_commission($order_info, $company_data, $payout_data)
{
    return fn_calculate_commission_for_payout($order_info, $company_data, $payout_data);
}

function fn_paypal_adaptive_mve_place_order(&$order_info, &$company_data, &$action, &$order_status, &$cart, &$data, &$payout_id, &$auth)
{
    list($is_processor_script, ) = fn_check_processor_script($order_info['payment_id']);
    if ($is_processor_script && fn_check_payment_script('paypal_adaptive.php', $order_info['order_id'])) {
        $data = fn_paypal_adaptive_calculate_commission($order_info, $company_data, $data);
    }
}

function fn_paypal_adaptive_mve_update_order(&$new_order_info, &$order_id, &$old_order_info, &$company_data, &$payout_id, &$payout_data)
{
    list($is_processor_script, ) = fn_check_processor_script($new_order_info['payment_id']);
    if ($is_processor_script && fn_check_payment_script('paypal_adaptive.php', $old_order_info['order_id'])) {
        $payout_data = fn_paypal_adaptive_calculate_commission($new_order_info, $company_data, $payout_data);
    }
}

/**
 * Hook handler: excludes the commission amount from a transaction value for a vendor
 * and sets a transaction value to the commission amount for an admin.
 *
 * @param VendorPayouts $instance       VendorPayouts instance
 * @param array         $params         Search parameters
 * @param int           $items_per_page Items per page
 * @param array         $fields         SQL query fields
 * @param string        $join           JOIN statement
 * @param string        $condition      General condition to filter payouts
 * @param string        $date_condition Additional condition to filter payouts by date
 * @param string        $sorting        ORDER BY statemet
 * @param string        $limit          LIMIT statement
 */
function fn_paypal_adaptive_vendor_payouts_get_list(&$instance, &$params, &$items_per_page, &$fields, &$join, &$condition, &$date_condition, &$sorting, &$limit)
{
    if ($instance->getVendor()) {
        $fields['payout_amount'] = 'IF(payouts.order_id <> 0, payouts.order_amount - payouts.commission_amount, payouts.payout_amount)';
    } else {
        $fields['payout_amount'] = 'IF(payouts.order_id <> 0, payouts.marketplace_profit, payouts.payout_amount)';
    }
}

/**
 * Hook handler: excludes commission from vendor income and sets admin income as a sum of commissions.
 *
 * @param VendorPayouts $instance       VendorPayouts instance
 * @param array         $params         Search parameters
 * @param array         $fields         SQL query fields
 * @param string        $join           JOIN statement
 * @param string        $condition      General condition to filter payouts
 * @param string        $date_condition Additional condition to filter payouts by date
 */
function fn_paypal_adaptive_vendor_payouts_get_income(&$instance, &$params, &$fields, &$join, &$condition, &$date_condition)
{
    if ($instance->getVendor()) {
        $fields['orders_summary'] = 'SUM(payouts.order_amount) - SUM(payouts.commission_amount)';
    } else {
        $fields['orders_summary'] = 'SUM(payouts.marketplace_profit)';
    }
}
