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
use Tygh\Registry;
use Tygh\VendorPayouts;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

include_once (Registry::get('config.dir.addons') . 'paypal_adaptive/payments/paypal_adaptive_func.php');

if (defined('PAYMENT_NOTIFICATION')) {

    if (empty($_REQUEST['order_ids'])) { // internal error
        $action = (AREA == 'A') ? 'orders.manage' : '';
        fn_redirect(fn_url($action));
    }

    $order_ids = explode(',', $_REQUEST['order_ids']);
    $parent_order_id = fn_paypal_get_parent_order_id($order_ids);
    $payment_id = !empty($_REQUEST['payment_id']) ? $_REQUEST['payment_id'] : '';
    $processor_data = fn_paypal_adaptive_get_processor_data($payment_id);

    if ($mode == 'return') {

        $pay_key = $_REQUEST['payKey'];

        if (empty($pay_key)) {
            $action = (AREA == 'A') ? 'orders.manage' : '';
            fn_redirect(fn_url($action));
        }

        $payment_details = fn_paypal_adaptive_payment_details($pay_key, $processor_data);
        $queue_orders = fn_paypal_get_queue($parent_order_id);

        if (!empty($queue_orders) && !fn_paypal_adaptive_any_paid($queue_orders)) {
            $pp_response['order_status'] = $processor_data['processor_params']['statuses']['pending_payment'];
            $pp_response['reason_text'] = '';

            fn_finish_payment($parent_order_id, $pp_response);
            fn_clear_cart(Tygh::$app['session']['cart']);
        }

        if (isset($processor_data['processor_params']['in_context'])
            && $processor_data['processor_params']['in_context'] == 'Y'
            && AREA == 'C'
        ) {
            fn_paypal_adaptive_start_payments($order_ids);
        }

        foreach($order_ids as $order_index => $order_id) {

            $order_info = fn_get_order_info($order_id);

            // check against transaction_id to prevent double processing
            if (!empty($order_info['payment_info']['transaction_id'])) {
                continue;
            }

            // the last item in the paymentInfoList contains the admin fee
            $create_withdrawal =
                isset($payment_details["paymentInfoList_paymentInfo({$order_index})_receiver_email"])
                && $payment_details["paymentInfoList_paymentInfo({$order_index})_receiver_email"] != $processor_data['processor_params']['primary_email'];

            if (fn_paypal_adaptive_check_payment_complete($payment_details, $order_index)) {
                $pp_response['order_status'] = 'P';
                $pp_response['reason_text'] = 'Success';

                $create_withdrawal = $create_withdrawal && true;

            } elseif (fn_paypal_adaptive_check_payment_pending($payment_details, $order_index)) {
                $pp_response['order_status'] = 'O';
                $pp_response['reason_text'] = 'Status transaction: PENDING';

                if (isset($payment_details["paymentInfoList_paymentInfo($order_index)_pendingReason"])) {
                    $pp_response['reason_text'] .= '; Reason: ' . $payment_details["paymentInfoList_paymentInfo($order_index)_pendingReason"];
                }

                $create_withdrawal = $create_withdrawal && true;

            } else {
                $pp_response['order_status'] = 'F';
                $pp_response['reason_text'] = !empty($payment_details['error(0)_message']) ? $payment_details['error(0)_message'] : 'Failed';
            }

            $pp_response['transaction_id'] = isset($payment_details["paymentInfoList_paymentInfo($order_index)_senderTransactionId"]) ? $payment_details["paymentInfoList_paymentInfo($order_index)_senderTransactionId"] : '';

            if ($create_withdrawal) {
                $payouts_manager = VendorPayouts::instance(array('vendor' => $order_info['company_id']));

                $payouts_manager->update(array(
                    'company_id' => $order_info['company_id'],
                    'payout_type' => VendorPayoutTypes::WITHDRAWAL,
                    'approval_status' => VendorPayoutApprovalStatuses::COMPLETED,
                    'payout_amount' => $payment_details["paymentInfoList_paymentInfo($order_index)_receiver_amount"],
                    'comments' => __('addons.paypal_adaptive.withdrawal_for_the_order', array(
                        '[order_id]' => $order_id,
                    )),
                ));

                if (Registry::get('addons.paypal_adaptive.collect_payouts') == 'Y') {
                    $pending_payouts = $payouts_manager->getSimple(array(
                        'payout_type' => VendorPayoutTypes::PAYOUT,
                        'approval_status' => VendorPayoutApprovalStatuses::PENDING
                    ));

                    foreach ($pending_payouts as $payout_data) {
                        $payouts_manager->update(array(
                            'approval_status' => VendorPayoutApprovalStatuses::COMPLETED
                        ), $payout_data['payout_id']);
                    }
                }
            }

            fn_finish_payment($order_id, $pp_response);
            fn_paypal_adaptive_set_status($order_id, PAID_PAYMENT_ORDERS);
        }

        fn_paypal_adaptive_next($order_ids);
        
    } elseif ($mode == 'cancel') { // Completely cancels operation
        fn_paypal_clear_queue($parent_order_id);

        $pp_response['order_status'] = 'N';
        $pp_response['reason_text'] = __('text_transaction_cancelled');
        fn_finish_payment($parent_order_id, $pp_response);

        fn_order_placement_routines('route', $parent_order_id);

    } elseif ($mode == 'skip') {

        $pp_response['order_status'] = 'F';
        $pp_response['reason_text'] = __('text_transaction_cancelled');

        foreach($order_ids as $order_id) {
            fn_finish_payment($order_id, $pp_response);
        }

        fn_paypal_adaptive_next($order_ids);

    } elseif ($mode == 'pay') {
        fn_paypal_adaptive_start_payments($order_ids);
        fn_paypal_adaptive_request($order_ids, $processor_data);
    }

} else { // First running the payment script

    if ($order_info['is_parent_order'] == 'Y') {

        $child_orders = fn_paypal_adaptive_get_childs_orders($order_id);

        if (count($child_orders) > MAX_AMOUNT_VENDORS_IN_ORDER) {

            $params = array(
                'order_id' => $order_id,
                'max_amount_vendors_in_order' => MAX_AMOUNT_VENDORS_IN_ORDER
            );

            fn_paypal_create_queue($params);

            $action = "paypal_adaptive.queue?order_id=$order_id";

            fn_redirect(fn_url($action));
        } else {
            fn_redirect("payment_notification.pay?payment=paypal_adaptive&order_ids=" . implode(',', $child_orders));
        }
    }

    fn_paypal_adaptive_request(array($order_id), $processor_data);
}