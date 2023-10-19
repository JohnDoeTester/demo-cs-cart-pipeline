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

use Tygh\Registry;
use Tygh\Settings;
use Tygh\Addons\AntiFraud\MinFraud\Client;
use Tygh\Addons\AntiFraud\MinFraud\Request;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_anti_fraud_place_order(&$order_id, &$action, &$order_status)
{
    $settings = Registry::get('addons.anti_fraud');

    if (empty($settings['anti_fraud_key']) || empty($settings['anti_fraud_user_id'])) {
        return false;
    }

    $checked = db_get_field('SELECT COUNT(*) FROM ?:order_data WHERE order_id = ?i AND type = ?s', $order_id, 'F');

    if ($action == 'save' || defined('ORDER_MANAGEMENT') || $checked) {
        return true;
    }

    $order_info = fn_get_order_info($order_id);

    $client = new Client($settings['anti_fraud_user_id'], $settings['anti_fraud_key']);
    $response = $client->send(Request::createFromOrder($order_info));

    $return = array();

    if (empty($order_info['ip_address'])) {
        $return['B'][] = 'af_ip_not_found';
    }

    $risk_factor = 1;

    if ($response->hasError()) {
        $return['error'] = $response->getErrorMessage();
        $risk_factor *= AF_ERROR_FACTOR;
    } else {
        $email_data = $response->getEmailData();
        $billing_address_data = $response->getBillingAddressData();

        // Check if order total greater than defined
        if (!empty($settings['anti_fraud_max_order_total'])
            && floatval($order_info['total']) > floatval($settings['anti_fraud_max_order_total'])
        ) {
            $risk_factor *= AF_ORDER_TOTAL_FACTOR;
            $return['B'][] = 'af_big_order_total';
        }

        if (!empty($order_info['user_id'])) {
            // Check if this customer has processed orders
            $amount = db_get_field('SELECT COUNT(*) FROM ?:orders WHERE status IN (?a) AND user_id = ?i', fn_get_settled_order_statuses(), $order_info['user_id']);
            if (!empty($amount)) {
                $risk_factor /= AF_COMPLETED_ORDERS_FACTOR;
                $return['G'][] = 'af_has_successfull_orders';
            }

            // Check if this customer has failed orders
            $amount = db_get_field('SELECT COUNT(*) FROM ?:orders WHERE status IN (?a) AND user_id = ?i', ['D','F'], $order_info['user_id']);
            if (!empty($amount)) {
                $risk_factor *= AF_FAILED_ORDERS_FACTOR;
                $return['B'][] = 'af_has_failed_orders';
            }
        }

        if (empty($billing_address_data)) {
            $return['B'][] = 'af_country_doesnt_match';
        } else {
            if (!empty($billing_address_data['is_high_risk'])) {
                $return['B'][] = 'af_high_risk_country';
            }

            if (!empty($settings['anti_fraud_safe_distance'])
                && !empty($billing_address_data['distance_to_ip_location'])
                && intval($billing_address_data['distance_to_ip_location']) > intval($settings['anti_fraud_safe_distance'])
            ) {
                $return['B'][] = 'af_long_distance';
            }
        }

        if (!empty($email_data['is_high_risk'])) {
            $return['B'][] = 'af_high_risk_email';
        }

        $risk_factor += (float) $response->getRiskScore();

        if ($risk_factor > 100) {
            $risk_factor = 100;
        }
    }

    $return['risk_factor'] = $risk_factor;

    if (floatval($risk_factor) >= floatval($settings['anti_fraud_risk_factor'])) {
        $action = 'save';
        $order_status = Registry::get('addons.anti_fraud.antifraud_order_status');
        $return['B'][] = 'af_high_risk_factor';
        $return['I'] = true;

        fn_set_notification('W', __('warning'), __('antifraud_failed_order'));
    } else {
        $return['G'][] = 'af_low_risk_factor';
    }

    $return = serialize($return);
    $data = array (
        'order_id' => $order_id,
        'type' => 'F', //fraud checking data
        'data' => $return,
    );
    db_query("REPLACE INTO ?:order_data ?e", $data);

    return true;
}

function fn_anti_fraud_get_order_info(&$order, &$additional_data)
{
    if (!empty($additional_data['F'])) {
        $order['fraud_checking'] = @unserialize($additional_data['F']);
    }

    return true;
}

function fn_anti_fraud_add_status()
{
    $status_data = array(
        'type' => 'O',
        'description' => 'Fraud checking',
        'params' => array(
            'inventory' => 'D',
            'remove_cc_info' => 'Y',
            'repay' => 'N',
            'appearance_type' => 'D',
            'allow_return' => 'N',
        ),
    );

    Settings::instance()->updateValue('antifraud_order_status', fn_update_status('', $status_data, STATUSES_ORDER), 'anti_fraud');
}

function fn_anti_fraud_remove_status()
{
    $status_code = Settings::instance()->getValue('antifraud_order_status', 'anti_fraud');

    $o_ids = db_get_fields('SELECT order_id FROM ?:orders WHERE status = ?s', $status_code);

    if (!empty($o_ids)) {
        foreach ($o_ids as $order_id) {
            fn_change_order_status($order_id, 'O'); // Change order status from "Fraud checking" to "Open"
        }
    }

    fn_delete_status($status_code, STATUSES_ORDER);
}

function fn_anti_fraud_placement_routines(&$order_id, &$order_info, &$force_notification, &$clear_cart, &$action, &$display_notification)
{
    if (!empty($order_info['fraud_checking']['I'])) {
        $action = 'save';
        $display_notification = false;

        $params = db_get_row('SELECT * FROM ?:order_data WHERE order_id = ?i AND type = ?s', $order_id, 'F');
        $params['data'] = unserialize($params['data']);
        unset($params['data']['I']);

        $params['data'] = serialize($params['data']);
        db_query("REPLACE INTO ?:order_data ?e", $params);
    }
}
