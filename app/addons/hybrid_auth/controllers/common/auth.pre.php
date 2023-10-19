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

use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\SiteArea;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode === 'login_provider' || $mode === 'link_provider') {
    $status = fn_hybrid_auth_process($mode, $redirect_url);

    if ($status === HYBRID_AUTH_LOADING) {
        Tygh::$app['view']->display('addons/hybrid_auth/views/auth/loading.tpl');
    } else {
        unset(Tygh::$app['session']['cart']['edit_step']);
        Tygh::$app['view']->assign('redirect_url', $redirect_url);
        Tygh::$app['view']->display('addons/hybrid_auth/views/auth/login_error.tpl');
    }

    exit;
} elseif ($mode === 'processlive') { // workaround for Microsoft Redirect URI limitations (see \Hybrid_Provider_Adapter::login line ~ 168)
    $lib_path = Registry::get('config.dir.addons') . 'hybrid_auth/lib/';
    $request = $_REQUEST;
    $request['hauth_done'] = 'Live';

    try {
        Hybrid_Endpoint::process($request);
    } catch (Exception $e) {
        fn_set_notification(NotificationSeverity::ERROR, __('error'), $e->getMessage());
        Tygh::$app['view']->display('addons/hybrid_auth/views/auth/login_error.tpl');

        exit;
    }
} elseif ($mode === 'process') {
    $lib_path = Registry::get('config.dir.addons') . 'hybrid_auth/lib/';

    try {
        Hybrid_Endpoint::process();
    } catch (Exception $e) {
        fn_set_notification(NotificationSeverity::ERROR, __('error'), $e->getMessage());
        Tygh::$app['view']->display('addons/hybrid_auth/views/auth/login_error.tpl');

        exit;
    }
} elseif ($mode === 'logout') {
    // Remove Hybrid auth data
    unset(Tygh::$app['session']['HA::CONFIG'], Tygh::$app['session']['HA::STORE']);

} elseif ($mode === 'connect_social') {
    $email = !empty(Tygh::$app['session']['hybrid_auth']['email']) ? Tygh::$app['session']['hybrid_auth']['email'] : '';
    $identifier = !empty(Tygh::$app['session']['hybrid_auth']['identifier']) ? Tygh::$app['session']['hybrid_auth']['identifier'] : '';
    $provider_id = !empty(Tygh::$app['session']['hybrid_auth']['provider_id']) ? Tygh::$app['session']['hybrid_auth']['provider_id'] : 0;
    $redirect_url = !empty(Tygh::$app['session']['hybrid_auth']['redirect_url']) ? Tygh::$app['session']['hybrid_auth']['redirect_url'] : fn_url();

    if (!empty(Tygh::$app['session']['auth']['user_id'])) {

        fn_hybrid_auth_link_provider(Tygh::$app['session']['auth']['user_id'], $identifier, $provider_id);
        unset(Tygh::$app['session']['hybrid_auth']);

        return [CONTROLLER_STATUS_REDIRECT, $redirect_url];
    }

    if (AREA !== SiteArea::ADMIN_PANEL) {
        fn_add_breadcrumb(__('hybrid_auth.connect_social'));
    }

    $user_id = fn_is_user_exists(0, ['email' => $email]);

    if (!empty($user_id)) {
        $user_data = fn_get_user_short_info($user_id);

        $user_login = $user_data['email'];
    } else {
        $user_login = '';
    }

    Tygh::$app['view']->assign('user_login', $user_login);
    Tygh::$app['view']->assign('identifier', $identifier);
    Tygh::$app['view']->assign('view_mode', 'simple');

} elseif ($mode === 'specify_email') {
    if (!empty($_REQUEST['user_email'])) {
        fn_hybrid_auth_process('login_provider', $redirect_url);
        $_REQUEST['redirect_url'] = $redirect_url;

        return [CONTROLLER_STATUS_REDIRECT, fn_url($redirect_url)];
    }

    $identifier = !empty(Tygh::$app['session']['hybrid_auth']['identifier']) ? Tygh::$app['session']['hybrid_auth']['identifier'] : '';
    $provider_id = !empty(Tygh::$app['session']['hybrid_auth']['provider_id']) ? Tygh::$app['session']['hybrid_auth']['provider_id'] : 0;
    $redirect_url = !empty(Tygh::$app['session']['hybrid_auth']['redirect_url']) ? Tygh::$app['session']['hybrid_auth']['redirect_url'] : fn_url();

    if (AREA !== SiteArea::ADMIN_PANEL) {
        fn_add_breadcrumb(__('hybrid_auth.specify_email'));
    }

    Tygh::$app['view']->assign('identifier', $identifier);
    Tygh::$app['view']->assign('provider_id', $provider_id);
    Tygh::$app['view']->assign('redirect_url', $redirect_url);
    Tygh::$app['view']->assign('view_mode', 'simple');

}
