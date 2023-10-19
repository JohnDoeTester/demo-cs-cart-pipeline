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

use Illuminate\Support\Collection;
use Tygh\Enum\OrderDataTypes;
use Tygh\Enum\YesNo;
use Tygh\Languages\Languages;
use Tygh\Registry;
use Tygh\Template\Document\Variables\PickpupPointVariable;
use Tygh\Themes\Themes;
use Tygh\Tools\SecurityHelper;

function fn_store_locator_install()
{
    $service = array(
        'status'      => 'A',
        'module'      => 'store_locator',
        'code'        => 'pickup',
        'sp_file'     => '',
        'description' => 'Pickup',
    );

    $service['service_id'] = db_get_field('SELECT service_id FROM ?:shipping_services WHERE module = ?s AND code = ?s', $service['module'], $service['code']);

    if (empty($service['service_id'])) {
        $service['service_id'] = db_query('INSERT INTO ?:shipping_services ?e', $service);
    }

    $languages = Languages::getAll();
    foreach ($languages as $lang_code => $lang_data) {

        if ($lang_code == 'ru') {
            $service['description'] = "Самовывоз";
        } else {
            $service['description'] = "Pickup";
        }

        $service['lang_code'] = $lang_code;

        db_query('INSERT INTO ?:shipping_service_descriptions ?e', $service);
    }
}

function fn_store_locator_uninstall()
{
    $service_ids = db_get_fields('SELECT service_id FROM ?:shipping_services WHERE module = ?s', 'store_locator');
    if (!empty($service_ids)) {
        db_query('DELETE FROM ?:shipping_services WHERE service_id IN (?a)', $service_ids);
        db_query('DELETE FROM ?:shipping_service_descriptions WHERE service_id IN (?a)', $service_ids);
    }
}

/**
 * The "delete_company" hook handler.
 *
 * Actions performed:
 *     - Deleting store locations when deleting vendor.
 *
 * @param int $company_id The company_id to be deleted
 *
 * @see \fn_delete_company()
 */
function fn_store_locator_delete_company($company_id)
{
    $store_location_ids = db_get_fields('SELECT store_location_id FROM ?:store_locations WHERE company_id = ?i', $company_id);
    foreach ($store_location_ids as $store_location_id) {
        fn_delete_store_location($store_location_id);
    }
}

function fn_store_locator_update_cart_by_data_post(&$cart, $new_cart_data, $auth)
{
    if (!empty($new_cart_data['select_store'])) {
        $cart['select_store'] = $new_cart_data['select_store'];
    }
}

function fn_store_locator_calculate_cart_taxes_pre(&$cart, $cart_products, &$product_groups)
{
    if (isset(Tygh::$app['session']['cart']['shippings_extra'])
        && !isset($cart['shippings_extra'])
    ) {
        $cart['shippings_extra'] = Tygh::$app['session']['cart']['shippings_extra'];
    }

    if (!empty($cart['shippings_extra']['data'])) {

        if (!empty($cart['select_store'])) {
            $select_store = $cart['select_store'];
        } elseif (!empty($_REQUEST['select_store'])) {
            $select_store = $cart['select_store'] = $_REQUEST['select_store'];
        }

        if (!empty($select_store)) {

            $tmp_surcharge_array = array();
            foreach ($select_store as $g_key => $g) {
                foreach ($g as $s_id => $s) {
                    if (isset($cart['shippings_extra']['data'][$g_key][$s_id]['stores'][$s]['pickup_surcharge'])) {
                        $tmp_surcharge = isset($cart['shippings_extra']['data'][$g_key][$s_id]['stores'][$s]['pickup_surcharge'])
                            ? $cart['shippings_extra']['data'][$g_key][$s_id]['stores'][$s]['pickup_surcharge']
                            : 0;

                        if (isset($product_groups[$g_key]['shippings'][$s_id]['rate'])) {
                            $tmp_rate = $product_groups[$g_key]['shippings'][$s_id]['rate'];
                            $tmp_surcharge_array[$g_key][$s_id] = $tmp_rate - $tmp_surcharge;
                        }
                    }
                }
            }

            foreach ($product_groups as $group_key => $group) {
                if (!empty($group['chosen_shippings'])) {
                    foreach ($group['chosen_shippings'] as $shipping_key => $shipping) {
                        if ($shipping['module'] != 'store_locator') {
                            continue;
                        }

                        $shipping_id = $shipping['shipping_id'];

                        if (!empty($cart['shippings_extra']['data'][$group_key][$shipping_id])) {
                            $shippings_extra = $cart['shippings_extra']['data'][$group_key][$shipping_id];
                            $product_groups[$group_key]['chosen_shippings'][$shipping_key]['data'] = $shippings_extra;

                            if (!empty($select_store[$group_key][$shipping_id])) {
                                $store_id = $select_store[$group_key][$shipping_id];
                                $product_groups[$group_key]['chosen_shippings'][$shipping_key]['store_location_id'] = $store_id;
                                if (!empty($shippings_extra['stores'][$store_id])) {
                                    $store_data = $shippings_extra['stores'][$store_id];
                                    $product_groups[$group_key]['chosen_shippings'][$shipping_key]['store_data'] = $store_data;
                                }
                            }
                        }
                    }
                }
            }
        }

        foreach ($cart['shippings_extra']['data'] as $group_key => $shippings) {
            foreach ($shippings as $shipping_id => $shippings_extra) {
                if (!empty($product_groups[$group_key]['shippings'][$shipping_id]['module'])) {
                    $module = $product_groups[$group_key]['shippings'][$shipping_id]['module'];

                    if ($module == 'store_locator' && !empty($shippings_extra)) {
                        $product_groups[$group_key]['shippings'][$shipping_id]['data'] = $shippings_extra;
                    }
                }
            }
        }

        foreach ($product_groups as $group_key => $group) {
            if (!empty($group['chosen_shippings'])) {
                foreach ($group['chosen_shippings'] as $shipping_key => $shipping) {
                    $shipping_id = $shipping['shipping_id'];
                    $module = $shipping['module'];

                    if ($module == 'store_locator' && !empty($cart['shippings_extra']['data'][$group_key][$shipping_id])) {
                        $shippings_extra = $cart['shippings_extra']['data'][$group_key][$shipping_id];
                        $product_groups[$group_key]['chosen_shippings'][$shipping_key]['data'] = $shippings_extra;
                    }
                }
            }
        }
    }
}

/**
 * Gets list of store locations
 *
 * @param array  $params         Request parameters
 * @param int    $items_per_page Amount of items per page
 * @param string $lang_code      Two-letter language code
 *
 * @return array List of store locations
 */
function fn_get_store_locations($params, $items_per_page = 0, $lang_code = CART_LANGUAGE)
{
    $params = array_merge([
        'page'              => 1,
        'q'                 => '',
        'match'             => 'any',
        'sort_by'           => 'position_name',
        'sort_order'        => 'asc',
        'items_per_page'    => $items_per_page,
        'store_location_id' => [],
    ], $params);

    $sortings = [
        'position_name' => '?:store_locations.position asc, ?:store_location_descriptions.name',
    ];

    $fields = [
        'locations'                   => '?:store_locations.*',
        'store_location_descriptions' => '?:store_location_descriptions.*',
        'country_descriptions'        => '?:country_descriptions.country as country_title',
    ];

    $joins['country_descriptions'] = db_quote(
        'LEFT JOIN ?:country_descriptions ON ?:store_locations.country = ?:country_descriptions.code AND ?:country_descriptions.lang_code = ?s',
        $lang_code
    );
    $joins['store_location_descriptions'] = db_quote(
        'LEFT JOIN ?:store_location_descriptions'
        . ' ON ?:store_locations.store_location_id = ?:store_location_descriptions.store_location_id AND ?:store_location_descriptions.lang_code = ?s', $lang_code
    );

    $conditions = ['1=1'];
    if (AREA == 'C') {
        $conditions['store_status'] = defined('CART_LOCALIZATION')
            ? db_quote('?:store_locations.status = ?s ?p', 'A', fn_get_localizations_condition('?:store_locations.localization'))
            : db_quote('?:store_locations.status = ?s', 'A');
    }

    if ($params['store_location_id']) {
        $conditions['store_location_id'] = db_quote(
            '?:store_locations.store_location_id IN (?n)',
            (array) $params['store_location_id']
        );
    }

    // Search string condition for SQL query
    if (!empty($params['q'])) {
        $search_words = [$params['q']];
        $search_type = '';

        if ($params['match'] === 'any' || $params['match'] === 'all') {
            $search_words = explode(' ', $params['q']);
            $search_type = $params['match'] === 'any' ? ' OR ' : ' AND ';
        }

        $search_condition = [];
        foreach ($search_words as $word) {
            $word_conditions = [
                'name'        => db_quote('?:store_location_descriptions.name LIKE ?l', "%{$word}%"),
                'city'        => db_quote('?:store_location_descriptions.city LIKE ?l', "%{$word}%"),
                'country'     => db_quote('?:country_descriptions.country LIKE ?l', "%{$word}%"),
                'description' => db_quote('?:store_location_descriptions.description LIKE ?l', "%{$word}%"),
            ];
            $search_condition[] = db_quote('(?p)', implode(' OR ', $word_conditions));
        }

        if (!empty($search_condition)) {
            $conditions['search'] = db_quote('(?p)', implode($search_type, $search_condition));
        }
        unset($word, $word_conditions, $search_condition);
    }

    if (!empty($params['city'])) {
        $conditions['city'] = db_quote('?:store_location_descriptions.city = ?s', $params['city']);
    }

    if (!empty($params['pickup_only'])) {
        $conditions['pickup_only'] = db_quote('main_destination_id IS NOT NULL');
    }

    $conditions['company_id'] = db_quote('?:store_locations.company_id <> 0');
    if (isset($params['company_id'])) {
        if (is_array($params['company_id']) && !empty($params['company_id'])) {
            $conditions['company_id'] = db_quote('?:store_locations.company_id IN (?n)', $params['company_id']);
        } elseif (fn_get_company_condition('?:store_locations.company_id', true, $params['company_id'], false, true)) {
            $conditions['company_id'] = fn_get_company_condition('?:store_locations.company_id', false, $params['company_id'], false, true);
        }
    }

    if (!empty($params['pickup_destination_id'])) {
        $conditions['pickup_destination_id'] = db_quote('FIND_IN_SET(?n, pickup_destinations_ids)', $params['pickup_destination_id']);
    }

    if (!empty($params['main_destination_id'])) {
        $conditions['main_destination_id'] = db_quote('main_destination_id = ?i', $params['main_destination_id']);
    }

    if (!empty($params['company_status'])) {
        $joins['company'] = db_quote('LEFT JOIN ?:companies ON ?:store_locations.company_id = ?:companies.company_id');
        $conditions['company_status'] = db_quote('?:companies.status = ?s', $params['company_status']);
    }

    /**
     * Change SQL parameters for store locations selection
     *
     * @param array    $params
     * @param array    $fields         List of fields for retrieving
     * @param string   $joins          String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string   $conditions     String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string[] $sortings       Possible sortings for a query
     * @param int      $items_per_page Amout of items per page
     * @param string   $lang_code      Two-letter language code
     */
    fn_set_hook('get_store_locations_before_select', $params, $fields, $joins, $conditions, $sortings, $items_per_page, $lang_code);

    $join = implode(' ', $joins);
    $condition = implode(' AND ', $conditions);
    $limit = '';
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field('SELECT COUNT(?:store_locations.store_location_id) FROM ?:store_locations ?p WHERE 1=1 AND ?p', $join, $condition);
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }
    $sorting = db_sort($params, $sortings);

    $data = db_get_hash_array(
        'SELECT ?p FROM ?:store_locations ?p'
        . ' WHERE 1=1'
        . ' AND ?p'
        . ' GROUP BY ?:store_locations.store_location_id'
        . ' ?p'
        . ' ?p',
        'store_location_id',
        implode(', ', $fields),
        $join,
        $condition,
        $sorting,
        $limit
    );

    /**
     * Executes after the store locations are obtained, allows you to modify locations data
     *
     * @param array  $params         Request parameters
     * @param int    $items_per_page Amount of items per page
     * @param string $lang_code      Two-letter language code
     * @param array  $data           List of store locations
     */
    fn_set_hook('store_locator_get_store_locations_post', $params, $items_per_page, $lang_code, $data);

    return [$data, $params];
}

/**
 * Fetches list of cities that have stores
 *
 * @param array $params Search parameters
 *
 * @return array
 */
function fn_get_store_location_cities(array $params = [])
{
    $params = array_merge([
        'page'           => 1,
        'page_size'      => 10,
        'items_per_page' => null,
        'q'              => null,
        'status'         => null,
        'company_id'     => null,
        'lang_code'      => CART_LANGUAGE,
        'total_items'    => 0,
    ], $params);

    if ($params['page_size'] && !$params['items_per_page']) {
        $params['items_per_page'] = $params['page_size'];
    }

    $condition = [
        'lang_code' => db_quote('descriptions.lang_code = ?s', $params['lang_code']),
    ];

    if ($params['status']) {
        $condition['status'] = db_quote('locations.status IN (?a)', $params['status']);
    }

    if ($params['company_id']) {
        $condition['company_id'] = db_quote('locations.company_id IN (?n)', $params['company_id']);
    }

    if ($params['q']) {
        $condition['q'] = db_quote('descriptions.city LIKE ?l', '%' . $params['q'] . '%');
    }

    $cities = db_get_fields(
        'SELECT descriptions.city AS city' .
        ' FROM ?:store_locations AS locations' .
        ' LEFT JOIN ?:store_location_descriptions AS descriptions' .
        ' ON locations.store_location_id = descriptions.store_location_id' .
        ' WHERE ?p' .
        ' GROUP BY city' .
        ' ?p',
        implode(' AND ', $condition),
        db_paginate($params['page'], $params['items_per_page'])
    );

    if ($params['items_per_page']) {
        $params['total_items'] = (int) db_get_field(
            'SELECT COUNT(DISTINCT(city)) AS count' .
            ' FROM ?:store_locations AS locations' .
            ' LEFT JOIN ?:store_location_descriptions AS descriptions' .
            ' ON locations.store_location_id = descriptions.store_location_id' .
            ' WHERE ?p',
            implode(' AND ', $condition)
        );
    }

    return [$cities, $params];
}

/**
 * Gets store location data.
 *
 * @param int    $store_location_id Store location identifier
 * @param string $lang_code         Two-letters language code
 *
 * @return array
 */
function fn_get_store_location($store_location_id, $lang_code = CART_LANGUAGE)
{
    $fields = array(
        '?:store_locations.*',
        '?:store_location_descriptions.*',
        '?:country_descriptions.country as country_title',
    );

    $join = db_quote(" LEFT JOIN ?:store_location_descriptions ON ?:store_locations.store_location_id = ?:store_location_descriptions.store_location_id AND ?:store_location_descriptions.lang_code = ?s", $lang_code);
    $join .= db_quote(" LEFT JOIN ?:country_descriptions ON ?:store_locations.country = ?:country_descriptions.code AND ?:country_descriptions.lang_code = ?s", $lang_code);

    $condition = db_quote(" ?:store_locations.store_location_id = ?i ", $store_location_id);
    $condition .= (AREA == 'C' && defined('CART_LOCALIZATION')) ? fn_get_localizations_condition('?:store_locations.localization') : '';

    /**
     * Executes before store location getting, allows you to modify SQL query parts
     *
     * @param int    $store_location_id Store location identifier
     * @param string $lang_code         Two-letters language code
     * @param array  $fields            List of fields for retrieving
     * @param string $join              String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $condition         String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     */
    fn_set_hook('store_locator_get_store_location_before_select', $store_location_id, $lang_code, $fields, $join, $condition);

    $store_location = db_get_row('SELECT ?p FROM ?:store_locations ?p WHERE ?p', implode(', ', $fields), $join, $condition);

    if (!empty($store_location['pickup_destinations_ids'])) {
        $store_location['pickup_destinations_ids'] = explode(',', $store_location['pickup_destinations_ids']);
    }

    /**
     * Executes after the store location is obtained, allows you to modify the location data
     *
     * @param int    $store_location_id Store location identifier
     * @param string $lang_code         Two-letters language code
     * @param array  $store_location    Store location data
     */
    fn_set_hook('store_locator_get_store_location_post', $store_location_id, $lang_code, $store_location);

    return $store_location;
}

function fn_get_store_location_name($store_location_id, $lang_code = CART_LANGUAGE)
{
    if (!empty($store_location_id)) {
        return db_get_field('SELECT name FROM ?:store_location_descriptions WHERE store_location_id = ?i AND lang_code = ?s', $store_location_id, $lang_code);
    }

    return false;
}

/**
 * Creates or updates a store location.
 *
 * @param array  $store_location_data Store location data
 * @param int    $store_location_id   Store location identifier
 * @param string $lang_code           Two-letter language code
 *
 * @return int Created or updated location identifier
 */
function fn_update_store_location($store_location_data, $store_location_id, $lang_code = DESCR_SL)
{
    SecurityHelper::sanitizeObjectData('store_location', $store_location_data);

    $store_location_data['localization'] = !empty($store_location_data['localization']) ? fn_implode_localizations($store_location_data['localization']) : '';
    $store_location_data['main_destination_id'] = !empty($store_location_data['main_destination_id']) && is_numeric($store_location_data['main_destination_id'])
        ? $store_location_data['main_destination_id']
        : null;

    if (!empty($store_location_data['pickup_destinations_ids']) && $store_location_data['main_destination_id']) {
        if (!is_array($store_location_data['pickup_destinations_ids'])) {
            $store_location_data['pickup_destinations_ids'] = explode(',', $store_location_data['pickup_destinations_ids']);
        }

        if (!in_array($store_location_data['main_destination_id'], $store_location_data['pickup_destinations_ids'])) {
            $store_location_data['pickup_destinations_ids'][] = $store_location_data['main_destination_id'];
        }

        $store_location_data['pickup_destinations_ids'] = implode(',', $store_location_data['pickup_destinations_ids']);
    } else {
        $store_location_data['pickup_destinations_ids'] = $store_location_data['main_destination_id'] ?: '0';
    }

    /**
     * Executes when creating or updating a store location right before the location data is stored in the database.
     * Allows you to modify the saved location data
     *
     * @param array  $store_location_data Store location data
     * @param int    $store_location_id   Store location identifier
     * @param string $lang_code           Two-letter language code
     */
    fn_set_hook('store_locator_update_store_location_before_update', $store_location_data, $store_location_id, $lang_code);

    $action = 'update';
    if (empty($store_location_id)) {
        $action = 'add';
        if (empty($store_location_data['position'])) {
            $store_location_data['position'] = db_get_field('SELECT MAX(position) FROM ?:store_locations');
            $store_location_data['position'] += 10;
        }

        $store_location_id = db_query('INSERT INTO ?:store_locations ?e', $store_location_data);

        $store_location_data['store_location_id'] = $store_location_id;

        foreach (Languages::getAll() as $store_location_data['lang_code'] => $v) {
            db_query("INSERT INTO ?:store_location_descriptions ?e", $store_location_data);
        }
    } else {
        db_query('UPDATE ?:store_locations SET ?u WHERE store_location_id = ?i', $store_location_data, $store_location_id);
        db_query('UPDATE ?:store_location_descriptions SET ?u WHERE store_location_id = ?i AND lang_code = ?s', $store_location_data, $store_location_id, $lang_code);
    }

    /**
     * Executes after store location was updated, allows you to update the corresponding data
     *
     * @param array  $store_location_data Set of store locator fields and their values
     * @param int    $store_location_id   Store location identifier
     * @param string $lang_code           Two-letters language code
     * @param string $action              Describe action with store location update or add
     */
    fn_set_hook('store_locator_update_store_location_post', $store_location_data, $store_location_id, $lang_code, $action);

    if ($action == 'add') {
        fn_store_locator_attach_location_to_shippings($store_location_id);
    }

    return $store_location_id;
}

function fn_delete_store_location($store_location_id)
{
    $deleted = true;

    $affected_rows = db_query('DELETE FROM ?:store_locations WHERE store_location_id = ?i', $store_location_id);
    db_query('DELETE FROM ?:store_location_descriptions WHERE store_location_id = ?i', $store_location_id);

    if (empty($affected_rows)) {
        $deleted = false;
    }

    /**
     * Executes after store location was deleted, modifies deletion results and allow to delete the corresponding data
     *
     * @param int   $store_location_id Store location identifier
     * @param int   $affected_rows     Deleted rows from store_locations table
     * @param bool  $deleted           Deletion result
     */
    fn_set_hook('store_locator_delete_store_location_post', $store_location_id, $affected_rows, $deleted);

    return $deleted;
}

function fn_store_locator_google_langs($lang_code)
{
    $supported_langs = array('en', 'eu', 'ca', 'da', 'nl', 'fi', 'fr', 'gl', 'de', 'el', 'it', 'ja', 'no', 'nn', 'ru', 'es', 'sv', 'th');

    if (in_array($lang_code, $supported_langs)) {
        return $lang_code;
    }

    return '';
}

function fn_store_locator_yandex_langs($lang_code)
{
    $supported_langs = array('en' => 'en-US', 'tr' => 'tr-TR', 'ru' => 'ru-RU');
    $default_lang_code = 'en';

    if (isset($supported_langs[$lang_code])) {
        return $supported_langs[$lang_code];
    }

    return $supported_langs[$default_lang_code];
}

function fn_store_locator_get_info()
{
    $text = '<a href="http://code.google.com/apis/maps/signup.html">' . __('singup_google_url') . '</a>';

    return $text;
}

function fn_get_store_locator_settings()
{
    static $settings;

    if (empty($settings)) {
        $settings = Registry::get('addons.store_locator');
        unset($settings['status'], $settings['priority'], $settings['unmanaged']);
    }

    return $settings;
}

function fn_get_store_locator_map_templates($area)
{
    $templates = array();

    if (empty($area) || !in_array($area, array('A', 'C'))) {
        return $templates;
    }

    $theme = Themes::areaFactory($area);
    $search_path = 'addons/store_locator/views/store_locator/components/maps/';

    $_templates = $theme->getDirContents(array(
        'dir'       => 'templates/' . $search_path,
        'get_dirs'  => false,
        'get_files' => true,
        'extension' => array('.tpl'),
    ), Themes::STR_MERGE, Themes::PATH_ABSOLUTE, Themes::USE_BASE);

    if (!empty($_templates)) {
        foreach ($_templates as $template => $file_info) {
            $template_provider = str_replace('.tpl', '', strtolower($template)); // Get provider name
            $templates[$template_provider] = $search_path . $template;
        }
    }

    return $templates;
}

if (fn_allowed_for('ULTIMATE')) {
    function fn_store_locator_ult_check_store_permission($params, &$object_type, &$object_name, &$table, &$key, &$key_id)
    {
        if (Registry::get('runtime.controller') == 'store_locator' && !empty($params['store_location_id'])) {
            $key = 'store_location_id';
            $key_id = $params[$key];
            $table = 'store_locations';
            $object_name = fn_get_store_location_name($key_id, DESCR_SL);
            $object_type = __('store_locator');
        }
    }
}

/**
 * Fetches locations list based on stores data
 *
 * @param string $lang_code Two-letters language code
 *
 * @return array
 */
function fn_store_locator_get_stores_locations_list($lang_code = CART_LANGUAGE)
{
    $fields = ['loc.country', 'country.country AS country_name', 'loc.state', 'state_descr.state AS state_name', 'loc_descr.city'];

    $joins['states'] = db_quote('LEFT JOIN ?:states AS states ON states.country_code = loc.country AND states.code = loc.state');
    $joins['country_descriptions'] = db_quote(
        'LEFT JOIN ?:country_descriptions AS country ON country.code = loc.country AND country.lang_code = ?s',
        $lang_code
    );
    $joins['state_descriptions'] = db_quote(
        'LEFT JOIN ?:state_descriptions AS state_descr ON state_descr.state_id = states.state_id AND state_descr.lang_code = ?s',
        $lang_code
    );
    $joins['store_location_descriptions'] = db_quote(
        'LEFT JOIN ?:store_location_descriptions AS loc_descr ON loc_descr.store_location_id = loc.store_location_id AND loc_descr.lang_code = ?s',
        $lang_code
    );

    $condition = db_quote(
        'WHERE country.country <> ?s AND state_descr.state <> ?s AND loc_descr.city <> ?s AND loc.status = ?s',
        '', '', '', 'A'
    );

    $locations = db_get_array(
        'SELECT ?p FROM ?:store_locations AS loc ?p ?p',
        implode(', ', $fields),
        implode(' ', $joins),
        $condition
    );

    $grouped_locations = (new Collection($locations))
        ->groupBy('country')
        ->map(function ($country_group) {
            $prepared_group = $country_group
                ->groupBy('state')
                ->map(function ($state_group) {
                    $state_name = $state_group->first()['state_name'];
                    $cities = array_unique(array_column($state_group->toArray(), 'city'));

                    return ['title' => $state_name, 'cities' => $cities];
                });

            $country_name = $country_group->first()['country_name'];
            return [
                'title'  => $country_name,
                'states' => $prepared_group->toArray(),
            ];
        })
        ->toArray();

    return $grouped_locations;
}

/**
 * Hook handler: sets pickup point data.
 */
function fn_store_locator_pickup_point_variable_init(
    PickpupPointVariable $instance,
    $order,
    $lang_code,
    &$is_selected,
    &$name,
    &$phone,
    &$full_address,
    &$open_hours_raw,
    &$open_hours,
    &$description_raw,
    &$description
) {
    if (!empty($order['shipping'])) {
        if (is_array($order['shipping'])) {
            $shipping = reset($order['shipping']);
        } else {
            $shipping = $order['shipping'];
        }

        if (!isset($shipping['module']) || $shipping['module'] !== 'store_locator') {
            return;
        }

        if (isset($shipping['store_data'])) {
            $pickup_data = $shipping['store_data'];

            $is_selected = true;
            $name = $pickup_data['name'];
            $phone = $pickup_data['pickup_phone'];
            $full_address = fn_store_locator_format_pickup_point_address($pickup_data);
            $open_hours = $pickup_data['pickup_time'];
            $open_hours_raw = [$pickup_data['pickup_time']];
            $description_raw = $pickup_data['description'];
            $description = strip_tags($description_raw, '<br><p><div><ul><li>');
        }
    }

    return;
}

/**
 * Formats store location address.
 *
 * @param array $pickup_data Store location
 *
 * @return string Address
 */
function fn_store_locator_format_pickup_point_address($pickup_data)
{
    $address_parts = array_filter([
        $pickup_data['city'],
        $pickup_data['pickup_address'],
    ], 'fn_string_not_empty');

    $address = implode(', ', $address_parts);

    return $address;
}

/**
 * The "calculate_cart_post" hook handler.
 *
 * Actions performed:
 * - Adds minimal price to shipping method.
 * - Copies shipping extra data from session into a cart object when working with the add-on via API.
 *
 * @param array<string, int|float|string|array>    $cart                  Cart data
 * @param array<string, int|string>                $auth                  Auth data
 * @param string                                   $calculate_shipping    Shipping calculation mode:
 *                                                                        A - calculate all available methods
 *                                                                        E - calculate selected methods only (from cart[shipping])
 *                                                                        S - skip calculation
 * @param bool                                     $calculate_taxes       Flag determines if taxes should be calculated
 * @param string                                   $options_style         Options retrieval style
 *                                                                        "F" - Full option information (with exceptions)
 *                                                                        "I" - Short info
 *                                                                        "" - "Source" info. Only ids array (option_id => variant_id)
 * @param bool                                     $apply_cart_promotions Flag determines if promotions should be applied to the cart
 * @param array<int, array<int, int|string|array>> $cart_products         Cart products
 * @param array<int, array<int, int|string|array>> $product_groups        Products grouped by packages, suppliers, vendors
 *
 * @psalm-param array{
 *   select_store?: array<int, array<int, int>>,
 *   shippings_extra?: array<array-key, int|string|array>
 * } $cart
 *
 * @psalm-param array<
 *   int, array{
 *     shippings: array<
 *       int, array{
 *         pickup_rate_from?: float|mixed,
 *         pickup_rate_to?: float|mixed
 *       }
 *     >
 *   }
 * > $product_groups
 *
 * @see \fn_calculate_cart_content()
 */
function fn_store_locator_calculate_cart_post(
    array &$cart,
    array $auth,
    $calculate_shipping,
    $calculate_taxes,
    $options_style,
    $apply_cart_promotions,
    array $cart_products,
    array &$product_groups
) {
    foreach ($product_groups as $group_key => $group) {
        $selected_shipping = isset($group['chosen_shippings']) ? reset($group['chosen_shippings']) : 0;
        $selected_shipping_id = isset($selected_shipping['shipping_id']) ? (int) $selected_shipping['shipping_id'] : 0;

        foreach ($group['shippings'] as $shipping_id => $shipping) {
            $is_selected_shipping = $selected_shipping_id === (int) $shipping_id;
            $is_store_selected = !empty($cart['select_store'][$group_key][$shipping_id]);

            if (
                (
                    $is_selected_shipping
                    && $is_store_selected
                )
                || empty($shipping['data']['stores'])
                || empty(array_column($shipping['data']['stores'], 'pickup_rate'))
            ) {
                continue;
            }

            $all_rates = array_column($shipping['data']['stores'], 'pickup_rate');

            if (!array_sum($all_rates)) {
                continue;
            }

            $min_rate = min($all_rates);
            $product_groups[$group_key]['shippings'][$shipping_id]['pickup_rate_from'] = $min_rate;

            $max_rate = max($all_rates);

            if ($min_rate === $max_rate) {
                continue;
            }

            $product_groups[$group_key]['shippings'][$shipping_id]['pickup_rate_to'] = $max_rate;
        }
    }

    if (!isset(Tygh::$app['session']['cart']['shippings_extra'])) {
        return;
    }

    /** @var array<array-key, int|string|array> $shipping_extra */
    $shipping_extra = Tygh::$app['session']['cart']['shippings_extra'];
    $cart['shippings_extra'] = $shipping_extra;
}

/**
 * The "calculate_cart_content_before_shipping_calculation" hook handler.
 *
 * Actions performed:
 * - Adds stores and pickup points table into caching condition.
 * - Copies product groups data from a cart object into a session when working with the add-on via API.
 *
 * @param array<string, int|float|string|array> $cart                  Cart contents
 * @param array<string,string>                  $auth                  Authentication info
 * @param string                                $calculate_shipping    Shipping calculation policy
 * @param bool                                  $calculate_taxes       Whether to calculate taxes
 * @param string                                $options_style         Options calculation policy
 * @param bool                                  $apply_cart_promotions Whether to apply promotions
 * @param array<string>                         $shipping_cache_tables Tables that affect shipping rates caching
 *
 * @psalm-param array{
 *   product_groups?: array<
 *     int, array{
 *       shippings?: array<
 *         int, array{
 *           shipping_id: int,
 *           module?: string,
 *           data?: array{
 *              stores: array<
 *                int, array{
 *                  company_id: int,
 *                  position: int,
 *                  country: string,
 *                  state: string,
 *                  localization: string,
 *                  status: string,
 *                  main_destination_id: int,
 *                  pickup_destinations_ids: string,
 *                  lang_code: string,
 *                  share_company_id?: int,
 *                  share_object_id?: int,
 *                  share_object_type: string,
 *                  shipping_position: int
 *                }
 *              >
 *           }
 *         }
 *       >
 *     }
 *   >
 * } $cart
 *
 * @see \fn_calculate_cart_content()
 */
function fn_store_locator_calculate_cart_content_before_shipping_calculation(
    array $cart,
    array $auth,
    $calculate_shipping,
    $calculate_taxes,
    $options_style,
    $apply_cart_promotions,
    array &$shipping_cache_tables
) {
    $shipping_cache_tables[] = 'store_locations';
    $shipping_cache_tables[] = 'store_location_descriptions';

    // FIXME: Dirty hack for API
    if (
        !fn_allowed_for('MULTIVENDOR')
        || !isset($cart['product_groups'])
        || isset(Tygh::$app['session']['cart']['product_groups'])
    ) {
        return;
    }

    Tygh::$app['session']['cart']['product_groups'] = $cart['product_groups'];
}

/**
 * Adds store location to 'Selected stores' in all Store locator-based shipping methods.
 *
 * @deprecated since 4.11.1.
 *
 * @param int $store_location_id
 *
 * @internal
 */
function fn_store_locator_attach_location_to_shippings($store_location_id)
{
    $store_location = fn_get_store_location($store_location_id);

    $shipping_company_ids = [$store_location['company_id']];
    if (fn_allowed_for('ULTIMATE')) {
        $shipping_company_ids = fn_ult_get_object_shared_companies('store_locations', $store_location_id);
    }

    foreach ($shipping_company_ids as $company_id) {
        $shippings = fn_get_available_shippings($company_id, true);
        foreach ($shippings as $shipping) {
            if ($shipping['module'] !== 'store_locator' || empty($shipping['service_params']['active_stores'])) {
                continue;
            }

            $shipping['service_params']['active_stores'][] = $store_location_id;
            $shipping['service_params']['active_stores'] = array_unique($shipping['service_params']['active_stores']);

            db_query('UPDATE ?:shippings SET service_params = ?s WHERE shipping_id = ?i',
                serialize($shipping['service_params']),
                $shipping['shipping_id']
            );
        }
    }
}

/**
 * The "update_shipping" hook handler.
 *
 * Actions performed:
 *  - Adds service parameters field to pickup shipping
 *  - Changes 'is_address_required' parameter according to service
 *
 * @see \fn_update_shipping()
 */
function fn_store_locator_update_shipping(&$shipping_data, $shipping_id, $lang_code)
{
    $service['service_id'] = db_get_field('SELECT service_id FROM ?:shipping_services WHERE module = ?s AND code = ?s', 'store_locator', 'pickup');
    if (!empty($shipping_id)) {
        $old_service_id = db_get_field('SELECT service_id FROM ?:shippings WHERE shipping_id = ?s', $shipping_id);
    }

    if (!isset($shipping_data['service_id']) || !isset($service['service_id'])) {
        return;
    }

    if ($shipping_data['service_id'] === $service['service_id']) {
        $shipping_data['service_params'] = serialize(['active_stores' => '']);

        if (empty($shipping_id) || isset($old_service_id) && $old_service_id !== $shipping_data['service_id']) {
            $shipping_data['is_address_required'] = YesNo::NO;
        }
    } elseif (!empty($shipping_id) && isset($old_service_id) && $old_service_id === $service['service_id']) {
        $shipping_data['is_address_required'] = YesNo::YES;
    }
}

/**
 * The "store_shipping_rates_post" hook handler.
 *
 * Actions performed:
 * - Stores previously selected pickup point in the cart data when working with order via API.
 *
 * @param int                                   $order_id      Order ID
 * @param array<string, int|float|string|array> $cart          Cart contents
 * @param array<string, string>                 $customer_auth Customer authentication data
 *
 * @psalm-param array{
 *   product_groups?: array<
 *     int, array{
 *       shippings?: array<
 *         int, array{
 *           shipping_id: int,
 *           module?: string,
 *           data?: array{
 *              stores: array<
 *                int, array{
 *                  company_id: int,
 *                  position: int,
 *                  country: string,
 *                  state: string,
 *                  localization: string,
 *                  status: string,
 *                  main_destination_id: int,
 *                  pickup_destinations_ids: string,
 *                  lang_code: string,
 *                  share_company_id?: int,
 *                  share_object_id?: int,
 *                  share_object_type: string,
 *                  shipping_position: int
 *                }
 *              >
 *           }
 *         }
 *       >
 *     }
 *   >
 * } $cart
 *
 * @see \fn_store_shipping_rates()
 */
function fn_store_locator_store_shipping_rates_post($order_id, array &$cart, array $customer_auth)
{
    if (!defined('API')) {
        return;
    }
    if (isset($cart['select_store'])) {
        return;
    }

    $old_shipping_data = db_get_field(
        'SELECT data FROM ?:order_data WHERE order_id = ?i AND type = ?s',
        $order_id,
        OrderDataTypes::SHIPPING
    );
    if (empty($old_shipping_data)) {
        return;
    }

    $old_shipping_data = unserialize($old_shipping_data);
    foreach ($old_shipping_data as $shipping) {
        if (
            empty($shipping['module'])
            || $shipping['module'] !== 'store_locator'
            || empty($shipping['store_location_id'])
        ) {
            continue;
        }

        $cart['select_store'][$shipping['group_key']][$shipping['shipping_id']]
            = Tygh::$app['session']['cart']['select_store'][$shipping['group_key']][$shipping['shipping_id']]
            = $shipping['store_location_id'];
    }

    // FIXME: Dirty hack for API
    if (
        isset(Tygh::$app['session']['cart']['product_groups'])
        || !isset($cart['product_groups'])
    ) {
        return;
    }

    Tygh::$app['session']['cart']['product_groups'] = $cart['product_groups'];
}

/**
 * The "storefront_rest_api_format_order_prices_post" hook handler.
 *
 * Actions performed:
 * - Formats pickup point delivery cost.
 *
 * @param array<string, int|float|string|array> $order    Order data
 * @param string                                $currency Currency code
 *
 * @psalm-param array{
 *   product_groups?: array<
 *     int, array{
 *       shippings?: array<
 *         int, array{
 *           shipping_id: int,
 *           module?: string,
 *           data?: array{
 *              stores: array<
 *                int, array{
 *                  pickup_rate: float,
 *                  pickup_rate_formatted: array{
 *                    price: string,
 *                    symbol: string
 *                  }
 *                }
 *              >
 *           }
 *         }
 *       >
 *     }
 *   >
 * } $order
 *
 * @see \fn_storefront_rest_api_format_order_prices()
 */
function fn_store_locator_storefront_rest_api_format_order_prices_post(array &$order, $currency)
{
    if (empty($order['product_groups'])) {
        return;
    }

    foreach ($order['product_groups'] as $group_key => &$product_group) {
        if (empty($product_group['shippings'])) {
            continue;
        }

        foreach ($product_group['shippings'] as &$shipping) {
            if (
                empty($shipping['module'])
                || $shipping['module'] !== 'store_locator'
                || empty($shipping['data']['stores'])
            ) {
                continue;
            }

            $shipping_id = $shipping['shipping_id'];
            foreach ($shipping['data']['stores'] as $store_id => &$store) {
                if (isset($order['shippings_extra']['data'][$group_key][$shipping['shipping_id']]['stores'][$store_id])) {
                    $order['shippings_extra']['data'][$group_key][$shipping_id]['stores'][$store_id]['pickup_rate_formatted'] =
                        fn_storefront_rest_api_format_price(
                            $store['pickup_rate'],
                            $currency
                        );
                }
                $store['pickup_rate_formatted'] = fn_storefront_rest_api_format_price($store['pickup_rate'], $currency);
            }
            unset($store);
        }
        unset($shipping);
    }
    unset($product_group);
}

/**
 * The "storefront_rest_api_strip_service_data_post" hook handler.
 *
 * Actions performed:
 * - Removes irrelevant pickup points data from cart info.
 *
 * @param array<string, int|float|string|array> $cart Cart content
 *
 * @psalm-param array{
 *   product_groups?: array<
 *     int, array{
 *       shippings?: array<
 *         int, array{
 *           shipping_id: int,
 *           module?: string,
 *           data?: array{
 *              stores: array<
 *                int, array{
 *                  company_id: int,
 *                  position: int,
 *                  country: string,
 *                  state: string,
 *                  localization: string,
 *                  status: string,
 *                  main_destination_id: int,
 *                  pickup_destinations_ids: string,
 *                  lang_code: string,
 *                  share_company_id?: int,
 *                  share_object_id?: int,
 *                  share_object_type: string,
 *                  shipping_position: int
 *                }
 *              >
 *           }
 *         }
 *       >
 *     }
 *   >
 * } $cart
 *
 * @see \fn_storefront_rest_api_strip_service_data()
 */
function fn_store_locator_storefront_rest_api_strip_service_data_post(array &$cart)
{
    if (empty($cart['product_groups'])) {
        return;
    }

    foreach ($cart['product_groups'] as $group_key => &$product_group) {
        if (empty($product_group['shippings'])) {
            continue;
        }

        foreach ($product_group['shippings'] as &$shipping) {
            if (
                empty($shipping['module'])
                || $shipping['module'] !== 'store_locator'
                || empty($shipping['data']['stores'])
            ) {
                continue;
            }

            $shipping_id = $shipping['shipping_id'];
            foreach ($shipping['data']['stores'] as $store_id => &$store) {
                unset(
                    $store['company_id'],
                    $cart['shippings_extra']['data'][$group_key][$shipping_id]['stores'][$store_id]['company_id'],
                    $store['position'],
                    $cart['shippings_extra']['data'][$group_key][$shipping_id]['stores'][$store_id]['position'],
                    $store['country'],
                    $cart['shippings_extra']['data'][$group_key][$shipping_id]['stores'][$store_id]['country'],
                    $store['state'],
                    $cart['shippings_extra']['data'][$group_key][$shipping_id]['stores'][$store_id]['state'],
                    $store['localization'],
                    $cart['shippings_extra']['data'][$group_key][$shipping_id]['stores'][$store_id]['localization'],
                    $store['status'],
                    $cart['shippings_extra']['data'][$group_key][$shipping_id]['stores'][$store_id]['status'],
                    $store['main_destination_id'],
                    $cart['shippings_extra']['data'][$group_key][$shipping_id]['stores'][$store_id]['main_destination_id'],
                    $store['pickup_destinations_ids'],
                    $cart['shippings_extra']['data'][$group_key][$shipping_id]['stores'][$store_id]['pickup_destinations_ids'],
                    $store['lang_code'],
                    $cart['shippings_extra']['data'][$group_key][$shipping_id]['stores'][$store_id]['lang_code'],
                    $store['share_company_id'],
                    $cart['shippings_extra']['data'][$group_key][$shipping_id]['stores'][$store_id]['share_company_id'],
                    $store['share_object_id'],
                    $cart['shippings_extra']['data'][$group_key][$shipping_id]['stores'][$store_id]['share_object_id'],
                    $store['share_object_type'],
                    $cart['shippings_extra']['data'][$group_key][$shipping_id]['stores'][$store_id]['share_object_type'],
                    $store['shipping_position'],
                    $cart['shippings_extra']['data'][$group_key][$shipping_id]['stores'][$store_id]['shipping_position']
                );
            }
            unset($store);

            $shipping['data']['stores'] = array_values($shipping['data']['stores']);
            $cart['shippings_extra']['data'][$group_key][$shipping_id]['stores'] = array_values(
                $cart['shippings_extra']['data'][$group_key][$shipping_id]['stores']
            );
        }
        unset($shipping);
    }
    unset($product_group);
}

/**
 * The "shippings_get_shipping_for_test_post" hook handler.
 *
 * Actions performed:
 * - Substitutes the company_id, if present in the request.
 *
 * @param int                                   $shipping_id    The shipping id
 * @param int                                   $service_id     The service id
 * @param array<string, array<string, int>>     $service_params Settings
 * @param array<string, int|string|array>       $package_info   Package info with cost, weight and amount of products calculated
 * @param string                                $lang           Language information
 * @param array<string, int|float|string|array> $shipping_info  Information about shipping
 *
 * @return void
 *
 * @param-out non-empty-array<string, mixed> $shipping_info
 */
function fn_store_locator_shippings_get_shipping_for_test_post(&$shipping_id, &$service_id, array &$service_params, &$package_info, &$lang, &$shipping_info)
{
    $shipping_info['company_id'] = isset($service_params['calculate_data']['company_id'])
        ? $service_params['calculate_data']['company_id']
        : $shipping_info['company_id'];
}

/**
 * The "place_suborders_pre" hook handler.
 *
 * Actions performed:
 * - Generates the correct keys of "shippings_extra" and "select_store" in suborder cart.
 *
 * @param int                                                                                                                         $order_id      Order identifier
 * @param array<string|int>                                                                                                           $cart          Cart contents
 * @param array<string|int>                                                                                                           $auth          Authentication data
 * @param string                                                                                                                      $action        Current action. Can be empty or "save"
 * @param int                                                                                                                         $issuer_id     Issuer identifier
 * @param array{shippings_extra?: array{data?: array<string|int>}, chosen_shipping?: array<int>, shipping?: array<array<string|int>>} $suborder_cart Child cart contents
 * @param int                                                                                                                         $key_group     Child cart products group key
 * @param array<string|int>                                                                                                           $group         Child cart products
 *
 * @see \fn_place_suborders()
 *
 * @return void
 */
function fn_store_locator_place_suborders_pre($order_id, $cart, $auth, $action, $issuer_id, &$suborder_cart, $key_group, $group)
{
    if (
        !isset($suborder_cart['chosen_shipping'][$key_group])
        || !isset($suborder_cart['shipping'][$suborder_cart['chosen_shipping'][$key_group]])
    ) {
        return;
    }

    $shipping = $suborder_cart['shipping'][$suborder_cart['chosen_shipping'][$key_group]];

    if (
        empty($shipping['module'])
        || $shipping['module'] !== 'store_locator'
    ) {
        return;
    }

    if (isset($suborder_cart['shippings_extra']['data'][$key_group])) {
        $suborder_cart['shippings_extra']['data'] = [$suborder_cart['shippings_extra']['data'][$key_group]];
    }

    if (!isset($suborder_cart['select_store'][$key_group])) {
        return;
    }

    $suborder_cart['select_store'] = [$suborder_cart['select_store'][$key_group]];
}

/**
 * The `uninstall_addon_post` hook handler.
 *
 * Actions performed:
 *      - Deletes stores that belong to marketplace after deleting order fulfillment add-on.
 *
 * @param string $addon_name Uninstalled add-on name.
 *
 * @return void
 */
function fn_store_locator_uninstall_addon_post($addon_name)
{
    if ($addon_name !== 'order_fulfillment') {
        return;
    }

    $ids = db_get_fields('SELECT store_location_id FROM ?:store_locations WHERE company_id = 0');
    foreach ($ids as $id) {
        fn_delete_store_location($id);
    }
}

/**
 * The `api_orders_create_after_add_product_to_cart` hook handler.
 *
 * Actions performed:
 *     - Edit data about the selected store when placing an order using API
 *
 * @param array $params       Request parameters
 * @param int   $status       API response status
 * @param array $data         API response data
 * @param bool  $valid_params Is valid params flag
 * @param array $auth         API authentication details
 * @param array $cart         Cart data
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_store_locator_api_orders_create_after_add_product_to_cart(array &$params, $status, array $data, $valid_params, array $auth, array &$cart)
{
    // phpcs:ignore
    if (isset($params['select_store'])) {
        $new_select_store = [];
        foreach ($cart['product_groups'] as $key => $group) {
            // phpcs:ignore
            if (isset($params['select_store'][$group['group_key']])) {
                $new_select_store[$key] = $params['select_store'][$group['group_key']];
            }
        }

        $cart['select_store'] = $new_select_store;
        unset($params['select_store']);
    }
}

/**
 * The "pre_update_order" hook handler.
 *
 * Actions performed:
 *     - Deletes information about available pick-up points in cart.
 *
 * @param array<string|int|float|array<string|int|float>> $cart     Cart contents
 * @param int                                             $order_id Order identifier
 *
 * @psalm-param array{
 *   product_groups?: array<
 *     int, array{
 *       chosen_shippings?: array<
 *         int, array{
 *           shipping_id: int,
 *           module?: string,
 *           data?: array{
 *              stores: array<
 *                int, array{
 *                  company_id: int
 *                }
 *              >
 *           }
 *         }
 *       >,
 *       shippings?: array<
 *         int, array{
 *           shipping_id: int,
 *           module?: string,
 *           data?: array{
 *              stores: array<
 *                int, array{
 *                  company_id: int
 *                }
 *              >
 *           }
 *         }
 *       >
 *     }
 *   >
 * } $cart
 *
 * @see \fn_update_order()
 *
 * @return void
 */
function fn_store_locator_pre_update_order(array &$cart, $order_id)
{
    if (empty($cart['product_groups'])) {
        return;
    }

    foreach ($cart['product_groups'] as $group_id => $group) {
        $group_shipping = !empty($group['chosen_shippings']) ? $group['chosen_shippings'] : [];

        foreach ($group_shipping as $shipping_id => $shipping) {
            if (
                empty($shipping['data']) || empty($shipping['data']['stores'])
                || empty($shipping['module']) || $shipping['module'] !== 'store_locator'
                || empty($shipping['store_location_id'])
            ) {
                continue;
            }

            unset($cart['product_groups'][$group_id]['chosen_shippings'][$shipping_id]['data']);

            if (
                empty($shipping['shipping_id'])
                || empty($cart['product_groups'][$group_id]['shippings'][$shipping['shipping_id']]['data']['stores'])
            ) {
                continue;
            }

            unset($cart['product_groups'][$group_id]['shippings'][$shipping['shipping_id']]['data']);
        }
    }
}

/**
 * The "order_fulfillment_set_marketplace_shipping_to_product_group" hook handler.
 *
 * Actions performed:
 *     - Adds selected store data to all product groups which are using marketplace shipping
 *
 * @param array $cart                  Cart contents
 * @param int   $group_key             Group key
 * @param int   $marketplace_group_key Marketplace group key
 *
 * @see \onPrePlaceOrder()
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_store_locator_order_fulfillment_set_marketplace_shipping_to_product_group(array &$cart, $group_key, $marketplace_group_key)
{
    if (empty($cart['select_store'][$marketplace_group_key])) {
        return;
    }

    $cart['select_store'][$group_key] = $cart['select_store'][$marketplace_group_key];
}

/**
 * The "commerceml_order_storage_update_order_cart_calculation_pre" hook handler.
 *
 * Actions performed:
 *     - Adds selected store from old order to cart contents
 *
 * @param array $cart Cart contents
 *
 * @see \Tygh\Addons\CommerceML\Storages\OrderStorage::updateOrder()
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_store_locator_commerceml_order_storage_update_order_cart_calculation_pre(array &$cart)
{
    if (empty($cart['shipping'])) {
        return;
    }

    foreach ($cart['shipping'] as $_shipping) {
        if (empty($_shipping['module']) || $_shipping['module'] !== 'store_locator' || empty($_shipping['store_location_id'])) {
            continue;
        }
        $cart['select_store'][$_shipping['group_key']][$_shipping['shipping_id']] = $_shipping['store_location_id'];
    }
}
