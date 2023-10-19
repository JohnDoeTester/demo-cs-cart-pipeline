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
/** @var string $action */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode == 'update' && $action == 'get_auth_token') {

        if ($user_id = Tygh::$app['session']['auth']['user_id']) {

            list($token) = fn_get_user_auth_token($user_id);

            if (!empty($_REQUEST['return_url'])) {
                $_REQUEST['return_url'] = fn_link_attach($_REQUEST['return_url'], 'token=' . $token);

                return array(CONTROLLER_STATUS_OK, $_REQUEST['return_url']);
            }
        }
    }

    return array(CONTROLLER_STATUS_OK);
}