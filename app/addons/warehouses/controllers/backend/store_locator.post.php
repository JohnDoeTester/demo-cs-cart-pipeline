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

use Tygh\Addons\Warehouses\ServiceProvider;
use Tygh\Registry;
use Tygh\Addons\Warehouses\Manager;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    return [CONTROLLER_STATUS_OK];
}

if (in_array($mode, ['add', 'update', 'manage'])) {
    Registry::set('navigation.tabs.pickup.title', __('warehouses.settings'));
    Tygh::$app['view']->assign('store_types', ServiceProvider::getStoreTypes());
}
