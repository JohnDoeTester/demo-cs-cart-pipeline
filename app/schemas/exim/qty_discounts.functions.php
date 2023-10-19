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

use Tygh\Enum\UsergroupStatuses;
use Tygh\Enum\UserTypes;
use Tygh\Enum\YesNo;
use Tygh\Registry;
use Tygh\Languages\Languages;

/**
 * The function checks if an entered percentage discount for the lower limit value equal to 1 to be greater than 0
 *
 * @param array $product_info Product information
 * @param string $lang_code 2-letter language code
 * @param bool $skip_record Skip or not current record
 * @return bool false if the record should be skipped or the "lower_limit" value of the currect record
 */
function fn_exim_check_discount($product_info, $lang_code, $skip_record)
{
    if (!isset($product_info['percentage_discount'])) {
        $product_info['percentage_discount'] = 0;
    }

    if (
        !isset($product_info['lower_limit'])
        || !isset($product_info['usergroup_id'])
    ) {
        $skip_record = true;
    }

    if (
        (int) $product_info['lower_limit'] === 1
        && $product_info['percentage_discount'] > 0
        && (int) $product_info['usergroup_id'] === 0
    ) {
        $skip_record = true;
    }

    return ($skip_record) ? false : $product_info['lower_limit'];
}

/**
 * Adds current company_id as altkey if object doesn't
 * have store defined. Needed to determine primary object
 * correctly.
 *
 * @param array<string, string> $alt_keys Keys that act as primary when adding/updating
 * @param array<string, string> $object   Object structure to export
 *
 * @return void
 */
function fn_qty_apply_company(array &$alt_keys, array &$object)
{
    if (empty($object['company'])) {
        if (Registry::get('runtime.company_id') && empty($alt_keys['company_id'])) {
            if (isset($object['is_shared_product']) && isset($object['shared_product_real_company_id'])) {
                $alt_keys['company_id'] = $object['shared_product_real_company_id'];
            } else {
                $alt_keys['company_id'] = Registry::get('runtime.company_id');
            }
        }
    } else {
        $company_id = fn_get_company_id_by_name($object['company']);
        if ($company_id !== null) {
            $alt_keys['company_id'] = $company_id;
        }
    }
}

/**
 * Changes prices of shared products for Ultimate edition
 *
 * @param int   $product_id Product id
 * @param array $params     Product params (product_code, lang_code, etc)
 *
 * @return void
 */
function fn_qty_update_prices($product_id, $params)
{
    if (!fn_allowed_for('ULTIMATE')) {
        return;
    }

    $params['product_id'] = $product_id;

    if (fn_ult_is_shared_product($product_id) === YesNo::YES) {
        $company_id = Registry::get('runtime.company_id');
        $prod_company_id = db_get_field('SELECT company_id FROM ?:products WHERE product_id = ?i', $product_id);
        unset($params['product_code'], $params['Language'], $params['lang_code']);
        if (!$company_id || (int) $company_id === (int) $prod_company_id || isset($params['is_shared_product'])) {
            if (
                isset($params['is_shared_product']) && isset($params['shared_product_real_company_id'])
                && (int) $params['shared_product_real_company_id'] === (int) $prod_company_id
            ) {
                $params['company_id'] = $company_id;
            } elseif (!$company_id || (int) $company_id === (int) $prod_company_id) {
                $params['company_id'] = $prod_company_id;
            }
            db_query('REPLACE INTO ?:ult_product_prices ?e', $params);
        }
        if (!isset($params['is_shared_product'])) {
            db_query('REPLACE INTO ?:product_prices ?e', $params);
        }
    } else {
        db_query('REPLACE INTO ?:product_prices ?e', $params);
    }
}

/**
 * The function gets usergroup id by usergroup name
 *
 * @param string $ug_name   Usergroup name
 * @param string $lang_code Two-letter language code
 *
 * @return int
 */
function fn_get_usergroup_id($ug_name, $lang_code)
{
    $usergroup_id = db_get_field('SELECT usergroup_id FROM ?:usergroup_descriptions WHERE usergroup = ?s AND lang_code = ?s LIMIT 1', $ug_name, $lang_code);

    return !empty($usergroup_id) ? $usergroup_id : 0;
}

/**
 * The function gets usergroup name by usergroup id
 *
 * @param int    $usergroup_id Usergroup id
 * @param string $lang_code    Two-letter language code
 *
 * @return string usergroup name
 */
function fn_exim_get_usergroup($usergroup_id, $lang_code = '')
{
    if ($usergroup_id < ALLOW_USERGROUP_ID_FROM) {
        $default_usergroups = fn_get_default_usergroups($lang_code);
        $usergroup = !empty($default_usergroups[$usergroup_id]['usergroup']) ? $default_usergroups[$usergroup_id]['usergroup'] : '';
    } else {
        $usergroup = db_get_field('SELECT usergroup FROM ?:usergroup_descriptions WHERE usergroup_id = ?i AND lang_code = ?s', $usergroup_id, $lang_code);
    }

    return $usergroup;
}

/**
 * The function converts a user group name into a user group ID or creates a new user group if a user group specified in the import file does not exist
 *
 * @param string $usergroup Usergroup name presented in the file
 * @param string $lang_code Two-letter language code
 *
 * @return int usergroup id
 */
function fn_exim_put_usergroup($usergroup, $lang_code)
{
    $default_usergroups = fn_get_default_usergroups($lang_code);
    foreach ($default_usergroups as $usergroup_id => $ug) {
        if ($ug['usergroup'] === $usergroup) {
            return $usergroup_id;
        }
    }

    $usergroup_id = fn_get_usergroup_id($usergroup, $lang_code);
    $user_type = Registry::get('user_info.user_type');

    // Create new usergroup
    if (empty($usergroup_id) && $user_type !== UserTypes::VENDOR) {
        $_data = [
            'type' => UserTypes::CUSTOMER, //customer
            'status' => UsergroupStatuses::ACTIVE
        ];

        $usergroup_id = db_query('INSERT INTO ?:usergroups ?e', $_data);

        $_data = [
            'usergroup_id' => $usergroup_id,
            'usergroup' => $usergroup,
        ];

        foreach (array_keys(Languages::getAll()) as $_data['lang_code']) {
            db_query('INSERT INTO ?:usergroup_descriptions ?e', $_data);
        }
    }

    return $usergroup_id;
}
