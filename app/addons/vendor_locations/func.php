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

use Tygh\Addons\VendorLocations\Enum\FilterTypes;
use Tygh\Addons\VendorLocations\Dto\Zone;
use Tygh\Addons\VendorLocations\Dto\Region;
use Tygh\Addons\VendorLocations\Dto\Location;
use Tygh\Addons\VendorLocations\FilterTypes\ZoneFilterType;
use Tygh\Addons\VendorLocations\FilterTypes\RegionFilterType;
use Tygh\Addons\VendorLocations\FilterTypes\BaseFilterType;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Updates geolocation data in DB
 *
 * @param int                                       $company_id Company ID
 * @param \Tygh\Addons\VendorLocations\Dto\Location $location   Vendor's geolocation data
 *
 * @return void
 */
function fn_vendor_locations_upsert_location($company_id, Location $location)
{
    db_replace_into('vendor_locations', array_merge(
        array_filter($location->toArray()),
        array('company_id' => $company_id)
    ));
}

/**
 * Creates and returns geolocation object
 *
 * @param int $company_id Company ID
 *
 * @return \Tygh\Addons\VendorLocations\Dto\Location
 */
function fn_vendor_locations_get_location($company_id)
{
    $data = db_get_row('SELECT * FROM ?:vendor_locations WHERE company_id = ?i', $company_id);

    return Location::createFromArray($data, false);
}

/**
 * Creates and returns filter type objects depends of $value's type
 *
 * @param \Tygh\Addons\VendorLocations\Dto\Region|\Tygh\Addons\VendorLocations\Dto\Zone $value Value object of type
 *                                                                                             Zone or Region
 *
 * @return bool|\Tygh\Addons\VendorLocations\FilterTypes\RegionFilterType|\Tygh\Addons\VendorLocations\FilterTypes\ZoneFilterType
 */
function fn_vendor_locations_create_filter_type($value)
{
    if ($value instanceof Zone) {
        return new ZoneFilterType($value, Tygh::$app['db'], Registry::get('addons.vendor_locations.distance_unit'));
    } elseif ($value instanceof Region) {
        return new RegionFilterType($value, Tygh::$app['db']);
    }

    return false;
}

/**
 * Adds fields and condition expressions to sql query
 *
 * @param \Tygh\Addons\VendorLocations\FilterTypes\BaseFilterType $filter_type               FilterType object
 * @param array<string, int|bool|string>                          $fields                    DB table fields set
 * @param string                                                  $condition                 DB WHERE condition expression
 * @param string                                                  $master_products_condition DB WHERE master products condition expression
 *
 * @return array
 */
function fn_vendor_locations_extend_sql_statement_by_filter(BaseFilterType $filter_type, array $fields, string $condition, string $master_products_condition = '')
{
    $fields += $filter_type->buildSqlSelectExpression();
    $extra_condition = $filter_type->buildSqlWhereConditions();

    if ($extra_condition) {
        $condition .= !empty($_REQUEST['company_id'])
            ? db_quote(' AND ?p', $extra_condition)
            : db_quote(' AND (?p ?p)', $extra_condition, $master_products_condition);
    }

    return array($fields, $condition);
}

/**
 * Gets geolocation data from filter's hash
 *
 * @param array $filter           products filter data
 * @param array $selected_filters selected filters data
 *
 * @return array
 */
function fn_vendor_locations_retrieve_location_from_selected_filter(array $filter, array $selected_filters)
{
    if (empty($filter['filter_id']) || empty($selected_filters[$filter['filter_id']][0])) {
        return array(null, null, null);
    }

    $hash = $selected_filters[$filter['filter_id']][0];
    $location = null;

    if ($filter['field_type'] === FilterTypes::ZONE) {
        $location = Zone::createFromHash($hash);
    } elseif ($filter['field_type'] === FilterTypes::REGION) {
        $location = Region::createFromHash($hash);
    }

    return array($location, $hash, $location ? $location->getPlaceId() : null);
}

// Hooks
/**
 * Extends companies data with geolocation info.
 * If the 'get_vendor_location' param passed, then vendor_locations table is joined and lat and lng fields adds to SQL
 * query. If the 'customer_geolocation' param passed, then filtering companies by distance conditions added to SQL
 * query If the 'location_filter' param passed, then filtering companies by address components added to SQL query
 */
function fn_vendor_locations_get_companies(array $params, array &$fields, array &$sortings, &$condition, &$join, array $auth, $lang_code, $group)
{
    $set_join = false;

    if (!empty($params['get_vendor_location'])) {
        $fields[] = 'vendor_locations.lat';
        $fields[] = 'vendor_locations.lng';
        $set_join = true;
    }

    if (empty($params['location_filter'])
        && ($location_filter = Registry::get('vendor_locations.default_filter'))
    ) {
        $params['location_filter'] = $location_filter;
    }

    if (!empty($params['customer_geolocation']) && $params['customer_geolocation'] instanceof Zone) {
        /** @var ZoneFilterType $filter_type */
        $filter_type = fn_vendor_locations_create_filter_type($params['customer_geolocation']);
        $additional_fields = $filter_type->buildSqlSelectExpression();

        foreach ($additional_fields as $field => $value) {
            $fields[] = sprintf('%s AS %s', $value, $field);
            $set_join = true;
        }

        if (isset($params['sort_by'])
            && isset($additional_fields[$params['sort_by']])
        ) {
            $sortings[$params['sort_by']] = $filter_type->getTableAlias() . '.lat IS NULL, ' . $additional_fields[$params['sort_by']];
        }
    }

    if (!empty($params['location_filter'])) {
        if (is_string($params['location_filter'])) {
            $params['location_filter'] = Region::createFromHash($params['location_filter']);
        }

        if ($params['location_filter'] instanceof Region) {
            /** @var RegionFilterType $filter_type */
            $filter_type = fn_vendor_locations_create_filter_type($params['location_filter']);

            $where_conditions = $filter_type->buildSqlWhereConditions();
            if (!empty($where_conditions)) {
                $condition .= sprintf(' AND %s', $where_conditions);
                $set_join = true;
            }
        }
    }

    if ($set_join) {
        $join .= db_quote(' LEFT JOIN ?:vendor_locations AS vendor_locations ON vendor_locations.company_id = ?:companies.company_id');
    }
}

/**
 * Deletes geolocation info from vendor_locations table.
 *
 * @param int $company_id Company id
 */
function fn_vendor_locations_delete_company($company_id)
{
    db_query('DELETE FROM ?:vendor_locations WHERE company_id = ?i', $company_id);
}

/**
 * Updates company's geolocation data.
 * If the 'vendor_location' param passed from the update company page,
 * then that location data updates in the vendor_locations DB table
 */
function fn_vendor_locations_update_company(array $company_data, $company_id)
{
    if (!isset($company_data['vendor_location'])) {
        return;
    }

    if (empty($company_data['vendor_location'])) {
        fn_vendor_locations_delete_company($company_id);
    } else {
        $location = Location::createFromJsonString($company_data['vendor_location']);

        if ($location->getPlaceId()) {
            fn_vendor_locations_upsert_location($company_id, $location);
        }
    }
}

/**
 * Adds the vendor_location field to company data structure.
 * The data gets from the vendor_locations table.
 */
function fn_vendor_locations_get_company_data_post($company_id, $lang_code, $extra, &$company_data)
{
    $company_data['vendor_location'] = fn_vendor_locations_get_location($company_id);
}

/**
 * Extends filter types.
 * REGION - filter by address' componets.
 * ZONE - filter by distance to vendor.
 */
function fn_vendor_locations_get_product_filter_fields(array &$filters)
{
    $filters[FilterTypes::REGION] = array(
        'condition_type' => FilterTypes::REGION,
        'db_field' => 'company_id',
        'description' => 'vendor_locations.filter_by_city',
        'table' => 'products',
        'variant_name_field' => 'companies.company',
    );

    $filters[FilterTypes::ZONE] = array(
        'condition_type' => FilterTypes::ZONE,
        'db_field' => 'company_id',
        'description' => 'vendor_locations.filter_by_distance',
        'slider' => true,
        'suffix' => Registry::get('addons.vendor_locations.distance_unit'),
        'table' => 'products',
        'variant_name_field' => 'companies.company',
    );
}

/**
 * Generates params for the get_products_before_select hook.
 * If address selected in the filter by address, then location data object adds to the fn_get_products function's
 * params. The location uses for generate SQL query's statements.
 */
function fn_vendor_locations_generate_filter_field_params(array &$params, array $filters, array $selected_filters, array $filter_fields, array $filter, array $structure)
{
    [$location] = fn_vendor_locations_retrieve_location_from_selected_filter($filter, $selected_filters);

    if (!$location) {
        return;
    }

    if ($filter['field_type'] === FilterTypes::ZONE) {
        $params['vendor_location_area'] = $location;
    } elseif ($filter['field_type'] === FilterTypes::REGION) {
        $params['vendor_location_region'] = $location;
    }
}

/**
 * Adds conditions for get products query by passed params.
 * That params can be added in the 'generate_filter_field_params' hook.
 * If the 'vendor_location_area' and/or 'vendor_location_region' param is not empty,
 * the vendor_locations table is joined and the $fields and $conditions expressions modified to filter products
 * by address components(country, city) or by distance to product vendor(company).
 */
function fn_vendor_locations_get_products(array $params, array &$fields, array $sortings, &$condition, &$join)
{
    $filter_types = array();

    if (isset($params['vendor_location_area'])) {
        $filter_types[] = fn_vendor_locations_create_filter_type($params['vendor_location_area']);
    }

    if (isset($params['vendor_location_region'])) {
        $filter_types[] = fn_vendor_locations_create_filter_type($params['vendor_location_region']);
    }

    $filter_types = array_filter($filter_types);
    $master_products_condition = '';

    /**
     * Executes before filtering query params if filter type is set, allows to modify params.
     *
     * @param array<string, int|bool|string>  $params                    Request params
     * @param array<string, int|bool|string>  $fields                    SELECT query fields
     * @param array<string, int|bool|string>  $sortings                  SELECT query sorting
     * @param string                          $condition                 SELECT query condition
     * @param string                          $join                      SELECT query joins
     * @param string                          $master_products_condition SELECT query master products condition
     */
    fn_set_hook('vendor_locations_get_products_pre', $params, $filter_types, $fields, $sortings, $condition, $join, $master_products_condition);

    if ($filter_types) {
        $join .= ' INNER JOIN ?:vendor_locations AS vendor_locations ON vendor_locations.company_id = products.company_id';

        foreach ($filter_types as $filter_type) {
            list($fields, $condition) = fn_vendor_locations_extend_sql_statement_by_filter(
                $filter_type,
                $fields,
                $condition,
                $master_products_condition
            );
        }
    }

    /**
     * Executes after filtering query params if filter type is set, allows to modify params.
     *
     * @param array  $params    Request params
     * @param array  $fields    SELECT query fields
     * @param array  $sortings  SELECT query sorting
     * @param string $condition SELECT query condition
     * @param string $join      SELECT query joins
     */
    fn_set_hook('vendor_locations_get_products', $params, $filter_types, $fields, $sortings, $condition, $join);
}

/**
 * Adds location data to address filters. If no companies records in vendor_locations table,
 * then filter by address components or by distance don't displayed.
 * The data uses in filters block.
 */
function fn_vendor_locations_get_current_filters_post(array $params, array &$filters, array $selected_filters, $area, $lang_code, array $variant_values, array $range_values, array &$field_variant_values, array $field_range_values)
{
    if (Registry::get('runtime.company_id')) {
        return;
    }

    static $cnt;

    foreach ($filters as &$filter) {
        if (!FilterTypes::has($filter['field_type'])) {
            continue;
        }

        if ($cnt === null) {
            $cnt = (int) db_get_field('SELECT COUNT(*) AS cnt FROM ?:vendor_locations');
        }

        // Filter should be displayed always if no location selected yet
        if (!$cnt) {
            return;
        }

        $field_variant_values[$filter['filter_id']]['variants'] = [];
        $filter['show_empty_filter'] = true;

        [$filter['location'], $filter['location_hash'], $filter['location_place_id']] = fn_vendor_locations_retrieve_location_from_selected_filter(
            $filter, $selected_filters
        );
    }
    unset($filter);
}

/**
 * Adds location data to address filters. The data uses in filters block.
 */
function fn_vendor_locations_get_filters_products_count_post(array $params, $lang_code, array &$filters, array $selected_filters)
{
    foreach ($filters as &$filter) {
        if (!FilterTypes::has($filter['field_type'])) {
            continue;
        }
        unset($filter['show_empty_filter']);

        [$filter['location'], $filter['location_hash'], $filter['location_place_id']] = fn_vendor_locations_retrieve_location_from_selected_filter(
            $filter, $selected_filters
        );

        if ($filter['location_place_id'] !== null) {
            // Need for the reset filter functionality
            $filter['selected_variants'] = array(array(
                'variant_id' => $filter['location_place_id'],
                'variant' => $filter['location_place_id'],
            ));
        }
    }
    unset($filter);
}

/**
 * Fetches stored locations and passes them to the templater
 */
function fn_vendor_locations_before_dispatch($controller, $mode, $action, $dispatch_extra, $area)
{
    if ($area !== 'C') {
        return;
    }

    $geolocation = fn_get_session_data(VENDOR_LOCATIONS_STORAGE_KEY_GEO_LOCATION) ?: array();
    $locality = fn_get_session_data(VENDOR_LOCATIONS_STORAGE_KEY_LOCALITY) ?: array();

    Tygh::$app['view']->assign(array(
        'vendor_locations_geolocation' => (array) $geolocation,
        'vendor_locations_locality'    => (array) $locality,
    ));
}

/**
 * The "storefront_rest_api_get_filter_style_post" hook.
 *
 *
 * Performed actions:
 * - Adds filter style for filters by Distance to vendor and Vendor's city.
 *
 * @see \fn_storefront_rest_api_get_filter_style()
 */
function fn_vendor_locations_storefront_rest_api_get_filter_style_post($filter, &$filter_style, $field_type)
{
    switch ($field_type) {
        case FilterTypes::ZONE:
            $filter_style = 'distance_to_vendor';
            break;
        case FilterTypes::REGION:
            $filter_style = 'vendor_city';
            break;
    }
}
