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

namespace Tygh\Addons\OrderFulfillment\HookHandlers;

use Tygh\Tygh;

class CartHookHandler
{
    /**
     * The `post_add_to_cart` hook handler.
     *
     * Action performed:
     *    - Deny unsetting product groups in the cart, at placing order moment.
     *
     * @param array<string>       $product_data Product data.
     * @param array<string, bool> $cart         Cart data.
     *
     * @see fn_add_product_to_cart()
     *
     * @return void
     */
    public function onPostAddToCart(array $product_data, array &$cart)
    {
        //phpcs:ignore
        if (!empty(Tygh::$app['session']['place_order'])) {
            $cart['deny_unsetting_product_group'] = true;
        }
    }

    /**
     * The `delete_cart_product` hook handler.
     *
     * Action performed:
     *    - Deny unsetting product groups in the cart, at placing order moment.
     *
     * @param array<string, bool> $cart Cart data.
     *
     * @see fn_delete_cart_product()
     *
     * @return void
     */
    public function onDeleteCartProduct(array &$cart)
    {
        //phpcs:ignore
        if (!empty(Tygh::$app['session']['place_order'])) {
            $cart['deny_unsetting_product_group'] = true;
        }
    }
}
