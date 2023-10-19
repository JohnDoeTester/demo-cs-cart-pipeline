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
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\YesNo;
use Tygh\Registry;

/**
 * Installs PayPal Checkout payment processor.
 *
 * @return void
 */
function fn_paypal_checkout_install()
{
    /** @var \Tygh\Database\Connection $db */
    $db = Tygh::$app['db'];

    if (
        $db->getField(
            'SELECT type FROM ?:payment_processors WHERE processor_script = ?s',
            PaypalCheckout::getScriptName()
        )
    ) {
        return;
    }

    $db->query(
        'INSERT INTO ?:payment_processors ?e',
        [
            'processor'          => __('paypal_checkout.paypal_checkout'),
            'processor_script'   => PaypalCheckout::getScriptName(),
            'processor_template' => 'addons/paypal_checkout/views/orders/components/payments/paypal_checkout.tpl',
            'admin_template'     => 'paypal_checkout.tpl',
            'callback'           => YesNo::YES,
            'type'               => 'P',
            'addon'              => PaypalCheckout::getPaymentName(),
        ]
    );
}

/**
 * Disables PayPal Checkout payment methods upon add-on uninstallation.
 *
 * @return void
 */
function fn_paypal_checkout_uninstall()
{
    /** @var \Tygh\Database\Connection $db */
    $db = Tygh::$app['db'];

    $processor_id = $db->getField(
        'SELECT processor_id FROM ?:payment_processors WHERE processor_script = ?s',
        PaypalCheckout::getScriptName()
    );

    if (!$processor_id) {
        return;
    }

    $db->query('DELETE FROM ?:payment_processors WHERE processor_id = ?i', $processor_id);
    $db->query(
        'UPDATE ?:payments SET ?u WHERE processor_id = ?i',
        [
            'processor_id'     => 0,
            'processor_params' => '',
            'status'           => ObjectStatuses::DISABLED,
        ],
        $processor_id
    );
}

/**
 * The "save_log" hook handler.
 *
 * Actions performed:
 * - Adds PayPal Debug ID response header value to the logged HTTP request.
 *
 * @param string                $type                Log type
 * @param string                $action              Event action
 * @param string                $data                Request data
 * @param int                   $user_id             Logged in user ID
 * @param array<string, string> $content             Logged data
 * @param string                $event_type          Event type
 * @param string                $object_primary_keys Object primary key names
 *
 * @return void
 */
function fn_paypal_checkout_save_log($type, $action, $data, $user_id, array &$content, $event_type, $object_primary_keys)
{
    if (
        $type !== 'requests'
        || $action !== 'http'
    ) {
        return;
    }

    /** @var string $request_debug_id */
    $request_debug_id = Registry::ifGet('runtime.paypal_checkout.debug_id', '');
    if (!$request_debug_id) {
        return;
    }

    $content['paypal_checkout.debug_id'] = $request_debug_id;
    Registry::del('runtime.paypal_checkout.debug_id');
}
