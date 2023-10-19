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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    return [CONTROLLER_STATUS_OK];
}

if ($mode == 'details') {
    $downloads_exist = Tygh::$app['view']->getTemplateVars('downloads_exist');
    if (!$downloads_exist) {
        return [CONTROLLER_STATUS_OK];
    }

    $order_info = Tygh::$app['view']->getTemplateVars('order_info');
    $downloads_exist = fn_gift_certificate_has_downloadable_products_in_order($order_info);


    if (!$downloads_exist) {
        Registry::del('navigation.tabs.downloads');
    }

    return [CONTROLLER_STATUS_OK];
}
