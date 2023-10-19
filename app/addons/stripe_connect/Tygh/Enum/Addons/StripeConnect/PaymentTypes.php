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

namespace Tygh\Enum\Addons\StripeConnect;

class PaymentTypes
{
    const CARD = 'card';
    const CARD_SIMPLE = 'card_simple';

    /**
     * Checks if 3-D Secure enabled.
     *
     * @param string $payment_type Payment type
     *
     * @return bool
     */
    public static function is3DSecureEnabled($payment_type)
    {
        return $payment_type === self::CARD;
    }
}
