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

namespace Tygh\Addons\Stripe\Webhook;

use Tygh;
use Tygh\Addons\Stripe\Payments\Stripe;
use Tygh\Enum\SiteArea;
use Stripe\Stripe as StripeSetup;
use Stripe\Event;
use Stripe\WebhookEndpoint;

final class StripeWebhook
{
    /**
     * Creates webhook event handler
     *
     * @param Event $event Event
     *
     * @return void
     */
    public static function handle(Event $event)
    {
        /** @var Handler|false $handler */
        $handler = self::getHandler($event->type);

        if (!$handler) {
            return;
        }

        $handler->handle($event);
    }

    /**
     * Registers webhook on the Stripe side
     *
     * @param int $payment_id Payment ID
     *
     * @return WebhookEndpoint
     *
     * @throws \Stripe\Exception\ApiErrorException Stripe exception.
     */
    public static function register($payment_id)
    {
        return WebhookEndpoint::create([
            'url'            => fn_url('', SiteArea::STOREFRONT) . 'stripe/webhook/' . $payment_id,
            'enabled_events' => [
                'payment_intent.succeeded',
                'checkout.session.async_payment_succeeded'
            ],
            'description'    => 'This webhook is created automatically. Please do not delete it.',
            'api_version'    => Stripe::API_VERSION,
            'metadata'       => [
                'payment_id' => $payment_id,
            ],
        ]);
    }

    /**
     * Retrieves webhook data
     *
     * @param string $id Webhook ID
     *
     * @return WebhookEndpoint
     *
     * @throws \Stripe\Exception\ApiErrorException Stripe exception.
     */
    public static function retrieve($id)
    {
        return WebhookEndpoint::retrieve($id);
    }

    /**
     * Sets config
     *
     * @param string $api_key Api key
     *
     * @return void
     */
    public static function setConfig($api_key)
    {
        StripeSetup::setApiKey($api_key);
    }

    /**
     * Converts event type to class name
     *
     * @param string $event_type Event type
     *
     * @return Handler|false
     */
    private static function getHandler($event_type)
    {
        $handler_key = 'addons.stripe.webhook_handler.' . $event_type;

        if (isset(Tygh::$app[$handler_key])) {
            return Tygh::$app[$handler_key];
        }

        return false;
    }
}
