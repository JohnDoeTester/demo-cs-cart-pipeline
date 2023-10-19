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

namespace Tygh\Addons\Stripe\Webhook\Handlers;

use Exception;
use Stripe\PaymentIntent;
use Stripe\Stripe as StripeSetup;
use Tygh\Addons\Stripe\Payments\Stripe;
use Tygh\Addons\Stripe\Webhook\Handler;
use Stripe\Event;
use Tygh\Enum\OrderStatuses;
use Tygh\Registry;

class CheckoutSessionAsyncPaymentSucceeded implements Handler
{
    /**
     * Handles the checkout.session.async_payment_succeeded event
     *
     * @param Event $event Stripe event
     *
     * @return void
     */
    public function handle(Event $event)
    {
        StripeSetup::setApiKey(Stripe::getProcessorParameters()['secret_key']);

        /** @var \Stripe\Checkout\Session $session */
        $session = $event->data->object;

        try {
            $payment_intent = PaymentIntent::retrieve((string) $session->payment_intent);

            if (
                empty($payment_intent->metadata['order_id'])
                || empty($payment_intent->metadata['payment_type'])
                || $payment_intent->metadata['payment_type'] !== 'stripe'
            ) {
                return;
            }

            if (fn_allowed_for('ULTIMATE')) {
                Registry::set('runtime.company_id', false);
            }

            /** @var array $order_info */
            $order_info = fn_get_order_info((int) $payment_intent->metadata['order_id']);

            if (
                $order_info
                && !in_array($order_info['status'], fn_get_settled_order_statuses())
            ) {
                fn_change_order_status((int) $payment_intent->metadata['order_id'], OrderStatuses::PAID);
            }
        } catch (Exception $e) {
            fn_log_event('general', 'runtime', [
                'message' => __('stripe.webhook_handle_error', [
                    '[error]' => $e->getMessage(),
                ]),
            ]);
        }
    }
}
