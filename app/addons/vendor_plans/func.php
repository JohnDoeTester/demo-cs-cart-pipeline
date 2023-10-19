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

use Tygh\Addons\ProductVariations\Service;
use Tygh\Addons\VendorPlans\ServiceProvider;
use Tygh\Common\OperationResult;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\ProfileTypes;
use Tygh\Enum\ReceiverSearchMethods;
use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Enum\VendorPayoutTypes;
use Tygh\Enum\VendorStatuses;
use Tygh\Languages\Languages;
use Tygh\Models\Company;
use Tygh\Models\VendorPlan;
use Tygh\Notifications\Receivers\SearchCondition;
use Tygh\Providers\EventDispatcherProvider;
use Tygh\Registry;
use Tygh\Storefront\Storefront;
use Tygh\Tygh;
use Tygh\VendorPayouts;
use Tygh\Enum\YesNo;
use Tygh\Settings;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_vendor_plans_install()
{
    // vendor_payouts table. These fields shouldn't remove: They are used by vendor_commission
    $fields = fn_get_table_fields('vendor_payouts');
    if (!in_array('commission_amount', $fields)) {
        db_query("ALTER TABLE ?:vendor_payouts ADD `commission_amount` decimal(12,2) NOT NULL default '0'");
    }
    if (!in_array('commission', $fields)) {
        db_query("ALTER TABLE ?:vendor_payouts ADD `commission` decimal(12,2) NOT NULL default '0'");
    }
    if (!in_array('commission_type', $fields)) {
        db_query("ALTER TABLE ?:vendor_payouts ADD `commission_type` char(1) NOT NULL default 'A'");
    }
    if (!in_array('marketplace_profit', $fields)) {
        db_query("ALTER TABLE ?:vendor_payouts ADD `marketplace_profit` decimal(12,2) NOT NULL default '0'");
    }

    // import data exported from the vendor commission add-on
    $vendors_demo = Registry::get('config.dir.addons') . 'vendor_plans/database/demo_vendors.sql';
    if (file_exists($vendors_demo)) {
        db_import_sql_file($vendors_demo, 16348, false, false);
        fn_rm($vendors_demo);
    }

    db_query("REPLACE INTO ?:privileges (privilege, is_default, section_id, group_id, is_view) VALUES ('view_vendor_plans', 'Y', 'vendors', 'vendor_plans', 'Y')");
    db_query("REPLACE INTO ?:privileges (privilege, is_default, section_id, group_id, is_view) VALUES ('manage_vendor_plans', 'Y', 'vendors', 'vendor_plans', 'N')");

    // create new profile field type
    $field = [
        'field_name'                   => 'plan_id',
        'profile_show'                 => 'Y',
        'profile_required'             => 'N',
        'checkout_show'                => 'N',
        'checkout_required'            => 'N',
        'partner_show'                 => 'N',
        'partner_required'             => 'N',
        'field_type'                   => PROFILE_FIELD_TYPE_VENDOR_PLAN,
        'profile_type'                 => ProfileTypes::CODE_SELLER,
        'position'                     => 15,
        'is_default'                   => 'Y',
        'section'                      => 'C',
        'matching_id'                  => 0,
        'class'                        => 'plan-id',
        'autocomplete_type'            => '',
        'description'                  => __('vendor_plans.plan'),
        'skip_checking_company_fields' => true
    ];

    $field_id = fn_update_profile_field($field, 0);

    if ($field_id) {
        $languages = Languages::getAvailable([
            'area'           => 'A',
            'include_hidden' => true,
        ]);

        foreach ($languages as $code => $lang) {
            fn_update_profile_field(array(
                'description' => __('vendor_plans.plan', array(), $code),
            ), $field_id, $code);
        }
    }

    list($root_admins,) = fn_get_users([
        'is_root' => YesNo::YES,
        'user_type' => UserTypes::ADMIN,
    ], Tygh::$app['session']['auth']);

    foreach ($root_admins as $root_admin) {
        if (!$root_admin['company_id']) {
            fn_update_notification_receiver_search_conditions(
                'group',
                'vendor_plans',
                UserTypes::ADMIN,
                [
                    new SearchCondition(ReceiverSearchMethods::USER_ID, $root_admin['user_id']),
                ]
            );

            break;
        }
    }
    fn_update_notification_receiver_search_conditions(
        'group',
        'vendor_plans',
        UserTypes::VENDOR,
        [
            new SearchCondition(ReceiverSearchMethods::VENDOR_OWNER, ReceiverSearchMethods::VENDOR_OWNER),
        ]
    );
}

function fn_vendor_plans_uninstall()
{
    db_query("DELETE FROM ?:privileges WHERE privilege IN (?a)", array('view_vendor_plans', 'manage_vendor_plans'));
    $plan_field_id = db_get_field('SELECT field_id FROM ?:profile_fields WHERE profile_type = ?s AND field_name = ?s',
        ProfileTypes::CODE_SELLER,
        'plan_id'
    );

    if ($plan_field_id) {
        fn_delete_profile_field($plan_field_id);
    }
    fn_update_notification_receiver_search_conditions(
        'group',
        'vendor_plans',
        UserTypes::ADMIN,
        []
    );
    fn_update_notification_receiver_search_conditions(
        'group',
        'vendor_plans',
        UserTypes::VENDOR,
        []
    );
}

function fn_vendor_plans_get_companies(&$params, &$fields, &$sortings, &$condition, &$join, &$auth, &$lang_code, &$group)
{
    $fields[] = '?:vendor_plan_descriptions.plan';
    $sortings['plan'] = '?:vendor_plan_descriptions.plan';
    $join .= db_quote(
        ' LEFT JOIN ?:vendor_plan_descriptions'
        . ' ON ?:companies.plan_id = ?:vendor_plan_descriptions.plan_id'
        . ' AND ?:vendor_plan_descriptions.lang_code = ?s',
        $lang_code
    );
    if (!empty($params['plan_id'])) {
        $condition .= db_quote(' AND ?:companies.plan_id IN (?n)', (array)$params['plan_id']);
    }
}

function fn_vendor_plans_get_company_data(&$company_id, &$lang_code, &$extra, &$fields, &$join, &$condition)
{
    $fields[] = '?:vendor_plan_descriptions.plan';
    $join .= db_quote(
        ' LEFT JOIN ?:vendor_plan_descriptions'
        . ' ON companies.plan_id = ?:vendor_plan_descriptions.plan_id'
        . ' AND ?:vendor_plan_descriptions.lang_code = ?s',
        $lang_code
    );
}

function fn_vendor_plans_update_company_pre(&$company_data, &$company_id, &$lang_code, &$can_update)
{
    // Getting current plan
    $company_data['current_plan'] = 0;
    if ($company_id) {
        $curent_data = db_get_row("SELECT plan_id, status FROM ?:companies WHERE company_id = ?i", $company_id);
        $company_data['current_plan'] = $curent_data['plan_id'];
        if (empty($company_data['status'])) {
            $company_data['status'] = $curent_data['status'];
        }
        if (empty($company_data['plan_id'])) {
            $company_data['plan_id'] = $company_data['current_plan'];
        }
    }

    // Check plan availability
    if (!empty($company_data['plan_id'])) {
        $selected_plan = VendorPlan::model()->find($company_data['plan_id'], array(
            'allowed_for_company_id' => $company_id
        ));
        if (!$selected_plan) {
            $company_data['plan_id'] = $company_data['current_plan'] ?: 0;
        }
    }

    // Set default plan
    if (empty($company_data['plan_id']) && empty($company_data['current_plan'])) {
        $default_plan = VendorPlan::model()->find([
            'is_default' => true
        ]);
        if ($default_plan) {
            $company_data['plan_id'] = $default_plan->plan_id;
        }
    }

    // Check params availability
    if (
        Registry::get('runtime.company_id')
        && !empty($company_data['plan_id'])
        && $company_data['plan_id'] != $company_data['current_plan']
    ) {
        $plan = VendorPlan::model()->find($company_data['plan_id'], array(
            'allowed_for_company_id' => $company_id,
            'check_availability' => true,
        ));
        if (!empty($plan->avail_errors) || Registry::ifGet('addons.vendor_plans.allow_vendors_to_change_plan', 'N') == 'N') {
            fn_set_notification('E', __('error'), __('vendor_plans.plan_not_available_text'));
            $can_update = false;
        }
    }

    if (!empty($company_data['plan_id']) && $company_data['plan_id'] == $company_data['current_plan']) {
        unset($company_data['current_plan']);
    }
}

function fn_vendor_plans_update_company(&$company_data, &$company_id, &$lang_code, &$action)
{
    if (
        isset($company_data['plan_id'])
        && isset($company_data['current_plan'])
        && $company_data['plan_id'] != $company_data['current_plan']
        && $company_data['status'] != VendorStatuses::NEW_ACCOUNT
    ) {
        $is_vendor_admin_creation_needed = fn_vendor_plans_is_vendor_admin_creation_needed($company_data);
        /** @var Company $company */
        $company = Company::model(['is_create_vendor_admin' => $is_vendor_admin_creation_needed])->find($company_id);
        /** @var VendorPlan $current_plan */
        $current_plan = VendorPlan::model()->find($company_data['current_plan']);

        if (!$is_vendor_admin_creation_needed) {
            $event_dispatcher = EventDispatcherProvider::getEventDispatcher();

            $event_dispatcher->dispatch(
                'vendor_plans.plan_changed',
                [
                    'company_id'  => $company_id,
                    'old_plan_id' => $company_data['current_plan'],
                    'lang_code'   => $company->lang_code,
                ]
            );
        }

        /** @var VendorPlan $new_plan */
        $new_plan = VendorPlan::model()->find($company_data['plan_id']);

        if (!empty($company_data['remove_vendor_from_old_storefronts'])) {
            /** @var \Tygh\Storefront\Repository $storefront_repository */
            $storefront_repository = Tygh::$app['storefront.repository'];
            $storefront_repository->removeCompaniesFromStorefronts($company_id, $current_plan->storefront_ids);
        }

        if (
            !empty($company_data['add_vendor_to_new_storefronts'])
            || ($action === 'add' && !empty($new_plan->storefront_ids))
        ) {
            /** @var \Tygh\Storefront\Repository $storefront_repository */
            $storefront_repository = Tygh::$app['storefront.repository'];
            $storefront_repository->addCompaniesToStorefronts($company_id, $new_plan->storefront_ids);
        }

        if ($company_data['status'] == VendorStatuses::ACTIVE) {
            $company->payment(TIME);
        }
    }
}

function fn_vendor_plans_change_company_status_before_mail(&$company_id, &$status_to, &$reason, &$status_from, &$skip_query, &$notify, &$company_data, &$user_data)
{
    $company = Company::model()->find($company_id);
    $user_data['plan'] = $company->plan; // Need for email notifications
    if ($status_from != VendorStatuses::ACTIVE && $status_to == VendorStatuses::ACTIVE) {
        $company->initialPayment();
    }
    if ($status_from == VendorStatuses::NEW_ACCOUNT && ($status_to == VendorStatuses::ACTIVE || $status_to == VendorStatuses::PENDING)) {
        /** @var \Tygh\Storefront\Repository $storefront_repository */
        $storefront_repository = Tygh::$app['storefront.repository'];
        $storefront_repository->addCompaniesToStorefronts($company_id, $company->storefront_ids);
    }
}

function fn_vendor_plans_delete_category_after(&$category_id)
{
    db_query('UPDATE ?:vendor_plans SET categories = ?p', fn_remove_from_set('categories', $category_id));
}

function fn_vendor_plans_storefront_repository_delete_post($storefront, $operation_result)
{
    db_query('UPDATE ?:vendor_plans SET storefronts = ?p', fn_remove_from_set('storefronts', $storefront->storefront_id));
}

/**
 * The "delete_usergroups" hook handler.
 *
 * Actions performed:
 *     - Removes usergroups from vendor plans.
 *
 * @param int|int[] $usergroup_ids User group identifiers
 *
 * @see fn_delete_usergroups()
 *
 * @return void
 */
function fn_vendor_plans_delete_usergroups($usergroup_ids)
{
    foreach ((array) $usergroup_ids as $usergroup_id) {
        db_query('UPDATE ?:vendor_plans SET usergroups = ?p', fn_remove_from_set('usergroups', (string) $usergroup_id));
    }
}

/**
 * Returns vendor plan by company id
 *
 * @param int $company_id Company identifier
 *
 * @return \Tygh\Models\VendorPlan|null
 */
function fn_vendor_plans_get_vendor_plan_by_company_id($company_id)
{
    /** @var \Tygh\Models\VendorPlan $vendor_plan */
    $vendor_plan =  VendorPlan::model()->findOne(['company_id' => $company_id]);

    return $vendor_plan;
}

/**
 * Hook handler: adds commission values based on the order totals when the order is placed.
 *
 * @param array  $order_info   Order infromation from ::fn_get_order_info()
 * @param array  $company_data Company data the order belongs to
 * @param string $action       Performed action: '' when editing the order, 'save' when saving
 * @param string $order_status Order status
 * @param array  $cart         Cart contents and user information necessary for purchase
 * @param array  $data         Payout data to be stored in the DB
 * @param int    $payout_id    Payout ID
 * @param array  $auth         User authentication data (e.g. uid, usergroup_ids, etc.)
 */
function fn_vendor_plans_mve_place_order(
    array $order_info,
    array $company_data,
    string $action,
    string $order_status,
    array $cart,
    array &$data,
    int $payout_id,
    array $auth
) {
    $data = fn_calculate_commission_for_payout($order_info, $company_data, $data);
}

/**
 * Hook handler: adds commission values based on the difference between the order totals when the order is changed.
 *
 * @param array<string|float|array>         $new_order_info New order information from ::fn_get_order_info()
 * @param int                               $order_id       Order ID
 * @param array<string|float|array>         $old_order_info Old order information from ::fn_get_order_info()
 * @param array<string, string|float|array> $company_data   Company data the order belongs to
 * @param int                               $payout_id      Existing payout ID
 * @param array<string, float>              $payout_data    Payout data to be stored in the DB.
 *
 * @param-out array<string, array{new_order_payment_surcharge?: float, new_order_shipping_cost?: float, new_order_surcharge_tax_amount?: float, new_order_tax_amount?: float, new_shipping_tax_amount?: float, old_order_payment_surcharge?: float, old_order_shipping_cost?: float, old_order_surcharge_tax_amount?: float, old_order_tax_amount?: float, old_shipping_tax_amount?: float, order_amount?: float}|float> $payout_data Payout data to be stored in the DB
 *
 * @return void
 */
function fn_vendor_plans_mve_update_order(array $new_order_info, int $order_id, array $old_order_info, array $company_data, int $payout_id, array &$payout_data)
{
    if (empty($payout_data)) {
        return;
    }
    $payout_data = fn_calculate_commission_for_payout($new_order_info, $company_data, $payout_data);
}

function fn_vendor_plans_mve_place_order_post(&$order_id, &$action, &$order_status, &$cart, &$auth, &$order_info, &$company_data, &$data, &$payout_id)
{
    if ($order_info['is_parent_order'] != 'Y' && !empty($order_info['company_id'])) {
        if ($company = Company::model()->find($order_info['company_id'])) {
            $company->canGetRevenue(true);
        }
    }
}

/**
 * Hook handler: excludes the commission amount from a transaction value for a vendor
 * and sets a transaction value to the commission amount for an admin.
 *
 * @param VendorPayouts $instance       VendorPayouts instance
 * @param array         $params         Search parameters
 * @param int           $items_per_page Items per page
 * @param array         $fields         SQL query fields
 * @param string        $join           JOIN statement
 * @param string        $condition      General condition to filter payouts
 * @param string        $date_condition Additional condition to filter payouts by date
 * @param string        $sorting        ORDER BY statemet
 * @param string        $limit          LIMIT statement
 */
function fn_vendor_plans_vendor_payouts_get_list(&$instance, &$params, &$items_per_page, &$fields, &$join, &$condition, &$date_condition, &$sorting, &$limit)
{
    if ($instance->getVendor()) {
        $fields['payout_amount'] = 'CASE WHEN payouts.order_id <> 0 THEN payouts.order_amount - payouts.commission_amount ELSE payouts.payout_amount END';
    } else {
        $fields['payout_amount'] = 'CASE WHEN payouts.order_id <> 0 THEN payouts.marketplace_profit ELSE payouts.payout_amount END';
    }
}

/**
 * Hook handler: excludes commission from vendor income and sets admin income as a sum of commissions.
 *
 * @param VendorPayouts $instance       VendorPayouts instance
 * @param array         $params         Search parameters
 * @param array         $fields         SQL query fields
 * @param string        $join           JOIN statement
 * @param string        $condition      General condition to filter payouts
 * @param string        $date_condition Additional condition to filter payouts by date
 */
function fn_vendor_plans_vendor_payouts_get_income(&$instance, &$params, &$fields, &$join, &$condition, &$date_condition)
{
    if ($instance->getVendor()) {
        $fields['orders_summary'] = 'SUM(payouts.order_amount) - SUM(payouts.commission_amount)';
    } else {
        $fields['orders_summary'] = 'SUM(payouts.marketplace_profit)';
    }
}

function fn_vendor_plans_get_categories(&$params, &$join, &$condition, &$fields, &$group_by, &$sortings, &$lang_code)
{
    if (!empty($params['ignore_company_condition'])) {
        return;
    }

    if (Registry::get('runtime.company_id')) {
        $company_id = Registry::get('runtime.company_id');
    } elseif (!empty($params['company_ids'])) {
        $company_id = (int) $params['company_ids'];
    } elseif (!empty($params['company_id'])) {
        $company_id = (int) $params['company_id'];
    }

    if (!empty($company_id)) {
        $plan = VendorPlan::model()->find(array('company_id' => $company_id));
        if ($plan && $plan->category_ids) {

            // This workaround is required when vendor has restricted categories, and total categories number
            // is below the CATEGORY_THRESHOLD, so vendor cannot see allowed categories in the picker
            // Here we add parent categories into the conditions, so vendor could navigate them from the root category
            // up to the allowed one
            if ($params['visible'] == true && empty($params['b_id'])) {
                $category_ids = fn_get_category_ids_with_parent($plan->category_ids);
                $condition .= db_quote(' AND ?:categories.category_id IN (?n)', $category_ids);

                Registry::set('runtime.vendor_plans_company_category_ids', $plan->category_ids);
            } else {
                $company_condition = db_quote(' AND ?:categories.category_id IN (?n)', $plan->category_ids);
                Registry::set('runtime.vendor_plans_company_condition', $company_condition);
                $condition .= $company_condition;
            }
        }
    }
}

function fn_vendor_plans_get_categories_after_sql(&$categories, &$params, &$join, &$condition, &$fields, &$group_by, &$sortings, &$sorting, &$limit, &$lang_code)
{
    // If we search by category name we do not need to change categories array
    if (isset($params['search_query'])) {
        Registry::del('runtime.vendor_plans_company_category_ids');
        Registry::del('runtime.vendor_plans_company_condition');
    } elseif ($category_ids = Registry::get('runtime.vendor_plans_company_category_ids')) {
        Registry::del('runtime.vendor_plans_company_category_ids');

        foreach ($categories as &$category) {
            if (!in_array($category['category_id'], $category_ids)) {
                $category['disabled'] = true;
            }
        }

        unset($category);
    } elseif ($company_condition = Registry::get('runtime.vendor_plans_company_condition')) {
        // we can't build the correct tree for vendors if there are not available parent categories
        Registry::del('runtime.vendor_plans_company_condition');
        $selected_ids = array_keys($categories);
        // so get skipped parent categories ids
        $parent_ids = array();
        foreach ($categories as $v) {
            if ($v['parent_id'] && !in_array($v['parent_id'], $selected_ids)) {
                $parent_ids = array_merge($parent_ids, explode('/', $v['id_path']));
            }
        }
        if ($parent_ids) {
            $_condition = str_replace($company_condition, '', $condition);
            $_condition .= db_quote(' AND ?:categories.category_id IN (?a)', array_unique($parent_ids));
            $fields[] = '1 as disabled'; //mark such categories as disabled
            $parent_categories = db_get_hash_array(
                "SELECT " . implode(',', $fields)
                . " FROM ?:categories"
                . " LEFT JOIN ?:category_descriptions"
                . "  ON ?:categories.category_id = ?:category_descriptions.category_id"
                . "  AND ?:category_descriptions.lang_code = ?s $join"
                . " WHERE 1 ?p $group_by $sorting ?p",
                'category_id', $lang_code, $_condition, $limit
            );
            $categories = $categories + $parent_categories;
        }
    }
}

function fn_vendor_plans_get_category_data(&$category_id, &$field_list, &$join, &$lang_code, &$conditions)
{
    if ($company_id = Registry::get('runtime.company_id')) {
        $plan = VendorPlan::model()->find(array('company_id' => $company_id));
        if ($plan && $plan->category_ids) {
            $conditions .= db_quote(" AND ?:categories.category_id IN(?n)", $plan->category_ids);
        }
    }
}

function fn_vendor_plans_set_admin_notification(&$user_data)
{
    Tygh::$app['session']['vendor_plans_payments'] = true;
}

function fn_vendor_plans_dispatch_before_display()
{
    if (!empty(Tygh::$app['session']['vendor_plans_payments'])) {
        unset(Tygh::$app['session']['vendor_plans_payments']);
        Tygh::$app['view']->assign('vendor_plans_payments', true);
    }
}

function fn_vendor_plans_update_product_pre(&$product_data, &$product_id, &$lang_code, &$can_update)
{
    if ($can_update) {

        $company_id = Registry::get('runtime.company_id');
        if (!$company_id) {
            if (isset($product_data['company_id'])) {
                $company_id = $product_data['company_id'];
            } else {
                $company_id = db_get_field('SELECT company_id FROM ?:products WHERE product_id = ?i', $product_id);
            }
        }

        if ($company_id) {

            $company = Company::model()->find($company_id);
            if (!$product_id && !$company->canAddProduct(true)) {
                $can_update = false;
            }

            if ($company->category_ids) {
                if (
                    !empty($product_data['main_category'])
                    && !in_array($product_data['main_category'], $company->category_ids)
                ) {
                    unset($product_data['main_category']);
                }
                if (empty($product_data['category_ids'])) {
                    $product_data['category_ids'] = db_get_fields(
                        "SELECT category_id FROM ?:products_categories WHERE product_id = ?i", $product_id
                    );
                }
                $product_data['category_ids'] = array_intersect($product_data['category_ids'], $company->category_ids);
                if (empty($product_data['category_ids'])) {
                    $can_update = false;
                }
                if (!$can_update) {
                    fn_set_notification('E', __('error'), __('vendor_plans.category_is_not_available'));
                }
            }
        }

    }
}

// Exim

function fn_vendor_plans_import_check_object_id($primary_object_id, &$processed_data, &$skip_record)
{
    $company = Company::current();
    if ($company && empty($primary_object_id) && !$company->canAddProduct(true)) {
        $skip_record = true;
        $processed_data['S'] ++;
    }
}

/**
 * @param string|int            $company_id                Company identifier
 * @param string                $company_name              Company name
 * @param string                $main_category_path        Main category path
 * @param string                $secondary_categories_path Secondary categories paths
 * @param string                $category_delimiter        Category delimiter
 * @param array<string, int>    $processed_data            Quantity of the loaded objects. Objects:
 *                                                      'E' - quantity existent products, 'N' - quantity new products,
 *                                                      'S' - quantity skipped products, 'C' - quantity vendors
 * @param string                $lang_code                 Two-letter language code (e.g. 'en', 'ru', etc.)
 * @param bool                  $skip_record               Skip record flag
 * @param array<string, string> $primary_object_id         Primary object definition
 */
function fn_vendor_plans_import_skip_products_with_unavailable_categories(
    $company_id,
    $company_name,
    $main_category_path,
    $secondary_categories_path,
    $category_delimiter,
    array &$processed_data,
    $lang_code,
    &$skip_record,
    $primary_object_id
) {
    if (empty($company_id)) {
        $company_id = fn_get_company_id_by_name((string) $company_name);
    }

    if (!$company_id) {
        return;
    }

    $lang_code = !empty($lang_code) ? $lang_code : CART_LANGUAGE;

    /** @var \Tygh\Models\Company $company */
    $company = Company::model()->find($company_id);
    if (!$company) {
        return;
    }

    $company_category_ids = $company->category_ids;

    if (!$company_category_ids) {
        //if vendor has no category restrictions
        return;
    }

    if (!$main_category_path && !$secondary_categories_path && isset($primary_object_id['product_id'])) {
        $category_id = db_get_field('SELECT category_id FROM ?:products_categories WHERE product_id = ?i', $primary_object_id['product_id']);
        if ($category_id && in_array($category_id, $company_category_ids)) {
            //if no categories to import and product already exists and have its own categories
            return;
        }
    }

    $set_delimiter = ';';
    if ($main_category_path && $secondary_categories_path) {
        $categories_paths = explode($set_delimiter, implode($set_delimiter, [$main_category_path, $secondary_categories_path]));
    } else {
        $categories_paths = $main_category_path ? [$main_category_path] : explode($set_delimiter, $secondary_categories_path);
    }

    if (fn_is_empty($categories_paths)) {
        static $default_categories;
        if (empty($default_categories)) {
            $default_categories = fn_get_all_default_categories_ids();
        }
        foreach ($default_categories as $default_category) {
            if (in_array($default_category, $company_category_ids)) {
                //no categories to import, one from the default will be imported later, since it is in the allowed list.
                return;
            }
        }
    }

    foreach ($categories_paths as $category_path) {
        if (strpos($category_path, $category_delimiter) !== false) {
            $paths = explode($category_delimiter, $category_path);
            array_walk($paths, 'fn_trim_helper');
        } else {
            $paths = [trim($category_path)];
        }

        $category_id = $parent_id = 0;
        if (!empty($paths)) {
            foreach ($paths as $name) {
                $sql = 'SELECT ?:categories.category_id FROM ?:category_descriptions'
                    . ' INNER JOIN ?:categories ON ?:categories.category_id = ?:category_descriptions.category_id'
                    . ' WHERE ?:category_descriptions.category = ?s AND lang_code = ?s AND parent_id = ?i';

                $category_id = db_get_field($sql, $name, $lang_code, $parent_id);

                if (empty($category_id)) {
                    // Skip record if category does not exist (trying to create new category)
                    $skip_record = true;
                    $processed_data['S']++;

                    return;
                }

                $parent_id = $category_id;
            }
        }

        if ($category_id && !in_array($category_id, $company_category_ids)) {
            // Skip record if the category not available to vendor
            $skip_record = true;
            $processed_data['S']++;

            return;
        }
    }
}

/**
 * Calculates commission based on payout.
 *
 * @param array<string, string|float|int|array> $order_info   Order information
 * @param array<string, string|int|array>       $company_data Company to which order belongs to
 * @param array<string, string|int|array>       $payout_data  Payout data to be written to database
 *
 * @return array<string, string|int|array> Payout data with calculated commission
 */
function fn_calculate_commission_for_payout(array $order_info, array $company_data, array $payout_data)
{
    if (
        empty($payout_data)
        || empty($company_data['plan_id'])
    ) {
        return $payout_data;
    }

    $plan = VendorPlan::model()->find($company_data['plan_id']);
    if (empty($plan)) {
        return $payout_data;
    }

    $vendor_plan_settings = Registry::get('addons.vendor_plans');
    $storefront_id = $order_info['storefront_id'] ?? null;
    $is_collect_taxes_from_vendors = fn_vendor_plans_is_collect_taxes_from_vendors((int) $storefront_id);
    $is_refund = $payout_data['payout_type'] === VendorPayoutTypes::ORDER_REFUNDED;

    switch ($payout_data['payout_type']) {
        case VendorPayoutTypes::ORDER_CHANGED:
            $total = $payout_data['details']['order_total'];
            $commission = $total === 0.0 ? 0 : $plan->commission;
            $fixed_commission = $payout_data['details']['order_total'] === 0.0 ? 0 : $plan->fixed_commission;

            $vendor_taxes = $taxes = $payout_data['details']['order_tax_amount'];
            $shipping_cost = $payout_data['details']['order_shipping_cost'] + $payout_data['details']['shipping_tax_amount'];
            if (!YesNo::toBool($vendor_plan_settings['include_shipping'])) {
                $taxes -= $payout_data['details']['shipping_tax_amount'];
            }
            $surcharge_to_commission = $surcharge_from_total =
                $payout_data['details']['order_payment_surcharge'] + $payout_data['details']['order_surcharge_tax_amount'];

            break;
        case VendorPayoutTypes::ORDER_REFUNDED:
            $total = $payout_data['order_amount']; // When order was refunded - $total always has to be a negative value or 0.
            $commission = $total === 0.0 ? 0 : $plan->commission; // Commission should be calculated from income. If there is no income - no commission.
            $fixed_commission = $total === 0.0 ? 0 : $plan->fixed_commission;

            $shipping_cost = $payout_data['details']['order_shipping_cost'] - $payout_data['old_details']['order_shipping_cost'];

            $new_taxes = $payout_data['details']['order_tax_amount'];
            $old_taxes = $payout_data['old_details']['order_tax_amount'];

            $surcharge_to_commission = $surcharge_from_total = 0.0;

            if (!YesNo::toBool($vendor_plan_settings['include_shipping'])) {
                $old_shipping_taxes = $payout_data['old_details']['shipping_tax_amount'];
                $new_shipping_taxes = $payout_data['details']['shipping_tax_amount'];
                $new_taxes -= abs($new_shipping_taxes);
                $old_taxes -= abs($old_shipping_taxes);
            }
            $vendor_taxes = $taxes = $new_taxes - $old_taxes;
            break;
        case VendorPayoutTypes::ORDER_PLACED:
        default:
            $total = $payout_data['details']['order_total'];
            $commission = $order_info['total'] > 0 ? $plan->commission : 0;
            $fixed_commission = $order_info['total'] > 0 ? $plan->fixed_commission : 0;

            $shipping_cost = $payout_data['details']['order_shipping_cost'] + $payout_data['details']['shipping_tax_amount'];
            $surcharge_to_commission = $surcharge_from_total =
                $payout_data['details']['order_payment_surcharge']
                + $payout_data['details']['order_surcharge_tax_amount'];
            $vendor_taxes = $taxes = $payout_data['details']['order_tax_amount'] - $payout_data['details']['order_surcharge_tax_amount'];
            if (!YesNo::toBool($vendor_plan_settings['include_shipping'])) {
                $taxes -= $payout_data['details']['shipping_tax_amount'];
            } elseif (!YesNo::toBool($vendor_plan_settings['include_taxes_in_commission'])) {
                $shipping_cost -= $payout_data['details']['shipping_tax_amount'];
            }
            break;
    }

    /**
     * This hook is executed before the commission amount was calculated for a payout.
     * Allows to modify the values that payout calculation is based on.
     *
     * @param array $order_info              Order information
     * @param array $company_data            Company to which order belongs to
     * @param array $payout_data             Payout data to be written to database
     * @param float $total                   Order total amount
     * @param float $shipping_cost           Order shipping cost amount
     * @param float $surcharge_from_total    Order payment surcharge to be subtracted from total
     * @param float $surcharge_to_commission Order payment surcharge to be added to commission amount
     * @param float $commission              The commission percent value
     * @param float $taxes                   All taxes that could be commissioned
     * @param float $vendor_taxes            All vendor taxes that can be collected
     */
    fn_set_hook(
        'vendor_plans_calculate_commission_for_payout_before',
        $order_info,
        $company_data,
        $payout_data,
        $total,
        $shipping_cost,
        $surcharge_from_total,
        $surcharge_to_commission,
        $commission,
        $taxes,
        $vendor_taxes
    );

    $formatter = ServiceProvider::getPriceFormatter();

    // Calculate commission excluding payment surcharge

    /** @var float $not_included_into_commission */
    $not_included_into_commission = $surcharge_from_total;
    if (!YesNo::toBool($vendor_plan_settings['include_shipping'])) {
        $not_included_into_commission += $shipping_cost;
    }
    if (!YesNo::toBool($vendor_plan_settings['include_taxes_in_commission'])) {
        $not_included_into_commission += $taxes;
    }

    $percent_commission = ($total - $not_included_into_commission) * $commission / 100;

    $percent_commission = $formatter->round($percent_commission);

    $commission_amount = $surcharge_to_commission;
    if (!$is_refund) {
        $fixed_commission = ((float) $order_info['total'] === (float) $payout_data['order_amount']) ? $fixed_commission : 0;
        $commission_amount += $percent_commission + $fixed_commission;
    } else {
        $fixed_commission = ((int) $order_info['subtotal'] === 0) ? $fixed_commission : 0;
        $commission_amount += $percent_commission - $fixed_commission;
    }

    $commission_amount = $formatter->round($commission_amount);

    if (abs($commission_amount) > abs($total)) {
        $commission_amount = $total;
    }

    $payout_data['commission'] = $commission;
    $payout_data['marketplace_profit'] = $commission_amount;
    $payout_data['commission_type'] = 'P'; // Backward compatibility
    $payout_data['plan_id'] = $company_data['plan_id'];
    $payout_data['extra'] = [
        'commission_amount'       => $commission_amount,
        'percent_commission'      => $percent_commission,
        'surcharge_to_commission' => $surcharge_to_commission,
        'shipping_cost'           => $shipping_cost,
        'taxes'                   => $taxes,
        'fixed_commission'        => $fixed_commission,
        'total'                   => $total,
        'commission'              => $commission,
        'vendor_taxes'            => $vendor_taxes,
        'surcharge_from_total'    => $surcharge_from_total,
    ];
    $payout_data['commission_amount'] = $commission_amount;
    if ($is_collect_taxes_from_vendors) {
        $payout_data['commission_amount'] += $vendor_taxes;
    }

    /**
     * This hook is executed after the commission amount was calculated for a payout.
     * Allows modifying payout data.
     *
     * @param array $order_info Order information
     * @param array $company_data Company to which order belongs to
     * @param array $payout_data Payout data to be written to database
     */
    fn_set_hook('vendor_plans_calculate_commission_for_payout_post', $order_info, $company_data, $payout_data);

    return $payout_data;
}

/**
 * Hook handler: adds commission values to refunds performed via RMA add-on.
 *
 * @param array<string, int|string|array> $data           Request parameters
 * @param array<string, int|string|array> $order_info     Order information from ::fn_get_orders()
 * @param array<string, int|string|array> $return_info    Return request from ::fn_get_return_info()
 * @param array<string, int|string|array> $payout_data    Payout data to be stored in the DB
 * @param array<string, int|string|array> $old_order_info Order information before refund
 */
function fn_vendor_plans_rma_update_details_create_payout(array &$data, array &$order_info, array &$return_info, array &$payout_data, array $old_order_info)
{
    $company_data = fn_get_company_data((int) $order_info['company_id']);
    $payout_data = fn_calculate_commission_for_payout($order_info, $company_data, $payout_data);
}

/**
 * Hook handler: adds commission values to refunds performed via PayPal add-on.
 *
 * @param int   $order_id    Order ID
 * @param array $data        IPN request parameters
 * @param array $order_info  Order info from ::fn_get_order_info()
 * @param array $payout_data Payout data to be stored in the DB
 */
function fn_vendor_plans_process_paypal_ipn_create_payout(&$order_id, &$data, &$order_info, &$payout_data)
{
    $company_data = fn_get_company_data($order_info['company_id']);
    //TODO Probably does not work for a long time.
    $payout_data = fn_calculate_commission_for_payout($order_info, $company_data, $payout_data);
}

/**
 * Hook handler: to add vendor plans data to corresponding profile field
 *
 * @param $location
 * @param $_auth
 * @param $lang_code
 * @param $params
 * @param $profile_fields
 * @param $sections
 */
function fn_vendor_plans_get_profile_fields_post($location, $_auth, $lang_code, $params, &$profile_fields, $sections)
{
    static $vendor_plans = null;

    foreach ($profile_fields as $section => &$fields) {

        foreach ($fields as &$field) {

            if ($field['field_type'] != PROFILE_FIELD_TYPE_VENDOR_PLAN) {
                continue;
            }

            if ($vendor_plans === null) {
                $vendor_plans = VendorPlan::model()->findMany(array(
                    'allowed_for_company_id' => Registry::get('runtime.company_id'),
                    'storefront_id'          => Tygh::$app['storefront']->storefront_id,
                ));
            }

            $field['plans'] = $vendor_plans;
        }
    }
}

/**
 * Hook handler: prepares extra data before saving to the database
 *
 * @param VendorPayouts $vendor_payouts Class instance
 * @param array         $data           Payout data
 * @param int           $payout_id      Payout identifier
 * @param string        $action         Current action (create or update)
 */
function fn_vendor_plans_vendor_payouts_update($vendor_payouts, &$data, $payout_id, $action)
{
    if (isset($data['extra']) && is_array($data['extra'])) {
        $data['extra'] = json_encode($data['extra']);
    }
}

/**
 * The "get_products_pre" hook handler.
 *
 * Actions performed:
 *     - Adds filtering of products by categories for a common products.
 *
 * @param array{show_master_products_only?: bool} $params         Product search params
 * @param int                                     $items_per_page Items per page
 * @param string                                  $lang_code      Two-letter language code (e.g. 'en', 'ru', etc.)
 *
 * @see fn_get_products
 */
function fn_vendor_plans_get_products_pre(array &$params, $items_per_page, $lang_code)
{
    if (empty($params['show_master_products_only']) || AREA !== SiteArea::ADMIN_PANEL) {
        return;
    }

    $company = Company::current();

    if (!$company || empty($company->category_ids)) {
        return;
    }

    $params['cid'] = implode(',', $company->category_ids);
}

/**
 * Hook handler: ignores pre-moderation when a vendor to change its plan
 *
 * @param array<string,string|int|bool|string[]> $company_data      Company data
 * @param array<string,string|int|bool|string[]> $orig_company_data Original company data
 * @param array<string,string|int|bool|string[]> $company_data_diff Changed company data
 *
 * @param-out array<string,string|int|bool|string[]>|array<empty,empty> $company_data_diff Changed company data
 *
 * @return void
 */
function fn_vendor_plans_vendor_data_premoderation_diff_company_data_post(array $company_data, array $orig_company_data, array &$company_data_diff)
{
    unset($company_data_diff['plan_id']);
}

/**
 * Defines is collect taxes from vendor or not
 *
 * @param int|null $storefront_id Storefront identifier
 *
 * @return bool True if taxes are collect from vendors, false otherwise
 */
function fn_vendor_plans_is_collect_taxes_from_vendors($storefront_id = null)
{
    $is_collect_taxes_from_vendors = isset($storefront_id)
        ? Settings::instance(['storefront_id' => (int) $storefront_id])->getValue('collect_taxes_from_vendors', 'vendor_plans')
        : Registry::get('addons.vendor_plans.collect_taxes_from_vendors');

    return YesNo::toBool($is_collect_taxes_from_vendors);
}

/**
 * The "google_sitemap_write_companies_to_sitemap_before_vendor_stores" hook handler
 *
 * @param \Tygh\Storefront\Storefront $storefront         Storefront to generate sitemap for
 * @param string                      $last_modified_time Sitemap's last modified time in format YYYY-MM-DD
 * @param string                      $change_frequency   Sitemap item's update frequency
 * @param float                       $priority           Sitemap item's priority
 * @param resource                    $file               File the sitemap is written into
 * @param int                         $link_counter       Amount of links in the current sitemap file
 * @param int                         $file_counter       Amount of sitemap files
 * @param string                      $sitemap_header     Sitemap header
 * @param string                      $sitemap_footer     Sitemap footer
 * @param string[]                    $languages          List of languages to generate the sitemap for
 * @param array<int, int>             $vendor_stores      List of company IDs
 *
 * @return void
 *
 * @param-out array<array-key, string|Tygh\Models\Company&static|mixed> $vendor_stores
 */
function fn_vendor_plans_google_sitemap_write_companies_to_sitemap_before_vendor_stores(
    Storefront $storefront,
    $last_modified_time,
    $change_frequency,
    $priority,
    $file,
    $link_counter,
    $file_counter,
    $sitemap_header,
    $sitemap_footer,
    array $languages,
    array &$vendor_stores
) {
    $plan_ids_with_vendor_store = VendorPlan::model()->findMany(['get_ids' => true, 'status' => ObjectStatuses::ACTIVE, 'vendor_store' => true]);
    $companies_with_vendor_store = Company::model()->findMany(
        ['get_ids' => true, 'status' => ObjectStatuses::ACTIVE, 'plan_id' => $plan_ids_with_vendor_store]
    );
    $vendor_stores = array_intersect($vendor_stores, $companies_with_vendor_store);
}

/**
 * The "create_company_admin_post" hook handler
 *
 * Actions performed:
 *  - Notifies the vendor to change the plan, if necessary.
 *
 * @param array<string, string|int|bool> $company_data Company data
 * @param string                         $fields       Fields list
 * @param bool                           $notify       Notify flag
 * @param array<string, string|int>      $user         User data
 *
 *  * @see \fn_create_company_admin()
 *
 * @return void
 */
function fn_vendor_plans_create_company_admin_post(array $company_data, $fields, $notify, array $user)
{
    if (!fn_vendor_plans_is_vendor_admin_creation_needed($company_data)) {
        return;
    }

    $event_dispatcher = EventDispatcherProvider::getEventDispatcher();

    $event_dispatcher->dispatch(
        'vendor_plans.plan_changed',
        [
            'company_id'  => $user['company_id'],
            'old_plan_id' => 0,
            'lang_code'   => $company_data['lang_code'],
            'new_account' => true
        ]
    );

    $event_dispatcher->dispatch(
        'vendor_plans.plan_payment',
        [
            'company_id'  => $user['company_id'],
            'lang_code'   => $company_data['lang_code'],
        ]
    );
}

/**
 * Checks if vendor admin creation is required.
 *
 * @param array<string, string|int|bool> $company_data Company data
 *
 * @return bool
 */
function fn_vendor_plans_is_vendor_admin_creation_needed(array $company_data)
{
    if (isset($company_data['is_create_vendor_admin']) && YesNo::toBool($company_data['is_create_vendor_admin'])) {
        return true;
    }

    return false;
}

/**
 * The "master_products_create_vendor_product_pre" hook handler
 *
 * @phpcs:disable SlevomatCodingStandard.Variables.UnusedVariable.UnusedVariable
 *
 * @param int                                    $master_product_id Master product ID
 * @param int                                    $company_id        Company ID
 * @param array<string,string|int|bool|string[]> $product           Master product data
 * @param \Tygh\Common\OperationResult           $result            Operation result
 * @param bool                                   $can_create        Flag, allows addon to forbid to create product
 *
 * @return void
 */
function fn_vendor_plans_master_products_create_vendor_product_pre($master_product_id, $company_id, $product, OperationResult $result, &$can_create)
{
    if (!$can_create) {
        return;
    }

    /** @var \Tygh\Models\Company $company */
    $company = Company::model()->find($company_id);

    if (!$company->canAddProduct(true)) {
        $can_create = false;
    }

    $result->setSuccess(false);
}

/**
 * The "product_variations_check_limit_new_product_variations" hook handler.
 *
 * Actions performed:
 *  - Adds an error message if the number of new variations being created as one product exceeds the available quantity according to the vendor plan.
 *
 * @param \Tygh\Addons\ProductVariations\Service $service            Service instance
 * @param int                                    $combinations_count Count of new combinations being created
 * @param int                                    $product_id         Product id
 * @param \Tygh\Common\OperationResult           $result             Result of current operation
 *
 * @return void
 *
 * @see \Tygh\Addons\ProductVariations\Service::checkLimitNewProductVariations()
 */
function fn_vendor_plans_product_variations_check_limit_new_product_variations(Service $service, $combinations_count, $product_id, OperationResult &$result)
{
    $product_data = fn_get_product_data($product_id);

    if (empty($product_data['company_id'])) {
        return;
    }

    $company_data = fn_get_company_data((int) $product_data['company_id']);

    if (empty($company_data['plan_id'])) {
        return;
    }

    $count_company_products = fn_get_products_count([
        'group_child_variations' => true,
        'company_id'             => $product_data['company_id'],
        'parent_product_id'      => 0
    ]);

    /** @var VendorPlan $current_vendor_plan */
    $current_vendor_plan = VendorPlan::model()->find($company_data['plan_id'], ['get_companies_count' => true]);
    $products_limit = !empty($current_vendor_plan)
        ? (int) $current_vendor_plan->products_limit
        : 0;

    if ($products_limit === 0) {
        return;
    }

    $calculation_limit = $products_limit - ($combinations_count + $count_company_products);

    if ($calculation_limit >= 0) {
        return;
    }

    if ($products_limit - $count_company_products > 0) {
        $result->addError(
            'combinations_limit_reached',
            __(
                'product_variations.error.variations_as_separate_products_exceeds_the_limit',
                [
                    '[available_count]' => $products_limit - $count_company_products,
                ]
            )
        );
    } else {
        if (defined('API')) {
            $result->addError(
                'combinations_limit_reached',
                __('product_variations.error.limit_creating_new_products_exhausted')
            );
        } else {
            $result->addError(
                'combinations_limit_reached',
                __(
                    'product_variations.error.limit_creating_new_products_exhausted_with_url',
                    [
                        '[url_upgrade_plan]' => fn_url('companies.update?company_id=' . $product_data['company_id'] . '&selected_section=plan')
                    ]
                )
            );
        }
    }
}
