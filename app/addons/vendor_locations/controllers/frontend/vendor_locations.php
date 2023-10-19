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

use Tygh\Addons\VendorLocations\Dto\Location;

if (!defined('BOOTSTRAP')) { exit('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'set_geolocation') {
        if (defined('AJAX_REQUEST') && !empty($_REQUEST['location']) && !empty($_REQUEST['locality'])) {
            $location = Location::createFromArray($_REQUEST['location']);
            $locality = Location::createFromArray($_REQUEST['locality']);

            fn_set_session_data(VENDOR_LOCATIONS_STORAGE_KEY_GEO_LOCATION, $location->toArray());
            fn_set_session_data(VENDOR_LOCATIONS_STORAGE_KEY_LOCALITY, $locality->toArray());

            /** @var \Tygh\Ajax $ajax */
            $ajax = Tygh::$app['ajax'];
            $ajax->assign('locality', $location->getLocalityText());
        }
    }

    return array(CONTROLLER_STATUS_OK);
}
