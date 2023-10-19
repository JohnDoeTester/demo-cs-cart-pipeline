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

use MaxMind\Db\Reader;
use Tygh\Enum\LocalizationTypes;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\SiteArea;
use Tygh\Languages\Languages;
use Tygh\Registry;
use Tygh\Enum\DestinationElementTypes;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Gets countries list
 *
 * @param array  $params         search params
 * @param int    $items_per_page number of countries per page. Gets all if zero
 * @param string $lang_code      language code
 *
 * @return array 2 elements array, first - found countries, second - filtered input params
 */
function fn_get_countries($params, $items_per_page = 0, $lang_code = CART_LANGUAGE)
{
    // Set default values to input params
    $default_params = array (
        'page' => 1,
        'items_per_page' => $items_per_page
    );

    /**
     * Change parameters for getting countries list
     *
     * @param array  $params         Params list
     * @param int    $items_per_page Countries per page
     * @param string $lang_code      Language code
     * @param array  $default_params Default params
     */
    fn_set_hook('get_countries_pre', $params,  $items_per_page, $lang_code, $default_params);

    $params = array_merge($default_params, $params);

    // Unset all SQL variables
    $fields = $joins = array();
    $condition = $sorting = $limit = $group = '';

    $fields = array(
        'a.code',
        'a.code_A3',
        'a.code_N3',
        'a.status',
        'a.region',
        'b.country'
    );

    $condition = 'WHERE 1';
    if (!empty($params['only_avail'])) {
        $condition .= db_quote(" AND a.status = ?s", 'A');
    }

    if (!empty($params['q'])) {
        $condition .= db_quote(" AND b.country LIKE ?l", '%' . $params['q'] . '%');
    }

    if (!empty($params['country_codes'])) {
        $condition .= db_quote(" AND a.code IN (?a)", (array)$params['country_codes']);
    }

    $joins[] = db_quote("LEFT JOIN ?:country_descriptions as b ON b.code = a.code AND b.lang_code = ?s", $lang_code);

    $limit = '';
    if (!empty($params['items_per_page'])) {
        $merge_joins = implode(' ', $joins);
        $params['total_items'] = db_get_field("SELECT count(*) FROM ?:countries as a $merge_joins $condition");
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $sorting = "ORDER BY b.country";

    /**
     * Prepare params for getting countries SQL query
     *
     * @param array  $params         Params list
     * @param int    $items_per_page Countries per page
     * @param string $lang_code      Language code
     * @param array  $fields         Fields list
     * @param array  $joins          Joins list
     * @param string $condition      Conditions query
     * @param string $group          Group condition
     * @param string $sorting        Sorting condition
     * @param string $limit          Limit condition
     */
    fn_set_hook('get_countries', $params,  $items_per_page, $lang_code, $fields, $joins, $condition, $group, $sorting, $limit);

    $countries = db_get_array("SELECT " . implode(', ', $fields) . " FROM ?:countries as a ". implode(' ', $joins)." $condition $group $sorting $limit");

    /**
     * Actions after countries list was prepared
     *
     * @param array  $params         Params list
     * @param int    $items_per_page States per page
     * @param string $lang_code      Language code
     * @param array  $states         List of selected states
     */
    fn_set_hook('get_countries_post', $params,  $items_per_page, $lang_code, $countries);

    return array($countries, $params);
}

/**
 * Gets countries list
 *
 * @param bool $avail_only if set to true - gets only enabled countries
 * @param string $lang_code language code
 * @return array key-value array with country code as key and name as value
 */
function fn_get_simple_countries($avail_only = false, $lang_code = CART_LANGUAGE)
{
    $avail_cond = ($avail_only == true)  ? "WHERE a.status = 'A'" : '';

    return db_get_hash_single_array("SELECT a.code, b.country FROM ?:countries as a LEFT JOIN ?:country_descriptions as b ON b.code = a.code AND b.lang_code = ?s $avail_cond ORDER BY b.country", array('code', 'country'), $lang_code);
}

/**
 * Gets phone country codes.
 *
 * @param bool   $avail_only If set to true - gets only enabled countries
 * @param string $lang_code  Language code
 *
 * @return array
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
 */
function fn_get_simple_phone_country_codes($avail_only = false, $lang_code = CART_LANGUAGE)
{
    // Unset all SQL variables
    $joins = [];

    $fields = [
        'a.code',
        'b.country'
    ];

    $condition = 'WHERE 1';

    if ($avail_only) {
        $condition .= db_quote(' AND a.status = ?s', ObjectStatuses::ACTIVE);
    }

    $joins[] = db_quote('LEFT JOIN ?:country_descriptions as b ON b.code = a.code AND b.lang_code = ?s', $lang_code);

    $countries = db_get_array(
        'SELECT ?p FROM ?:countries as a ?p ?p ORDER BY b.country',
        implode(', ', $fields),
        implode(' ', $joins),
        $condition
    );

    $phone_codes_schema = fn_get_schema('locations', 'phone_codes');
    $phone_codes = $phone_codes_schema['code_map'];
    $main_countries = $phone_codes_schema['main_countries'];

    foreach ($countries as $key => $value) {
        $code = $value['code'];

        $countries[$key]['phone_code'] = $phone_codes[$code];
        $countries[$key]['name'] = $value['country'];
        $countries[$key]['country_code'] = $value['code'];
        $countries[$key]['is_main_for_phone_code'] = in_array($code, $main_countries);

        $countries[strtolower($code)] = $countries[$key];
        unset($countries[$key]);
    }

    return $countries;
}

/**
 * Gets states list
 *
 * @param array  $params         search params
 * @param int    $items_per_page number of states per page. Gets all if zero
 * @param string $lang_code      language code
 *
 * @return array 2 elements array, first - found states, second - filtered input params
 */
function fn_get_states($params = [], $items_per_page = 0, $lang_code = CART_LANGUAGE)
{
    $default_params = [
        'page'           => 1,
        'items_per_page' => $items_per_page,
    ];

    /**
     * Change parameters for getting states list
     *
     * @param array  $params         Params list
     * @param int    $items_per_page States per page
     * @param string $lang_code      Language code
     * @param array  $default_params Default params
     */
    fn_set_hook('get_states_pre', $params,  $items_per_page, $lang_code, $default_params);

    $params = array_merge($default_params, $params);

    $fields = ['a.state_id', 'a.country_code', 'a.code', 'a.status', 'b.state', 'c.country'];
    $joins = [
        'state_desc'   => db_quote('LEFT JOIN ?:state_descriptions as b ON b.state_id = a.state_id AND b.lang_code = ?s', $lang_code),
        'country_desc' => db_quote('LEFT JOIN ?:country_descriptions as c ON c.code = a.country_code AND c.lang_code = ?s', $lang_code),
    ];

    $condition = 'WHERE 1=1';
    if (!empty($params['only_avail'])) {
        $condition .= db_quote(' AND a.status = ?s', 'A');
    }
    if (!empty($params['q'])) {
        $condition .= db_quote(' AND b.state LIKE ?l', '%' . $params['q'] . '%');
    }
    if (!empty($params['country_code'])) {
        $condition .= db_quote(' AND a.country_code = ?s', $params['country_code']);
    }

    $sorting = 'ORDER BY c.country, b.state';
    $limit = $group = '';
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field('SELECT count(*) FROM ?:states as a ?p', $condition);
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    /**
     * Prepare params for getting states SQL query
     *
     * @param array  $params         Params list
     * @param int    $items_per_page States per page
     * @param string $lang_code      Language code
     * @param array  $fields         Fields list
     * @param array  $joins          Joins list
     * @param string $condition      Conditions query
     * @param string $group          Group condition
     * @param string $sorting        Sorting condition
     * @param string $limit          Limit condition
     */
    fn_set_hook('get_states', $params, $items_per_page, $lang_code, $fields, $joins, $condition, $group, $sorting, $limit);

    $states = db_get_array(
        'SELECT ' . implode(', ', $fields) . ' FROM ?:states as a ?p ?p ?p ?p ?p',
        implode(' ', $joins), $condition, $group, $sorting, $limit
    );

    /**
     * Actions after states list was prepared
     *
     * @param array  $params         Params list
     * @param int    $items_per_page States per page
     * @param string $lang_code      Language code
     * @param array  $states         List of selected states
     */
    fn_set_hook('get_states_post', $params,  $items_per_page, $lang_code, $states);

    return array($states, $params);
}

/**
 * Gets states list for the country
 *
 * @param string $country_code country code
 * @param bool $avail_only if set to true - gets only enabled states
 * @param string $lang_code language code
 * @return array key-value array with country code as key and name as value
 */
function fn_get_country_states($country_code, $avail_only = true, $lang_code = CART_LANGUAGE)
{
    $condition = db_quote(" a.country_code = ?s", $country_code);
    if ($avail_only) {
        $condition .= db_quote(" AND a.status = ?s", 'A');
    }

    return db_get_hash_single_array("SELECT a.code, b.state FROM ?:states as a LEFT JOIN ?:state_descriptions as b ON b.state_id = a.state_id AND b.lang_code = ?s WHERE ?p ORDER BY b.state", array('code', 'state'), $lang_code, $condition);
}

/**
 * Gets all states
 *
 * @param bool $avail_only if set to true - gets only enabled states
 * @param string $lang_code language code
 * @return array multi key-value array with country code as key and array with state code and name as value
 */
function fn_get_all_states($avail_only = true, $lang_code = CART_LANGUAGE)
{
    $avail_cond = ($avail_only == true) ? " WHERE a.status = 'A' " : '';

    return db_get_hash_multi_array("SELECT a.country_code, a.code, b.state FROM ?:states as a LEFT JOIN ?:state_descriptions as b ON b.state_id = a.state_id AND b.lang_code = ?s $avail_cond ORDER BY a.country_code, b.state", array('country_code'), $lang_code);
}

// Get state name (results are cached)
function fn_get_state_name($state_code, $country_code, $lang_code = CART_LANGUAGE)
{
    static $states = array();

    if (!isset($states[$country_code][$state_code])) {
        $states[$country_code][$state_code] = db_get_field("SELECT ?:state_descriptions.state FROM ?:states LEFT JOIN ?:state_descriptions ON ?:state_descriptions.state_id = ?:states.state_id AND ?:state_descriptions.lang_code = ?s WHERE ?:states.country_code = ?s AND ?:states.code = ?s", $lang_code, $country_code, $state_code);
    }

    return $states[$country_code][$state_code];
}

// Get country name (results are cached)
function fn_get_country_name($country_code, $lang_code = CART_LANGUAGE)
{
    static $countries = array();
    if (empty($countries[$lang_code][$country_code])) {
        $countries[$lang_code][$country_code] = db_get_field("SELECT country FROM ?:country_descriptions WHERE code = ?s AND lang_code = ?s", $country_code, $lang_code);
    }

    return $countries[$lang_code][$country_code];
}

// Get countries name (results are cached)
function fn_get_countries_name($country_codes, $lang_code = CART_LANGUAGE)
{
    $countries = array();

    if (!empty($country_codes)) {
        $countries = db_get_hash_array("SELECT country, code FROM ?:country_descriptions WHERE code IN (?a) AND lang_code = ?s", 'code', $country_codes, $lang_code);
    }

    return $countries;
}

/**
 * Get all destinations list
 *
 * @param string $lang_code Language code
 * @param array  $params    Search parameters
 *
 * @return array
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_get_destinations($lang_code = CART_LANGUAGE, array $params = [])
{
    $joins = [];
    $limit = $group = '';

    $fields = [
        'a.destination_id',
        'a.status',
        'a.localization',
        'b.destination',
    ];

    $joins[] = db_quote('LEFT JOIN ?:destination_descriptions as b ON a.destination_id = b.destination_id AND b.lang_code = ?s', $lang_code);

    $condition = 'WHERE 1=1';
    $sorting = 'ORDER BY b.destination';

    /**
     * Prepare params for getting destinations SQL query
     *
     * @param string $lang_code Language code
     * @param array  $fields    Fields list
     * @param array  $join      Joins list
     * @param string $condition Conditions query
     * @param string $group     Group condition
     * @param string $sorting   Sorting condition
     * @param string $limit     Limit condition
     * @param array  $params    Search parameters
     */
    fn_set_hook('get_destinations', $lang_code, $fields, $joins, $condition, $group, $sorting, $limit, $params);

    if (isset($params['destination_id'])) {
        $condition .= db_quote(' AND a.destination_id = ?i', $params['destination_id']);
    }

    if (isset($params['status'])) {
        $condition .= db_quote(' AND a.status = ?s', $params['status']);
    }

    if (isset($params['localization'])) {
        $condition .= db_quote(' AND a.localization = ?s', $params['localization']);
    }

    if (isset($params['destination'])) {
        $condition .= db_quote(' AND a.destination = ?s', $params['destination']);
    }

    $destinations = db_get_hash_array(
        'SELECT ?p FROM ?:destinations as a ?p ?p ?p ?p ?p',
        'destination_id',
        implode(', ', $fields),
        implode(' ', $joins),
        $condition,
        $group,
        $sorting,
        $limit
    );

    if (isset($destinations[1])) {
        $default = $destinations[1];
        unset($destinations[1]);
        array_unshift($destinations, $default);
    }

    /**
     * Actions after getting destinations list
     *
     * @param string $lang_code    Language code
     * @param array  $destinations Destinations list
     */
    fn_set_hook('get_destinations_post', $lang_code, $destinations);

    return $destinations;
}

//
// Get destination name
//
function fn_get_destination_name($destination_id, $lang_code = CART_LANGUAGE)
{
    if (!empty($destination_id)) {
        return db_get_field("SELECT destination FROM ?:destination_descriptions WHERE destination_id = ?i AND lang_code = ?s", $destination_id, $lang_code);
    }

    return false;
}

//
// Helper for fn_get_available_destination function
//
// @$partial - check for partial equality
//
function fn_check_element($elms, $elm, $partial = false)
{
    if (empty($elm)) {
        return false;
    }
    $suitable = false;
    foreach ($elms as $k => $v) {
        if ($partial == true) {
            $__tmp = preg_quote($v, '/');
            $__tmp = str_replace(['\*', '\?'], ['.*', '.'], $__tmp);
            if (preg_match("/^{$__tmp}\$/iu", $elm)) {
                $suitable = true;
                break;
            }
        } else {
            if ($v == $elm) {
                $suitable = true;
                break;
            }
        }
    }

    return $suitable;
}

//
// Return most coincedence available destination by the following parameters...
//
function fn_get_available_destination($location)
{
    $result = false;
    static $country_states_list = [];

    /**
     * Prepare params for getting available destination
     *
     * @param array $location Location for which destination is required
     */
    fn_set_hook('get_available_destination_pre', $location);

    $country = !empty($location['country']) ? $location['country'] : '';
    $state = !empty($location['state']) ? $location['state'] : '';
    $zipcode = !empty($location['zipcode']) ? $location['zipcode'] : '';
    $city = !empty($location['city']) ? $location['city'] : '';
    $address = !empty($location['address']) ? $location['address'] : '';

    if (!empty($country)) {

        $state_id = fn_get_state_id($state, $country);

        if (!isset($country_states_list[$country])) {
            $country_states_list[$country] = fn_get_country_states($country);
        }

        $condition = '';
        if (AREA == 'C') {
            $condition .= fn_get_localizations_condition('localization');

            if (!empty($condition)) {
                $condition = db_quote('OR (1 ?p)', $condition);
            }
        }

        $__dests = db_get_array("SELECT a.* FROM ?:destination_elements as a LEFT JOIN ?:destinations as b ON b.destination_id = a.destination_id WHERE b.status = 'A' ?p", $condition);

        $destinations = array();
        foreach ($__dests as $k => $v) {
            $destinations[$v['destination_id']][$v['element_type']][] = $v['element'];
        }

        $concur_destinations = array();

        foreach ($destinations as $dest_id => $elm_types) {
            // Significance level. The more significance level means the most amount of coincidences
            $significance = 0;
            $dest_countries = !empty($elm_types['C']) ? $elm_types['C'] : array();
            foreach ($elm_types as $elm_type => $elms) {
                // Check country
                if ($elm_type == 'C') {
                    $suitable = fn_check_element($elms, $country);
                    if ($suitable == false) {
                        break;
                    }

                    $significance += 1 * (1 / count($elms));
                }

                // Check state
                if ($elm_type == 'S') {
                    // if country is in destanation_countries and it haven't got states,
                    // we suppose that destanation cover all country
                    if (!in_array($country, $dest_countries) || $country_states_list[$country]) {
                        $suitable = fn_check_element($elms, $state_id);
                        if ($suitable == false) {
                            break;
                        }
                    } else {
                        $suitable = true;
                    }
                    $significance += 2 * (1 / count($elms));
                }
                // Check city
                if ($elm_type == 'T') {
                    $suitable = fn_check_element($elms, $city, true);
                    if ($suitable == false) {
                        break;
                    }
                    $significance += 3 * (1 / count($elms));
                }
                // Check zipcode
                if ($elm_type == 'Z') {
                    $suitable = fn_check_element($elms, $zipcode, true);
                    if ($suitable == false) {
                        break;
                    }
                    $significance += 4 * (1 / count($elms));
                }

                // Check address
                if ($elm_type == 'A') {
                    $suitable = fn_check_element($elms, $address, true);
                    if ($suitable == false) {
                        break;
                    }
                    $significance += 5 * (1 / count($elms));
                }
            }

            $significance = number_format($significance, 4, '.', '');

            if ($suitable == true) {
                $concur_destinations[$significance][] = $dest_id;
            }
        }

        if (!empty($concur_destinations)) {
            ksort($concur_destinations, SORT_NUMERIC);
            $concur_destinations = array_pop($concur_destinations);

            $result = reset($concur_destinations);
        }
    }

    /**
     * Post processing of available destination
     *
     * @param array  $location            Location information
     * @param string $result              Available destination
     * @param array  $concur_destinations Destinations list
     */
    fn_set_hook('get_available_destination_post', $location, $result, $concur_destinations);

    return $result;
}

//
// Return state ID by it's code and country code
//
function fn_get_state_id($state = '', $country = '')
{
    static $state_ids;

    if (empty($state) || empty($country)) {
        return false;
    }

    if (empty($state_ids[$country][$state])) {
        $state_ids[$country][$state] = db_get_field("SELECT state_id FROM ?:states WHERE code = ?s AND country_code = ?s", $state, $country);
    }

    return $state_ids[$country][$state];
}

//
// Return lang_code from default browser language
//
function fn_get_browser_language($languages = array())
{
    if (empty($languages)) {
        return false;
    }

    $browser_language = false;

    if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $preg_string = fn_strtolower(implode('|' , array_keys($languages)));
        if (preg_match("/($preg_string)+(-|;|,)?(.*)?/", $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches)) {
            $browser_language = $matches[1];
        }
    }

    return $browser_language;
}

//
// Localizations
//

function fn_get_localizations($lang_code = CART_LANGUAGE, $status_check = false)
{
    $localizations = array();
    if (!Registry::get('config.tweaks.disable_localizations')) {
        $status_condition = ($status_check) ? db_quote(' WHERE a.status = ?s', ObjectStatuses::ACTIVE) : '';
        $language_condition = (isset(Tygh::$app['session']['auth']['area']) && (Tygh::$app['session']['auth']['area'] == 'A')) ? '' : "LEFT JOIN ?:localization_elements AS c ON a.localization_id = c.localization_id AND c.element_type = 'L' RIGHT JOIN ?:languages AS d ON c.element = d.lang_code AND d.status = 'A'";
        $localizations = db_get_hash_array('SELECT a.localization_id, a.status, b.localization, a.is_default FROM ?:localizations AS a LEFT JOIN ?:localization_descriptions AS b ON a.localization_id = b.localization_id AND b.lang_code = ?s ?p ?p ORDER BY localization', 'localization_id', $lang_code, $language_condition, $status_condition);
    }

    return $localizations;
}

function fn_get_simple_localizations($lang_code = CART_LANGUAGE, $status_check = false)
{
    $status_condition = ($status_check) ? db_quote(' WHERE a.status = ?s', ObjectStatuses::ACTIVE) : '';
    return db_get_hash_single_array(
        'SELECT a.localization_id, b.localization'
        . ' FROM ?:localizations AS a'
        . ' LEFT JOIN ?:localization_descriptions AS b ON a.localization_id = b.localization_id AND b.lang_code = ?s ?p'
        . ' ORDER BY localization',
        ['localization_id' , 'localization'],
        $lang_code,
        $status_condition
    );
}

function fn_get_localization_data($localization_id , $lang_code = CART_LANGUAGE, $additional_data = false)
{
    $loc_data = [];
    $loc_data['data'] = db_get_row(
        'SELECT a.localization_id, a.status, a.custom_weight_settings, a.weight_symbol, a.weight_unit, a.is_default, b.localization'
        . ' FROM ?:localizations AS a'
        . ' LEFT JOIN ?:localization_descriptions AS b ON a.localization_id = b.localization_id AND b.lang_code = ?s'
        . ' WHERE a.localization_id = ?i'
        . ' ORDER BY localization',
        $lang_code,
        $localization_id
    );

    if (empty($loc_data['data'])) {
        return [];
    }

    if ($additional_data === true) {
        $loc_data['countries'] = db_get_hash_single_array(
            'SELECT a.code, b.country'
            . ' FROM ?:countries AS a'
            . ' LEFT JOIN ?:country_descriptions AS b ON b.code = a.code AND b.lang_code = ?s'
            . ' LEFT JOIN ?:localization_elements AS c ON c.element_type = ?s AND c.element = a.code'
            . ' WHERE c.localization_id = ?i'
            . ' ORDER BY position',
            ['code', 'country'],
            $lang_code,
            LocalizationTypes::COUNTRIES,
            $localization_id
        );
        $loc_data['currencies'] = db_get_hash_single_array(
            'SELECT a.currency_id, a.description'
            . ' FROM ?:currency_descriptions AS a'
            . ' LEFT JOIN ?:localization_elements AS b ON b.element_type = ?s AND b.element = a.currency_id'
            . ' WHERE b.localization_id = ?i AND a.lang_code = ?s'
            . ' ORDER BY position',
            ['currency_id', 'description'],
            LocalizationTypes::CURRENCIES,
            $localization_id,
            $lang_code
        );
        $loc_data['languages'] = db_get_hash_single_array(
            'SELECT a.lang_code, a.name'
            . ' FROM ?:languages AS a'
            . ' LEFT JOIN ?:localization_elements AS b ON b.element_type = ?s AND b.element = a.lang_code'
            . ' WHERE b.localization_id = ?i'
            . ' ORDER BY position',
            ['lang_code', 'name'],
            LocalizationTypes::LANGUAGES,
            $localization_id
        );
    }

    return $loc_data;
}

function fn_implode_localizations($localizations)
{
    return empty($localizations) ? '' : implode($localizations, ',');
}

function fn_explode_localizations($localizations)
{
    return empty($localizations) ? '' : explode(',' , $localizations);
}

function fn_get_localizations_condition($db_field, $and = true, $localization = '')
{
    $localization = !empty($localization) ? $localization : (defined('CART_LOCALIZATION') ? CART_LOCALIZATION : '');
    return (empty($localization) || !SiteArea::isStorefront(AREA)) ? '' : (($and === true) ? ' AND' : '') . " (FIND_IN_SET($localization , $db_field))";
}

function fn_get_country_by_ip($ip)
{
    static $cached_ips = [];

    if (isset($cached_ips[$ip])) {
        return $cached_ips[$ip];
    }

    $code = '';

    try {
        static $reader = null;

        if ($reader === null) {
            $reader = new Reader(Registry::get('config.dir.lib') . 'other/maxmind/GeoLite2-Country.mmdb');
        }

        $geo_data = $reader->get(long2ip($ip));
        $code = !empty($geo_data['country']['iso_code'])
            ? $geo_data['country']['iso_code']
            : '';
    } catch (Exception $e) {
    }

    if (!$code && function_exists('geoip_country_code_by_name')) {
        $code = @geoip_country_code_by_name(long2ip($ip));
        $code = !empty($code) ? $code : '';
    }

    $cached_ips[$ip] = $code;

    return $code;
}

/**
 * Removes destinations.
 *
 * @param int[] $destination_ids Destination identifiers
 */
function fn_delete_destinations($destination_ids)
{
    $can_delete = true;

    /**
     * Adds additional actions before destinations deleting
     *
     * @param array<int>    $destination_ids   Destinations identifiers
     * @param bool          $can_delete        Check permissions
     */
    fn_set_hook('delete_destinations', $destination_ids, $can_delete);

    if (!$can_delete) {
         return false;
    }

    foreach ($destination_ids as $dest_id) {
        if (!empty($dest_id) && $dest_id != 1) {
            db_query("DELETE FROM ?:destinations WHERE destination_id = ?i", $dest_id);
            db_query("DELETE FROM ?:destination_descriptions WHERE destination_id = ?i", $dest_id);
            db_query("DELETE FROM ?:destination_elements WHERE destination_id = ?i", $dest_id);
            db_query("DELETE FROM ?:shipping_rates WHERE destination_id = ?i", $dest_id);
            db_query("DELETE FROM ?:tax_rates WHERE destination_id = ?i", $dest_id);
        } elseif ((int) $dest_id === 1) {
            fn_set_notification(NotificationSeverity::WARNING, __('notice'), __('delete_default_destination_notice'));
        }
    }

    /**
     * Executes after rate areas are removed, allows you to clean up any associated data.
     *
     * @param int[] $destination_ids Destination identifiers
     */
    fn_set_hook('delete_destinations_post', $destination_ids);
}

/**
 * Adds or updates a destination.
 *
 * @param array  $data           Destination data
 * @param int    $destination_id Destination identifier
 * @param string $lang_code      Language code
 *
 * @return int $destination_id
 */
function fn_update_destination($data, $destination_id, $lang_code = DESCR_SL)
{
    /**
     * Adds additional params before adding or updating destination
     *
     * @param array $data Array of destination data
     * @param int $destination_id destination identifier
     * @param string $lang_code language code
     */
    fn_set_hook('update_destination_pre', $data, $destination_id, $lang_code);

    $data['localization'] = empty($data['localization']) ? '' : fn_implode_localizations($data['localization']);

    if (!empty($destination_id)) {
        db_query('UPDATE ?:destinations SET ?u WHERE destination_id = ?i', $data, $destination_id);
        db_query('UPDATE ?:destination_descriptions SET ?u WHERE destination_id = ?i AND lang_code = ?s', $data, $destination_id, $lang_code);
        db_query("DELETE FROM ?:destination_elements WHERE destination_id = ?i", $destination_id);
    } else {
        $destination_id = $data['destination_id'] = db_query("REPLACE INTO ?:destinations ?e", $data);

        foreach (Languages::getAll() as $data['lang_code'] => $_v) {
            db_query("REPLACE INTO ?:destination_descriptions ?e", $data);
        }

        if (isset($data['add_in_all_realtime_shippings'])) {
            $all_realtime_shippings = db_get_fields('SELECT shipping_id FROM ?:shippings WHERE rate_calculation = ?s', 'R');
            foreach ($all_realtime_shippings as $realtime_shipping_id) {
                db_replace_into('shipping_rates', [
                    'shipping_id'    => $realtime_shipping_id,
                    'destination_id' => $destination_id
                ]);
            }
        }
    }

    $_data = array(
        'destination_id' => $destination_id
    );

    if (!empty($data['states'])) {
        $_data['element_type'] = 'S';
        foreach ($data['states'] as $key => $_data['element']) {
            db_query("INSERT INTO ?:destination_elements ?e", $_data);
        }
    }

    if (!empty($data['countries'])) {
        $_data['element_type'] = 'C';
        foreach ($data['countries'] as $key => $_data['element']) {
            db_query("INSERT INTO ?:destination_elements ?e", $_data);
        }
    }

    if (!empty($data['zipcodes'])) {
        $zipcodes = explode("\n", $data['zipcodes']);
        $_data['element_type'] = 'Z';
        foreach ($zipcodes as $key => $value) {
            $value = trim($value);
            if (!empty($value)) {
                $_data['element'] = $value;
                db_query("INSERT INTO ?:destination_elements ?e", $_data);
            }
        }
    }

    if (!empty($data['cities'])) {
        $cities = explode("\n", $data['cities']);
        $_data['element_type'] = 'T';
        foreach ($cities as $key => $value) {
            $value = trim($value);
            if (!empty($value)) {
                $_data['element'] = $value;
                db_query("INSERT INTO ?:destination_elements ?e", $_data);
            }
        }
    }

    if (!empty($data['addresses'])) {
        $addresses = explode("\n", $data['addresses']);
        $_data['element_type'] = 'A';
        foreach ($addresses as $key => $value) {
            $value = trim($value);
            if (!empty($value)) {
                $_data['element'] = $value;
                db_query("INSERT INTO ?:destination_elements ?e", $_data);
            }
        }
    }

    /**
     * Executes after a rate area was updated, allows you to modify the data returned by the function.
     *
     * @param array  $data           Destination data
     * @param int    $destination_id Destination identifier
     * @param string $lang_code      Language code
     */
    fn_set_hook('update_destination_post', $data, $destination_id, $lang_code);

    return $destination_id;
}

/**
 * Gets states destination
 *
 * @param string                         $lang_code Language code
 * @param array<string, bool|string|int> $params    Query parameters
 *
 * @return array $states states destination
 */
function fn_destination_get_states($lang_code, $params = [])
{
    list($_states) = fn_get_states($params, 0, $lang_code);
    $states = array();
    foreach ($_states as $_state) {
        $states[$_state['state_id']] = $_state['country'] . ': ' . $_state['state'];
    }

    return $states;

}

/**
 * Gets short summary of all elements that rate area has.
 *
 * @param int    $destination_id
 * @param string $lang_code
 * @param array  $params
 * @return string
 */
function fn_destination_get_short_summary($destination_id, $lang_code = DESCR_SL, $params = [])
{
    $default_params = [
        DestinationElementTypes::STATE => [
            'length' => 2,
        ],
        DestinationElementTypes::COUNTRY => [
            'length' => 2,
        ],
        DestinationElementTypes::CITY => [
            'length' => 2,
        ],
        DestinationElementTypes::ZIPCODE => [
            'length' => 2,
        ],
    ];
    $params = array_merge($default_params, $params);
    $summary = '';
    $states = db_get_array('SELECT CONCAT(d.country, ?s, b.state) as state FROM ?:states as a LEFT JOIN ?:state_descriptions as b ON b.state_id = a.state_id AND b.lang_code = ?s LEFT JOIN ?:destination_elements as c ON c.element_type = ?s AND c.element = a.state_id LEFT JOIN ?:country_descriptions as d ON d.code = a.country_code AND d.lang_code = ?s WHERE c.destination_id = ?i', ': ', $lang_code, DestinationElementTypes::STATE, $lang_code, $destination_id);
    $states = array_column($states, 'state');
    if (count($states) > $params[DestinationElementTypes::STATE]['length']) {
        $summary .= __('states') . ': ' . count($states);
    } else {
        $states = implode(', ', $states);
        if ($states) {
            $summary .= $states;
        }
    }
    $countries = db_get_array('SELECT b.country FROM ?:countries as a LEFT JOIN ?:country_descriptions as b ON b.code = a.code AND b.lang_code = ?s LEFT JOIN ?:destination_elements as c ON c.element_type = ?s AND c.element = a.code WHERE c.destination_id = ?i', $lang_code, DestinationElementTypes::COUNTRY, $destination_id);
    $countries = array_column($countries, 'country');
    if (count($countries) > $params[DestinationElementTypes::COUNTRY]['length']) {
        if ($summary) {
            $summary .= ', ';
        }
        $summary .= __('countries') . ': ' . count($countries);
    } else {
        $countries = implode(', ', $countries);
        if ($countries) {
            if ($summary) {
                $summary .= ', ';
            }
            $summary .= $countries;
        }
    }
    $zipcodes = db_get_array('SELECT element FROM ?:destination_elements WHERE element_type = ?s AND destination_id = ?i', DestinationElementTypes::ZIPCODE, $destination_id);
    $zipcodes = array_column($zipcodes, 'element');
    if (count($zipcodes) > $params[DestinationElementTypes::ZIPCODE]['length']) {
        if ($summary) {
            $summary .= ', ';
        }
        $summary .= __('zipcodes') . ': ' . count($zipcodes);
    } else {
        $zipcodes = implode(', ', $zipcodes);
        if ($zipcodes) {
            if ($summary) {
                $summary .= ', ';
            }
            $summary .= $zipcodes;
        }
    }
    $cities = db_get_array('SELECT element FROM ?:destination_elements WHERE element_type = ?s AND destination_id = ?i', DestinationElementTypes::CITY, $destination_id);
    $cities = array_column($cities, 'element');
    if (count($cities) > $params[DestinationElementTypes::CITY]['length']) {
        if ($summary) {
            $summary .= ', ';
        }
        $summary .= __('cities') . ': ' . count($cities);
    } else {
        $cities = implode(', ', $cities);
        if ($cities) {
            if ($summary) {
                $summary .= ', ';
            }
            $summary .= $cities;
        }
    }
    return $summary;
}

/**
 * Selects needed destinations for destination picker
 *
 * @param array $params
 * @param string $lang_code
 *
 * @return array
 */
function fn_get_destinations_for_picker(array $params, $lang_code = DESCR_SL)
{
    $params = array_merge(
        [
            'ids'               => [],
            'shipping_id'       => null,
            'page'              => null,
            'page_size'         => null,
            'q'                 => '',
        ],
        $params
    );
    $destinations = fn_get_destinations($lang_code);
    if ($params['ids']) {
        $destinations = array_filter(
            $destinations,
            function ($destination) use ($params) {
                return in_array($destination['destination_id'], $params['ids']);
            }
        );
    }
    if ($params['q'] !== '') {
        $destinations = array_filter(
            $destinations,
            function ($destination) use ($params) {
                $search_critiera = fn_strtolower($params['q']);
                $destination_name = fn_strtolower($destination['destination']);
                return strstr($destination_name, $search_critiera) !== false;
            }
        );
    }

    $shipping_info = fn_get_shipping_info($params['shipping_id'], $lang_code);
    $destinations = array_map(
        function ($destination) use ($shipping_info) {
            $destination_id = (int) $destination['destination_id'];
            $destination_name = $destination['destination'];
            $is_active = $destination['status'] === ObjectStatuses::ACTIVE;
            $additional_class = !$is_active ? 'shipping-rate-area-d cm-row-status-d' : '';
            $rate = $shipping_info['rates'][$destination_id];
            return [
                'id'   => $destination_id,
                'text' => $destination_name,
                'data' => [
                    'destination'      => $destination_name,
                    'destination_id'   => $destination_id,
                    'description'      => fn_destination_get_short_summary($destination_id),
                    'additional_class' => $additional_class,
                    'is_disabled'      => !$is_active,
                    'is_active'        => $is_active,
                    'is_selected'      => isset($rate['rates_defined']),
                    'delivery_time'    => isset($rate['delivery_time'])
                        ? $rate['delivery_time']
                        : '',
                    'price'       => isset($rate['base_rate'])
                        ? $rate['base_rate']
                        : 0,
                    'rate_value'    => isset($rate['rate_value'])
                        ? $rate['rate_value']
                        : [],
                ],
            ];
        },
        $destinations
    );

    $objects = array_filter($destinations, static function ($destination) {
        return $destination['data']['is_active'] || $destination['data']['is_selected'];
    });

    if ($params['page'] && $params['page_size']) {
        $objects = array_slice($objects, ($params['page'] - 1) * $params['page_size'], $params['page_size']);
    }
    $total_objects = count($destinations);
    return [$objects, $total_objects];
}

/**
 * Returns a hash of available countries.
 *
 * @return string
 *
 * @internal
 */
function fn_get_hash_of_available_countries()
{
    Registry::registerCache('available_countries_hash', ['countries'], Registry::cacheLevel('static'));

    if (Registry::isExist('available_countries_hash')) {
        $avail_countries_hash = Registry::get('available_countries_hash');
    } else {
        $avail_countries_hash = md5(json_encode(fn_get_simple_countries(true)));
        Registry::set('available_countries_hash', $avail_countries_hash);
    }

    return $avail_countries_hash;
}
