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

function fn_exim_pickup_set_company_id($company, $pickup_id)
{
    if (Registry::get('runtime.company_id')) {
        $company_id= Registry::get('runtime.company_id');
    } else {
        $company_id = fn_get_company_id_by_name($company);
    }

    db_query("UPDATE ?:store_locations SET company_id = ?i WHERE store_location_id = ?i ", $company_id , $pickup_id);

    if (fn_allowed_for('ULTIMATE')) {
        fn_ult_update_share_object($pickup_id, 'store_locations', $company_id);
    }

    return true;
}

function fn_exim_pickup_get_destinations($store_location_id, $destinations, $lang_code) {

    $result = '';

    if (!empty($destinations)) {
        $result = array();
        $destinations = explode(',', $destinations);

        foreach ($destinations as $key => $destination_id) {
            $result[] = fn_get_destination_name($destination_id, $lang_code);
        }

        $result = implode(',', $result);
    }

    return $result;
}

/**
 * Prepares main destination value for insertion
 *
 * @param string $destination Destination name
 * @param string $lang_code   Two-letter language-code
 *
 * @return string|null
 */
function fn_exim_pickup_set_main_destination($destination, $lang_code)
{
    return fn_exim_pickup_prepare_destinations($destination, $lang_code) ?: null;
}

/**
 * Converts destination names to corresponding identifiers
 *
 * @param string $destinations Destination names
 * @param string $lang_code    Two-letters language code
 *
 * @return string
 */
function fn_exim_pickup_prepare_destinations($destinations, $lang_code)
{
    if (empty($destinations)) {
        return '';
    }

    $result = [];
    $destinations = explode(',', $destinations);

    foreach($destinations as $destination) {
        $destination_id = db_get_field('SELECT destination_id FROM ?:destination_descriptions WHERE destination = ?s AND lang_code = ?s', $destination, $lang_code);
        if (!empty($destination_id)) {
            $result[] = $destination_id;
        }
    }

    return implode(',', array_unique($result));
}

/**
 * Prepares pickup destinations for insertion
 *
 * @param string      $destinations     Destination names
 * @param string      $lang_code        Two-letters language code
 * @param string|null $main_destination Main destination name
 *
 * @return string
 */
function fn_exim_pickup_set_destinations($destinations, $lang_code, $main_destination = null)
{
    if (!$main_destination) {
        return fn_exim_pickup_prepare_destinations($destinations, $lang_code);
    }

    if (!is_numeric($main_destination)) {
        $destinations .= $destinations ? ",{$main_destination}" : $main_destination;
        return fn_exim_pickup_prepare_destinations($destinations, $lang_code);
    }

    $prepared_destinations = explode(',', fn_exim_pickup_prepare_destinations($destinations, $lang_code));
    $prepared_destination[] = $main_destination;
    return implode(',', array_unique($prepared_destinations));
}

/**
 * Import pickup process
 *
 * @param array<string, array<string, string|int>> $data           Imported data
 * @param array<string, int>                       $processed_data Processed data
 * @param bool                                     $skip_record    Skip record flag
 *
 * @return int
 */
function fn_check_store_location_id($data, array &$processed_data, &$skip_record)
{
    $pickup = reset($data);

    if (Registry::get('runtime.company_id')) {
        $company_id = Registry::get('runtime.company_id');
    } else {
        $company_id = fn_get_company_id_by_name((string) $pickup['company_id']);
    }

    $company_id = (int) $company_id;

    if (!empty($company_id) && !empty($pickup['store_location_id'])) {
        $pickup_data = fn_execute_as_company(static function () use ($pickup) {
            return db_get_row(
                'SELECT store_location_id, company_id FROM ?:store_locations WHERE store_location_id = ?i',
                (int) $pickup['store_location_id']
            );
        }, 0);

        if ($pickup_data && (int) $pickup_data['company_id'] !== $company_id) {
            $processed_data['S']++;
            $skip_record = true;
            return $pickup_data['store_location_id'];
        }
    }

    return !empty($pickup['store_location_id']) ? (int) $pickup['store_location_id'] : 0;
}

