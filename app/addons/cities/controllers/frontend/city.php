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

defined('BOOTSTRAP') or die('Access denied');

if ($mode === 'autocomplete_city') {
    $params = $_REQUEST;

    if (defined('AJAX_REQUEST')) {
        $items_per_page = isset($_REQUEST['items_per_page']) ? $_REQUEST['items_per_page'] : 10;
        $cities = fn_cities_find_cities($params, CART_LANGUAGE, $items_per_page);
        $list_cities = fn_cities_format_to_autocomplete($cities);

        Registry::get('ajax')->assign('autocomplete', $list_cities);
        exit();
    }

    exit();
}

if ($mode === 'shipping_estimation_city') {
    $params = $_REQUEST;

    $location = fn_cities_get_location_from_session();

    if (defined('AJAX_REQUEST')) {
        $lang_code = DESCR_SL;

        $params = array_merge([
            'check_country' => '',
            'check_state'   => '',
        ], $params);

        list($cities,) = fn_cities_get_cities([
            'country_code' => $params['check_country'],
            'state_code'   => $params['check_state'],
            'status'       => ObjectStatuses::ACTIVE,
        ], 0, $lang_code);

        Tygh::$app['view']->assign([
            'cities'       => $cities,
            'customer_loc' => $location,
        ]);
        if (!empty($params['additional_id'])) {
            Tygh::$app['view']->assign(['additional_id' => $params['additional_id']]);
        }
        Tygh::$app['view']->display('views/checkout/components/shipping_estimation.tpl');
        return [CONTROLLER_STATUS_NO_CONTENT];
    }
}
