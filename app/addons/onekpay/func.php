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
use Tygh\Settings;
use Tygh\Http;

if (!defined('BOOTSTRAP')) { die('Access denied'); }


/**
 * Installs OnekPay payment processor.
 */
function fn_onekpay_install()
{
    /** @var \Tygh\Database\Connection $db */
    $db = Tygh::$app['db'];

    if (!$db->getField('SELECT type FROM ?:payment_processors WHERE processor_script = ?s', 'onekpay.php')) {
        $db->query("INSERT INTO ?:payment_processors ?e", array(
            'processor' => 'BillriantPay',
            'processor_script' => 'onekpay.php',
            'processor_template' => 'views/orders/components/payments/cc.tpl',
            'admin_template' => 'onekpay.tpl',
            'callback' => 'Y',
            'type' => 'P',
            'addon' => 'onekpay',
        ));
    }
}

/**
 * Disables OnekPay payment methods upon add-on uninstallation.
 */
function fn_onekpay_uninstall()
{
    /** @var \Tygh\Database\Connection $db */
    $db = Tygh::$app['db'];

    $processor_id = $db->getField(
        'SELECT processor_id FROM ?:payment_processors WHERE processor_script = ?s', 'onekpay.php'
    );

    if ($processor_id) {
        $db->query('DELETE FROM ?:payment_processors WHERE processor_id = ?i', $processor_id);
        $db->query('UPDATE ?:payments SET ?u WHERE processor_id = ?i',
                   array(
                       'processor_id'     => 0,
                       'processor_params' => '',
                       'status'           => 'D',
                   ),
                   $processor_id
        );
    }
}

/**
 * Get payment hash.
 *
 * @param array  $params      Array with data for getting hash
 * @param string $secure_hash Merchant secure hash
 *
 * @return string
 */
function fn_onekpay_get_hash($params, $secure_hash)
{
    $str = '';
    foreach ($params as $key => $value) {
        $str .= $key.'='.$value;
        $str .= '&';
    }
    $str .= $secure_hash;

    $hash = hash('sha256', $str);
    return $hash;
}

/**
 * Payment response processing.
 *
 * @param array $response_data Array with response data
 * @param array $secure_hash   Merchant secure hash
 *
 * @return array
 */
function fn_onekpay_response_processing($response_data, $secure_hash)
{
    $hash = fn_onekpay_get_hash(array(
        'amount'       => $response_data['amount'],
        'currencyCode' => $response_data['currencyCode'],
        'merNo'        => $response_data['merNo'],
        'orderNo'      => $response_data['orderNo'],
        'respCode'     => $response_data['respCode'],
        'respMsg'      => $response_data['respMsg'],
        'terNo'        => $response_data['terNo'],
        'tradeNo'      => $response_data['tradeNo'],
        'transType'    => $response_data['transType'],
    ), $secure_hash);

    if (strcmp($hash, $response_data['hashcode']) == 0) {
        if ($response_data['respCode'] == '00') {
            $pp_response['order_status'] = 'P';
            $pp_response['reason_text'] = ucfirst($response_data['respMsg']);
            $pp_response['transaction_id'] = $response_data['tradeNo'];
        } else {
            $pp_response['order_status'] = 'F';
            $pp_response['reason_text'] = __("onekpay.payment_failed", ['[error_message]' => ucfirst($response_data['respMsg'])]);
        }
    } else {
        $pp_response['order_status'] = 'F';
        $pp_response['reason_text'] = __("onekpay.payment_failed", ['[error_message]' => ucfirst($response_data['respMsg'])]);
    }

    return $pp_response;
}
