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

use Tygh\Addons\VendorLocations\Dto\Zone;
use Tygh\Enum\VendorStatuses;
use Tygh\Tygh;

if (!defined('BOOTSTRAP')) { exit('Access denied'); }

/**
 * Returns items for the 'vendors_map' block
 * @param array $params params that passed to the fn_get_companies function
 *
 * @return array
 */
function fn_vendor_locations_get_block_vendors(array $params = array())
{
    if (empty($params['item_ids'])) {
        $storefront = Tygh::$app['storefront'];
        if ($storefront->getCompanyIds()) {
            $params['company_id'] = $storefront->getCompanyIds();
        }
    } else {
        $params['company_id'] = fn_explode(',', $params['item_ids']);
    }

    $params['get_vendor_location'] = true;
    $params['status'] = VendorStatuses::ACTIVE;

    list($companies,) = fn_get_companies($params, Tygh::$app['session']['auth']);

    return array($companies);
}

/**
 * Returns items for the 'closest_vendors' block
 * @param array $params params that passed to the fn_get_companies function
 *
 * @return array
 */
function fn_vendor_locations_block_get_closest_vendors(array $params = array())
{
    $params['company_id'] = empty($params['item_ids']) ? [] : fn_explode(',', $params['item_ids']);
    if (empty($params['company_id'])) {
        /** @var \Tygh\Storefront\Storefront $storefront */
        $storefront = Tygh::$app['storefront'];
        $params['company_id'] = $storefront->getCompanyIds();
    }
    $params['get_vendor_location'] = true;
    $params['sort_by'] = 'distance';

    $customer_geolocation = fn_get_session_data(VENDOR_LOCATIONS_STORAGE_KEY_GEO_LOCATION);
    if (is_array($customer_geolocation)) {
        /** @var \Tygh\Addons\VendorLocations\Dto\Zone */
        $params['customer_geolocation'] = Zone::createFromArray($customer_geolocation);
    }

    $params['extend'] = array(
        'products_count' => empty($params['block_data']['properties']['show_products_count']) ? 'N' : $params['block_data']['properties']['show_products_count'],
        'logos'          => true,
        'placement_info' => true,
    );

    $displayed_vendors = empty($params['block_data']['properties']['displayed_vendors']) ? 0 : $params['block_data']['properties']['displayed_vendors'];

    list($companies,) = fn_get_companies($params, Tygh::$app['session']['auth'], $displayed_vendors);

    return array(array('companies' => $companies));
}
