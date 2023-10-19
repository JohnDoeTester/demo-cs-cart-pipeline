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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'change_variation') {
        $cart = Tygh::$app['session']['cart'];
        $auth = Tygh::$app['session']['auth'];

        $cart_item_id = isset($_REQUEST['cart_item_id']) ? (string) $_REQUEST['cart_item_id'] : null;
        $product_id = isset($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : null;

        $custom_files = [];
        $product_data = [
            $product_id => [
                'product_id' => $product_id,
                'amount'     => 1,
            ]
        ];

        if (!empty($cart['products'][$cart_item_id]['amount'])) {
            $product_data[$product_id]['amount'] = (int) $cart['products'][$cart_item_id]['amount'];
        }

        if (!empty($cart['products'][$cart_item_id]['product_options'])) {
            $product_options = fn_get_product_options($product_id);

            $product_data[$product_id]['extra'] = [];
            $product_data[$product_id]['product_options'] = array_intersect_key($cart['products'][$cart_item_id]['product_options'], $product_options);

            if (!empty($cart['products'][$cart_item_id]['extra']['custom_files'])) {
                foreach ($cart['products'][$cart_item_id]['extra']['custom_files'] as $option_id => $custom_file) {
                    if (!isset($product_options[$option_id])) {
                        continue;
                    }

                    $custom_files[$option_id] = $custom_file;
                    unset($cart['products'][$cart_item_id]['extra']['custom_files'][$option_id]);
                }
            }
        }
        $cart['prev_variation_position'] = array_search($cart_item_id, array_keys($cart['products']));
        fn_delete_cart_product($cart, $cart_item_id);

        if ($cart_item_ids = fn_add_product_to_cart($product_data, $cart, $auth)) {
            reset($cart_item_ids);
            $cart_item_id = key($cart_item_ids);
            $cart['products'][$cart_item_id]['extra']['custom_files'] = $custom_files;

            Tygh::$app['session']['cart'] = $cart;
        }

        fn_save_cart_content(Tygh::$app['session']['cart'], $auth['user_id']);

        return [CONTROLLER_STATUS_REDIRECT, 'checkout.cart'];
    }
}

return [CONTROLLER_STATUS_OK];
