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

use Tygh\Enum\YesNo;
use Tygh\Http;
use Tygh\Registry;

/**
 * @var array $processor_data
 * @var array $order_info
 * @var int $order_id
 */

if (!defined('BOOTSTRAP')) { die('Access denied'); }

include_once(Registry::get('config.dir.payments') . 'cmpi.php');

$use_cardinal = !empty($processor_data['processor_params']['merchant_id'])
    && !empty($processor_data['processor_params']['processor_id'])
    && !empty($processor_data['processor_params']['transaction_password'])
    && !empty($processor_data['processor_params']['transaction_url']);

if ($use_cardinal) {
    if (!defined('CMPI_PROCESSED')) {
        fn_cmpi_lookup($processor_data, $order_info, $mode);
    }
} else {
    define('DO_DIRECT_PAYMENT', true);
}

if (defined('DO_DIRECT_PAYMENT')) {

$currency = fn_paypal_get_valid_currency($processor_data['processor_params']['currency']);

$payflow_username = $processor_data['processor_params']['username'];
$payflow_vendor = $processor_data['processor_params']['vendor'];
$payflow_partner = $processor_data['processor_params']['partner'];
$payflow_password = $processor_data['processor_params']['password'];
$items = '';

if ($processor_data['processor_params']['mode'] == 'test') {
    $payflow_url = "pilot-payflowpro.paypal.com";
} else {
    $payflow_url = "payflowpro.paypal.com";
}

if ($currency['code'] == CART_PRIMARY_CURRENCY) {
    if (!empty($order_info['products'])) {
        foreach ($order_info['products'] as $k => $v) {
            $unit_price = fn_format_price($v['subtotal'] / $v['amount']);
            $items .= "<SKU>{$v['product_code']}</SKU>
                    <Description>" . htmlspecialchars($v['product']) . "</Description>
                    <Quantity>{$v['amount']}</Quantity>
                    <UnitPrice>{$unit_price}</UnitPrice>";
        }
    }
    //phpcs:ignore
    if (!empty($order_info['taxes'])) {
        foreach ($order_info['taxes'] as $tax_id => $tax) {
            //phpcs:ignore
            if (YesNo::isTrue($tax['price_includes_tax'])) {
                continue;
            }
            $unit_price = fn_format_price($tax['tax_subtotal']);
            $items .= "<SKU>" . htmlspecialchars($tax['description']) . "</SKU>
            <Description>" . htmlspecialchars($tax['description']) . "</Description>
            <Quantity>1</Quantity>
            <UnitPrice>{$unit_price}</UnitPrice>";
        }
    }
    $subtotal_discount = (floatval($order_info['subtotal_discount'])) ? fn_format_price($order_info['subtotal_discount']) : 0;
    $total = $order_info['total'];
} else {
    $subtotal_discount = 0;
    $total = fn_format_price_by_currency($order_info['total'], CART_PRIMARY_CURRENCY, $currency['code']);

    $items .= "<SKU>ORDER_ID_{$order_id}</SKU>
            <Description>" . __('total_product_cost') . "</Description>
            <Quantity>1</Quantity>
            <UnitPrice>{$total}</UnitPrice>";
}


$payflow_expire = '20' . $order_info['payment_info']['expiry_year'] . $order_info['payment_info']['expiry_month'];
$payflow_order_id = $processor_data['processor_params']['order_prefix'] . $order_id . (($order_info['repaid']) ? "_{$order_info['repaid']}" : '')  . '_' . fn_date_format(time(), '%H_%M_%S');

    $b_fullname = $order_info['b_firstname'] . ' ' . $order_info['b_lastname'];

$payflow_3d_secure = '';
if ($use_cardinal) {
    $session = & Tygh::$app['session'];

    $fields = array('eci_flag', 'pares', 'xid', 'cavv', 'enrolled');
    foreach ($fields as $field) {
        $session['cmpi'][$field] = isset($session['cmpi'][$field])
            ? $session['cmpi'][$field]
            : '';
    }

    $payflow_3d_secure = <<<XML
<BuyerAuthResult>
    <Status>{$session['cmpi']['enrolled']}</Status>
    <AuthenticationId>{$session['cmpi']['pares']}</AuthenticationId>
    <ECI>{$session['cmpi']['eci_flag']}</ECI>
    <CAVV>{$session['cmpi']['cavv']}</CAVV>
    <XID>{$session['cmpi']['xid']}</XID>
</BuyerAuthResult>
XML;

}

$post = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<XMLPayRequest Timeout='45' version="2.0">
    <RequestData>
        <Partner>$payflow_partner</Partner>
        <Vendor>$payflow_vendor</Vendor>
        <Transactions>
            <Transaction>
                <Sale>
                    <PayData>
                        <Invoice>
                            <BillTo>
                                <Name>{$b_fullname}</Name>
                                <Address>
                                    <Street>{$order_info['b_address']}</Street>
                                    <City>{$order_info['b_city']}</City>
                                    <State>{$order_info['b_state']}</State>
                                    <Zip>{$order_info['b_zipcode']}</Zip>
                                    <Country>{$order_info['b_country']}</Country>
                                </Address>
                                <EMail>{$order_info['email']}</EMail>
                                <Phone>{$order_info['phone']}</Phone>
                                <Fax>{$order_info['fax']}</Fax>
                            </BillTo>
                            <ShipTo>
                                <Address>
                                    <Street>{$order_info['s_address']}</Street>
                                    <City>{$order_info['s_city']}</City>
                                    <State>{$order_info['s_state']}</State>
                                    <Zip>{$order_info['s_zipcode']}</Zip>
                                    <Country>{$order_info['s_country']}</Country>
                                </Address>
                            </ShipTo>
                            <DiscountAmt>{$subtotal_discount}</DiscountAmt>
                            <TotalAmt>{$total}</TotalAmt>
                            <Comment>{$payflow_order_id}</Comment>
                            <Items>{$items}</Items>
                        </Invoice>
                        <Tender>
                            <Card>
                                <CardNum>{$order_info['payment_info']['card_number']}</CardNum>
                                <ExpDate>{$payflow_expire}</ExpDate>
                                <NameOnCard>{$order_info['payment_info']['cardholder_name']}</NameOnCard>
                                <CVNum>{$order_info['payment_info']['cvv2']}</CVNum>
                            </Card>
                            {$payflow_3d_secure}
                        </Tender>
                    </PayData>
                </Sale>
            </Transaction>
        </Transactions>
    </RequestData>
    <RequestAuth>
        <UserPass>
            <User>{$payflow_username}</User>
            <Password>{$payflow_password}</Password>
        </UserPass>
    </RequestAuth>
</XMLPayRequest>
XML;

$post_url = "https://{$payflow_url}:443/transaction";

Registry::set('log_cut_data', array('CardNum', 'ExpDate', 'NameOnCard', 'CVNum'));
$response_data = Http::post($post_url, $post, array(
    'headers' => array(
        'Content-type: text/xml',
        'X-VPS-REQUEST-ID: ' . $payflow_order_id,
        'X-VPS-VIT-CLIENT-CERTIFICATION-ID: 5b329b34269933161c60aeda0f14d0d8',
        'X-VPS-CLIENT-TIMEOUT: 45',
        'Connection: close'

    )
));

fn_pp_save_mode($order_info);

$pp_response = array();
$pp_response['reason_text'] = '';

preg_match("/<Result>(.*)<\/Result>/", $response_data, $_result);
if (!empty($_result[1])) {
    $pp_response['reason_text'] = "Result: ".$_result[1];
}

preg_match_all("/<Message>(.*?)<\/Message>/", $response_data, $_message);
if (!empty($_message[1])) {
    $pp_response['reason_text'] .= ("; " . end($_message[1]) . "; ");
}

preg_match("/<AuthCode>(.*)<\/AuthCode>/", $response_data, $_auth);
if (!empty($_auth[1])) {
    $pp_response['reason_text'] .= ("Auth Code: ".$_auth[1] . "; ");
}

preg_match('/<TransactionResult (?:.*) Duplicate="(.*)"/i', $response_data, $_duplicate);
if (!empty($_duplicate[1])) {
    $pp_response['reason_text'] .= ("Duplicate: " . $_duplicate[1] . "; ");
}

preg_match("/<PNRef>(.*)<\/PNRef>/", $response_data, $_transaction_id);
if (!empty($_transaction_id[1])) {
    $pp_response['transaction_id'] = $_transaction_id[1];
}

preg_match("/<IAVSResult>(.*)<\/IAVSResult>/", $response_data, $_avs);
if (!empty($_avs[1])) {
    $pp_response['descr_avs'] = $_avs[1];
}

preg_match("/<StreetMatch>(.*)<\/StreetMatch>/", $response_data, $_avs_street);
if (!empty($_avs_street[1])) {
    $pp_response['descr_avs'] =  "; Street Match: ". $_avs_street[1];
}

preg_match("/<PNZipMatchRef>(.*)<\/ZipMatch>/", $response_data, $_avs_zip);
if (!empty($_avs_zip[1])) {
    $pp_response['descr_avs'] =  "; Zip Match: ". $_avs_zip[1];
}

if ($_result[1] === '0') {
    $pp_response['order_status'] = 'P';
} else {
    $pp_response['order_status'] = 'F';
}

}
