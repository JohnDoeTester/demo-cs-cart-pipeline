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

use Tygh\Menu;
use Tygh\Registry;
use Tygh\Enum\NotificationSeverity;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    //
    // Update menu
    //
    if (($mode == 'update') || ($mode == 'add')) {
        if (!empty($_REQUEST['menu_data'])) {
            $_REQUEST['menu_data']['lang_code'] = DESCR_SL;
            Menu::update($_REQUEST['menu_data']);
        }
    }

    //
    // Delete menu
    //
    if ($mode == 'delete') {
        if (!empty($_REQUEST['menu_id'])) {
            Menu::delete($_REQUEST['menu_id']);
        }
    }

    if ($mode === 'm_delete') {
        $menu_ids = empty($_REQUEST['menu_ids']) ? [] : $_REQUEST['menu_ids'];

        if (!empty($menu_ids)) {
            foreach ($menu_ids as $menu_id) {
                Menu::delete($menu_id);
            }
        }

        unset(Tygh::$app['session']['menu_ids']);
        fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('text_menus_have_been_deleted'));
    }

    if (
        $mode === 'm_update_statuses'
        && !empty($_REQUEST['menu_ids'])
        && is_array($_REQUEST['menu_ids'])
        && !empty($_REQUEST['status'])
    ) {
        $status_to = $_REQUEST['status'];
        $menu_ids  = $_REQUEST['menu_ids'];

        foreach ($menu_ids as $menu_id) {
            fn_tools_update_status(
                [
                    'table'             => 'menus',
                    'status'            => $status_to,
                    'id_name'           => 'menu_id',
                    'id'                => $menu_id,
                    'show_error_notice' => false,
                ]
            );
        }

        if (defined('AJAX_REQUEST')) {
            $redirect_url = fn_url('menus.manage');
            if (isset($_REQUEST['redirect_url'])) {
                $redirect_url = $_REQUEST['redirect_url'];
            }
            Tygh::$app['ajax']->assign('force_redirection', $redirect_url);
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            return [CONTROLLER_STATUS_NO_CONTENT];
        }
    }

    return array(CONTROLLER_STATUS_OK, 'menus.manage');
}

// ---------------------- GET routines ---------------------------------------

if ($mode == 'manage') {

    $menus = Menu::getList('', DESCR_SL);

    Tygh::$app['view']->assign('menus', $menus);

} elseif ($mode == 'update') {
    $menu_id = isset($_REQUEST['menu_data']['menu_id']) ? $_REQUEST['menu_data']['menu_id'] : 0;

    if (!empty($_REQUEST['menu_data'])) {
        $menu_data = $_REQUEST['menu_data'];
    } else {
        $menu_data = array();
    }

    // If edit block
    if ($menu_id > 0 && empty($_REQUEST['menu_data']['content'])) {
        $menu_data = current(Menu::getList(db_quote(' AND ?:menus.menu_id=?i', $menu_id), DESCR_SL));
    }

    Tygh::$app['view']->assign('menu_data', $menu_data);
}
