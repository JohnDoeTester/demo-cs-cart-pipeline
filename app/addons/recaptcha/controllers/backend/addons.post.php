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

use Tygh\Providers\StorefrontProvider;
use Tygh\Settings;
use Tygh\Enum\Addons\Recaptcha\RecaptchaTypes;

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        $mode === 'update'
        && isset($_REQUEST['addon'], $_REQUEST['recaptcha_use_for'])
        && $_REQUEST['addon'] === 'recaptcha'
    ) {
        $recaptcha_use_for_settings = $_REQUEST['recaptcha_use_for'];

        $new_value_for_core = $new_value_for_addon = [];

        $use_for_settings_variants = fn_settings_variants_image_verification_use_for();
        foreach (array_keys($use_for_settings_variants) as $variant) {
            if (!empty($recaptcha_use_for_settings[$variant])) {
                $new_value_for_core[] = $variant;
                if (RecaptchaTypes::isRecapthcaType($recaptcha_use_for_settings[$variant])) {
                    $new_value_for_addon[$variant] = $recaptcha_use_for_settings[$variant];
                }
            } else {
                $new_value_for_addon[$variant] = '';
            }
        }

        $storefront_id = empty($_REQUEST['storefront_id'])
            ? 0
            : (int) $_REQUEST['storefront_id'];

        if (fn_allowed_for('ULTIMATE')) {
            $storefront_id = 0;
            if (fn_get_runtime_company_id()) {
                $storefront_id = StorefrontProvider::getStorefront()->storefront_id;
            }
        }

        $settings_manager = Settings::instance(['storefront_id' => $storefront_id]);

        $settings_manager->updateValue('use_for', $new_value_for_core, 'Image_verification');
        $core_setting_id = (int) $settings_manager->getId('use_for', 'Image_verification');

        $settings_manager->updateValue('recaptcha_use_for_value', serialize($new_value_for_addon));
        $addon_settings_id = (int) $settings_manager->getId('recaptcha_use_for_value', 'recaptcha');

        if (
            $core_setting_id
            && $addon_settings_id
            && !empty($_REQUEST['update_all_vendors']['use_for'])
        ) {
            $settings_manager->resetAllOverrides($core_setting_id);
            $settings_manager->resetAllOverrides($addon_settings_id);
        }
    }

    return [CONTROLLER_STATUS_OK];
}
