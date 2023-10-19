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

use Imagine\Image\Box;
use Imagine\Image\Palette\RGB;
use Imagine\Image\Point;
use Tygh\Addons\MobileApp\Notifications\Sender;
use Tygh\BlockManager\Exim;
use Tygh\BlockManager\Layout;
use Tygh\Common\OperationResult;
use Tygh\Enum\Addons\MobileApp\ColorPresets;
use Tygh\Enum\Addons\MobileApp\ReportTypes;
use Tygh\Enum\OrderStatuses;
use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Enum\YesNo;
use Tygh\Exceptions\DeveloperException;
use Tygh\Http;
use Tygh\Less;
use Tygh\NotificationsCenter\NotificationsCenter;
use Tygh\Registry;
use Tygh\Settings;
use Tygh\Storage;
use Tygh\Storefront\Storefront;
use Tygh\Tools\Url;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Saves mobile application config
 *
 * @param int                                                                                        $setting_id    Setting identifier
 * @param array{app_settings: array<array<string>>, app_appearance: array<array<array<string|int>>>} $settings      List of colors and parameters from form
 * @param int                                                                                        $storefront_id Storefront identifier
 * @param string                                                                                     $color_preset  Selected color preset
 *
 * @return void
 */
function fn_mobile_app_update_settings($setting_id, array $settings, $storefront_id = 0, $color_preset = ColorPresets::CUSTOM)
{
    $setting_id = (int) $setting_id;

    // Get addon settings before its updated
    $current_colors = fn_mobile_app_get_mobile_app_settings($storefront_id);
    $current_colors = $current_colors['app_appearance']['colors'];

    // Merge structure from setting with structure from form
    foreach ($current_colors as $category => $variables) {
        $category_colors = &$settings['app_appearance']['colors'][$category];

        foreach ($variables as $variable_name => $values) {
            $values['value'] = $category_colors[$variable_name];
            $category_colors[$variable_name] = $values;
        }
    }

    Settings::instance(['storefront_id' => $storefront_id])->updateValue('color_preset', $color_preset, 'mobile_app');

    if ($setting_id) {
        $settings = (array) $settings;
        $settings['modified_time'] = TIME;
        Settings::instance(['storefront_id' => $storefront_id])->updateValueById((int) $setting_id, json_encode($settings));
    }
}

/**
 * Fetches application settings from saved settings array
 *
 * @param array $options Array containing add-ons' settings
 *
 * @return array
 */
function fn_mobile_app_extract_settings_from_options(array $options)
{
    $settings = array();
    $setting_id = 0;

    if (!empty($options['service'])) {

        foreach ($options['service'] as $id => $service) {

            if ($service['name'] === 'config_data') {
                $settings = json_decode($service['value'], true);
                $setting_id = $id;
                break;
            }
        }
    }

    if (isset($settings['app_settings']['utility']['shopName'])
        && trim($settings['app_settings']['utility']['shopName']) === ''
    ) {
        $settings['app_settings']['utility']['shopName'] = Registry::get('settings.Company.company_name');
    }

    if (!isset($settings['app_settings']['utility']['pushNotifications'])) {
        $settings['app_settings']['utility']['pushNotifications'] = true;
    }

    $settings['app_settings']['apple_pay'] = fn_mobile_app_extract_apple_pay_settings($settings);
    $settings['app_settings']['google_pay'] = fn_mobile_app_extract_google_pay_settings($settings);

    return array($setting_id, $settings);
}

/**
 * Extracts apple pay settings
 *
 * @param array $settings Settings
 *
 * @return array
 */
function fn_mobile_app_extract_apple_pay_settings(array $settings)
{
    $apple_pay_settings = isset($settings['app_settings']['apple_pay']) ? $settings['app_settings']['apple_pay'] : [];

    $enable_apple_pay = false;
    if (isset($apple_pay_settings['applePay']) && $apple_pay_settings['applePay'] === 'on') {
        $enable_apple_pay = true;
    }
    $apple_pay_settings['applePay'] = $enable_apple_pay;

    $apple_pay_supported_networks = [];
    if (isset($apple_pay_settings['applePaySupportedNetworks'])) {
        $apple_pay_supported_networks = (array) $apple_pay_settings['applePaySupportedNetworks'];
    }
    $apple_pay_settings['applePaySupportedNetworks'] = $apple_pay_supported_networks;

    return $apple_pay_settings;
}

/**
 * Extracts google pay settings
 *
 * @param array{app_settings: array{google_pay: array{googlePay: string, googlePayApiKey: string, googlePaySupportedNetworks?: array<string>}}} $settings Settings
 *
 * @return array<bool|string|array<string>>
 */
function fn_mobile_app_extract_google_pay_settings(array $settings)
{
    $google_pay_settings = isset($settings['app_settings']['google_pay']) ? $settings['app_settings']['google_pay'] : [];

    $enable_google_pay = false;
    if (isset($google_pay_settings['googlePay']) && $google_pay_settings['googlePay'] === 'on') {
        $enable_google_pay = true;
    }
    $google_pay_settings['googlePay'] = $enable_google_pay;

    $google_pay_supported_networks = [];
    if (isset($google_pay_settings['googlePaySupportedNetworks'])) {
        $google_pay_supported_networks = $google_pay_settings['googlePaySupportedNetworks'];
    }
    $google_pay_settings['googlePaySupportedNetworks'] = $google_pay_supported_networks;

    return $google_pay_settings;
}

/**
 * Fetches saved mobile application settings along with additional that required for application config
 *
 * @param int $storefront_id Storefront identifier
 *
 * @return array
 */
function fn_mobile_app_get_mobile_app_settings($storefront_id = 0)
{
    $section = Settings::instance(['storefront_id' => $storefront_id])->getSectionByName('mobile_app', Settings::ADDON_SECTION);
    $options = Settings::instance(['storefront_id' => $storefront_id])->getList($section['section_id']);

    list(, $settings) = fn_mobile_app_extract_settings_from_options($options);

    $root_user_email = db_get_field('SELECT email FROM ?:users WHERE is_root = ?s AND user_type = ?s LIMIT 1', YesNo::YES, UserTypes::ADMIN);
    $settings['app_settings']['utility']['username'] = $root_user_email;
    $settings['app_settings']['utility']['apiKey'] = Registry::get('addons.storefront_rest_api.access_key');

    list($storefront_url, $api_url) = fn_mobile_app_get_store_urls($storefront_id);
    $settings['app_settings']['utility']['siteUrl'] = $storefront_url;
    $settings['app_settings']['utility']['baseUrl'] = $api_url;

    $settings['app_settings']['utility']['version'] = fn_allowed_for('ULTIMATE') ? 'ULT' : 'MVE';

    $settings['app_settings']['apple_pay']['applePayMerchantIdentifier'] = !empty($settings['app_settings']['apple_pay']['applePayMerchantIdentifier'])
        ? $settings['app_settings']['apple_pay']['applePayMerchantIdentifier']
        : APPLE_PAY_DEFAULT_MERCHANT_ID;

    $settings['app_settings']['apple_pay']['applePayMerchantName'] = !empty($settings['app_settings']['apple_pay']['applePayMerchantName'])
        ? $settings['app_settings']['apple_pay']['applePayMerchantName']
        : APPLE_PAY_DEFAULT_MERCHANT_NAME;

    return $settings;
}

/**
 * Fetches store urls that required for application
 *
 * @param int $storefront_id Storefront identifier
 *
 * @return array
 */
function fn_mobile_app_get_store_urls($storefront_id = 0)
{
    $store_url = fn_get_storefront_url('current', $storefront_id) . '/';
    $api_url = str_replace(Registry::get('config.customer_index'), '', $store_url);
    $api_url .= 'api/4.0/';

    return [$store_url, $api_url];
}

/**
 * Fetches mobile app images data
 *
 * @param int $storefront_id Storefront identifier
 *
 * @return array
 */
function fn_mobile_app_get_mobile_app_images($storefront_id = 0)
{
    $images = array();
    $schema = fn_get_schema('mobile_app', 'app_settings');

    foreach ($schema['images'] as $data) {
        $type = $data['type'];
        $images[$type] = fn_get_image_pairs($storefront_id, $type, 'M');
    }

    return $images;
}

/**
 * Compiles provided styles array
 *
 * @param array $styles Array of styles
 *
 * @return bool|int|string
 */
function fn_mobile_app_compile_app_styles(array $styles)
{
    $path_to_mobile_app_skin_less = fn_get_theme_path('[relative]/css/addons/mobile_app/variables.less');
    $less = file_get_contents($path_to_mobile_app_skin_less);

    $less_compiler = new Less();
    $config = $less_compiler::arrayToLessVars($styles);

    return $less_compiler->parse($config . $less);
}

/**
 * Installs mobile app layout for the current theme.
 */
function fn_mobile_app_install_layout()
{
    if (fn_allowed_for('MULTIVENDOR')) {
        $layout_path = Registry::get('config.dir.addons') . 'mobile_app/resources/layouts_mve.xml';
    } else {
        $layout_path = Registry::get('config.dir.addons') . 'mobile_app/resources/layouts.xml';
    }

    /** @var \Tygh\Storefront\Repository $storefront_repository */
    $storefront_repository = Tygh::$app['storefront.repository'];
    list($storefronts) = $storefront_repository->find();

    /** @var \Tygh\Storefront\Storefront $storefront */
    foreach ($storefronts as $storefront) {
        fn_mobile_app_create_mobile_layout($storefront);
    }
}

/**
 * Fetches working dir for archiving application settings
 *
 * @return string
 */
function fn_mobile_app_get_working_dir()
{
    return implode(DIRECTORY_SEPARATOR, array('sess_data', 'mobile_app'));
}

/**
 * Resizes images for android
 *
 * @param array $image_types_schema Existent image types
 * @param array $image_sizes_schema Android phone image sizes
 * @param array $pair_data          Image pair
 * @param bool  $crop_when_resize   Crop image
 *
 * @return void
 */
function fn_mobile_app_resize_android_images(array $image_types_schema, array $image_sizes_schema, array $pair_data, $crop_when_resize = false)
{
    if (empty($pair_data['detailed']['absolute_path'])) {
        return;
    }

    $working_dir = fn_mobile_app_get_working_dir();
    $file_name = isset($image_sizes_schema['file_name']) ? $image_sizes_schema['file_name'] : '';
    $resized_image_paths = reset($image_sizes_schema['paths']);

    /** @var \Tygh\Backend\Storage\File $storage */
    $storage = Storage::instance('custom_files');

    // add resized images
    foreach ($image_sizes_schema['resolutions'] as $resolution_code => $resolution) {
        list($contents, $extension) = fn_mobile_app_resize_image($pair_data, $resolution, $crop_when_resize);

        if (!$contents) {
            continue;
        }

        $resized_file_path = $resized_image_paths['path'];

        if ($resolution_code == 'original') {
            $resized_file_path = $resized_image_paths['original_path'];
        }

        if (!empty($resized_image_paths['variables'])) {

            foreach ($resized_image_paths['variables'] as $variable) {

                if (isset(${$variable})) {
                    $resized_file_path = str_replace(implode('', ['%', $variable, '%']), ${$variable}, $resized_file_path);
                }
            }
        }

        $resized_file_name = implode('.', [$file_name, $extension]);

        $params = [
            'contents'  => $contents,
            'overwrite' => true,
        ];

        $resized_file_full_path = implode(DIRECTORY_SEPARATOR, [$working_dir, $resized_file_path, $resized_file_name]);
        $storage->put($resized_file_full_path, $params);
    }
}

/**
 * Get common images for app config
 *
 * @param array $image_types_schema Existent image types
 * @param array $pair_data          Image pair
 * @param bool  $crop_when_resize   Crop image
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_mobile_app_get_common_images(array $image_types_schema, array $pair_data, $crop_when_resize)
{
    $working_dir = fn_mobile_app_get_working_dir();

    /** @var \Tygh\Backend\Storage\File $storage */
    $storage = Storage::instance('custom_files');

    list($contents, $extension) = fn_mobile_app_resize_image($pair_data, $image_types_schema['image_params']['resolution'], $crop_when_resize);

    if (!$contents) {
        return;
    }

    $file_name = implode('.', [$image_types_schema['image_params']['name'], $extension]);

    $params = [
        'contents'  => $contents,
        'overwrite' => true,
    ];

    $full_path = implode(DIRECTORY_SEPARATOR, [$working_dir, $file_name]);
    $storage->put($full_path, $params);
}

/**
 * Resizes images for ios
 *
 * @param array $image_types_schema Existent image types
 * @param array $image_sizes_schema iOS phone image sizes
 * @param array $pair_data          Image pair
 * @param bool  $crop_when_resize   Crop image
 *
 * @return void
 */
function fn_mobile_app_resize_ios_images(array $image_types_schema, array $image_sizes_schema, array $pair_data, $crop_when_resize = false)
{
    $working_dir = fn_mobile_app_get_working_dir();
    $resized_image_path = isset($image_sizes_schema['path']) ? $image_sizes_schema['path'] : '';

    /** @var \Tygh\Backend\Storage\File $storage */
    $storage = Storage::instance('custom_files');

    // add resized images
    foreach ($image_sizes_schema['resolutions'] as $resolution) {
        list($contents, $extension) = fn_mobile_app_resize_image($pair_data, $resolution, $crop_when_resize);

        if (!$contents) {
            continue;
        }

        $resized_file_name = implode('.', [$resolution['name'], $extension]);

        $params = [
            'contents'  => $contents,
            'overwrite' => true,
        ];

        $resized_file_full_path = implode(DIRECTORY_SEPARATOR, [$working_dir, $resized_image_path, $resized_file_name]);
        $storage->put($resized_file_full_path, $params);
    }
}

/**
 * Resizes image and return it's content as blob
 *
 * @param array<string|array<string, string>> $pair_data        Original image data
 * @param array<int>                          $resolution       Target resolution data
 * @param bool                                $crop_when_resize Crop image
 *
 * @return array
 */
function fn_mobile_app_resize_image(array $pair_data, array $resolution, $crop_when_resize = false)
{
    $extension = 'png';

    if (empty($pair_data['detailed']['absolute_path'])
        || empty($resolution['width'])
        || empty($resolution['height'])
    ) {
        return [false, $extension];
    }

    if ($crop_when_resize) {
        list($content, $extension) = fn_mobile_app_resize_with_crop($pair_data, $resolution);
    } else {
        list($content, $extension) = fn_mobile_app_resize_with_borders($pair_data, $resolution);
    }

    return [$content, $extension];
}

/**
 * First resizes image by the smallest side, then crops resized image to desired size
 *
 * @param array $pair_data  Original image data
 * @param array $resolution Target resolution data
 *
 * @return array
 */
function fn_mobile_app_resize_with_crop($pair_data, $resolution)
{
    try {
        $image = Tygh::$app['image']->open($pair_data['detailed']['absolute_path']);

        $original_image_width = $image->getSize()->getWidth();
        $original_image_height = $image->getSize()->getHeight();
        $original_image_proportion = $original_image_width / $original_image_height;

        $new_to_original_width_proportion = $resolution['width'] / $original_image_width;
        $new_to_original_height_proportion = $resolution['height'] / $original_image_height;

        if ($new_to_original_width_proportion < $new_to_original_height_proportion) {
            $new_height = $resolution['height'];
            $new_width = ceil($new_height * $original_image_proportion);
            $crop_point = new Point(
                ($new_width - $resolution['width']) / 2,
                0
            );
        } else {
            $new_width = $resolution['width'];
            $new_height = ceil($new_width / $original_image_proportion);
            $crop_point = new Point(
                0,
                ($new_height - $resolution['height']) / 2
            );
        }

        $content = $image->resize(new Box($new_width, $new_height))
            ->crop($crop_point, new Box($resolution['width'], $resolution['height']))
            ->get('png', [
                'png_compression_level' => 9,
            ]);
    } catch (Exception $e) {
        $content = false;
    }

    return [$content, 'png'];
}

/**
 * First creates desired size blank image, then resizes original image by the smallest side
 * and pastes it to blank image
 *
 * @param array $pair_data  Original image data
 * @param array $resolution Target resolution data
 *
 * @return array
 */
function fn_mobile_app_resize_with_borders($pair_data, $resolution)
{
    try {
        $original_image = Tygh::$app['image']->open($pair_data['detailed']['absolute_path']);

        $original_image_width = $original_image->getSize()->getWidth();
        $original_image_height = $original_image->getSize()->getHeight();

        $original_image_proportion = $original_image_width / $original_image_height;

        $new_to_original_width_proportion = $resolution['width'] / $original_image_width;
        $new_to_original_height_proportion = $resolution['height'] / $original_image_height;

        if ($new_to_original_width_proportion > $new_to_original_height_proportion) {
            $new_height = $resolution['height'];
            $new_width = ceil($new_height * $original_image_proportion);

        } else {
            $new_width = $resolution['width'];
            $new_height = ceil($new_width / $original_image_proportion);
        }

        $bottom_right = new Point(
            abs($resolution['width'] - $new_width) / 2,
            abs($resolution['height'] - $new_height) / 2
        );

        $resized_original = $original_image->resize(new Box($new_width, $new_height));

        $palette = new RGB();
        $color = $palette->color('#FFF', 0);
        $size  = new Box($resolution['width'], $resolution['height']);
        $image = Tygh::$app['image']->create($size, $color);

        $content = $image
            ->paste($resized_original, $bottom_right)
            ->get('png', [
                'png_compression_level' => 9,
            ]);
    } catch (Exception $e) {
        $content = false;
    }

    return [$content, 'png'];
}

/**
 * Resizes icons for ios
 *
 * @param array $image_types_schema Existent image types
 * @param array $image_sizes_schema iOS phone icon sizes
 * @param array $pair_data          Image pair
 * @param bool  $crop_when_resize   Crop image
 *
 * @return void
 */
function fn_mobile_app_resize_ios_icons(array $image_types_schema, array $image_sizes_schema, array $pair_data, $crop_when_resize = false)
{
    $working_dir = fn_mobile_app_get_working_dir();
    $file_path = isset($image_sizes_schema['path']) ? $image_sizes_schema['path'] : '';

    $common_file_name_pattern = isset($image_sizes_schema['name']['file_name'])
        ? $image_sizes_schema['name']['file_name'] : '';
    $common_file_name_pattern_variables = isset($image_sizes_schema['name']['variables'])
        ? $image_sizes_schema['name']['variables'] : array();

    /** @var \Tygh\Backend\Storage\File $storage */
    $storage = Storage::instance('custom_files');

    // add resized images
    foreach ($image_sizes_schema['resolutions'] as $resolution) {
        $width = $resolution['width'];
        $height = $resolution['height'];

        $resized_file_name_pattern = isset($resolution['name']['file_name']) ? $resolution['name']['file_name'] : $common_file_name_pattern;
        $file_name_pattern_variables = isset($resolution['name']['variables']) ? $resolution['name']['variables'] : $common_file_name_pattern_variables;

        foreach ($resolution['scales'] as $scale => $idioms) {
            $resized_file_name = $resized_file_name_pattern;

            foreach ($file_name_pattern_variables as $variable) {

                if (isset(${$variable})) {
                    $resized_file_name = str_replace(implode('', array('%', $variable, '%')), ${$variable}, $resized_file_name);
                }
            }

            list($contents, $extension) = fn_mobile_app_resize_image(
                $pair_data,
                [
                    'width'  => $width * $scale,
                    'height' => $height * $scale,
                ],
                $crop_when_resize
            );

            if (!$contents) {
                continue;
            }

            $resized_file_name = implode('.', array($resized_file_name, $extension));

            $params = array(
                'contents' => $contents,
                'overwrite' => true,
            );

            $resized_file_full_path = implode(DIRECTORY_SEPARATOR, array($working_dir, $file_path, $resized_file_name));
            $storage->put($resized_file_full_path, $params);
        }
    }
}

/**
 * Creates or updates a notification subscription.
 *
 * @param int    $user_id       User identifier
 * @param string $device_id     Device UUID
 * @param string $platform      Device OS
 * @param string $locale        Device locale
 * @param string $token         Firebase Cloud Messaging token
 * @param int    $storefront_id Storefront identifier
 *
 * @return int Subscription ID
 */
function fn_mobile_app_update_notification_subscription($user_id, $device_id, $platform, $locale, $token, $storefront_id = 0)
{
    $params = [
        'user_id'       => $user_id,
        'device_id'     => $device_id,
        'platform'      => $platform,
        'locale'        => $locale,
        'token'         => $token,
        'storefront_id' => $storefront_id,
    ];

    db_replace_into('mobile_app_notification_subscriptions', $params);

    $subscription = fn_mobile_app_get_notification_subscriptions(
        [
            'user_id'       => $user_id,
            'device_id'     => $device_id,
            'platform'      => $platform,
            'storefront_id' => $storefront_id,
        ]
    );

    $subscription = reset($subscription);

    return (int)$subscription['subscription_id'];
}

/**
 * Gets the list of notifications subscriptions.
 *
 * @param array $conditions Conditions that subscriptions must meet to be returned
 *                          @see \Tygh\Database\Connection::process for ?w placeholder
 *
 * @return array
 */
function fn_mobile_app_get_notification_subscriptions($conditions)
{
    $subscriptions = db_get_array('SELECT * FROM ?:mobile_app_notification_subscriptions WHERE ?w', $conditions);

    return $subscriptions;
}

/**
 * Removes notifications subscriptions.
 *
 * @param array $conditions Conditions that subscriptions must meet to be removed
 *                          @see \Tygh\Database\Connection::process for ?w placeholder
 *
 * @return int Number of removed subscriptions
 */
function fn_mobile_app_remove_notification_subscriptions($conditions)
{
    return db_query('DELETE FROM ?:mobile_app_notification_subscriptions WHERE ?w', $conditions);
}

/**
 * Sends push notification to the connected user devices.
 *
 * @param int    $user_id       User identifier
 * @param string $title         Notification title
 * @param string $message       Notification text
 * @param string $target_screen Target screen to open in the mobile app
 * @param int    $storefront_id Storefront identifier
 *
 * @return bool Whether notification have been sent
 */
function fn_mobile_app_notify_user($user_id, $title, $message, $target_screen = '', $storefront_id = 0)
{
    $connected_devices = fn_mobile_app_get_notification_subscriptions(
        [
            'user_id'       => $user_id,
            'storefront_id' => $storefront_id,
        ]
    );

    if (!$connected_devices) {
        return false;
    }

    /** @var \Tygh\Addons\MobileApp\Notifications\Factory $notifications_factory */
    $notifications_factory = Tygh::$app['addons.mobile_app.notifications.factory'];
    /** @var \Tygh\Addons\MobileApp\Notifications\Sender $notifications_sender */
    $notifications_sender = new Sender(
        fn_mobile_app_get_mobile_app_settings($storefront_id),
        new Http()
    );

    if (!$notifications_sender->isSetUp()) {
        return false;
    }

    foreach ($connected_devices as $device_info) {
        try {
            $notification = $notifications_factory->get($device_info['platform'], $title, $message, $target_screen);
            $notifications_sender->addNotification($device_info['token'], $notification);
        } catch (DeveloperException $e) {
            // silently ignore
        }
    }

    return $notifications_sender->send();
}

/**
 * Hook handler: sends push notification on order status change.
 */
function fn_mobile_app_change_order_status(
    $status_to,
    $status_from,
    $order_info,
    $force_notification,
    $order_statuses,
    $place_order
) {
    if (
        $place_order
        || $force_notification === false
        || isset($force_notification[UserTypes::CUSTOMER]) && $force_notification[UserTypes::CUSTOMER] === false
        || empty($order_info['user_id'])
        || $status_to === OrderStatuses::INCOMPLETED
        || $order_info['status'] === OrderStatuses::PARENT
    ) {
        return;
    }

    $order_id = $order_info['order_id'];
    $user_id = $order_info['user_id'];
    $notification_language = isset($order_info['lang_code'])
        ? $order_info['lang_code']
        : CART_LANGUAGE;

    $title = __('change_order_status_default_subj', [
        '[order]' => $order_id,
        '[status]'   => $order_statuses[$status_to]['description'],
    ], $notification_language);
    $message = __('change_order_status_default_text', [
        '[status]'   => $order_statuses[$status_to]['description'],
    ], $notification_language);

    fn_mobile_app_notify_user(
        $user_id,
        $title,
        $message,
        Registry::get('config.customer_index') . '?dispatch=orders.details&order_id=' . $order_id,
        $order_info['storefront_id']
    );
}

/**
 * Fetches full path to archived settings
 *
 * @return string
 */
function fn_mobile_app_get_archive_full_path()
{
    static $archive_path = null;

    if ($archive_path === null) {
        /** @var \Tygh\Backend\Storage\File $storage */
        $storage = Storage::instance('custom_files');
        $archive_path = $storage->getAbsolutePath(SETTINGS_ARCHIVE_FILE_NAME);
    }

    return $archive_path;
}

/**
 * Generates bundle ID based on provided URL
 *
 * @param string $url URL
 *
 * @return string
 */
function fn_mobile_app_generate_bundle_id($url)
{
    $parsed = parse_url($url);
    $dashless_host = str_replace('-', '', $parsed['host']);
    $bundle_id = implode('.', array_reverse(explode('.', $dashless_host)));

    return $bundle_id . '.app';
}

/**
 * Returns Apple Pay supported networks
 *
 * @return array
 */
function fn_mobile_app_get_apple_pay_supported_networks()
{
    return [
        'amex'            => 'American Express',
        'cartesBancaires' => 'Cartes Bancaires',
        'chinaUnionPay'   => 'China UnionPay',
        'discover'        => 'Discover',
        'eftpos'          => 'EFTPOS',
        'electron'        => 'Visa Electron',
        'elo'             => 'EloPar',
        'interac'         => 'Interac',
        'jcb'             => 'JCB',
        'mada'            => 'Mada',
        'maestro'         => 'Maestro',
        'masterCard'      => 'MasterCard',
        'privateLabel'    => 'Private Label',
        'visa'            => 'Visa',
        'vPay'            => 'V Pay',
    ];
}

/**
 * The "delete_image_pre" hook handler.
 *
 * Actions performed:
 * - Updates mobile app add-on settings to initiate cache cleanup.
 *
 * @param int    $image_id    Image identifier
 * @param int    $pair_id     Image pair identifier
 * @param string $object_type Image object type
 */
function fn_mobile_app_delete_image_pre($image_id, $pair_id, $object_type)
{
    if (
        $object_type !== 'detailed'
        || AREA !== SiteArea::ADMIN_PANEL
    ) {
        return;
    }

    $is_mobile_app_icon = (bool) db_get_field(
        'SELECT 1' .
        ' FROM ?:images_links' .
        ' WHERE pair_id = ?i' .
        ' AND detailed_id = ?i' .
        ' AND object_type = ?s',
        $pair_id,
        $image_id,
        'm_app_icon'
    );

    if (!$is_mobile_app_icon) {
        return;
    }

    $mobile_app_config_data = json_decode(Registry::ifGet('addons.mobile_app.config_data', '[]'), true);
    $mobile_app_config_data['modified_time'] = TIME;
    Settings::instance()->updateValue('config_data', json_encode($mobile_app_config_data), 'mobile_app');
}

/**
 * Creates a mobile layout for a new storefront.
 *
 * @param \Tygh\Storefront\Storefront  $storefront  Storefront
 * @param \Tygh\Common\OperationResult $save_result Result of the save process
 */
function fn_mobile_app_storefront_repository_save_post(Storefront $storefront, OperationResult $save_result)
{
    if ($storefront->storefront_id) {
        // exit if this is a storefront update
        return;
    }

    $storefront->storefront_id = $save_result->getData();

    fn_mobile_app_create_mobile_layout($storefront);
}

/**
 * Creates a mobile layout by storefront.
 *
 * @param \Tygh\Storefront\Storefront $storefront Storefront
 */
function fn_mobile_app_create_mobile_layout(Storefront $storefront)
{
    if (fn_allowed_for('MULTIVENDOR')) {
        $layout_path = Registry::get('config.dir.addons') . 'mobile_app/resources/layouts_mve.xml';
    } else {
        $layout_path = Registry::get('config.dir.addons') . 'mobile_app/resources/layouts.xml';
    }

    $theme_name = $storefront->theme_name;
    $storefront_id = $storefront->storefront_id;

    $layout_exists = db_get_field(
        'SELECT layout_id FROM ?:bm_layouts WHERE name = ?s AND theme_name = ?s AND storefront_id = ?i',
        'MobileAppLayout',
        $theme_name,
        $storefront_id
    );

    if ($layout_exists) {
        return;
    }

    $layout_id = Exim::instance(0, 0, $theme_name, $storefront_id)->importFromFile(
        $layout_path,
        [
            'override_by_dispatch' => true,
            'clean_up'             => true,
            'import_style'         => 'create',
        ]
    );

    if (!$layout_id) {
        return;
    }

    $layout_data = Layout::instance(0, [], $storefront_id)->get($layout_id);

    $company_ids = $storefront->getCompanyIds();

    foreach ($company_ids as $company_id) {
        fn_create_theme_logos_by_layout_id($theme_name, $layout_id, $company_id, false, $layout_data['style_id']);
    }
}

/**
 * Generates file content according to the scheme.
 *
 * @param array{path: string, content: string, variables?: array<string|callable>}                                                                                    $file_schema File schema
 * @param array{modified_time: int, promotion: array<string>, app_appearance: array{colors: array<array<string|int>>}, app_settings: array<string|int|array<string>>} $settings    Mobile app add-on settings
 *
 * @return string $content
 */
function fn_mobile_app_generate_file_content_from_settings(array $file_schema, array $settings)
{
    $variables = empty($file_schema['variables']) ? [] : $file_schema['variables'];

    foreach ($variables as $name => $param) {
        if (is_callable($param)) {
            $variables[$name] = $param($settings);
        } else {
            $keys = explode('.', $param);
            $_setting = $settings;

            foreach ($keys as $key) {
                if (!isset($_setting[$key])) {
                    $_setting = '';
                    break;
                }

                $_setting = $_setting[$key];
            }

            $variables[$name] = $_setting;
        }
    }

    return strtr($file_schema['content'], $variables);
}

/**
 * The "storefront_rest_api_get_storefront" hook handler.
 *
 * Actions performed:
 * - Adds mobile app layout ID to the storefront entity response.
 *
 * @param int   $storefront_id Storefront ID
 * @param array $storefront    Storefront data
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_mobile_app_storefront_rest_api_get_storefront($storefront_id, array &$storefront)
{
    $theme_name = fn_get_theme_path('[theme]', SiteArea::STOREFRONT, null, true, $storefront_id);

    $settings = fn_mobile_app_get_mobile_app_settings($storefront_id);

    $storefront[STOREFRONT_FIELD_PROPERTIES]['settings']['general']['termsOfServiceUrl'] = $settings['app_settings']['build']['termsOfServiceUrl'] ?? '';

    $storefront[STOREFRONT_FIELD_PROPERTIES]['layout_id'] = (int) db_get_field(
        'SELECT layout_id FROM ?:bm_layouts WHERE name = ?s AND theme_name = ?s AND storefront_id = ?i',
        'MobileAppLayout',
        $theme_name,
        $storefront_id
    );
}

/**
 * Adds notification about reports on mobile app.
 *
 * @param int    $report_object_id ID of the entity to which the report was sent
 * @param string $report_type      Type of the entity to which the report was sent
 * @param string $message          Report message
 * @param array  $user_data        User data who submitted report
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_mobile_app_add_notification_about_report($report_object_id, $report_type, $message, array $user_data)
{
    $action_url = '';
    $notification_title = '';

    /** @var \Tygh\NotificationsCenter\NotificationsCenter $notifications_center */
    $notifications_center = Tygh::$app['notifications_center'];

    switch ($report_type) {
        case ReportTypes::REPORT_TYPE_PRODUCT:
            $action_url = Url::buildUrn(['products', 'update'], ['product_id' => $report_object_id]);
            $notification_title = __(
                'mobile_app.notification.report.title_product',
                [
                    '[product_name]' => fn_get_product_name($report_object_id, Registry::get('settings.Appearance.backend_default_language'))
                ]
            );

            break;
        case ReportTypes::REPORT_TYPE_USER:
            $action_url = Url::buildUrn(['profiles', 'update'], ['user_id' => $report_object_id]);
            $notification_title = __(
                'mobile_app.notification.report.title_user',
                [
                    '[user_name]' => fn_get_user_name($report_object_id)
                ]
            );

            break;
        case ReportTypes::REPORT_TYPE_VENDOR:
            $action_url = Url::buildUrn(['companies', 'update'], ['company_id' => $report_object_id]);
            $notification_title = __(
                'mobile_app.notification.report.title_vendor',
                [
                    '[vendor_name]' => fn_get_company_name($report_object_id),
                ]
            );

            break;
        case ReportTypes::REPORT_TYPE_DISCUSSION:
            $action_url = Url::buildUrn(['discussion_manager', 'manage']);
            $notification_title = __(
                'mobile_app.notification.report.title_review',
                [
                    '[review_id]' => $report_object_id,
                ]
            );

            break;
        case ReportTypes::REPORT_TYPE_REVIEW:
            $action_url = Url::buildUrn(['product_reviews', 'update'], ['product_review_id' => $report_object_id]);
            $notification_title = __(
                'mobile_app.notification.report.title_review',
                [
                    '[review_id]' => $report_object_id,
                ]
            );

            break;
    }

    list($root_admins,) = fn_get_users(
        [
            'is_root'   => YesNo::YES,
            'user_type' => UserTypes::ADMIN,
        ],
        Tygh::$app['session']['auth']
    );

    foreach ($root_admins as $root_admin) {
        if (!$root_admin['company_id']) {
            $notifications_center->add([
                'user_id'       => $root_admin['user_id'],
                'title'         => $notification_title,
                'message'       => __(
                    'mobile_app.notification.report.message',
                    [
                        '[profile_url]'    => fn_url('profiles.update?user_id=' . $user_data['user_id'], SiteArea::ADMIN_PANEL),
                        '[user_id]'        => $user_data['user_id'],
                        '[user_email]'     => fn_get_user_email($user_data['user_id']),
                        '[report_message]' => $message
                    ]
                ),
                'area'          => SiteArea::ADMIN_PANEL,
                'section'       => NotificationsCenter::SECTION_COMMUNICATION,
                'tag'           => 'mobile_app.event.new_report',
                'action_url'    => $action_url,
                'language_code' => Registry::get('settings.Appearance.backend_default_language'),
            ]);

            break;
        }
    }
}
