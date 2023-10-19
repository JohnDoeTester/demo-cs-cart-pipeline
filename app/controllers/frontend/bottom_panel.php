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

use Tygh\Enum\SiteArea;
use Tygh\Tools\Url;
use Tygh\Enum\UserTypes;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

/**
 * @var string $mode
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'login_as_vendor' || $mode === 'login_as_admin') {
        if ($mode === 'login_as_vendor') {
            $area = SiteArea::VENDOR_PANEL;
            $user_type = UserTypes::VENDOR;
        } else {
            $area = SiteArea::ADMIN_PANEL;
            $user_type = UserTypes::ADMIN;
        }

        if (
            (!empty($_REQUEST['area']) && $_REQUEST['area'] !== SiteArea::STOREFRONT)
            || !Tygh::$app['session']['auth']['user_type']
            || Tygh::$app['session']['auth']['user_type'] !== $user_type
            || empty(Tygh::$app['session']['auth']['user_id'])
        ) {
            $redirect_url = Url::buildUrn('bottom_panel.redirect', [
                'url'     => !empty($_REQUEST['url']) ? $_REQUEST['url'] : '',
                'user_id' => !empty($_REQUEST['user_id']) ? $_REQUEST['user_id'] : '',
                'area'    => !empty($_REQUEST['area']) ? $_REQUEST['area'] : ''
            ]);

            return [CONTROLLER_STATUS_REDIRECT, fn_url($redirect_url, $area)];
        }

        $user_id = Tygh::$app['session']['auth']['user_id'];
        $email = fn_get_user_email($user_id);

        if (empty($email)) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        $ekey = fn_generate_ekey($user_id, 'U', SECONDS_IN_DAY);

        $redirect_url = Url::buildUrn('bottom_panel.redirect', [
            'url'     => !empty($_REQUEST['url']) ? $_REQUEST['url'] : '',
            'user_id' => !empty($_REQUEST['user_id']) ? $_REQUEST['user_id'] : '',
            'ekey'    => $ekey,
            'area'    => AREA
        ]);

        return [CONTROLLER_STATUS_REDIRECT, fn_url($redirect_url, $area), true];
    }
}