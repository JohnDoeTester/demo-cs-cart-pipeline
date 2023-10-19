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

if ($mode == 'update' && !isset($_REQUEST['is_ajax'])) {

    $_cart = Tygh::$app['session']['cart'];

    if (!empty($_cart['order_id'])) {
        $old_ship_data = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s", $_cart['order_id'], 'L');
        if (!empty($old_ship_data)) {
            $old_ship_data = unserialize($old_ship_data);
            foreach($old_ship_data as $group_key => $shipping) {
                if (!empty($shipping['module']) && $shipping['module'] == 'store_locator' && !empty($shipping['store_location_id'])) {

                    Tygh::$app['session']['cart']['select_store'][$shipping['group_key']][$shipping['shipping_id']] = $shipping['store_location_id'];

                    Tygh::$app['view']->assign('old_ship_data', $old_ship_data);
                }
            }
        }
    }
}

if ($mode == "update_shipping") {
    if (!empty($_REQUEST['shipping_ids'])) {
        foreach($_REQUEST['shipping_ids'] as $group_key => $shiping_id) {
            if (!empty($_REQUEST['select_store'][$group_key][$shiping_id])) {
                Tygh::$app['session']['cart']['select_store'][$group_key][$shiping_id] = $_REQUEST['select_store'][$group_key][$shiping_id];
            }
        }
    }
}
