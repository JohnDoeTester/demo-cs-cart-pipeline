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

use Tygh\Api;
use Tygh\Api\AEntity;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\UserTypes;
use Tygh\Enum\VendorStatuses;
use Tygh\Models\Company;
use Tygh\Models\VendorPlan;
use Tygh\Registry;
use Tygh\Settings;
use Tygh\Addons\VendorPrivileges\ServiceProvider;
use Tygh\Enum\UsergroupStatuses;

/**
 * Add-on install handler
 */
function fn_vendor_privileges_install()
{
    $usergroup_id = (int) db_get_field('SELECT usergroup_id FROM ?:usergroups WHERE type = ?s LIMIT 1', USERGROUP_TYPE_VENDOR);

    if (empty($usergroup_id)) {
        return;
    }

    $privileges = ServiceProvider::createPrivileges();

    foreach ($privileges->getVendorPrivileges() as $privilege) {
        db_query('INSERT INTO ?:usergroup_privileges ?e', [
            'usergroup_id' => $usergroup_id,
            'privilege'    => $privilege
        ]);
    }

    Settings::instance()->updateValue('default_vendor_usesrgroup', $usergroup_id, 'vendor_privileges');
}

/**
 * Hook handler for adding new user group type
 */
function fn_vendor_privileges_usergroup_types_get_list(&$usergroup_types)
{
    $usergroup_types[USERGROUP_TYPE_VENDOR] = __('vendor');
}

/**
 * Hook handler for adding new user group type
 */
function fn_vendor_privileges_usergroup_types_get_map_user_type(&$map)
{
    $map[UserTypes::VENDOR] = USERGROUP_TYPE_VENDOR;
}

/**
 * Hook handler for filtering out privileges that are not allowed to Vendor user group
 */
function fn_vendor_privileges_get_privileges_post($usergroup, &$privileges)
{
    if (empty($privileges) || $usergroup['type'] !== USERGROUP_TYPE_VENDOR) {
        return;
    }

    $vendor_allowed_privileges = Tygh::$app['addons.vendor_privileges.privileges']->getVendorPrivileges();

    foreach ($privileges as $key => $privilege) {
        if (!in_array($privilege['privilege'], $vendor_allowed_privileges, true)) {
            unset($privileges[$key]);
        }
    }
}

/**
 * Hook handler for checking vendor administrator privilege for editing other users (vendor administrators from the same company)
 */
function fn_vendor_privileges_check_editable_permissions_post($auth, $user_data, &$has_permissions)
{
    if (!$has_permissions) {
        return;
    }

    if ($auth['user_type'] === UserTypes::VENDOR
        && $auth['user_id'] !== $user_data['user_id']
        && !empty($auth['usergroup_ids'])
    ) {
        $has_permissions = fn_check_current_user_access('manage_users');
    }
}

/**
 * Hook handler for adding user group after updating vendor administrator
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
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_vendor_privileges_change_company_status_before_mail(
    $company_id,
    $status_to,
    $reason,
    $status_from,
    $skip_query,
    $notify,
    array $company_data,
    array $user_data,
    $result,
    $account
) {
    if (
        $account !== 'updated'
        || $user_data['user_type'] !== UserTypes::VENDOR
    ) {
        return;
    }

    fn_vendor_privileges_check_and_update_usergroup_status($user_data);
}

/**
 * Hook handler for adding default user group after creating new vendor administrator
 *
 * @param string $action    Action type
 * @param array  $user_data User information
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_vendor_privileges_update_profile($action, array $user_data, $current_user_data)
{
    if (
        $action !== 'add'
        || $user_data['user_type'] !== UserTypes::VENDOR
    ) {
        return;
    }

    fn_vendor_privileges_check_and_update_usergroup_status($user_data);
}

/**
 * Hook handler
 * Allows privileges for the vendor usergroups
 */
function fn_vendor_privileges_check_can_usergroup_have_privileges_post($usergroup, &$result)
{
    if ($result) {
        return;
    }

    $result = $usergroup['type'] === USERGROUP_TYPE_VENDOR;
}

/**
 * Hook handler: adds vendor usergroups for the payment configuration page.
 */
function fn_vendor_privileges_get_payment_usergroups(&$params, $lang_code)
{
    if (Registry::get('runtime.company_id')) {
        return;
    }

    if (isset($params['type'])) {
        $params['type'][] = USERGROUP_TYPE_VENDOR;
    }
}

/**
 * Hook handler: adds vendor usergroups for logged in user.
 */
function fn_vendor_privileges_define_usergroups($user_data, $area, &$usergroup_types)
{
    if (isset($user_data['user_type']) && $user_data['user_type'] === 'V') {
        $usergroup_types[] = USERGROUP_TYPE_VENDOR;
    }
}

/**
 * Checks if vendor plans addon active and updates usergroup status
 *
 * @param array $user_data User information
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_vendor_privileges_check_and_update_usergroup_status(array $user_data)
{
    if (Registry::get('addons.vendor_plans.status') === ObjectStatuses::ACTIVE) {
        $company = Company::model()->find($user_data['company_id']);

        if ($company instanceof Company) {
            fn_vendor_privileges_change_usergroups_for_company_admins([$company->company_id], [], $company->plan->usergroup_ids);
        }
    }

    if (!$default_usergroup_id = Registry::get('addons.vendor_privileges.default_vendor_usesrgroup')) {
        return;
    }

    fn_change_usergroup_status(ObjectStatuses::ACTIVE, $user_data['user_id'], $default_usergroup_id);
}

/**
 * Checks if vendor administrator can use order_management
 *
 * @return bool Flag: indicates if vendor administrator has the permission to order management
 */
function fn_vendor_privileges_check_permission_order_management()
{
    $have_privilege = fn_check_current_user_access('edit_order');
    $user_id = isset(Tygh::$app['session']['auth']['user_id']) ? Tygh::$app['session']['auth']['user_id'] : null;
    $auth_user_type = isset(Tygh::$app['session']['auth']['user_type']) ? Tygh::$app['session']['auth']['user_type'] : null;

    if (!$user_id || !$auth_user_type || $auth_user_type != UserTypes::VENDOR) {
        return $have_privilege;
    }

    $have_vendor_user_group = false;
    foreach (fn_get_user_usergroups($user_id) as $user_usergroup) {
        if ($user_usergroup['type'] === USERGROUP_TYPE_VENDOR && $user_usergroup['status'] == UsergroupStatuses::ACTIVE) {
            $have_vendor_user_group = true;
            break;
        }
    }

    return $have_vendor_user_group && $have_privilege;
}

/**
 * Disables and activates user groups for company administrators.
 *
 * @param int[] $company_ids            Company identifiers
 * @param int[] $disable_usergroup_ids  Disable user group identifiers
 * @param int[] $activate_usergroup_ids Activate user group identifiers
 *
 * @return void
 */
function fn_vendor_privileges_change_usergroups_for_company_admins(array $company_ids, array $disable_usergroup_ids, array $activate_usergroup_ids)
{
    if (
        !$company_ids
        || !(
            $disable_usergroup_ids
            || $activate_usergroup_ids
        )
    ) {
        return;
    }

    $companies_admin_ids = db_get_fields(
        'SELECT user_id FROM ?:users WHERE company_id IN (?n) AND user_type = ?s',
        $company_ids,
        UserTypes::VENDOR
    );

    foreach ($companies_admin_ids as $company_admin_id) {
        foreach ($disable_usergroup_ids as $disable_usergroup_id) {
            fn_change_usergroup_status(ObjectStatuses::AVAILABLE, $company_admin_id, $disable_usergroup_id);
        }

        foreach ($activate_usergroup_ids as $activate_usergroup_id) {
            fn_change_usergroup_status(ObjectStatuses::ACTIVE, $company_admin_id, $activate_usergroup_id);
        }
    }
}

/**
 * The "vendor_plan_update" hook handler.
 *
 * Actions performed:
 *     - Disables and activates usergroups based on attributes.
 *
 * @param \Tygh\Models\VendorPlan $plan      Instance of VendorPlan
 * @param bool                    $result    Update result
 * @param int[]                   $companies Companies
 *
 * @return void
 */
function fn_vendor_privileges_vendor_plan_update(VendorPlan $plan, $result, array $companies)
{
    /** @var array{usergroup_ids: array<int>, disable_removed_usergroups?: string, activate_added_usergroups?: string} $attributes */
    $attributes = $plan->attributes();
    /** @var array{usergroup_ids: array<int>} $current_attributes */
    $current_attributes = $plan->currentAttributes();

    $disable_usergroup_ids = $activate_usergroup_ids = [];
    if ($companies && !empty($attributes['disable_removed_usergroups'])) {
        $disable_usergroup_ids = array_diff($current_attributes['usergroup_ids'], $attributes['usergroup_ids']);
    }

    if ($companies && !empty($attributes['activate_added_usergroups'])) {
        $activate_usergroup_ids = array_diff($attributes['usergroup_ids'], $current_attributes['usergroup_ids']);
    }

    fn_vendor_privileges_change_usergroups_for_company_admins($companies, $disable_usergroup_ids, $activate_usergroup_ids);
}

/**
 * The "update_company" hook handler.
 *
 * Actions performed:
 *     - Disables and activates usergroups when changing plan.
 *
 * @param array<string|int> $company_data Company data
 * @param int               $company_id   Company integer identifier
 * @param string            $lang_code    Two-letter language code (e.g. 'en', 'ru', etc.)
 * @param string            $action       Flag determines if company was created (add) or just updated (update).
 *
 * @return void
 */
function fn_vendor_privileges_update_company($company_data, $company_id, $lang_code, $action)
{
    if (Registry::get('addons.vendor_plans.status') !== ObjectStatuses::ACTIVE) {
        return;
    }

    if (
        !(isset($company_data['plan_id'])
        && isset($company_data['current_plan'])
        && $company_data['plan_id'] !== $company_data['current_plan']
        && $company_data['status'] !== VendorStatuses::NEW_ACCOUNT)
    ) {
        return;
    }

    /** @var \Tygh\Models\Company $company */
    $company = Company::model()->find($company_id);
    /** @var \Tygh\Models\VendorPlan $current_plan */
    $current_plan = VendorPlan::model()->find($company_data['current_plan']);
    /** @var \Tygh\Models\VendorPlan $new_plan */
    $new_plan = VendorPlan::model()->find($company_data['plan_id']);

    $disable_usergroup_ids = array_diff($current_plan->usergroup_ids ?: [], $new_plan->usergroup_ids ?: []);
    $activate_usergroup_ids = array_diff($new_plan->usergroup_ids ?: [], $current_plan->usergroup_ids ?: []);

    fn_vendor_privileges_change_usergroups_for_company_admins([$company->company_id], $disable_usergroup_ids, $activate_usergroup_ids);
}

/**
 * The "api_check_access" hook handler.
 *
 * Actions performed:
 *     - Disables and activates usergroups when changing plan.
 *
 * @param \Tygh\Api              $api         Api object
 * @param \Tygh\Api\AEntity|null $entity      Api entity
 * @param string                 $method_name Method name (index, create, update, delete)
 * @param bool                   $can_access  Is user can access this method
 *
 * @return void
 */
function fn_vendor_privileges_api_check_access(Api $api, $entity, $method_name, &$can_access)
{
    if (!$entity instanceof AEntity || !$entity->isVendorUser()) {
        return;
    }

    $privileges = $entity->privileges();

    if (!isset($privileges[$method_name])) {
        return;
    }

    if (is_bool($privileges[$method_name])) {
        $can_access = $privileges[$method_name];
    } else {
        if ($entity->getAuth()) {
            $can_access = fn_check_user_access((int) $entity->getAuth()['user_id'], $privileges[$method_name]);
        }
    }
}

/**
 * The "delete_usergroups_pre" hook handler.
 *
 * Actions performed:
 *     - Removes ids of vendor usergroups if the user does not have permission to manage vendor usergroups.
 *
 * @param int[] $usergroup_ids User group identifiers
 *
 * @return void
 *
 * @see \fn_delete_usergroups()
 */
function fn_vendor_privileges_delete_usergroups_pre(array &$usergroup_ids)
{
    if (
        !isset(Tygh::$app['session']['auth']['user_id'])
        || fn_check_user_access(Tygh::$app['session']['auth']['user_id'], 'manage_vendor_usergroups')
    ) {
        return;
    }

    /** @var int[] $usergroup_ids */
    $usergroup_ids = db_get_fields('SELECT usergroup_id FROM ?:usergroups WHERE usergroup_id IN (?n) AND type != ?s', $usergroup_ids, USERGROUP_TYPE_VENDOR);
}

/**
 * The "update_usergroup_pre" hook handler.
 *
 * Actions performed:
 *     - Prohibits updating or creating the vendor usergroup if the user dous not have permission to manage vendor usergroups.
 *
 * @param array<int|string> $usergroup_data User group data
 * @param int               $usergroup_id   User group integer identifier
 *
 * @param-out array<int|string>|null $usergroup_data User group data
 *
 * @return void
 *
 * @see \fn_update_usergroup()
 */
function fn_vendor_privileges_update_usergroup_pre(array &$usergroup_data, $usergroup_id)
{
    if (
        !isset(Tygh::$app['session']['auth']['user_id'])
        || fn_check_user_access(Tygh::$app['session']['auth']['user_id'], 'manage_vendor_usergroups')
    ) {
        return;
    }

    $requested_mtype = db_get_field('SELECT type FROM ?:usergroups WHERE usergroup_id = ?i', $usergroup_id);
    //phpcs:ignore
    if (
        $requested_mtype === USERGROUP_TYPE_VENDOR
        || !empty($usergroup_data['type'])
        && $usergroup_data['type'] === USERGROUP_TYPE_VENDOR
    ) {
        fn_set_notification(NotificationSeverity::ERROR, __('error'), __('access_denied'));

        $usergroup_data = null;
    }
}
