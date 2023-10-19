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

use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && $mode === 'place_order'
    && $action === 'instant_payment'
) {
    $cart = &Tygh::$app['session']['cart'];
    $auth = &Tygh::$app['session']['auth'];

    if (isset($_REQUEST['user_data'])) {
        list($cart, $auth) = fn_checkout_update_user_data($cart, $auth, $_REQUEST['user_data'], false, $auth['user_id']);
    }

    fn_clear_cart($cart);

    foreach ($_REQUEST['products'] as $product) {
        $product_id = $product['product_id'];
        $product_options = empty($product['product_options'])
            ? []
            : $product['product_options'];

        fn_add_product_to_cart([
            $product['product_id'] => [
                'product_id'      => $product_id,
                'amount'          => 1,
                'product_options' => $product_options,
            ],
        ], $cart, $auth, false);
    }

    fn_calculate_cart_content($cart, $auth);
    fn_save_cart_content($cart, $auth['user_id']);
}
