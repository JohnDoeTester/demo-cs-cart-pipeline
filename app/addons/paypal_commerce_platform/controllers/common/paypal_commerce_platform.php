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

use Tygh\Addons\PaypalCommercePlatform\Enum\WebhookEventType;
use Tygh\Addons\PaypalCommercePlatform\Payments\PaypalCommercePlatform;
use Tygh\Addons\PaypalCommercePlatform\ServiceProvider;
use Tygh\Addons\PaypalCommercePlatform\Webhook\PaymentFinishedEvent;
use Tygh\Addons\PaypalCommercePlatform\Webhook\PaymentRefundedEvent;

defined('BOOTSTRAP') or die('Access denied');

if ($mode === 'webhook') {
    $payload_body = file_get_contents('php://input');

    fn_log_event(
        'general',
        'runtime',
        [
            'message' => __('paypal_commerce_platform.webhook', ['[payload]' => $payload_body]),
        ]
    );

    $event = json_decode($payload_body, false);
    if (
        !isset($event->event_type)
        || !in_array($event->event_type, WebhookEventType::getCaptureTypes(), true)
    ) {
        die('err_event');
    }

    if ($event->event_type === WebhookEventType::PAYMENT_CAPTURE_REFUNDED) {
        $event = new PaymentRefundedEvent($event);
    } else {
        $event = new PaymentFinishedEvent($event);
    }
    $order_info = $event->getCapture()->getOrderInfo();
    if (!$order_info) {
        die('err_order');
    }

    if ($event->isProcessed()) {
        die('err_processed');
    }

    $capture = $event->getCapture();

    if (!fn_check_payment_script(PaypalCommercePlatform::getScriptName(), $capture->getOrderId())) {
        die('err_order');
    }

    $processor = ServiceProvider::getProcessorFactory()->getByPaymentId(
        $order_info['payment_id'],
        $order_info['payment_method']['processor_params']
    );
    $pp_response = $event->handle($processor);

    fn_update_order_payment_info($capture->getOrderId(), $pp_response);
    fn_change_order_status($capture->getOrderId(), (string) $pp_response['order_status']);
}

return [CONTROLLER_STATUS_NO_CONTENT];
