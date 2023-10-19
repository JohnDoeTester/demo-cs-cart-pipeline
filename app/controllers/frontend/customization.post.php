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
use Tygh\Enum\UserTypes;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

/** @var string $mode */
/** @var array $auth */

if ($mode === 'edit_checkout') {
    if (!Registry::get('runtime.customization_mode.block_manager')) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    if (!UserTypes::isAdmin($auth['user_type'])) {
        return [CONTROLLER_STATUS_DENIED];
    }

    if (empty(Tygh::$app['session']['cart'])) {
        fn_clear_cart(Tygh::$app['session']['cart']);
    }

    $cart = &Tygh::$app['session']['cart'];

    if (!empty($cart['products'])) {
        return [CONTROLLER_STATUS_OK, 'checkout.checkout'];
    }

    list($products, ) = fn_get_products(['amount_from' => '1'], 1, CART_LANGUAGE);

    $product = reset($products);

    if (!empty($product)) {
        fn_add_product_to_cart(
            [
                $product['product_id'] => [
                    'product_id'      => $product['product_id'],
                    'amount'          => 1
                ],
            ],
            $cart,
            $auth
        );
        fn_save_cart_content($cart, $auth['user_id']);
    }

    if (empty($cart['products'])) {
        fn_set_notification(
            NotificationSeverity::ERROR,
            __('error'),
            __('block_manager.can_not_add_product')
        );
        return [CONTROLLER_STATUS_OK, 'index.index'];
    }

    return [CONTROLLER_STATUS_OK, 'checkout.checkout'];
}
