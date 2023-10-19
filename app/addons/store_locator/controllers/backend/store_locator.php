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

use Tygh\Enum\ObjectStatuses;
use Tygh\Registry;
use Tygh\Tygh;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $suffix = '';
    fn_trusted_vars('store_locations', 'store_location_data');

    if ($mode == 'update') {

        $store_location_id = fn_update_store_location($_REQUEST['store_location_data'], $_REQUEST['store_location_id'], DESCR_SL);

        if (empty($store_location_id)) {
            $suffix = ".manage";
        } else {
            $suffix = ".update?store_location_id=$store_location_id";
        }
    }

    if ($mode == 'delete') {
        if (!empty($_REQUEST['store_location_id'])) {
            fn_delete_store_location($_REQUEST['store_location_id']);
        }
        $suffix = '.manage';
    }

    if ($mode === 'm_update') {
        if (!empty($_REQUEST['store_locators']) && is_array($_REQUEST['store_locators'])) {
            foreach ($_REQUEST['store_locators'] as $id => $stores) {
                fn_update_store_location($stores, $id);
            }
        }

        $suffix .= '.manage';
    }

    if ($mode === 'm_delete') {
        if (!empty($_REQUEST['store_locator_ids'])) {
            foreach ($_REQUEST['store_locator_ids'] as $store_location_id) {
                fn_delete_store_location($store_location_id);
            }
        }
        $suffix = '.manage';
    }

    if (
        $mode === 'm_update_statuses'
        && !empty($_REQUEST['store_locator_ids'])
        && !empty($_REQUEST['status'])
    ) {
        $status_to = $_REQUEST['status'];

        foreach ((array) $_REQUEST['store_locator_ids'] as $store_locator_id) {
            fn_tools_update_status(
                [
                    'table'             => 'store_locations',
                    'status'            => $status_to,
                    'id_name'           => 'store_location_id',
                    'id'                => $store_locator_id,
                    'show_error_notice' => false,
                ]
            );
        }

        if (defined('AJAX_REQUEST')) {
            $redirect_url = fn_url('store_locator.manage');
            if (isset($_REQUEST['redirect_url'])) {
                $redirect_url = $_REQUEST['redirect_url'];
            }
            Tygh::$app['ajax']->assign('force_redirection', $redirect_url);
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            return [CONTROLLER_STATUS_NO_CONTENT];
        }

        $suffix = '.manage';
    }

    if (
        $mode === 'm_update_pickup'
        && !empty($_REQUEST['store_locator_ids'])
        && isset($_REQUEST['pickup_id'])
    ) {
        $pickup_id = (int) $_REQUEST['pickup_id'];

        foreach ((array) $_REQUEST['store_locator_ids'] as $store_locator_id) {
            $store_location = fn_get_store_location($store_locator_id, DESCR_SL);

            if (!$store_location) {
                continue;
            }

            if (empty($store_location['pickup_destinations_ids'])) {
                $store_location['pickup_destinations_ids'] = [$pickup_id];
            } else {
                $store_location['pickup_destinations_ids'][] = $pickup_id;
            }

            $store_location['main_destination_id'] = $pickup_id;
            $store_location['pickup_destinations_ids'] = array_unique($store_location['pickup_destinations_ids']);

            fn_update_store_location($store_location, $store_locator_id);
        }

        $suffix = '.manage';
    }

    return [CONTROLLER_STATUS_OK, 'store_locator' . $suffix];
}

if ($mode == 'manage') {
    $params = $_REQUEST;
    if ($company_id = Registry::get('runtime.company_id')) {
        $params['company_id'] = $company_id;
    }

    list($store_locations, $search) = fn_get_store_locations($params, Registry::get('settings.Appearance.admin_elements_per_page'), DESCR_SL);
    $raw_destinations = fn_get_destinations();
    $destinations = array_combine(array_column($raw_destinations, 'destination_id'), $raw_destinations);

    Tygh::$app['view']->assign([
        'sl_settings'     => fn_get_store_locator_settings(),
        'store_locations' => $store_locations,
        'destinations'    => $destinations,
        'search'          => $search,
    ]);

    if (fn_allowed_for('MULTIVENDOR')) {
        list($companies, ) = fn_get_companies([], $auth);
        Tygh::$app['view']->assign('vendors', $companies);
    }

} elseif ($mode == 'add' || $mode == 'update') {
    $params = [];
    if ($mode === 'add') {
        $params = [
            'status' => ObjectStatuses::ACTIVE
        ];
    }

    $destinations = fn_get_destinations(DESCR_SL, $params);

    if ($mode == 'update') {
        $store_location = fn_get_store_location($_REQUEST['store_location_id'], DESCR_SL);
        if (empty($store_location)) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        if (isset($_REQUEST['add_all_destinations'])) {
            list($objects,) = fn_warehouses_get_destinations_for_picker(
                [
                    'store_location_id' => $_REQUEST['store_location_id'],
                    'destination_id'    => 0,
                ]);
            unset($store_location['shipping_destinations_ids']);
            foreach ($objects as $key => $object) {
                $store_location['shipping_destinations_ids'][$key] = $object['id'];
            }
        }

        $destinations = array_filter($destinations, static function ($destination) use ($store_location) {
            return $destination['status'] === ObjectStatuses::ACTIVE || $destination['destination_id'] === $store_location['main_destination_id'];
        });

        Tygh::$app['view']->assign('store_location', $store_location);
    }

    Registry::set('navigation.tabs', [
        'detailed' => [
            'title' => __('general'),
            'js' => true
        ],
        'addons' => [
            'title' => __('addons'),
            'js' => true
        ],
        'pickup' => [
            'title' => __('store_locator.pickup'),
            'js' => true
        ],
    ]);

    Tygh::$app['view']->assign([
        'destinations' => $destinations,
        'sl_settings'  => fn_get_store_locator_settings(),
        'states'       => fn_get_all_states(true, DESCR_SL),
    ]);
}

if (in_array($mode, ['add', 'update', 'manage'])) {
    $dynamic_sections = Registry::ifGet('navigation.dynamic.sections', []);
    $dynamic_sections['shippings'] = [
        'title' => __('shipping_methods'),
        'href'  => 'shippings.manage',
    ];
    $dynamic_sections['destinations'] = [
        'title' => __('rate_areas'),
        'href'  => 'destinations.manage',
    ];
    $dynamic_sections['store_locator'] = [
        'title' => __('store_locator'),
        'href'  => 'store_locator.manage',
    ];
    Registry::set('navigation.dynamic.active_section', 'store_locator');
    Registry::set('navigation.dynamic.sections', $dynamic_sections);
}
