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

namespace Tygh\Addons\PaypalCommercePlatform\Enum;

class WebhookEventType
{
    const PAYMENT_CAPTURE_COMPLETED = 'PAYMENT.CAPTURE.COMPLETED';
    const PAYMENT_CAPTURE_PENDING = 'PAYMENT.CAPTURE.PENDING';
    const PAYMENT_CAPTURE_DENIED = 'PAYMENT.CAPTURE.DENIED';
    const PAYMENT_CAPTURE_REFUNDED = 'PAYMENT.CAPTURE.REFUNDED';

    /**
     * Gets webhook event types that relate to the payment capture.
     *
     * @return array<string>
     */
    public static function getCaptureTypes()
    {
        return [
            self::PAYMENT_CAPTURE_COMPLETED,
            self::PAYMENT_CAPTURE_PENDING,
            self::PAYMENT_CAPTURE_DENIED,
            self::PAYMENT_CAPTURE_REFUNDED
        ];
    }
}
