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

use Tygh\Enum\YesNo;
use Tygh\Http;

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'update') {
        fn_update_mailing_list($_REQUEST['mailing_list_data'], $_REQUEST['list_id'], DESCR_SL);
    }

    if ($mode === 'delete') {
        if (!empty($_REQUEST['list_id'])) {
            fn_newsletters_delete_mailing_lists((array) $_REQUEST['list_id']);

            list($_mailing_lists) = fn_get_mailing_lists(['only_available' => false], 0, DESCR_SL);
            if (empty($_mailing_lists)) {
                Tygh::$app['view']->display('addons/newsletters/views/mailing_lists/manage.tpl');
            }
        }
        exit;
    }

    if (
        $mode === 'm_delete'
        && !empty($_REQUEST['list_ids'])
    ) {
        fn_newsletters_delete_mailing_lists((array) $_REQUEST['list_ids']);
    }

    if (
        $mode === 'm_update_statuses'
        && !empty($_REQUEST['list_ids'])
        && !empty($_REQUEST['status'])
    ) {
        $status_to = $_REQUEST['status'];

        foreach ((array) $_REQUEST['list_ids'] as $list_id) {
            fn_tools_update_status(
                [
                    'table'             => 'mailing_lists',
                    'status'            => $status_to,
                    'id_name'           => 'list_id',
                    'id'                => $list_id,
                    'show_error_notice' => false,
                ]
            );
        }

        if (defined('AJAX_REQUEST')) {
            $redirect_url = fn_url('mailing_lists.manage');
            if (isset($_REQUEST['redirect_url'])) {
                $redirect_url = $_REQUEST['redirect_url'];
            }
            Tygh::$app['ajax']->assign('force_redirection', $redirect_url);
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            return [CONTROLLER_STATUS_NO_CONTENT];
        }
    }

    if (
        $mode === 'm_set_display'
        && !empty($_REQUEST['display_on'])
        && in_array($_REQUEST['display_on'], ['checkout', 'registration'], true)
        && !empty($_REQUEST['value'])
        && in_array($_REQUEST['value'], [YesNo::YES, YesNo::NO], true)
        && !empty($_REQUEST['list_ids'])
        && is_array($_REQUEST['list_ids'])
    ) {
        $field_name = 'show_on_' . $_REQUEST['display_on'];
        $value = YesNo::toBool($_REQUEST['value']) ? 1 : 0;

        foreach ($_REQUEST['list_ids'] as $list_id) {
            $mailing_list_data = [
                $field_name => $value,
            ];

            fn_update_mailing_list($mailing_list_data, $list_id);
        }

        if (defined('AJAX_REQUEST')) {
            $redirect_url = fn_url('mailing_lists.manage');
            if (isset($_REQUEST['redirect_url'])) {
                $redirect_url = $_REQUEST['redirect_url'];
            }
            Tygh::$app['ajax']->assign('force_redirection', $redirect_url);
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            return [CONTROLLER_STATUS_NO_CONTENT];
        }
    }

    return [CONTROLLER_STATUS_OK, 'mailing_lists.manage'];
}

if ($mode == 'update') {
    [$autoresponders] = fn_get_newsletters(['type' => NEWSLETTER_TYPE_AUTORESPONDER, 'only_available' => true], 0, DESCR_SL);
    Tygh::$app['view']->assign('autoresponders', $autoresponders);
    Tygh::$app['view']->assign('mailing_list', fn_get_mailing_list_data($_REQUEST['list_id'], DESCR_SL));

} elseif ($mode == 'manage') {
    $params = $_REQUEST;
    $params['only_available'] = false;

    list($mailing_lists) = fn_get_mailing_lists($params, 0, DESCR_SL);

    $subscribers = db_get_hash_array('SELECT * FROM ?:subscribers', 'subscriber_id');
    foreach ($mailing_lists as &$list) {
        $list['subscribers_num'] = db_get_field('SELECT COUNT(*) FROM ?:user_mailing_lists WHERE list_id = ?i', $list['list_id']);
    }

    [$autoresponders] = fn_get_newsletters(['type' => NEWSLETTER_TYPE_AUTORESPONDER, 'only_available' => true], 0, DESCR_SL);
    Tygh::$app['view']->assign([
        'mailing_lists'                 => $mailing_lists,
        'autoresponders'                => $autoresponders,
        'subscribers'                   => $subscribers,
        'is_allow_update_mailing_lists' => fn_check_permissions('mailing_lists', 'update', 'admin', Http::POST),
    ]);

    fn_newsletters_generate_sections('mailing_lists');
}

/** /Body **/
