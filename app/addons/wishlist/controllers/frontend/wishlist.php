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
use Tygh\Storage;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

Tygh::$app['session']['wishlist'] = isset(Tygh::$app['session']['wishlist']) ? Tygh::$app['session']['wishlist'] : array();
$wishlist = & Tygh::$app['session']['wishlist'];
Tygh::$app['session']['continue_url'] = isset(Tygh::$app['session']['continue_url']) ? Tygh::$app['session']['continue_url'] : '';
$auth = & Tygh::$app['session']['auth'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Add product to the wishlist
    if ($mode == 'add') {
        // wishlist is empty, create it
        if (empty($wishlist)) {
            $wishlist = array(
                'products' => array()
            );
        }

        $prev_wishlist = $wishlist['products'];

        $product_ids = fn_add_product_to_wishlist($_REQUEST['product_data'], $wishlist, $auth);

        fn_save_cart_content($wishlist, $auth['user_id'], 'W');

        $product_cnt = 0;
        $added_products = array();
        foreach ($wishlist['products'] as $key => $data) {
            if (empty($prev_wishlist[$key]) || !empty($prev_wishlist[$key]) && $prev_wishlist[$key]['amount'] != $data['amount']) {
                $added_products[$key] = $data;
                $added_products[$key]['product_option_data'] = fn_get_selected_product_options_info($data['product_options']);
                if (!empty($prev_wishlist[$key])) {
                    $added_products[$key]['amount'] = $data['amount'] - $prev_wishlist[$key]['amount'];
                }
                $product_cnt += $added_products[$key]['amount'];
            }
        }

        if (defined('AJAX_REQUEST')) {
            if (!empty($added_products)) {
                foreach ($added_products as $key => $data) {
                    $product = fn_get_product_data($data['product_id'], $auth);
                    $product['extra'] = !empty($data['extra']) ? $data['extra'] : array();
                    $product['selected_options'] = $data['product_options'];
                    fn_gather_additional_product_data($product, true, true);
                    $added_products[$key]['product_option_data'] = fn_get_selected_product_options_info($data['product_options']);
                    $added_products[$key]['display_price'] = $product['price'];
                    $added_products[$key]['amount'] = empty($data['amount']) ? 1 : $data['amount'];
                    $added_products[$key]['main_pair'] = fn_get_cart_product_icon($data['product_id'], $data);
                }
                Tygh::$app['view']->assign('added_products', $added_products);

                if (Registry::get('settings.Checkout.allow_anonymous_shopping') == 'hide_price_and_add_to_cart') {
                    Tygh::$app['view']->assign('hide_amount', true);
                }

                $title = __('product_added_to_wl');
                $msg = Tygh::$app['view']->fetch('addons/wishlist/views/wishlist/components/product_notification.tpl');
                fn_set_notification('I', $title, $msg, 'I');
            } else {
                if ($product_ids) {
                    fn_set_notification('W', __('notice'), __('product_in_wishlist'));
                }
            }
        } else {
            unset($_REQUEST['redirect_url']);
        }
    }

    return array(CONTROLLER_STATUS_OK, 'wishlist.view');
}

if ($mode == 'clear') {
    $wishlist = array();

    fn_save_cart_content($wishlist, $auth['user_id'], 'W');

    return array(CONTROLLER_STATUS_REDIRECT, 'wishlist.view');

} elseif ($mode == 'delete' && !empty($_REQUEST['cart_id'])) {
    fn_delete_wishlist_product($wishlist, $_REQUEST['cart_id']);

    fn_save_cart_content($wishlist, $auth['user_id'], 'W');

    return array(CONTROLLER_STATUS_OK, 'wishlist.view');

} elseif ($mode == 'view') {

    fn_add_breadcrumb(__('wishlist_content'));

    if (
        !empty($auth['user_id'])
        && !empty($wishlist['products'])
    ) {
        $wishlist['products'] = fn_wishlist_get_wishlist_from_db($wishlist['products'], $auth['user_id']);
    }

    $products = !empty($wishlist['products']) ? $wishlist['products'] : array();
    $extra_products = array();
    $wishlist_is_empty = fn_cart_is_empty($wishlist);

    if (!empty($products)) {
        list($products, $extra_products) = fn_wishlist_gather_product_data($products, $extra_products, $auth);
        foreach (array_keys($wishlist['products']) as $wishlist_id) {
            if (
                isset($products[$wishlist_id])
                || isset($extra_products[$wishlist_id])
            ) {
                continue;
            }
            unset($wishlist['products'][$wishlist_id]);
        }
    }

    Tygh::$app['view']->assign('show_qty', true);
    Tygh::$app['view']->assign('products', $products);
    Tygh::$app['view']->assign('wishlist_is_empty', $wishlist_is_empty);
    Tygh::$app['view']->assign('extra_products', $extra_products);
    Tygh::$app['view']->assign('wishlist', $wishlist);
    Tygh::$app['view']->assign('continue_url', Tygh::$app['session']['continue_url']);

} elseif ($mode == 'delete_file' && isset($_REQUEST['cart_id'])) {
    if (isset($wishlist['products'][$_REQUEST['cart_id']]['extra']['custom_files'][$_REQUEST['option_id']][$_REQUEST['file']])) {
        // Delete saved custom file
        $file = $wishlist['products'][$_REQUEST['cart_id']]['extra']['custom_files'][$_REQUEST['option_id']][$_REQUEST['file']];

        Storage::instance('custom_files')->delete($file['path']);
        Storage::instance('custom_files')->delete($file['path'] . '_thumb');

        unset($wishlist['products'][$_REQUEST['cart_id']]['extra']['custom_files'][$_REQUEST['option_id']][$_REQUEST['file']]);

        if (defined('AJAX_REQUEST')) {
            fn_set_notification('N', __('notice'), __('text_product_file_has_been_deleted'));
        }
    }

    return array(CONTROLLER_STATUS_REDIRECT, 'wishlist.view');
}
