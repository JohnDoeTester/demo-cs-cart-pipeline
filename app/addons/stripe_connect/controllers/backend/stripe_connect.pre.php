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

use Tygh\Addons\StripeConnect\ServiceProvider;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\Addons\StripeConnect\AccountTypes;

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /** @var string $mode */
    if (
        $mode === 'transfer_funds'
        && !empty($_REQUEST['order_id'])
    ) {
        $order_info = fn_get_order_info($_REQUEST['order_id']);

        if (!$order_info || !fn_stripe_connect_is_allowed_transfer_funds_by_order_info($order_info)) {
            return [CONTROLLER_STATUS_NO_CONTENT];
        }
        $processor = ServiceProvider::getProcessorFactory()->getByPaymentId($order_info['payment_id']);

        try {
            /** @var \Tygh\Common\OperationResult $result */
            $result = $processor->manuallyTransferFunds($order_info);
            $processor->updatePaymentsDescriptions($order_info);

            $result->showNotifications();
            $result->getData() && fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('stripe_connect.funds_transferred_successfully'));
        } catch (Exception $e) {
            fn_set_notification(
                NotificationSeverity::ERROR,
                __('error'),
                __('stripe_connect.transfer_funds_error')
                . ' '
                . __(
                    'stripe_connect.reason_with_error_text',
                    ['[error_text]' => $e->getMessage()]
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
    $mode === 'transfer_funds_by_cron'
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

    if (!$timestamp_from = fn_get_storage_data('stripe_connect_last_transfer_timestamp_' . $payment_id)) {
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

        if (!$order_info || !fn_stripe_connect_is_allowed_transfer_funds_by_order_info($order_info)) {
            continue;
        }

        try {
            $processor->manuallyTransferFunds($order_info);

            $timestamp_from = $order_info['timestamp'];
        } catch (Exception $e) {
        }
    }

    fn_set_storage_data('stripe_connect_last_transfer_timestamp_' . $payment_id, $timestamp_from);

    exit();
}

if ($mode === 'check_accounts' && defined('CONSOLE')) {
    $account_helper = ServiceProvider::getAccountHelper();
    $offset = 0;
    $limit  = 1000;

    while (true) {
        $express_accounts = db_get_hash_single_array(
            'SELECT stripe_connect_account_id, company_id'
            . ' FROM ?:companies'
            . ' WHERE stripe_connect_account_type = ?s AND stripe_connect_account_id <> \'\''
            . ' LIMIT ?i OFFSET ?i',
            ['company_id', 'stripe_connect_account_id'],
            AccountTypes::EXPRESS,
            $limit,
            $offset
        );

        if (empty($express_accounts)) {
            break;
        }

        foreach ($express_accounts as $company_id => $account_id) {
            $result = $account_helper->retrieveAccount($account_id);

            if ($result->isSuccess()) {
                /** @var \Stripe\Account $account */
                $account = $result->getData();

                if ($account_helper->isAccountRejected($account)) {
                    $account_helper->disconnectAccount($company_id);
                }
            }
        }

        $offset += $limit;
    }

    exit();
}
