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

namespace Tygh\Addons\PaypalCommercePlatform\Webhook;

use Tygh\Addons\PaypalCommercePlatform\Enum\CaptureStatus;
use Tygh\Addons\PaypalCommercePlatform\Payments\PaypalCommercePlatform;
use Tygh\Addons\PaypalCommercePlatform\PayoutsManager;
use Tygh\Enum\YesNo;

/**
 * Class PaymentCaptureCompletedEvent implements PaymentCaptureCompleted webhook event.
 *
 * @package Tygh\Addons\PaypalCommercePlatform\Webhook
 */
class PaymentFinishedEvent extends Event implements PaymentCaptureEventInterface
{
    /**
     * @var \Tygh\Addons\PaypalCommercePlatform\Webhook\PaymentCapture
     */
    protected $capture;

    /** @inheritDoc */
    public function getCapture()
    {
        if ($this->capture === null) {
            $platform_fee = isset($this->getResource()->seller_receivable_breakdown->platform_fees[0]->amount->value)
                ? $this->getResource()->seller_receivable_breakdown->platform_fees[0]->amount->value
                : 0;
            $this->capture = new PaymentCapture(
                $this->getResource()->id,
                $this->getResource()->status,
                $this->getResource()->custom_id,
                $this->getResource()->amount->value,
                $platform_fee
            );
        }

        return $this->capture;
    }

    /** @inheritDoc */
    public function handle(PaypalCommercePlatform $processor)
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

        $pp_response['paypal_commerce_platform.capture_id'] = $capture->getCaptureId();

        $processor_params = $processor::getProcessorParameters();
        if (
            YesNo::isFalse($processor_params['delay_disburse_of_payouts'])
            && $capture->getCompanyId()
        ) {
            $payouts_manager = new PayoutsManager((int) $capture->getCompanyId());
            $payouts_manager->createWithdrawal($capture->getWithdrawalAmount(), $capture->getOrderId());
        }

        return $pp_response;
    }

    /** @inheritDoc */
    public function isProcessed()
    {
        $order_info = $this->getCapture()->getOrderInfo();

        return !empty($order_info['payment_info']['paypal_commerce_platform.capture_id']);
    }
}
