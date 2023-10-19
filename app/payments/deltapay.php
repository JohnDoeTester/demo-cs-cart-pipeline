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

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

if (defined('PAYMENT_NOTIFICATION')) {

    /** @var string $mode */

    if ($mode == 'process') {

        $_REQUEST = array_merge(array(
            'Param1' => '',
            'Param2' => '',
        ), $_REQUEST);

        list($order_id,) = explode('_', $_REQUEST['Param1']);

        $is_request_valid = $_REQUEST['Param2'];
        if ($is_request_valid) {
            $is_request_valid = fn_get_ekeys(array(
                'object_id'   => $order_id,
                'object_type' => 'V',
                'ekey'        => $_REQUEST['Param2'],
            ));
        }

        if (!fn_check_payment_script('deltapay.php', $order_id)
            || !$is_request_valid
        ) {
            die('Access denied');
        }

        $pp_response = array(
            'order_status' => OrderStatuses::INCOMPLETED,
            'reason_text'  => '',
        );

        switch ($_REQUEST['Result']) {
            case 1:
                $pp_response['order_status'] = 'P';
                break;
            case 2:
                $pp_response['order_status'] = 'F';
                $pp_response['reason_text'] = isset($_REQUEST['ErrorMessage'])
                    ? $_REQUEST['ErrorMessage']
                    : __('error');
                break;
            case 3:
                $pp_response['order_status'] = 'I';
                $pp_response['reason_text'] = __('cancelled');
                break;
        }

        if (isset($_REQUEST['DeltaPayId'])) {
            $pp_response['transaction_id'] = $_REQUEST['DeltaPayId'];
        }

        db_query('DELETE FROM ?:ekeys WHERE ekey = ?s', $_REQUEST['Param2']);
        fn_finish_payment($order_id, $pp_response);
        fn_order_placement_routines('route', $order_id);
    }
} else {

    /** @var array $processor_data */
    /** @var array $order_info */
    /** @var int $order_id */

    $order_no = $order_id;
    if ($order_info['repaid']) {
        $order_no .= '_' . $order_info['repaid'];
    }

    $currency_code = $processor_data['processor_params']['currency'];
    if ($currency_code != CART_SECONDARY_CURRENCY) {
        $order_info['total'] = fn_format_price_by_currency(
            $order_info['total'],
            CART_SECONDARY_CURRENCY,
            $currency_code
        );
    }

    $amount = str_replace('.', ',', $order_info['total']);

    $validation = fn_generate_ekey($order_id, 'V', SECONDS_IN_HOUR);

    $submit_url = 'https://www.deltapay.gr/entry.asp';
    $post_data = array(
        'MerchantCode'    => $processor_data['processor_params']['merchant_id'],
        'Param1'          => $order_no,
        'Param2'          => $validation,
        'Charge'          => $amount,
        'CurrencyCode'    => $currency_code,
        'TransactionType' => 1,
        'Installments'    => 0,
        'CardHolderEmail' => $order_info['email'],
    );

    fn_create_payment_form($submit_url, $post_data, 'DeltaPay');
}
