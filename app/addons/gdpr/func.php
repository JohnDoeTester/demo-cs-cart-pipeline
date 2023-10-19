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

use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\SiteArea;
use Tygh\Registry;
use Tygh\Settings;
use Tygh\Addons\Gdpr\CookiesPolicyManager;
use Tygh\Addons\Gdpr\Service;

defined('BOOTSTRAP') or die('Access denied');

/**
 * Creates Klaro config file at installation moment.
 *
 * @return void
 */
function fn_gdpr_install()
{
    $klaro_config = fn_gdpr_get_klaro_config();
    fn_gdpr_save_js_config($klaro_config);
}

/**
 * Removes Klaro configuration after uninstallation of add-on.
 *
 * @return void
 */
function fn_gdpr_uninstall()
{
    $file_dir  = sprintf('%s/gdpr', Registry::get('config.dir.files'));
    $file_path = sprintf('%s/gdpr/klaro/config.js', Registry::get('config.dir.files'));
    if (file_exists($file_path)) {
        fn_rm($file_dir);
    }
}

/**
 * Updates add-on settings
 *
 * @param int   $setting_id Setting id
 * @param array $settings   Settings
 */
function fn_gdpr_update_settings($setting_id, array $settings)
{
    $setting_id = (int) $setting_id;

    if ($setting_id) {
        $settings = json_encode((array) $settings);
        Settings::instance()->updateValueById($setting_id, $settings);
    }
}

/**
 * Fetches settings data
 *
 * @param string $name     Setting name
 * @param string $section  Setting section
 * @param array  $settings Settings
 *
 * @return mixed
 */
function fn_gdpr_get_setting_data($name, $section, $settings)
{
    return current(
        array_filter(
            isset($settings[$section]) ? $settings[$section] : array(),
            function($setting) use ($name) {
                return isset($setting['name']) && $setting['name'] == $name;
            }
        )
    );
}

/**
 * Proxies saving user agreement request to a specialized service
 *
 * @param array  $params         Parameters
 * @param string $agreement_type Type of agreement
 *
 * @return mixed
 */
function fn_gdpr_save_user_agreement($params, $agreement_type)
{
    /** @var Tygh\Addons\Gdpr\Service $service Gdpr service */
    $service = Tygh::$app['addons.gdpr.service'];

    if ($service->isNeeded($agreement_type)) {
        return $service->saveAcceptedAgreement($params, $agreement_type);
    }

    return false;
}

/**
 * Hook handler for saving accepted placing order agreement to the log
 */
function fn_gdpr_checkout_place_orders_pre_route($cart, $auth, $params)
{
    if (AREA !== 'C') {
        return false;
    }

    $params = array(
        'user_id' => isset($auth['user_id']) ? (int) $auth['user_id'] : 0,
        'email' => isset($cart['user_data']['email']) ? $cart['user_data']['email'] : '',
    );

    fn_gdpr_save_user_agreement($params, 'checkout_place_order');
}

/**
 * Hook handler for saving accepted update user profile agreement to the log
 */
function fn_gdpr_update_profile($action, $user_data, $current_user_data)
{
    if (AREA !== 'C') {
        return false;
    }

    $params = array(
        'user_id' => isset($user_data['user_id']) ? (int) $user_data['user_id'] : 0,
        'email' => isset($user_data['email']) ? $user_data['email'] : '',
    );

    $agreement_type = !empty($user_data['agreement_type']) ? $user_data['agreement_type'] : 'profiles_update';

    fn_gdpr_save_user_agreement($params, $agreement_type);
}

/**
 * Hook handler for saving accepted apply for vendor agreement to the log
 */
function fn_gdpr_update_company($company_data, $company_id, $lang_code, $action)
{
    if (AREA !== 'C') {
        return false;
    }

    $params = array(
        'user_id' => Tygh::$app['session']['auth']['user_id'],
        'email' => isset($company_data['email']) ? $company_data['email'] : '',
    );

    fn_gdpr_save_user_agreement($params, 'apply_for_vendor');
}

/**
 * Hook handler for saving accepted product subscription agreement
 */
function fn_gdpr_update_product_notifications_post($data, $subscribed, $deleted)
{
    if (AREA !== 'C') {
        return false;
    }

    $params = array(
        'email' => isset($data['email']) ? $data['email'] : '',
        'user_id' => Tygh::$app['session']['user_id'],
    );

    fn_gdpr_save_user_agreement($params, 'product_subscription');
}

/**
 * Hook handler for fetching user anonymization status
 */
function fn_gdpr_get_users($params, &$fields, $sortings, $condition, &$join, $auth)
{
    if (AREA === 'C') {
        return;
    }

    $join .= db_quote(' LEFT JOIN ?:gdpr_user_data ON ?:gdpr_user_data.user_id = ?:users.user_id');
    $fields['anonymized'] = '?:gdpr_user_data.anonymized';
}

/**
 * Hook handler for saving user agreement (profile editing) on guest checkout
 */
function fn_gdpr_save_cart_content_post($cart, $user_id, $type, $user_type)
{
    if (AREA !== 'C' || empty($cart['guest_checkout'])) {
        return;
    }

    $is_edit_profile_step = !empty($cart['edit_step']) && $cart['edit_step'] === 'step_two';

    if ($is_edit_profile_step) {
        $params = array(
            'email' => isset($cart['user_data']['email']) ? $cart['user_data']['email'] : '',
            'user_id' => 0,
        );

        fn_gdpr_save_user_agreement($params, 'checkout_profiles_update');
    }
}

/**
 * Hook handler for clean up agreements saved into the session when user logs out
 */
function fn_gdpr_user_logout_after($auth)
{
    if (AREA !== 'C') {
        return;
    }

    unset(Tygh::$app['session']['gdpr']);
}

/**
 * Hook handler for cleaning up cart data after user anonymizing
 */
function fn_gdpr_user_init($auth, $user_info, $first_init)
{
    if (AREA !== 'C') {
        return;
    }

    $cart_user_mismatch = empty($auth['user_id'])
        && !empty(Tygh::$app['session']['cart']['user_data']['user_id']);

    if (!$cart_user_mismatch) {
        return;
    }

    $is_anonymized = db_get_field('SELECT user_id FROM ?:gdpr_user_data WHERE user_id = ?i AND anonymized = ?s',
        Tygh::$app['session']['cart']['user_data']['user_id'],
        'Y'
    );

    if ($is_anonymized) {
        fn_clear_cart(Tygh::$app['session']['cart'], true, true);
    }
}

/**
 * Hook handler for saving accepted cookie agreement after user has authorised
 */
function fn_gdpr_login_user_post($user_id, $cu_id, $udata, $auth, $condition, $result)
{
    if (AREA !== 'C') {
        return;
    }

    if ($user_id) {
        /** @var CookiesPolicyManager $cookies_policy_manager */
        $cookies_policy_manager = Tygh::$app['addons.gdpr.cookies_policy_manager'];

        if ($cookies_policy_manager->hasStoredUserAgreement()) {
            /** @var Service $service GDPR service */
            $service = Tygh::$app['addons.gdpr.service'];
            $has_saved_agreement = $service->hasUserAgreement(CookiesPolicyManager::AGREEMENT_TYPE_COOKIES, $auth, false);

            if (!$has_saved_agreement) {
                $cookies_policy_manager->saveAgreement($user_id);
            }
        }
    }
}

/**
 * The "smarty_function_script_after_formations" hook handler.
 *
 * Actions performed:
 *  - Apply additional attributes to the script.
 *
 * @param array  $scripts List of scripts
 * @param array  $params  Script parameters and attributes
 * @param string $src     Script path
 *
 * @see smarty_function_script()
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_gdpr_smarty_function_script_after_formation(array &$scripts, array $params, $src)
{
    if (
        Registry::get('addons.gdpr.gdpr_cookie_consent') === CookiesPolicyManager::COOKIE_POLICY_NONE
        && SiteArea::isStorefront(AREA)
    ) {
        return;
    }

    // phpcs:ignore
    if (isset($params['cookie-name'])) {
        $scripts[$params['src']] = '<script'
            . (!empty($params['async']) ? ' async ' : '')
            . (!empty($params['defer']) ? ' defer ' : '')
            . ' class="cm-ajax-skip-load' . (!empty($params['class']) ? ' ' . $params['class'] : '') . '"'
            . ' type="text/plain"'
            . ' data-type="application/javascript"'
            . ' data-name="' . $params['cookie-name'] . '"'
            . ' data-src="' . $src . '" '
            . ' src="' . $src . '" '
            . (isset($params['charset']) ? ('charset="' . $params['charset'] . '"') : '')
            . (isset($params['escape']) ? '><\/script>' : '></script>');
    }
}

/**
 * Gets page list for "Privacy policy page" setting
 *
 * @return array
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
 */
function fn_settings_variants_addons_gdpr_privacy_policy_page()
{
    $data = [];

    list($pages, ) = fn_get_pages();

    foreach ($pages as $page) {
        $data[$page['page_id']] = $page['page'];
    }

    return $data;
}

/**
 * The "get_addons_post" hook handler.
 *
 * Actions performed:
 *  - Updates Klaro config after get addons.
 *
 * @see fn_get_addons()
 *
 * @return void
 */
function fn_gdpr_get_addons_post()
{
    $klaro_config = fn_gdpr_get_klaro_config();
    fn_gdpr_save_js_config($klaro_config);
}


/**
 * Updates Klaro config after activating gdpr addon.
 *
 * @param string $status Add-on status
 *
 * @see fn_update_addon_status()
 *
 * @return void
 */
function fn_settings_actions_addons_post_gdpr(string $status)
{
    if ($status !== ObjectStatuses::ACTIVE) {
        return;
    }

    $klaro_config = fn_gdpr_get_klaro_config();
    fn_gdpr_save_js_config($klaro_config);
}

/**
 * The "update_payment_post" hook handler.
 *
 * Actions performed:
 *  - Updates Klaro config after update payment.
 *
 * @see fn_update_payment()
 *
 * @return void
 */
function fn_gdpr_update_payment_post()
{
    $klaro_config = fn_gdpr_get_klaro_config();
    fn_gdpr_save_js_config($klaro_config);
}

/**
 * Get Klaro config
 *
 * @return array<string, string|bool|array>
 */
function fn_gdpr_get_klaro_config()
{
    if (defined('INSTALLER_INITED') || defined('INSTALLER_STARTED') || !isset(Tygh::$app['addons.gdpr.cookies_policy_manager'])) {
        require_once Registry::get('config.dir.addons') . 'gdpr/Tygh/Addons/Gdpr/CookiesPolicyManager.php';

        $klaro_config = fn_get_schema('gdpr', 'klaro_config');
        $gdpr_schema = include Registry::get('config.dir.addons') . 'gdpr/schemas/gdpr/klaro_config.php';
        $klaro_config = array_merge($gdpr_schema, $klaro_config);

        $klaro_config['services'] = array_values($klaro_config['services']);

        $gdpr_cookie_consent = db_get_field('SELECT value FROM ?:settings_objects WHERE name = ?s ', 'gdpr_cookie_consent');
        if ($gdpr_cookie_consent === CookiesPolicyManager::COOKIE_POLICY_EXPLICIT) {
            $klaro_config['hideLearnMore'] = false;
            $klaro_config['optOut'] = false;
        }

        return $klaro_config;
    }

    /** @var Tygh\Addons\Gdpr\CookiesPolicyManager $cookies_policy_manager */
    $cookies_policy_manager = Tygh::$app['addons.gdpr.cookies_policy_manager'];

    return $cookies_policy_manager->getKlaroConfig();
}

/**
 * Save Klaro config in JS file.
 *
 * @param array<string, string|bool|array> $klaro_config Klaro config
 *
 * @return void
 */
function fn_gdpr_save_js_config(array $klaro_config)
{
    $file_dir = sprintf('%s/gdpr/klaro', Registry::get('config.dir.files'));
    fn_mkdir($file_dir);
    $file_path = sprintf('%s/%s', $file_dir, 'config.js');

    file_put_contents($file_path, 'var cookieConfig = ' . json_encode($klaro_config) . ';');
}
