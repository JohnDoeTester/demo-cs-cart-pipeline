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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode === 'complete') {
    $is_event = isset(Tygh::$app['session']['facebook_pixel']['order_placed']) ? Tygh::$app['session']['facebook_pixel']['order_placed'] : false;
    if ($is_event) {
        Tygh::$app['view']->assign('fb_track_order_placed_event', true);
        unset(Tygh::$app['session']['facebook_pixel']['order_placed']);

        if (!empty($_REQUEST['order_id'])) {
            $order_info = fn_get_order_info($_REQUEST['order_id']);

            Tygh::$app['view']->assign('fb_order_total', $order_info['total']);
        }
    }
}
