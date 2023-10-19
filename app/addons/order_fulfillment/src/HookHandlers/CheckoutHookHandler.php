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

class CheckoutHookHandler
{
    /**
     * The `checkout_place_orders_pre_route` hook handler.
     *
     * Action performed:
     *     - Removes specified parameter for session for blocking creation the temporary product group.
     *
     * @param array<string> $cart   Cart information.
     * @param array<string> $auth   Authentication data.
     * @param array<string> $params Request parameters.
     *
     * @see \fn_checkout_place_order()
     *
     * @param-out array<array-key, mixed> $cart
     *
     * @return void
     */
    public function onCheckoutPlaceOrdersPreRoute(array &$cart, array $auth, array $params)
    {
        if (!isset(Tygh::$app['session']['place_order'])) {
            return;
        }

        unset(Tygh::$app['session']['place_order']);
        $cart['calculate_shipping'] = true;
    }
}
