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

use Tygh\Enum\Addons\VendorCommunication\CommunicationTypes;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\UserTypes;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode === 'redirect') {
    return [CONTROLLER_STATUS_REDIRECT, $_REQUEST['redirect_url'], true];
}

if (empty($auth['user_id'])) {
    return array(CONTROLLER_STATUS_REDIRECT, 'auth.login_form?return_url=' . urlencode(Registry::get('config.current_url')));
} else {
    /** @var Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'post_message') {
        $thread_id = !empty($_REQUEST['message']['thread_id']) ? (int) $_REQUEST['message']['thread_id'] : null;

        if ($thread_id
            && fn_vendor_communication_can_user_access_thread($thread_id, $auth)
        ) {
            fn_trusted_vars('message');

            if (!empty($_REQUEST['message']['message'])) {
                $message = array(
                    'user_id'   => $auth['user_id'],
                    'user_type' => $auth['user_type'],
                    'message'   => $_REQUEST['message']['message'],
                    'thread_id' => $thread_id,
                );

                $result = fn_vendor_communication_add_thread_message($message, true);

                if (!$result->isSuccess()) {
                    fn_set_notification(NotificationSeverity::ERROR, __('error'), __('vendor_communication.cannot_post_message'));
                }
            }

            return [CONTROLLER_STATUS_REDIRECT, 'vendor_communication.view&thread_id=' . $thread_id . '&communication_type=' . $_REQUEST['communication_type']];
        }
        return [CONTROLLER_STATUS_NO_PAGE];
    }
    return;
}
