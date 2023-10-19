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

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'update') {
        $usergroup_id = isset($_REQUEST['usergroup_id']) ? $_REQUEST['usergroup_id'] : null;

        $requested_mtype = db_get_field('SELECT type FROM ?:usergroups WHERE usergroup_id = ?i', $usergroup_id);
        if (
            (
                $requested_mtype === USERGROUP_TYPE_VENDOR
                || !empty($_REQUEST['usergroup_data']['type'])
                && $_REQUEST['usergroup_data']['type'] === USERGROUP_TYPE_VENDOR
            )
            && !fn_check_current_user_access('manage_vendor_usergroups')
        ) {
            return [CONTROLLER_STATUS_DENIED];
        }
    }

    return [CONTROLLER_STATUS_OK];
}
