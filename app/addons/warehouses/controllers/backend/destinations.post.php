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

use Tygh\Addons\Warehouses\Manager;
use Tygh\Addons\Warehouses\ServiceProvider;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

if ($mode === 'update') {
    $tabs = Registry::ifGet('navigation.tabs', []);
    $tabs['warehouses'] = [
        'title' => __('warehouses.stores_and_warehouses'),
        'js'    => true,
    ];
    Registry::set('navigation.tabs', $tabs);

    $params = [
        'shipping_destination_id' => $_REQUEST['destination_id'],
        'store_types'             => [
            Manager::STORE_LOCATOR_TYPE_STORE,
            Manager::STORE_LOCATOR_TYPE_WAREHOUSE,
        ],
        'sort_by'                 => 'destination_position_name',
        'sort_order'              => 'asc',
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

    $view->assign(
        [
            'stores'      => $stores,
            'store_types' => ServiceProvider::getStoreTypes(),
        ]
    );
}

if ($mode === 'manage') {
    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    /** @var array $destinations */
    $destinations = $view->getTemplateVars('destinations');

    $store_location_params = [
        'store_types'              => [
            Manager::STORE_LOCATOR_TYPE_STORE,
            Manager::STORE_LOCATOR_TYPE_WAREHOUSE,
        ],
    ];

    if (fn_allowed_for('MULTIVENDOR') && Registry::get('runtime.company_id')) {
        $store_location_params['company_id'] = Registry::get('runtime.company_id');
    }

    $destinations = array_map(
        static function ($destination) use ($store_location_params) {
            $store_location_params['shipping_destination_id'] = $destination['destination_id'];
            list(, $params) = fn_get_store_locations(
                $store_location_params,
                1
            );
            $destination['store_count'] = $params['total_items'];

            return $destination;
        },
        $destinations
    );

    $view->assign('destinations', $destinations);
}

if ($mode === 'picker') {
    list($objects, $total_objects) = fn_warehouses_get_destinations_for_picker($_REQUEST);

    /** @var \Tygh\Ajax $ajax */
    $ajax = Tygh::$app['ajax'];
    $ajax->assign('objects', $objects);
    $ajax->assign('total_objects', $total_objects);
    exit(0);
}

return [CONTROLLER_STATUS_OK];
