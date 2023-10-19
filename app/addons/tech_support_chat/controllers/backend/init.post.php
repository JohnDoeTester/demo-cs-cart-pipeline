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

/** @var string $controller */
if ($controller == 'auth' || $controller == 'settings' && $mode == 'change_store_mode') {
    unset(Tygh::$app['session']['license_information'], Tygh::$app['session']['tech_support_chat_widget_id']);
}

if (isset(Tygh::$app['session']['license_information'])
    && !isset(Tygh::$app['session']['tech_support_chat_widget_id'])
) {
    $license = Tygh::$app['session']['license_information'];

    if (strpos($license, '<?xml') !== false) {
        $license = simplexml_load_string($license);

        Tygh::$app['session']['tech_support_chat_widget_id'] = false;
        if (isset($license->OnlineTechSupportWidgetId)) {
            Tygh::$app['session']['tech_support_chat_widget_id'] = (string) $license->OnlineTechSupportWidgetId;
        }
    }
}

return array(CONTROLLER_STATUS_OK);