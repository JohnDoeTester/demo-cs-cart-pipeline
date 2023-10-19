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

use Tygh\Registry;
use Tygh\Http;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// backward compatibility for admin panel
if (strlen($order_info['payment_info']['expiry_year']) == 2) {
    $current_year = date('Y');
    $order_info['payment_info']['expiry_year'] = substr($current_year, 0, 2) . $order_info['payment_info']['expiry_year'];
}

$post_data = array(
    'merNo'          => $processor_data['processor_params']['onekpay_merchant_number'],
    'terNo'          => $processor_data['processor_params']['onekpay_terminal_number'],
    'hash'           => $processor_data['processor_params']['onekpay_secure_hash'],
    'transType'      => 'sales', // FIXME: Should be configurable
    'transModel'     => 'M',     // FIXME: Should be configurable
    'EncryptionMode' => 'SHA256',
    'CharacterSet'   => 'UTF8',
    'merMgrURL'      => fn_get_storefront_url('current', $order_info['company_id']),
    'platForm'       => 'cs-cart',
    'orderNo'        => $order_info['order_id'],
    'amount'         => number_format($order_info['total'], 2, '.', ''),
    'currencyCode'   => CART_PRIMARY_CURRENCY,
    'cardCountry'    => $order_info['b_country'] ? $order_info['b_country'] : $order_info['s_country'],
    'cardState'      => $order_info['b_state'] ? $order_info['b_state'] : $order_info['s_state'],
    'cardCity'       => $order_info['b_city'] ? $order_info['b_city'] : $order_info['s_city'],
    'cardAddress'    => $order_info['b_address'] ? $order_info['b_address'] : $order_info['s_address'],
    'cardZipCode'    => $order_info['b_zipcode'] ? $order_info['b_zipcode'] : $order_info['s_zipcode'],
    'cardEmail'      => $order_info['email'],
    'cardFullname'   => $order_info['b_firstname'] ? $order_info['b_firstname'] : $order_info['s_fisrtname'],
    'cardFullPhone'  => $order_info['b_phone'] ? $order_info['b_phone'] : $order_info['s_phone'],
    'grContry'       => $order_info['s_country'],
    'grState'        => $order_info['s_state'],
    'grCity'         => $order_info['s_city'],
    'grAddress'      => $order_info['s_address'],
    'grZipCode'      => $order_info['s_zipcode'],
    'grEmail'        => $order_info['email'],
    'grphoneNumber'  => $order_info['s_phone'],
    'grPerName'      => $order_info['s_firstname'],
    'payIP'          => $order_info['ip_address'],
    'cardNO'         => $order_info['payment_info']['card_number'],
    'expYear'        => $order_info['payment_info']['expiry_year'],
    'expMonth'       => $order_info['payment_info']['expiry_month'],
    'cvv'            => $order_info['payment_info']['cvv2'],
);

if ($processor_data['processor_params']['mode'] == 'test') {
    $action = ONEKPAY_TEST_API_URL;
} else {
    $action = ONEKPAY_API_URL;
}

$hashcode = fn_onekpay_get_hash(array(
    'EncryptionMode' => $post_data['EncryptionMode'],
    'CharacterSet'   => $post_data['CharacterSet'],
    'merNo'          => $post_data['merNo'],
    'terNo'          => $post_data['terNo'],
    'orderNo'        => $post_data['orderNo'],
    'currencyCode'   => $post_data['currencyCode'],
    'amount'         => $post_data['amount'],
    'payIP'          => $post_data['payIP'],
    'transType'      => $post_data['transType'],
    'transModel'     => $post_data['transModel'],
), $post_data['hash']);

$post_data['hashcode'] = $hashcode;

Registry::set('log_cut_data', array('cardNO', 'expYear', 'expMonth', 'cvv', 'merNo', 'terNo', 'hash'));
$response_data = Http::post($action, $post_data);
$response_data = json_decode($response_data, true);
$pp_response = fn_onekpay_response_processing($response_data, $post_data['hash']);
