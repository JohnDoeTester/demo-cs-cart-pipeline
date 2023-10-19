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

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use Tygh\Common\OperationResult;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ProductTracking;
use Tygh\Enum\YesNo;
use Tygh\Helpdesk;
use Tygh\Http;
use Tygh\Mailer\Mailer;
use Tygh\Mailer\Transports\PhpMailerTransport;
use Tygh\Providers\HelpdeskProvider;
use Tygh\Registry;
use Tygh\Settings;

/**
 * Check if secure connection is available
 */
function fn_settings_actions_security_secure_storefront(&$new_value, $old_value)
{
    if ($new_value !== YesNo::NO) {
        $company_id = fn_get_runtime_company_id();

        if (!fn_allowed_for('ULTIMATE') || (fn_allowed_for('ULTIMATE') && $company_id)) {

            $suffix = '';
            if (fn_allowed_for('ULTIMATE')) {
                $suffix = '&company_id=' . $company_id;
            }

            $storefront_url = fn_url('index.index?check_https=Y' . $suffix, 'C', 'https');

            $content = Http::get($storefront_url);
            if (empty($content) || $content != 'OK') {
                // Disable https
                Settings::instance()->updateValue('secure_storefront', YesNo::NO, 'Security');
                $new_value = YesNo::NO;

                $error = Http::getErrorFields();
                $error_warning = __('warning_https_is_disabled', array(
                    '[href]' => Registry::get('config.resources.kb_https_failed_url'
                )));

                $error_warning .= fn_settings_actions_build_detailed_error_message($error);
                fn_set_notification('W', __('warning'), $error_warning);
            }
        }
    }
}

/**
 * Check if secure connection is available
 */
function fn_settings_actions_security_secure_admin(&$new_value, $old_value)
{
    if ($new_value !== YesNo::NO) {
        $suffix = '';
        if (fn_allowed_for('ULTIMATE')) {
            $suffix = '&company_id=' . Registry::get('runtime.company_id');
        }

        $admin_url = fn_url('index.index?check_https=Y' . $suffix, 'A', 'https');

        $content = Http::get($admin_url);

        if (empty($content) || $content != 'OK') {
            // Disable https
            Settings::instance()->updateValue('secure_admin', 'N', 'Security');
            $new_value = 'N';

            $error = Http::getErrorFields();
            $error_warning = __('warning_https_is_disabled', array(
                    '[href]' => Registry::get('config.resources.kb_https_failed_url'
                    )));

            $error_warning .= fn_settings_actions_build_detailed_error_message($error);
            fn_set_notification('W', __('warning'), $error_warning);
        }
    }
}

/**
 * Sets the correct value for the password validity period setting
 *
 * @param string|int $new_value New setting value
 *
 * @param-out int $new_value
 *
 * @return void
 */
function fn_settings_actions_security_account_password_expiration_period(&$new_value)
{
    if (!is_numeric($new_value)) {
        $new_value = 0;
    }
    $new_value = abs((int) $new_value);
}

/**
 * Alter order initial ID
 */
function fn_settings_actions_checkout_order_start_id(&$new_value, $old_value)
{
    $new_value = intval($new_value);
    if ($new_value > 0) {

        if ($new_value <= MAX_INITIAL_ORDER_ID) {
            db_query("ALTER TABLE ?:orders AUTO_INCREMENT = ?i", $new_value);

            return true;
        }
    }

    $new_value = $old_value;
    fn_set_notification('W', __('warning'), __('wrong_number_initial_order_id', array('[max_initial_order_id]' => MAX_INITIAL_ORDER_ID)));

    return false;
}

/**
 * Save empty value if has no checked check boxes
 */
function fn_settings_actions_general_search_objects(&$new_value, $old_value)
{
    if ($new_value == 'N') {
        $new_value = '';
    }
}

function fn_settings_actions_upgrade_center_license_number(&$new_value, &$old_value)
{
    if (empty($new_value)) {
        $new_value = $old_value;
        fn_set_notification(NotificationSeverity::ERROR, __('error'), __('license_number_cannot_be_empty'));

        return;
    }

    $old_mode = fn_get_storage_data('store_mode');

    list($license_status, , $new_mode) = Helpdesk::getStoreMode($new_value, Tygh::$app['session']['auth']);
    if ($license_status === 'ACTIVE' && $old_mode !== $new_mode) {
        fn_set_storage_data('store_mode', $new_mode, true);
        Tygh::$app['session']['mode_recheck'] = true;
    } elseif ($license_status !== 'ACTIVE') {
        $new_value = $old_value;
    }

    if ($license_status !== 'ACTIVE') {
        return;
    }

    call_user_func(HelpdeskProvider::getAccountConnectionReporter());
}

function fn_settings_actions_appearance_backend_default_language(&$new_value, &$old_value)
{
    if (fn_allowed_for('ULTIMATE')) {
        db_query("UPDATE ?:companies SET lang_code = ?s", $new_value);
    }
}

if (fn_allowed_for('ULTIMATE')) {
    function fn_settings_actions_stores_share_users(&$new_value, $old_value)
    {
        $emails = fn_get_double_user_emails();
        if (!empty($emails)) {
            fn_delete_notification('changes_saved');
            fn_set_notification('E', __('error'), __('ult_share_users_setting_disabled'));
            $new_value = $old_value;
        }
    }
}

function fn_settings_actions_appearance_notice_displaying_time(&$new_value, $old_value)
{
    $new_value = fn_convert_to_numeric($new_value);
}

function fn_settings_actions_build_detailed_error_message($error) {

    $detailed_message = "";

    if (!empty($error['error_number'])) {
        $transport_prefix = __('http_transport_error_prefix_' . $error['transport']);

        $detailed_message .= "<br/><strong>{$transport_prefix} {$error['error_number']}</strong>";

        if ($error['transport'] == 'curl') {
            $error_description_paragraph = __('curl_error_code_reference_link',
                array(
                    '[href]' => Registry::get('config.resources.curl_error_interpretation'
                    )));
            $detailed_message .= "<br/>" . $error_description_paragraph;

        }
    }
    return $detailed_message;
}

/**
 * Checks whether SMTP connection details are valid.
 *
 * @param string $host                 The "SMTP host" setting value
 * @param string $username             The "SMTP username" setting value
 * @param string $password             The "SMTP password" setting value
 * @param string $encrypted_connection The "Use Encrypted Connection" setting value
 * @param string $auth                 The "Use SMTP authentication" setting value
 *
 * @return \Tygh\Common\OperationResult
 */
function fn_validate_stmp_connection_details($host, $username, $password, $encrypted_connection, $auth)
{
    $transport_settings = [
        'mailer_send_method'              => 'smtp',
        'mailer_smtp_host'                => $host,
        'mailer_smtp_username'            => $username,
        'mailer_smtp_password'            => $password,
        'mailer_smtp_ecrypted_connection' => $encrypted_connection,
        'mailer_smtp_auth'                => $auth,
    ];

    /** @var \Tygh\Mailer\Mailer $mailer */
    $mailer = new Mailer(
        Tygh::$app['mailer.message_builder_factory'],
        Tygh::$app['mailer.transport_factory'],
        $transport_settings,
        false
    );

    $transport = $mailer->getTransport($transport_settings);

    $result = new OperationResult(false);
    if ($transport instanceof PhpMailerTransport) {
        try {
            $debug_output_handler = static function ($error_message) use ($result) {
                static $i = 0;
                if (strpos($error_message, 'SMTP ERROR: ') !== 0) {
                    return;
                }

                $error_message = substr_replace($error_message, '', 0, strlen('SMTP ERROR: '));
                $result->addError((string) $i++, $error_message);
            };
            $transport->SMTPDebug = 1;
            $transport->Debugoutput = $debug_output_handler;
            $result->setSuccess($transport->smtpConnect());
        } catch (PHPMailerException $e) {
            $result->addError('', $e->getMessage());
        }
    }

    return $result;
}

/**
 * Checks that new SMTP settings are valid and restores old setting values when the check fails.
 */
function fn_check_smtp_settings_and_restore_on_fail()
{
    $old_settings = Registry::ifGet('smtp_settings', []);
    /** @var array<string, string> $new_settings */
    $new_settings = Settings::instance()->getValues('Emails');

    $result = fn_validate_stmp_connection_details(
        $new_settings['mailer_smtp_host'],
        $new_settings['mailer_smtp_username'],
        $new_settings['mailer_smtp_password'],
        $new_settings['mailer_smtp_ecrypted_connection'],
        $new_settings['mailer_smtp_auth']
    );

    if ($result->isSuccess()) {
        return;
    }

    /**
     * The validation callback is executed in a shutdown function and session is shut down already.
     * We should start it to store notifications.
     */
    Tygh::$app['session']->start();

    fn_delete_notification('changes_saved');

    foreach ($result->getErrors() as $error_message) {
        fn_set_notification(NotificationSeverity::ERROR, __('settings.smtp_error'), $error_message);
    }
    foreach ($old_settings as $old_setting => $old_value) {
        Settings::instance()->updateValue($old_setting, $old_value, 'Emails', false, null, false);
    }

    Tygh::$app['session']->shutdown();
}

/**
 * Stores old setting value when the "SMTP host" setting value.
 *
 * @param string $new_value Old setting value
 * @param string $old_value New setting value
 */
function fn_settings_actions_emails_mailer_smtp_host($new_value, $old_value)
{
    Registry::isExist('smtp_settings') or register_shutdown_function('fn_check_smtp_settings_and_restore_on_fail');
    Registry::set('smtp_settings.mailer_smtp_host', $old_value);
}

/**
 * Stores old setting value when the "SMTP username" setting value.
 *
 * @param string $new_value Old setting value
 * @param string $old_value New setting value
 */
function fn_settings_actions_emails_mailer_smtp_username($new_value, $old_value)
{
    Registry::isExist('smtp_settings') or register_shutdown_function('fn_check_smtp_settings_and_restore_on_fail');
    Registry::set('smtp_settings.mailer_smtp_username', $old_value);
}

/**
 * Stores old setting value when the "SMTP password" setting value.
 *
 * @param string $new_value Old setting value
 * @param string $old_value New setting value
 */
function fn_settings_actions_emails_mailer_smtp_password($new_value, $old_value)
{
    Registry::isExist('smtp_settings') or register_shutdown_function('fn_check_smtp_settings_and_restore_on_fail');
    Registry::set('smtp_settings.mailer_smtp_password', $old_value);
}

/**
 * Stores old setting value when the "Use Encrypted Connection" setting value.
 *
 * @param string $new_value Old setting value
 * @param string $old_value New setting value
 */
function fn_settings_actions_emails_mailer_smtp_ecrypted_connection($new_value, $old_value)
{
    Registry::isExist('smtp_settings') or register_shutdown_function('fn_check_smtp_settings_and_restore_on_fail');
    Registry::set('smtp_settings.mailer_smtp_ecrypted_connection', $old_value);
}

/**
 * Stores old setting value when the "Use SMTP authentication" setting value.
 *
 * @param string $new_value Old setting value
 * @param string $old_value New setting value
 */
function fn_settings_actions_emails_mailer_smtp_auth($new_value, $old_value)
{
    Registry::isExist('smtp_settings') or register_shutdown_function('fn_check_smtp_settings_and_restore_on_fail');
    Registry::set('smtp_settings.mailer_smtp_auth', $old_value);
}

/**
 * For backward compatibility:
 *  - Saves inventory_tracking setting
 *
 * @param string              $new_value New setting value
 * @param string              $old_value Old setting value
 * @param \Tygh\Settings|null $instance  Instance of settings
 */
function fn_settings_actions_general_global_tracking($new_value, $old_value, $instance = null)
{
    if ($instance === null) {
        $instance = Settings::instance();
    }

    if (fn_allowed_for('ULTIMATE') && !$instance->getCompanyId()) {
        /** @var int|false $inventory_tracking_id */
        $inventory_tracking_id = $instance->getId('inventory_tracking', 'General');

        if ($inventory_tracking_id) {
            $instance->resetAllOverrides($inventory_tracking_id);
        }
    }

    if ($new_value === ProductTracking::TRACK || !isset($new_value)) {
        $instance->updateValue('inventory_tracking', YesNo::YES, 'General', false, null, false);
        return;
    }

    $instance->updateValue('inventory_tracking', YesNo::NO, 'General', false, null, false);
}

/**
 * For backward compatibility:
 *  - Saves default_product_details_view setting
 *
 * @param string              $new_value New setting value
 * @param string              $old_value Old setting value
 * @param \Tygh\Settings|null $instance  Instance of settings
 */
function fn_settings_actions_appearance_global_product_details_view($new_value, $old_value, $instance = null)
{
    if ($new_value === null) {
        return;
    }
    if ($instance === null) {
        $instance = Settings::instance();
    }

    if (
        fn_allowed_for('ULTIMATE') && !$instance->getCompanyId()
        || fn_allowed_for('MULTIVENDOR') && !$instance->getStorefrontId(null, null)
    ) {
        /** @var int|false $default_product_details_view_id */
        $default_product_details_view_id = $instance->getId('default_product_details_view', 'Appearance');

        if ($default_product_details_view_id) {
            $instance->resetAllOverrides($default_product_details_view_id);
        }
    }

    $instance->updateValue('default_product_details_view', $new_value, 'Appearance');
}

/**
 * Validates max qty settings with qty step setting
 *
 * @param string $new_value New value
 * @param string $old_value Old value
 *
 * @return void
 */
function fn_settings_actions_checkout_global_max_qty($new_value, $old_value)
{
    Registry::isExist('qty_settings') or register_shutdown_function('fn_validate_qty_settings', 'global_');
    Registry::set('qty_settings.max_qty', $old_value);
}

/**
 * Validates min qty settings with qty step setting
 *
 * @param string $new_value New value
 * @param string $old_value Old value
 *
 * @return void
 */
function fn_settings_actions_checkout_global_min_qty($new_value, $old_value)
{
    Registry::isExist('qty_settings') or register_shutdown_function('fn_validate_qty_settings', 'global_');
    Registry::set('qty_settings.min_qty', $old_value);
}

/**
 * Validates qty step settings with min/max qty settings
 *
 * @param string $new_value New value
 * @param string $old_value Old value
 *
 * @return void
 */
function fn_settings_actions_checkout_global_qty_step($new_value, $old_value)
{
    Registry::isExist('qty_settings') or register_shutdown_function('fn_validate_qty_settings', 'global_');
    Registry::set('qty_settings.qty_step', $old_value);
}

/**
 * Validates list qty count setting
 *
 * @param string $new_value New value
 * @param string $old_value Old value
 *
 * @return void
 */
function fn_settings_actions_checkout_global_list_qty_count($new_value, $old_value)
{
    Registry::isExist('qty_settings') or register_shutdown_function('fn_validate_qty_settings', 'global_');
    Registry::set('qty_settings.list_qty_count', $old_value);
}

/**
 * Validates max qty settings with qty step setting
 *
 * @param string $new_value New value
 * @param string $old_value Old value
 *
 * @return void
 */
function fn_settings_actions_checkout_default_max_qty($new_value, $old_value)
{
    Registry::isExist('qty_settings') or register_shutdown_function('fn_validate_qty_settings', 'default_');
    Registry::set('qty_settings.max_qty', $old_value);
}

/**
 * Validates min qty settings with qty step setting
 *
 * @param string $new_value New value
 * @param string $old_value Old value
 *
 * @return void
 */
function fn_settings_actions_checkout_default_min_qty($new_value, $old_value)
{
    Registry::isExist('qty_settings') or register_shutdown_function('fn_validate_qty_settings', 'default_');
    Registry::set('qty_settings.min_qty', $old_value);
}

/**
 * Validates qty step settings with min/max qty settings
 *
 * @param string $new_value New value
 * @param string $old_value Old value
 *
 * @return void
 */
function fn_settings_actions_checkout_default_qty_step($new_value, $old_value)
{
    Registry::isExist('qty_settings') or register_shutdown_function('fn_validate_qty_settings', 'default_');
    Registry::set('qty_settings.qty_step', $old_value);
}

/**
 * Validates list qty count setting
 *
 * @param string $new_value New value
 * @param string $old_value Old value
 *
 * @return void
 */
function fn_settings_actions_checkout_default_list_qty_count($new_value, $old_value)
{
    Registry::isExist('qty_settings') or register_shutdown_function('fn_validate_qty_settings', 'default_');
    Registry::set('qty_settings.list_qty_count', $old_value);
}

/**
 * Validates all qty settings with quantity step and updates it if needs
 *
 * @param string $prefix Setting prefix
 *
 * @return void
 */
function fn_validate_qty_settings($prefix)
{
    $list_qty_count = Settings::instance()->getValue($prefix . 'list_qty_count', 'Checkout');

    if (is_numeric($list_qty_count)) {
        $list_qty_count = $list_qty_count < 0 ? 0 : ceil(abs($list_qty_count));
        Settings::instance()->updateValue($prefix . 'list_qty_count', (int) $list_qty_count, 'Checkout', false, null, false);
    }

    $qty_step = Settings::instance()->getValue($prefix . 'qty_step', 'Checkout');

    if (!is_numeric($qty_step)) {
        return;
    }

    $qty_step = (int) abs($qty_step);

    $old_min_qty_value = Settings::instance()->getValue($prefix . 'min_qty', 'Checkout');
    $old_max_qty_value = Settings::instance()->getValue($prefix . 'max_qty', 'Checkout');

    $correct_min_qty_value = fn_ceil_to_step((int) abs($old_min_qty_value), $qty_step);
    $correct_max_qty_value = fn_ceil_to_step((int) abs($old_max_qty_value), $qty_step);

    if (
        !empty($correct_min_qty_value)
        && !empty($correct_max_qty_value)
        && $correct_min_qty_value > $correct_max_qty_value
    ) {
        $correct_min_qty_value = 0;
    }

    if ($old_min_qty_value !== $correct_min_qty_value) {
        Settings::instance()->updateValue($prefix . 'min_qty', (int) $correct_min_qty_value, 'Checkout', false, null, false);
    }

    if ($old_max_qty_value !== $correct_max_qty_value) {
        Settings::instance()->updateValue($prefix . 'max_qty', (int) $correct_max_qty_value, 'Checkout', false, null, false);
    }

    if (is_numeric($qty_step)) {
        Settings::instance()->updateValue($prefix . 'qty_step', $qty_step, 'Checkout', false, null, false);
    }

    Registry::del('qty_settings');
}
