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
use Tygh\Enum\YesNo;
use Tygh\Registry;
use Tygh\Tools\Url;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * @var string $mode
 * @var string $action
 */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    //
    // Create/Update user
    //
    if ($mode == 'update') {
        $is_update = !empty($auth['user_id']);

        if (!$is_update) {
            $is_valid_user_data = true;

            if (empty($_REQUEST['user_data']['email'])) {
                fn_set_notification('W', __('warning'), __('error_validator_required', array('[field]' => __('email'))));
                $is_valid_user_data = false;

            } elseif (!fn_validate_email($_REQUEST['user_data']['email'])) {
                fn_set_notification('W', __('error'), __('text_not_valid_email', array('[email]' => $_REQUEST['user_data']['email'])));
                $is_valid_user_data = false;
            }

            if (empty($_REQUEST['user_data']['password1']) || empty($_REQUEST['user_data']['password2'])) {

                if (empty($_REQUEST['user_data']['password1'])) {
                    fn_set_notification('W', __('warning'), __('error_validator_required', array('[field]' => __('password'))));
                }

                if (empty($_REQUEST['user_data']['password2'])) {
                    fn_set_notification('W', __('warning'), __('error_validator_required', array('[field]' => __('confirm_password'))));
                }
                $is_valid_user_data = false;

            } elseif ($_REQUEST['user_data']['password1'] !== $_REQUEST['user_data']['password2']) {
                fn_set_notification('W', __('warning'), __('error_validator_password', array('[field2]' => __('password'), '[field]' => __('confirm_password'))));
                $is_valid_user_data = false;
            }

            if (!$is_valid_user_data) {
                $redirect_params = array();

                if (isset($_REQUEST['return_url'])) {
                    $redirect_params['return_url'] = $_REQUEST['return_url'];
                }

                return array(CONTROLLER_STATUS_REDIRECT, Url::buildUrn(array('profiles', 'add', $action), $redirect_params));
            }
        }

        fn_restore_processed_user_password($_REQUEST['user_data'], $_POST['user_data']);

        $user_data = (array) $_REQUEST['user_data'];

        if (empty($auth['user_id']) && !empty(Tygh::$app['session']['cart']['user_data'])) {
            $user_data += array_filter((array) Tygh::$app['session']['cart']['user_data']);
        }

        $res = fn_update_user($auth['user_id'], $user_data, $auth, !empty($_REQUEST['ship_to_another']), true);

        if ($res) {
            list($user_id, $profile_id) = $res;

            if (
                !empty(Tygh::$app['session']['cart'])
                && !isset(Tygh::$app['session']['cart']['payment_id'])
            ) {
                Tygh::$app['session']['cart']['user_data'] = $_REQUEST['user_data'];
            }

            if (empty(Tygh::$app['session']['cart']['user_data']['profile_id'])) {
                Tygh::$app['session']['cart']['user_data']['profile_id'] = $profile_id;
            }

            // Delete anonymous authentication
            if ($cu_id = fn_get_session_data('cu_id') && !empty($auth['user_id'])) {
                fn_delete_session_data('cu_id');
            }

            Tygh::$app['session']->regenerateID();

        } else {
            fn_save_post_data('user_data');
            fn_delete_notification('changes_saved');
        }

        Registry::set('runtime.profile_updated', $res ? YesNo::YES : YesNo::NO);

        $redirect_params = array();

        if (!empty($user_id) && !$is_update) {
            fn_login_user($user_id);

            if (!empty($_REQUEST['return_url'])) {
                return array(CONTROLLER_STATUS_OK, $_REQUEST['return_url']);
            }

            $redirect_dispatch = array('profiles', 'success_add');
        } else {
            $redirect_dispatch = array('profiles', empty($user_id) ? 'add' : 'update', $action);

            if (Registry::get('settings.General.user_multiple_profiles') == 'Y' && isset($profile_id)) {
                $redirect_params['profile_id'] = $profile_id;
            }

            if (!empty($_REQUEST['return_url']) && $res) {
                return array(CONTROLLER_STATUS_OK, $_REQUEST['return_url']);
            }
        }

        $_REQUEST['return_url'] = Url::buildUrn($redirect_dispatch, $redirect_params);

        return array(CONTROLLER_STATUS_OK, $_REQUEST['return_url']);
    } elseif ($mode === 'anonymization_request') {
        $redirect_url = !empty($_REQUEST['redirect_url']) ? $_REQUEST['redirect_url'] : '';
        $allow_users_send_request = Registry::get('settings.General.show_delete_account_button');

        if (!YesNo::toBool($allow_users_send_request)) {
            return [CONTROLLER_STATUS_REDIRECT, fn_url($redirect_url)];
        }

        $result = false;

        /** @var array $auth */
        $user_id = $auth['user_id'];

        if ($user_id) {
            $comment = !empty($_REQUEST['anonymization_request']['comment']) ? $_REQUEST['anonymization_request']['comment'] : '';
            $user_info = fn_get_user_short_info($user_id);
            $result = fn_send_anonymization_request_email($user_info, $comment);
        }

        if ($result) {
            fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('user_action_request_success'));
        } else {
            $message = __(
                'user_action_request_fail',
                [
                    '[email]' => Registry::get('settings.Company.company_users_department')
                ]
            );
            fn_set_notification(NotificationSeverity::ERROR, __('error'), $message);
        }

        return [CONTROLLER_STATUS_REDIRECT, fn_url($redirect_url)];
    }
}

if ($mode == 'add') {

    if (!empty($auth['user_id'])) {
        return array(CONTROLLER_STATUS_REDIRECT, 'profiles.update');
    }

    fn_add_breadcrumb(__('registration'));

    $user_data = array();
    if (!empty(Tygh::$app['session']['cart']) && !empty(Tygh::$app['session']['cart']['user_data'])) {
        $user_data = Tygh::$app['session']['cart']['user_data'];
    }

    $restored_user_data = fn_restore_post_data('user_data');
    if ($restored_user_data) {
        $user_data = fn_array_merge($user_data, $restored_user_data);
    }

    Registry::set('navigation.tabs.general', array (
        'title' => __('general'),
        'js' => true
    ));

    $params = array();
    if (isset($_REQUEST['user_type'])) {
        $params['user_type'] = $_REQUEST['user_type'];
    }

    $profile_fields = fn_get_profile_fields('C', array(), CART_LANGUAGE, $params);

    Tygh::$app['view']->assign('profile_fields', $profile_fields);
    Tygh::$app['view']->assign('user_data', $user_data);
    Tygh::$app['view']->assign('ship_to_another', fn_check_shipping_billing($user_data, $profile_fields));
    Tygh::$app['view']->assign('countries', fn_get_simple_countries(true, CART_LANGUAGE));
    Tygh::$app['view']->assign('states', fn_get_all_states());

} elseif ($mode == 'update') {

    if (empty($auth['user_id'])) {
        return array(CONTROLLER_STATUS_REDIRECT, 'auth.login_form?return_url=' . urlencode(Registry::get('config.current_url')));
    }

    $profile_id = empty($_REQUEST['profile_id']) ? 0 : $_REQUEST['profile_id'];
    fn_add_breadcrumb(__('editing_profile'));

    if (!empty($_REQUEST['profile']) && $_REQUEST['profile'] == 'new') {
        $user_data = fn_get_user_info($auth['user_id'], false);
    } else {
        $user_data = fn_get_user_info($auth['user_id'], true, $profile_id);
    }

    if (empty($user_data)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    $restored_user_data = fn_restore_post_data('user_data');
    if ($restored_user_data) {
        $user_data = fn_array_merge($user_data, $restored_user_data);
    }

    Registry::set('navigation.tabs.general', array (
        'title' => __('general'),
        'js' => true
    ));

    $show_usergroups = true;
    if (Registry::get('settings.General.allow_usergroup_signup') != 'Y') {
        $show_usergroups = fn_user_has_active_usergroups($user_data);
    }

    if ($show_usergroups) {
        $usergroups = fn_get_usergroups(array('type' => 'C', 'status' => 'A'));
        if (!empty($usergroups)) {
            Registry::set('navigation.tabs.usergroups', array (
                'title' => __('usergroups'),
                'js' => true
            ));

            Tygh::$app['view']->assign('usergroups', $usergroups);
        }
    }

    $profile_fields = fn_get_profile_fields();

    Tygh::$app['view']->assign('profile_fields', $profile_fields);
    Tygh::$app['view']->assign('user_data', $user_data);
    Tygh::$app['view']->assign('ship_to_another', fn_check_shipping_billing($user_data, $profile_fields));
    Tygh::$app['view']->assign('countries', fn_get_simple_countries(true, CART_LANGUAGE));
    Tygh::$app['view']->assign('states', fn_get_all_states());
    if (Registry::get('settings.General.user_multiple_profiles') == 'Y') {
        Tygh::$app['view']->assign('user_profiles', fn_get_user_profiles($auth['user_id']));
    }

// Delete profile
} elseif ($mode == 'delete_profile') {

    fn_delete_user_profile($auth['user_id'], $_REQUEST['profile_id']);

    return array(CONTROLLER_STATUS_OK, 'profiles.update');

} elseif ($mode == 'usergroups') {
    if (empty($auth['user_id']) || empty($_REQUEST['type']) || empty($_REQUEST['usergroup_id'])) {
        return array(CONTROLLER_STATUS_DENIED);
    }

    if (fn_request_usergroup($auth['user_id'], $_REQUEST['usergroup_id'], $_REQUEST['type'])) {
        $user_data = fn_get_user_info($auth['user_id']);
        $usergroups = fn_get_usergroups(
            array('type' => 'C', 'status' => 'A'),
            Registry::get('settings.Appearance.backend_default_language')
        );

        $usergroup_id = $_REQUEST['usergroup_id'];

        /** @var \Tygh\Notifications\EventDispatcher $event_dispatcher */
        $event_dispatcher = Tygh::$app['event.dispatcher'];

        $event_dispatcher->dispatch('profile.usergroup_request', [
            'user_data' => $user_data,
            'usergroups' => $usergroups,
            'usergroup_id' => $usergroup_id
        ]);
    }

    return array(CONTROLLER_STATUS_OK, 'profiles.update');

} elseif ($mode == 'success_add') {

    if (empty($auth['user_id'])) {
        return array(CONTROLLER_STATUS_REDIRECT, 'profiles.add');
    }

    fn_add_breadcrumb(__('registration'));
}

/**
 * Requests usergroup for customer
 *
 * @param int $user_id User identifier
 * @param int $usergroup_id Usergroup identifier
 * @param string $type Type of request (join|cancel)
 * @return bool True if request successfuly sent, false otherwise
 */
function fn_request_usergroup($user_id, $usergroup_id, $type)
{
    $success = false;
    if (!empty($user_id)) {
        $_data = array(
            'user_id' => $user_id,
            'usergroup_id' => $usergroup_id,
        );

        if ($type == 'cancel') {
            $_data['status'] = 'F';

        } elseif ($type == 'join') {
            $_data['status'] = 'P';
            $success = true;
        }

        if (!empty($_data['status'])) {
            db_query("REPLACE INTO ?:usergroup_links SET ?u", $_data);
        }
    }

    return $success;
}
