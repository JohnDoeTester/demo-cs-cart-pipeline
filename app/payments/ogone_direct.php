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

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

use Tygh\Http;
use Tygh\Registry;

require_once(Registry::get('config.dir.payments') . 'ogone_files/func.php');

/** @var array $processor_data */
/** @var array $order_info */
/** @var int $order_id */

$processor_params = $processor_data['processor_params'];
$pspid = $processor_params['pspid'];
$sha_in = $processor_params['sha_sign'];
$currency_code = $processor_params['currency'];
$submit_url = $processor_params['mode'] == 'test'
    ? 'https://secure.ogone.com:443/ncol/test/orderdirect.asp'
    : 'https://secure.ogone.com:443/ncol/prod/orderdirect.asp';

$order_no = $processor_data['processor_params']['order_prefix'] . $order_id;
if ($order_info['repaid']) {
    $order_no .= '_' . $order_info['repaid'];
}

$owneraddress = trim($order_info['b_address']);
if (!empty($order_info['b_address_2'])) {
    $owneraddress .= '; ' . trim($order_info['b_address_2']);
}

if ($currency_code == CART_SECONDARY_CURRENCY) {
    $amount = $order_info['total'];
} else {
    $amount = fn_format_price_by_currency($order_info['total'], CART_SECONDARY_CURRENCY, $currency_code);
}
$amount *= 100;

$post = array(
    'amount'       => $amount,
    'email'        => fn_ogone_subtrim($order_info['email'], 50),
    'owneraddress' => fn_ogone_subtrim($owneraddress, 35),
    'ownertown'    => fn_ogone_subtrim($order_info['b_city'], 25),
    'ownercty'     => fn_ogone_subtrim($order_info['b_country']),
    'ownerzip'     => fn_ogone_subtrim($order_info['b_zipcode'], 10),
    'ownertelno'   => fn_ogone_subtrim($order_info['phone'], 30),
    'pspid'        => $pspid,
    'pswd'         => $processor_data['processor_params']['password'],
    'orderid'      => $order_no,
    'currency'     => $currency_code,
    'cn'           => $order_info['payment_info']['cardholder_name'],
    'cardno'       => $order_info['payment_info']['card_number'],
    'ed'           => $order_info['payment_info']['expiry_month'] . '/' . $order_info['payment_info']['expiry_year'],
    'cvc'          => $order_info['payment_info']['cvv2'],
    'withroot'     => 'Y',
    'remote_addr'  => $_SERVER['REMOTE_ADDR'],
);
if (!empty($processor_params['userid'])) {
    $post['userid'] = $processor_data['processor_params']['userid'];
}

if (isset($processor_params['use_new_sha_method'])
    && $processor_params['use_new_sha_method'] == 'Y'
) {
    //New: All parameters in alphabetical order
    $post['shasign'] = fn_ogone_calculate_signature(array_filter($post, 'fn_string_not_empty'), $sha_in);
} else {
    //Old: SHA-1(OrderID + Amount + Currency + Cardno + PSPID + SHA-IN)
    $post['shasign'] = sha1($post['orderid'] . $post['amount'] . $post['currency'] . $post['cardno'] . $post['pspid'] . $sha_in);
}

$response_body = Http::post($submit_url, $post);

$response_decoded = simplexml_load_string($response_body);

if (isset($response_decoded->ncresponse)) {

    $response = $response_decoded->ncresponse->attributes();

    $pp_response['order_status'] = fn_ogone_get_status($response['STATUS']);
    $pp_response['transaction_id'] = (string) $response['PAYID'];

    list($is_status_known, $status_description) = fn_ogone_get_status_description($response['STATUS']);
    $pp_response['reason_text'] = $status_description;

    if ($pp_response['order_status'] == 'P') {
        $pp_response["reason_text"] .= ' (ACCEPTANCE: ' . $response['ACCEPTANCE'] . ')';
        $pp_response["reason_text"] .= ' (' . $response['PM'] . ': ' . $response['BRAND'] . ' ' . $response['CARDNO'] . ')';

    } elseif (!empty($response['NCERROR'])) {
        list($is_error_known, $error_description) = fn_ogone_get_error_description($response['NCERROR']);
        if (!$is_error_known && !empty($response['NCERRORPLUS'])) {
            $error_description = (string) $response['NCERRORPLUS'];
        }
        $pp_response['reason_text'] = $error_description;
    }
}
