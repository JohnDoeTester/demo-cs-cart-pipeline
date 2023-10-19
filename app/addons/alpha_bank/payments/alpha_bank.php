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

defined('BOOTSTRAP') or die('Access denied');

use Tygh\Registry;
use Tygh\Tygh;

if (defined('PAYMENT_NOTIFICATION')) {
    $pp_response = [];
    $pp_response['order_status'] = 'F';
    $pp_response['reason_text'] = __('text_transaction_declined');
    $order_id = !empty($_REQUEST['order_id']) ? (int) $_REQUEST['order_id'] : 0;

    if ($mode === 'success' && !empty($_REQUEST['order_id'])) {
        $order_info = fn_get_order_info($order_id);

        if (empty($processor_data)) {
            $processor_data = fn_get_processor_data($order_info['payment_id']);
        }

        $post_data = [];
        $post_data_values = [
            'mid',
            'orderid',
            'status',
            'orderAmount',
            'currency',
            'paymentTotal',
            'riskScore',
            'payMethod',
            'txId',
            'paymentRef'
        ];

        foreach ($post_data_values as $post_data_value) {
            if (!isset($_REQUEST[$post_data_value])) {
                continue;
            }

            $post_data[] = $_REQUEST[$post_data_value];
        }

        if ($_REQUEST['status'] === 'CAPTURED') {
            $pp_response['order_status'] = 'P';
            $pp_response['reason_text'] = __('transaction_approved');
            $pp_response['transaction_id'] = $_REQUEST['paymentRef'];
        }
    }

    if (fn_check_payment_script('alpha_bank.php', $order_id)) {
        fn_finish_payment($order_id, $pp_response);
        fn_order_placement_routines('route', $order_id);
    }
} else {
    if ($processor_data['processor_params']['mode'] === 'test') {
        $payment_url = 'https://alphaecommerce-test.cardlink.gr/vpos/shophandlermpi';
    } else {
        $payment_url = 'https://www.alphaecommerce.gr/vpos/shophandlermpi';
    }

    $amount = fn_format_price($order_info['total'], $processor_data['processor_params']['currency']);

    $current_location = rtrim(Registry::get('config.current_location'), '/');

    $confirm_url = $current_location . '/abs/' . $order_id;
    $cancel_url = $current_location . '/abf/' . $order_id;

    /** @var \Tygh\Web\Session $session */
    $session = Tygh::$app['session'];
    $confirm_url = fn_link_attach($confirm_url, $session->getName() . '=' . $session->getID());
    $cancel_url = fn_link_attach($cancel_url, $session->getName() . '=' . $session->getID());

    /** @var \Tygh\Location\Manager $location_manager */
    $location_manager = Tygh::$app['location'];

    $post_data = [
        'mid'         => $processor_data['processor_params']['merchant_id'],
        'lang'        => $processor_data['processor_params']['language'],
        'orderid'     => time() . $order_id,
        'orderDesc'   => '#' . $order_id,
        'orderAmount' => $amount,
        'currency'    => $processor_data['processor_params']['currency'],
        'payerEmail'  => $order_info['email'],
        'payerPhone'  => $location_manager->getLocationField($order_info, 'phone', '', BILLING_ADDRESS_PREFIX),
        'trType'      => '1',
        'confirmUrl'  => $confirm_url,
        'cancelUrl'   => $cancel_url,
        'billState'   => $location_manager->getLocationField($order_info, 'state', '', BILLING_ADDRESS_PREFIX),
        'billZip'     => $location_manager->getLocationField($order_info, 'zipcode', '', BILLING_ADDRESS_PREFIX),
        'billCity'    => $location_manager->getLocationField($order_info, 'city', '', BILLING_ADDRESS_PREFIX),
        'billAddress' => $location_manager->getLocationField($order_info, 'address', '', BILLING_ADDRESS_PREFIX),
        'shipCountry' => $location_manager->getLocationField($order_info, 'country', '', SHIPPING_ADDRESS_PREFIX),
        'shipState'   => $location_manager->getLocationField($order_info, 'state', '', SHIPPING_ADDRESS_PREFIX),
        'shipZip'     => $location_manager->getLocationField($order_info, 'zipcode', '', SHIPPING_ADDRESS_PREFIX),
        'shipCity'    => $location_manager->getLocationField($order_info, 'city', '', SHIPPING_ADDRESS_PREFIX),
        'shipAddress' => $location_manager->getLocationField($order_info, 'address', '', SHIPPING_ADDRESS_PREFIX),
    ];

    $post_data['digest'] = base64_encode(sha1(implode('', $post_data) . $processor_data['processor_params']['shared_secret'], true));

    fn_create_payment_form($payment_url, $post_data, 'Alpha Bank', false);
}
exit;
