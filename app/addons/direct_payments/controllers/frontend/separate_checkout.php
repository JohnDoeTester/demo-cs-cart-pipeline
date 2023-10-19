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
use Tygh\Enum\OptionsCalculationTypes;
use Tygh\Enum\ShippingCalculationTypes;
use Tygh\Registry;
use Tygh\Tygh;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

fn_enable_checkout_mode();

/** @var \Tygh\Addons\DirectPayments\Cart\Service $cart_service */
$cart_service = Tygh::$app['addons.direct_payments.cart.service'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($auth['user_id'])) {
    $cart_service->load($auth['user_id']);
}

if (isset($_REQUEST['vendor_id'])) {
    $cart_service->setCurrentVendorId((int) $_REQUEST['vendor_id']);
}

/** @var array $cart */
$cart = &$cart_service->getCart();

/** @var \Tygh\SmartyEngine\Core $view */
$view = Tygh::$app['view'];

/** @var array $auth */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    fn_restore_processed_user_password($_REQUEST['user_data'], $_POST['user_data']);

    //
    // Add product to cart
    //
    if ($mode == 'add') {
        if (empty($auth['user_id']) && Registry::get('settings.Checkout.allow_anonymous_shopping') != 'allow_shopping') {
            return array(CONTROLLER_STATUS_REDIRECT, 'auth.login_form?return_url=' . urlencode($_REQUEST['return_url']));
        }

        // Add to cart button was pressed for single product on advanced list
        if (!empty($dispatch_extra)) {
            if (empty($_REQUEST['product_data'][$dispatch_extra]['amount'])) {
                $_REQUEST['product_data'][$dispatch_extra]['amount'] = 1;
            }
            foreach ($_REQUEST['product_data'] as $key => $data) {
                if ($key != $dispatch_extra && $key != 'custom_files') {
                    unset($_REQUEST['product_data'][$key]);
                }
            }
        }

        $group_products = $cart_service->getGroupProducts($_REQUEST['product_data']);

        foreach ($group_products as $vendor_id => $products) {
            if (empty($products[key($products)]['product_id'])) {
                break;
            }
            $cart_service->setCurrentVendorId($vendor_id);
            $cart = & $cart_service->getCart($vendor_id);

            $prev_cart_products = empty($cart['products']) ? array() : $cart['products'];

            fn_add_product_to_cart($products, $cart, $auth);

            $previous_state = md5(serialize($cart['products']));
            $cart['change_cart_products'] = true;
            fn_calculate_cart_content($cart, $auth, 'E', true, 'F', true);
            $cart_service->save($auth['user_id']);

            if (md5(serialize($cart['products'])) != $previous_state && empty($cart['skip_notification'])) {
                $product_cnt = 0;
                $added_products = array();
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
                    $view->assign('vendor_id', $vendor_id);
                    $view->assign('cart', $cart);
                    $mini_cart = fn_direct_payments_get_mini_cart();
                    $view->assign('amount', $mini_cart['amount']);
                    $view->assign('display_subtotal', $mini_cart['display_subtotal']);
                    $view->assign('proceed_to_checkout_href', 'checkout.checkout&vendor_id=' . $vendor_id);

                    if (Registry::get('config.tweaks.disable_dhtml') && Registry::get('config.tweaks.redirect_to_cart')) {
                        $view->assign('continue_url', (!empty($_REQUEST['redirect_url']) && empty($_REQUEST['appearance']['details_page'])) ? $_REQUEST['redirect_url'] : Tygh::$app['session']['continue_url']);
                    }

                    $msg = $view->fetch('views/checkout/components/product_notification.tpl');
                    fn_set_notification('I', __($product_cnt > 1 ? 'products_added_to_cart' : 'product_added_to_cart'), $msg, 'I');
                    $cart['recalculate'] = true;
                } else {
                    fn_set_notification('N', __('notice'), __('product_in_cart'));
                }
            }

            unset($cart['skip_notification']);

        }

        if (Registry::get('config.tweaks.disable_dhtml') && Registry::get('config.tweaks.redirect_to_cart') && !defined('AJAX_REQUEST')) {
            if (!empty($_REQUEST['redirect_url']) && empty($_REQUEST['appearance']['details_page'])) {
                Tygh::$app['session']['continue_url'] = fn_url_remove_service_params($_REQUEST['redirect_url']);
            }
            unset($_REQUEST['redirect_url']);
        }

        return array(CONTROLLER_STATUS_OK, 'checkout.cart');
    }

    //
    // Update products quantity in the cart
    //
    if ($mode == 'update') {

        if (!empty($_REQUEST['cart_products'])) {
            $group_products = $cart_service->getGroupProducts($_REQUEST['cart_products']);

            foreach ($group_products as $vendor_id => $products) {
                $cart_service->setCurrentVendorId($vendor_id);
                $cart = & $cart_service->getCart($vendor_id);

                foreach ($products as $_key => $_data) {
                    if (empty($_data['amount']) && !isset($cart['products'][$_key]['extra']['parent'])) {
                        fn_delete_cart_product($cart, $_key);
                    }
                }
                fn_add_product_to_cart($products, $cart, $auth, true);
                fn_calculate_cart_content($cart, $auth, ShippingCalculationTypes::SKIP_CALCULATION, true, OptionsCalculationTypes::FULL, true);
                fn_save_cart_content($cart, $auth['user_id']);
                unset($cart['product_groups']);

                // Recalculate cart when updating the products
                if (!empty($cart['chosen_shipping'])) {
                    $cart['calculate_shipping'] = true;
                }
                $cart['recalculate'] = true;
            }
        }

        fn_set_notification('N', __('notice'), __('text_products_updated_successfully'));

        return array(CONTROLLER_STATUS_OK, 'checkout.' . $_REQUEST['redirect_mode']);

    }

    //
    // Estimate shipping cost
    //
    if ($mode == 'shipping_estimation') {

        fn_define('ESTIMATION', true);

        $stored_cart = $cart;

        $action = empty($action) ? 'get_rates' : $action; // backward compatibility

        $customer_location = array();
        if ($action == 'get_rates') {
            $customer_location = !empty($_REQUEST['customer_location'])
                ? array_map('trim', $_REQUEST['customer_location'])
                : array();
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

        $cart['chosen_shipping'] = array();

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
        $view->assign('vendor_id', empty($_REQUEST['vendor_id']) ? '' : $_REQUEST['vendor_id']);

        if (defined('AJAX_REQUEST')) {

            if (fn_is_empty($cart_products) && fn_is_empty($cart['product_groups'])) {
                $additional_id = !empty($_REQUEST['additional_id']) ? '_' . $_REQUEST['additional_id'] : '';
                Tygh::$app['ajax']->assignHtml('shipping_estimation_rates' . $additional_id, '');

                fn_set_notification('W', __('warning'), __('no_rates_for_empty_cart_warning'));
            } else {
                $view->display(
                    empty($_REQUEST['location'])
                    ? 'addons/direct_payments/views/separate_checkout/components/checkout_totals.tpl'
                    : 'addons/direct_payments/views/separate_checkout/components/shipping_estimation.tpl'
                );
            }

            $cart = $stored_cart;
            exit;
        }

        $cart = $stored_cart;
        $redirect_mode = !empty($_REQUEST['current_mode']) ? $_REQUEST['current_mode'] : 'cart';

        return array(CONTROLLER_STATUS_OK, 'checkout.' . $redirect_mode . '?show_shippings=Y');
    }
}

// Cart Items
if ($mode === 'cart') {

    fn_add_breadcrumb(__('cart_contents'));

    $carts = & $cart_service->getCarts();
    $group_product_groups = $group_cart_products = [];
    $group_checkout_buttons = $group_take_surcharge_from_vendor = [];
    $group_payment_methods = [];
    $carts_total = 0;

    foreach ($carts as $vendor_id => &$cart) {
        // for promotions
        $cart_service->setCurrentVendorId($cart['vendor_id']);

        if (!fn_cart_is_empty($cart)) {
            list($cart_products, $product_groups) = fn_calculate_cart_content($cart, $auth, 'E', true, 'F', true);

            fn_gather_additional_products_data($cart_products, array('get_icon' => true, 'get_detailed' => true, 'get_options' => true, 'get_discounts' => false));

            fn_update_payment_surcharge($cart, $auth);

            $cart_products = array_reverse($cart_products, true);

            $group_cart_products[$vendor_id] = $cart_products;
            $group_product_groups[$vendor_id] = $cart['product_groups'];
            $group_checkout_buttons[$vendor_id] = fn_get_checkout_payment_buttons($cart, $cart_products, $auth);

            if (fn_allowed_for('MULTIVENDOR')) {
                $group_take_surcharge_from_vendor[$vendor_id] = fn_take_payment_surcharge_from_vendor($cart['products']);
            }

            $carts_total += floatval($carts[$vendor_id]['total']);
            $group_payment_methods[$vendor_id] = fn_prepare_checkout_payment_methods($cart, $auth);
        }
    }
    unset($cart);

    $view->assign([
        'group_cart_products'        => $group_cart_products,
        'product_groups'             => $group_product_groups,
        'group_checkout_add_buttons' => $group_checkout_buttons,
        'group_payment_methods'      => $group_payment_methods,
        'carts_total'                => $carts_total,
    ]);

    if (fn_allowed_for('MULTIVENDOR')) {
        $view->assign('group_take_surcharge_from_vendor', $group_take_surcharge_from_vendor);
    }
} elseif ($mode === 'delete' && isset($_REQUEST['cart_id'])) {
    $vendor_id = isset($_REQUEST['vendor_id']) ? $_REQUEST['vendor_id'] : $cart['vendor_id'];
    fn_delete_cart_product($cart, $_REQUEST['cart_id']);

    if ($cart_service->isEmpty()) {
        $cart_service->clear();
    }
    $cart['vendor_id'] = $vendor_id;
    fn_save_cart_content($cart, $auth['user_id']);

    $cart['recalculate'] = true;
    fn_calculate_cart_content($cart, $auth, 'A', true, 'F', true);

    if (defined('AJAX_REQUEST')) {
        fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('text_product_has_been_deleted'));
    }

    $redirect_mode = empty($_REQUEST['redirect_mode']) ? 'cart' : $_REQUEST['redirect_mode'];

    return [CONTROLLER_STATUS_REDIRECT, 'checkout.' . $redirect_mode];
}

fn_direct_payments_bootstrap_checkout_data($cart_service, $view, $auth);

$view->assign(
    'continue_url',
    empty(Tygh::$app['session']['continue_url'])
        ? ''
        : Tygh::$app['session']['continue_url']
);
