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

use Tygh\Addons\VendorLocations\Dto\Region;

if (!defined('BOOTSTRAP')) { exit('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    return array(CONTROLLER_STATUS_OK);
}

if ($mode === 'catalog') {
    $params = $_REQUEST;
    if (empty($params['location_filter'])) {
        return array(CONTROLLER_STATUS_OK);
    }

    /** @var \Tygh\Addons\VendorLocations\Dto\Region $vendors_search_location */
    $vendors_search_location = Region::createFromHash($params['location_filter']);

    Tygh::$app['view']->assign('vendors_search_location_place_id', $vendors_search_location->getPlaceId());
}
