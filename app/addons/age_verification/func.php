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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_age_verification_get_products(&$params, &$fields, &$sortings, &$condition, &$join)
{
    if ($params['area'] == 'C') {
        fn_age_verification_extend_product_fields($fields);

        if (!empty(Tygh::$app['session']['auth']['age'])) {
            if (Tygh::$app['session']['auth']['age'] == -1) {
                $condition .= " AND products.age_verification = 'N' AND ?:categories.age_verification = 'N'";
            } else {
                $condition .= db_quote(" AND (products.age_verification = 'N' OR (products.age_verification = 'Y' AND products.age_limit <= ?i)) AND (?:categories.age_verification = 'N' OR (?:categories.age_verification = 'Y' AND ?:categories.age_limit <= ?i)) AND (?:categories.parent_age_verification = 'N' OR (?:categories.parent_age_verification = 'Y' AND ?:categories.parent_age_limit <= ?i))", Tygh::$app['session']['auth']['age'], Tygh::$app['session']['auth']['age'], Tygh::$app['session']['auth']['age']);
            }
        }
    }
}

/**
 * Generates SQL fields selection
 *
 * @param array $fields                     List of table column names to be returned
 * @param string $age_verification_name     Age verification field name
 * @param string $age_limit_name            Age limit field name
 * @return void
 */
function fn_age_verification_extend_product_fields(&$fields, $age_verification_name = 'need_age_verification', $age_limit_name = 'age_limit')
{
    $fields[$age_verification_name] = "IF(products.age_verification = 'Y', 'Y', IF(?:categories.age_verification = 'Y', 'Y', ?:categories.parent_age_verification)) as $age_verification_name";
    $fields[$age_limit_name] = "IF(products.age_limit > ?:categories.age_limit, IF(products.age_limit > ?:categories.parent_age_limit, products.age_limit, ?:categories.parent_age_limit), IF(?:categories.age_limit > ?:categories.parent_age_limit, ?:categories.age_limit, ?:categories.parent_age_limit)) as $age_limit_name";
}

function fn_age_verification_get_user_info(&$user_id, &$get_profile, &$profile_id, &$user_data)
{
    if (!empty($user_data['user_id'])) {
        $user_data['birthday'] = db_get_field('SELECT birthday FROM ?:users WHERE user_id = ?i', $user_data['user_id']);
    }
}

function fn_age_verification_get_categories(&$params, &$join, &$condition, &$fields, &$group_by, &$sortings)
{
    $fields[] = '?:categories.age_verification';
    $fields[] = '?:categories.age_limit';
    $fields[] = '?:category_descriptions.age_warning_message';

    if (AREA == 'C') {
        if (!empty(Tygh::$app['session']['auth']['age'])) {
            if (Tygh::$app['session']['auth']['age'] == -1) {
                $condition .= " AND ?:categories.age_verification = 'N'";
            } else {
                $condition .= db_quote(" AND (?:categories.age_verification = 'N' OR (?:categories.age_verification = 'Y' AND ?:categories.age_limit <= ?i))", Tygh::$app['session']['auth']['age']);
            }
        }
    }

    return true;
}

function fn_age_verification_category_check($category_id)
{
    if (
        empty(Tygh::$app['session']['auth']['age'])
        && !empty(Tygh::$app['session']['auth']['act_as_user'])
        && !empty(Tygh::$app['session']['auth']['user_id'])
    ) {
        $user_data = fn_get_user_info(Tygh::$app['session']['auth']['user_id']);
        fn_age_verification_check_age($user_data);
    }

    $age = !empty(Tygh::$app['session']['auth']['age']) ? Tygh::$app['session']['auth']['age'] : 0;

    while ($category_id) {
        $data = db_get_row("SELECT category_id, parent_id, age_verification, age_limit FROM ?:categories WHERE category_id = ?i", $category_id);

        if (empty($data)) {
            return array (false, 0);
        }

        if ($data['age_verification'] == 'Y') {
            if (!$age) {
                return array ('form', $data['category_id']);
            } else {
                if ($age < $data['age_limit']) {
                    return array ('deny', $data['category_id']);
                }
            }
        }

        $category_id = $data['parent_id'];
    }

    return array (false, 0);
}

function fn_age_verification_update_profile(&$action, &$user_data)
{
    return fn_age_verification_check_age($user_data);
}

function fn_age_verification_check_age($user_data)
{
    if (!empty($user_data['birthday']) && !empty($user_data['status']) && $user_data['status'] == 'A') {
        $year = date('Y', $user_data['birthday']);
        $month = date('m-d', $user_data['birthday']);

        $_year = date('Y', TIME);
        $_month = date('m-d', TIME);

        $age = $_year - $year;

        if ($month > $_month) {
            $age--;
        }

        Tygh::$app['session']['auth']['age'] = $age;

        return true;
    }

    return false;
}

/**
 * Birthday promotion condition
 *
 */

function fn_age_verification_birthday_promo($auth)
{
    $result = false;

    if (!empty($auth['user_id'])) {
        $birthday = db_get_field('SELECT birthday FROM ?:users WHERE user_id = ?i', $auth['user_id']);

        if (!empty($birthday)) {
            $month = date('m', $birthday);
            $day = date('d', $birthday);
            $current_month = date('m', TIME);
            $current_day = date('d', TIME);

            if ($month == $current_month && $day == $current_day) {
                $result = true;
            }
        }
    }

    return $result;
}

/**
 * Check and parse user birthday
 *
 * @param int $user_id - user ID to update (empty for new user)
 * @param array $user_data - user data
 * @param array $auth - authentication information
 * @param bool $ship_to_another - flag indicates that shipping and billing fields are different
 * @param bool $notify_user - flag indicates that user should be notified
 * @return bool Always true
 */
function fn_age_verification_update_user_pre(&$user_id, &$user_data, &$auth, &$ship_to_another, &$notify_user)
{
    if (!empty($user_data['birthday']) && !is_numeric($user_data['birthday'])) {
        $user_data['birthday'] = fn_parse_date($user_data['birthday']);
    }

    return true;
}

/**
 * Saves the user age after authorization
 *
 * @param int                                              $user_id   User identifier
 * @param int                                              $cu_id     Cart user identifier
 * @param array<string, string>                            $udata     User data
 * @param array<string, string|int|array<string|int, int>> $auth      Authentication data
 * @param string                                           $condition String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
 * @param string                                           $result    Result user login
 *
 * @return void
 */
function fn_age_verification_login_user_post($user_id, $cu_id, array $udata, array $auth, $condition, $result)
{
    if ($result !== LOGIN_STATUS_OK) {
        return;
    }

    $u_data_for_verification = [
        'birthday' => $udata['birthday'],
        'status' => $udata['status']
    ];
    fn_age_verification_check_age($u_data_for_verification);
}
