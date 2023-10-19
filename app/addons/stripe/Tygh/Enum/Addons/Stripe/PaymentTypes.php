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

namespace Tygh\Enum\Addons\Stripe;

class PaymentTypes
{
    const CARD = 'card';
    const APPLE_PAY = 'apple_pay';
    const GOOGLE_PAY = 'google_pay';
    const STRIPE_CHECKOUT = 'stripe_checkout';

    /**
     * Checks if Card enabled.
     *
     * @param string $payment_type Payment type
     *
     * @return bool
     */
    public static function isCardEnabled($payment_type)
    {
        return $payment_type === self::CARD;
    }

    /**
     * Checks if Apple Pay enabled.
     *
     * @param string $payment_type Payment type
     *
     * @return bool
     */
    public static function isApplePayEnabled($payment_type)
    {
        return $payment_type === self::APPLE_PAY;
    }

    /**
     * Checks if Google Pay enabled.
     *
     * @param string $payment_type Payment type
     *
     * @return bool
     */
    public static function isGooglePayEnabled($payment_type)
    {
        return $payment_type === self::GOOGLE_PAY;
    }

    /**
     * Checks if Stripe Checkout enabled.
     *
     * @param string $payment_type Payment type
     *
     * @return bool
     */
    public static function isStripeCheckoutEnabled($payment_type)
    {
        return $payment_type === self::STRIPE_CHECKOUT;
    }
}
