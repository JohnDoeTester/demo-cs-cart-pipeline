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

/**
 * @var $auth array User authorization data
 */

use Tygh\Registry;
use Tygh\Addons\Gdpr\Service;

defined('BOOTSTRAP') or die('Access denied');

/** @var Service $service Gdpr service */
$service = Tygh::$app['addons.gdpr.service'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode == 'user_action_request') {
        $result = false;

        if (!empty($_REQUEST['gdpr']['action']) && $auth['user_id']) {
            $comment = !empty($_REQUEST['gdpr']['comment']) ? $_REQUEST['gdpr']['comment'] : '';
            $action = $_REQUEST['gdpr']['action'];

            if ($action === Service::USER_ACTION_ANONYMIZATION_REQUEST) {
                $result = $service->notifyStaffOfAnonymizationRequest($auth['user_id'], $comment);
            } elseif ($action === Service::USER_ACTION_DATA_REQUEST) {
                $result = $service->notifyStaffOfDataRequest($auth['user_id'], $comment);
            }
        }

        if ($result) {
            fn_set_notification('N', __('notice'), __('gdpr.user_action_request_success'));
        } else {
            $message = __(
                'gdpr.user_action_request_fail',
                array('[email]' => Registry::get('settings.Company.company_users_department'))
            );
            fn_set_notification('E', __('error'), $message);
        }

        $redirect_url = !empty($_REQUEST['redirect_url']) ? $_REQUEST['redirect_url'] : '';

        return array(CONTROLLER_STATUS_REDIRECT, fn_url($redirect_url));
    }

    return array(CONTROLLER_STATUS_OK);
}
