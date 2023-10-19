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

use Tygh\Enum\YesNo;
use Tygh\Registry;
use Tygh\Addons\ProductVariations\ServiceProvider as ProductVariationsServiceProvider;
use Tygh\VendorPayoutDetailsBuilder;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Get product/category/global earned points list
 *
 * @param integer $object_id Object ID
 * @param string $object_type Object type (see constants in the config.php file)
 * @param array $usergroup_ids Array with usergroup IDs
 * @param integer $company_id Company ID
 * @return array
 */
function fn_get_reward_points($object_id, $object_type = PRODUCT_REWARD_POINTS, $usergroup_ids = array(), $company_id = 0)
{
    $op_suffix = (Registry::get('addons.reward_points.consider_zero_values') == 'Y') ? '=' : '';

    if (fn_allowed_for('ULTIMATE')) {
        if ($object_type == GLOBAL_REWARD_POINTS) {
            if (empty($company_id) && Registry::get('runtime.company_id')) {
                $company_id = Registry::get('runtime.company_id');
            } elseif (!Registry::get('runtime.company_id')) {
                return array();
            }
        }
    }

    if (!empty($usergroup_ids)) {
        if (Registry::get('addons.reward_points.several_points_action') == 'minimal_absolute') {
            $order_by = 'amount_type ASC, amount ASC';
        } elseif (Registry::get('addons.reward_points.several_points_action') == 'minimal_percentage') {
            $order_by = 'amount_type DESC, amount ASC';
        } elseif (Registry::get('addons.reward_points.several_points_action') == 'maximal_absolute') {
            $order_by = 'amount_type ASC, amount DESC';
        } elseif (Registry::get('addons.reward_points.several_points_action') == 'maximal_percentage') {
            $order_by = 'amount_type DESC, amount DESC';
        }

        return db_get_row(
            "SELECT *, amount AS pure_amount FROM ?:reward_points"
            . " WHERE object_id = ?i AND object_type = ?s AND company_id = ?i"
                . " AND amount >$op_suffix 0 AND usergroup_id IN(?n)"
            . " ORDER BY ?p LIMIT 1",
            $object_id, $object_type, $company_id, $usergroup_ids, $order_by
        );
    } else {
        return db_get_hash_array(
            "SELECT *, amount AS pure_amount FROM ?:reward_points"
            . " WHERE object_id = ?i AND object_type = ?s AND company_id = ?i AND amount >$op_suffix 0"
            . " ORDER BY usergroup_id",
            'usergroup_id', $object_id, $object_type, $company_id
        );
    }
}

/**
 * Function adds record about reward point for given object
 *
 * @param array $object_data Array with data
 * @param integer $object_id Id of object
 * @param string $object_type Type of object (see config.php for list of defined types)
 * @param integer $company_id Company ID
 * @return boolean Result of addition
 */
function fn_add_reward_points($object_data, $object_id = 0, $object_type = GLOBAL_REWARD_POINTS, $company_id = 0)
{
    $object_data = fn_array_merge($object_data, array('object_id' => $object_id, 'object_type' => $object_type));

    if (fn_allowed_for('ULTIMATE')) {
        if ($object_type == GLOBAL_REWARD_POINTS) {
            if (!empty($company_id)) {
                $object_data['company_id'] = $company_id;
            } elseif (Registry::get('runtime.company_id')) {
                $object_data['company_id'] = Registry::get('runtime.company_id');
            } else {
                return false;
            }
        }
    }

    $reward_point_id = db_query("REPLACE INTO ?:reward_points ?e", $object_data);

    fn_set_hook('reward_points_add_points_post', $object_data, $object_id, $object_type, $company_id, $reward_point_id);

    return $reward_point_id;
}

/**
 * Hook deletes data defined for given company ID
 *
 * @param integer $company_id Company ID
 */
function fn_reward_points_ult_delete_company(&$company_id)
{
    db_query("DELETE FROM ?:reward_points WHERE company_id = ?i", $company_id);
}

function fn_reward_points_get_cart_product_data(&$product_id, &$_pdata, &$product)
{
    $_pdata = fn_array_merge($_pdata, db_get_row("SELECT is_pbp, is_oper, is_op FROM ?:products WHERE product_id = ?i", $product_id));
    if (isset($product['extra']['configuration'])) {
        $_pdata['extra']['configuration'] = $product['extra']['configuration'];
    }
}

function fn_reward_points_calculate_cart_taxes_pre(&$cart, &$cart_products, &$shipping_rates, &$calculate_taxes, &$auth)
{
    fn_set_hook('reward_points_cart_calculation', $cart_products, $cart, $auth);

    if (defined('ORDER_MANAGEMENT')
        && in_array(Registry::get('runtime.mode'), array('update', 'place_order', 'add'))
        && !isset($cart['points_info']['in_use']['cost'])
    ) {
        if (isset($cart['deleted_points_info']['in_use']['cost'])) {
            if ($cart['deleted_points_info']['in_use']['cost'] <= $cart['subtotal_discount']) {
                $cart['subtotal_discount'] -= $cart['deleted_points_info']['in_use']['cost'];
                unset($cart['deleted_points_info']);
            }
        }
    }

    // calculating price in points
    if (isset($cart['points_info']['in_use']['cost'])) {
        $cost_covered_by_applied_points = $cart['points_info']['in_use']['cost'];
    } else {
        $cost_covered_by_applied_points = 0;
    }

    $in_use_total_points = false;
    if (isset($cart['points_info']['total_price'])) {
        if (!empty($cart['points_info']['in_use']['points']) && $cart['points_info']['in_use']['points'] <= $cart['points_info']['total_price']) {
            $in_use_total_points = true;
        }
        unset($cart['points_info']['total_price']);
    }

    if (Registry::get('addons.reward_points.price_in_points_order_discount') == 'Y' && !empty($cart['subtotal_discount']) && !empty($cart['subtotal'])) {
        $price_coef =
            ((1 - (($cart['subtotal_discount'] - $cost_covered_by_applied_points) / $cart['subtotal']))* 100) / 100;
    } else {
        $price_coef = 1;
    }

    foreach ((array) $cart_products as $k => $v) {

        fn_set_hook('reward_points_calculate_item', $cart_products, $cart, $k, $v);

        if (!isset($v['exclude_from_calculate'])) {
            if (isset($cart['products'][$k]['extra']['points_info'])) {
                unset($cart['products'][$k]['extra']['points_info']);
            }
            fn_gather_reward_points_data($cart_products[$k], $auth);

            if (isset($cart_products[$k]['points_info']['raw_price'])) {
                $product_price_in_points = $price_coef * $cart_products[$k]['points_info']['raw_price'];
                $cart['products'][$k]['extra']['points_info']['raw_price'] = $product_price_in_points;
                $cart['products'][$k]['extra']['points_info']['display_price'] = $cart['products'][$k]['extra']['points_info']['price'] = ceil($product_price_in_points);
                $cart['points_info']['total_price'] =
                    (isset($cart['points_info']['total_price']) ? $cart['points_info']['total_price'] : 0)
                    + $product_price_in_points;
            }
        }
    }

    $cart['points_info']['raw_total_price'] = isset($cart['points_info']['total_price']) ?  $cart['points_info']['total_price'] : 0;
    $cart['points_info']['total_price'] = ceil((int)($cart['points_info']['raw_total_price'] * 100) / 100);

    if (!empty($cart['points_info']['in_use']['points']) && $cart['points_info']['in_use']['points'] > $cart['points_info']['total_price'] && $in_use_total_points) {
        $cart['points_info']['in_use']['points'] = $cart['points_info']['total_price'];
    }

    if (!empty($cart['points_info']['in_use'])
        &&
        (
            Registry::get('runtime.controller') == 'checkout'
            ||
            (
                defined('ORDER_MANAGEMENT')
                && in_array(
                    Registry::get('runtime.mode'),
                    array('update', 'place_order', 'add')
                )
            )
        )
    ) {
        fn_set_point_payment($cart, $cart_products, $auth);
    }

    // calculating reward points
    if (isset($cart['points_info']['reward'])) {
        unset($cart['points_info']['reward']);
    }

    if (isset($cart['points_info']['additional'])) {
        $cart['points_info']['reward'] = $cart['points_info']['additional'];
        unset($cart['points_info']['additional']);
    }

    $discount = 0;
    if (Registry::get('addons.reward_points.reward_points_order_discount') == 'Y'
        && !empty($cart['subtotal_discount'])
        && !empty($cart['subtotal'])
    ) {
        $discount += $cart['subtotal_discount'];
    } elseif (!empty($cart['points_info'])
        && !empty($cart['points_info']['in_use'])
        && !empty($cart['points_info']['in_use']['cost'])
    ) {
        $discount += $cart['points_info']['in_use']['cost'];
    }

    if ($discount && !empty($cart['subtotal'])) {
        $reward_coef = 1 - $discount / $cart['subtotal'];
    } else {
        $reward_coef = 1;
    }

    foreach ((array) $cart_products as $k => $v) {

        fn_set_hook('reward_points_calculate_item', $cart_products, $cart, $k, $v);

        if (!isset($v['exclude_from_calculate'])) {
            if (isset($cart_products[$k]['points_info']['reward'])) {
                $product_reward = $v['amount'] * (!empty($v['product_options']) ? fn_apply_options_modifiers($cart['products'][$k]['product_options'], $cart_products[$k]['points_info']['reward']['raw_amount'], POINTS_MODIFIER_TYPE) : $cart_products[$k]['points_info']['reward']['raw_amount']);
                $cart['products'][$k]['extra']['points_info']['reward'] = round($product_reward);
                $cart_reward = round($reward_coef * $product_reward);
                $cart['points_info']['reward'] = (isset($cart['points_info']['reward']) ? $cart['points_info']['reward'] : 0) + $cart_reward;
            }
        }
    }
}

/**
 * Apply point payment
 *
 * @param array $cart           Array of cart data.
 * @param array $cart_products  List of cart products.
 * @param array $auth           Array of user authentication data (e.g. uid, usergroup_ids, etc.).
 */
function fn_set_point_payment(&$cart, &$cart_products, &$auth)
{
    $user_info = Registry::get('user_info');

    // pick previously applied points before update
    $cost_covered_by_applied_points = !empty($cart['points_info']['in_use']['cost']) ? $cart['points_info']['in_use']['cost'] : 0;

    $point_exchange_rate = floatval(Registry::get('addons.reward_points.point_rate'));
    if (defined('ORDER_MANAGEMENT')) {
        $user_points = fn_get_user_additional_data(POINTS, $auth['user_id']) + (!empty($cart['previous_points_info']['in_use']['points']) ? $cart['previous_points_info']['in_use']['points'] : 0);
    } else {
        $user_points = !empty($user_info) ? $user_info['points'] : 0;
    }

    /**
     * Executed before the reward points are applied.
     *
     * @param array $cart                           Array of cart data.
     * @param array $cart_products                  List of cart products.
     * @param array $auth                           Array of user authentication data (e.g. uid, usergroup_ids, etc.).
     * @param array $user_info                      Array of user data.
     * @param float $cost_covered_by_applied_points Total sum of products covered by previously applied points.
     * @param float $point_exchange_rate            The number of points equal to 1 conventional unit.
     * @param float $user_points                    Total sum of points available for user.
     */
    fn_set_hook('set_point_payment', $cart, $cart_products, $auth, $user_info, $cost_covered_by_applied_points, $point_exchange_rate, $user_points);

    if ($point_exchange_rate * $user_points * floatval($cart['subtotal']) > 0) {
        $points_in_use = $cart['points_info']['in_use']['points'];
        if ($points_in_use > $user_points) {
            $points_in_use = $user_points;
            fn_set_notification('W', __('warning'), __('text_points_exceed_points_on_account'));
        }
        if (empty($cart['points_info']['total_price'])) {
            $cart['points_info']['total_price'] = 0;
        }
        if ($points_in_use > $cart['points_info']['total_price']) {
            $points_in_use = $cart['points_info']['total_price'];
            fn_set_notification('W', __('warning'), __('text_points_exceed_points_that_can_be_applied'));
        }

        if (!empty($points_in_use)) {
            $cost = 0;
            $subtotal_discount_coef = (!empty($cart['subtotal_discount']))
                ? (1 - (($cart['subtotal_discount'] - $cost_covered_by_applied_points) / $cart['subtotal']))
                : 1;

            foreach ($cart['products'] as $cart_id=>$v) {
                if (isset($v['extra']['points_info']['price'])) {
                    $all_points = ($points_in_use == $cart['points_info']['total_price'])
                        ? $cart['points_info']['total_price']
                        : $cart['points_info']['raw_total_price'];

                    $discount = ($points_in_use / $all_points) * $cart_products[$cart_id]['subtotal'] * $subtotal_discount_coef;

                    $cart['products'][$cart_id]['extra']['points_info']['discount'] = fn_format_price($discount);
                    $cost += $discount;
                }
            }

            // check for subtotal discounts
            $subtotal_odds = $cart['subtotal']
                - $cost
                - (!empty($cart['subtotal_discount'])
                    ? $cart['subtotal_discount'] - $cost_covered_by_applied_points
                    : 0
                );

            // check for totals discounts, certificates and etc.
            $total_odds = floatval($cart['subtotal']) - $cost;

            $odds = min($subtotal_odds, $total_odds);

            if (fn_format_price($odds) < 0) {
                $points_in_use = ceil($points_in_use * ($cost + $odds) / $cost);
                $cost += $odds;
            }

            if ($points_in_use == floor($cart['points_info']['raw_total_price'])) {
                $points_in_use = (int) (($cart['points_info']['raw_total_price'] * 100) / 100);
            }

            if (fn_format_price($cost) && $cost > 0) {
                $cost = fn_format_price($cost);
                $cart['points_info']['in_use'] = array(
                    'points' => $points_in_use,
                    'cost' => $cost
                );

                if (!empty($cost_covered_by_applied_points)) {
                    // avoid repeated discount applying
                    $cost -= $cost_covered_by_applied_points;
                }

                if (!empty($cost)) {
                    $cart['subtotal_discount'] += $cost;
                    $cart['subtotal_discount'] = ($cart['subtotal_discount'] > 0) ? $cart['subtotal_discount'] : 0;
                    $cart['subtotal_discount'] = fn_format_price($cart['subtotal_discount']);
                }
            } else {
                fn_set_notification('E', __('error'), __('text_points_cannot_applied_because_subtotal_redeemed'));
                unset($cart['points_info']['in_use']);
            }
        } else {
            unset($cart['points_info']['in_use']);
        }
    } else {
        if (floatval($cart['subtotal']) == 0) {
            fn_set_notification('E', __('error'), __('text_cannot_apply_points_to_this_order_because_total'));
        }
        if ($user_points <= 0) {
            fn_set_notification('E', __('error'), __('text_cannot_apply_points_to_this_order_because_user'));
        }
        unset($cart['points_info']['in_use']);
    }

    Registry::set('user_info', $user_info);
}

function fn_change_user_points($value, $user_id, $reason = '', $action = CHANGE_DUE_ADDITION)
{

    $value = (int) $value;
    if (!empty($value)) {
        $current_value = (int) fn_get_user_additional_data(POINTS, $user_id);
        fn_save_user_additional_data(POINTS, $current_value + $value, $user_id);

        $change_points = array(
            'user_id' => $user_id,
            'amount' => $value,
            'timestamp' => TIME,
            'action' => $action,
            'reason' => $reason
        );

        return db_query("REPLACE INTO ?:reward_point_changes ?e", $change_points);
    }

    return '';
}

function fn_reward_points_place_order(&$order_id, &$fake, &$fake1, &$cart)
{

    if (!empty($order_id)) {
        if (isset($cart['points_info']['reward'])) {
            $order_data = array(
                'order_id' => $order_id,
                'type' => POINTS,
                'data' => $cart['points_info']['reward']
            );
            db_query("REPLACE INTO ?:order_data ?e", $order_data);
        }

        if (isset($cart['points_info']['in_use'])) {
            $order_data = array(
                'order_id' => $order_id,
                'type' => POINTS_IN_USE,
                'data' => serialize($cart['points_info']['in_use'])
            );
            db_query("REPLACE INTO ?:order_data ?e", $order_data);

        } elseif (isset($cart['previous_points_info']['in_use'])) {
            db_query("DELETE FROM ?:order_data WHERE order_id = ?i AND type = ?s", $order_id, POINTS_IN_USE);
        }
    }
}

function fn_reward_points_place_suborders(&$cart, &$_cart)
{
    if (!empty($cart['points_info']['in_use']) && !empty($_cart['points_info']['in_use'])) {
        $cart['points_info']['in_use']['points'] -= $_cart['points_info']['in_use']['points'];
        $cart['points_info']['in_use']['cost'] -= $_cart['points_info']['in_use']['cost'];
    }
}

function fn_reward_points_get_order_info(&$order, &$additional_data)
{
    if (empty($order)) {
        return false;
    }

    foreach ($order['products'] as $k => $v) {
        if (isset($v['extra']['points_info']['price'])) {
            $order['points_info']['price'] = (
                isset($order['points_info']['price'])
                    ? $order['points_info']['price']
                    : 0
                ) + $v['extra']['points_info']['price'];
        }
    }

    if (isset($additional_data[POINTS])) {
        $order['points_info']['reward'] = $additional_data[POINTS];
    }
    if (!empty($additional_data[POINTS_IN_USE])) {
        $order['points_info']['in_use'] = unserialize($additional_data[POINTS_IN_USE]);
    }
    $order['points_info']['is_gain'] = isset($additional_data[ORDER_DATA_POINTS_GAIN]) ? 'Y' : 'N';
}

function fn_reward_points_change_order_status(&$status_to, &$status_from, &$order_info, &$force_notification, &$order_statuses, &$place_order)
{
    static $log_id;

    if (isset($order_info['deleted_order'])) {
        if (!empty($log_id)) {
            $log_item = array(
                'action' => CHANGE_DUE_ORDER_DELETE
            );
            db_query("UPDATE ?:reward_point_changes SET ?u WHERE change_id = ?i", $log_item, $log_id);
        }

        return true;
    }

    $points_info = (isset($order_info['points_info'])) ? $order_info['points_info'] : array();
    if (!empty($points_info)) {
        if (!empty($order_info['parent_order_id'])) {
            $parent_order_id = $order_info['parent_order_id'];
            $parent_order_info = fn_execute_as_company(static function () use ($parent_order_id) {
                return fn_get_order_info($parent_order_id);
            }, 0);
            $parent_order_points_info = isset($parent_order_info['points_info']) ? $parent_order_info['points_info'] : [];

            $suborders_short_info = fn_get_suborders_info($order_info['parent_order_id']);
            $suborders_points_info = [];
            foreach ($suborders_short_info as $short_info) {
                $suborder_info = fn_get_order_info($short_info['order_id']);
                if (!isset($suborder_info['points_info'])) {
                    continue;
                }
                $suborders_points_info[] = $suborder_info['points_info'];
            }
        }
        $reason = array(
            'order_id' => $order_info['order_id'],
            'to' => $status_to,
            'from' =>$status_from
        );
        $action = empty($place_order) ? CHANGE_DUE_ORDER : CHANGE_DUE_ORDER_PLACE;

        $grant_points_to = (!empty($order_statuses[$status_to]['params']['grant_reward_points'])) ? $order_statuses[$status_to]['params']['grant_reward_points'] : 'N';
        $grant_points_from = (!empty($order_statuses[$status_from]['params']['grant_reward_points'])) ? $order_statuses[$status_from]['params']['grant_reward_points'] : 'N';

        if ($order_statuses[$status_to]['params']['inventory'] == 'I' && $order_statuses[$status_from]['params']['inventory'] == 'D') {
            if (!empty($points_info['in_use']['points'])) {
                // increase points in use
                $log_id = fn_change_user_points($points_info['in_use']['points'], $order_info['user_id'], serialize(fn_array_merge($reason, array('text' => 'text_increase_points_in_use'))), $action);
            }
        }

        if (
            $grant_points_to === YesNo::NO && $grant_points_from === YesNo::YES
            && $points_info['is_gain'] === YesNo::YES && !empty($points_info['reward'])
            && (!isset($parent_order_points_info) || !empty($parent_order_points_info['reward']))
        ) {
            // decrease earned points
            $log_id = fn_change_user_points(-$points_info['reward'], $order_info['user_id'], serialize($reason), $action);
            db_query('DELETE FROM ?:order_data WHERE order_id = ?i AND type = ?s', $order_info['order_id'], ORDER_DATA_POINTS_GAIN);
            if (!empty($suborders_points_info) && fn_reward_points_is_need_change_parent_order_flag_gain($suborders_points_info, 'decrease')) {
                db_query('DELETE FROM ?:order_data WHERE order_id = ?i AND type = ?s', $order_info['parent_order_id'], ORDER_DATA_POINTS_GAIN);
            }
        }

        if ($order_statuses[$status_to]['params']['inventory'] == 'D' && $order_statuses[$status_from]['params']['inventory'] == 'I') {
            if (!empty($points_info['in_use']['points'])) {
                // decrease points in use
                if ($points_info['in_use']['points'] > fn_get_user_additional_data(POINTS, $order_info['user_id'])) {
                    fn_set_notification('W', __('warning'), __('text_order_status_has_not_been_changed'));
                    $status_to = $status_from;
                } else {
                    $log_id = fn_change_user_points( - $points_info['in_use']['points'], $order_info['user_id'], serialize(fn_array_merge($reason, array('text' => 'text_decrease_points_in_use'))), $action);
                }
            }
        }

        if (
            $grant_points_to === YesNo::YES && $points_info['is_gain'] === YesNo::NO && !empty($points_info['reward'])
                && (!isset($parent_order_points_info)
                || ($parent_order_points_info['is_gain'] === YesNo::NO && !empty($parent_order_points_info['reward'])))
        ) {
            // increase  rewarded points
            $log_id = fn_change_user_points($points_info['reward'], $order_info['user_id'], serialize($reason), $action);
            $order_data = [
                'order_id' => $order_info['order_id'],
                'type'     => ORDER_DATA_POINTS_GAIN,
                'data'     => YesNo::YES,
            ];
            db_replace_into('order_data', $order_data);
            if (!empty($suborders_points_info) && fn_reward_points_is_need_change_parent_order_flag_gain($suborders_points_info, 'increase')) {
                $order_data['order_id'] = $order_info['parent_order_id'];
                db_replace_into('order_data', $order_data);
            }
        }
    }
}

/**
 * Сhecks whether the 'gain' flag of the parent order needs to be changed.
 *
 * @param array<int, array<string, string|int>> $suborders Array of suborders data.
 * @param string                                $action    Action.
 *
 * @return bool
 */
function fn_reward_points_is_need_change_parent_order_flag_gain(array $suborders, $action)
{
    if ($action === 'increase') {
        $flag_to_check = YesNo::NO;
    } elseif ($action === 'decrease') {
        $flag_to_check = YesNo::YES;
    } else {
        return false;
    }
    foreach ($suborders as $order_info) {
        if ($order_info['is_gain'] === $flag_to_check) {
            return false;
        }
    }
    return true;
}

function fn_reward_points_delete_order(&$order_id)
{
    $order_info = array('deleted_order' => true);
    $status_to = $status_from = '';
    $empty_array = array();
    $place_order = false;
    fn_reward_points_change_order_status($status_to, $status_from, $order_info, $empty_array, $empty_array, $place_order);
}

function fn_reward_points_get_user_info(&$user_id, &$get_profile, &$profile_id, &$user_data)
{
    $user_data['points'] = 0;

    if (!empty($user_data['user_id'])) {
        $user_data['points'] = fn_get_user_additional_data(POINTS, $user_data['user_id']);
        if (empty($user_data['points'])) {
            $user_data['points'] = 0;
        }
    }
}

//
// Update product point price
//
function fn_add_price_in_points($price, $product_id)
{
    if (empty($price['lower_limit'])) {
        $price['lower_limit'] = '1';
    }

    $price['point_price'] = !empty($price['point_price']) ? abs($price['point_price']) : 0;
    $price['usergroup_id'] = isset($price['usergroup_id']) ? intval($price['usergroup_id']) : USERGROUP_ALL;
    $price['product_id'] =	$product_id;

    $point_price_id = db_query("REPLACE INTO ?:product_point_prices ?e", $price);

    fn_set_hook('reward_points_add_product_point_prices_post', $price, $product_id, $point_price_id);
}

function fn_get_price_in_points($product_id, &$auth)
{
    $usergroup = db_quote(" AND usergroup_id IN (?n)", ((AREA == 'C') ? array_merge(array(USERGROUP_ALL), $auth['usergroup_ids']) : USERGROUP_ALL));

    return db_get_field("SELECT MIN(point_price) FROM ?:product_point_prices WHERE product_id = ?i AND lower_limit = 1 ?p", $product_id, $usergroup);
}

function fn_reward_points_gather_additional_product_data_post(&$product, &$auth, &$params)
{
    if (empty($params['get_for_one_product'])) {
        return;
    }

    $get_point_info = false;
    if (Registry::get('runtime.controller') == 'products' &&
        in_array(Registry::get('runtime.mode'), array('view', 'quick_view', 'options'))
    ) {
        $get_point_info = !empty($params['get_options']);
    }
    fn_gather_reward_points_data($product, $auth, $get_point_info);
}

/**
 * Gathers data for reward points.
 *
 * @param array $product        Array of product data.
 * @param array $auth           Array of user authentication data (e.g. uid, usergroup_ids, etc.).
 * @param bool  $get_point_info Determines whether the reward point data is required. Defaults to true.
 *
 * @return bool
 */
function fn_gather_reward_points_data(&$product, &$auth, $get_point_info = true)
{
    if (empty($auth['usergroup_ids'])) {
        $auth['usergroup_ids'] = [
            USERGROUP_ALL,
            empty($auth['user_id']) ? USERGROUP_GUEST : USERGROUP_REGISTERED
        ];
    }

    /**
     * Executed before the reward point data is gathered.
     *
     * @param array $product        Array of product data.
     * @param array $auth           Array of user authentication data (e.g. uid, usergroup_ids, etc.).
     * @param bool  $get_point_info Determines whether the reward point data is required. Defaults to true.
     */
    fn_set_hook('gather_reward_points_data_pre', $product, $auth, $get_point_info);

    // Check, if the product has any option points modifiers
    if (empty($product['options_update']) && !empty($product['product_options'])) {
        foreach ($product['product_options'] as $_id => $option) {
            if (!empty($product['product_options'][$_id]['variants'])) {
                foreach ($product['product_options'][$_id]['variants'] as $variant) {
                    if (!empty($variant['point_modifier']) && floatval($variant['point_modifier'])) {
                        $product['options_update'] = true;
                        break 2;
                    }
                }
            }
        }
    }

    if (isset($product['exclude_from_calculate']) || (isset($product['points_info']['reward']) && !(Registry::get('runtime.controller') == 'products' && Registry::get('runtime.mode') == 'options')) || $get_point_info == false) {
        return false;
    }

    if (!isset($product['main_category'])) {
        $product['main_category'] = db_get_field(
            "SELECT category_id FROM ?:products_categories WHERE product_id = ?i AND link_type = 'M'",
            $product['product_id']
        );
    }
    $candidates = array(
        PRODUCT_REWARD_POINTS  => $product['product_id'],
        CATEGORY_REWARD_POINTS => $product['main_category'],
        GLOBAL_REWARD_POINTS   => 0
    );

    $reward_points = array();
    foreach ($candidates as $object_type => $object_id) {
        $_reward_points = fn_get_reward_points($object_id, $object_type, $auth['usergroup_ids']);

        if ($object_type == CATEGORY_REWARD_POINTS && !empty($_reward_points)) {
            // get the "override point" setting
            $category_is_op = db_get_field("SELECT is_op FROM ?:categories WHERE category_id = ?i", $_reward_points['object_id']);
        }
        if ($object_type == CATEGORY_REWARD_POINTS && (empty($_reward_points) || $category_is_op != 'Y')) {
            // if there is no points of main category of the "override point" setting is disabled
            // then get point of secondary categories
            if (!isset($product['category_ids'])) {
                $secondary_categories = db_get_fields("SELECT category_id FROM ?:products_categories WHERE product_id = ?i AND link_type = 'A'", $product['product_id']);
            } else {
                $secondary_categories = array_diff($product['category_ids'], (array) $product['main_category']);
            }

            if (!empty($secondary_categories)) {
                $secondary_categories_points = array();
                foreach ($secondary_categories as $value) {
                    $_rp = fn_get_reward_points($value, $object_type, $auth['usergroup_ids']);
                    if (isset($_rp['amount'])) {
                        $secondary_categories_points[] = $_rp;
                    }
                    unset($_rp);
                }

                if (!empty($secondary_categories_points)) {
                    $sorted_points = fn_sort_array_by_key($secondary_categories_points, 'amount', (Registry::get('addons.reward_points.several_points_action') == 'minimal_absolute' || Registry::get('addons.reward_points.several_points_action') == 'minimal_percentage') ? SORT_ASC : SORT_DESC);
                    $_reward_points = array_shift($sorted_points);
                }
            }

            if (!isset($_reward_points['amount'])) {
                if (Registry::get('addons.reward_points.higher_level_extract') == 'Y' && !empty($candidates[$object_type])) {
                    $id_path = db_get_field("SELECT REPLACE(id_path, '{$candidates[$object_type]}', '') FROM ?:categories WHERE category_id = ?i", $candidates[$object_type]);
                    if (!empty($id_path)) {
                        $c_ids = explode('/', trim($id_path, '/'));
                        $c_ids = array_reverse($c_ids);
                        foreach ($c_ids as $category_id) {
                            $__reward_points = fn_get_reward_points($category_id, $object_type, $auth['usergroup_ids']);
                            if (!empty($__reward_points)) {
                                // get the "override point" setting
                                $_category_is_op = db_get_field("SELECT is_op FROM ?:categories WHERE category_id = ?i", $__reward_points['object_id']);
                                if ($_category_is_op == 'Y') {
                                    $category_is_op = $_category_is_op;
                                    $_reward_points = $__reward_points;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        if (!empty($_reward_points) && (($object_type == GLOBAL_REWARD_POINTS) || ($object_type == PRODUCT_REWARD_POINTS && $product['is_op'] == 'Y') || ($object_type == CATEGORY_REWARD_POINTS && (!empty($category_is_op) && $category_is_op == 'Y')))) {
            // if global points or category points (and override points is enabled) or product points (and override points is enabled)
            $reward_points = $_reward_points;
            break;
        }
    }

    if (isset($reward_points['amount'])) {
        $reward_points['coefficient'] = 1;
        if ((defined('ORDER_MANAGEMENT') || Registry::get('runtime.controller') == 'checkout') && isset($product['subtotal']) && isset($product['original_price'])) {
            if (Registry::get('addons.reward_points.points_with_discounts') == 'Y' && $reward_points['amount_type'] == 'P' && !empty($product['discount'])) {
                $reward_points['coefficient'] = (floatval($product['original_price'])) ? ($product['original_price'] - $product['discount']) / $product['original_price'] : 0;
            }
        } else {
            if (Registry::get('addons.reward_points.points_with_discounts') == 'Y' && $reward_points['amount_type'] == 'P' && isset($product['discount'])) {
                $reward_points['coefficient'] = $product['price'] / ($product['price'] + $product['discount']);
            }
        }

        if (isset($product['extra']['configuration'])) {
            if ($reward_points['amount_type'] == 'P') {
                // for configurable product calc reward points only for base price
                $price = $product['original_price'];
                if (!empty($product['discount'])) {
                    $price -= $product['discount'];
                }
                $reward_points['amount'] = $price * $reward_points['amount'] / 100;
            } else {
                $points_info = Registry::get("runtime.product_configurator.points_info");
                if (!empty($points_info[$product['product_id']])) {
                    $reward_points['amount'] = $points_info[$product['product_id']]['reward'];
                    $reward_points['coefficient'] = 1;
                }
            }
        } else {
            if ($reward_points['amount_type'] == 'P') {
                $price = $product['price'];
                if (!empty($product['discount'])) {
                    $price += $product['discount'];
                }
                $reward_points['amount'] = $price * $reward_points['amount'] / 100;
            }
        }

        $reward_points['raw_amount'] = $reward_points['coefficient'] * $reward_points['amount'];
        $reward_points['raw_amount'] = !empty($product['selected_options']) ? fn_apply_options_modifiers($product['selected_options'], $reward_points['raw_amount'], POINTS_MODIFIER_TYPE) : $reward_points['raw_amount'];

        $reward_points['amount'] = round($reward_points['raw_amount']);
        $product['points_info']['reward'] = $reward_points;
    }

    fn_calculate_product_price_in_points($product, $auth, $get_point_info);
}

function fn_calculate_product_price_in_points(&$product, &$auth, $get_point_info = true)
{
    if (isset($product['exclude_from_calculate'])
        || (AREA == 'A' && !defined('ORDER_MANAGEMENT') && Registry::get('runtime.controller') != 'subscriptions')
        || floatval($product['price']) == 0
        || (
            isset($product['points_info']['price'])
            && !(Registry::get('runtime.controller') == 'products' && Registry::get('runtime.mode') == 'options')
        )
        || $get_point_info == false
        || !isset($product['is_pbp'])
        || $product['is_pbp'] == 'N'
    ) {
        return false;
    }

    if (isset($product['subtotal']) || defined('ORDER_MANAGEMENT')) {
        if (Registry::get('addons.reward_points.auto_price_in_points') == 'Y' && $product['is_oper'] == 'N') {
            $per = Registry::get('addons.reward_points.point_rate');

            $subtotal = $product['subtotal'];
            if (Registry::get('addons.reward_points.price_in_points_with_discounts') == 'N' && isset($product['discount'])) {
                $subtotal = ($product['price'] + $product['discount']) * $product['amount'];
            }
        } else {
            $per = (!empty($product['original_price']) && floatval($product['original_price'])) ? fn_get_price_in_points($product['product_id'], $auth) / $product['original_price'] : 0;
            $subtotal = $product['original_price'] * $product['amount'];
        }
    } else {
        if (Registry::get('addons.reward_points.auto_price_in_points') == 'Y' && $product['is_oper'] == 'N') {
            $per = Registry::get('addons.reward_points.point_rate');

            $subtotal = $product['price'];
            if (Registry::get('addons.reward_points.price_in_points_with_discounts') == 'N' && isset($product['discount'])) {
                $subtotal = $product['price'] + $product['discount'];
                if (defined('ORDER_MANAGEMENT')) {
                    $subtotal *= $product['amount'];
                }
            }

        } else {
            $per = (!empty($product['price']) && floatval($product['price'])) ? fn_get_price_in_points($product['product_id'], $auth) / $product['price'] : 0;
            $subtotal = $product['price'];
        }
    }

    $product['points_info']['raw_price'] = fn_convert_to_numeric($per) * $subtotal;
    $product['points_info']['price'] = ceil($product['points_info']['raw_price']);
}

function fn_reward_points_clone_product(&$from_product_id, &$to_product_id)
{
    $reward_points = fn_get_reward_points($from_product_id);
    if (!empty($reward_points)) {
        foreach ($reward_points as $v) {
            unset($v['reward_point_id']);
            fn_add_reward_points($v, $to_product_id, PRODUCT_REWARD_POINTS);
        }
    }

    $fake = '';
    $price_in_points = fn_get_price_in_points($from_product_id, $fake);
    fn_add_price_in_points(array('point_price' => $price_in_points), $to_product_id);
}

function fn_check_points_gain($order_id)
{

    $is_gain = db_get_field("SELECT order_id FROM ?:order_data WHERE type = ?s AND order_id = ?i", ORDER_DATA_POINTS_GAIN, $order_id);

    return (!empty($is_gain)) ? true : false;
}

function fn_reward_points_get_selected_product_options_before_select(&$fields, &$condition, &$join, &$extra_variant_fields)
{
    $extra_variant_fields .= 'a.point_modifier, a.point_modifier_type,';
}

function fn_reward_points_get_product_options(&$fields, &$condition, &$join, &$extra_variant_fields)
{
    $extra_variant_fields .= 'a.point_modifier, a.point_modifier_type,';
}

function fn_reward_points_get_product_option_data_pre(&$option_id, &$product_id, &$fields, &$condition, &$join, &$extra_variant_fields, &$lang_code)
{
    $extra_variant_fields .= 'a.point_modifier, a.point_modifier_type,';
}

function fn_reward_points_apply_option_modifiers_pre(&$product_options, &$base_value, &$orig_options, &$extra, &$fields, &$type)
{
    if ($type == POINTS_MODIFIER_TYPE) {
        $fields = "a.point_modifier as modifier, a.point_modifier_type as modifier_type";
    }
}

//
//Integrate with RMA
//
function fn_reward_points_rma_recalculate_order(&$item, &$mirror_item, &$type, &$ex_data, &$amount)
{

    if (!isset($item['extra']['exclude_from_calculate'])) {
        if (isset($mirror_item['extra']['points_info']['reward'])) {
            $item['extra']['points_info']['reward'] = floor((isset($item['primordial_amount']) ? $item['primordial_amount'] : $item['amount']) * ($mirror_item['extra']['points_info']['reward'] / $mirror_item['amount']));
        }
        if (isset($mirror_item['extra']['points_info']['price'])) {
            $item['extra']['points_info']['price'] = floor((isset($item['primordial_amount']) ? $item['primordial_amount'] : $item['amount']) * ($mirror_item['extra']['points_info']['price'] / $mirror_item['amount']));
        }
        if (in_array($type, array('O-', 'M-O+'))) {
            if (isset($item['extra']['points_info']['reward'])) {
                $points = (($type == 'O-') ? 1 : -1) * floor($amount * (!empty($item['amount']) ? ($item['extra']['points_info']['reward'] / $item['amount']) : ($mirror_item['extra']['points_info']['reward'] / $mirror_item['amount'])));
                $additional_data = db_get_hash_single_array("SELECT type,data FROM ?:order_data WHERE order_id = ?i", array('type', 'data'), $ex_data['order_id']);

                if (!empty($additional_data[POINTS])) {
                    db_query('UPDATE ?:order_data SET ?u WHERE order_id = ?i AND type = ?s', array('data' => $additional_data[POINTS] + $points), $ex_data['order_id'], POINTS);
                }

                if (!empty($additional_data[ORDER_DATA_POINTS_GAIN]) && $additional_data[ORDER_DATA_POINTS_GAIN] == 'Y') {
                    $user_id = db_get_field("SELECT user_id FROM ?:orders WHERE order_id = ?i", $ex_data['order_id']);
                    $reason = array(
                        'return_id' => $ex_data['return_id'],
                        'to' 		=> $ex_data['status_to'],
                        'from' 		=> $ex_data['status_from']
                    );
                    fn_change_user_points($points, $user_id, serialize($reason), CHANGE_DUE_RMA);
                }
            }
        }
    }
}

function fn_reward_points_get_external_discounts(&$product, &$discounts)
{
    if (!empty($product['extra']['points_info']['discount'])) {
        $discounts += $product['extra']['points_info']['discount'];
    }
}

function fn_reward_points_form_cart(&$order_info, &$cart, &$auth)
{
    if (!empty($order_info['points_info'])) {
        $cart['points_info'] = $cart['previous_points_info'] = $order_info['points_info'];
    }

    if (!empty($auth['user_id']) && !isset($auth['points'])) {
        $auth['points'] = unserialize(db_get_field(
            "SELECT `data` FROM ?:user_data WHERE type=?s AND user_id = ?i",
            POINTS,
            $auth['user_id']
        ));
    }
}

function fn_reward_points_allow_place_order(&$total, &$cart)
{
    if (!empty($cart['points_info'])) {
        if (!empty($cart['points_info']['in_use']) && isset($cart['points_info']['in_use']['cost'])) {
            $total += $cart['points_info']['in_use']['cost'];
        }
    }

    return true;
}

function fn_reward_points_user_init(&$auth, &$user_info)
{
    if (empty($auth['user_id']) || AREA != 'C') {
        return false;
    }

    $points = fn_get_user_additional_data(POINTS, $auth['user_id']);
    if (empty($points)) {
        $points = 0;
    }

    $auth['points'] = $user_info['points'] = $points;

    return true;
}

function fn_reward_points_get_users(&$params, &$fields, &$sortings, &$condition, &$join)
{
    if ($params['extended_search']) {
        $sortings['points'] = '?:user_data.data';

        $join .= " LEFT JOIN ?:user_data ON ?:user_data.user_id = ?:users.user_id AND ?:user_data.type = 'W'";
        $fields[] = 'IF(?:user_data.data IS NOT NULL, ?:user_data.data, 0) as points';
    }

    return true;
}

function fn_reward_points_get_orders(&$params, &$fields, &$sortings, &$condition, &$join)
{
    if (empty($params['extra'])) {
        return;
    }

    $sortings['points'] = '?:order_data.data';

    $join .= db_quote(" LEFT JOIN ?:order_data ON ?:order_data.order_id = ?:orders.order_id AND ?:order_data.type = ?s", POINTS);
    $fields[] = "?:order_data.data as points";
}

function fn_reward_points_get_product_data(&$product_id, &$field_list, &$join, &$auth)
{
    $field_list .= ", MIN(point_prices.point_price) as point_price";

    $auth_usergroup_ids = !empty($auth['usergroup_ids']) ? $auth['usergroup_ids'] : array();
    $usergroup_ids = (AREA == 'C') ? array_merge(array(USERGROUP_ALL), $auth_usergroup_ids) : USERGROUP_ALL;
    $join .= db_quote(
        " LEFT JOIN ?:product_point_prices as point_prices"
        . " ON point_prices.product_id = ?:products.product_id"
        . " AND point_prices.lower_limit = 1"
        . " AND point_prices.usergroup_id IN (?n)"
        , $usergroup_ids
    );
}

function fn_reward_points_update_product_post(&$product_data, &$product_id)
{
    if (isset($product_data['point_price'])) {
        fn_add_price_in_points(array('point_price' => $product_data['point_price']), $product_id);
    }

    if (isset($product_data['reward_points']) && ($product_data['is_op'] == 'Y')) {
        foreach ($product_data['reward_points'] as $v) {
            fn_add_reward_points($v, $product_id, PRODUCT_REWARD_POINTS);
        }
    }
}

function fn_reward_points_promotion_give_points($bonus, &$cart, &$auth, &$cart_products)
{
    $cart['promotions'][$bonus['promotion_id']]['bonuses'][$bonus['bonus']] = $bonus;

    if ($bonus['bonus'] == 'give_points') {
        $cart['points_info']['additional'] = (!empty($cart['points_info']['additional']) ? $cart['points_info']['additional'] : 0) + $bonus['value'];
    }

    return true;
}

function fn_reward_points_update_category_post(&$category_data, &$category_id)
{
    if (isset($category_data['reward_points']) && $category_data['is_op'] == 'Y') {
        foreach ($category_data['reward_points'] as $v) {
            fn_add_reward_points($v, $category_id, CATEGORY_REWARD_POINTS);
        }
    }
}

function fn_reward_points_global_update_products(&$table, &$field, &$value, &$type, &$msg, &$update_data)
{
    // Updating product prices in points
    if (!empty($update_data['price_in_points'])) {
        $table[] = '?:product_point_prices';
        $field[] = 'point_price';
        $value[] = $update_data['price_in_points'];
        $type[] = $update_data['price_in_points_type'];

        $msg .= ($update_data['price_in_points'] > 0 ? __('price_in_points_increased') : __('price_in_points_decreased')) . ' ' . ($update_data['price_in_points_type'] == 'A' ? ' ' . __('points_lowercase', array(abs($update_data['price_in_points']))) : abs($update_data['price_in_points']) . '%') . '.';
    }
}

/**
 * Additional actions after 'Buy together' combinations' products changes
 *
 * @param array $cart Array of cart content and user information necessary for purchase
 * @param array $cart_products Array of new data for products information update
 * @return boolean Always true
 */
function fn_reward_points_buy_together_calculate_cart_post(&$cart, &$cart_products)
{
    if (isset($cart['products']) && is_array($cart['products'])) {
        foreach ($cart['products'] as $key => $value) {
            if (!empty($value['extra']['buy_together'])) {
                foreach ($cart_products as $k => $v) {
                    if (!empty($cart['products'][$k]['extra']['parent']['buy_together'])
                        && $cart['products'][$k]['extra']['parent']['buy_together'] == $key
                        && isset($cart['products'][$k]['extra']['points_info']['display_price'])
                        && isset($cart['products'][$key]['extra']['points_info']['display_price'])
                    ) {
                        $cart['products'][$key]['extra']['points_info']['display_price'] += $cart['products'][$k]['extra']['points_info']['display_price'];
                    }
                }
            }
        }
    }

    return true;
}

/**
 * Apply points to cart data
 *
 * @param array $cart Array of cart content and user information necessary for purchase
 * @param array $new_cart_data Array of new data for products, totals, discounts and etc. update
 * @param array $auth Array of user authentication data (e.g. uid, usergroup_ids, etc.)
 * @return boolean Always true
 */
function fn_reward_points_update_cart_by_data_post(&$cart, &$new_cart_data, &$auth)
{
    if (isset($new_cart_data['points_to_use'])) {
        $points_to_use = intval($new_cart_data['points_to_use']);
        if (!empty($points_to_use) && abs($points_to_use) == $points_to_use) {
            $cart['points_info']['in_use']['points'] = $points_to_use;
        }
    }

    return true;
}

/**
 * Changes before applying promotion rules
 *
 * @param array $promotions List of promotions
 * @param string $zone - promotiontion zone (catalog, cart)
 * @param array $data data array (product - for catalog rules, cart - for cart rules)
 * @param array $auth (optional) - auth array (for car rules)
 * @param array $cart_products (optional) - cart products array (for car rules)
 * @return boolean Always true
 */
function fn_reward_points_promotion_apply_pre(&$promotions, &$zone, &$data, &$auth, &$cart_products)
{
    // If we're in cart, set flag that promotions available
    if ($zone === 'cart') {
        if (empty($data['stored_subtotal_discount'])) {
            // unset points discount if discount amount is not set manually
            unset($data['points_info']['in_use']['cost']);
        }
    }

    return true;
}

function fn_reward_points_get_status_params_definition(&$status_params, $type)
{
    if ($type == STATUSES_ORDER) {
        $status_params['grant_reward_points'] = array (
            'type' => 'checkbox',
            'label' => 'grant_point',
            'default_value' => 'Y'
        );

    }

    return true;
}

/**
 * Adds record about reward point for products without data of the reward points.
 *
 * @param int     $product_id       Product identifier.
 * @param string  $override_points  The value of override_points setting. Possible values: 'Y' or 'N'.
 * @param int     $company_id       The identifier of the company.
 *
 * return void.
 */
function fn_reward_points_override_points($product_id, $override_points, $company_id = 0)
{
    if ($override_points != 'Y') {
        return;
    }

    $global_reward_points = fn_get_usergroups(array('type' => 'C', 'status' => array('A', 'H'), 'include_default' => true));
    $reward_points = fn_get_reward_points($product_id);

    $diff_reward_points = empty($reward_points) ? $global_reward_points : array_diff_key($global_reward_points, $reward_points);

    if (empty($diff_reward_points)) {
        return;
    }

    $global_reward_points = $diff_reward_points;

    if (!empty($global_reward_points)) {
        foreach ($global_reward_points as $reward_point) {
            unset($reward_point['reward_point_id']);

            $reward_point['amount'] = 0;
            $reward_point['pure_amount'] = 0;
            $reward_point['company_id'] = $company_id;

            fn_add_reward_points($reward_point, $product_id, PRODUCT_REWARD_POINTS);
        }
    }
}

/**
 * Hook handler: adds Reward points management fields into the list of product fields that can be bulk edited.
 */
function fn_reward_points_get_product_fields(&$fields)
{
    $fields[] = [
        'name' => '[data][is_pbp]',
        'text' => __('pay_by_points')
    ];

    if (Registry::get('addons.reward_points.auto_price_in_points') === 'Y') {
        $fields[] = [
            'name' => '[data][is_oper]',
            'text' => __('override_per')
        ];
    }

    $fields[] = [
        'name' => '[product_point_prices][point_price]',
        'text' => __('price_in_points')
    ];

    $fields[] = [
        'name' => '[data][is_op]',
        'text' => __('override_gc_points_brief')
    ];
}

function fn_product_variations_reward_points_add_points_post($object_data, $object_id, $object_type, $company_id, $reward_point_id)
{
    if ($object_type !== PRODUCT_REWARD_POINTS) {
        return;
    }

    $sync_service = ProductVariationsServiceProvider::getSyncService();

    $sync_service->onTableChanged('reward_points', $object_id, [
        'object_type'  => $object_data['object_type'],
        'usergroup_id' => $object_data['usergroup_id'],
        'company_id'   => $company_id
    ]);
}

function fn_product_variations_reward_points_add_product_point_prices_post($price, $product_id, $point_price_id)
{
    $sync_service = ProductVariationsServiceProvider::getSyncService();

    $sync_service->onTableChanged('product_point_prices', $product_id, [
        'lower_limit'  => $price['lower_limit'],
        'usergroup_id' => $price['usergroup_id']
    ]);
}

/**
 * The "load_products_extra_data" hook handler.
 *
 * Actions performed:
 *  - Loads point_price products value.
 *
 * @see fn_load_products_extra_data
 */
function fn_reward_points_load_products_extra_data(&$extra_fields, $products, $product_ids, $params, $lang_code)
{
    if (in_array('point_price', $params['extend'])) {
        $auth_usergroup_ids = !empty(Tygh::$app['session']['auth']['usergroup_ids']) ? Tygh::$app['session']['auth']['usergroup_ids'] : [];
        $usergroup_ids = (AREA == 'C') ? array_merge(array(USERGROUP_ALL), $auth_usergroup_ids) : USERGROUP_ALL;

        $extra_fields['?:product_point_prices'] = [
            'primary_key' => 'product_id',
            'fields' => [
                'point_price' => 'MIN(?:product_point_prices.point_price)'
            ],
            'condition'   => db_quote(
                ' AND ?:product_point_prices.lower_limit = 1 AND ?:product_point_prices.usergroup_id IN (?n)',
                $usergroup_ids
            )
        ];
    }
}

/**
 * The `vendor_payout_details_builder_create_details_post` hook handler.
 *
 * Action performed:
 *    - Adds information about applied reward points to payout details.
 *
 * @param VendorPayoutDetailsBuilder                                                $builder    Builder object.
 * @param array<string,array<string,array<string,array<string,array<string,int>>>>> $order_info Order information.
 * @param array<string, string>                                                     $cart       Cart information.
 * @param array<string, float|array<string, array{id: int, cost: float}>>           $details    Payout details.
 *
 * @see \Tygh\VendorPayoutDetailsBuilder::createDetails()
 *
 * @return void
 */
function fn_reward_points_vendor_payout_details_builder_create_details_post(
    VendorPayoutDetailsBuilder $builder,
    array $order_info,
    array $cart,
    array &$details
) {
    if (!isset($order_info['products'])) {
        return;
    }
    $details['points_used'] = 0.0;
    foreach ($order_info['products'] as $product) {
        if (empty($product['extra']['points_info']['discount'])) {
            continue;
        }
        $details['points_used'] += $product['extra']['points_info']['discount'];
    }
}

/**
 * The `vendor_payout_details_builder_create_updated_details_post` hook handler.
 *
 * Action performed:
 *     - Creates updated payout details about used reward points into specified order.
 *
 * @param VendorPayoutDetailsBuilder                $builder            Object of VendorPayoutDetailsBuilder class.
 * @param array<string, string>                     $updated_order_info Updated order info.
 * @param array{points_used? : int, string : float} $old_details        Payout details from previous payout at this order.
 * @param array<string, float>                      $updated_details    Updated payout details for updated order.
 *
 * @return void
 */
function fn_reward_points_vendor_payout_details_builder_create_updated_details_post(
    VendorPayoutDetailsBuilder $builder,
    array $updated_order_info,
    array $old_details,
    array &$updated_details
) {
    //phpcs:ignore
    if (isset($old_details['points_used'])) {
        $updated_details['points_used'] -= $old_details['points_used'];
    }
}

/**
 * The `rus_boxberry_create_parsel` hook handler.
 *
 * Action performed:
 *     - Reduces the payment amount when using reward points when placing an order.
 *
 * @param array<string, string|int|array<string|int>>                                                                             $shipment_data Shipment data
 * @param array<array-key, string|float|null|int|array<array-key, string|float|null|int|array<array-key, string|float|null|int>>> $order_info    Order data
 * @param int                                                                                                                     $group_key     Products group key
 * @param float                                                                                                                   $total         Payment amount
 *
 * @return void
 */
function fn_reward_points_rus_boxberry_create_parsel(array $shipment_data, array $order_info, $group_key, &$total)
{
    if (empty($order_info['points_info']['in_use']['cost'])) {
        return;
    }

    $total -= $order_info['points_info']['in_use']['cost'];
}
