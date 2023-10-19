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

use Tygh\Addons\Stripe\Webhook\StripeWebhook;
use Stripe\Webhook;

defined('BOOTSTRAP') or die('Access denied');

if (!isset($_SERVER['HTTP_STRIPE_SIGNATURE'])) {
    die('Access denied');
}

$payload = @file_get_contents('php://input');
$stripe_signature = $_SERVER['HTTP_STRIPE_SIGNATURE'];
$event = null;
/** @var Tygh\Addons\Stripe\Webhook\StripeWebhookRepository $webhook_repository */
$webhook_repository = Tygh::$app['addons.stripe.webhook_repository'];

try {
    $event = Webhook::constructEvent(
        $payload,
        $stripe_signature,
        $webhook_repository->findSecretKeyByPaymentId($_REQUEST['payment_id'])
    );
} catch (Exception $e) {
    return [CONTROLLER_STATUS_NO_CONTENT];
}

fn_flush();

StripeWebhook::handle($event);

exit;
