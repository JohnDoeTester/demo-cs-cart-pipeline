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

use Tygh\Enum\UsergroupTypes;
use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    return [CONTROLLER_STATUS_OK];
}

if ($mode === 'manage') {
    $exclude_groups = [];
    if (!fn_check_current_user_access('manage_admin_usergroups')) {
        $exclude_groups[] = UsergroupTypes::TYPE_ADMIN;
    }
    if (!fn_check_current_user_access('manage_vendor_usergroups')) {
        $exclude_groups[] = USERGROUP_TYPE_VENDOR;
    }
    $usergroup_types = UsergroupTypes::getList($exclude_groups);

    $usergroups = fn_get_usergroups(['exclude_types' => $exclude_groups], DESCR_SL);
    $privileges_data = fn_get_usergroup_privileges(['type' => UsergroupTypes::TYPE_ADMIN]);
    $grouped_privileges = fn_group_usergroup_privileges($privileges_data);

    Tygh::$app['view']->assign([
        'usergroups'         => $usergroups,
        'usergroup_types'    => $usergroup_types,
        'grouped_privileges' => $grouped_privileges,
    ]);
}

if ($mode === 'update') {
    $usergroup_id = isset($_REQUEST['usergroup_id']) ? $_REQUEST['usergroup_id'] : null;

    $requested_mtype = db_get_field('SELECT type FROM ?:usergroups WHERE usergroup_id = ?i', $usergroup_id);
    if (
        $requested_mtype === USERGROUP_TYPE_VENDOR
        && !fn_check_current_user_access('manage_vendor_usergroups')
    ) {
        return [CONTROLLER_STATUS_DENIED];
    }
}
