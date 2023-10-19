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
use Tygh\Addons\StripeConnect\ServiceProvider;
use Tygh\Common\OperationResult;
use Tygh\Enum\OrderStatuses;

class StripeConnect implements IDirectPayment, IConfigurablePayment
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
        $confirmation_result = new OperationResult(false);

        if (!isset($request['payment_method_id']) && !isset($request['token'])) {
            $result->setErrors([
                __('api_required_field', [
                    '[field]' => 'payment_method_id',
                ]),
                __('api_required_field', [
                    '[field]' => 'token',
                ])
            ]);

            return $result;
        }

        $order_id = (int) $this->order_info['order_id'];
        $payment_id = (int) $this->payment_info['payment_id'];
        $total = (float) $this->order_info['total'];
        $payment_name = $this->payment_info['payment'];

        if (isset($request['payment_method_id'])) {
            $processor = ServiceProvider::getProcessorFactory()->getByPaymentId($payment_id);

            try {
                $confirmation_result = $processor->getPaymentConfirmationDetails($request['payment_method_id'], $total);
            } catch (Exception $e) {
                $result->setErrors([
                    __('stripe_connect.payment_intent_error', [
                        '[payment_id]' => $payment_name,
                        '[error]' => $e->getMessage(),
                    ])
                ]);
            }

            if ($confirmation_result->isSuccess()) {
                fn_update_order_payment_info($order_id, [
                    'transaction_id' => $confirmation_result->getData('payment_intent_id'),
                ]);
                $order_info = fn_get_order_info($order_id);

                if (!$order_info) {
                    $result->setErrors([__('text_order_placed_error')]);
                    $result->setSuccess(false);

                    return $result;
                }

                $order_info['payment_info']['stripe_connect.payment_intent_id'] = !empty($order_info['payment_info']['transaction_id'])
                    ? $order_info['payment_info']['transaction_id']
                    : '';

                $pp_response = $processor->chargeWith3DSecure($order_info);
                fn_change_order_status($order_id, $pp_response['order_status']);

                $result->setSuccess(in_array($pp_response['order_status'], fn_get_settled_order_statuses()));
            } else {
                $result->setErrors([__('text_order_placed_error')]);
                $result->setSuccess(false);
            }
        }

        if (isset($request['token'])) {
            try {
                fn_update_order_payment_info($order_id, [
                    'stripe_connect.token' => $request['token'],
                ]);

                fn_start_payment($order_id);
                $order_info = fn_get_order_info($order_id);

                if ($order_info['status'] === OrderStatuses::PARENT) {
                    foreach (fn_get_suborders_info($order_info['order_id']) as $suborder) {
                        $is_settled_order = in_array($suborder['status'], fn_get_settled_order_statuses());

                        if (!$is_settled_order) {
                            $result->setSuccess(false);
                            return $result;
                        }

                        $result->setSuccess(true);
                    }
                } else {
                    $result->setSuccess(in_array($order_info['status'], fn_get_settled_order_statuses()));
                }
            } catch (Exception $e) {
                $result->setErrors([
                    __('stripe_connect.payment_intent_error', [
                        '[payment_id]' => $payment_name,
                        '[error]' => $e->getMessage(),
                    ])
                ]);
            }
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
            'publishable_key'   => $payment_method['processor_params']['publishable_key'],
            'client_id'         => $payment_method['processor_params']['client_id'],
            'currency'          => $payment_method['processor_params']['currency'],
            'is_stripe_connect' => $payment_method['processor_params']['is_stripe_connect'],
            'payment_type'      => $payment_method['processor_params']['payment_type'],
        ];
    }
}
