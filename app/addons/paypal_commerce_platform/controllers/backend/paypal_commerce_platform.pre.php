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

use Tygh\Addons\PaypalCommercePlatform\ServiceProvider;
use Tygh\Enum\NotificationSeverity;

defined('BOOTSTRAP') or die('Access denied');

/** @var string $mode */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        $mode === 'disburse_payouts'
        && !empty($_REQUEST['order_id'])
    ) {
        $order_info = fn_get_order_info($_REQUEST['order_id']);

        if (!$order_info || !fn_paypal_commerce_platform_is_allowed_disburse_payouts_by_order_info($order_info)) {
            return [CONTROLLER_STATUS_NO_CONTENT];
        }

        $processor = ServiceProvider::getProcessorFactory()->getByPaymentId(
            $order_info['payment_id'],
            $order_info['payment_method']['processor_params']
        );

        if (!$processor) {
            return [CONTROLLER_STATUS_NO_CONTENT];
        }

        $pp_response = $processor->manuallyDisbursePayouts($order_info);

        if (isset($pp_response['paypal_commerce_platform.payout_id'])) {
            fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('paypal_commerce_platform.funds_transferred_successfully'));
        } elseif (isset($pp_response['paypal_commerce_platform.payout_failure_reason'])) {
            fn_set_notification(
                NotificationSeverity::ERROR,
                __('error'),
                __('paypal_commerce_platform.transfer_funds_error')
                    . ' '
                    . __(
                        'paypal_commerce_platform.reason_with_error_text',
                        ['[error_text]' => $pp_response['paypal_commerce_platform.payout_failure_reason']]
                    )
            );
        }

        if (isset($_REQUEST['redirect_url'])) {
            return [CONTROLLER_STATUS_REDIRECT, $_REQUEST['redirect_url']];
        } else {
            return [CONTROLLER_STATUS_REDIRECT, 'orders.details?order_id=' . $order_info['order_id']];
        }
    }

    return [CONTROLLER_STATUS_OK];
}

if (
    $mode === 'disburse_payouts_by_cron'
    && !empty($_REQUEST['days'])
    && !empty($_REQUEST['payment_id'])
    && defined('CONSOLE')
) {
    $days = (int) $_REQUEST['days'];
    $payment_id = (int) $_REQUEST['payment_id'];
    $timestamp = strtotime('-' . $days . ' days');

    $processor = ServiceProvider::getProcessorFactory()->getByPaymentId($payment_id);

    if (!$processor) {
        exit();
    }

    if (!$timestamp_from = fn_get_storage_data('paypal_commerce_platform_last_disburse_timestamp_' . $payment_id)) {
        $timestamp_from = $processor::getProcessorParameters()['created_at'];
    }

    $order_ids = db_get_fields(
        'SELECT order_id'
        . ' FROM ?:orders'
        . ' WHERE timestamp > ?i'
            . ' AND timestamp < ?i'
            . ' AND payment_id = ?i'
            . ' AND parent_order_id = 0'
        . ' ORDER BY timestamp ASC',
        $timestamp_from,
        $timestamp,
        $payment_id
    );

    foreach ($order_ids as $order_id) {
        $order_info = fn_get_order_info($order_id);

        if (!$order_info || !fn_paypal_commerce_platform_is_allowed_disburse_payouts_by_order_info($order_info)) {
            continue;
        }

        $processor->manuallyDisbursePayouts($order_info);
        $timestamp_from = $order_info['timestamp'];
    }

    fn_set_storage_data('paypal_commerce_platform_last_disburse_timestamp_' . $payment_id, $timestamp_from);

    exit();
}
