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

use Tygh\BlockManager\Block;
use Tygh\BlockManager\Layout;
use Tygh\BlockManager\ProductTabs;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\ProfileTypes;
use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Enum\VendorStatuses;
use Tygh\Enum\YesNo;
use Tygh\Languages\Languages;
use Tygh\Menu;
use Tygh\Navigation\LastView;
use Tygh\Registry;
use Tygh\Settings;
use Tygh\Tools\SecurityHelper;
use Tygh\Tools\Url;
use Tygh\Tygh;

/**
 * Gets brief company data array: <i>(company_id => company_name)</i>
 *
 * @param array $params Array of search params:
 * <ul>
 *        <li>string status - Status field from the <i>?:companies table</i></li>
 *        <li>string item_ids - Comma separated list of company IDs</li>
 *        <li>int displayed_vendors - Number of companies for displaying. Will be used as LIMIT condition</i>
 * </ul>
 * Global variable <i>$_REQUEST</i> can be passed as argument
 * @return mixed If <i>$params</i> was not empty returns array:
 * <ul>
 *   <li>companies - Hash array of companies <i>(company_id => company)</i></li>
 *   <li>count - Number of returned companies</li>
 * </ul>
 * else returns hash array of companies <i>(company_id => company)</i></li>
 */
function fn_get_short_companies($params = array())
{
    $condition = $limit = $join = $companies = '';

    if (!empty($params['status'])) {
        $condition .= db_quote(" AND ?:companies.status = ?s ", $params['status']);
    }

    if (!empty($params['item_ids'])) {
        $params['item_ids'] = fn_explode(",", $params['item_ids']);
        $condition .= db_quote(" AND ?:companies.company_id IN (?n) ", $params['item_ids']);
    }

    if (!empty($params['displayed_vendors'])) {
        $limit = 'LIMIT ' . $params['displayed_vendors'];
    }

    $condition .= Registry::get('runtime.company_id') ? fn_get_company_condition('?:companies.company_id', true, Registry::get('runtime.company_id')) : '';

    fn_set_hook('get_short_companies', $params, $condition, $join, $limit);

    $count = db_get_field("SELECT COUNT(*) FROM ?:companies $join WHERE 1 $condition");

    $_companies = db_get_hash_single_array("SELECT ?:companies.company_id, ?:companies.company FROM ?:companies $join WHERE 1 $condition ORDER BY ?:companies.company $limit", array('company_id', 'company'));

    $companies = array();
    if (!fn_allowed_for('ULTIMATE')) {
        $companies[0] = Registry::get('settings.Company.company_name');
        $companies = $companies + $_companies;
    } else {
        $companies = $_companies;
    }

    $return = array(
        'companies' => $companies,
        'count' => $count,
    );

    if (!empty($params)) {
        unset($return['companies'][0]);

        return array($return);
    }

    return $companies;
}

/**
 * Gets company name by id.
 *
 * @param int                           $company_id                 Company id
 * @param string                        $zero_company_name_lang_var If <i>$company_id</i> is empty, this name will be returned (used in MVE for pages and shippings)
 * @param string                        $lang_code                  Language code
 * @param array{use_i18n_fields?: bool} $params                     Additional parameters
 *
 * @staticvar array $cache_names Static cache for company names
 *
 * @return mixed Company name string in case company name for the given id is found, <i>null</i> otherwise
 */
function fn_get_company_name($company_id, $zero_company_name_lang_var = '', $lang_code = DESCR_SL, array $params = [])
{
    static $cache_names = [];

    if (empty($company_id)) {
        return __($zero_company_name_lang_var);
    }

    if (!empty($params['use_i18n_fields'])) {
        if (!isset($cache_names[$lang_code][$company_id])) {
            $cache_names[$lang_code][$company_id] = db_get_field(
                'SELECT i18n_company as company FROM ?:company_descriptions WHERE company_id = ?i AND lang_code = ?s',
                $company_id,
                $lang_code
            );
        }
        return $cache_names[$lang_code][$company_id];
    } elseif (!isset($cache_names[$company_id])) {
        if (Registry::get('runtime.company_id') === $company_id) {
            $cache_names[$company_id] = Registry::get('runtime.company_data.company');
        } else {
            $cache_names[$company_id] = db_get_field('SELECT company FROM ?:companies WHERE company_id = ?i', $company_id);
        }

        return $cache_names[$company_id];
    }

    return $cache_names[$company_id];
}

/**
 * Gets company data array
 *
 * @param array $params Array of search params:
 * <ul>
 *		  <li>string company - Name of company</li>
 *		  <li>string status - Status of company</li>
 *		  <li>string email - Email of company</li>
 *		  <li>string address - Address of company</li>
 *		  <li>string zipcode - Zipcode of company</li>
 *		  <li>string country - 2-letters country code of company country</li>
 *		  <li>string state - State code of company</li>
 *		  <li>string city - City of company</li>
 *		  <li>string phone - Phone of company</li>
 *		  <li>string url - URL address of company</li>
 *		  <li>mixed company_id - Company ID, array with company IDs or comma-separated list of company IDs.
 * If defined, data will be returned only for companies with such company IDs.</li>
 *		  <li>int exclude_company_id - Company ID, if defined,
 * result array will not include the data for company with such company ID.</li>
 *		  <li>int page - First page to displaying list of companies (if <i>$items_per_page</i> it not empty.</li>
 *		  <li>string sort_order - <i>ASC</i> or <i>DESC</i>: database query sorting order</li>
 *		  <li>string sort_by - One or list of database fields for sorting.</li>
 * </ul>
 * @param array $auth Array of user authentication data (e.g. uid, usergroup_ids, etc.)
 * @param int $items_per_page
 * @param string $lang_code 2-letter language code (e.g. 'en', 'ru', etc.)
 * @return array Array:
 * <ul>
 *		<li>0 - First element is array with companies data.</li>
 *		<li>1 - is possibly modified array with searh params (<i>$params</i>).</li>
 * </ul>
 */
function fn_get_companies($params, &$auth, $items_per_page = 0, $lang_code = CART_LANGUAGE)
{
    // Init filter
    $_view = 'companies';

    /**
     * Changes params for selecting companies
     *
     * @param array  $params         Companies search params
     * @param int    $items_per_page Items per page
     * @param string $lang_code      Two-letter language code (e.g. 'en', 'ru', etc.)
     */
    fn_set_hook('get_companies_pre', $params, $items_per_page, $lang_code);

    $params = LastView::instance()->update($_view, $params);

    // Set default values to input params
    $default_params = [
        'page' => 1,
        'items_per_page' => $items_per_page
    ];

    $params = array_merge($default_params, $params);

    // Define fields that should be retrieved
    $fields = [
        '?:companies.company_id',
        '?:companies.lang_code',
        '?:companies.email',
        '?:companies.timestamp',
        '?:companies.status',
    ];

    if (fn_allowed_for('ULTIMATE')) {
        $fields[] = '?:companies.storefront';
        $fields[] = '?:companies.secure_storefront';
    }

    // Define sort fields
    $sortings = [
        'id'      => '?:companies.company_id',
        'email'   => '?:companies.email',
        'date'    => '?:companies.timestamp',
        'status'  => '?:companies.status',
    ];

    if (fn_allowed_for('ULTIMATE')) {
        $sortings['storefront'] = '?:companies.storefront';
    }

    $condition = $join = $group = '';

    if (fn_allowed_for('MULTIVENDOR') && !empty($params['use_i18n_fields'])) {
        $fields[] = 'company_descr.i18n_company as company';
        $sortings['company'] = 'company_descr.i18n_company';
        $join .= db_quote(
            ' LEFT JOIN ?:company_descriptions as company_descr ON company_descr.company_id = ?:companies.company_id AND company_descr.lang_code = ?s ',
            $lang_code
        );
    } else {
        $fields[] = '?:companies.company';
        $sortings['company'] = '?:companies.company';
    }

    $condition .= fn_get_company_condition('?:companies.company_id');

    $group .= ' GROUP BY ?:companies.company_id';

    if (isset($params['company']) && fn_string_not_empty($params['company'])) {
        $condition .= db_quote(' AND ?:companies.company LIKE ?l', '%' . trim($params['company']) . '%');
    }

    if (!empty($params['status'])) {
        if (is_array($params['status'])) {
            $condition .= db_quote(' AND ?:companies.status IN (?a)', $params['status']);
        } else {
            $condition .= db_quote(' AND ?:companies.status = ?s', $params['status']);
        }
    }

    if (isset($params['email']) && fn_string_not_empty($params['email'])) {
        $condition .= db_quote(' AND ?:companies.email LIKE ?l', '%' . trim($params['email']) . '%');
    }

    if (isset($params['address']) && fn_string_not_empty($params['address'])) {
        $condition .= db_quote(' AND ?:companies.address LIKE ?l', '%' . trim($params['address']) . '%');
    }

    if (isset($params['zipcode']) && fn_string_not_empty($params['zipcode'])) {
        $condition .= db_quote(' AND ?:companies.zipcode LIKE ?l', '%' . trim($params['zipcode']) . '%');
    }

    if (!empty($params['country'])) {
        $condition .= db_quote(' AND ?:companies.country = ?s', $params['country']);
    }

    if (isset($params['state']) && fn_string_not_empty($params['state'])) {
        $condition .= db_quote(' AND ?:companies.state LIKE ?l', '%' . trim($params['state']) . '%');
    }

    if (isset($params['state_code']) && fn_string_not_empty($params['state_code'])) {
        $condition .= db_quote(' AND ?:companies.state = ?s', $params['state_code']);
    }

    if (isset($params['city']) && fn_string_not_empty($params['city'])) {
        $condition .= db_quote(' AND ?:companies.city LIKE ?l', '%' . trim($params['city']) . '%');
    }

    if (isset($params['phone']) && fn_string_not_empty($params['phone'])) {
        $condition .= db_quote(' AND ?:companies.phone LIKE ?l', '%' . trim($params['phone']) . '%');
    }

    if (isset($params['url']) && fn_string_not_empty($params['url'])) {
        $condition .= db_quote(' AND ?:companies.url LIKE ?l', '%' . trim($params['url']) . '%');
    }

    if (!empty($params['company_id'])) {
        $condition .= db_quote(' AND ?:companies.company_id IN (?n)', $params['company_id']);
    }

    if (!empty($params['exclude_company_id'])) {
        $condition .= db_quote(' AND ?:companies.company_id != ?i', $params['exclude_company_id']);
    }

    if (!empty($params['created_from']) && !empty($params['created_to'])) {
        $condition .= db_quote(' AND ?:companies.timestamp BETWEEN ?i AND ?i', $params['created_from'], $params['created_to']);
    }

    if (!empty($params['not_login_from']) && !empty($params['not_login_to'])) {
        $join .= db_quote(' LEFT JOIN ?:users ON ?:users.company_id = ?:companies.company_id');
        $condition .= db_quote(
            ' AND ?:users.last_login NOT BETWEEN ?i AND ?i AND ?:companies.timestamp BETWEEN ?i AND ?i'
            . ' AND ?:users.status = ?s AND ?:users.user_type = ?s',
            $params['not_login_from'],
            $params['not_login_to'],
            $params['not_login_from'],
            $params['not_login_to'],
            ObjectStatuses::ACTIVE,
            UserTypes::VENDOR
        );
    }

    if (!empty($params['sales_from']) && !empty($params['sales_to'])) {
        $join .= db_quote(' LEFT JOIN ?:orders ON ?:orders.company_id = ?:companies.company_id');
        $condition .= db_quote(
            ' AND ?:orders.timestamp BETWEEN ?i AND ?i'
            . ' AND ?:orders.company_id != ?i',
            $params['sales_from'],
            $params['sales_to'],
            0
        );
    }

    if (!empty($params['extend']) && in_array('products', $params['extend'], true)) {
        $join .= db_quote(' LEFT JOIN ?:products ON ?:companies.company_id = ?:products.company_id');

        if (!empty($params['product_status']) && is_array($params['product_status'])) {
            $condition .= db_quote(' AND ?:products.status IN(?a)', $params['product_status']);
        }

        if (!empty($params['product_types']) && is_array($params['product_types'])) {
            $condition .= db_quote(' AND ?:products.product_type IN(?a)', $params['product_types']);
        }

        if (!empty($params['new_products_from']) && !empty($params['new_products_to'])) {
            $condition .= db_quote(
                'AND ?:products.timestamp BETWEEN ?i AND ?i',
                $params['new_products_from'],
                $params['new_products_to']
            );
        }
    }

    fn_set_hook('get_companies', $params, $fields, $sortings, $condition, $join, $auth, $lang_code, $group);

    $sorting = db_sort($params, $sortings, 'company', 'asc');

    // Paginate search results
    $limit = '';
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field('SELECT COUNT(DISTINCT(?:companies.company_id)) FROM ?:companies ?p WHERE 1 ?p', $join, $condition);
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    if (!empty($params['get_conditions'])) {
        return [$fields, $join, $condition];
    }

    $companies = db_get_array('SELECT ' . implode(', ', $fields) . ' FROM ?:companies ?p WHERE 1 ?p ?p ?p ?p', $join, $condition, $group, $sorting, $limit);

    if (!empty($params['extend'])) {
        $company_ids = array_column($companies, 'company_id');

        if ($company_ids && !empty($params['extend']['products_count'])) {
            $companies_products_count = fn_get_companies_active_products_count($company_ids);
        }

        $_extra_params = [];
        if (fn_allowed_for('MULTIVENDOR') && !empty($params['use_i18n_fields'])) {
            $_extra_params = [
                'use_i18n_fields' => true
            ];
        }

        foreach ($companies as &$company) {
            $company = empty($params['extend']['placement_info']) ?
                $company : fn_array_merge($company, fn_get_company_data($company['company_id'], $lang_code, $_extra_params));

            if (!empty($params['extend']['logos'])) {
                $company['logos'] = fn_get_logos($company['company_id']);
            }

            $company['products_count'] = empty($companies_products_count[$company['company_id']]) ? 0 : $companies_products_count[$company['company_id']];
        }
        unset($company);
    }

    /**
     * This hook allows you to modify the selection parameters of companies and the resulting list of companies.
     *
     * @param array   $params          Selection parameters
     * @param array   $auth            Array of user authentication data (e.g. uid, usergroup_ids, etc.)
     * @param int     $items_per_page  Items per page
     * @param string  $lang_code       2-letter language code (e.g. 'en', 'ru', etc.)
     * @param array   $companies       Information about the companies
     * */
    fn_set_hook('get_companies_post', $params, $auth, $items_per_page, $lang_code, $companies);

    return [$companies, $params];
}

/**
 * Checks if products belong to the current company.
 *
 * @param int|int[]|string|string[] $product_ids Product ID or array of product IDs
 * @param bool                      $notify      Whether notification must be shown on failed check
 *
 * @return bool
 */
function fn_company_products_check($product_ids, $notify = false)
{
    if (!empty($product_ids)) {

        $product_ids = (array) $product_ids;

        $company_condition = fn_get_company_condition('?:products.company_id');

        /**
         * Executes before executing database query when checking product ownership,
         * allows to modify SQL query parts.
         *
         * @param int|int[]|string|string[] $product_ids       Product ID or array of product IDs
         * @param bool                      $notify            Whether notification must be shown on failed check
         * @param string                    $company_condition Products filtering condition
         */
        fn_set_hook('company_products_check', $product_ids, $notify, $company_condition);

        $company_products_count = (int) db_get_field(
            'SELECT count(*) FROM ?:products WHERE product_id IN (?n) ?p',
            $product_ids,
            $company_condition
        );

        if (count($product_ids) === $company_products_count) {
            return true;
        } else {
            if ($notify) {
                fn_company_access_denied_notification();
            }

            return false;
        }
    }

    return true;
}

function fn_company_access_denied_notification()
{
    fn_set_notification('W', __('warning'), __('access_denied'), '', 'company_access_denied');
}

/**
 * Gets part of SQL-query with codition for company_id field.
 *
 * @staticvar array $sharing_schema Local static cache for sharing schema
 * @param string $db_field Field name (usually table_name.company_id)
 * @param bool $add_and Include or not AND keyword berofe condition.
 * @param mixed $company_id Company ID for using in SQL condition.
 * @param bool $show_admin Include or not company_id == 0 in condition (used in the MultiVendor Edition)
 * @param bool $force_condition_for_area_c Used in the MultiVendor Edition. By default, SQL codition should be empty in the customer area. But in some cases,
 * this condition should be enabled in the customer area. If <i>$force_condition_for_area_c</i> is set, condtion will be formed for the customer area.
 * @return string Part of SQL query with company ID condition
 */
function fn_get_company_condition($db_field = 'company_id', $add_and = true, $company_id = '', $show_admin = false, $force_condition_for_area_c = false)
{
    if (fn_allowed_for('ULTIMATE')) {
        // Completely remove company condition for sharing objects

        static $sharing_schema;

        if (empty($sharing_schema) && Registry::get('addons_initiated') === true) {
            $sharing_schema = fn_get_schema('sharing', 'schema');
        }

        // Check if table was passed
        if (strpos($db_field, '.')) {
            list($table, $field) = explode('.', $db_field);
            $table = str_replace('?:', '', $table);

            // Check if the db_field table is in the schema
            if (isset($sharing_schema[$table])) {
                return '';
            }

        } else {
            return '';
        }

        if (Registry::get('runtime.company_id') && !$company_id) {
            $company_id = Registry::get('runtime.company_id');
        }
    }

    if ($company_id === '') {
        $company_id = Registry::ifGet('runtime.company_id', '');
    }

    $skip_cond = AREA == 'C' && !$force_condition_for_area_c && !fn_allowed_for('ULTIMATE');

    if (!$company_id || $skip_cond) {
        $cond = '';
    } else {
        $cond = $add_and ? ' AND' : '';
        // FIXME 2tl show admin
        if ($show_admin && $company_id) {
            $cond .= db_quote(" $db_field IN (0, ?i)", $company_id);
        } elseif (is_array($company_id)) {
            $cond .= db_quote(" $db_field IN (?n)", $company_id);
        } else {
            $cond .= db_quote(" $db_field = ?i", $company_id);
        }
    }

    /**
     * Hook for changing result of function
     *
     * @param string $db_field                   Field name (usually table_name.company_id)
     * @param bool   $add_and                    Include or not AND keyword berofe condition.
     * @param mixed  $company_id                 Company ID for using in SQL condition.
     * @param bool   $show_admin                 Include or not company_id == 0 in condition (used in the
     *                                           MultiVendor Edition)
     * @param bool   $force_condition_for_area_c Used in the MultiVendor Edition. By default, SQL codition should be
     *                                           empty in the customer area. But in some cases, this condition should
     *                                           be enabled in the customer area. If <i>$force_condition_for_area_c</i>
     *                                           is set, condition will be formed for the customer area.
     * @param string $cond                       Final condition
     */
    fn_set_hook(
        'get_company_condition_post',
        $db_field,
        $add_and,
        $company_id,
        $show_admin,
        $force_condition_for_area_c,
        $cond
    );

    return $cond;
}

/**
 * Gets company data by it ID
 *
 * @staticvar array $company_data_cache Array with cached companies data
 * @param int $company_id Company ID
 * @param string $lang_code 2-letter language code (e.g. 'en', 'ru', etc.)
 * @param array $extra Array with extra parameters
 *
 * @return boolean|array with company data
 */
function fn_get_company_data($company_id, $lang_code = DESCR_SL, array $extra = [])
{
    static $company_data_cache = [];

    if (empty($company_id)) {
        return false;
    }

    $cache_key = md5($company_id . $lang_code . serialize($extra));

    if (!empty($extra['reset_cache'])) {
        $company_data_cache = [];
    }

    if (empty($extra['skip_cache']) && isset($company_data_cache[$cache_key])) {
        return $company_data_cache[$cache_key];
    }

    /**
     * Hook for changing incoming parameters
     *
     * @param int    $company_id Company ID
     * @param string $lang_code  2-letter language code (e.g. 'en', 'ru', etc.)
     * @param array  $extra      Array with extra parameters
     */
    fn_set_hook('get_company_data_pre', $company_id, $lang_code, $extra);

    $fields = array(
        'companies.*',
    );

    if (fn_allowed_for('MULTIVENDOR')) {
        $fields = array_merge(array(
            'company_descriptions.*',
        ), $fields);
    }

    $join = '';

    if (fn_allowed_for('MULTIVENDOR')) {
        $join .= db_quote(
            ' LEFT JOIN ?:company_descriptions AS company_descriptions'
            . ' ON company_descriptions.company_id = companies.company_id'
            . ' AND company_descriptions.lang_code = ?s',
            $lang_code
        );
    }

    $condition = '';

    /**
     * Hook for changing parameters before SQL query
     *
     * @param int    $company_id Company ID
     * @param string $lang_code  2-letter language code (e.g. 'en', 'ru', etc.)
     * @param array  $extra      Array with extra parameters
     * @param array  $fields     Array with tables fields for SQL query
     * @param string $join       String with SQL join statements
     * @param string $condition  String with conditions for the WHERE SQL statement
     */
    fn_set_hook('get_company_data', $company_id, $lang_code, $extra, $fields, $join, $condition);

    $company_data = db_get_row(
        'SELECT ' . implode(', ', $fields) . ' FROM ?:companies AS companies ?p'
        . ' WHERE companies.company_id = ?i ?p',
        $join,
        $company_id,
        $condition
    );

    if ($company_data) {
        $company_data['shippings_ids'] = !empty($company_data['shippings']) ? explode(',', $company_data['shippings']) : array();
        $company_data['countries_list'] = !empty($company_data['countries_list']) ? explode(',', $company_data['countries_list']) : array();
    }

    // Get alternative multilingual fields instead of main fields
    if (fn_allowed_for('MULTIVENDOR') && !empty($extra['use_i18n_fields'])) {
        $company_data['company'] = $company_data['i18n_company'] ?? $company_data['company'];
        if (isset($company_data['city'])) {
            $company_data['city'] = $company_data['i18n_city'] ?? $company_data['city'];
        }
        if (isset($company_data['address'])) {
            $company_data['address'] = $company_data['i18n_address'] ?? $company_data['address'];
        }
    }

    /**
     * Hook for changing result of function
     *
     * @param int    $company_id   Company ID
     * @param string $lang_code    2-letter language code (e.g. 'en', 'ru', etc.)
     * @param array  $extra        Array with extra parameters
     * @param array  $company_data Array with company data
     */
    fn_set_hook('get_company_data_post', $company_id, $lang_code, $extra, $company_data);

    if (empty($extra['skip_cache'])) {
        $company_data_cache[$cache_key] = $company_data;
    }

    return $company_data;
}

/**
 * Gets object's company ID value for given object from thegiven table.
 * Function checks is some object has the given company ID.
 *
 * @param string $table Table name
 * @param string $field Field name
 * @param mixed $field_value Value of given field
 * @param mixed $company_id Company ID for additional condition.
 * @return mixed Company ID or false, if check fails.
 */
function fn_get_company_id($table, $field, $field_value, $company_id = '')
{
    if (!db_has_table($table)) {
        return false;
    }

    $condition = ($company_id !== '') ? db_quote(' AND company_id = ?i ', $company_id) : '';

    $id = db_get_field("SELECT company_id FROM ?:$table WHERE ?f = ?s $condition", $field, $field_value);

    return ($id !== NULL) ? $id : false;
}

/**
 * Gets runtime company_id in any mode
 *
 * @return int Company id | 0
 */
function fn_get_runtime_company_id()
{
    $company_id = Registry::ifGet('runtime.company_id', 0);
    if (!$company_id && Registry::get('runtime.simple_ultimate')) {
        $company_id = Registry::get('runtime.forced_company_id');
    }

    return $company_id;
}

/**
 * Gets company ID for the given company name
 *
 * @staticvar array $companies Little static cache for company ids
 * @param string $company_name Company name
 * @return integer Company ID or null, if company name was not found.
 */
function fn_get_company_id_by_name($company_name)
{
    static $companies = array();

    if (!empty($company_name)) {
        if (empty($companies[md5($company_name)])) {

            $condition = db_quote(' AND company = ?s', $company_name);

            /**
             * Hook get_company_id_by_name is executing before selecting the company ID by name.
             *
             * @param string $company_name Company name
             * @param string $condition 'Where' condition of SQL query
             */
            fn_set_hook('get_company_id_by_name', $company_name, $condition);

            $companies[md5($company_name)] = db_get_field("SELECT company_id FROM ?:companies WHERE 1 $condition");
        }

        return $companies[md5($company_name)];
    }

    return false;
}

function fn_get_available_company_ids($company_ids = array())
{
    $condition = '';
    if ($company_ids) {
        $condition = db_quote(' AND company_id IN (?n)', $company_ids);
    }

    $allowed_statuses = VendorStatuses::getList([VendorStatuses::DISABLED]);

    return db_get_fields('SELECT company_id FROM ?:companies WHERE 1 ?p AND status IN (?a)', $condition, $allowed_statuses);
}

function fn_check_company_id($table, $key, $key_id, $company_id = '')
{
    if (!db_has_table($table)) {
        return false;
    }

    if (!Registry::get('runtime.company_id')) {
        return true;
    }

    if ($company_id === '') {
        $company_id = Registry::get('runtime.company_id');
    }

    $id = db_get_field("SELECT ?f FROM ?:$table WHERE ?f = ?i AND company_id = ?i", $key, $key, $key_id, $company_id);

    return (!empty($id)) ? true : false;
}

/**
 * Function checks is given object is shared for selected store.
 *
 * @param string $object Name of object
 * @param int $object_id Object ID
 * @param int $company_id Company ID, if empty, value of Registry::get('runtime.company_id') will be used
 * @return boolean true if ojbect is shared for given company_id, false otherwise
 */
function fn_check_shared_company_id($object, $object_id, $company_id = '')
{
    if ($company_id === '') {
        if (!Registry::get('runtime.company_id')) {
            return true;
        }

        $company_id = Registry::get('runtime.company_id');
    }

    $id = db_get_field("SELECT share_company_id FROM ?:ult_objects_sharing WHERE share_object_type = ?s AND share_object_id = ?i AND share_company_id = ?i", $object, $object_id, $company_id);

    return (!empty($id)) ? true : false;
}

/**
 * Function checks is given object is shared for given stores.
 *
 * @param string $object Name of object
 * @param int $object_id Object ID
 * @param array $company_ids Company IDs
 * @return boolean true if ojbect is shared for given company_ids, false otherwise
 */
function fn_check_shared_company_ids($object, $object_id, $company_ids = array())
{
    if (empty($company_ids)) {
        return false;
    }

    $id = db_get_field("SELECT share_object_id FROM ?:ult_objects_sharing WHERE share_object_type = ?s AND share_object_id = ?i AND share_company_id IN (?n)", $object, $object_id, $company_ids);

    return (!empty($id)) ? true : false;
}

/**
 * Set company_id to actual company_id
 *
 * @param mixed $data Array with data
 */
function fn_set_company_id(&$data, $key_name = 'company_id', $only_defined = false)
{
    if (Registry::get('runtime.company_id')) {
        $data[$key_name] = Registry::get('runtime.company_id');
    } elseif (!isset($data[$key_name]) && !fn_allowed_for('ULTIMATE') && !$only_defined) {
        $data[$key_name] = 0;
    }
}

function fn_payments_set_company_id($order_id = 0, $company_id = 0, $area = AREA)
{
    if ($area != 'A' && fn_allowed_for('ULTIMATE')) {
        if (!empty($order_id)) {
            $company_id = db_get_field("SELECT company_id FROM ?:orders WHERE order_id = ?i", $order_id);
        }
        Registry::set('runtime.company_id', $company_id);
    }
}

function fn_get_companies_shipping_ids($company_id)
{
    static $company_shippings;

    if (isset($company_shippings[$company_id])) {
        return $company_shippings[$company_id];
    }

    $shippings = array();

    $companies_shippings = explode(',', db_get_field("SELECT shippings FROM ?:companies WHERE company_id = ?i", $company_id));
    $default_shippings = db_get_fields("SELECT shipping_id FROM ?:shippings WHERE company_id = ?i", $company_id);
    $shippings = array_merge($companies_shippings, $default_shippings);

    $company_shippings[$company_id] = $shippings;

    return $shippings;
}

function fn_update_company($company_data, $company_id = 0, $lang_code = CART_LANGUAGE)
{
    $can_update = true;

    /**
     * Update company data (running before fn_update_company() function)
     *
     * @param array   $company_data Company data
     * @param int     $company_id   Company identifier
     * @param string  $lang_code    Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param boolean $can_update   Flag, allows addon to forbid to create/update company
     */
    fn_set_hook('update_company_pre', $company_data, $company_id, $lang_code, $can_update);

    if ($can_update == false) {
        return false;
    }

    array_walk($company_data, 'fn_trim_helper');

    SecurityHelper::sanitizeObjectData('company', $company_data);

    if (Registry::get('runtime.company_id')) {
        if (fn_allowed_for('MULTIVENDOR')) {
            unset($company_data['shippings']);
        } elseif (fn_allowed_for('ULTIMATE')) {
            unset($company_data['storefront'], $company_data['secure_storefront']);
        }
    }

    if (fn_allowed_for('ULTIMATE') && !Registry::get('runtime.company_id')) {

        if (isset($company_data['storefront'])) {
            if (empty($company_data['storefront'])) {
                fn_set_notification('E', __('error'), __('storefront_url_not_defined'));

                return false;

            } else {
                if (empty($company_data['secure_storefront'])) {
                    $company_data['secure_storefront'] = $company_data['storefront'];
                }

                $company_data['storefront'] = Url::clean($company_data['storefront']);
                $company_data['secure_storefront'] = Url::clean($company_data['secure_storefront']);
            }
        }
    }

    unset($company_data['company_id']);
    $_data = $company_data;

    if (fn_allowed_for('MULTIVENDOR') && (!empty($_data['email']) || !empty($_data['company']) )) {
        // Check if company with same email already exists
        if (!empty($_data['email'])) {
            $is_exist = db_get_field('SELECT email FROM ?:companies WHERE company_id != ?i AND email = ?s', $company_id, $_data['email']);
            if (!empty($is_exist)) {
                $_text = 'error_vendor_exists';
                fn_set_notification(NotificationSeverity::ERROR, __('error'), __($_text));
            }
        }

        // Check if company with same name already exists
        if (!empty($_data['company'])) {
            if (fn_allowed_for('MULTIVENDOR')) {
                $name_exist = db_get_field(
                    'SELECT i18n_company FROM ?:company_descriptions WHERE company_id != ?i AND i18n_company = ?s AND lang_code = ?s',
                    $company_id,
                    $_data['company'],
                    $lang_code
                );
            } else {
                $name_exist = db_get_field('SELECT company FROM ?:companies WHERE company_id != ?i AND company = ?s', $company_id, $_data['company']);
            }
            if (!empty($name_exist)) {
                $_text = 'error_vendor_name_exists';
                fn_set_notification(NotificationSeverity::ERROR, __('error'), __($_text));
            }
        }

        if (!empty($is_exist) || !empty($name_exist)) {
            return false;
        }

        $_data['i18n_company'] = $_data['company'];
        if (isset($_data['city'])) {
            $_data['i18n_city'] = $_data['city'];
        }
        if (isset($_data['address'])) {
            $_data['i18n_address'] = $_data['address'];
        }
    }

    if (fn_allowed_for('ULTIMATE') && !empty($company_data['storefront'])) {
        // Check if company with the same Storefront URL already exists
        $http_exist = db_get_row('SELECT company_id, storefront FROM ?:companies WHERE storefront = ?s', $company_data['storefront']);
        $https_exist = db_get_row('SELECT company_id, secure_storefront FROM ?:companies WHERE secure_storefront = ?s', $company_data['secure_storefront']);

        if (!empty($http_exist) || !empty($https_exist)) {
            if (empty($company_id)) {
                if (!empty($http_exist)) {
                    fn_set_notification('E', __('error'), __('storefront_url_already_exists'));
                } else {
                    fn_set_notification('E', __('error'), __('secure_storefront_url_already_exists'));
                }

                return false;

            } elseif ((!empty($http_exist) && $company_id != $http_exist['company_id']) || (!empty($https_exist) && $company_id != $https_exist['company_id'])) {

                if (!empty($http_exist) && $company_id != $http_exist['company_id']) {
                    fn_set_notification('E', __('error'), __('storefront_url_already_exists'));
                    unset($_data['storefront']);
                } else {
                    fn_set_notification('E', __('error'), __('secure_storefront_url_already_exists'));
                    unset($_data['secure_storefront']);
                }

                return false;
            }
        }
    }

    if (isset($company_data['shippings'])) {
        $_data['shippings'] = fn_create_set($company_data['shippings']);
    }

    if (!empty($_data['countries_list'])) {
        $_data['countries_list'] = implode(',', $_data['countries_list']);
    } else {
        $_data['countries_list'] = '';
    }

    // add new company
    if (empty($company_id)) {
        // company title can't be empty
        if (empty($company_data['company'])) {
            fn_set_notification('E', __('error'), __('error_empty_company_name'));

            return false;
        }

        $_data['timestamp'] = isset($_data['timestamp']) ? fn_parse_date($_data['timestamp']) : TIME;

        $company_id = db_query("INSERT INTO ?:companies ?e", $_data);

        if (empty($company_id)) {
            return false;
        }

        $_data['company_id'] = $company_id;

        foreach (Languages::getAll() as $_data['lang_code'] => $_v) {
            db_query("INSERT INTO ?:company_descriptions ?e", $_data);
        }

        $action = 'add';

    // update company information
    } else {
        if (isset($company_data['company']) && empty($company_data['company'])) {
            unset($company_data['company']);
        }

        if (!empty($_data['lang_code']) && $_data['lang_code'] !== $lang_code) {
            unset($_data['company']);
            unset($_data['city']);
            unset($_data['address']);
        }
        if (!empty($_data['status'])) {
            $status_from = db_get_field("SELECT status FROM ?:companies WHERE company_id = ?i", $company_id);
        }
        db_query("UPDATE ?:companies SET ?u WHERE company_id = ?i", $_data, $company_id);

        if (isset($status_from) && $status_from != $_data['status']) {
            fn_change_company_status($company_id, $_data['status'], '', $status_from, true);
        }

        // unset data lang code as it determines company main language not description language
        unset($_data['lang_code']);
        db_query(
            "UPDATE ?:company_descriptions SET ?u WHERE company_id = ?i AND lang_code = ?s",
            $_data, $company_id, $lang_code
        );

        $action = 'update';
    }

    $logo_ids = [];
    if ($action === 'add' && fn_allowed_for('MULTIVENDOR')) {
        /** @var \Tygh\Storefront\Repository $storefront_repository */
        $storefront_repository = Tygh::$app['storefront.repository'];
        $storefront = $storefront_repository->findDefault();
        $logo_ids = fn_create_theme_logos_by_layout_id($storefront->theme_name, 0, $company_id, true);
    }

    /**
     * Update company data (running after fn_update_company() function)
     *
     * @param array  $company_data Company data
     * @param int    $company_id   Company integer identifier
     * @param string $lang_code    Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param string $action       Flag determines if company was created (add) or just updated (update).
     */
    fn_set_hook('update_company', $company_data, $company_id, $lang_code, $action);

    fn_attach_image_pairs('logotypes', 'logos', 0, $lang_code, $logo_ids);

    return $company_id;
}

function fn_delete_company($company_id)
{
    if (empty($company_id)) {
        return false;
    }

    $can_delete = true;

    /**
     * Performs company pre-delete actions
     *
     * @param int     $company_id Company integer identifier
     * @param boolean $can_delete Flag if company can be deleted
     */
    fn_set_hook('delete_company_pre', $company_id, $can_delete);

    $orders_count = db_get_field('SELECT COUNT(*) FROM ?:orders WHERE company_id = ?i', $company_id);

    if (fn_allowed_for('MULTIVENDOR')) {
        // Do not delete vendor if there're any orders associated with this company
        if ($orders_count) {
            fn_set_notification(NotificationSeverity::WARNING, __('warning'), __('unable_delete_vendor_orders_exists'), '', 'company_has_orders');
            $can_delete = false;
        }
    }

    if (fn_allowed_for('ULTIMATE')) {
        // Do not delete storefront if there're any orders associated with this company
        if ($orders_count) {
            fn_set_notification(NotificationSeverity::WARNING, __('warning'), __('unable_delete_storefront_orders_exists'), '', 'company_has_orders');
            $can_delete = false;
        // Forbid to delete the last company
        } elseif ((int) db_get_field('SELECT COUNT(*) FROM ?:companies') === 1) {
            fn_set_notification(NotificationSeverity::WARNING, __('warning'), __('unable_to_delete_last_storefront'));
            $can_delete = false;
        }
    }

    if ($can_delete == false) {
        return false;
    }

    $result = db_query("DELETE FROM ?:companies WHERE company_id = ?i", $company_id);

    // deleting categories
    $cat_ids = db_get_fields("SELECT category_id FROM ?:categories WHERE company_id = ?i", $company_id);
    foreach ($cat_ids as $cat_id) {
        fn_delete_category($cat_id, false);
        db_query("DELETE FROM ?:products_categories WHERE category_id = ?i", $cat_id);
    }

    // deleting products
    $product_ids = db_get_fields("SELECT product_id FROM ?:products WHERE company_id = ?i", $company_id);
    foreach ($product_ids as $product_id) {
        fn_delete_product($product_id);
    }

    // deleting shipping
    $shipping_ids = db_get_fields("SELECT shipping_id FROM ?:shippings WHERE company_id = ?i", $company_id);
    foreach ($shipping_ids as $shipping_id) {
        fn_delete_shipping($shipping_id);
    }

    if (fn_allowed_for('ULTIMATE')) {
        // deleting layouts
        $layouts = Layout::instance($company_id)->getList();
        foreach ($layouts as $layout_id => $layout) {
            Layout::instance($company_id)->delete($layout_id);
        }
    }

    $blocks = Block::instance($company_id)->getAllUnique();
    foreach ($blocks as $block) {
        Block::instance($company_id)->remove($block['block_id']);
    }

    $product_tabs = ProductTabs::instance($company_id)->getList();
    foreach ($product_tabs as $product_tab) {
        ProductTabs::instance($company_id)->delete($product_tab['tab_id'], true);
    }

    $_menus = Menu::getList(db_quote(" AND company_id = ?i" , $company_id));
    foreach ($_menus as $menu) {
        Menu::delete($menu['menu_id']);
    }


    db_query("DELETE FROM ?:company_descriptions WHERE company_id = ?i", $company_id);

    // deleting product_options
    $option_ids = db_get_fields("SELECT option_id FROM ?:product_options WHERE company_id = ?i", $company_id);
    foreach ($option_ids as $option_id) {
        fn_delete_product_option($option_id);
    }

    // deleting company admins and users
    if (Registry::get('settings.Stores.share_users') != 'Y') {
        $users_condition = db_quote(' OR company_id = ?i', $company_id);
    } else {
        $users_condition = '';

        $admin_ids = db_get_fields("SELECT user_id FROM ?:users WHERE company_id = ?i AND user_type = ?s", $company_id, 'A');
        foreach ($admin_ids as $admin_id) {
            fn_delete_user($admin_id);
        }

        // Unassign users from deleted company
        db_query('UPDATE ?:users SET company_id = 0 WHERE company_id = ?i', $company_id);
    }

    $user_ids = db_get_fields("SELECT user_id FROM ?:users WHERE company_id = ?i AND user_type = ?s ?p", $company_id, 'V', $users_condition);
    foreach ($user_ids as $user_id) {
        fn_delete_user($user_id);
    }

    // deleting pages
    $page_ids = db_get_fields("SELECT page_id FROM ?:pages WHERE company_id = ?i", $company_id);
    foreach ($page_ids as $page_id) {
        fn_delete_page($page_id);
    }

    // deleting promotions
    $promotion_ids = db_get_fields("SELECT promotion_id FROM ?:promotions WHERE company_id = ?i", $company_id);
    fn_delete_promotions($promotion_ids);

    // deleting features
    $feature_ids = db_get_fields("SELECT feature_id FROM ?:product_features WHERE company_id = ?i", $company_id);
    foreach ($feature_ids as $feature_id) {
        fn_delete_feature($feature_id);
    }

    // deleting logos
    $types = fn_get_logo_types();
    foreach ($types as $type => $data) {
        fn_delete_logo($type, $company_id);
    }

    $payment_ids = db_get_fields('SELECT payment_id FROM ?:payments WHERE company_id = ?i', $company_id);
    foreach ($payment_ids as $payment_id) {
        fn_delete_payment($payment_id);
    }

    // Delete sitemap sections and links
    $params = array(
        'company_id' => $company_id,
    );
    $section_ids = fn_get_sitemap_sections($params);
    fn_delete_sitemap_sections(array_keys($section_ids));

    /** @var \Tygh\Storefront\Repository $storefronts_repository */
    $storefronts_repository = Tygh::$app['storefront.repository'];
    /** @var \Tygh\Storefront\Storefront[] $storefronts */
    $storefronts = $storefronts_repository->findByCompanyId($company_id, false);
    foreach ($storefronts as $storefront) {
        $storefront_company_ids = $storefront->getCompanyIds();
        $storefront_company_ids = array_filter($storefront_company_ids, function($storefront_company_id) use ($company_id) {
            return $storefront_company_id != $company_id;
        });
        $storefront->setCompanyIds($storefront_company_ids);
        $storefronts_repository->save($storefront);
    }

    /**
     * Performs company post-delete actions
     *
     * @param int                           $company_id  Company integer identifier
     * @param bool                          $result      Company deletion result
     * @param \Tygh\Storefront\Storefront[] $storefronts Storefronts the company belonged to
     */
    fn_set_hook('delete_company', $company_id, $result, $storefronts);

    return $result;
}

function fn_chown_company($from, $to)
{
    // Only allow the superadmin to merge vendors

    if (
        empty($from)
        || empty($to)
        || Registry::get('runtime.company_id')
        || !isset(Tygh::$app['session']['auth']['user_id'])
        || !fn_check_user_access(Tygh::$app['session']['auth']['user_id'], 'merge_vendors')
        || !fn_check_user_access(Tygh::$app['session']['auth']['user_id'], 'manage_vendors')
    ) {
        return false;
    }

    // Chown & disable vendor's admin accounts
    db_query("UPDATE ?:users SET status = 'D', company_id = ?i WHERE company_id = ?i AND user_type = 'V'", $to, $from);

    $config = Registry::get('config');
    // select all tables that have `company_id` column and have names starting with `table_prefix`
    $tables = db_get_fields(
        "SELECT INFORMATION_SCHEMA.COLUMNS.TABLE_NAME"
        . " FROM INFORMATION_SCHEMA.COLUMNS"
        . " WHERE INFORMATION_SCHEMA.COLUMNS.COLUMN_NAME = ?s"
        . " AND INFORMATION_SCHEMA.COLUMNS.TABLE_NAME LIKE '?:%'"
        . " AND TABLE_SCHEMA = ?s",
        'company_id',
        $config['db_name']
    );

    $excluded_tables = [
        'companies',
        'company_descriptions',
        'cache',
        'vendor_styles'
    ];

    /**
     * Executes before merging the data of companies, allows to excluding tables from merging
     *
     * @param int    $from Company identifier from which data merging
     * @param int    $to   Company identifier into which data merging
     * @param array  $excluded_tables Array excluded tables
     * @param array  $tables          Array tables for merge
     */
    fn_set_hook('chown_company', $from, $to, $excluded_tables, $tables);

    foreach ($tables as $table) {
        $table = str_replace(Registry::get('config.table_prefix'), '', $table);

        if (in_array($table, $excluded_tables)) {
            continue;
        }

        if ($table == 'category_vendor_product_count') {
            db_query(
                "UPDATE ?:$table AS c
                    JOIN (SELECT SUM(product_count) AS sum_score
                        FROM ?:$table
                        WHERE company_id IN (?i, ?i)
                    ) AS grp
                ON c.company_id = c.company_id
                SET c.product_count = grp.sum_score
                WHERE c.company_id = ?i"
            , $from, $to, $to);
            db_query("DELETE FROM ?:$table WHERE company_id = ?i", $from);
        }

        db_query("UPDATE ?:$table SET company_id = ?i WHERE company_id = ?i", $to, $from);
    }

    return true;
}

/**
 * Function returns address of company and emails of company' departments.
 *
 * @param int                                   $company_id ID of company
 * @param string                                $lang_code  Language of retrieving data. If null, lang_code of company will be used.
 * @param array{use_i18n_company_fields?: bool} $params     Additional parameters
 *
 * @return array Company address, emails and lang_code.
 */
function fn_get_company_placement_info($company_id, $lang_code = null, array $params = [])
{
    $default_company_placement_info = Registry::get('settings.Company');

    if (empty($company_id)) {
        $company_placement_info = $default_company_placement_info;
        $company_placement_info['lang_code'] = !empty($lang_code) ? $lang_code : CART_LANGUAGE;
    } else {
        $company_data_params = [];
        if (fn_allowed_for('MULTIVENDOR') && !empty($params['use_i18n_company_fields'])) {
            $company_data_params['use_i18n_fields'] = true;
        }
        $company = fn_get_company_data($company_id, (!empty($lang_code) ? $lang_code : CART_LANGUAGE), $company_data_params);

        if (fn_allowed_for('ULTIMATE')) {
            $company_placement_info = Settings::instance(['company_id' => $company_id])->getValues('Company', Settings::CORE_SECTION, true, $company_id);
            $default_company_placement_info = $company_placement_info;
            if (isset($company['lang_code'])) {
                $company_placement_info['lang_code'] = $company['lang_code'];
            }
        } else {
            $company_placement_info = [
                'company_name'                  => $company['company'],
                'company_address'               => $company['address'],
                'company_city'                  => $company['city'],
                'company_country'               => $company['country'],
                'company_state'                 => $company['state'],
                'company_zipcode'               => $company['zipcode'],
                'company_phone'                 => $company['phone'],
                'company_phone_2'               => '',
                'company_website'               => $company['url'],
                'company_users_department'      => $company['email'],
                'company_site_administrator'    => $company['email'],
                'company_orders_department'     => $company['email'],
                'company_support_department'    => $company['email'],
                'company_newsletter_email'      => $company['email'],
                'company_tax_number'            => $company['tax_number'],
                'lang_code'                     => $company['lang_code'],
                'registered_from_storefront_id' => $company['registered_from_storefront_id'],
            ];
        }
    }

    $company_placement_info['company_id'] = $company_id;
    foreach ($default_company_placement_info as $k => $v) {
        $company_placement_info['default_' . $k] = $v;
    }

    $lang_code = !empty($lang_code) ? $lang_code : $company_placement_info['lang_code'];

    $company_placement_info['company_country_descr'] = fn_get_country_name($company_placement_info['company_country'], $lang_code);
    $company_placement_info['company_state_descr'] = fn_get_state_name(
        $company_placement_info['company_state'],
        $company_placement_info['company_country'],
        $lang_code
    );

    /**
     * Executes after company info was retrieved.
     * Allows to modify company info.
     *
     * @param int    $company_id             ID of company
     * @param string $lang_code              Language of retrieving data
     * @param array  $company_placement_info Company info
     */
    fn_set_hook('get_company_placement_info_post', $company_id, $lang_code, $company_placement_info);

    return $company_placement_info;
}

function fn_get_company_language($company_id)
{
    if (empty($company_id)) {
        return Registry::get('settings.Appearance.backend_default_language');
    } else {
        $company = fn_get_company_data($company_id);

        return empty($company['lang_code']) ? CART_LANGUAGE : $company['lang_code'];
    }
}

/**
 * Changes company status. Allowed statuses are A(ctive), P(ending) and D(isabled)
 *
 * @param int $company_id
 * @param string $status_to A or P or D
 * @param string $reason The reason of the change
 * @param string $status_from Previous status
 * @param boolean $skip_query By default false. Update query might be skipped if status is already changed.
 * @return boolean True on success or false on failure
 */
function fn_change_company_status($company_id, $status_to, $reason = '', &$status_from = '', $skip_query = false, $notify = true)
{
    /**
     * Actions before change company status
     *
     * @param int    $company_id  Company ID
     * @param string $status_to   Status to letter
     * @param string $reason      Reason text
     * @param string $status_from Status from letter
     * @param bool   $skip_query  Skip query flag
     * @param bool   $notify      Notify flag
     */
    fn_set_hook('change_company_status_pre', $company_id, $status_to, $reason, $status_from, $skip_query, $notify);

    if (empty($status_from)) {
        $status_from = db_get_field('SELECT status FROM ?:companies WHERE company_id = ?i', $company_id);
    }

    if (!in_array($status_to, VendorStatuses::getStatusesTo()) || $status_from === $status_to) {
        return false;
    }

    $result = $skip_query ? true : db_query('UPDATE ?:companies SET status = ?s WHERE company_id = ?i', $status_to, $company_id);

    if (!$result) {
        return false;
    }

    $company_data = fn_get_company_data($company_id, DESCR_SL, ['reset_cache' => true]);

    $account = '';
    if (
        $status_from === VendorStatuses::NEW_ACCOUNT
        && (
            $status_to === VendorStatuses::ACTIVE
            || $status_to === VendorStatuses::PENDING
        )
    ) {
        if (YesNo::toBool(Registry::ifGet('settings.Vendors.create_vendor_administrator_account', YesNo::YES))) {
            if (!empty($company_data['request_user_id'])) {
                $password_change_timestamp = db_get_field('SELECT password_change_timestamp FROM ?:users WHERE user_id = ?i', $company_data['request_user_id']);
                $_set = '';
                if (empty($password_change_timestamp)) {
                    $_set = ', password_change_timestamp = 1';
                }
                db_query('UPDATE ?:users SET company_id = ?i, user_type = ?s, is_root = ?s?p WHERE user_id = ?i', $company_id, UserTypes::VENDOR, YesNo::YES, $_set, $company_data['request_user_id']);

                $account = 'updated';

                $msg = __('new_administrator_account_created') . '<a href="' . fn_url('profiles.update?user_id=' . $company_data['request_user_id']) . '">' . __('you_can_edit_account_details') . '</a>';
                fn_set_notification(NotificationSeverity::NOTICE, __('notice'), $msg, 'K');

            } else {
                $request_account_data = (array) unserialize($company_data['request_account_data']);
                $_company_data = $company_data + $request_account_data;
                $_company_data['status'] = VendorStatuses::ACTIVE;

                if (!empty($_company_data['request_account_name'])) {
                    $_company_data['admin_username'] = $_company_data['request_account_name'];
                }

                $fields = isset($request_account_data['fields']) ? $request_account_data['fields'] : $_company_data['fields'];
                $user_data = fn_create_company_admin($_company_data, $fields, false);

                if (!empty($user_data['user_id'])) {
                    $account = 'new';
                }
            }
        }
    }

    if (empty($user_data)) {
        $user_id = db_get_field(
            'SELECT user_id FROM ?:users WHERE company_id = ?i AND is_root = ?s AND user_type = ?s',
            $company_id,
            YesNo::YES,
            UserTypes::VENDOR
        );

        $user_data = fn_get_user_info((int) $user_id);
    }

    /**
     * Actions between change company status and send mail
     *
     * @param int    $company_id   Company ID
     * @param string $status_to    Status to letter
     * @param string $reason       Reason text
     * @param string $status_from  Status from letter
     * @param bool   $skip_query   Skip query flag
     * @param bool   $notify       Notify flag
     * @param array  $company_data Company data
     * @param array  $user_data    User data
     * @param bool   $result       Updated flag
     * @param string $account      Account action
     */
    fn_set_hook(
        'change_company_status_before_mail',
        $company_id,
        $status_to,
        $reason,
        $status_from,
        $skip_query,
        $notify,
        $company_data,
        $user_data,
        $result,
        $account
    );

    if (
        $notify
        && !empty($company_data['email'])
    ) {
        $e_username = $e_account = $e_password = '';
        if (
            !empty($user_data['email'])
            && $status_from === VendorStatuses::NEW_ACCOUNT
            && (
                $status_to === VendorStatuses::ACTIVE
                || $status_to === VendorStatuses::PENDING
                || $status_to === VendorStatuses::SUSPENDED
            )
        ) {
            list($e_username, $e_account) = [$user_data['email'], $account];
            if ($account === 'new') {
                $e_password = $user_data['password1'];
            }
        }

        $company_info = fn_get_company_placement_info($company_id);

        $event_dispatcher = Tygh::$app['event.dispatcher'];
        $notification_settings_factory = Tygh::$app['event.notification_settings.factory'];
        $force_notification = [UserTypes::VENDOR => $notify];
        $notification_rules = $notification_settings_factory->create($force_notification);
        $data = [
            'company'       => $company_info,
            'to_company_id' => $company_id,
            'status_to'     => $status_to,
            'reason'        => $reason,
            'status_from'   => $status_from,
            'user_data'     => $user_data,
            'e_username'    => $e_username,
            'e_account'     => $e_account,
            'e_password'    => $e_password,
            'vendor_url'    => fn_url('', SiteArea::VENDOR_PANEL),
        ];

        $event_code = '';

        switch ($status_to) {
            case VendorStatuses::DISABLED:
                $data['status'] = 'disabled';
                $event_code = 'vendor_status_changed_disabled';
                break;
            case VendorStatuses::PENDING:
                $data['status'] = 'pending';
                $event_code = 'vendor_status_changed_pending';
                break;
            case VendorStatuses::ACTIVE:
                $data['status'] = 'active';
                $event_code = 'vendor_status_changed_active';
                break;
            case VendorStatuses::SUSPENDED:
                $data['status'] = 'suspended';
                $event_code = 'vendor_status_changed_suspended';
                break;
        }

        if ($status_from === VendorStatuses::SUSPENDED) {
            $event_code = 'vendor_status_changed_from_suspended';
        }

        $event_dispatcher->dispatch($event_code, $data, $notification_rules);
    }

    return $result;
}

function fn_get_company_by_product_id($product_id)
{
    return db_get_row("SELECT * FROM ?:companies AS com LEFT JOIN ?:products AS prod ON com.company_id = prod.company_id WHERE prod.product_id = ?i", $product_id);
}

/**
 * Gets IDs of companies that own specified products.
 *
 * @param int[] $product_ids
 *
 * @return array Returns product IDs groupped by the company ID:
 *               [
 *                   (int) company_id => (array) product_ids
 *               ]
 */
function fn_get_company_ids_by_product_ids(array $product_ids)
{
    $products_companies = db_get_hash_multi_array(
        'SELECT product_id, company_id FROM ?:products WHERE ?w',
        [
            'company_id',
            'product_id',
        ],
        [
            'product_id' => $product_ids,
        ]
    );

    array_walk($products_companies, function (&$products) {
        $products = array_keys($products);
    });

    return $products_companies;
}

function fn_get_companies_sorting()
{
    $sorting = array(
        'company' => array('description' => __('name'), 'default_order' => 'asc'),
    );

    fn_set_hook('companies_sorting', $sorting);

    return $sorting;
}

function fn_get_companies_sorting_orders()
{
    return array('asc', 'desc');
}

/**
 * Gets ids of all companies
 *
 * @staticvar array $all_companies_ids Static cache variable
 * @param boolean $renew_cache If defined, cache of companies ids will be renewed.
 * @return array Ids of all companies
 */
function fn_get_all_companies_ids($renew_cache = false)
{
    static $all_companies_ids = null;

    if ($all_companies_ids === null || $renew_cache) {
        $all_companies_ids = db_get_fields("SELECT company_id FROM ?:companies");
    }

    return $all_companies_ids;
}

function fn_get_default_company_id()
{
    return db_get_field("SELECT company_id FROM ?:companies WHERE status = 'A' ORDER BY company_id LIMIT 1");
}


/**
 * Gets default company id for storefront
 *
 * @param int $storefront_id Storefront ID.
 *
 * @return int Company ID
 */
function fn_get_default_company_id_for_storefront($storefront_id)
{
    /** @var \Tygh\Storefront\Repository $repository */
    $repository = Tygh::$app['storefront.repository'];

    $storefront = $repository->findById($storefront_id);
    if ($storefront) {
        $company_id = $storefront->getCompanyIds();
        return reset($company_id);
    }

    return 0;
}

function fn_set_data_company_id(&$data)
{
    if (fn_allowed_for('ULTIMATE')) {
        $data['company_id'] = Registry::get('runtime.company_id');
    }
}

function fn_get_ult_company_condition($db_field = 'company_id', $and = true, $company_id = '', $show_admin = false, $area_c = false)
{
    return (fn_allowed_for('ULTIMATE')) ? fn_get_company_condition($db_field, $and, $company_id, $show_admin, $area_c) : '';
}

/**
 * Creating company admin
 *
 * @param array<string, string|int|bool>   $company_data Company data
 * @param string|array<string, string|int> $fields       Fields list
 * @param bool                             $notify       Notify flag
 *
 * @return array
 */
function fn_create_company_admin($company_data, $fields = '', $notify = false)
{
    /**
     * Actions before creating company admin
     *
     * @param  array   $company_data Company data
     * @param  string  $fields       Fields list
     * @param  boolean $notify       Notify flag
     */
    fn_set_hook('create_company_admin_pre', $company_data, $fields, $notify);

    $user = [
        'fields' => $fields,
    ];

    $password_length = USER_PASSWORD_LENGTH;
    $min_password_length = (int) Registry::get('settings.Security.min_account_password_length');
    if ($min_password_length > $password_length) {
        $password_length = $min_password_length;
    }

    $user['create_vendor_admin'] = true;
    $user['user_type'] = UserTypes::VENDOR;
    $user['password1'] = fn_generate_password($password_length);
    $user['password2'] = $user['password1'];
    $user['status'] = !empty($company_data['status'])
        ? $company_data['status']
        : ObjectStatuses::ACTIVE;
    $user['company_id'] = $company_data['company_id'];
    $user['email'] = $company_data['email'];
    $user['company'] = $company_data['company'];
    $user['last_login'] = 0;
    $user['lang_code'] = $company_data['lang_code'];
    $user['password_change_timestamp'] = 1;
    $user['is_root'] = !empty($company_data['is_root']) ? $company_data['is_root'] : YesNo::NO;

    // Copy vendor admin billing and shipping addresses from the company's credentials
    $user['firstname'] = (!empty($company_data['admin_firstname'])) ? $company_data['admin_firstname'] : '';
    $user['b_firstname'] = $user['s_firstname'] = $user['firstname'];
    $user['lastname'] = (!empty($company_data['admin_lastname'])) ? $company_data['admin_lastname'] : '';
    $user['b_lastname'] = $user['s_lastname'] = $user['lastname'];

    if (isset($company_data['phone'])) {
        $user['b_phone'] = $user['s_phone'] = $user['phone'] = $company_data['phone'];
    }
    if (isset($company_data['url'])) {
        $user['url'] = $company_data['url'];
    }
    if (isset($company_data['address'])) {
        $user['b_address'] = $user['s_address'] = $company_data['address'];
    }
    if (isset($company_data['city'])) {
        $user['b_city'] = $user['s_city'] = $company_data['city'];
    }
    if (isset($company_data['country'])) {
        $user['b_country'] = $user['s_country'] = $company_data['country'];
    }
    if (isset($company_data['state'])) {
        $user['b_state'] = $user['s_state'] = $company_data['state'];
    }
    if (isset($company_data['zipcode'])) {
        $user['b_zipcode'] = $user['s_zipcode'] = $company_data['zipcode'];
    }

    /**
     * Actions before directly creating company admin
     *
     * @param  array   $company_data Company data
     * @param  string  $fields       Fields list
     * @param  boolean $notify       Notify flag
     * @param  array   $user         User data
     */
    fn_set_hook('create_company_admin', $company_data, $fields, $notify, $user);

    // Create new user, avoiding switching to the vendor admin's session ($null as the 3rd argument)
    list($added_user_id) = fn_update_user(0, $user, $null, false, $notify);
    if ($added_user_id) {
        $msg = sprintf('%s<a href="%s">%s</a>',
            __('new_administrator_account_created'),
            fn_url('profiles.update?user_id=' . $added_user_id),
            __('you_can_edit_account_details')
        );
        fn_set_notification('N', __('notice'), $msg, 'K');
        $user['user_id'] = $added_user_id;
        $user['user_login'] = db_get_field('SELECT user_login FROM ?:users WHERE user_id = ?i', $added_user_id);
    }

    /**
     * Actions after creating company admin
     *
     * @param  array   $company_data Company data
     * @param  string  $fields       Fields list
     * @param  boolean $notify       Notify flag
     * @param  array   $user         User data
     */
    fn_set_hook('create_company_admin_post', $company_data, $fields, $notify, $user);

    return $user;
}

/**
 * Determines whether company condition must be applied when selecting product data.
 *
 * @param int|string $product_id Product ID to get product data
 *
 * @return bool
 */
function fn_is_product_company_condition_required($product_id)
{
    $is_required = true;
    if (fn_allowed_for('ULTIMATE') && fn_ult_is_shared_product($product_id) == 'Y') {
        $is_required = false;
    }

    /**
     * Executes after company condition requirement is determined when selecting a product data,
     * allows to modify the requirement.
     *
     * @param int|string $product_id  Product ID to get product data
     * @param bool       $is_required Whether the company_condition is required
     */
    fn_set_hook('is_product_company_condition_required_post', $product_id, $is_required);

    return $is_required;
}

/**
 * Clones layout
 *
 * @param array    $data             This parameter is not used
 * @param int      $company_id       Source company ID.
 *                                   This parameter is deprecated and will be removed in v5.0.0.
 *                                   Use $storefront_id instead.
 * @param int      $to_company_id    Destination company ID.
 *                                   This parameter is deprecated and will be removed in v5.0.0.
 *                                   Use $to_storefront_id instead.
 * @param int|null $storefront_id    Source storefront ID
 * @param int|null $to_storefront_id Destination storefront ID
 *
 * @return bool
 */
function fn_clone_layouts($data, $company_id, $to_company_id, $storefront_id = null, $to_storefront_id = null)
{
    // We need to clone logos, not attached to any layout too
    $logos = fn_get_logos($company_id, 0, null, $storefront_id);

    if (!empty($logos)) {
        Registry::set('runtime.allow_upload_external_paths', true);

        foreach ($logos as $type => $logo) {
            fn_update_logo(
                [
                    'type'       => $logo['type'],
                    'layout_id'  => $logo['layout_id'],
                    'image_path' => empty($logo['image']['absolute_path'])
                        ? ''
                        : $logo['image']['absolute_path'],
                    'style_id' => $logo['style_id']
                        ? $logo['style_id']
                        : '',
                ],
                $logo['company_id']
                    ? $to_company_id
                    : $logo['company_id'],
                $logo['storefront_id']
                    ? $to_storefront_id
                    : $logo['storefront_id']
            );
        }

        Registry::set('runtime.allow_upload_external_paths', false);
    }

    // clean up layouts
    $layouts = Layout::instance($to_company_id, [], $to_storefront_id)->getList();
    foreach ($layouts as $layout) {
        Layout::instance($to_company_id, [], $to_storefront_id)->delete($layout['layout_id']);
    }

    return Layout::instance($company_id, [], $storefront_id)->copy($to_company_id, $to_storefront_id);
}

/**
 * Gets storefront name.
 * Used for storefronts picker only.
 *
 * @param int $storefront_id Storefront idenfitier
 *
 * @internal
 * @return \Tygh\Storefront\Storefront
 */
function fn_get_storefront($storefront_id)
{
    /** @var \Tygh\Storefront\Repository $storefront_repository */
    $storefront_repository = \Tygh::$app['storefront.repository'];
    $storefront = $storefront_repository->findById($storefront_id);

    if (!$storefront) {
        /** @var \Tygh\Storefront\Factory $storefront_factory */
        $storefront_factory = Tygh::$app['storefront.factory'];
        $storefront = $storefront_factory->getBlank();
    }

    return $storefront;
}

/**
 * Gets products count by companies
 *
 * @param int[] $company_ids Company IDs, allows to limit query by specified companies, all allowed company will be get by default
 *
 * @return array<int, int> Companies and their products count list
 */
function fn_get_companies_active_products_count(array $company_ids = [])
{
    $company_ids = array_filter($company_ids);
    $products_condition = (empty($company_ids)) ? '' : db_quote(' AND products.company_id IN (?n)', $company_ids);
    $params = [
        'only_short_fields' => true,
        'extend'            => ['companies', 'sharing'],
        'status'            => 'A',
        'get_conditions'    => true,
        'only_for_counting' => true
    ];

    list(, $joins, $conditions) = fn_get_products($params);

    $conditions .= $products_condition;
    $fields = [
        'company_id'     => 'products.company_id',
        'products_count' => 'COUNT(DISTINCT products.product_id) as products_count'
    ];

    $result = db_get_hash_single_array(
        'SELECT ?p'
        . ' FROM ?:products as products ?p'
        . ' WHERE 1=1?p'
        . ' GROUP BY products.company_id',
        ['company_id', 'products_count'],
        implode(', ', $fields),
        $joins,
        $conditions
    );

    foreach ($company_ids as $company_id) {
        $company_id = (int) $company_id;

        if (isset($result[$company_id])) {
            continue;
        }

        $result[$company_id] = 0;
    }

    return $result;
}

/**
 * Gets user with parameter 'is_root' for specified company
 *
 * @param int                            $company_id Company identifier
 * @param array<string, string|int>|null $auth       Auth data
 *
 * @return int User with ID is root
 */
function fn_get_company_root_admin_user_id($company_id, array $auth = null)
{
    if (empty($company_id)) {
        return 0;
    }

    if ($auth === null) {
        $auth = Tygh::$app['session']['auth'];
    }

    $params = [
        'company_id' => $company_id,
        'is_root'    => 'Y'
    ];

    list($users,) = fn_get_users($params, $auth);

    if (empty($users)) {
        return 0;
    }

    $user = reset($users);

    return $user['user_id'];
}

/**
 * Filtering company data by profile fields.
 *
 * @param array<string|int|array>                                                $company_data Company data
 * @param array{field_prefix?: string, fields_map?: array<string|array<string>>} $params       Array of params:
 *                                                                                             - 'field_prefix' - prefix for company profile fields
 *                                                                                             - 'fields_map' - custom fields mapping
 *
 * @return array<string|int> $company_data
 */
function fn_filter_company_data_by_profile_fields(array $company_data = [], array $params = [])
{
    if (!$company_data) {
        return $company_data;
    }

    static $company_profile_fields = null;

    if ($company_profile_fields === null) {
        $company_profile_fields = db_get_hash_array(
            'SELECT * FROM ?:profile_fields WHERE profile_type = ?s',
            'field_name',
            ProfileTypes::CODE_SELLER
        );
    }

    $default_fields_map = [
        'phone'   => 'phone_2',
        'email'   => [
            'users_department',
            'site_administrator',
            'orders_department',
            'support_department',
            'newsletter_email',
        ],
        'country' => 'country_descr',
        'state'   => 'state_descr',
        'url'     => 'website',
    ];

    $params['field_prefix'] = empty($params['field_prefix']) ? '' : (string) $params['field_prefix'];
    $params['fields_map'] = isset($params['fields_map'])
        ? array_merge_recursive($default_fields_map, (array) $params['fields_map'])
        : $default_fields_map;

    array_walk(
        $company_profile_fields,
        static function ($value, $field_name) use (&$company_data, $params) {
            if (YesNo::toBool($value['storefront_show'])) {
                return;
            }

            if (isset($company_data[$params['field_prefix'] . $field_name])) {
                $company_data[$params['field_prefix'] . $field_name] = '';
            }

            if (!isset($params['fields_map'][$field_name])) {
                return;
            }

            foreach ((array) $params['fields_map'][$field_name] as $company_field) {
                if (!isset($company_data[$params['field_prefix'] . $company_field])) {
                    continue;
                }

                $company_data[$params['field_prefix'] . $company_field] = '';
            }
        }
    );

    return $company_data;
}

/**
 * Executes an action in the context of the company specified by its ID.
 *
 * @phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint.DisallowedMixedTypeHint
 *
 * @param callable $action     Action to execute
 * @param int|null $company_id Company identifier
 *
 * @return mixed
 */
function fn_execute_as_company(callable $action, $company_id)
{
    $backup_company_id = Registry::get('runtime.company_id');

    Registry::set('runtime.company_id', $company_id);
    Registry::resetCacheCompanyId();
    Registry::clearCacheLevels();

    /**
     * Executes after company identifier has been replaced with the new one,
     * allows you to perform additional runtime state manipulations.
     *
     * @param callable $action     Action to execute
     * @param int|null $company_id Company identifier
     */
    fn_set_hook('execute_as_company_after_set_company_id', $action, $company_id);

    $result = $action();

    Registry::set('runtime.company_id', $backup_company_id);
    Registry::resetCacheCompanyId();
    Registry::clearCacheLevels();

    /**
     * Executes after company identifier has been restored,
     * allows you to perform additional runtime state manipulations.
     *
     * @param callable $action     Action to execute
     * @param int|null $company_id Company identifier
     */
    fn_set_hook('execute_as_company_after_restore_company_id', $action, $company_id);

    return $result;
}

/**
 * Returns true if orders of products by specified company fulfills by marketplace.
 *
 * @param int $company_id Company identifier.
 *
 * @return bool|null
 */
function fn_are_company_orders_fulfilled_by_marketplace($company_id)
{
    if (empty($company_id)) {
        return null;
    }

    $fulfillment_status = false;

    if (fn_allowed_for('ULTIMATE')) {
        return $fulfillment_status;
    }

    /**
     * Executes after company orders fulfillment status has been identified,
     * allows you to change it's type.
     *
     * @param int  $company_id         Company identifier.
     * @param bool $fulfillment_status Company's order fulfillment by marketplace status.
     */
    fn_set_hook('are_company_orders_fulfilled_by_marketplace', $company_id, $fulfillment_status);

    return $fulfillment_status;
}

/**
 * Returns company ids which orders fulfilled by marketplace.
 *
 * @return array<int>
 */
function fn_what_companies_orders_are_fulfilled_by_marketplace()
{
    $fulfilled_company_ids = [];

    if (fn_allowed_for('ULTIMATE')) {
        return $fulfilled_company_ids;
    }

    /**
     * Executes after companies which order fulfillment status have been identified,
     * allows you to change them.
     *
     * @param array<int> $fulfilled_company_ids Companies ids which order fulfillment by marketplace.
     */
    fn_set_hook('what_companies_orders_are_fulfilled_by_marketplace', $fulfilled_company_ids);

    return $fulfilled_company_ids;
}

/**
 * Gets the statuses of vendors that should be shown in the store.
 *
 * @return array<string>
 */
function fn_get_available_company_statuses()
{
    $statuses = [VendorStatuses::ACTIVE];

    /**
     * Executes after all the statuses of the vendors that should be shown in the store have been received,
     * allows you to add other statuses of the vendors.
     *
     * @param array<string> $statuses Available company statuses
     */
    fn_set_hook('get_available_company_statuses_post', $statuses);

    return $statuses;
}
