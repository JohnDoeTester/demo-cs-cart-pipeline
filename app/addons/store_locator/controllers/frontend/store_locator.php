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

use Illuminate\Support\Collection;
use Tygh\Registry;
use Tygh\Enum\VendorStatuses;
use Tygh\Tygh;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'search') {
    fn_add_breadcrumb(__('store_locator.stores_and_pickup_points'));

    $sl_search = isset($_REQUEST['sl_search']) ? (array) $_REQUEST['sl_search'] : [];
    $params = [
        'status' => 'A',
    ];

    if (fn_allowed_for('MULTIVENDOR')) {
        /** @var \Tygh\Storefront\Storefront $storefront */
        $storefront = Tygh::$app['storefront'];
        $storefront_company_ids = $storefront->getCompanyIds();
        if (!empty($storefront_company_ids)) {
            $sl_search['company_id'] = empty($sl_search['company_id'])
                ? $storefront_company_ids
                : $sl_search['company_id'];
        }

        list($companies, ) = fn_get_companies($params, $auth);
        Tygh::$app['view']->assign('is_vendors_picker_enabled', true);
    }

    $sl_search['company_status'] = VendorStatuses::ACTIVE;
    $sl_search['storefront_search'] = true;
    list($store_locations, $search) = fn_get_store_locations($sl_search);
    if (empty($sl_search['city']) || fn_allowed_for('ULTIMATE')) {
        $grouped_locations = (new Collection($store_locations))->groupBy('city')->toArray();
    } else {
        $grouped_locations = (new Collection($store_locations))->groupBy('company_id')->toArray();
        $company_names = array_column($companies, 'company', 'company_id');
        foreach ($grouped_locations as $company_id => $grouped_location) {
            if (!isset($company_names[$company_id])) {
                continue;
            }
            $grouped_locations[$company_names[$company_id]] = $grouped_location;
            unset($grouped_locations[$company_id]);
        }
    }

    if (empty($sl_search) && Registry::get('addons.geo_maps.status') === 'A') {
        $geolocation_data = fn_geo_maps_get_customer_stored_geolocation();
        if (isset($geolocation_data['city']) && in_array($geolocation_data['city'], $cities)) {
            $sl_search['city'] = $geolocation_data['city'];
        }
    }

    Tygh::$app['view']->assign([
        'store_locations'       => $grouped_locations,
        'store_locations_count' => count($store_locations),
        'sl_search'             => $sl_search,
    ]);
} elseif ($mode === 'get_cities_list') {
    $params = array_merge([
        'q' => '',
        'page_size' => 10,
        'page' => 1,
    ], $_REQUEST);

    list($cities, $params) = fn_get_store_location_cities($params);
    array_walk($cities, function (&$city) {
        $city = [
            'id' => $city,
            'text' => $city,
        ];
    });

    if (isset($params['ids'])) {
        $cities[] = [
            'id' => $params['ids'],
            'text' => $params['ids']
        ];
    }

    /** @var \Tygh\Ajax $ajax */
    $ajax = Tygh::$app['ajax'];

    $ajax->assign('objects', $cities);
    $ajax->assign('total_objects', $params['total_items']);

    return [CONTROLLER_STATUS_NO_CONTENT];
}
