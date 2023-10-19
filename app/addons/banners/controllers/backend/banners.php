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

if ($_SERVER['REQUEST_METHOD']	== 'POST') {

    fn_trusted_vars('banners', 'banner_data');
    $suffix = '';

    //
    // Delete banners
    //
    if ($mode == 'm_delete') {
        foreach ($_REQUEST['banner_ids'] as $v) {
            fn_delete_banner_by_id($v);
        }

        $suffix = '.manage';
    }

    if (
        $mode === 'm_update_statuses'
        && !empty($_REQUEST['banner_ids'])
        && is_array($_REQUEST['banner_ids'])
        && !empty($_REQUEST['status'])
    ) {
        $status_to = (string) $_REQUEST['status'];

        foreach ($_REQUEST['banner_ids'] as $banner_id) {
            if (!fn_check_company_id('banners', 'banner_id', $banner_id)) {
                continue;
            }
            fn_tools_update_status([
                'table'             => 'banners',
                'status'            => $status_to,
                'id_name'           => 'banner_id',
                'id'                => $banner_id,
                'show_error_notice' => false
            ]);
        }

        if (defined('AJAX_REQUEST')) {
            $redirect_url = fn_url('banners.manage');
            if (isset($_REQUEST['redirect_url'])) {
                $redirect_url = $_REQUEST['redirect_url'];
            }
            Tygh::$app['ajax']->assign('force_redirection', $redirect_url);
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            return [CONTROLLER_STATUS_NO_CONTENT];
        }
    }

    //
    // Add/edit banners
    //
    if ($mode == 'update') {
        $banner_id = fn_banners_update_banner($_REQUEST['banner_data'], $_REQUEST['banner_id'], DESCR_SL);

        $suffix = ".update?banner_id=$banner_id";
    }

    if ($mode == 'delete') {
        if (!empty($_REQUEST['banner_id'])) {
            fn_delete_banner_by_id($_REQUEST['banner_id']);
        }

        $suffix = '.manage';
    }

    return array(CONTROLLER_STATUS_OK, 'banners' . $suffix);
}

if ($mode == 'update') {
    $banner = fn_get_banner_data($_REQUEST['banner_id'], DESCR_SL);

    if (empty($banner)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    Registry::set('navigation.tabs', array (
        'general' => array (
            'title' => __('general'),
            'js' => true
        ),
    ));

    Tygh::$app['view']->assign('banner', $banner);

} elseif ($mode == 'manage' || $mode == 'picker') {

    list($banners, $params) = fn_get_banners($_REQUEST, DESCR_SL, Registry::get('settings.Appearance.admin_elements_per_page'));

    Tygh::$app['view']->assign(array(
        'banners'  => $banners,
        'search' => $params,
    ));
}

//
// Banners picker
//
if ($mode == 'picker') {
    Tygh::$app['view']->display('addons/banners/pickers/banners/picker_contents.tpl');
    exit;
}
