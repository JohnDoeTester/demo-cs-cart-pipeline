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

use Tygh\Enum\Addons\Pingpp\Channels;
use Tygh\Enum\OrderStatuses;
use Tygh\Payments\Addons\Pingpp\Pingpp;

/** @var array $order_info */
/** @var array $processor_data */
/** @var string $mode */

if (defined('PAYMENT_NOTIFICATION')) {

    $order_id = $_REQUEST['order_id'];

    if (!fn_check_payment_script(Pingpp::getScriptName(), $order_id)) {
        return array(CONTROLLER_STATUS_DENIED);
    }

    $order_info = fn_get_order_info($order_id);

    $order_status = fn_pingpp_get_order_status($order_info);

    if ($mode == 'notify' || $mode == 'check') {

        $is_complete = false;

        try {
            $charge = fn_retreive_pingpp_charge(
                $order_info['payment_method']['processor_params']['api_key'],
                $order_info['payment_info']['transaction_id']
            );

            $is_complete = $mode == 'notify' || $charge['paid'];

            if ($is_complete) {

                fn_finish_payment($order_id, array(
                    'order_status'   => $is_complete
                        ? 'P'
                        : 'F',
                    'transaction_id' => $is_complete
                        ? $charge['transaction_no']
                        : $charge['id'],
                    'reason_text'    => $is_complete
                        ? __('pingpp.paid_at', array('[time]' => Tygh::$app['formatter']->asDateTime($charge['time_paid'])))
                        : __('text_transaction_declined'),

                ));
            }
        } catch (Exception $e) {
            if ($mode == 'notify') {
                fn_set_notification('E', __('error'), $e->getMessage());
                fn_order_placement_routines('checkout_redirect');
            }
        }

        if ($is_complete) {
            fn_order_placement_routines('route', $order_id, false);
        }
        exit;
    } elseif ($mode == 'cancel' || $mode == 'fail') {

        fn_finish_payment($order_id, array(
            'order_status'   => $mode == 'cancel'
                ? OrderStatuses::CANCELED
                : OrderStatuses::FAILED,
            'reason_text'    => $mode == 'cancel'
                ? __('text_transaction_cancelled')
                : __('text_transaction_declined'),
            'transaction_id' => '',
        ));

        fn_order_placement_routines('route', $order_id, false);
    } elseif ($mode == 'continue') {

        $processor_data = $order_info['payment_method'];
    }
}

if (empty($order_info['payment_info']['channel'])) {
    fn_set_notification('E', __('error'), __('pingpp.payment_channel_is_not_selected'));
    fn_order_placement_routines('checkout_redirect');
}

$processor = new Pingpp($order_info['payment_id'], $processor_data);
$processor->setChannel($order_info['payment_info']['channel']);
$processor->setExtra('server', $_SERVER);

if (in_array($processor->getChannelName(), array(Channels::WX_PUB, Channels::WX_LITE))) {
    if (isset($_REQUEST['code'])) {
        $processor->setExtra('open_id', $processor->getWxOpenId($_REQUEST['code']));
    } else {
        fn_redirect($processor->getWxOauthUrl($order_info['order_id']), true);
    }
}

$charge_result = $processor->charge($order_info);

// failed on creation
if ($charge_result['order_status'] == 'F') {
    fn_set_notification('E', __('error'), $charge_result['reason_text']);
    fn_order_placement_routines('checkout_redirect');
}

// payment form created
if (isset($charge_result['payment_form'])) {
    fn_create_payment_form(
        $charge_result['payment_form']['url'],
        $charge_result['payment_form']['data'],
        '',
        false,
        $charge_result['payment_form']['method']
    );
}

// qr code or wechat payment
if (isset($charge_result['qr_code_url']) || isset($charge_result['wx_pay_request'])) {
    Tygh::$app['view']
        ->assign(array(
            'qr_code_url'    => isset($charge_result['qr_code_url']) ? $charge_result['qr_code_url'] : null,
            'wx_pay_request' => isset($charge_result['wx_pay_request']) ? $charge_result['wx_pay_request'] : null,
            'instructions'   => $charge_result['instructions'],
            'channel'        => $processor->getChannelName(),
            'order_id'       => $processor->getOrderId(),
            'order_number'   => $processor->getOrderNumber(),
        ))
        ->display('addons/pingpp/views/pingpp/payment.tpl');
    exit;
}

// fallback
$pp_response = array(
    'order_status'   => $charge_result['order_status'],
    'reason_text'    => $charge_result['reason_text'],
    'transaction_id' => $charge_result['transaction_id'],
);