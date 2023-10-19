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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * @var string $mode
 * @var string $action
 */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode === 'clean') {
        if ($action === 'old') {
            fn_cleanup_old_logs(Registry::get('runtime.company_id'));
        } else {
            fn_cleanup_all_logs(Registry::get('runtime.company_id'));
        }

        fn_set_notification('N', __('notice'), __('successful'));
    }

    return [CONTROLLER_STATUS_REDIRECT, 'logs.manage'];
}

if ($mode == 'manage') {

    list($logs, $search) = fn_get_logs($_REQUEST, Registry::get('settings.Appearance.admin_elements_per_page'));

    Tygh::$app['view']->assign([
        'logs'      => $logs,
        'search'    => $search,
        'log_types' => fn_get_log_types(),
    ]);
}
