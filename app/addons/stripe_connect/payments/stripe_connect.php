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

use Tygh\Addons\StripeConnect\ServiceProvider;
use Tygh\Enum\Addons\StripeConnect\PaymentTypes;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\OrderStatuses;
use Tygh\Enum\YesNo;

/** @var array $order_info */
/** @var array $processor_data */

if (defined('PAYMENT_NOTIFICATION') && !empty($_REQUEST['order_id'])) {

    $order_info = fn_get_order_info($_REQUEST['order_id']);
    if (!$order_info) {
        fn_set_notification(NotificationSeverity::ERROR, __('error'), __('stripe_connect.order_not_found'));
    }

    if ($mode === 'cancel') {
        fn_change_order_status($_REQUEST['order_id'], OrderStatuses::INCOMPLETED);
    }

    if (
        $mode === 'cancel'
        || !$order_info
    ) {
        fn_order_placement_routines('route', $_REQUEST['order_id']);
    }

    $processor_data = [];
    $processor_data['processor_params'] = null;
}

if (!empty($order_info['payment_info']['stripe_connect.payment_intent_id'])) {
    Tygh::$app['session']['stripe_connect_order_id'] = $order_info['order_id'];

    fn_order_placement_routines('route', $_REQUEST['order_id']);
} elseif (!empty($order_info['payment_info']['stripe_connect.token'])) {
    $processor = ServiceProvider::getProcessorFactory()->getByPaymentId(
        $order_info['payment_id'],
        $processor_data['processor_params']
    );

    // phpcs:ignore
    $pp_response = $processor->chargeWithout3DSecure($order_info);
} elseif (
    !empty($order_info)
    && !empty($processor_data['processor_params']['is_checkout_enabled'])
    && $processor_data['processor_params']['is_checkout_enabled'] === YesNo::YES
    && !empty($processor_data['processor_params']['payment_type'])
    && $processor_data['processor_params']['payment_type'] === PaymentTypes::CARD
) {
    $processor = ServiceProvider::getProcessorFactory()->getByPaymentId(
        $order_info['payment_id'],
        $processor_data['processor_params']
    );

    $session = $processor->createCheckoutSession($order_info);

    if (empty($session)) {
        fn_set_notification(NotificationSeverity::ERROR, __('error'), __('stripe_connect.session_checkout_error'));
        fn_redirect('checkout.checkout');
    }

    fn_change_order_status($order_info['order_id'], OrderStatuses::OPEN);
    if (!empty($session->payment_intent)) {
        fn_update_order_payment_info(
            $order_info['order_id'],
            ['stripe_connect.payment_intent_id' => $session->payment_intent]
        );
    }

    if (!empty($session->url)) {
        fn_create_payment_form($session->url, [], 'Stripe Connect Checkout', true, 'get');
    }
} elseif (defined('AJAX_REQUEST')) {
    //phpcs:ignore
    $pp_response = [
        'order_status' => OrderStatuses::OPEN,
    ];
}

if (
    defined('PAYMENT_NOTIFICATION')
    && !empty($_REQUEST['order_id'])
    && $mode === 'success'
) {
    fn_order_placement_routines('route', $_REQUEST['order_id']);
}
