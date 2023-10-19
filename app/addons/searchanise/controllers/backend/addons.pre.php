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

$notice_addons = [
    'seo',
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($_REQUEST['addon']) && in_array($_REQUEST['addon'], $notice_addons) && in_array($mode, ['update', 'install', 'uninstall'])) {
        fn_se_display_addon_notice($_REQUEST['addon']);
    }

    return;
}

if ($mode == 'update') {
    if ($_REQUEST['addon'] == 'searchanise') {
        fn_se_check_connect();
        fn_se_check_queue();
    }

} elseif ($mode == 'update_status' && in_array($_REQUEST['id'], $notice_addons)) {
    fn_se_display_addon_notice($_REQUEST['addon']);
}
