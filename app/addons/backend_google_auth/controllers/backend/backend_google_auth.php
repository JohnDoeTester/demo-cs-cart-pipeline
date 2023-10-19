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

use Tygh\Helpdesk;

/**
 * @var string $mode
 * @var string $action
 */

if (ACCOUNT_TYPE !== 'admin') {
    return array(CONTROLLER_STATUS_OK);
}

if ($mode === 'check') {
    if (!fn_backend_google_auth_is_configured()) {
        fn_set_notification('E', __('error'), __('backend_google_auth.errors.not_configured'));

        return array(CONTROLLER_STATUS_REDIRECT, 'addons.manage');
    }

    fn_set_session_data('backend_google_auth.is_check', true);
    fn_backend_google_auth_hybrid_auth_authenticate('addons.manage');
    exit();
} elseif ($mode === 'callback') {
    try {
        Hybrid_Endpoint::process();
    } catch (Exception $exception) {
        fn_delete_session_data('backend_google_auth.is_check');
        fn_set_notification('E', __('error'), $exception->getMessage());
    }

    return array(CONTROLLER_STATUS_REDIRECT, 'auth.login_form');
} elseif ($mode === 'done') {
    $return_url = isset($_REQUEST['return_url']) ? $_REQUEST['return_url'] : '';

    try {
        $hybrid_auth = fn_backend_google_auth_create_hybrid_auth_instance();

        if ($hybrid_auth->isConnectedWith(BACKEND_GOOGLE_AUTH_PROVIDER)) {
            $adapter = $hybrid_auth->getAdapter(BACKEND_GOOGLE_AUTH_PROVIDER);

            $profile = $adapter->getUserProfile();

            if ($profile && !empty($profile->email)) {
                $email = trim($profile->email);

                $user_id = fn_backend_google_auth_find_active_user_by_email($email);

                if ($user_id && fn_login_user($user_id, true) === LOGIN_STATUS_OK) {
                    Helpdesk::auth();
                    fn_log_event('users', 'session', array(
                        'user_id' => $user_id
                    ));

                    if (fn_get_session_data('backend_google_auth.is_check')) {
                        fn_delete_session_data('backend_google_auth.is_check');
                        fn_set_notification('N', __('notice'), __('successful'));
                    }
                    return array(CONTROLLER_STATUS_REDIRECT, $return_url);
                } else {
                    fn_set_notification('E', __('error'), __('backend_google_auth.user_not_found', array('[user]' => $email)));
                }
            }
        }
    } catch (Exception $exception) {
        fn_set_notification('E', __('error'), $exception->getMessage());
    }

    return array(CONTROLLER_STATUS_REDIRECT, 'auth.login_form');
}