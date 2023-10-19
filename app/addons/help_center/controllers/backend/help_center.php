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

$auth = & Tygh::$app['session']['auth'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'set_timestamp_last_view') {
        $storage_key = 'help_center_last_view_' . $auth['user_id'];
        fn_set_storage_data($storage_key, TIME);
    }

    return [CONTROLLER_STATUS_OK];
}

if ($mode === 'manage') {
    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];
    $dispatch_id = empty($_REQUEST['dispatch_id']) ? '' : $_REQUEST['dispatch_id'];

    $view->assign([
        'dispatch_id' => $dispatch_id
    ]);
}
