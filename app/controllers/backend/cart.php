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
use Tygh\Tygh;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'm_delete') {
        if (!empty($_REQUEST['user_ids'])) {
            if (fn_allowed_for('ULTIMATE')) {
                foreach ($_REQUEST['user_ids'] as $company_id => $user_ids) {
                    fn_delete_user_cart($user_ids, $company_id);
                }
            } else {
                fn_delete_user_cart($_REQUEST['user_ids'], '', isset($_REQUEST['storefront_id']) ? $_REQUEST['storefront_id'] : 0);
            }
        }
    }

    if ($mode == 'delete') {
        $user_ids = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : 0;
        $data = isset($_REQUEST['company_id']) ? $_REQUEST['company_id'] : null;
        $storefront_id = isset($_REQUEST['storefront_id']) ? $_REQUEST['storefront_id'] : 0;

        if ($user_ids) {
            fn_delete_user_cart($user_ids, $data, $storefront_id);
        }
    }

    if ($mode == 'm_delete_all') {
        if (!empty(Tygh::$app['session']['abandoned_carts'])) {
            if (fn_allowed_for('ULTIMATE')) {
                foreach (Tygh::$app['session']['abandoned_carts'] as $company_id => $user_ids) {
                    fn_delete_user_cart($user_ids, $company_id);
                }
            } else {
                fn_delete_user_cart(Tygh::$app['session']['abandoned_carts']);
            }
        }
    }

    if ($mode == 'convert_to_order') {
        if (empty($_REQUEST['user_id'])) {
            return [CONTROLLER_STATUS_REDIRECT, 'cart.cart_list'];
        }

        list($cart, $customer_auth) = fn_form_cart_from_abandoned($_REQUEST);

        Tygh::$app['session']['cart'] = $cart;
        Tygh::$app['session']['customer_auth'] = $customer_auth;

        $redirect_url = 'order_management.add';

        if (!empty($_REQUEST['storefront_id'])) {
            $redirect_url .= "?storefront_id={$_REQUEST['storefront_id']}";
        }

        if (!empty($cart['abandoned_cart_company_id']) && fn_allowed_for('ULTIMATE') && !Registry::get('runtime.simple_ultimate')) {
            if (!empty($_REQUEST['storefront_id'])) {
                $redirect_url .= "&switch_company_id={$cart['abandoned_cart_company_id']}";
            } else {
                $redirect_url .= "?switch_company_id={$cart['abandoned_cart_company_id']}";
            }
        }

        return [CONTROLLER_STATUS_REDIRECT, $redirect_url];
    }

    return [CONTROLLER_STATUS_OK, 'cart.cart_list'];
}

if ($mode == 'cart_list') {
    $item_types = fn_get_cart_content_item_types();
    $params = array_merge($_REQUEST, [
        'check_shipping_billing' => true
    ]);

    list($carts_list, $search, $user_ids) = fn_get_carts(
        $params,
        Registry::get('settings.Appearance.admin_elements_per_page')
    );

    foreach ($carts_list as $key => $cart) {
        $cart_extra = unserialize($cart['extra']);
        if (!empty($cart_extra['user_data'])) {
            $carts_list[$key]['user_data'] = $cart_extra['user_data'];
        }
    }

    Tygh::$app['view']->assign('countries', fn_get_simple_countries(true));
    Tygh::$app['view']->assign('states', fn_get_all_states());
    Tygh::$app['view']->assign('carts_list', $carts_list);
    Tygh::$app['view']->assign('search', $search);

    Tygh::$app['session']['abandoned_carts'] = $user_ids;

    if (!empty($_REQUEST['user_id'])) {
        $cart_products = fn_get_cart_products($_REQUEST['user_id'], $_REQUEST);
        Tygh::$app['view']->assign('cart_products', $cart_products);
        Tygh::$app['view']->assign('sl_user_id', $_REQUEST['user_id']);
    }

    if (fn_allowed_for('MULTIVENDOR')) {
        Tygh::$app['view']->assign('selected_storefront_id', empty($_REQUEST['storefront_id']) ? 0 : (int) $_REQUEST['storefront_id']);
    }
}
