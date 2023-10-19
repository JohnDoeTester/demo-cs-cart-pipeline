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

use Tygh\Addons\PaypalCommercePlatform\Enum\ProductType;

defined('BOOTSTRAP') or die('Access denied');

/**
 * Describes which product should be used when connecting a vendor from the specific country.
 *
 * @see https://developer.paypal.com/docs/platforms/checkout/reference/country-availability-advanced-cards/
 * @see https://developer.paypal.com/docs/platforms/seller-onboarding/before-payment/#modify-the-code
 */
return [
    // Regions that support advanced credit and debit card payments will use PayPal Complete Payments
    'AU'        => ProductType::PAYPAL_COMPLETE_PAYMENTS,
    'CA'        => ProductType::PAYPAL_COMPLETE_PAYMENTS,
    'FR'        => ProductType::PAYPAL_COMPLETE_PAYMENTS,
    'IT'        => ProductType::PAYPAL_COMPLETE_PAYMENTS,
    'ES'        => ProductType::PAYPAL_COMPLETE_PAYMENTS,
    'GB'        => ProductType::PAYPAL_COMPLETE_PAYMENTS,
    'US'        => ProductType::PAYPAL_COMPLETE_PAYMENTS,
    // All other regions will use Express Checkout payments
    '__default' => ProductType::EXPRESS_CHECKOUT,
];
