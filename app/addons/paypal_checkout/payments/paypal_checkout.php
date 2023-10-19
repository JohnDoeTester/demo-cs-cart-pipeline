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

defined('BOOTSTRAP') or die('Access denied');

use Tygh\Addons\PaypalCheckout\Payments\PaypalCheckout;
use Tygh\Addons\PaypalCheckout\ServiceProvider;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\OrderStatuses;
use Tygh\Tygh;

$processor_factory = ServiceProvider::getProcessorFactory();

if (defined('PAYMENT_NOTIFICATION')) {
    $params = array_merge(
        [
            'order_id'           => null,
            'order_id_in_paypal' => null,
        ],
        $_REQUEST
    );

    $order_id = (int) $params['order_id'];
    $order_id_in_paypal = !empty($params['order_id_in_paypal']) ? (string) $params['order_id_in_paypal'] : $params['token'];

    $order_info = fn_get_order_info($order_id);

    if (
        !$order_info
        || $order_info['payment_info']['paypal_checkout.order_id'] !== $order_id_in_paypal
        || !fn_check_payment_script(PaypalCheckout::getScriptName(), $order_id)
    ) {
        die('Access denied');
    }

    $processor = $processor_factory->getByPaymentId(
        $order_info['payment_id'],
        $order_info['payment_method']['processor_params']
    );

    $pp_response = [];

    if ($mode === 'return') {
        $result = $processor->capture($order_id_in_paypal);
        if ($result->isSuccess()) {
            $pp_response['order_status'] = OrderStatuses::OPEN; // keep order open until IPN arrives
        } else {
            $pp_response['order_status'] = OrderStatuses::FAILED;
            $pp_response['reason_text'] = $result->getFirstError();
        }
    } elseif ($mode === 'cancel') {
        $pp_response['order_status'] = OrderStatuses::INCOMPLETED;
    }

    fn_finish_payment($order_id, $pp_response);
    fn_order_placement_routines('route', $order_id);
} else {
    /**
     * @var array<string, int|float|string> $order_info     Order info
     * @var array<string, int|float|string> $processor_data Payment method data
     *
     * @psalm-var array{
     *   order_id: int,
     *   payment_id: int
     * } $order_info
     *
     * @psalm-var array{
     *   processor_params: array{
     *     access_token: string,
     *     client_id: string,
     *     expiry_time: int,
     *     mode: string,
     *     secret: string,
     *     payer_id: string,
     *     currency: string
     *   }
     * } $processor_data
     */

    $processor = $processor_factory->getByPaymentId(
        $order_info['payment_id'],
        $processor_data['processor_params']
    );
    $pp_response = $processor->charge($order_info);

    if ($pp_response['order_status'] === OrderStatuses::FAILED) {
        fn_set_notification(NotificationSeverity::ERROR, __('error'), $pp_response['reason_text']);
    }

    if (defined('AJAX_REQUEST') && !empty($_REQUEST['custom_paypal_button'])) {
        if ($pp_response['order_status'] === OrderStatuses::FAILED) {
            Tygh::$app['ajax']->assign('error', true);
        } else {
            Tygh::$app['ajax']->assign('order_id', $order_info['order_id']);
            Tygh::$app['ajax']->assign('order_id_in_paypal', $pp_response['paypal_checkout.order_id']);
        }

        exit();
    }

    if (!empty($pp_response['links'])) {
        foreach ($pp_response['links'] as $link) {
            if ($link['rel'] !== 'approve') {
                continue;
            }
            $approval_link = $link['href'];
        }
        if (!empty($approval_link)) {
            fn_create_payment_form($approval_link, [], 'Paypal Checkout', true, 'get');
        } else {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), __('error_occured'));
        }
    } else {
        fn_set_notification(NotificationSeverity::ERROR, __('error'), __('error_occured'));
    }

    fn_redirect('checkout.checkout');
}
