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
use Tygh\Enum\OrderDataTypes;
use Tygh\Enum\ShippingCalculationTypes;
use Tygh\Enum\YesNo;
use Tygh\Registry;
use Tygh\Shippings\Shippings;
use Tygh\Storage;
use Tygh\Tygh;
use Tygh\Enum\ProfileFieldSections;

defined('BOOTSTRAP') or die('Access denied');

/** @var string $controller */
/** @var string $mode */
/** @var array $auth */

fn_enable_checkout_mode();

fn_define('ORDERS_TIMEOUT', 60);

// Cart is empty, create it
if (empty(Tygh::$app['session']['cart'])) {
    fn_clear_cart(Tygh::$app['session']['cart']);
}

/** @var array $cart */
$cart = &Tygh::$app['session']['cart'];

/** @var \Tygh\SmartyEngine\Core $view */
$view = Tygh::$app['view'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($auth['user_id'])) {
        fn_extract_cart_content($cart, $auth['user_id']);
    }

    fn_restore_processed_user_password($_REQUEST['user_data'], $_POST['user_data']);

    //
    // Add product to cart
    //
    if ($mode == 'add') {
        if (empty($auth['user_id']) && Registry::get('settings.Checkout.allow_anonymous_shopping') != 'allow_shopping') {
            return [CONTROLLER_STATUS_REDIRECT, 'auth.login_form?return_url=' . urlencode($_REQUEST['return_url'])];
        }

        /** @var \Tygh\Ajax $ajax */
        $ajax = Tygh::$app['ajax'];
        $product_data = $ajax->getAssignedVar('product_data', []);

        if (
            empty($product_data)
            && !empty($_REQUEST['product_data'])
        ) {
            $product_data = $_REQUEST['product_data'];
        }

        // Add to cart button was pressed for single product on advanced list
        if (!empty($dispatch_extra)) {
            if (empty($product_data[$dispatch_extra]['amount'])) {
                $product_data[$dispatch_extra]['amount'] = 1;
            }
            foreach ($product_data as $key => $data) {
                if ($key != $dispatch_extra && $key != 'custom_files') {
                    unset($product_data[$key]);
                }
            }
        }

        $prev_cart_products = empty($cart['products']) ? [] : $cart['products'];

        fn_add_product_to_cart($product_data, $cart, $auth);

        $previous_state = md5(serialize($cart['products']));
        $cart['change_cart_products'] = true;
        fn_calculate_cart_content($cart, $auth, 'E', true, 'F', true);
        fn_save_cart_content($cart, $auth['user_id']);

        if (md5(serialize($cart['products'])) != $previous_state && empty($cart['skip_notification'])) {
            $product_cnt = 0;
            $added_products = [];
            foreach ($cart['products'] as $key => $data) {
                if (empty($prev_cart_products[$key]) || !empty($prev_cart_products[$key]) && $prev_cart_products[$key]['amount'] != $data['amount']) {
                    $added_products[$key] = $data;
                    $added_products[$key]['product_option_data'] = fn_get_selected_product_options_info($data['product_options']);
                    if (!empty($prev_cart_products[$key])) {
                        $added_products[$key]['amount'] = $data['amount'] - $prev_cart_products[$key]['amount'];
                    }
                    $product_cnt += $added_products[$key]['amount'];
                }
            }

            if (!empty($added_products)) {
                $view->assign('added_products', $added_products);
                if (Registry::get('config.tweaks.disable_dhtml') && Registry::get('config.tweaks.redirect_to_cart')) {
                    $view->assign('continue_url', (!empty($_REQUEST['redirect_url']) && empty($_REQUEST['appearance']['details_page'])) ? $_REQUEST['redirect_url'] : Tygh::$app['session']['continue_url']);
                }

                if (!empty($_REQUEST['notification_products_simple'])) {

                    $msg = '';
                    if ($product_cnt > 1) {
                        $msg = __('products_number_added_to_cart', ['[number]' => $product_cnt]);
                    } else {
                        $msg = __('product_name_added_to_cart', [
                            '[product]' => implode('',
                                array_map(function($added_product) {
                                    return $added_product['product'];},
                                    $added_products
                                )
                            )
                        ]);
                    }
                    fn_set_notification('N', __('notice'), $msg, 'I');
                } else {
                    $msg = $view->fetch('views/checkout/components/product_notification.tpl');
                    fn_set_notification('I',
                        __($product_cnt > 1 ? 'products_added_to_cart' : 'product_added_to_cart'),
                        $msg,
                        'I'
                    );
                }
                $cart['recalculate'] = true;
            } else {
                fn_set_notification('N', __('notice'), __('product_in_cart'));
            }
        }

        unset($cart['skip_notification']);

        if (Registry::get('config.tweaks.disable_dhtml') && Registry::get('config.tweaks.redirect_to_cart') && !defined('AJAX_REQUEST')) {
            if (!empty($_REQUEST['redirect_url']) && empty($_REQUEST['appearance']['details_page'])) {
                Tygh::$app['session']['continue_url'] = fn_url_remove_service_params($_REQUEST['redirect_url']);
            }
            unset($_REQUEST['redirect_url']);
        }

        return [CONTROLLER_STATUS_OK, 'checkout.cart'];
    }

    //
    // Update products quantity in the cart
    //
    if ($mode == 'update') {

        if (!empty($_REQUEST['cart_products'])) {
            foreach ($_REQUEST['cart_products'] as $_key => $_data) {
                if (empty($_data['amount']) && !isset($cart['products'][$_key]['extra']['parent'])) {
                    fn_delete_cart_product($cart, $_key);
                }
            }
            fn_add_product_to_cart($_REQUEST['cart_products'], $cart, $auth, true);
            fn_save_cart_content($cart, $auth['user_id']);
        }

        unset($cart['product_groups']);

        fn_set_notification('N', __('notice'), __('text_products_updated_successfully'));

        // Recalculate cart when updating the products
        if (!empty($cart['chosen_shipping'])) {
            $cart['calculate_shipping'] = true;
        }
        $cart['recalculate'] = true;

        return [CONTROLLER_STATUS_OK, 'checkout.' . $_REQUEST['redirect_mode']];
    }

    //
    // Estimate shipping cost
    //
    if ($mode == 'shipping_estimation') {

        fn_define('ESTIMATION', true);

        $stored_cart = $cart;

        $action = empty($action) ? 'get_rates' : $action; // backward compatibility

        $customer_location = [];
        if ($action == 'get_rates') {
            $customer_location = !empty($_REQUEST['customer_location'])
                ? array_map('trim', $_REQUEST['customer_location'])
                : [];
            Tygh::$app['session']['stored_location'] = $customer_location;
            $shipping_calculation_type = 'A';
        } elseif ($action == 'get_total') {
            $customer_location = Tygh::$app['session']['stored_location'];
            $shipping_calculation_type = 'S';
        }
        foreach ($customer_location as $k => $v) {
            $cart['user_data']['s_' . $k] = $v;
        }

        $cart['recalculate'] = true;

        $cart['chosen_shipping'] = [];

        if (!empty($_REQUEST['shipping_ids'])) {
            fn_checkout_update_shipping($cart, $_REQUEST['shipping_ids']);
            $shipping_calculation_type = 'A';
        }

        list ($cart_products, $product_groups) = fn_calculate_cart_content($cart, $auth, $shipping_calculation_type, true, 'F', true);
        if (Registry::get('settings.Checkout.display_shipping_step') != 'Y' && fn_allowed_for('ULTIMATE')) {
            $view->assign('show_only_first_shipping', true);
        }

        $view->assign('product_groups', $cart['product_groups']);
        $view->assign('cart', $cart);
        $view->assign('cart_products', array_reverse($cart_products, true));
        $view->assign('location', empty($_REQUEST['location']) ? 'cart' : $_REQUEST['location']);
        $view->assign('additional_id', empty($_REQUEST['additional_id']) ? '' : $_REQUEST['additional_id']);

        if (defined('AJAX_REQUEST')) {

            if (fn_is_empty($cart_products) && fn_is_empty($cart['product_groups'])) {
                $additional_id = !empty($_REQUEST['additional_id']) ? '_' . $_REQUEST['additional_id'] : '';
                Tygh::$app['ajax']->assignHtml('shipping_estimation_rates' . $additional_id, '');

                fn_set_notification('W', __('warning'), __('no_rates_for_empty_cart_warning'));
            } else {
                $view->display(
                    empty($_REQUEST['location'])
                        ? 'views/checkout/components/checkout_totals.tpl'
                        : 'views/checkout/components/shipping_estimation.tpl'
                );
            }

            $cart = $stored_cart;
            exit;
        }

        $cart = $stored_cart;
        $redirect_mode = !empty($_REQUEST['current_mode']) ? $_REQUEST['current_mode'] : 'cart';

        return [CONTROLLER_STATUS_OK, 'checkout.' . $redirect_mode . '?show_shippings=Y'];
    }

    if ($mode == 'update_shipping') {
        $shipping_address_changed = false;

        if (!empty(Tygh::$app['session']['stored_location'])) {
            foreach (Tygh::$app['session']['stored_location'] as $k => $v) {
                if (!isset($cart['user_data']['s_' . $k]) || $cart['user_data']['s_' . $k] != $v) {
                    $cart['user_data']['s_' . $k] = $v;
                    $shipping_address_changed = true;
                }
            }
            Tygh::$app['session']['customer_loc'] = Tygh::$app['session']['stored_location'];
        }

        if (!empty($_REQUEST['shipping_ids'])) {
            fn_checkout_update_shipping($cart, $_REQUEST['shipping_ids']);
            fn_calculate_cart_content($cart, $auth, 'A', true, 'F', true);
            fn_delete_notification('shipping_rates_changed');
        }

        // notify guest users about changed address
        if ($shipping_address_changed && empty($auth['user_id'])) {
            fn_set_notification('W', __('important'), __('shipping_address_changed'));
            // Billing and Shipping Address step will be shown
            // if address in checkout estimator is not complete
            unset($cart['edit_step']);
        }

        return [CONTROLLER_STATUS_OK, 'checkout.' . $_REQUEST['redirect_mode']];
    }

    // Apply Discount Coupon
    if ($mode == 'apply_coupon') {
        fn_trusted_vars('coupon_code');

        unset(Tygh::$app['session']['promotion_notices']);
        $cart['pending_coupon'] = fn_strtolower(trim($_REQUEST['coupon_code']));
        $cart['recalculate'] = true;

        return [CONTROLLER_STATUS_OK];
    }

    if ($mode == 'add_profile') {
        $user_data = (array) $_REQUEST['user_data'];

        if (!empty($cart['user_data'])) {
            $user_data += array_filter((array) $cart['user_data']);
        }

        $registration_result = fn_update_user(0, $user_data, $auth, false, true);

        if ($registration_result !== false) {
            list($user_id, $profile_id) = $registration_result;

            $profile_fields = fn_get_profile_fields('O');

            db_query(
                "DELETE FROM ?:user_session_products WHERE session_id = ?s AND type = ?s AND user_type = ?s",
                Tygh::$app['session']->getID(),
                'C', 'U'
            );
            fn_save_cart_content($cart, $user_id);

            fn_login_user($user_id, true);

            $step = 'step_two';
            if (empty($profile_fields['B']) && empty($profile_fields['S'])) {
                $step = 'step_three';
            }

            $suffix = '?edit_step=' . $step;
        } else {
            fn_save_post_data('user_data');
            $suffix = '?login_type=register';
        }

        return [CONTROLLER_STATUS_OK, 'checkout.checkout' . $suffix];
    }

    if ($mode == 'customer_info') {

        $is_anonymous_checkout_disabled = Registry::get('settings.Checkout.disable_anonymous_checkout') === YesNo::YES;

        $params = [];

        if ($is_anonymous_checkout_disabled && empty($auth['user_id'])) {
            fn_save_post_data('user_data');
            $redirect_params = ['login_type' => 'guest'];
        } else {
            $switch_profile = !empty($_REQUEST['profile_id']) && fn_checkout_is_multiple_profiles_allowed($auth);
            if ($switch_profile) {
                $profile_id = $_REQUEST['profile_id'];
                list(, $selectable_profiles) = fn_checkout_get_user_profiles($auth);

                if (empty($selectable_profiles[$profile_id])) {
                    $first_selectable_profile = reset($selectable_profiles);
                    $profile_id = isset($first_selectable_profile['profile_id']) ? $first_selectable_profile['profile_id'] : null;
                }
                if (!empty($profile_id)) {
                    $cart['user_data']['ship_to_another'] = fn_check_shipping_billing($selectable_profiles[$profile_id], fn_get_profile_fields('O'));

                    $params = [
                        'user_data' => $selectable_profiles[$profile_id],
                        'ship_to_another' => $cart['user_data']['ship_to_another'],
                        'force_rendering' => true,
                    ];
                }

                fn_checkout_set_cart_profile_id($cart, $auth, $profile_id);
            }

            list($success, $redirect_params) = fn_checkout_update_steps($cart, $auth, array_merge($_REQUEST, $params));

            if (!$success) {
                if (!empty($cart['user_data']['user_exists'])) {
                    if (defined('AJAX_REQUEST')) {
                        /** @var \Tygh\Ajax $ajax */
                        $ajax = Tygh::$app['ajax'];
                        $ajax->assign('has_errors', true);
                    }

                    $view->assign('email', $cart['user_data']['found_user_email']);

                    fn_set_notification(
                        'I',
                        __('checkout.email_exists.popup.title'),
                        $view->fetch('views/checkout/components/user_exists.tpl')
                    );
                    fn_delete_notification('error_checkout_user_exists');
                }

                return [CONTROLLER_STATUS_REDIRECT, 'checkout.update_steps?' . http_build_query($redirect_params)];
            }

            fn_save_cart_content($cart, $auth['user_id']);
        }

        return [CONTROLLER_STATUS_OK, 'checkout.checkout?' . http_build_query($redirect_params)];
    }

    if ($mode == 'place_order') {

        list($success, $redirect_params) = fn_checkout_update_steps($cart, $auth, $_REQUEST);
        if (!$success) {
            return [CONTROLLER_STATUS_REDIRECT, 'checkout.checkout?' . http_build_query($redirect_params)];
        }

        if (empty($cart['user_data']['email'])) {
            if (empty($auth['user_id'])) {
                $cart['user_data']['email'] = fn_checkout_generate_fake_email_address($cart['user_data'], TIME);
            } else {
                $user_data = fn_get_user_info($auth['user_id'], false);
                $cart['user_data']['email'] =  $user_data['email'];
            }
        }

        $status = fn_checkout_place_order($cart, $auth, $_REQUEST);

        if ($status == PLACE_ORDER_STATUS_TO_CART) {
            return [CONTROLLER_STATUS_REDIRECT, 'checkout.cart'];
        } elseif ($status == PLACE_ORDER_STATUS_DENIED) {
            return [CONTROLLER_STATUS_DENIED];
        }
    }

    if ($mode == 'update_steps') {
        list(, $redirect_params) = fn_checkout_update_steps($cart, $auth, $_REQUEST);

        return [CONTROLLER_STATUS_REDIRECT, 'checkout.checkout?' . http_build_query($redirect_params)];
    }

    if ($mode == 'create_profile') {

        if (!empty($_REQUEST['order_id']) && !empty($auth['order_ids']) && in_array($_REQUEST['order_id'], $auth['order_ids'])) {

            $order_info = fn_get_order_info($_REQUEST['order_id']);
            $user_data = $_REQUEST['user_data'];

            fn_fill_user_fields($user_data);

            foreach ($user_data as $k => $v) {
                // email field sets from request
                if (isset($order_info[$k]) && $k !== 'email') {
                    $user_data[$k] = $order_info[$k];
                }
            }

            if (!empty($order_info['fields'])) {
                $user_data['fields'] = isset($user_data['fields']) ? $user_data['fields'] : [];

                foreach ($order_info['fields'] as $field_id => $field_value) {
                    $user_data['fields'][$field_id] = $field_value;
                }
            }

            if ($res = fn_update_user(0, $user_data, $auth, true, true)) {
                list($user_id) = $res;
                fn_login_user($user_id, true);

                // update email in user orders
                list($orders) = fn_get_orders(['user_id' => $user_id]);
                $order_ids = array_column($orders, 'order_id');
                db_query('UPDATE ?:orders SET email = ?s WHERE order_id IN (?n)', $user_data['email'], $order_ids);

                return [CONTROLLER_STATUS_REDIRECT, 'profiles.success_add'];
            } else {
                return [CONTROLLER_STATUS_REDIRECT, 'checkout.complete?order_id=' . $_REQUEST['order_id']];
            }
        } else {
            return [CONTROLLER_STATUS_DENIED];
        }
    }

    if ($mode == 'update_profile') {
        if (empty($auth['user_id']) || empty($_REQUEST['user_data'])) {
            return [CONTROLLER_STATUS_REDIRECT, 'checkout.checkout'];
        }

        $user_data = $_REQUEST['user_data'];
        $is_creating_new_profile = empty($user_data['profile_id']);
        if ($is_creating_new_profile) {
            $user_data['profile_id'] = 0;
        }

        $profile_id = fn_update_user_profile($auth['user_id'], $user_data);

        if ($is_creating_new_profile || !empty($_REQUEST['switch_after_update'])) {
            fn_checkout_set_cart_profile_id($cart, $auth, $profile_id);
        }

        list(, $redirect_params) = fn_checkout_update_steps($cart, $auth, []);
        if (isset($_REQUEST['ship_to_another'])) {
            $redirect_params['ship_to_another'] = $_REQUEST['ship_to_another'];
        }

        return [CONTROLLER_STATUS_REDIRECT, 'checkout.checkout?' . http_build_query($redirect_params)];
    }

    return [CONTROLLER_STATUS_OK, 'checkout.cart'];
}

//
// Delete discount coupon
//
if ($mode == 'delete_coupon') {
    fn_trusted_vars('coupon_code');
    unset($cart['coupons'][$_REQUEST['coupon_code']], $cart['pending_coupon']);
    $cart['recalculate'] = true;

    if (!empty($cart['chosen_shipping'])) {
        $cart['calculate_shipping'] = true;
    }

    return [CONTROLLER_STATUS_OK];
}

if (empty($mode)) {
    $redirect_mode = empty($_REQUEST['redirect_mode']) ? 'checkout' : $_REQUEST['redirect_mode'];

    return [CONTROLLER_STATUS_REDIRECT, 'checkout.' . $redirect_mode];
}

// FIXME: This section is confusing. It should be wrapped in $mode check
$payment_methods = fn_prepare_checkout_payment_methods($cart, $auth);
// FIXME: Backward compatibility: $payment_methods were assigned in the end of the controller
$view->assign('payment_methods', $payment_methods);

// Cart Items
if ($mode === 'cart') {

    fn_add_breadcrumb(__('cart_contents'));

    list($cart_products, $product_groups) = fn_calculate_cart_content($cart, $auth, 'E', true, 'F', true);

    fn_gather_additional_products_data($cart_products, ['get_icon' => true, 'get_detailed' => true, 'get_options' => true, 'get_discounts' => false]);

    fn_update_payment_surcharge($cart, $auth);

    $cart_products = array_reverse($cart_products, true);
    $view->assign('cart_products', $cart_products);
    $view->assign('product_groups', $cart['product_groups']);

    if (fn_allowed_for('MULTIVENDOR')) {
        $view->assign('take_surcharge_from_vendor', fn_take_payment_surcharge_from_vendor($cart['products']));
    }

    // Check if any outside checkout is enbaled
    if (fn_cart_is_empty($cart) != true) {
        $checkout_buttons = fn_get_checkout_payment_buttons($cart, $cart_products, $auth);
        if (!empty($checkout_buttons)) {
            $view->assign('checkout_add_buttons', $checkout_buttons, false);
        } elseif (empty($payment_methods) && !fn_notification_exists('extra', 'no_payment_notification')) {
            fn_set_notification(
                NotificationSeverity::WARNING,
                __('notice'),
                __('cannot_proccess_checkout_without_payment_methods'),
                '',
                'no_payment_notification'
            );
        }
    }
// All checkout steps
} elseif ($mode === 'checkout') {

    unset(Tygh::$app['session']['place_order']);

    $is_anonymous_checkout_disabled = Registry::get('settings.Checkout.disable_anonymous_checkout') === YesNo::YES;
    if ($is_anonymous_checkout_disabled && empty($auth['user_id'])) {
        return [CONTROLLER_STATUS_REDIRECT, 'checkout.login_form'];
    }

    $checkout_settings = fn_get_checkout_settings($cart);

    if (Registry::get('settings.General.min_order_amount_type') == 'only_products'
        && (!isset($cart['subtotal']) || $checkout_settings['min_order_amount'] > $cart['subtotal'])
    ) {
        /** @var \Tygh\Tools\Formatter $formatter */
        $formatter = Tygh::$app['formatter'];
        $min_amount = $formatter->asPrice($checkout_settings['min_order_amount']);

        fn_set_notification(
            'W',
            __('notice'),
            __('checkout.min_cart_subtotal_required', [
                '[amount]' => $min_amount,
            ])
        );

        return [CONTROLLER_STATUS_REDIRECT, 'checkout.cart'];
    }

    fn_add_breadcrumb(__('checkout'));

    $profile_fields = fn_get_profile_fields('O');

    if (!empty($_REQUEST['shipping_ids'])) {
        fn_checkout_update_shipping($cart, $_REQUEST['shipping_ids']);
    }

    if (!empty($_REQUEST['payment_id'])) {
        $cart = fn_checkout_update_payment($cart, $auth, $_REQUEST['payment_id']);
    }

    $profile_id = $old_profile_id = isset($cart['user_data']['profile_id']) ? $cart['user_data']['profile_id'] : null;
    $allow_multiple_profiles = fn_checkout_is_multiple_profiles_allowed($auth);
    $view->assign('allow_multiple_profiles', $allow_multiple_profiles);

    if ($allow_multiple_profiles) {
        list($user_profiles, $selectable_profiles, $show_profiles_on_checkout) = fn_checkout_get_user_profiles($auth);

        $view->assign([
            'user_profiles'             => $user_profiles,
            'show_profiles_on_checkout' => $show_profiles_on_checkout,
        ]);

        if (empty($selectable_profiles[$profile_id]) && $selectable_profiles) {
            $first_selectable_profile = reset($selectable_profiles);
            $profile_id = isset($first_selectable_profile['profile_id']) ? $first_selectable_profile['profile_id'] : null;
        }

        if ((int) $old_profile_id !== (int) $profile_id) {
            fn_checkout_set_cart_profile_id($cart, $auth, $profile_id);
        }

        list(, $redirect_params) = fn_checkout_update_steps($cart, $auth, []);
    }

    if (!empty($auth['user_id'])
        || (empty($user_data) && isset($cart['user_data']))
    ) {
        $user_data = $cart['user_data'];
    } else {
        $user_data = [];
    }

    // FIXME: #CHECKOUT Backward compatibility
    if (Registry::ifGet('checkout.prefill_address', YesNo::YES) === YesNo::YES) {
        /** @var \Tygh\Location\Manager $manager */
        $manager = Tygh::$app['location'];
        // prefill some address fields from default settings when it's necessary
        list($cart['user_data'],) = $manager->setLocationFromUserData($user_data);
    } else {
        $cart['user_data'] = $user_data;
    }

    if (isset($_REQUEST['is_location_changed'])) {
        $is_location_changed = $_REQUEST['is_location_changed'];
    } else {
        $location_hash = fn_checkout_get_location_hash($cart['user_data'] ?: []);
        $is_location_changed = isset($cart['location_hash']) && $cart['location_hash'] !== $location_hash;
    }

    $shipping_calculation_type = fn_checkout_get_shippping_calculation_type($cart, $is_location_changed);

    $show_unavailable_shippings = YesNo::toBool(Registry::get('settings.Checkout.show_unavailable_shipping_methods'));
    $view->assign('show_unavailable_shippings', $show_unavailable_shippings);

    $cart['keep_chosen_shipping'] = isset($cart['keep_chosen_shipping']) ? $cart['keep_chosen_shipping'] : true;
    $cart['keep_chosen_shipping'] = $cart['keep_chosen_shipping'] && $show_unavailable_shippings;

    list($cart_products, $product_groups) = fn_calculate_cart_content(
        $cart,
        $auth,
        !empty($cart['chosen_shipping']) ? ShippingCalculationTypes::CALCULATE_SELECTED_SHIPPING_METHODS : $shipping_calculation_type,
        true,
        'F'
    );

    if (!empty($_REQUEST['shipping_ids'])) {
        fn_save_cart_content($cart, $auth['user_id']);
    }

    $payment_methods = fn_prepare_checkout_payment_methods($cart, $auth, CART_LANGUAGE);
    if ($payment_methods) {
        $checkout_buttons = fn_get_checkout_payment_buttons($cart, $cart_products, $auth, $payment_methods);
        if ($checkout_buttons) {
            $view->assign('checkout_buttons', $checkout_buttons);
        }
    }

    if ((float) $cart['total'] == 0) {
        $cart['payment_id'] = 0;
    }

    if (empty($cart['payment_id']) && $payment_methods && (float) $cart['total'] != 0) {
        $payment_list = fn_checkout_flatten_payments_list($payment_methods);
        $cart['payment_id'] = reset($payment_list)['payment_id'];
        // recalculate cart after payment method update
        list($cart_products, $product_groups) = fn_calculate_cart_content($cart, $auth, $shipping_calculation_type, true, 'F');
    }
    // If shipping methods changed and shipping step is completed, display notification
    $shipping_hash = fn_get_shipping_hash($cart['product_groups']);

    if (Registry::get('settings.Checkout.display_shipping_step') !== YesNo::NO
        && !empty(Tygh::$app['session']['shipping_hash'])
        && Tygh::$app['session']['shipping_hash'] !== $shipping_hash
        && $cart['shipping_required']
    ) {
        fn_set_notification('W', __('important'), __('text_shipping_rates_changed'), '', 'shipping_rates_changed');

        $view->assign([
            'shipping_rates_changed' => true,
        ]);
    }

    Tygh::$app['session']['shipping_hash'] = $shipping_hash;

    fn_gather_additional_products_data($cart_products, ['get_icon' => true, 'get_detailed' => true, 'get_options' => true, 'get_discounts' => false]);

    // FIXME: #CHECKOUT: backward compatibility
    $completed_steps_legacy = ['step_one' => true, 'step_two' => true, 'step_three' => false, 'step_four' => false];
    fn_set_hook('checkout_select_default_payment_method', $cart, $payment_methods, $completed_steps_legacy, $auth);

    $payment_info = [];
    if (isset($cart['payment_id']) && $cart['payment_id']) {
        $payment_info = fn_get_payment_method_data($cart['payment_id']);
        $cart['payment_method_data'] = $payment_info;

        if (!empty($payment_info['processor_params']['iframe_mode']) && $payment_info['processor_params']['iframe_mode'] == 'Y') {
            $view->assign('iframe_mode', true);
        }
    }

    $cart['payment_surcharge'] = 0;
    if (isset($cart['payment_id']) && $cart['payment_id'] && $payment_info) {
        fn_update_payment_surcharge($cart, $auth);
    }

    if (fn_allowed_for('MULTIVENDOR')) {
        $view->assign('take_surcharge_from_vendor', fn_take_payment_surcharge_from_vendor($cart['products']));
    }

    $cart['ship_to_another'] = $cart['user_data']['ship_to_another'] = $_REQUEST['ship_to_another'] ?? fn_check_shipping_billing($cart['user_data'], $profile_fields);

    fn_checkout_summary($cart);

    if (!empty($cart['failed_order_id']) || !empty($cart['processed_order_id'])) {
        $last_orders = empty($cart['failed_order_id'])
            ? $cart['processed_order_id']
            : $cart['failed_order_id'];
        $last_order_id = reset($last_orders);

        $last_order_payment_info = db_get_field(
            'SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s',
            $last_order_id,
            OrderDataTypes::PAYMENT
        );
        $last_order_payment_info = $last_order_payment_info
            ? unserialize(fn_decrypt_text($last_order_payment_info))
            : [];

        if (!empty($cart['failed_order_id'])) {
            $order_placement_error_message = empty($last_order_payment_info['reason_text'])
                ? __('text_order_placed_error')
                : $last_order_payment_info['reason_text'];
            fn_set_notification('O', '', $order_placement_error_message);
            $cart['processed_order_id'] = $cart['failed_order_id'];
        }

        unset(
            $last_order_payment_info['card_number'],
            $last_order_payment_info['cvv2'],
            $cart['failed_order_id']
        );

        $cart['payment_info'] = $last_order_payment_info;
    }

    if (!empty($cart['extra_payment_info'])) {
        $cart['payment_info'] = empty($cart['payment_info'])
            ? []
            : $cart['payment_info'];
        $cart['payment_info'] = array_merge($cart['payment_info'], $cart['extra_payment_info']);
    }

    fn_add_user_data_descriptions($cart['user_data']);

    if ($payment_methods) {
        $payment_methods = fn_checkout_flatten_payments_list($payment_methods);
    }

    $profile_field_sections = fn_get_profile_fields_sections();

    $location = fn_get_customer_location($auth, $cart);
    $product_groups = Shippings::groupProductsList($cart_products, $location);
    $shippings_group = [];
    foreach ($product_groups as $key_group => $group) {
        $shippings_group[$key_group] = Shippings::getShippingsList($group);
    }

    $all_shippings = $shippings_group;

    if ($auth['user_id']) {
        $profile_id = isset($cart['profile_id']) ? $cart['profile_id'] : null;
        $current_user_data = fn_get_user_info($auth['user_id'], true, $profile_id);
    } else {
        $current_user_data = [];
    }

    $view->assign([
        'user_data'              => $cart['user_data'],
        'profile_fields'         => $profile_fields,
        'profile_field_sections' => $profile_field_sections,
        'payment_info'           => $payment_info,
        'usergroups'             => fn_get_usergroups(['type' => 'C', 'status' => 'A']),
        'countries'              => fn_get_simple_countries(true),
        'states'                 => fn_get_all_states(true),
        'payment_methods'        => $payment_methods,
        'use_ajax'               => 'true',
        'location'               => 'checkout',
        'cart'                   => $cart,
        'cart_products'          => array_reverse($cart_products, true),
        'product_groups'         => $cart['product_groups'],
        'all_shippings'          => $all_shippings,
        'current_user_data'      => $current_user_data,
        'is_terms_and_conditions_agreement_required' => fn_checkout_is_terms_and_conditions_agreement_required(),
    ]);
// Delete product from the cart
} elseif ($mode == 'delete' && isset($_REQUEST['cart_id'])) {

    fn_delete_cart_product($cart, $_REQUEST['cart_id']);

    if (fn_cart_is_empty($cart) == true) {
        fn_clear_cart($cart);
    }

    fn_save_cart_content($cart, $auth['user_id']);

    $cart['recalculate'] = true;
    fn_calculate_cart_content($cart, $auth, 'A', true, 'F', true);

    if (defined('AJAX_REQUEST')) {
        fn_set_notification('N', __('notice'), __('text_product_has_been_deleted'));
    }

    $redirect_mode = empty($_REQUEST['redirect_mode']) ? 'cart' : $_REQUEST['redirect_mode'];

    return [CONTROLLER_STATUS_REDIRECT, 'checkout.' . $redirect_mode];
} elseif ($mode == 'get_custom_file' && isset($_REQUEST['cart_id']) && isset($_REQUEST['option_id']) && isset($_REQUEST['file'])) {
    if (isset($cart['products'][$_REQUEST['cart_id']]['extra']['custom_files'][$_REQUEST['option_id']][$_REQUEST['file']])) {
        $file = $cart['products'][$_REQUEST['cart_id']]['extra']['custom_files'][$_REQUEST['option_id']][$_REQUEST['file']];

        Storage::instance('custom_files')->get($file['path'], $file['name']);
    }
} elseif ($mode == 'delete_file' && isset($_REQUEST['cart_id'])) {

    if (isset($cart['products'][$_REQUEST['cart_id']]['extra']['custom_files'][$_REQUEST['option_id']][$_REQUEST['file']])) {
        // Delete saved custom file
        $product = $cart['products'][$_REQUEST['cart_id']];
        $option_id = $_REQUEST['option_id'];
        $file_id = $_REQUEST['file'];

        $file = $product['extra']['custom_files'][$option_id][$file_id];

        Storage::instance('custom_files')->delete($file['path']);
        Storage::instance('custom_files')->delete($file['path'] . '_thumb');

        unset($product['extra']['custom_files'][$option_id][$file_id]);

        if (!empty($product['extra']['custom_files'][$option_id])) {
            $product['product_options'][$option_id] = md5(serialize($product['extra']['custom_files'][$option_id]));
        } else {
            unset($product['product_options'][$option_id]);
        }
        $product['extra']['product_options'] = empty($product['product_options']) ? [] : $product['product_options'];

        $cart['products'][$_REQUEST['cart_id']] = $product;
    }

    fn_save_cart_content($cart, $auth['user_id']);

    $cart['recalculate'] = true;

    if (defined('AJAX_REQUEST')) {
        fn_set_notification('N', __('notice'), __('text_product_file_has_been_deleted'));
        if (Registry::get('runtime.action') == 'from_status') {
            fn_calculate_cart_content($cart, $auth, 'S', true, 'F', true);
        }
    }

    return [CONTROLLER_STATUS_REDIRECT, 'checkout.' . $_REQUEST['redirect_mode']];
//Clear cart
} elseif ($mode == 'clear') {

    fn_clear_cart($cart);
    fn_save_cart_content($cart, $auth['user_id']);

    return [CONTROLLER_STATUS_REDIRECT, 'checkout.cart'];
//Purge undeliverable products
} elseif ($mode == 'purge_undeliverable') {

    fn_purge_undeliverable_products($cart);
    fn_set_notification('N', __('notice'), __('notice_undeliverable_products_removed'));

    return [CONTROLLER_STATUS_REDIRECT, 'checkout.checkout'];
} elseif ($mode == 'complete') {

    if (!empty($_REQUEST['order_id'])) {
        if (empty($auth['user_id']) && empty($auth['order_ids'])) {
            return [
                CONTROLLER_STATUS_REDIRECT,
                'auth.login_form?return_url=' . urlencode(Registry::get('config.current_url')),
            ];
        }

        if (!fn_is_order_allowed($_REQUEST['order_id'], $auth)) {
            return [CONTROLLER_STATUS_DENIED];
        }

        $order_info = fn_get_order_info($_REQUEST['order_id']);

        if (!empty($order_info['is_parent_order']) && $order_info['is_parent_order'] == 'Y') {
            $child_ids = db_get_fields(
                "SELECT order_id FROM ?:orders WHERE parent_order_id = ?i", $_REQUEST['order_id']
            );
            $order_info['child_ids'] = implode(',', $child_ids);
        }

        if (
            !empty($order_info['email'])
            && fn_checkout_is_email_address_fake($order_info['email'])
        ) {
            // for set new email
            $order_info['email'] = '';
        }

        if (!empty($order_info)) {
            $view->assign('order_info', $order_info);
        }
    }
    fn_add_breadcrumb(__('landing_header'));
} elseif ($mode == 'process_payment') {
    if (fn_allow_place_order($cart, $auth) == true) {
        $order_info = $cart;
        $order_info['products'] = $cart['products'];
        $order_info = fn_array_merge($order_info, $cart['user_data']);
        $order_info['order_id'] = $order_id = TIME . "_" . (!empty($auth['user_id']) ? $auth['user_id'] : 0);
        unset($order_info['user_data']);

        list($is_processor_script, $processor_data) = fn_check_processor_script($order_info['payment_id']);
        if ($is_processor_script) {
            set_time_limit(300);
            fn_define('IFRAME_MODE', true);

            if ($script_path = fn_get_processor_script_path($processor_data['processor_script'])) {
                include($script_path);
            }

            fn_finish_payment($order_id, $pp_response, []);
            fn_order_placement_routines('route', $order_id);
        }
    }
} elseif ($mode == 'login_form') {
    if (defined('AJAX_REQUEST') && empty($auth['user_id'])) {
        Tygh::$app['view']->assign([
            'return_url'   => isset($_REQUEST['return_url']) ? $_REQUEST['return_url'] : null,
            'redirect_url' => isset($_REQUEST['redirect_url']) ? $_REQUEST['redirect_url'] : null,
            'title'        => __('authorize_before_order'),
        ]);

        Tygh::$app['view']->display('views/auth/popup_login_form.tpl');
        exit;
    }

    fn_set_notification('W', __('warning'), __('authorize_before_order'));

    return [CONTROLLER_STATUS_REDIRECT, 'auth.login_form'];
} elseif ($mode == 'update_profile') {
    if (!defined('AJAX_REQUEST')) {
        return [CONTROLLER_STATUS_REDIRECT, 'checkout.checkout'];
    }

    if (!empty($_REQUEST['profile_id'])) {
        $user_profile = fn_get_user_info($auth['user_id'], true, $_REQUEST['profile_id']);
        Tygh::$app['view']->assign([
            'user_profile'        => $user_profile,
            'profile_id'          => $_REQUEST['profile_id'],
            'switch_after_update' => !empty($_REQUEST['switch_after_update']),
        ]);
    }

    $profile_fields = fn_get_profile_fields();
    $countries = fn_get_simple_countries(true, CART_LANGUAGE);
    $states = fn_get_all_states();

    Tygh::$app['view']->assign([
        'countries'       => $countries,
        'states'          => $states,
        'profile_fields'  => $profile_fields,
        'ship_to_another' => !empty($cart['user_data']['ship_to_another']) && $cart['user_data']['ship_to_another'],
    ]);

    Tygh::$app['view']->display('views/checkout/components/profile.tpl');
    exit;
}
if (
    !in_array(
        $mode,
        ['clear', 'delete', 'cart', 'update', 'apply_coupon', 'shipping_estimation', 'update_shipping', 'complete']
    )
    && !fn_get_access_to_checkout($cart, $payment_methods)
) {
    if (empty($payment_methods)) {
        fn_set_notification(
            NotificationSeverity::WARNING,
            __('notice'),
            __('cannot_proccess_checkout_without_payment_methods'),
            'K',
            'no_payment_notification'
        );
    } else {
        fn_set_notification(NotificationSeverity::WARNING, __('cart_is_empty'), __('cannot_proccess_checkout'), 'K', 'cannot_proccess_checkout');
    }
    $force_redirection = 'checkout.cart';
    if (defined('AJAX_REQUEST')) {
        Tygh::$app['ajax']->assign('force_redirection', fn_url($force_redirection));
        return [CONTROLLER_STATUS_NO_CONTENT];
    }
    return [CONTROLLER_STATUS_REDIRECT, $force_redirection];
}

// FIXME: #CHECKOUT: Are $profile_fields required anywhere but on 'checkout.checkout'?
if (!empty($profile_fields)) {
    $view->assign('profile_fields', $profile_fields);
}

$view->assign('cart', $cart);
$view->assign(
    'continue_url',
    empty(Tygh::$app['session']['continue_url'])
        ? ''
        : Tygh::$app['session']['continue_url']
);
$view->assign('mode', $mode);

// Remember mode for the check shipping rates
Tygh::$app['session']['checkout_mode'] = $mode;
