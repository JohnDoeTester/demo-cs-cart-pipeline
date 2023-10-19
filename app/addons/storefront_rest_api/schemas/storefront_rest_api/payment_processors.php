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

use Tygh\Enum\Addons\StorefrontRestApi\PaymentTypes;
use Tygh\Enum\ObjectStatuses;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

/**
 * This schema describes payment processors that can be used to perform the order settlement via Storefront REST API.
 *
 * Structure:
 *
 * [
 *     payment_processor_script => [
 *       'type'  => Payment type.
 *                  @see \Tygh\Enum\Addons\StorefrontRestApi\PaymentTypes
 *       'class' => FQDN of the class to perform payment.
 *                  Must implement \Tygh\Addons\StorefrontRestApi\Payments\IRedirectionPayment or
 *                  \Tygh\Addons\StorefrontRestApi\Payments\IDirectPayment interface
 *     ]
 * ]
 */
$schema = [];

$addons = Registry::get('addons');

if (isset($addons['paypal']['status']) && $addons['paypal']['status'] === ObjectStatuses::ACTIVE) {
    $schema['paypal_express.php'] = [
        'type'  => PaymentTypes::REDIRECTION,
        'class' => '\Tygh\Addons\StorefrontRestApi\Payments\PaypalExpress',
    ];
}

if (isset($addons['rus_payments']['status']) && $addons['rus_payments']['status'] === ObjectStatuses::ACTIVE) {
    $schema['yandex_money.php'] = [
        'type'  => PaymentTypes::REDIRECTION,
        'class' => '\Tygh\Addons\StorefrontRestApi\Payments\YandexCheckpoint',
    ];
}

if (isset($addons['yandex_checkout']['status']) && $addons['yandex_checkout']['status'] === ObjectStatuses::ACTIVE) {
    $schema['yandex_checkout_for_marketplaces.php'] = [
        'type'  => PaymentTypes::REDIRECTION,
        'class' => '\Tygh\Addons\StorefrontRestApi\Payments\YandexCheckoutForMarketplaces',
    ];

    $schema['yandex_checkout.php'] = [
        'type'  => PaymentTypes::REDIRECTION,
        'class' => '\Tygh\Addons\StorefrontRestApi\Payments\YandexCheckout',
    ];
}

if (isset($addons['stripe']['status']) && $addons['stripe']['status'] === ObjectStatuses::ACTIVE) {
    $schema['stripe.php'] = [
        'type'  => PaymentTypes::DIRECT,
        'class' => '\Tygh\Addons\StorefrontRestApi\Payments\Stripe',
    ];
}

if (isset($addons['stripe_connect']['status']) && $addons['stripe_connect']['status'] === ObjectStatuses::ACTIVE) {
    $schema['stripe_connect.php'] = [
        'type'  => PaymentTypes::DIRECT,
        'class' => '\Tygh\Addons\StorefrontRestApi\Payments\StripeConnect',
    ];
}

return $schema;
