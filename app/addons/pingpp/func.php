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

use Pingpp\Charge;
use Pingpp\Pingpp as Sdk;
use Tygh\Payments\Addons\Pingpp\Pingpp;
use Tygh\Registry;

/**
 * Installs Ping++ payment processor.
 */
function fn_pingpp_install_payment_processors()
{
    /** @var \Tygh\Database\Connection $db */
    $db = Tygh::$app['db'];

    if (!$db->getField('SELECT type FROM ?:payment_processors WHERE processor_script = ?s', Pingpp::getScriptName())) {
        $db->query('INSERT INTO ?:payment_processors ?e', array(
            'processor'          => __('pingpp.pingpp'),
            'processor_script'   => Pingpp::getScriptName(),
            'processor_template' => 'addons/pingpp/views/orders/components/payments/pingpp.tpl',
            'admin_template'     => 'pingpp.tpl',
            'callback'           => 'Y',
            'type'               => 'P',
            'addon'              => Pingpp::getPaymentName(),
        ));
    }
}

/**
 * Disables Pinp++ payment methods upon add-on uninstallation.
 */
function fn_pingpp_remove_payment_processors()
{
    /** @var \Tygh\Database\Connection $db */
    $db = Tygh::$app['db'];

    $processor_id = $db->getField(
        'SELECT processor_id FROM ?:payment_processors WHERE processor_script = ?s',
        Pingpp::getScriptName()
    );

    if (!$processor_id) {
        return;
    }

    $db->query('DELETE FROM ?:payment_processors WHERE processor_id = ?i', $processor_id);
    $db->query(
        'UPDATE ?:payments SET ?u WHERE processor_id = ?i',
        array(
            'processor_id'     => 0,
            'processor_params' => '',
            'status'           => 'D',
        ),
        $processor_id
    );
}

/**
 * Hook handler: removes Pinp++ method from customer area when Chinese yuan currency is not configured in the store.
 *
 * @param array  $params
 * @param string $fields
 * @param string $join
 * @param string $order
 * @param array  $condition
 * @param string $having
 */
function fn_pingpp_get_payments(&$params, &$fields, &$join, &$order, &$condition, &$having)
{
    if ($params['area'] == 'C') {
        if (!Registry::get('currencies.' . Pingpp::getCurrencyCode())) {
            $condition[] = db_quote(
                '(?:payment_processors.processor_script IS NULL'
                . ' OR ?:payment_processors.processor_script <> ?s)',
                Pingpp::getScriptName()
            );
        }
    }
}

/**
 * Returns status of the order.
 * If order is the parent order, the status of the its first child is returned.
 *
 * @param array $order_info Order info obtained from ::fn_get_order_info()
 *
 * @return array Status of the order or its first child.
 */
function fn_pingpp_get_order_status($order_info)
{
    if ($order_info['is_parent_order'] != 'Y') {
        return $order_info['status'];
    }

    return db_get_field(
        "SELECT status"
        . " FROM ?:orders"
        . " WHERE parent_order_id = ?i"
        . " ORDER BY order_id ASC"
        . " LIMIT 1",
        $order_info['order_id']
    );
}

/**
 * Retreives charge.
 *
 * @param string $api_key   Ping++ API key
 * @param string $charge_id Charge identifier
 *
 * @return array
 */
function fn_retreive_pingpp_charge($api_key, $charge_id)
{
    Sdk::setApiKey($api_key);

    return json_decode(Charge::retrieve($charge_id), true);
}

/**
 * Hook handler: removes Ping++ from the list of available payment methods when no suitable channels found.
 *
 * @param array $params
 * @param array $payments
 */
function fn_pingpp_get_payments_post(&$params, &$payments)
{
    if ($params['area'] == 'C') {
        $user_agent = fn_strtolower($_SERVER['HTTP_USER_AGENT']);

        if (preg_match('/(andorid|iphone|ipad|ipod)/', $user_agent)) {
            $scope = 'mobile';
        } elseif (preg_match('/micromessenger/', $user_agent)) {
            $scope = 'wx';
        } else {
            $scope = 'pc';
        }

        foreach (array_keys($payments) as $payment_id) {
            $payment = fn_get_processor_data($payment_id);
            if (!empty($payment['processor_script']) && $payment['processor_script'] == Pingpp::getScriptName()) {
                $has_channels = false;

                foreach ($payment['processor_params']['channels'] as $channel_id => $channel) {
                    if ($channel['is_enabled'] == 'Y' && in_array($scope, $channel['scopes'])) {
                        $has_channels = true;
                        break;
                    }
                }

                if (!$has_channels) {
                    unset($payments[$payment_id]);
                }
            }
        }
    }
}
