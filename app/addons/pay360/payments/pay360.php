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

use Tygh\Enum\OrderStatuses;
use Tygh\Enum\YesNo;
use Tygh\Http;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

if (defined('PAYMENT_NOTIFICATION')) {
    $order_id = $_REQUEST['order_id'];

    if (!fn_check_payment_script('pay360.php', $order_id)) {
        die('Access denied');
    }

    $payment_id = db_get_field('SELECT payment_id FROM ?:orders WHERE order_id = ?i', $order_id);
    $processor_data = fn_get_payment_method_data((int) $payment_id);

    if (YesNo::toBool($processor_data['processor_params']['test'])) {
        $payment_gateway_api_url = 'https://api.mite.pay360.com';
    } else {
        $payment_gateway_api_url = 'https://api.pay360.com';
    }

    $authorization = base64_encode(
        "{$processor_data['processor_params']['api_username']}:{$processor_data['processor_params']['api_password']}"
    );

    $extra = [
        'headers' => [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic ' . $authorization,
        ],
    ];

    $inst_id = $processor_data['processor_params']['inst_id'];

    if ($mode === 'return') {
        $response = Http::get(
            "{$payment_gateway_api_url}/hosted/rest/sessions/{$inst_id}/{$_REQUEST['sessionId']}/status",
            '',
            $extra
        );

        $session_status = json_decode($response, true);
        $transaction_status = $session_status['status'] !== 'FAILED'
            ? $session_status['hostedSessionStatus']['transactionState']['transactionState']
            : null;

        if ($transaction_status === 'SUCCESS') {
            $pp_response = [
                'order_status'   => 'P',
                'transaction_id' => $session_status['hostedSessionStatus']['transactionState']['id'],
            ];
            fn_finish_payment($order_id, $pp_response);
        } elseif (
            $transaction_status === 'FAILED'
            || $transaction_status === 'EXPIRED'
        ) {
            $transaction_id = $session_status['hostedSessionStatus']['transactionState']['id'];

            $transaction = Http::get(
                "{$payment_gateway_api_url}/acceptor/rest/transactions/{$inst_id}/{$transaction_id}",
                '',
                $extra
            );

            $transaction_data = json_decode($transaction, true);
            $pp_response = [
                'order_status'   => 'F',
                'transaction_id' => $transaction_id,
                'reason_text' => end($transaction_data['history'])['reasonMessage'] . '.',
            ];
            fn_finish_payment($order_id, $pp_response);
        } elseif ($transaction_status === 'CANCELLED') {
            $pp_response = [
                'order_status'   => OrderStatuses::CANCELED,
                'transaction_id' => $session_status['hostedSessionStatus']['transactionState']['id'],
            ];
            fn_finish_payment($order_id, $pp_response);
        } else {
            $pp_response = [
                'order_status'   => 'O',
                'transaction_id' => $session_status['hostedSessionStatus']['transactionState']['id'],
            ];
            fn_change_order_status($order_id, 'O');
            fn_update_order_payment_info($order_id, $pp_response);
        }
    } elseif ($mode === 'notify') {
        $order_info = fn_get_order_info($order_id);
        $transaction_id = $order_info['payment_data']['transaction_id'];

        $response = Http::get(
            "{$payment_gateway_api_url}/acceptor/rest/transactions/{$inst_id}/{$transaction_id}",
            '',
            $extra
        );

        $response_data = json_decode($response, true);
        $transaction_status = $response_data['transaction']['status'];

        $pp_response = [
            'order_status'   => $transaction_status === 'SUCCESS'
                ? 'P'
                : 'F',
            'transaction_id' => $transaction_id,
        ];

        fn_finish_payment($order_id, $pp_response);
    } elseif ($mode === 'cancel') {
        $pp_response = [
            'order_status' => OrderStatuses::INCOMPLETED,
            'reason_text'  => __('text_transaction_cancelled'),
        ];
        fn_finish_payment($order_id, $pp_response);
    }

    fn_order_placement_routines('route', $order_id);
} else {
    if (YesNo::toBool($processor_data['processor_params']['test'])) {
        $payment_gateway_api_url = "https://api.mite.pay360.com/hosted/rest/sessions/{$processor_data['processor_params']['inst_id']}";
    } else {
        $payment_gateway_api_url = "https://api.pay360.com/hosted/rest/sessions/{$processor_data['processor_params']['inst_id']}";
    }

    $authorization = base64_encode(
        "{$processor_data['processor_params']['api_username']}:{$processor_data['processor_params']['api_password']}"
    );

    $extra = [
        'headers' => [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Basic ' . $authorization,
        ],
    ];

    $notify_url = fn_url("payment_notification.notify?payment=pay360&order_id={$order_id}", AREA, 'current');
    $return_url = fn_url("payment_notification.return?payment=pay360&order_id={$order_id}", AREA, 'current');
    $cancel_url = fn_url("payment_notification.cancel?payment=pay360&order_id={$order_id}", AREA, 'current');

    $payment_request_data = [
        'transaction' => [
            'money'      => [
                'currency' => $processor_data['processor_params']['currency'],
                'amount'   => [
                    'fixed' => $order_info['total'],
                ],
            ],
            'do3DSecure' => true,
        ],
        'session'     => [
            'returnUrl'               => [
                'url' => $return_url,
            ],
            'transactionNotification' => [
                'url' => $notify_url,
            ],
            'cancelUrl'               => [
                'url' => $cancel_url,
            ],
        ],
    ];

    if (!empty($processor_data['processor_params']['date_of_birth'])) {
        $timestamp = fn_parse_date($processor_data['processor_params']['date_of_birth']);
        $payment_request_data['financialServices']['dateOfBirth'] = date('Ymd', $timestamp);
    }

    if (!empty($processor_data['processor_params']['postal_code'])) {
        $clean_postal_code = preg_replace('/[^a-zA-Z0-9]/', '', $processor_data['processor_params']['postal_code']);
        $payment_request_data['financialServices']['postCode'] = substr($clean_postal_code, 0, 6);
    }

    if (!empty($processor_data['processor_params']['surname'])) {
        $clean_surname = preg_replace('/[^a-zA-Z]/', '', $processor_data['processor_params']['surname']);
        $payment_request_data['financialServices']['surname'] = substr($clean_surname, 0, 6);
    }

    if (!empty($processor_data['processor_params']['account_number'])) {
        $payment_request_data['financialServices']['accountNumber'] = $processor_data['processor_params']['account_number'];
    }

    $response = Http::post(
        $payment_gateway_api_url . '/payments',
        json_encode($payment_request_data),
        $extra
    );

    $response_data = json_decode($response, true);
    $redirect_url = isset($response_data['redirectUrl'])
        ? $response_data['redirectUrl']
        : null;

    if ($redirect_url) {
        fn_create_payment_form($redirect_url, [], 'Pay360', true, 'GET');
    }
}
