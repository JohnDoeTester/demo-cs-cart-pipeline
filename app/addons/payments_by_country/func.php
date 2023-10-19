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

use Tygh\Enum\Addons\PaymentsByCountry\CountrySelectionMode;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Sets countries for a payment method.
 *
 * @param int   $payment_id    Payment method ID
 * @param array $country_codes List of ISO-3166-1 alpha2 country codes
 */
function fn_payments_by_country_set_payment_countries($payment_id, array $country_codes)
{
    db_query('DELETE FROM ?:payments_countries WHERE payment_id = ?i', $payment_id);

    if ($country_codes) {
        $data = array_map(
            function ($el) use ($payment_id) {
                return [
                    'payment_id'   => $payment_id,
                    'country_code' => $el
                ];
            },
            $country_codes
        );

        db_query('INSERT INTO ?:payments_countries ?m', $data);
    }
}

/**
 * Get countries for a payment method.
 *
 * @param int    $payment_id    Payment method ID
 * @param string $lang_code     Language code
 *
 * @return array $countries     List of countries
 */
function fn_payments_by_country_get_payment_countries($payment_id, $lang_code = CART_LANGUAGE)
{
    $countries = db_get_hash_single_array('SELECT code, country FROM ?:payments_countries as a LEFT JOIN ?:country_descriptions as b ON a.country_code = b.code WHERE payment_id = ?i AND b.lang_code = ?s', ['code', 'country'], $payment_id, $lang_code);

    return $countries;
}

/**
 * The "update_payment_post" hook handler.
 *
 * Actions performed:
 *  - Sets countries in database for a payment method
 *
 * @see fn_update_payment
 */
function fn_payments_by_country_update_payment_post($payment_data, $payment_id, $lang_code, $certificate_file, $certificates_dir, $processor_params, $action)
{
    $country_codes = isset($payment_data['country_codes']) ? $payment_data['country_codes'] : [];

    fn_payments_by_country_set_payment_countries($payment_id, $country_codes);
}

/**
 * The "prepare_checkout_payment_methods_after_get_payments" hook handler.
 *
 * Actions performed:
 *  - Filters payment methods by selected countries
 *
 * @see fn_prepare_checkout_payment_methods
 */
function fn_payments_by_country_prepare_checkout_payment_methods_after_get_payments($cart, $auth, $lang_code, $get_payment_groups, &$payment_methods, $get_payments_params, $cache_key)
{
    $user_countries = [isset($cart['user_data']['b_country']) ? $cart['user_data']['b_country'] : Registry::get('settings.Company.company_country')];
    $country_by_ip = fn_get_country_by_ip(fn_get_ip(true)['host']);

    if ($country_by_ip) {
        $user_countries[] = $country_by_ip;
    }

    foreach ($payment_methods[$cache_key] as $key => $payment) {
        $country_selection_mode = $payment['country_selection_mode'];
        $countries_for_payment = db_get_fields('SELECT country_code FROM ?:payments_countries WHERE payment_id = ?i', $payment['payment_id']);

        if ($countries_for_payment) {
            if ($country_selection_mode == CountrySelectionMode::SHOW) {
                if (!array_intersect($user_countries, $countries_for_payment)) {
                    unset($payment_methods[$cache_key][$key]);
                }
            } else {
                if (array_intersect($user_countries, $countries_for_payment)) {
                    unset($payment_methods[$cache_key][$key]);
                }
            }
        }
    }
}

/**
 * The "delete_payment_post" hook handler.
 *
 * Actions performed:
 *  - Delete records from the table "payments_countries"
 *
 * @see fn_delete_payment
 */
function fn_payments_by_country_delete_payment_post($payment_id, $result)
{
    db_query('DELETE FROM ?:payments_countries WHERE payment_id = ?i', $payment_id);
}
