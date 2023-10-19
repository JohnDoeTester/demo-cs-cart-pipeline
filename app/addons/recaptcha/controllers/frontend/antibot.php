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

use Tygh\Addons\Recaptcha\RecaptchaDriver;
use Tygh\Registry;
use Tygh\Enum\YesNo;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($mode == 'valid_recaptcha') {
        if (!isset($_REQUEST[RecaptchaDriver::RECAPTCHA_V3_TOKEN_PARAM_NAME])) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        /** @var \Tygh\Web\Antibot $antibot */
        $antibot = Tygh::$app['antibot'];

        $driver = $antibot->getDriver();

        if ($driver instanceof RecaptchaDriver) {
            $driver->validateHttpRequest($_REQUEST);
            return [CONTROLLER_STATUS_OK];
        } else {
            return [CONTROLLER_STATUS_NO_PAGE];
        }
    }

    return;
}

