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

namespace Tygh\Addons\PaypalCheckout\Webhook;

use Tygh\Addons\PaypalCheckout\Payments\PaypalCheckout;

interface PaymentCaptureEventInterface
{
    /**
     * @return \Tygh\Addons\PaypalCheckout\Webhook\PaymentCapture
     */
    public function getCapture();

    /**
     * @param \Tygh\Addons\PaypalCheckout\Payments\PaypalCheckout $processor Processor to perform
     *                                                                                       payment transactions
     *
     * @return array<string, string>
     */
    public function handle(PaypalCheckout $processor);
}
