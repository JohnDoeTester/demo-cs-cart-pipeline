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
use Tygh\Enum\Addons\Recaptcha\RecaptchaTypes;
use Tygh\Registry;
use Tygh\Settings;
use Tygh\Web\Antibot\NativeCaptchaDriver;

/**
 * Instantiates captcha driver according to settings and detected country
 *
 * @return \Tygh\Web\Antibot\NativeCaptchaDriver|\Tygh\Addons\Recaptcha\RecaptchaDriver
 */
function fn_recaptcha_get_captcha_driver()
{
    if (!isset(Tygh::$app['session']['recaptcha']['driver'])) {
        $ip = fn_get_ip(true);
        $forbidden_countries = (array) Registry::get('addons.recaptcha.forbidden_countries');
        $country = fn_get_country_by_ip($ip['host']);

        $is_google_blocked_in_country = array_key_exists($country, $forbidden_countries);
        if ($is_google_blocked_in_country && extension_loaded('gd')) {
            Tygh::$app['session']['recaptcha']['driver'] = 'native';
        } else {
            Tygh::$app['session']['recaptcha']['driver'] = 'recaptcha';
        }
    }

    if (Tygh::$app['session']['recaptcha']['driver'] === 'native') {
        return new NativeCaptchaDriver(Tygh::$app['session']);
    }

    return new RecaptchaDriver(
        Registry::get('addons.recaptcha'),
        Tygh::$app['session'],
        fn_recaptcha_get_use_for_settings()
    );
}

/**
 * @return string|null HTML code of Image verification settings inputs
 */
function fn_recaptcha_image_verification_settings_proxy()
{
    // For example, during the installation
    if (!isset(Tygh::$app['view'])) {
        return null;
    }

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];
    $settings = Settings::instance(
        [
            'company_id'    => (int) Registry::get('runtime.company_id'),
            'storefront_id' => (int) $view->getTemplateVars('selected_storefront_id'),
        ]
    );
    $proxied_section = $settings->getSectionByName('Image_verification');
    $proxied_setting_objects = $settings->getList($proxied_section['section_id'], 0);

    $output = '';
    foreach ($proxied_setting_objects as $subsection_name => $setting_objects) {
        foreach ($setting_objects as $setting_object) {
            if ($setting_object['name'] == 'use_for') {
                continue;
            }

            $view->assign('item', $setting_object);
            $view->assign('section', $proxied_section['section_id']);
            $view->assign('html_name', "addon_data[options][{$setting_object['object_id']}]");
            $view->assign('class', 'setting-wide');
            $view->assign('html_id', "addon_option_recaptcha_{$setting_object['name']}");

            $output .= $view->fetch('common/settings_fields.tpl');
        }
    }

    return $output;
}

/**
 * Gets an array of scenario and recaptcha type.
 *
 * @param int|null $storefront_id Storefront ID to get settings for.
 *
 * @return array<string, string>
 */
function fn_recaptcha_get_use_for_settings($storefront_id = null)
{
    $addon_use_for_value = Registry::get('addons.recaptcha.recaptcha_use_for_value');
    $addon_use_for_value = unserialize($addon_use_for_value);
    $core_use_for_setting = Registry::get('settings.Image_verification.use_for');

    if ($storefront_id !== null) {
        $settings_manager = Settings::instance(['storefront_id' => $storefront_id]);
        
        $addon_use_for_value = $settings_manager->getValue('recaptcha_use_for_value', 'recaptcha');
        $addon_use_for_value = unserialize($addon_use_for_value);
        $core_use_for_setting = $settings_manager->getValue('use_for', 'Image_verification');
    }

    $settings = [];

    foreach (array_keys($core_use_for_setting) as $key) {
        if (isset($addon_use_for_value[$key])) {
            $settings[$key] = $addon_use_for_value[$key];
        } else {
            $settings[$key] = RecaptchaTypes::RECAPTCHA_TYPE_V2;
        }
    }

    return $settings;
}

/**
 * Gets recaptcha version by the scenario, or null
 *
 * @param string $scenario Scenario
 *
 * @return string|null
 */
function fn_recaptcha_get_recaptcha_type_by_scenario($scenario)
{
    $settings = fn_recaptcha_get_use_for_settings();
    return isset($settings[$scenario])
        ? $settings[$scenario]
        : null;
}
