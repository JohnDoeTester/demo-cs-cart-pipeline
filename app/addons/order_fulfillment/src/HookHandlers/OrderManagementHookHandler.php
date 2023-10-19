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

class OrderManagementHookHandler
{
    /**
     * The 'place_order_manually_post' hook handler.
     *
     * Action performed:
     *    - Removes specified parameter for session for blocking creation the temporary product group.
     *
     * @see \fn_place_order_manually()
     *
     * @return void
     */
    public function onPlaceOrderManuallyPost()
    {
        if (!isset(Tygh::$app['session']['place_order'])) {
            return;
        }

        unset(Tygh::$app['session']['place_order']);
    }
}
