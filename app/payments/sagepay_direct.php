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
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

require_once(Registry::get('config.dir.payments') . 'sagepay_files/sagepay.functions.php');

if (defined('PAYMENT_NOTIFICATION')) {
    if ($_SERVER['REQUEST_METHOD'] == "POST" && !empty($_REQUEST['cres']) && !empty($_REQUEST['threeDSSessionData'])) {

        $post['CRes'] = $_REQUEST['cres'];
        $post['VPSTxId'] = str_replace(['{', '}'], '', $_REQUEST['threeDSSessionData']);
        $secure_verified_3d = true;
        $order_id = $_REQUEST['order_id'];
    }

    Tygh::$app['session']['already_posted'] = empty(Tygh::$app['session']['already_posted']) ? false : Tygh::$app['session']['already_posted'];
    $already_posted = &Tygh::$app['session']['already_posted'];

    if (!empty($secure_verified_3d) && empty($already_posted)) {
        if ($_REQUEST['payment_mode'] == 'Y') {
            $post_address = 'https://test.sagepay.com/gateway/service/direct3dcallback.vsp';
        } elseif ($_REQUEST['payment_mode'] == 'N') {
            $post_address = 'https://live.sagepay.com/gateway/service/direct3dcallback.vsp';
        } elseif ($_REQUEST['payment_mode'] == 'S') {
            $post_address = 'https://test.sagepay.com/Simulator/VSPDirectCallback.asp';
        }

        $result = Http::post($post_address, $post);
        $already_posted = true;
    }

} else {

    $pp_merch = $processor_data['processor_params']['vendor'];
    $pp_curr = $processor_data['processor_params']['currency'];

    if ($processor_data['processor_params']['testmode'] == 'Y') {
        $post_address = 'https://test.sagepay.com/gateway/service/vspdirect-register.vsp';
    } elseif ($processor_data['processor_params']['testmode'] == 'N') {
        $post_address = 'https://live.sagepay.com/gateway/service/vspdirect-register.vsp';
    } elseif ($processor_data['processor_params']['testmode'] == 'S') {
        $post_address = 'https://test.sagepay.com/Simulator/VSPDirectGateway.asp';
    }

    $already_posted = false;
    $card_type = fn_get_payment_card($order_info['payment_info']['card_number'], array(
        'visa' => 'VISA',
        'visa_debit' => 'DELTA',
        'mastercard' => 'MC',
        'mastercard_debit' => 'MCDEBIT',
        'amex' => 'AMEX',
        'jcb' => 'JCB',
        'maestro' => 'MAESTRO',
        'visa_electron' => 'UKE',
        'laser' => 'LASER',
        'diners_club_carte_blanche' => 'DINERS',
        'diners_club_international' => 'DINERS'
    ));

    $post = array();
    $post['VPSProtocol'] = '4.00';
    $post['TxType'] = $processor_data['processor_params']['transaction_type'];
    $post['Vendor'] = $pp_merch;
    $post['VendorTxCode'] = ((!empty($processor_data['processor_params']['order_prefix']) ? $processor_data['processor_params']['order_prefix'] : 'O') . '-' . (($order_info['repaid']) ? ($order_info['order_id'] . '-' . $order_info['repaid']) : $order_info['order_id'])) . '-' . fn_date_format(time(), '%H_%M_%S');
    $post['Amount'] = fn_format_price_by_currency($order_info['total'], CART_PRIMARY_CURRENCY, $pp_curr);
    $post['Currency'] = $pp_curr;
    $post['Description'] = 'Payment for Order ' . $order_id;
    $post['CardHolder'] = $order_info['payment_info']['cardholder_name'];
    $post['CardNumber'] = $order_info['payment_info']['card_number'];
    $post['ExpiryDate'] = $order_info['payment_info']['expiry_month'] . $order_info['payment_info']['expiry_year'];
    $post['CV2'] = $order_info['payment_info']['cvv2'];
    $post['CardType'] = $card_type;
    $post['Apply3DSecure'] = 1;

    $post['BillingAddress1'] = !empty($order_info['b_address']) ? $order_info['b_address'] : $order_info['s_address'];
    $post['BillingAddress2'] = !empty($order_info['b_address_2']) ? $order_info['b_address_2'] : $order_info['s_address_2'];
    //Workaround for the Irish customers. According to the documentation we should enter zip code anyway.
    if (!empty($order_info['b_zipcode'])) {
        $post['BillingPostCode'] = $order_info['b_zipcode'];
    } elseif (!empty($order_info['s_zipcode'])) {
        $post['BillingPostCode'] = $order_info['s_zipcode'];
    } else {
        $post['BillingPostCode'] = '0000';
    }
    $post['BillingCountry'] = !empty($order_info['b_country']) ? $order_info['b_country'] : $order_info['s_country'];
    if ($order_info['b_country'] == 'US') { // state is for US customers only
        if (!empty($order_info['b_state'])) {
            $post['BillingState'] = $order_info['b_state'];
        } else {
            $post['BillingState'] = $order_info['s_state'];
        }
    }
    $post['BillingCity'] = !empty($order_info['b_city']) ? $order_info['b_city'] : $order_info['s_city'];
    $post['BillingFirstnames'] = !empty($order_info['b_firstname']) ? $order_info['b_firstname'] : $order_info['s_firstname'];
    $post['BillingSurname'] = !empty($order_info['b_lastname']) ? $order_info['b_lastname'] : $order_info['s_lastname'];

    $post['DeliveryAddress1'] = $order_info['s_address'];
    $post['DeliveryAddress2'] = $order_info['s_address_2'];
    $post['DeliveryPostCode'] = !empty($order_info['s_zipcode']) ? $order_info['s_zipcode'] : '0000';
    $post['DeliveryCountry'] = $order_info['s_country'];

    if ($order_info['s_country'] == 'US') {// state is for US customers only
        $post['DeliveryState'] = $order_info['s_state'];
    }
    $post['DeliveryCity'] = $order_info['s_city'];
    $post['DeliveryFirstnames'] = $order_info['s_firstname'];
    $post['DeliverySurname'] = $order_info['s_lastname'];

    $post['CustomerName'] = $order_info['firstname'] . ' ' . $order_info['lastname'];
    $post['ContactNumber'] = $order_info['phone'];
    $post['ContactFax'] = $order_info['fax'];
    $post['CustomerEMail'] = $order_info['email'];

    // affiliate code
    $post['ReferrerID'] = '7B356DCA-BDB1-42EF-B4CB-FEC07D977879';

    $post['Basket'] = fn_sagepay_get_basket($order_info, CART_PRIMARY_CURRENCY, $pp_curr);

    $post['ClientIPAddress'] = $_SERVER['REMOTE_ADDR'];

    $browser_settings = $_REQUEST['browser_settings'];
    $post['BrowserJavascriptEnabled'] = !empty($browser_settings['js_enabled']) ? 1 : 0;
    if ($post['BrowserJavascriptEnabled']) {
        // FIXME: Workaround Chrome bug: https://support.google.com/chrome/thread/53600062?hl=en
        if ((int) $browser_settings['color_depth'] === 30) {
            $browser_settings['color_depth'] = 24;
        }
        $post['BrowserJavaEnabled'] = !empty($browser_settings['java_enabled']) ? 1 : 0;
        $post['BrowserColorDepth'] = $browser_settings['color_depth'];
        $post['BrowserScreenHeight'] = $browser_settings['screen_height'];
        $post['BrowserScreenWidth'] = $browser_settings['screen_width'];
        $post['BrowserTZ'] = $browser_settings['timezone'];
    }

    $post['BrowserAcceptHeader'] = $_SERVER['HTTP_ACCEPT'];
    $post['BrowserLanguage'] = $browser_settings['language'];
    $post['BrowserUserAgent'] = $browser_settings['user_agent'];

    $payment_mode = $processor_data['processor_params']['testmode'];
    $post['ThreeDSNotificationURL'] = fn_url("payment_notification.notify?payment=sagepay_direct&order_id={$order_info['order_id']}&payment_mode={$payment_mode}", AREA, 'current');

    $post['ChallengeWindowSize'] = '05';

    Registry::set('log_cut_data', array('CardNumber', 'ExpiryDate', 'StartDate', 'CV2'));
    $result = Http::post($post_address, $post);
}

$rarr = explode("\r\n", $result);
$response = array();
foreach ($rarr as $v) {
    if (preg_match('/([^=]+?)=(.+)/', $v, $m)) {
        $response[$m[1]] = trim($m[2]);
    }
}

if ($response['Status'] == '3DAUTH') {

    $post_data = [
        'creq' => $response['CReq'],
        'threeDSSessionData' => $response['VPSTxId']
    ];

    fn_create_payment_form($response['ACSURL'], $post_data, '3D Secure');
    exit;

} elseif ($response['Status'] == 'OK' || $response['Status'] == 'AUTHENTICATED' || $response['Status'] == 'REGISTERED') {
    $pp_response['order_status'] = 'P';
    if (!empty($response['TxAuthNo'])) $pp_response['reason_text'] = 'AuthNo: ' . @$response['TxAuthNo'];
    if (!empty($response['SecurityKey'])) {
        $pp_response['reason_text'] = 'SecurityKey: ' . $response['SecurityKey'];
    } else {
        $pp_response['reason_text'] = '';
    }
} else {
    $pp_response['order_status'] = 'F';
    $pp_response['reason_text'] = '';
}

if (!empty($response['Status'])) {
    $pp_response['reason_text'] = 'Status: ' . @$response['StatusDetail'] . ' (' . $response['Status'] . ') ';
}
if (!empty($response['VPSTxId'])) {
    $pp_response['transaction_id'] = $response['VPSTxId'];
}
if (!empty($response['AVSCV2']) && $response['AVSCV2'] != 'DATA NOT CHECKED') {
    $pp_response['reason_text'] .= ' (AVS/CVV2: {' . $response['AVSCV2'] . '})  ';
}
if (!empty($response['AddressResult']) && $response['AddressResult'] != 'NOTPROVIDED') {
    $pp_response['reason_text'] .= ' (Address: {' . $response['AddressResult'] . '})  ';
}
if (!empty($response['PostCodeResult']) && $response['PostCodeResult'] != 'NOTPROVIDED') {
    $pp_response['reason_text'] .= ' (PostCode: {' . $response['PostCodeResult'] . '})  ';
}
if (!empty($response['CV2Result']) && $response['CV2Result'] != 'NOTPROVIDED') {
    $pp_response['reason_text'] .= ' (CV2: {' . $response['CV2Result'] . '})  ';
}
if (!empty($response['3DSecureStatus'])) {
    $pp_response['reason_text'] .= ' (3D Result: {' . $response['3DSecureStatus'] . '})  ';
}

if (!empty($secure_verified_3d) && !empty($order_id) && fn_check_payment_script('sagepay_direct.php', $order_id) == true) {
    unset(Tygh::$app['session']['already_posted']);

    fn_finish_payment($order_id, $pp_response);
    fn_order_placement_routines('route', $order_id);
}
