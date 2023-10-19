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

use Tygh\Addons\StripeConnect\ServiceProvider;
use Tygh\Common\OperationResult;
use Tygh\Addons\StripeConnect\Logger;
use Tygh\Enum\OrderStatuses;

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'check_confirmation') {
        /** @var \Tygh\Ajax $ajax */
        $ajax = Tygh::$app['ajax'];
        /** @var array $cart */
        $cart = Tygh::$app['session']['cart'];

        if (!empty($cart['user_data']['user_exists'])) {
            return [CONTROLLER_STATUS_NO_CONTENT];
        }

        $params = array_merge([
            'payment_intent_id' => null,
            'order_id'          => null,
            'payment_id'        => null,
        ], $_REQUEST);

        $total = 0;
        if ($params['order_id']) {
            $order_info = fn_get_order_info($params['order_id']);
            $total = $order_info['total'];
        } else {
            $total = $cart['total'];
            if (!empty($cart['payment_surcharge'])) {
                $total += $cart['payment_surcharge'];
            }
        }

        $payment_id = $params['payment_id'];
        if (!$payment_id) {
            $payment_id = $cart['payment_id'];
        }
        $processor = ServiceProvider::getProcessorFactory()->getByPaymentId($payment_id);

        $confirmation_result = new OperationResult(false);
        try {
            $confirmation_result = $processor->getPaymentConfirmationDetails($params['payment_intent_id'], $total, $params['order_id']);
        } catch (Exception $e) {
            Logger::log(Logger::ACTION_FAILURE, __('stripe_connect.payment_intent_error', [
                '[payment_id]' => $payment_id,
                '[error]' => $e->getMessage(),
            ]));
        }

        if ($confirmation_result->isSuccess()) {
            foreach ($confirmation_result->getData() as $field => $value) {
                $ajax->assign($field, $value);
            }

            if (
                !empty($params['order_id'])
                && $confirmation_result->getData('payment_intent_id')
            ) {
                fn_update_order_payment_info(
                    (int) $params['order_id'],
                    [
                        'transaction_id' => $confirmation_result->getData('payment_intent_id'),
                    ]
                );
            }
        } else {
            $ajax->assign('error', [
                'message' => __('text_order_placed_error'),
            ]);
        }

        return [CONTROLLER_STATUS_NO_CONTENT];
    }

    if (
        $mode === 'confirm'
        && !empty($_REQUEST['order_id'])
    ) {
        $params = array_merge(
            [
                'payment_id' => null,
                'order_id'   => null,
            ],
            $_REQUEST
        );

        /** @var int $payment_id */
        $payment_id = $params['payment_id'];
        if (!$payment_id) {
            $payment_id = Tygh::$app['session']['cart']['payment_id'];
        }

        $processor = ServiceProvider::getProcessorFactory()->getByPaymentId($payment_id);
        try {
            /** @var array $order_info */
            $order_info = fn_get_order_info((int) $params['order_id']);
            $order_info['payment_info']['stripe_connect.payment_intent_id'] = !empty($order_info['payment_info']['transaction_id'])
                ? $order_info['payment_info']['transaction_id']
                : '';
            $pp_response = [];

            if (!empty($order_info['payment_info']['stripe_connect.payment_intent_id'])) {
                $pp_response = $processor->chargeWith3DSecure($order_info);
            } elseif (!empty($order_info['payment_info']['stripe_connect.token'])) {
                $pp_response = $processor->chargeWithout3DSecure($order_info);
            }

            if (
                !in_array($order_info['status'], fn_get_settled_order_statuses())
                && $pp_response['order_status'] === OrderStatuses::PAID
            ) {
                fn_change_order_status((int) $order_info['order_id'], $pp_response['order_status']);
            }
        } catch (Exception $e) {
            fn_log_event('general', 'runtime', [
                'message' => __('stripe_connect.payment_intent_error', [
                    '[payment_id]' => $payment_id,
                    '[error]'      => $e->getMessage(),
                ]),
            ]);
        }
    }

    if ($mode === 'update_payments_description') {
        $session = & Tygh::$app['session'];

        if (
            empty($session['stripe_connect_order_id'])
            && empty($_REQUEST['order_id'])
        ) {
            return [CONTROLLER_STATUS_NO_CONTENT];
        }

        $order_id = isset($session['stripe_connect_order_id'])
            ? $session['stripe_connect_order_id']
            : $_REQUEST['order_id'];
        unset($session['stripe_connect_order_id']);

        $order_info = fn_get_order_info($order_id);

        if (!$order_info) {
            return [CONTROLLER_STATUS_NO_CONTENT];
        }

        $processor = ServiceProvider::getProcessorFactory()->getByPaymentId($order_info['payment_id']);

        if (!$processor) {
            return [CONTROLLER_STATUS_NO_CONTENT];
        }

        $processor->updatePaymentsDescriptions($order_info);

        return [CONTROLLER_STATUS_NO_CONTENT];
    }
}

return [CONTROLLER_STATUS_NO_PAGE];
