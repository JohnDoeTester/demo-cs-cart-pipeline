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

use Tygh\Enum\OrderStatuses;
use Tygh\Registry;

require_once(Registry::get('config.dir.payments') . 'ogone_files/func.php');

if (defined('PAYMENT_NOTIFICATION')) {

    if ($mode == 'process') {
        $pp_response = array();

        list($order_id,) = explode('_', $_REQUEST['orderID']);

        if (!fn_check_payment_script('ogone_web.php', $order_id)) {
            die('Access denied');
        }

        $order_info = fn_get_order_info($order_id);

        $processor_params = $order_info['payment_method']['processor_params'];
        $response = $_REQUEST;

        if (!empty($processor_params['sha_sign_out'])) {
            $expected_signature = isset($response['SHASIGN']) ? $response['SHASIGN']: '';
            unset($response['dispatch'], $response['payment'], $response['SHASIGN']);

            $actual_signature = fn_ogone_calculate_signature($response, $processor_params['sha_sign_out']);

            if (strtoupper($actual_signature) !== strtoupper($expected_signature)) {
                die('Access denied');
            }
        }

        $pp_response['order_status'] = fn_ogone_get_status($response['STATUS']);
        $pp_response['transaction_id'] = $response['PAYID'];

        list($is_status_known, $status_description) = fn_ogone_get_status_description($response['STATUS']);
        $pp_response['reason_text'] = $status_description;

        if ($pp_response['order_status'] == 'P') {
            $pp_response["reason_text"] .= ' (ACCEPTANCE: ' . $response['ACCEPTANCE'] . ')';
            $pp_response["reason_text"] .= ' (' . $response['PM'] . ': ' . $response['BRAND'] . ' ' . $response['CARDNO'] . ')';

        } elseif (!empty($response['NCERROR'])) {
            list($is_error_known, $error_description) = fn_ogone_get_error_description($response['NCERROR']);
            if (!$is_error_known && !empty($response['NCERRORPLUS'])) {
                $error_description = $response['NCERRORPLUS'];
            }
            $pp_response['reason_text'] = $error_description;
        }

        fn_finish_payment($order_id, $pp_response);
        exit;

    } elseif ($mode == 'result') {
        if (fn_check_payment_script('ogone_web.php', $_REQUEST['order_id'])) {
            $order_info = fn_get_order_info($_REQUEST['order_id'], true);
            if ($order_info['status'] === OrderStatuses::INCOMPLETED) {
                fn_change_order_status($_REQUEST['order_id'], OrderStatuses::OPEN, '', fn_get_notification_rules([], true));
            }
        }
        fn_order_placement_routines('route', $_REQUEST['order_id'], false);

    } elseif ($mode == 'cancel') {
        if (fn_check_payment_script('ogone_web.php', $_REQUEST['order_id'])) {
            $pp_response = array();
            $pp_response['order_status'] = 'N';
            $pp_response['reason_text'] = __('text_transaction_cancelled');
            fn_finish_payment($_REQUEST['order_id'], $pp_response);
            fn_order_placement_routines('route', $_REQUEST['order_id'], false);
        }
    }
} else {

    /** @var array $processor_data */
    /** @var array $order_info */
    /** @var int $order_id */

    $processor_params = $processor_data['processor_params'];

    $pspid = $processor_params['pspid'];
    $sha_in = $processor_params['sha_sign'];
    $currency_code = $processor_params['currency'];
    $submit_url = $processor_params['mode'] == 'test'
        ? 'https://secure.ogone.com:443/ncol/test/orderstandard.asp'
        : 'https://secure.ogone.com:443/ncol/prod/orderstandard.asp';

    $order_no = $order_id . '_' . TIME;
    if ($order_info['repaid']) {
        $order_no .= '_' . $order_info['repaid'];
    }

    $supported_languages = array(
        'en' => 'en_US',
        'fr' => 'fr_FR',
        'nl' => 'nl_NL',
        'it' => 'it_IT',
        'de' => 'de_DE',
        'es' => 'es_ES',
        'no' => 'no_NO',
    );
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
        'language'     => isset($supported_languages[CART_LANGUAGE])
            ? $supported_languages[CART_LANGUAGE]
            : 'en_US',
        'pspid'        => $pspid,
        'orderid'      => $order_no,
        'currency'     => $currency_code,
        'accepturl'    => fn_url("payment_notification.result?payment=ogone_web&order_id={$order_id}", AREA, 'current'),
        'declineurl'   => fn_url("payment_notification.result?payment=ogone_web&order_id={$order_id}", AREA, 'current'),
        'exceptionurl' => fn_url("payment_notification.result?payment=ogone_web&order_id={$order_id}", AREA, 'current'),
        'cancelurl'    => fn_url("payment_notification.cancel?payment=ogone_web&order_id={$order_id}", AREA, 'current'),
    );

    if (isset($processor_params['use_new_sha_method'])
        && $processor_params['use_new_sha_method'] == 'Y'
    ) {
        //New: All parameters in alphabetical order
        $post['shasign'] = fn_ogone_calculate_signature(array_filter($post, 'fn_string_not_empty'), $sha_in);
    } else {
        //Old: SHA-1(OrderId + Amount + Currency + PSPID + SHA-IN)
        $post['shasign'] = sha1($post['orderid'] . $post['amount'] . $post['currency'] . $post['pspid'] . $sha_in);
    }

    fn_create_payment_form($submit_url, $post, 'Ingenico ePayments');
}
