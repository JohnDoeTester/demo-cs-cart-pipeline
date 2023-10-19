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

use Tygh\Addons\Stripe\Payments\Stripe;
use Tygh\Enum\Addons\Stripe\PaymentTypes;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\OrderStatuses;

/** @var array $order_info */
/** @var array $processor_data */

if (defined('AJAX_REQUEST')) {
    //phpcs:ignore
    $pp_response = [
        'order_status' => OrderStatuses::INCOMPLETED,
    ];
}

if (defined('PAYMENT_NOTIFICATION')) {
    if (empty($_REQUEST['order_id'])) {
        exit;
    }

    if ($mode === 'cancel') {
        fn_change_order_status($_REQUEST['order_id'], OrderStatuses::INCOMPLETED);
    }

    $order_info = fn_get_order_info($_REQUEST['order_id']);
    if (!$order_info) {
        fn_set_notification(NotificationSeverity::ERROR, __('error'), __('stripe_connect.order_not_found'));
    }

    if (
        $mode === 'success'
        && !in_array($order_info['status'], fn_get_settled_order_statuses())
    ) {
        fn_change_order_status($order_info['order_id'], OrderStatuses::OPEN);
    }
    fn_order_placement_routines('route', $_REQUEST['order_id']);
}

if (!empty($order_info['payment_info']['stripe.payment_intent_id'])) {
    $processor = new Stripe(
        $order_info['payment_id'],
        Tygh::$app['db'],
        Tygh::$app['addons.stripe.price_formatter'],
        $processor_data['processor_params']
    );

    $pp_response = $processor->charge($order_info);
} elseif (
    !empty($order_info)
    && !empty($processor_data['processor_params']['payment_type'])
    && PaymentTypes::isStripeCheckoutEnabled($processor_data['processor_params']['payment_type'])
) {
    $processor = new Stripe(
        $order_info['payment_id'],
        Tygh::$app['db'],
        Tygh::$app['addons.stripe.price_formatter'],
        $processor_data['processor_params']
    );

    $session = $processor->createCheckoutSession($order_info);

    if (empty($session)) {
        fn_set_notification(NotificationSeverity::ERROR, __('error'), __('stripe.session_checkout_error'));
        fn_redirect('checkout.checkout');
    }

    if (!empty($session->payment_intent)) {
        fn_update_order_payment_info(
            $order_info['order_id'],
            ['stripe.payment_intent_id' => $session->payment_intent]
        );
    }

    if (!empty($session->url)) {
        fn_create_payment_form($session->url, [], 'Stripe Checkout', true, 'get');
    }
}
