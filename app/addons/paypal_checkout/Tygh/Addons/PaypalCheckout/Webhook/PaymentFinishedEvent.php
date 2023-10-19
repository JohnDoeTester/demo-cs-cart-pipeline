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

use Tygh\Addons\PaypalCheckout\Enum\CaptureStatus;
use Tygh\Addons\PaypalCheckout\Payments\PaypalCheckout;

/**
 * Class PaymentCaptureCompletedEvent implements PaymentCaptureCompleted webhook event.
 *
 * @package Tygh\Addons\PaypalCheckout\Webhook
 */
class PaymentFinishedEvent extends Event implements PaymentCaptureEventInterface
{
    /**
     * @var \Tygh\Addons\PaypalCheckout\Webhook\PaymentCapture
     */
    protected $capture;

    /** @inheritDoc */
    public function getCapture()
    {
        if ($this->capture === null) {
            $this->capture = new PaymentCapture(
                $this->getResource()->id,
                $this->getResource()->status,
                $this->getResource()->custom_id,
                $this->getResource()->amount->value
            );
        }

        return $this->capture;
    }

    /** @inheritDoc */
    public function handle(PaypalCheckout $processor)
    {
        $capture = $this->getCapture();
        $capture_status = $capture->getCaptureStatus();

        $pp_response = [
            'reason_text'  => $this->getSummary(),
            'order_status' => (string) $processor->getOrderStatusByCaptureStatus($capture_status),
        ];

        if ($capture_status !== CaptureStatus::COMPLETED) {
            return $pp_response;
        }

        $pp_response['paypal_checkout.capture_id'] = $capture->getCaptureId();

        return $pp_response;
    }

    /** @inheritDoc */
    public function isProcessed()
    {
        $order_info = $this->getCapture()->getOrderInfo();

        return !empty($order_info['payment_info']['paypal_checkout.capture_id']);
    }
}
