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

use Tygh\Enum\NotificationSeverity;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_payment_dependencies_get_shipping_info_post(&$shipping_id, &$lang_code, &$shipping)
{
    $shipping['disable_payment_ids'] = db_get_fields('SELECT disable_payment_id FROM ?:payment_dependencies WHERE shipping_id = ?i', $shipping_id);
}

/**
 * The "update_shipping_post" hook handler.
 *
 * Actions performed:
 *  - Update entries in the 'payment_dependencies' table.
 *
 * @param array{enable_payment_ids: array<int, string>} $shipping_data Shipping info
 * @param int                                           $shipping_id   Shipping identifier
 * @param string                                        $lang_code     Two-letter language code (e.g. 'en', 'ru', etc.)
 * @param string                                        $action        Action that is performed with the shipping method
 *
 * @see fn_update_shipping
 */
function fn_payment_dependencies_update_shipping_post(array $shipping_data, $shipping_id, $lang_code, $action)
{
    if (isset($shipping_data['enable_payment_ids'])) {
        if (empty($shipping_data['enable_payment_ids']) || !is_array($shipping_data['enable_payment_ids'])) {
            $shipping_data['enable_payment_ids'] = [];
        }

        $disable_payment_ids = array_diff(
            array_keys(fn_get_payments(['direct_payments_skip_company_id' => true])),
            $shipping_data['enable_payment_ids']
        );

        fn_payment_dependencies_update_dependencies($shipping_id, $disable_payment_ids);
    }
}

function fn_payment_dependencies_prepare_checkout_payment_methods(&$cart, &$auth, &$payment_groups)
{
    if (!empty($cart['shipping'])) {
        $disable_payment_ids = [];
        foreach ($cart['shipping'] as $shipping) {
            if (empty($shipping['disable_payment_ids'])) {
                continue;
            }

            $disable_payment_ids = array_merge($disable_payment_ids, $shipping['disable_payment_ids']);
        }
        $disable_payment_ids = array_unique($disable_payment_ids);
        if ($disable_payment_ids) {
            foreach ($payment_groups as $g_key => $group) {
                foreach ($group as $p_key => $payment) {
                    if (in_array($payment['payment_id'], $disable_payment_ids)) {
                        unset($payment_groups[$g_key][$p_key]);
                        Tygh::$app['session']['payment_removed'] = true;
                    }
                }
                if (empty($payment_groups[$g_key])) {
                    unset($payment_groups[$g_key]);
                }
            }
        }
    }
}

function fn_payment_dependencies_shippings_get_shippings_list_post(&$group, &$lang, &$area, &$shippings_info)
{
    foreach ($shippings_info as $shipping_id => &$shipping) {
        $shipping['disable_payment_ids'] = db_get_fields('SELECT disable_payment_id FROM ?:payment_dependencies WHERE shipping_id = ?i', $shipping_id);
    }
}

function fn_payment_dependencies_checkout_select_default_payment_method(&$cart, &$payment_methods, &$completed_steps, $auth)
{
    $available_payment_ids = [];
    foreach ($payment_methods as $group) {
        foreach ($group as $method) {
            $available_payment_ids[] = $method['payment_id'];
        }
    }
    
    // Change default payment if it doesn't exists
    if (floatval($cart['total']) != 0 && !in_array($cart['payment_id'], $available_payment_ids)) {
        $cart['payment_id'] = reset($available_payment_ids);
        $cart['payment_method_data'] = fn_get_payment_method_data($cart['payment_id']);
        fn_calculate_cart_content($cart, $auth);
    }
}

/**
 * Update or create new payment dependencies
 *
 * @param int        $shipping_id         Shipping id
 * @param array<int> $disable_payment_ids Disabled payments for specific shipping
 */
function fn_payment_dependencies_update_dependencies($shipping_id, array $disable_payment_ids = [])
{
    if (!$shipping_id) {
        return;
    }

    db_query('DELETE FROM ?:payment_dependencies WHERE shipping_id = ?i', $shipping_id);

    if (!$disable_payment_ids) {
        return;
    }

    foreach ($disable_payment_ids as $disable_payment_id) {
        db_replace_into(
            'payment_dependencies',
            [
                'shipping_id'        => $shipping_id,
                'disable_payment_id' => $disable_payment_id,
            ]
        );
    }
}

/**
 * The "update_payment_post" hook handler.
 *
 * Actions performed:
 *  - Adds entries in the 'payment_dependencies' table.
 *
 * @param array<string, int|string> $payment_data     Payment data
 * @param int                       $payment_id       Payment identifier
 * @param string                    $lang_code        Language code
 * @param array<string>             $certificate_file Certificate file
 * @param string                    $certificates_dir Certificates directory
 * @param array<string>             $processor_params Payment processor parameters
 * @param string                    $action           Action (update/add)
 *
 * @see fn_update_payment
 */
function fn_payment_dependencies_update_payment_post(array $payment_data, $payment_id, $lang_code, array $certificate_file, $certificates_dir, array $processor_params, $action)
{
    if (fn_allowed_for('ULTIMATE') || $action === 'update') {
        return;
    }

    $shipping_ids = db_get_fields('SELECT shipping_id FROM ?:shippings WHERE company_id != ?i', Registry::get('runtime.company_id'));

    foreach ($shipping_ids as $shipping_id) {
        db_replace_into(
            'payment_dependencies',
            [
                'shipping_id'        => $shipping_id,
                'disable_payment_id' => $payment_id,
            ]
        );
    }
}

/**
 * The "update_payment_post" hook handler.
 *
 * Actions performed:
 *  - Delete entries from the 'payment_dependencies' table.
 *
 * @param int  $payment_id Payment identifier
 * @param bool $result     Result
 *
 * @see fn_delete_payment
 */
function fn_payment_dependencies_delete_payment_post($payment_id, $result)
{
    if (!$result) {
        return;
    }

    db_query(
        'DELETE FROM ?:payment_dependencies WHERE disable_payment_id = ?i',
        $payment_id
    );
}

/**
 * The "delete_shipping" hook handler.
 *
 * Actions performed:
 *  - Delete entries from the 'payment_dependencies' table.
 *
 * @param int  $shipping_id Shipping identifier
 * @param bool $result      Result
 *
 * @see fn_delete_shipping
 */
function fn_payment_dependencies_delete_shipping($shipping_id, $result)
{
    if (!$result) {
        return;
    }

    db_query(
        'DELETE FROM ?:payment_dependencies WHERE shipping_id = ?i',
        $shipping_id
    );
}

/**
 * The "prepare_checkout_payment_methods_before_get_payments" hook handler.
 *
 * Actions performed:
 *  - Adds company filtering for payments.
 *
 * @param array<string|bool|int|array<string>> $cart                Array of the cart contents and user information necessary for purchase
 * @param array<string|array<int>>             $auth                Array of user authentication data (e.g. uid, usergroup_ids, etc.)
 * @param string                               $lang_code           Two-letter language code (e.g. 'en', 'ru', etc.)
 * @param bool                                 $get_payment_groups  If set to true, payment methods groupped by category will be returned.
 *                                                                  Otherwise, payment methods will be returned ungroupped
 * @param array<array<string|int>>             $payment_methods     Payment methods
 * @param array{company_ids?: int|array<int>}  $get_payments_params Parameters that are used to fetch payments
 *
 * @see fn_prepare_checkout_payment_methods()
 */
function fn_payment_dependencies_prepare_checkout_payment_methods_before_get_payments(
    $cart,
    $auth,
    $lang_code,
    $get_payment_groups,
    $payment_methods,
    &$get_payments_params
) {
    if (!fn_allowed_for('MULTIVENDOR')) {
        return;
    }

    if (!isset($get_payments_params['company_id'])) {
        $get_payments_params['company_id'] = (int) Registry::get('runtime.company_id');
    }

    if (!empty($get_payments_params['company_ids']) && is_array($get_payments_params['company_ids'])) {
        $get_payments_params['company_ids'][] = (int) $get_payments_params['company_id'];
    } else {
        $get_payments_params['company_ids'] = [(int) $get_payments_params['company_id']];
    }
}

/**
 * The "calculate_cart_content_before_shipping_calculation" hook handler.
 *
 * Actions performed:
 *  - Adds a payments table to the list of tables by witch the checkout cache is updated.
 *
 * @param array<string> $cart                  Cart data
 * @param array<string> $auth                  Authentication data
 * @param string        $calculate_shipping    One-letter flag indicating how to calculate the shipping cost:
 *                                             A - calculate all available methods
 *                                             E - calculate selected methods only (from cart[shipping])
 *                                             S - skip calculation
 * @param bool          $calculate_taxes       Whether taxes should be calculated
 * @param string        $options_style         One-letter flag indicating how to obtain options information:
 *                                             F - full
 *                                             S - skip selection
 *                                             I - info
 * @param bool          $apply_cart_promotions Whether promotions should be applied to the cart
 * @param array<string> $shipping_cache_tables Database tables that cause shipping recalculation
 * @param string        $shipping_cache_key    Shipping rates cache key
 *
 * @see fn_calculate_cart_content
 */
function fn_payment_dependencies_calculate_cart_content_before_shipping_calculation(
    array $cart,
    array $auth,
    $calculate_shipping,
    $calculate_taxes,
    $options_style,
    $apply_cart_promotions,
    array &$shipping_cache_tables,
    $shipping_cache_key
) {
    $shipping_cache_tables[] = 'payments';
}

/**
 * The `get_access_to_checkout` hook handler.
 *
 * Action performed:
 *     - Allows access to checkout if all payment methods were removed by shipping association.
 *
 * @param array<string> $cart            Cart information.
 * @param array<string> $payment_methods Payment methods.
 * @param bool          $access          True if user can access checkout page, false otherwise.
 *
 * @see \fn_get_access_to_checkout()
 *
 * @return void
 */
function fn_payment_dependencies_get_access_to_checkout(array $cart, array $payment_methods, &$access)
{
    if ($access) {
        return;
    }
    if (!empty($payment_methods) || !isset(Tygh::$app['session']['payment_removed'])) {
        return;
    }
    $access = !fn_cart_is_empty($cart);
    if (!$access || fn_notification_exists('extra', 'no_payment_notification')) {
        return;
    }
    fn_set_notification(
        NotificationSeverity::WARNING,
        __('notice'),
        __('pd.there_are_no_available_payment_methods'),
        '',
        'no_payment_notification'
    );
}

/**
 * The `allow_place_order_post` hook handler.
 *
 * Action performed:
 *     - Forbids placing order if there are no available payment methods and some of them were removed by shipping method association.
 *
 * @param array<string> $cart            Array of the cart contents and user information necessary for purchase.
 * @param array<string> $auth            Array with authorization data.
 * @param int|null      $parent_order_id Parent order id.
 * @param int           $total           Order total.
 * @param bool          $result          Flag determines if order can be placed.
 *
 * @see \fn_allow_place_order()
 *
 * @return void
 */
function fn_payment_dependencies_allow_place_order_post(array $cart, array $auth, $parent_order_id, $total, &$result)
{
    if (
        empty($cart) || empty($auth)
    ) {
        return;
    }
    if (!isset(Tygh::$app['session']['payment_removed'])) {
        return;
    }
    $payment_methods = fn_prepare_checkout_payment_methods($cart, $auth);
    $result = !empty($payment_methods);
    unset(Tygh::$app['session']['payment_removed']);
}
