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

namespace Tygh\Addons\StripeConnect\Webhook;

use Tygh;
use Tygh\Addons\StripeConnect\Payments\StripeConnect;
use Tygh\Enum\SiteArea;
use Stripe\Stripe;
use Stripe\Event;
use Stripe\WebhookEndpoint;
use Tygh\Registry;

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
     * Registers webhook on the Stripe Connect side
     *
     * @param string                            $id     Webhook id from schema.
     * @param array<string, bool|array<string>> $params Webhook params like 'enabled_events'.
     *
     * @return void
     *
     * @throws \Stripe\Exception\ApiErrorException Stripe exception.
     */
    public static function register($id, array $params)
    {
        $default_params = [
            'description' => 'This webhook is created automatically. Please do not delete it.',
            'api_version' => StripeConnect::API_VERSION,
        ];

        $url = fn_url('', SiteArea::STOREFRONT);
        if (!empty(Registry::get('config.customer_index'))) {
            $url = str_replace(Registry::get('config.customer_index'), '', $url);
        }
        $default_params['url'] = $url . 'stripe-connect/webhook?id=' . $id;

        $params = array_merge($default_params, $params);

        $endpoint = WebhookEndpoint::create($params);

        fn_set_storage_data('stripe_connect_webhook_' . $id, $endpoint->id);
        fn_set_storage_data('stripe_connect_webhook_' . $id . '_secret', $endpoint->secret);
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
     * Gets webhook secret key
     *
     * @param string $id Webhook id from schema.
     *
     * @return string
     */
    public static function getSecretKey($id)
    {
        return (string) fn_get_storage_data('stripe_connect_webhook_' . $id . '_secret');
    }

    /**
     * Gets webhook ID
     *
     * @param string $id Webhook id from schema.
     *
     * @return string
     */
    public static function getId($id)
    {
        return (string) fn_get_storage_data('stripe_connect_webhook_' . $id);
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
        Stripe::setApiKey($api_key);
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
        $handler_key = 'addons.stripe_connect.webhook_handler.' . $event_type;

        if (isset(Tygh::$app[$handler_key])) {
            return Tygh::$app[$handler_key];
        }

        return false;
    }
}
