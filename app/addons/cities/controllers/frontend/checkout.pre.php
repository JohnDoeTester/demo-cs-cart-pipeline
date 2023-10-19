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

defined('BOOTSTRAP') or die('Access denied');

use Tygh\Tygh;

/** @var string $mode */

if ($mode === 'cart' || $mode === 'shipping_estimation') {
    if ($action === 'get_rates') {
        $customer_location = !empty($_REQUEST['customer_location'])
            ? array_map('trim', $_REQUEST['customer_location'])
            : [];
        Tygh::$app['session']['stored_location'] = $customer_location;
    }

    $location = fn_cities_get_location_from_session($mode === 'shipping_estimation');

    if ($location) {
        list($cities,) = fn_cities_get_cities([
            'country_code' => $location['s_country'],
            'state_code'   => $location['s_state'],
            'status'       => 'A',
        ], 0, DESCR_SL);

        Tygh::$app['view']->assign([
            'cities'       => $cities,
            'customer_loc' => $location,
        ]);
    }
}

Tygh::$app['view']->assign(
    'city_autocomplete',
    [
        'url'                  => fn_url('city.autocomplete_city'),
        'city_param'           => 'q',
        'country_param'        => 'check_country',
        'items_per_page_param' => 'items_per_page',
        'items_per_page'       => 50,
    ]
);

return [CONTROLLER_STATUS_OK];
