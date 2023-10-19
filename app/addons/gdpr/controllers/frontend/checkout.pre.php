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

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode == 'update_steps') {

        if (empty($auth['user_id'])
            && isset($_REQUEST['gdpr_agreements']['checkout_profiles_update'])
            && $_REQUEST['gdpr_agreements']['checkout_profiles_update'] == 'Y'
        ) {
            $params = array(
                'email' => isset($_REQUEST['user_data']['email']) ? $_REQUEST['user_data']['email'] : '',
            );

            fn_gdpr_save_user_agreement($params, 'checkout_profiles_update');
        }
        return [CONTROLLER_STATUS_OK];
    }

    if ($mode == 'update_profile' || $mode == 'place_order') {

        if (!empty($auth['user_id'])
            && isset($_REQUEST['gdpr_agreements']['checkout_profiles_update'])
            && $_REQUEST['gdpr_agreements']['checkout_profiles_update'] == \Tygh\Enum\YesNo::YES
        ) {
            $params = [
                'email' => isset(Tygh::$app['session']['cart']['user_data']['email']) ? Tygh::$app['session']['cart']['user_data']['email'] : '',
                'user_id' => $auth['user_id'],
            ];

            fn_gdpr_save_user_agreement($params, 'checkout_profiles_update');
        }
        return [CONTROLLER_STATUS_OK];
    }
}

