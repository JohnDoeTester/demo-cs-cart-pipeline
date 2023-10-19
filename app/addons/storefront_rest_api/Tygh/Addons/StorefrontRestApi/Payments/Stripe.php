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

namespace Tygh\Addons\StorefrontRestApi\Payments;

use Exception;
use Tygh\Common\OperationResult;
use Tygh\Enum\OrderStatuses;
use Tygh\Tygh;
use Tygh\Addons\Stripe\Payments\Stripe as StripePayment;

class Stripe implements IDirectPayment, IConfigurablePayment
{
    /** @var array<string|array> $order_info */
    protected $order_info;

    /** @var array<string|array> $auth_info */
    protected $auth_info;

    /** @var array<string|array> $payment_info */
    protected $payment_info;

    /** @inheritDoc */
    public function pay(array $request)
    {
        $result = new OperationResult(false);

        if (!isset($request['payment_method_id'])) {
            $result->setErrors([
                __('api_required_field', [
                    '[field]' => 'payment_method_id',
                ])
            ]);

            return $result;
        }

        $order_id = (int) $this->order_info['order_id'];
        $payment_id = (int) $this->payment_info['payment_id'];
        $total = (float) $this->order_info['total'];
        $payment_name = $this->payment_info['payment'];

        $processor = new StripePayment(
            $payment_id,
            Tygh::$app['db'],
            Tygh::$app['addons.stripe.price_formatter']
        );

        try {
            $result = $processor->getPaymentConfirmationDetails($request['payment_method_id'], $total);
        } catch (Exception $e) {
            $result->setErrors([
                __('stripe.payment_intent_error', [
                    '[payment_id]' => $payment_name,
                    '[error]' => $e->getMessage(),
                ])
            ]);
        }

        if ($result->isSuccess()) {
            fn_change_order_status($order_id, OrderStatuses::PAID);

            fn_update_order_payment_info($order_id, [
                'transaction_id' => $result->getData('payment_intent_id'),
                'order_status'   => OrderStatuses::PAID
            ]);
        }

        return $result;
    }

    /** @inheritDoc */
    public function setOrderInfo(array $order_info)
    {
        $this->order_info = $order_info;

        return $this;
    }

    /** @inheritDoc */
    public function setAuthInfo(array $auth_info)
    {
        $this->auth_info = $auth_info;

        return $this;
    }

    /** @inheritDoc */
    public function setPaymentInfo(array $payment_info)
    {
        $this->payment_info = $payment_info;

        return $this;
    }

    /** @inheritDoc */
    public function getProcessorParameters(array $payment_method)
    {
        return [
            'publishable_key'     => $payment_method['processor_params']['publishable_key'],
            'merchant_identifier' => $payment_method['processor_params']['merchant_identifier'],
            'country'             => $payment_method['processor_params']['country'],
            'currency'            => $payment_method['processor_params']['currency'],
            'is_stripe'           => $payment_method['processor_params']['is_stripe'],
        ];
    }
}
