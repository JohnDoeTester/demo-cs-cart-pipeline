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

use Tygh\Addons\SchemesManager;
use Tygh\Addons\XmlScheme3;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\YesNo;
use Tygh\Languages\Languages;
use Tygh\Providers\MarketplaceProvider;
use Tygh\Providers\StorefrontProvider;
use Tygh\Registry;
use Tygh\Settings;
use Tygh\Snapshot;
use Tygh\Tools\Url;
use Tygh\Tygh;
use Tygh\UpgradeCenter\App;

defined('BOOTSTRAP') or die('Access denied');

/** @var string $mode */
/** @var string $action */
/** @var array $auth */

/** @var \Tygh\SmartyEngine\Core $view */
$view = Tygh::$app['view'];

$auth = Tygh::$app['session']['auth'];

$storefront_id = empty($_REQUEST['storefront_id'])
    ? 0
    : (int) $_REQUEST['storefront_id'];

if (fn_allowed_for('ULTIMATE')) {
    $storefront_id = 0;
    if (fn_get_runtime_company_id()) {
        $storefront_id = StorefrontProvider::getStorefront()->storefront_id;
    }
}

if (
    fn_allowed_for('MULTIVENDOR:ULTIMATE')
    && !empty($auth['storefront_id'])
) {
    $storefront_id = (int) $auth['storefront_id'];
}

$client = MarketplaceProvider::getClient();

$is_storefront_admin = fn_allowed_for('ULTIMATE') && $auth['company_id'] !== 0;
$view->assign('is_storefront_admin', $is_storefront_admin);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($is_storefront_admin && !in_array($mode, ['update', 'send_message', 'set_favorite'], true)) {
        return [CONTROLLER_STATUS_DENIED];
    }

    /** @var string $dispatch_extra */
    $dispatch_extra = isset($dispatch_extra)
        ? $dispatch_extra
        : '';

    fn_trusted_vars(
        'addon_data'
    );

    $redirect_url = !empty($_REQUEST['return_url'])
        ? $_REQUEST['return_url']
        : 'addons.manage';

    if ($mode === 'update') {
        $addon_scheme = SchemesManager::getScheme($_REQUEST['addon']);

        if ($addon_scheme === false || $addon_scheme->isPromo()) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        if (isset($_REQUEST['addon_data'])) {
            fn_update_addon($_REQUEST['addon_data'], $storefront_id);
        }

        if (isset($_REQUEST['marketplace_license_key'])) {
            fn_update_addon_license_key($_REQUEST['addon'], $_REQUEST['marketplace_license_key']);
        }

        $redirect_url_params = [
            'addon' => $_REQUEST['addon'],
        ];

        if (fn_allowed_for('MULTIVENDOR') && $storefront_id) {
            $redirect_url_params['storefront_id'] = $storefront_id;
        }

        if (!empty($_REQUEST['selected_sub_section'])) {
            $redirect_url_params['selected_sub_section'] = $_REQUEST['selected_sub_section'];
        }

        return [
            CONTROLLER_STATUS_OK,
            Url::buildUrn(['addons', 'update'], $redirect_url_params),
        ];
    }
    if ($mode === 'set_favorite') {
        if (!empty($_REQUEST['addon']) && !empty($_REQUEST['favorite'])) {
            $result = fn_update_addon_favorite_status($_REQUEST['addon'], $_REQUEST['favorite']);
            if ($result) {
                $set = YesNo::toBool($_REQUEST['favorite']);
                $message = $set ? __('addon_added_to_favorites') : __('addon_removed_from_favorites');
                fn_set_notification(NotificationSeverity::NOTICE, __('notice'), $message);
            }
        }

        if (defined('AJAX_REQUEST')) {
            if (empty($_REQUEST['detailed'])) {
                [$addons,] = fn_get_addons([], 0, CART_LANGUAGE, $storefront_id, fn_get_runtime_company_id());
                $view->assign('addons_list', $addons);
                $view->display('design/backend/templates/views/addons/components/addons_list.tpl');
            } else {
                [$addon_info,] = fn_get_addons(['name' => $_REQUEST['addon']]);
                $view->assign('addon', reset($addon_info));
                $view->display('design/backend/templates/views/addons/update.tpl');
            }
            return [CONTROLLER_STATUS_NO_CONTENT];
        }
    }
    if ($mode === 'send_message') {
        if (!empty($_REQUEST['marketplace_id']) && !empty($_REQUEST['text'])) {
            $result = $client->sentDeveloperMessage(['marketplace_id' => $_REQUEST['marketplace_id'], 'text' => $_REQUEST['text']]);
            if ($result->isSuccess()) {
                fn_set_notification(
                    NotificationSeverity::NOTICE,
                    __('notice'),
                    __('message_successfully_sent_to_developer', ['[developer]' => $_REQUEST['addon_supplier']])
                );
            } else {
                $result->showNotifications();
            }
        }
    }
    if ($mode === 'recheck') {
        $addon_name = $_REQUEST['addon_name'];
        $addon_extract_path = $_REQUEST['addon_extract_path'];
        $source = Registry::get('config.dir.root') . '/' . $addon_extract_path;
        $destination = Registry::get('config.dir.root');

        if (!file_exists($source) || !fn_validate_addon_structure($addon_name, $source)) {
            fn_set_notification(
                NotificationSeverity::ERROR,
                __('error'),
                __('broken_addon_pack')
            );

            if (defined('AJAX_REQUEST')) {
                Tygh::$app['ajax']->assign('non_ajax_notifications', true);
                Tygh::$app['ajax']->assign('force_redirection', fn_url($redirect_url));
                exit();
            }

            return [CONTROLLER_STATUS_REDIRECT, $redirect_url];
        }

        if ($action === 'ftp_upload') {
            $ftp_access = [
                'hostname'  => $_REQUEST['ftp_access']['ftp_hostname'],
                'username'  => $_REQUEST['ftp_access']['ftp_username'],
                'password'  => $_REQUEST['ftp_access']['ftp_password'],
                'directory' => $_REQUEST['ftp_access']['ftp_directory'],
            ];

            if ($dispatch_extra === 'uninstall') {
                fn_uninstall_addon($addon_name, false, true);
            }

            $ftp_install_result = __('cant_remove_addon_files');
            if (fn_remove_addon_files($addon_name, $ftp_access)) {
                $ftp_install_result = fn_copy_by_ftp($source, $destination, $ftp_access);
            }

            if ($ftp_install_result === true && fn_check_addon_exists($addon_name)) {
                if (fn_reinstall_addon_files($addon_name)) {
                    fn_set_notification('N', __('notice'), __('addon_files_was_copied', [
                        '[addon]' => $addon_name
                    ]));
                }
            } elseif ($ftp_install_result === true) {
                fn_install_addon($addon_name);
            } else {
                fn_set_notification(
                    NotificationSeverity::ERROR,
                    __('error'),
                    $ftp_install_result
                );
            }

            if (defined('AJAX_REQUEST')) {
                Tygh::$app['ajax']->assign('non_ajax_notifications', true);
                Tygh::$app['ajax']->assign('force_redirection', fn_url($redirect_url));
                exit();
            }

            return [CONTROLLER_STATUS_OK, $redirect_url];
        }

        $non_writable_folders = fn_check_copy_ability($source, $destination);

        if (!empty($non_writable_folders)) {
            if (!empty($_REQUEST['ftp_access'])) {
                $view->assign('ftp_access', $_REQUEST['ftp_access']);
            }

            $view->assign([
                'non_writable' => $non_writable_folders,
                'return_url'   => $redirect_url,
            ]);

            if (defined('AJAX_REQUEST')) {
                $view->assign([
                    'addon_name'         => $addon_name,
                    'addon_extract_path' => $addon_extract_path,
                    'dispatch_extra'     => $dispatch_extra,
                ]);
                $view->display('views/addons/components/correct_permissions.tpl');
                exit();
            }
        } else {
            if ($dispatch_extra === 'uninstall') {
                fn_uninstall_addon($addon_name, false, true);
            }

            fn_addons_move_and_install($source, Registry::get('config.dir.root'));

            if (defined('AJAX_REQUEST')) {
                Tygh::$app['ajax']->assign('force_redirection', fn_url($redirect_url));
                exit();
            }
        }
    }
    if ($mode === 'upload') {
        if (defined('RESTRICTED_ADMIN') || Registry::get('runtime.company_id')) {
            fn_set_notification(
                NotificationSeverity::ERROR,
                __('error'),
                __('access_denied')
            );

            return [CONTROLLER_STATUS_REDIRECT, $redirect_url];
        }

        $addon_pack = fn_filter_uploaded_data('addon_pack', Registry::get('config.allowed_pack_exts'));
        $addon_pack = reset($addon_pack);

        if (!$addon_pack) {
            fn_set_notification(
                NotificationSeverity::ERROR,
                __('error'),
                __('text_allowed_to_upload_file_extension', [
                    '[ext]' => implode(',', Registry::get('config.allowed_pack_exts'))
                ])
            );
        } else {
            $tmp_path = fn_get_cache_path(false) . 'tmp/';
            $addon_file = $tmp_path . $addon_pack['name'];

            fn_mkdir($tmp_path);
            fn_copy($addon_pack['path'], $addon_file);

            $addon_pack_result = fn_extract_addon_package($addon_file);

            fn_rm($addon_file);

            if ($addon_pack_result) {
                [$addon_name, $extract_path] = $addon_pack_result;

                if (fn_validate_addon_structure($addon_name, $extract_path)) {
                    $view->assign([
                        'addon_extract_path' => fn_get_rel_dir($extract_path),
                        'addon_name'         => $addon_name,
                        'return_url'         => $redirect_url,
                    ]);

                    if (Registry::get("addons.{$addon_name}.status") && defined('AJAX_REQUEST')) {
                        $view->display('views/addons/components/reinstall.tpl');
                        exit();
                    }

                    $non_writable_folders = fn_check_copy_ability($extract_path, Registry::get('config.dir.root'));
                    if (!empty($non_writable_folders)) {
                        $view->assign('non_writable', $non_writable_folders);

                        if (defined('AJAX_REQUEST')) {
                            $view->display('views/addons/components/correct_permissions.tpl');
                            exit();
                        }
                    } else {
                        fn_addons_move_and_install($extract_path, Registry::get('config.dir.root'));

                        if (defined('AJAX_REQUEST')) {
                            Tygh::$app['ajax']->assign('force_redirection', fn_url($redirect_url));
                            exit();
                        }
                    }
                }
            }

            fn_set_notification(
                NotificationSeverity::ERROR,
                __('error'),
                __('broken_addon_pack')
            );

            if (defined('AJAX_REQUEST')) {
                Tygh::$app['ajax']->assign('non_ajax_notifications', true);
                Tygh::$app['ajax']->assign('force_redirection', fn_url($redirect_url));
                exit();
            }

            return [CONTROLLER_STATUS_REDIRECT, $redirect_url];
        }

        if (defined('AJAX_REQUEST')) {
            $view->display('views/addons/components/upload_addon.tpl');

            exit();
        }
    }
    if ($mode === 'set_rating') {
        if (empty($_REQUEST['value']) || empty($_REQUEST['marketplace_id']) || empty($_REQUEST['message']) || empty($_REQUEST['redirect_url'])) {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), __('addons.error_at_sending_addon_review'));
            return [CONTROLLER_STATUS_REDIRECT, $redirect_url];
        }
        $id = $_REQUEST['marketplace_id'];
        $value = $_REQUEST['value'];
        $message = $_REQUEST['message'];
        $redirect_url = $_REQUEST['redirect_url'];
        $result = $client->setProductReview($id, $value, $message);

        if ($result->isSuccess()) {
            fn_set_notification(NotificationSeverity::NOTICE, __('addons.text_thank_you_for_post'), __('addons.text_post_pended'));
        } else {
            $result->showNotifications();
        }
        return [CONTROLLER_STATUS_REDIRECT, $redirect_url];
    }
    if ($mode === 'licensing') {  // Used for saving add-on license key to the DB
        if (!isset($_REQUEST['addon'], $_REQUEST['redirect_url'], $_REQUEST['marketplace_license_key'])) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        $addon_id = $_REQUEST['addon'];
        $redirect_url = $_REQUEST['redirect_url'];
        $license_key = $_REQUEST['marketplace_license_key'];

        $addon_data = db_get_row(
            'SELECT * FROM ?:addons AS a'
            . ' WHERE a.addon = ?s'
            . ' AND a.unmanaged <> 1'
            . ' AND a.marketplace_id IS NOT NULL',
            $addon_id
        );

        if (empty($addon_data)) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        fn_update_addon_license_key($addon_id, $license_key);

        fn_set_notification(
            NotificationSeverity::NOTICE,
            __('notice'),
            __('text_changes_saved')
        );

        // Redirect browser back
        if (defined('AJAX_REQUEST')) {
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            Tygh::$app['ajax']->assign('force_redirection', $redirect_url);
        } else {
            return [CONTROLLER_STATUS_REDIRECT, $redirect_url];
        }

        exit;
    }

    if ($mode === 'update_status') {
        $is_addon_disabled = fn_is_addon_init_disabled($_REQUEST['id']);
        if ($is_addon_disabled) {
            return [CONTROLLER_STATUS_REDIRECT, $redirect_url];
        }

        $is_snapshot_correct = fn_check_addon_snapshot($_REQUEST['id']);
        if (!$is_snapshot_correct) {
            $status = false;
        } else {
            $status = fn_update_addon_status($_REQUEST['id'], $_REQUEST['status']);
        }

        if ($status !== true) {
            Tygh::$app['ajax']->assign('return_status', $status);
        }
        Registry::clearCachedKeyValues();

        return [CONTROLLER_STATUS_REDIRECT, $redirect_url];
    }

    if ($mode === 'install') {
        fn_install_addon($_REQUEST['addon']);
        Registry::clearCachedKeyValues();

        return [CONTROLLER_STATUS_REDIRECT, $redirect_url];
    }

    if ($mode === 'uninstall') {
        fn_uninstall_addon($_REQUEST['addon']);

        return [CONTROLLER_STATUS_REDIRECT, $redirect_url];
    }

    if ($mode === 'tools') {
        if (Snapshot::exist()) {
            $init_addons = !empty($_REQUEST['init_addons']) ? $_REQUEST['init_addons'] : '';

            if ($init_addons !== 'none' && $init_addons !== 'core' && $init_addons !== 'restore') {
                $init_addons = '';
            }

            Settings::instance()->updateValue('init_addons', $init_addons);
            fn_clear_cache();
        } else {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), __('tools_snapshot_not_found'));
        }

        return [CONTROLLER_STATUS_REDIRECT, $redirect_url];
    }

    if ($mode === 'refresh') {
        if (!empty($_REQUEST['addon'])) {
            $addon_id = $_REQUEST['addon'];
            $addon_scheme = SchemesManager::getScheme($addon_id);

            fn_update_addon_language_variables($addon_scheme);

            $setting_values = [];
            $settings_values = fn_get_addon_settings_values($addon_id);
            $settings_vendor_values = fn_get_addon_settings_vendor_values($addon_id);

            $update_addon_settings_result = fn_update_addon_settings(
                $addon_scheme,
                true,
                $settings_values,
                $settings_vendor_values
            );

            fn_clear_cache();
            Registry::clearCachedKeyValues();

            if ($update_addon_settings_result) {
                fn_set_notification(
                    NotificationSeverity::NOTICE,
                    __('notice'),
                    __('text_addon_refreshed', [
                        '[addon]' => $addon_id,
                    ])
                );
            }

            return [CONTROLLER_STATUS_REDIRECT, $redirect_url];
        }
    }

    return [CONTROLLER_STATUS_OK, $redirect_url];
}

if ($mode === 'update') {
    $addon_scheme = SchemesManager::getScheme($_REQUEST['addon']);

    if ($addon_scheme === false) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $settings_manager = Settings::instance(['storefront_id' => $storefront_id]);

    $view->assign('selected_storefront_id', $storefront_id);

    $tabs = [
        'detailed' => [
            'title' => __('general'),
            'js'    => true,
        ],
    ];
    $select_storefront = false;

    $section = $settings_manager->getSectionByName($_REQUEST['addon'], Settings::ADDON_SECTION);
    if (!empty($section)) {
        $has_options = $settings_manager->optionsExists($_REQUEST['addon'], 'ADDON');
        if ($has_options) {
            $tabs['settings'] = [
                'title' => __('settings'),
                'js' => true,
            ];
        }
        if (
            !empty($section['edition_type'])
            && $settings_manager->areOverriddenValuesSupportedByEdition($section['edition_type'], true)
        ) {
            $select_storefront = true;
        }
        $subsections = $settings_manager->getSectionTabs($section['section_id'], CART_LANGUAGE);
        $options = $settings_manager->getList($section['section_id']);
        $view->assign([
            'options' => $options,
            'subsections' => $subsections,
        ]);
        fn_update_lang_objects('sections', $subsections);
        fn_update_lang_objects('options', $options);
    } elseif (fn_allowed_for('MULTIVENDOR') && $storefront_id !== 0) {
        return [
            CONTROLLER_STATUS_REDIRECT,
            fn_url('addons.update?addon=' . $_REQUEST['addon']),
        ];
    }

    $view->assign([
        'select_storefront' => $select_storefront,
    ]);
    $tabs['information'] = [
        'title' => __('information'),
        'js'    => true,
    ];

    $installed_addon_info = db_get_row(
        'SELECT a.addon, a.status, b.name as name, b.description as description, a.version, a.install_datetime'
        . ' FROM ?:addons as a'
        . ' LEFT JOIN ?:addon_descriptions as b ON b.addon = a.addon AND b.lang_code = ?s WHERE a.addon = ?s'
        . ' ORDER BY b.name ASC',
        CART_LANGUAGE,
        $_REQUEST['addon']
    );
    if ($installed_addon_info) {
        $view->assign(
            [
                'addon_install_datetime' => $installed_addon_info['install_datetime'],
                'addon_name'             => $installed_addon_info['name'],
            ]
        );
    }

    [$addon_info,] = fn_get_addons(['name' => $_REQUEST['addon'], 'get_marketplace_info' => true]);
    $addon_info = reset($addon_info);
    if (isset($addon_info['category'])) {
        $view->assign(
            'addon_category_url',
            fn_url(
                Registry::get('config.resources.marketplace_url')
                . '?'
                . http_build_query(['dispatch' => 'categories.view', 'category_id' => $addon_info['category']])
            )
        );
    }
    if ($addon_info['is_core_addon']) {
        $addon_info['support'] = fn_get_addons_support_links();
    }

    if ($addon_scheme instanceof XmlScheme3) {
        $marketplace_product_id = $addon_scheme->getMarketplaceProductID();
    }
    if (isset($marketplace_product_id)) {
        $addon_info['marketplace_id'] = $marketplace_product_id;
        $view->assign(
            'addon_marketplace_page',
            fn_url(
                Registry::get('config.resources.marketplace_url')
                . '?' . http_build_query(['dispatch' => 'products.view', 'product_id' => $marketplace_product_id])
            )
        );
        $view->assign(
            'addon_reviews_url',
            fn_url(
                Registry::get('config.resources.marketplace_url')
                . '?'
                . http_build_query(
                    [
                        'dispatch' => 'products.view',
                        'product_id' => $marketplace_product_id,
                        'selected_section' => 'discussion'
                    ]
                )
                . '#content_discussion'
            )
        );
        $product_params = [
            'ver'             => $addon_info['version'],
            'product_version' => PRODUCT_VERSION,
            'edition'         => PRODUCT_EDITION,
            'sl'              => isset($_REQUEST['sl']) ? $_REQUEST['sl'] : CART_LANGUAGE,
        ];
        if (isset($addon_info['marketplace_license_key'])) {
            $product_params['license_number'] = $addon_info['marketplace_license_key'];
        }
        $product = $client->getProduct($marketplace_product_id, $product_params);
        if (!empty($product)) {
            $product = reset($product);
            $addon_info['marketplace'] = $product;
        }
        if (!empty($product['links'])) {
            $addon_info['support'] = $product['links'];
        }
        if (!empty($product['reviews'])) {
            $review_stats = fn_get_addons_review_stats($product['reviews']);
            $view->assign(
                [
                    'reviews'                    => $product['reviews'],
                    'addon_reviews_rating_stats' => $review_stats,
                ]
            );
        }
        if (isset($product['personal_review'])) {
            $view->assign('personal_review', $product['personal_review']);
        }
        if (!empty($product['license'])) {
            $view->assign('license_expires', $product['license']['expires_at']);
        }
        if (isset($product['average_rating'])) {
            $view->assign('average_rating', $product['average_rating']);
        }
        if ($installed_addon_info) {
            if (isset($product['actual_package']) || isset($product['latest_upgrade_package'])) {
                $tabs['upgrades'] = [
                    'title' => __('addons.upgrades'),
                    'js' => true,
                ];
            }
            $tabs['subscription'] = [
                'title' => __('license'),
                'js' => true,
            ];
        }
        if (isset($product['actual_package'])) {
            $latest_version = $product['actual_package'];
            $actual_change_log = [
                'version' => $latest_version['file_name'],
            ];
            if (isset($latest_version['readme'])) {
                $actual_change_log['readme'] = $latest_version['readme'];
            }
            if (isset($latest_version['available_since'])) {
                $actual_change_log['available_since'] = $latest_version['available_since'];
            }
            if (isset($latest_version['timestamp'])) {
                $actual_change_log['timestamp'] = $latest_version['timestamp'];
            }
            if (isset($latest_version['compatibility'])) {
                $actual_change_log['compatibility'] = in_array(PRODUCT_VERSION, $latest_version['compatibility'])
                    ? true
                    : end($latest_version['compatibility']);
            }
            $view->assign('actual_change_log', $actual_change_log);
        }
        if (isset($product['latest_upgrade_package']) && isset($product['latest_upgrade_package']['file_name'])) {
            $latest_change_log = [
                'readme'          => isset($product['latest_upgrade_package']['readme']) ? $product['latest_upgrade_package']['readme'] : '',
                'version'         => $product['latest_upgrade_package']['file_name'],
                'available_since' => isset($product['latest_upgrade_package']['available_since']) ? $product['latest_upgrade_package']['available_since'] : '',
                'timestamp'       => isset($product['latest_upgrade_package']['timestamp']) ? $product['latest_upgrade_package']['timestamp'] : '',
                'compatibility'   => in_array(PRODUCT_VERSION, $product['latest_upgrade_package']['compatibility'])
                    ? true
                    : end($product['latest_upgrade_package']['compatibility']),
            ];
            $view->assign('latest_change_log', $latest_change_log);
        }
        if (isset($product['current_package'])) {
            $product['current_package']['compatibility'] =
                in_array(PRODUCT_VERSION, $product['current_package']['compatibility'])
                    ? true
                    : end($product['current_package']['compatibility']);
            $view->assign('current_package', $product['current_package']);
        }
        if (isset($product['product'])) {
            $product = $product['product'];
            if (isset($product['company_id'])) {
                $view->assign(
                    'addon_developer_url',
                    fn_url(
                        Registry::get('config.resources.marketplace_url')
                        . '?'
                        . http_build_query(['dispatch' => 'companies.view', 'company_id' => $product['company_id']])
                    )
                );
            }
            if (isset($product['product_features'])) {
                if (isset($product['product_features']['COMPATIBLE_VERSIONS'])) {
                    $compatibility = $product['product_features']['COMPATIBLE_VERSIONS'];
                }
                if (isset($compatibility)) {
                    $is_version_compare = in_array(PRODUCT_VERSION, array_column($compatibility['variants'], 'variant'), true);
                    $view->assign([
                        'version_compare' => $is_version_compare,
                        'compatibility' => $compatibility['variant'],
                    ]);
                }

                if (isset($product['product_features']['LANGUAGES']['variants'])) {
                    $languages = $product['product_features']['LANGUAGES']['variants'];
                    $view->assign('addon_languages', $languages);
                }
                if (isset($product['product_features']['EDITIONS']['variants'])) {
                    $support_editions = $product['product_features']['EDITIONS']['variants'];
                    $view->assign('support_editions', $support_editions);
                }
            }
        }

        $tabs['reviews'] = [
            'title' => __('addon_reviews'),
            'js' => true,
        ];
    } else {
        $view->assign('addon_languages', Languages::getSimpleLanguages());
    }
    Registry::set('navigation.tabs', $tabs);
    $init_addons = Settings::instance()->getValue('init_addons', '');
    $is_init_set = $init_addons === 'none' || $init_addons === 'restore' || $init_addons === 'core';
    if (($is_init_set) && $override_addons = Registry::get('addons')) {
        $addon_name = $addon_info['addon'];
        if (array_key_exists($addon_name, $override_addons)) {
            $addon_info['status'] = $override_addons[$addon_name]['status'];
        }
    }

    $view->assign([
        'addon_version'          => $addon_info['version'],
        'addon_supplier'         => $addon_scheme->getSupplier(),
        'addon_supplier_link'    => $addon_scheme->getSupplierLink(),
        'addon_developer_page'   => $addon_scheme->getSupplierPage($addon_info['status']),
        'addon'                  => $addon_info,
    ]);
} elseif ($mode === 'manage') {
    [$addons, $search, $addons_counter] = fn_get_addons(
        [],
        0,
        CART_LANGUAGE,
        $storefront_id,
        fn_get_runtime_company_id()
    );
    $all_suppliers = fn_get_addon_suppliers($addons);

    if (!empty($_REQUEST)) {
        $params = $_REQUEST;
        $params['for_company'] = (bool) Registry::get('runtime.company_id');
        $params['get_marketplace_info'] = true;

        [$addons, $search, $addons_counter] = fn_get_addons(
            $params,
            0,
            CART_LANGUAGE,
            $storefront_id,
            fn_get_runtime_company_id()
        );
    }

    $app = App::instance();
    $upgrade_packages = $app->getUpgradePackagesWithCache();

    Registry::set(
        'navigation.dynamic.sections.manage',
        [
            'title' => __('downloaded_addons', [count($addons)]),
            'href'  => 'addons.manage',
        ]
    );
    Registry::set(
        'navigation.dynamic.sections.upgrades',
        [
            'title' => __('upgrades', [count(array_column($addons, 'upgrade_available')) + (!empty($upgrade_packages['core']['core']) ? 1 : 0)]),
            'href'  => 'upgrade_center.manage',
        ]
    );
    Registry::set(
        'navigation.dynamic.sections.market',
        [
            'title' => __('addon_market'),
            'href'  => 'addons.market',
        ]
    );

    Registry::set('navigation.dynamic.active_section', $mode);
    $categories = $client->getCategories();
    if ($categories) {
        $view->assign('category_tree', $categories);
        if (isset($_REQUEST['category_id'])) {
            $active_category_id = (int) $_REQUEST['category_id'];
            $active_category_ids = fn_get_addons_active_category_ids($categories, $active_category_id);
            $view->assign('active_category_id', $_REQUEST['category_id']);
            $view->assign('active_category_ids', $active_category_ids);
        }
    }
    $versions = $client->getProductVersions();
    if ($versions) {
        $view->assign('versions', $versions);
    }

    $init_addons = Settings::instance()->getValue('init_addons', '');
    $is_init_set = $init_addons === 'none' || $init_addons === 'restore' || $init_addons === 'core';
    if (($is_init_set) && $override_addons = Registry::get('addons')) {
        foreach ($override_addons as $addon_name => $addon) {
            if (array_key_exists($addon_name, $addons)) {
                $addons[$addon_name]['status'] = $addon['status'];
                $addons[$addon_name]['is_disabled'] = $addon['is_disabled'];
            }
        }
    }
    $view->assign([
        'search'                 => $search,
        'addons_list'            => $addons,
        'addons_counter'         => $addons_counter,
        'snapshot_exist'         => Snapshot::exist(),
        'developers'             => $all_suppliers,
        'selected_storefront_id' => $storefront_id,
    ]);
} elseif ($mode === 'licensing') {
    if (empty($_REQUEST['addon'])) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $addon_id = $_REQUEST['addon'];
    $redirect_url = isset($_REQUEST['return_url']) ? $_REQUEST['return_url'] : null;

    $addon_data = db_get_row(
        'SELECT * FROM ?:addons AS a'
        . ' WHERE a.addon = ?s'
        . ' AND a.unmanaged <> 1'
        . ' AND a.marketplace_id IS NOT NULL',
        $addon_id
    );

    if (empty($addon_data)) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $view
        ->assign('addon_data', $addon_data)
        ->assign('redirect_url', $redirect_url);
} elseif ($mode === 'market') {
    $q = $_REQUEST['q'] ?? '';
    $lang_code = isset($_REQUEST['lang_code']) ? $_REQUEST['lang_code'] : CART_LANGUAGE;
    $addons = MarketplaceProvider::getClient()->getSearchProducts(['q' => $q, 'lang_code' => $lang_code]);

    foreach ($addons as &$addon) {
        $_addon = [
            'addon'                        => !empty($addon['product_code']) ? $addon['product_code'] : $addon['product_id'],
            'status'                       => 'M',
            'name'                         => $addon['title'],
            'supplier'                     => $addon['company_name'],
            'identified'                   => true,
            'description'                  => $addon['description'],
            'addon_marketplace_image'      => $addon['image_link'],
            'addon_marketplace_page'       => $addon['link'],
            'addon_marketplace_price'      => $addon['price'],
            'addon_marketplace_list_price' => (float) $addon['list_price'],
            'marketplace_product_id'       => $addon['product_id'],
            'currency'                     => $addon['currency'],
        ];
        $addon = $_addon;
    }
    unset($addon);

    Registry::set(
        'navigation.dynamic.sections.manage',
        [
            'title' => __('downloaded_add_ons'),
            'href'  => 'addons.manage',
        ]
    );
    Registry::set(
        'navigation.dynamic.sections.upgrades',
        [
            'title' => __('addons.upgrades'),
            'href'  => 'upgrade_center.manage',
        ]
    );
    Registry::set(
        'navigation.dynamic.sections.market',
        [
            'title' => __('addon_market_n', [count($addons)]),
            'href'  => 'addons.market',
        ]
    );
    Registry::set('navigation.dynamic.active_section', $mode);

    Tygh::$app['view']->assign([
        'addons_list' => $addons,
        'search'      => ['q' => $q],
    ]);
}
