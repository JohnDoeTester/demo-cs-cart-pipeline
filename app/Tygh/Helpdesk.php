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

/* WARNING: DO NOT MODIFY THIS FILE TO AVOID PROBLEMS WITH THE CART FUNCTIONALITY */

namespace Tygh;

use Tygh\Enum\SiteArea;
use Tygh\Tygh;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\UserTypes;
use Tygh\Enum\YesNo;
use Tygh\NotificationsCenter\NotificationsCenter;
use Tygh\UpgradeCenter\App;

/**
 *
 * Helpdesk connector class
 *
 */
class Helpdesk
{
    /**
     * Returns current license status
     *
     * @param  string $license_number
     * @param  array $extra_fields
     *
     * @return string
     */
    public static function getLicenseInformation($license_number = '', $extra_fields = array())
    {
        if (empty($license_number)) {
            $uc_settings = Settings::instance()->getValues('Upgrade_center');
            $license_number = $uc_settings['license_number'];
        }

        $store_mode = fn_get_storage_data('store_mode');

        if (empty($license_number) && !in_array($store_mode, ['trial', 'free'])) {
            return 'LICENSE_IS_INVALID';
        }

        $store_ip = fn_get_ip();
        $store_ip = $store_ip['host'];

        $request = [
            'license_number'   => $license_number,
            'ver'              => PRODUCT_VERSION,
            'product_status'   => PRODUCT_STATUS,
            'product_build'    => strtoupper(PRODUCT_BUILD),
            'edition'          => isset($extra_fields['edition'])
                ? $extra_fields['edition']
                : PRODUCT_EDITION,
            'lang'             => strtoupper(CART_LANGUAGE),
            'store_uri'        => fn_url('', 'C', 'http'),
            'secure_store_uri' => fn_url('', 'C', 'https'),
            'https_enabled'    => (Registry::get('settings.Security.secure_storefront') === YesNo::YES || Registry::get('settings.Security.secure_admin') === YesNo::YES)
                ? 'Y'
                : 'N',
            'admin_uri'        => str_replace(fn_get_index_script('A'), '', fn_url('', 'A', 'http')),
            'store_ip'         => $store_ip,
            'store_mode'       => strtoupper(isset($extra_fields['store_mode'])
                ? $extra_fields['store_mode']
                : $store_mode
            ),
        ];

        $request = array(
            'Request@action=check_license@api=3' => array_merge($extra_fields, $request),
        );

        $request = '<?xml version="1.0" encoding="UTF-8"?>' . fn_array_to_xml($request);

        Registry::set('log_cut', Registry::ifGet('config.demo_mode', false));

        $data = Http::get(Registry::get('config.resources.updates_server') . '/index.php?dispatch=product_updates.check_available', array('request' => $request), array(
            'timeout' => 10
        ));

        if (empty($data)) {
            $data = fn_get_contents(Registry::get('config.resources.updates_server') . '/index.php?dispatch=product_updates.check_available&request=' . urlencode($request));
        }

        $_SESSION['license_information'] = $data;

        if (empty($license_number) && !fn_allowed_for('ULTIMATE:FREE')) {
            return 'LICENSE_IS_INVALID';
        }

        return $data;
    }

    /**
     * Set/Get token auth key
     * @param  string $generate If generate value is equal to "true", new token will be generated
     * @return string token value
     */
    public static function token($generate = false)
    {
        if ($generate) {
            $token = fn_crc32(microtime());
            fn_set_storage_data('hd_request_code', $token);
        } else {
            $token = fn_get_storage_data('hd_request_code');
        }

        return $token;
    }

    /**
     * Get store auth key
     *
     * @return string store key
     */
    public static function getStoreKey()
    {
        $key = Registry::get('settings.store_key');
        $host_path = Registry::get('config.http_host') . Registry::get('config.http_path');
        /** @var \Tygh\Database\Connection $db */
        $db = Tygh::$app['db'];
        $actual_root_admin_email = $db->getField('SELECT email FROM ?:users WHERE is_root = ?s AND company_id = ?i', YesNo::YES, 0);

        if (!empty($key)) {
            [, $host, $root_admin_email] = array_pad(explode(';', $key), 3, null);
            if ($host !== $host_path || $root_admin_email !== $actual_root_admin_email) {
                unset($key);
            }
        }

        if (empty($key)) {
            // Generate new value
            $key = fn_crc32(microtime());
            $key .= ';' . $host_path . ';' . $actual_root_admin_email;

            Settings::instance()->updateValue('store_key', $key);
        }

        return $key;
    }

    public static function auth()
    {
        $_SESSION['last_status'] = 'INIT';

        self::initHelpdeskRequest();

        return true;
    }

    public static function initHelpdeskRequest($area = AREA)
    {
        if ($area != 'C') {
            $protocol = defined('HTTPS') ? 'https' : 'http';

            $_SESSION['stats'][] = '<img src="' . fn_url('helpdesk_connector.auth', 'A', $protocol) . '" alt="" style="display:none" />';
        }
    }

    /**
     * Parse license information
     *
     * @param  string    $data             Result from [self::getLicenseInformation]
     * @param  array     $auth
     * @param  bool|true $process_messages
     *
     * @phpcs:disable SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     *
     * @return array Return string $license, string $updates, array $messages, array $params, array $restrictions
     */
    public static function parseLicenseInformation($data, $auth, $process_messages = true)
    {
        $updates = $messages = $license = '';
        $params = $restrictions = [];

        if (!empty($data)) {
            // Check if we can parse server response
            if (strpos($data, '<?xml') !== false) {
                $xml = simplexml_load_string($data);
                $updates = (string) $xml->Updates;
                $messages = $xml->Messages;
                $license = (string) $xml->License;

                if (isset($xml->TrialExpiryTime)) {
                    $params['trial_expiry_time'] = (int) $xml->TrialExpiryTime;
                }

                if (isset($xml->TrialLeftTime)) {
                    $params['trial_left_time'] = (int) $xml->TrialLeftTime;
                }

                if (isset($xml->AllowedNumberOfStores)) {
                    fn_set_storage_data('allowed_number_of_stores', (int) $xml->AllowedNumberOfStores);
                } else {
                    fn_set_storage_data('allowed_number_of_stores', null);
                }

                if (isset($xml->Restrictions)) {
                    $json_restrictions = json_encode($xml->Restrictions);
                    $restrictions = json_decode($json_restrictions, true);

                    fn_set_storage_data('restrictions', serialize($restrictions));
                } else {
                    fn_set_storage_data('restrictions', '');
                }

                if (isset($xml->LicenseKey)) {
                    Settings::instance()->updateValue('license_number', (string) $xml->LicenseKey, '', false, null, false);
                }

                if (isset($xml->FreeMode) && fn_allowed_for('ULTIMATE:FREE') && YesNo::isFalse(fn_get_storage_data('free_mode', false))) {
                    fn_set_storage_data('free_mode', YesNo::YES);
                }

                if (isset($xml->LatestAvailableVersion)) {
                    fn_set_storage_data('latest_available_version', (string) $xml->LatestAvailableVersion->Version);
                    fn_set_storage_data('latest_available_version_timestamp', (string) $xml->LatestAvailableVersion->Timestamp);
                }
            } else {
                $license = $data;
            }
        }

        if (!empty($auth)) {
            if (
                Registry::get('settings.General.auto_check_updates') === YesNo::YES
                && fn_check_user_access($auth['user_id'], 'upgrade_store')
            ) {
                $app = App::instance();
                $upgrade_packages = $app->getUpgradePackagesWithCache();

                /** @var \Tygh\NotificationsCenter\NotificationsCenter $notifications_center */
                $notifications_center = Tygh::$app['notifications_center'];
                if (!empty($upgrade_packages['core']['core'])) {
                    $check_core_notifications = $notifications_center->get(
                        ['title' => __('notification.upgrade_available.title')]
                    );
                    if (empty($check_core_notifications)) {
                        $notifications_center->add([
                            'user_id'       => $auth['user_id'],
                            'title'         => __('notification.upgrade_available.title'),
                            'message'       => __('notification.upgrade_available.message', ['[link]' => fn_url('upgrade_center.manage')]),
                            'area'          => SiteArea::ADMIN_PANEL,
                            'section'       => NotificationsCenter::SECTION_ADMINISTRATION,
                            'tag'           => NotificationsCenter::TAG_UPDATE,
                            'action_url'    => fn_url('upgrade_center.manage'),
                            'language_code' => Registry::get('settings.Appearance.backend_default_language'),
                            'pinned'        => true
                        ]);
                    }
                }
                if (!empty($upgrade_packages['addon'])) {
                    $check_addons_notifications = $notifications_center->get(
                        ['title' => __('notification.addons_upgrade_available.title')]
                    );
                    if (empty($check_addons_notifications)) {
                        $notifications_center->add([
                            'user_id'       => $auth['user_id'],
                            'title'         => __('notification.addons_upgrade_available.title'),
                            'message'       => __(
                                'notification.addons_upgrade_available.message',
                                ['[link]' => fn_url('upgrade_center.manage')]
                            ),
                            'area'          => SiteArea::ADMIN_PANEL,
                            'section'       => NotificationsCenter::SECTION_ADMINISTRATION,
                            'tag'           => NotificationsCenter::TAG_UPDATE,
                            'action_url'    => fn_url('upgrade_center.manage'),
                            'language_code' => Registry::get('settings.Appearance.backend_default_language'),
                            'pinned'        => true
                        ]);
                    }
                }
            }

            if (!empty($data)) {
                $_SESSION['last_status'] = $license;
            }
        }

        $messages = self::processMessages($messages, $process_messages, $license);

        return [$license, $updates, $messages, $params, $restrictions];
    }

    public static function processMessages($messages, $process_messages = true, $license_status = '')
    {
        $new_messages = [];

        if (!empty($messages)) {

            foreach ($messages->Message as $message) {
                $message_id = empty($message->Id)
                    ? intval(fn_crc32(microtime()) / 2)
                    : (string) $message->Id;

                $new_messages[$message_id] = [
                    'text'  => (string) $message->Text,
                    'type'  => empty($message->Type)
                        ? NotificationSeverity::WARNING
                        : (string) $message->Type,
                    'title' => empty($message->Title)
                        ? __('notice')
                        : (string) $message->Title,
                    'state' => empty($message->State)
                        ? null
                        : (string) $message->State,
                    'action_url' => empty($message->ActionUrl)
                        ? ''
                        : (string) $message->ActionUrl,
                    'pinned' => !empty($message->Pinned) && $message->Pinned,
                    'remind' => !empty($message->Remind) && $message->Remind
                ];
            }

            // check new messages for 'special' messages
            $special_messages = fn_get_schema('settings', 'licensing');
            foreach ($special_messages as $special_message_id => $message_info) {
                if (isset($new_messages[$special_message_id])) {
                    $new_messages[$special_message_id] = fn_array_merge(
                        $new_messages[$special_message_id],
                        [
                            'text'       => $message_info['message']    ?: $new_messages[$special_message_id]['text'],
                            'type'       => $message_info['severity']   ?: $new_messages[$special_message_id]['type'],
                            'title'      => $message_info['title']      ?: $new_messages[$special_message_id]['title'],
                            'state'      => $message_info['state']      ?: $new_messages[$special_message_id]['state'],
                            'action_url' => $message_info['action_url'] ?: $new_messages[$special_message_id]['action_url'],
                            'section'    => $message_info['section']    ?: NotificationsCenter::SECTION_ADMINISTRATION,
                            'tag'        => $message_info['tag']        ?: NotificationsCenter::TAG_OTHER,
                        ]
                    );
                }
            }

            if (!empty($license_status) && !$new_messages) {
                switch ($license_status) {
                    case 'PENDING':
                    case 'SUSPENDED':
                    case 'DISABLED':
                        $new_messages['license_error_license_is_disabled'] = [
                            'type'       => NotificationSeverity::ERROR,
                            'title'      => __('error'),
                            'text'       => __('licensing.license_error_license_is_disabled'),
                            'action_url' => '',
                            'section'    => NotificationsCenter::SECTION_ADMINISTRATION,
                            'tag'        => NotificationsCenter::TAG_LICENSE,
                        ];
                        break;
                    case 'LICENSE_IS_INVALID':
                        $new_messages['license_error_license_is_invalid'] = [
                            'type'       => NotificationSeverity::ERROR,
                            'title'      => __('error'),
                            'text'       => __('licensing.license_error_license_is_invalid'),
                            'action_url' => '',
                            'section'    => NotificationsCenter::SECTION_ADMINISTRATION,
                            'tag'        => NotificationsCenter::TAG_LICENSE,
                        ];
                        break;
                }
            }

            if ($process_messages) {
                /** @var \Tygh\NotificationsCenter\NotificationsCenter $notifications_center */
                $notifications_center = Tygh::$app['notifications_center'];
                /** @var \Tygh\Database\Connection $db */
                $db = Tygh::$app['db'];
                $root_admin_user_id = (int) $db->getField(
                    'SELECT user_id FROM ?:users WHERE user_type = ?s AND is_root = ?s AND company_id = ?i',
                    UserTypes::ADMIN,
                    YesNo::YES,
                    0
                );

                $force_notification = [
                    UserTypes::ADMIN => true,
                ];

                /** @var \Tygh\Notifications\Settings\Factory $notification_settings_factory */
                $notification_settings_factory = Tygh::$app['event.notification_settings.factory'];
                $notification_rules = $notification_settings_factory->create($force_notification);

                foreach ($new_messages as $msg) {
                    $notifications_center->add([
                        'user_id'    => $root_admin_user_id,
                        'title'      => $msg['title'],
                        'message'    => $msg['text'],
                        'severity'   => $msg['type'],
                        'area'       => SiteArea::ADMIN_PANEL,
                        'action_url' => $msg['action_url'],
                        'section'    => isset($msg['section'])
                            ? $msg['section']
                            : NotificationsCenter::SECTION_OTHER,
                        'tag'        => isset($msg['tag'])
                            ? $msg['tag']
                            : NotificationsCenter::TAG_OTHER,
                        'language_code' => Registry::get('settings.Appearance.backend_default_language'),
                        'pinned'     => $msg['pinned'],
                        'remind'     => $msg['remind']
                    ]);

                    Tygh::$app['event.dispatcher']->dispatch('helpdesk_process_message', $msg, $notification_rules);
                }
            }
        }

        return $new_messages;
    }

    public static function registerLicense($license_data)
    {
        $request = array(
            'Request@action=registerLicense@api=2' => array(
                'product_type' => PRODUCT_EDITION,
                'domain' => Registry::get('config.http_host'),
                'first_name' => $license_data['first_name'],
                'last_name' => $license_data['last_name'],
                'email' => $license_data['email'],
            ),
        );

        $request = '<?xml version="1.0" encoding="UTF-8"?>' . fn_array_to_xml($request);

        $data = Http::get(Registry::get('config.resources.updates_server') . '/index.php?dispatch=licenses_remote.add', array('request' => $request), array(
            'timeout' => 10
        ));

        if (empty($data)) {
            $data = fn_get_contents(Registry::get('config.resources.updates_server') . '/index.php?dispatch=licenses_remote.create&request=' . urlencode($request));
        }

        $result = $messages = $license = '';

        if (!empty($data)) {
            // Check if we can parse server response
            if (strpos($data, '<?xml') !== false) {
                $xml = simplexml_load_string($data);
                $result = (string) $xml->Result;
                $messages = $xml->Messages;
                $license = (array) $xml->License;
            }
        }

        self::processMessages($messages, true, $license);

        return array($result, $license, $messages);
    }

    public static function checkStoreImportAvailability($license_number, $version, $edition = PRODUCT_EDITION)
    {
        $request = array(
            'dispatch' => 'product_updates.check_storeimport_available',
            'license_key' => $license_number,
            'ver' => $version,
            'edition' => $edition,
        );

        $data = Http::get(Registry::get('config.resources.updates_server'), $request, array(
            'timeout' => 10
        ));

        if (empty($data)) {
            $data = fn_get_contents(Registry::get('config.resources.updates_server') . '/index.php?' . http_build_query($request));
        }

        $result = false;

        if (!empty($data)) {
            // Check if we can parse server response
            if (strpos($data, '<?xml') !== false) {
                $xml = simplexml_load_string($data);
                $result = ((string) $xml == 'Y') ? true : false;
            }
        }

        return $result;
    }

    /**
     * Masques license number when the demo mode is enabled
     *
     * @param string $license_number License number
     * @param bool   $is_demo_mode   True if demo mode enabled
     *
     * @return string Spoofed (if necessary) license number
     */
    public static function masqueLicenseNumber($license_number, $is_demo_mode = false)
    {
        if ($license_number && $is_demo_mode) {
            $license_number = preg_replace('/[^-]/', 'X', $license_number);
        }

        return $license_number;
    }

    /**
     * Checks store mode.
     *
     * @param string $license_number License number
     * @param array  $auth           Auth data
     * @param array  $extra          Extra data to include into license check
     *
     * @phpcs:disable SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
     *
     * @return array License status, messages, store mode and restrictions
     */
    public static function getStoreMode($license_number, $auth, $extra = array())
    {
        $license_status = 'LICENSE_IS_INVALID';
        $store_mode = '';
        $messages = [];

        if (fn_allowed_for('MULTIVENDOR')) {
            $store_modes_list = ['', 'plus', 'ultimate', 'enterprise'];
        } else {
            $store_modes_list = ['free', '', 'ultimate', 'enterprise'];
        }

        foreach ($store_modes_list as $store_mode) {
            $extra['store_mode'] = $store_mode;
            $data = Helpdesk::getLicenseInformation($license_number, $extra);
            [$license_status, , $messages, , $restrictions] = Helpdesk::parseLicenseInformation($data, $auth, false);
            if ($license_status == 'ACTIVE') {
                break;
            }
        }

        return [$license_status, $messages, $store_mode, $restrictions];
    }

    /**
     * Checks if companies limitations have been reached.
     *
     * @deprecated since 4.10.1.
     * Use \Tygh\Helpdesk::isStorefrontsLimitReached instead
     *
     * @return bool
     */
    public static function isCompaniesLimitReached()
    {
        return static::isStorefrontsLimitReached();
    }

    /**
     * Checks if storefronts limitations have been reached.
     *
     * @return bool True if there are too many storefronts
     */
    public static function isStorefrontsLimitReached()
    {
        if ($storefronts_limit = fn_get_storage_data('allowed_number_of_stores')) {
            /** @var \Tygh\Storefront\Repository $repository */
            $repository = Tygh::$app['storefront.repository'];
            $storefronts_count = $repository->getCount();

            return $storefronts_count >= $storefronts_limit;
        }

        return false;
    }

    /**
     * Sends usage feature metrics.
     */
    public static function sendReportMetrics()
    {
        $uc_settings = Settings::instance()->getValues('Upgrade_center');
        $license_number = $uc_settings['license_number'];

        if ($license_number) {
            $metrics = fn_get_schema('reporting', 'metrics');

            foreach ($metrics as &$value) {
                if (is_callable($value)) {
                    $value = call_user_func($value);
                }
            }
            unset($value);

            $logging = Http::$logging;
            Http::$logging = false;

            Http::post(
                Registry::get('config.resources.updates_server') . '/index.php?dispatch=license_tracking.report',
                array(
                    'metrics' => $metrics,
                    'license_number' => $license_number
                ),
                array(
                    'timeout' => 10
                )
            );

            Http::$logging = $logging;
        }
    }

    public static function isValidRequest(array $request, array $additional_validation_params = [])
    {
        if (!isset($request['token'])) {
            return false;
        }

        $validation_params = array_merge([
            'dispatch' => 'validators.validate_request',
            'token'    => $request['token'],
        ], $additional_validation_params);

        $validator_url = Registry::get('config.resources.updates_server') . '/index.php';

        $log_cut = Registry::ifGet('log_cut', false);

        Registry::set('log_cut', true);
        $validator_response = Http::get($validator_url, $validation_params);
        Registry::set('log_cut', $log_cut);

        $validator_response = strtolower(trim($validator_response));

        return $validator_response === 'valid';
    }

    /**
     * @param bool   $stop_execution  Allows you to stop the execution of the script after the output of the response
     * @param string $format          Response format
     * @param string $get_last_action Gets last action
     *
     * @return string|void
     */
    public static function getSoftwareInformation($stop_execution = true, $format = 'html', $get_last_action = YesNo::NO)
    {
        /** @var \Tygh\SoftwareProductEnvironment $software */
        $software = Tygh::$app['product.env'];

        if ($format === 'json') {
            $version = [
                'product_name'   => $software->getProductName(),
                'version'        => $software->getProductVersion(),
                'product_status' => $software->getProductStatus(),
                'product_build'  => $software->getProductBuild(),
                'store_mode'     => $software->getStoreMode(),
            ];

            if (YesNo::toBool($get_last_action)) {
                $version['last_action'] = self::getLastAction();
            }

            $version_string = json_encode($version);
        } else {
            $version_string = $software->getProductName() . ' <b>' . $software->getProductVersion() . ' ';
            if ($software->getProductStatus() !== '') {
                $version_string .= ' (' . $software->getProductStatus() . ')';
            }
            if ($software->getProductBuild()) {
                $version_string .= ' ' . $software->getProductBuild();
            }
            $version_string .= '</b>';
        }

        if ($stop_execution) {
            echo $version_string;
            exit(0);
        }

        return $version_string;
    }

    /**
     * @return string
     */
    private static function getLastAction()
    {
        $table_names = ['users', 'orders', 'products', 'shipments'];
        /** @var \Tygh\Database\Connection $db */
        $db = Tygh::$app['db'];
        $actions_timestamps = array_map(static function ($table_name) use ($db) {
            return $db->getField('SELECT MAX(TIMESTAMP) FROM ?:?p', $table_name);
        }, $table_names);

        return max($actions_timestamps);
    }
}
