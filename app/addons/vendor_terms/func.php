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
use Tygh\Enum\ProfileFieldTypes;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Getting vendor terms and conditions
 * @param  mixed  $company_ids Array or int
 * @param  string $lang_code   Language code
 * @return array
 */
function fn_get_vendor_terms($company_ids = 0, $lang_code = DESCR_SL)
{
    $conditions = array(
        db_quote("TRIM(terms) <> '' AND d.lang_code = ?s", $lang_code)
    );

    if ($company_ids) {
        $conditions[] = db_quote("company_id IN(?n)", (array)$company_ids);
    }

    $terms = db_get_array(
        "SELECT company_id, company, terms"
        . " FROM ?:companies c"
        . " JOIN ?:company_descriptions d USING(company_id)"
        . " WHERE " . implode(' AND ', $conditions)
    );

    return $terms;
}

/**
 * The "storefront_rest_api_get_checkout_fields" hook handler.
 *
 * Actions performed:
 *  - Gets vendors terms and conditions by user session cart products.
 *
 * @see fn_storefront_rest_api_get_checkout_fields
 */
function fn_vendor_terms_storefront_rest_api_get_checkout_fields($cart, $auth, $lang_code, &$fields)
{
    if (!empty($cart['products'])) {
        $company_ids = [];

        foreach ($cart['products'] as $product) {
            if (!in_array($product['company_id'], $company_ids)) {
                $company_ids[] =  $product['company_id'];
            }
        }

        if ($company_ids) {
            $vendor_terms = fn_get_vendor_terms($company_ids);

            foreach ($vendor_terms as $terms_data) {
                $fields[CUSTOM_CHECKOUT_FIELDS]['accept_terms']['fields'][] = [
                    'field_id'    => 'accept_terms_' . $terms_data['company_id'],
                    'field_name'  => 'agreements[]',
                    'field_type'  => ProfileFieldTypes::CHECKBOX,
                    'is_default'  => true,
                    'description' => $terms_data['terms'],
                    'required'    => true
                ];
            }
        }
    }
}
