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

use Tygh\Addons\Stripe\Payments\Stripe;
use Tygh\Common\OperationResult;
use Tygh\Enum\OrderStatuses;
use Tygh\Enum\SiteArea;

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'check_confirmation') {
        /** @var \Tygh\Ajax $ajax */
        $ajax = Tygh::$app['ajax'];

        $params = array_merge(
            [
                'total' => null,
                'order_id' => null,
                'payment_id' => null,
                'payment_intent_id' => null,
                'email' => null,
            ],
            $_REQUEST
        );

        $user_id = empty(Tygh::$app['session']['auth']['user_id'])
            ? 0
            : Tygh::$app['session']['auth']['user_id'];
        if ($params['email'] && fn_is_user_exists($user_id, $params)) {
            $ajax->assign(
                'error',
                [
                    'message' => __('error_user_exists')
                ]
            );
            return [CONTROLLER_STATUS_NO_CONTENT];
        } elseif (!empty(Tygh::$app['session']['cart']['user_data']['user_exists'])) {
            return [CONTROLLER_STATUS_NO_CONTENT];
        }

        $total = 0;
        if ($action === 'instant_payment') {
            $total = $params['total'];
        } elseif ($params['order_id']) {
            $order_info = fn_get_order_info($params['order_id']);
            $total = $order_info['total'];
        } else {
            $total = Tygh::$app['session']['cart']['total'];
            if (!empty(Tygh::$app['session']['cart']['payment_surcharge'])) {
                $total += Tygh::$app['session']['cart']['payment_surcharge'];
            }
        }

        $payment_id = $params['payment_id'];
        if (!$payment_id) {
            $payment_id = Tygh::$app['session']['cart']['payment_id'];
        }

        $processor = new Stripe(
            $payment_id,
            Tygh::$app['db'],
            Tygh::$app['addons.stripe.price_formatter']
        );

        $confirmation_result = new OperationResult(false);
        try {
            $confirmation_result = $processor->getPaymentConfirmationDetails($params['payment_intent_id'], $total, $params['order_id']);
        } catch (Exception $e) {
            fn_log_event('general', 'runtime', [
                'message' => __('stripe.payment_intent_error', [
                    '[payment_id]' => $payment_id,
                    '[error]' => $e->getMessage(),
                ]),
            ]);
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

                $ajax->assign('order_complete_url', fn_url('checkout.complete?order_id=' . (int) $params['order_id'], SiteArea::STOREFRONT));
            }
        } else {
            $ajax->assign('error', [
                'message' => __('text_order_placed_error'),
            ]);
            fn_change_order_status((int) $params['order_id'], OrderStatuses::FAILED);
        }

        return [CONTROLLER_STATUS_NO_CONTENT];
    } elseif (
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

        $payment_id = $params['payment_id'];
        if (!$payment_id) {
            $payment_id = Tygh::$app['session']['cart']['payment_id'];
        }

        /** @var int $payment_id */
        $processor = new Stripe(
            $payment_id,
            Tygh::$app['db'],
            Tygh::$app['addons.stripe.price_formatter']
        );

        try {
            /** @var array $order_info */
            $order_info = fn_get_order_info((int) $params['order_id']);
            $order_info['payment_info']['stripe.payment_intent_id'] = !empty($order_info['payment_info']['transaction_id'])
                ? $order_info['payment_info']['transaction_id']
                : '';
            $pp_response = $processor->charge($order_info);

            if (
                !in_array($order_info['status'], fn_get_settled_order_statuses())
                && $pp_response['order_status'] === OrderStatuses::PAID
            ) {
                fn_change_order_status((int) $order_info['order_id'], $pp_response['order_status']);
                fn_order_placement_routines('route', $_REQUEST['order_id']);
            }
        } catch (Exception $e) {
            fn_log_event('general', 'runtime', [
                'message' => __('stripe.payment_intent_error', [
                    '[payment_id]' => $payment_id,
                    '[error]'      => $e->getMessage(),
                ]),
            ]);
        }
    }
}

return [CONTROLLER_STATUS_NO_PAGE];
