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


/**
 * @var string $mode
 * @var string $action
 */

if (ACCOUNT_TYPE !== 'admin' || !fn_backend_google_auth_is_configured()) {
    return array(CONTROLLER_STATUS_OK);
}

if (in_array($mode, array('recover_password', 'password_change', 'change_login', 'ekey_login'), true)) {
    return array(CONTROLLER_STATUS_REDIRECT, 'auth.login_form');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'login') {
        $return_url = isset($_REQUEST['return_url']) ? $_REQUEST['return_url'] : '';

        fn_backend_google_auth_hybrid_auth_authenticate($return_url);
        exit();
    }
}

return array(CONTROLLER_STATUS_OK);