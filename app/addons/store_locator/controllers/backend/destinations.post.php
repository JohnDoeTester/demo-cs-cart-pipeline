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

if (in_array($mode, ['add', 'update', 'manage'])) {
    $dynamic_sections = Registry::ifGet('navigation.dynamic.sections', []);
    $dynamic_sections['store_locator'] = [
        'title' => __('store_locator'),
        'href'  => 'store_locator.manage',
    ];
    Registry::set('navigation.dynamic.sections', $dynamic_sections);
}

if ($mode === 'update') {
    $tabs = Registry::ifGet('navigation.tabs', []);
    $tabs['pickup'] = [
        'title' => __('store_locator.pickup_locations'),
        'js'    => true,
    ];
    Registry::set('navigation.tabs', $tabs);

    $params = [
        'pickup_destination_id' => $_REQUEST['destination_id'],
    ];

    if (fn_allowed_for('MULTIVENDOR') && Registry::get('runtime.company_id')) {
        $params['company_id'] = Registry::get('runtime.company_id');
    }

    $stores = [];
    if (!empty($_REQUEST['destination_id'])) {
        list($stores,) = fn_get_store_locations(
            $params,
            0,
            DESCR_SL
        );
    }

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];
    $view->assign('pickup_locations', $stores);
}

return [CONTROLLER_STATUS_OK];
