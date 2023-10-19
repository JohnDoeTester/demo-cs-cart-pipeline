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

use Tygh\Enum\YesNo;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

if (isset($schema['checkout']['customer_info'])) {
    $schema['checkout']['customer_info']['condition'] = function ($request_data) {
        $dispatch_action = Registry::get('runtime.action');

        return !YesNo::toBool(Registry::get('settings.Checkout.disable_anonymous_checkout'))
            && empty(Tygh::$app['session']['cart']['user_data']['email'])
            && $dispatch_action !== 'instant_payment';
    };
}

return $schema;
