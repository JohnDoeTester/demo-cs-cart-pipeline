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

/** @var string $mode */

if ($mode == 'token_login' && !empty($_REQUEST['token']) && AREA == 'C') {

    $ekey = $_REQUEST['token'];
    $redirect_url = !empty($_REQUEST['redirect_url']) ? $_REQUEST['redirect_url'] : '';

    $token = fn_get_ekeys(array('ekey' => $ekey, 'object_type' => 'U', 'ttl' => TIME));

    if ($token) {
        $token = reset($token);

        $result = fn_login_user($token['object_id'], true);

        if (!is_null($result)) {
            if ($result === LOGIN_STATUS_USER_NOT_FOUND || $result === LOGIN_STATUS_USER_DISABLED || $result === false) {
                $redirect_url = '';
            } else {
                fn_delete_notification('notice_text_change_password');
            }
        }
    } else {
        fn_set_notification('E', __('error'), __('error_incorrect_login'));
    }

    return array(CONTROLLER_STATUS_REDIRECT, fn_url($redirect_url));
}
