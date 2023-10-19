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
use Tygh\Addons\VendorLocations\Dto\Region;

if (!defined('BOOTSTRAP')) { exit('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    return array(CONTROLLER_STATUS_OK);
}

if ($mode === 'catalog') {
    if (isset($_REQUEST['location_filter'])) {
        return array(CONTROLLER_STATUS_OK);
    }

    $location_filter = $geocomplete_type = false;
    $place_id = 0;

    switch (Registry::get('addons.vendor_locations.filter_by')) {
        case 'city':
            $geolocation = fn_get_session_data(VENDOR_LOCATIONS_STORAGE_KEY_GEO_LOCATION) ?: array();
            $locality = fn_get_session_data(VENDOR_LOCATIONS_STORAGE_KEY_LOCALITY) ?: array();
            $place_id = isset($locality['place_id']) ? $locality['place_id'] : false;

            $location_filter = Region::createFromArray(array(
                'place_id' => $place_id,
                'country'  => isset($geolocation['country']) ? $geolocation['country'] : false,
                'locality' => isset($locality['locality']) ? $locality['locality'] : false,
            ));
            break;
        case 'country':
            $geocomplete_type = '(regions)';
            $geolocation = fn_get_session_data(VENDOR_LOCATIONS_STORAGE_KEY_GEO_LOCATION) ?: array();
            $place_id = isset($geolocation['country_place_id']) ? $geolocation['country_place_id'] : false;

            $location_filter = Region::createFromArray(array(
                'place_id' => $place_id,
                'country'  => isset($geolocation['country']) ? $geolocation['country'] : false,
            ));
            break;
    }

    Registry::set('vendor_locations.default_filter', $location_filter);

    Tygh::$app['view']->assign(array(
        'vendors_search_location_place_id' => $place_id,
        'geocomplete_type'                 => $geocomplete_type,
    ));
}
