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

use Tygh\Development;
use Tygh\Registry;
use Tygh\Settings;
use Tygh\SoftwareProductEnvironment;
use Tygh\UpgradeCenter\App as UpgradeCenter;
use Tygh\UpgradeCenter\Log;
use Tygh\UpgradeCenter\Console\Command\UpgradeCommand;
use Tygh\UpgradeCenter\Console\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Application as ConsoleApplication;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (in_array($mode, array('upgrade', 'revert'))) {
    // temporary set development mode, for full error displaying
    Development::enable('compile_check');
}

$app = UpgradeCenter::instance();

$custom_theme_files = array();

$skip_files = array(
    'manifest.json'
);

$backend_files = array(
    'admin_index' => 'admin.php',
    'vendor_index' => 'vendor.php',
);

$uc_settings = Settings::instance()->getValues('Upgrade_center');

// If we're performing the update, check if upgrade center override controller is exist in the package
if (!empty(Tygh::$app['session']['uc_package']) && file_exists(Registry::get('config.dir.upgrade') . Tygh::$app['session']['uc_package'] . '/uc_override.php')) {
    return include(Registry::get('config.dir.upgrade') . Tygh::$app['session']['uc_package'] . '/uc_override.php');
}

if (defined('CONSOLE') && CONSOLE) {
    if ($mode == 'console') {
        $application = new ConsoleApplication();
        $application->addCommands(array(new UpgradeCommand($app)));

        $input = new ArgvInput();
        $output = new ConsoleOutput();

        Registry::set('runtime.console.output', $output);
        Registry::set('config.upgrade_log.output_message', true);

        if ($input->getFirstArgument() === 'help' || isset($_REQUEST['help'])) {
            // This is a hack to correct command help.
            $_SERVER['PHP_SELF'] = $_SERVER['PHP_SELF'] . ' --dispatch=' . $_REQUEST['dispatch'];
        }

        $application->run($input, $output);
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode == 'update_settings') {
        if (!empty($_REQUEST['settings_data'])) {
            foreach ($_REQUEST['settings_data'] as $setting_name => $setting_value) {
                Settings::instance()->updateValue($setting_name, $setting_value, 'Upgrade_center');
            }
        }
    }

    if ($mode == 'download') {
        $app->downloadPackage($_REQUEST['id']);

        $return_url = !empty($_REQUEST['return_url']) ? $_REQUEST['return_url'] : 'upgrade_center.manage';
        return [CONTROLLER_STATUS_REDIRECT, $return_url];
    }

    if ($mode == 'upload') {
        $upgrade_pack = fn_filter_uploaded_data('upgrade_pack', Registry::get('config.allowed_pack_exts'));

        if (empty($upgrade_pack[0])) {
            fn_set_notification('E', __('error'), __('text_allowed_to_upload_file_extension', array('[ext]' => implode(',', Registry::get('config.allowed_pack_exts')))));
        } else {
            $upgrade_pack = $upgrade_pack[0];
            $app->uploadUpgradePack($upgrade_pack);
        }

        return array(CONTROLLER_STATUS_REDIRECT, 'upgrade_center.manage');

    }

    if ($mode == 'install') {
        $return_url = !empty($_REQUEST['return_url']) ? $_REQUEST['return_url'] : 'upgrade_center.manage';

        if (!empty($_REQUEST['change_ftp_settings'])) {
            Log::instance($_REQUEST['id'])->add('Update FTP connection settings');

            foreach ($_REQUEST['change_ftp_settings'] as $setting_name => $value) {
                Settings::instance()->updateValue($setting_name, $value, '', true);
                Registry::set('settings.Upgrade_center.' . $setting_name, $value);
            }
        }

        $app->perform_backup = empty($_REQUEST['skip_backup']) || $_REQUEST['skip_backup'] != 'Y';

        $validators = new \Tygh\Validators();
        if (!$validators->isZipArchiveAvailable() && $app->perform_backup) {
            fn_set_notification('E', __('error'),
                __('error_unable_to_create_backups') . PHP_EOL . __('error_zip_php_extension_not_installed')
            );

            if (defined('AJAX_REQUEST')) {
                Tygh::$app['ajax']->assign('non_ajax_notifications', true);
                Tygh::$app['ajax']->assign(
                    'force_redirection',
                    !empty($_REQUEST['return_url']) ? $_REQUEST['return_url'] : fn_url('upgrade_center.manage')
                );

                exit;
            }

            return [CONTROLLER_STATUS_REDIRECT, $return_url];
        }

        fn_delete_notification('uc.timeout_check_success');

        list($result, $data) = $app->install($_REQUEST['id'], $_REQUEST);

        if ($result === UpgradeCenter::PACKAGE_INSTALL_RESULT_FAIL) {
            $view = Tygh::$app['view'];

            $_id = !empty($_REQUEST['id']) ? $_REQUEST['id'] : '';
            $type = !empty($_REQUEST['type']) ? $_REQUEST['type'] : '';

            $view->assign('validation_result', $result);
            $view->assign('validation_data', $data);
            $view->assign('id', str_replace('.', '_', $_REQUEST['id']));
            $view->assign('_id', str_replace('.', '_', $_id));
            $view->assign('type', $type);
            $view->assign('caption', __('continue'));
            $view->assign('show_pre_upgrade_notice', false);
            $view->assign('form', 'upgrade_form_' . $type . '_' . str_replace('.', '_', $_id));

            if (defined('AJAX_REQUEST')) {
                Tygh::$app['ajax']->updateRequest();
            }

            $view->display('views/upgrade_center/components/notices.tpl');
            $view->display('views/upgrade_center/components/install_button.tpl');

            exit;
        } else {
            if ($result === UpgradeCenter::PACKAGE_INSTALL_RESULT_SUCCESS) {

                $type = !empty($_REQUEST['type']) ? $_REQUEST['type'] : '';
                if ($type === 'core') {
                    fn_set_notification('N', __('successful'), __('text_uc_upgrade_completed'), 'K');
                } else {
                    $addon_schema = \Tygh\Addons\SchemesManager::getScheme($_REQUEST['id']);

                    if ($addon_schema) {
                        $addon_name = $addon_schema->getName();
                    } else {
                        $addon_name = $_REQUEST['id'];
                    }

                    fn_set_notification('N', __('successful'), __('text_uc_addon_upgrade_completed', array('[name]' => $addon_name)), 'K');
                }
            } elseif ($result === UpgradeCenter::PACKAGE_INSTALL_RESULT_WITH_ERRORS) {
                fn_set_notification('W', __('warning'), __('text_uc_package_installed_with_errors'), 'K');
            }

            // Browser should perform redirect right after AJAX-request,
            // because inner soft-redirect causes errors in case of this file was modified by upgrade package.
            if (defined('AJAX_REQUEST')) {
                Tygh::$app['ajax']->assign('non_ajax_notifications', true);
                Tygh::$app['ajax']->assign(
                    'force_redirection',
                    !empty($_REQUEST['return_url']) ? $_REQUEST['return_url'] : fn_url('upgrade_center.manage')
                );

                exit;
            }

            return [CONTROLLER_STATUS_REDIRECT, $return_url];

        }
    }

    /**
     * This mode is used to check whether server environment is suitable for long-running tasks such as upgrade or backup.
     */
    if ($mode == 'check_timeout' && defined('AJAX_REQUEST')) {
        $time_limit_applied = set_time_limit(0);

        // Don't ruin response body with max execution time exceed fatal error
        ini_set('display_errors', 'Off');
        register_shutdown_function(function(){
            $error = error_get_last();

            // Caught fatal error
            if (isset($error['type']) && $error['type'] == E_ERROR) {
                fn_set_storage_data('timeout_check_failed', true);

                // Tell browser to redirect.
                // Notifications shouldn't be sent at current response.
                Tygh::$app['ajax']->assign('non_ajax_notifications', true);
                Tygh::$app['ajax']->assign('force_redirection', fn_url('upgrade_center.manage'));
                Tygh::$app['ajax']->destruct();

                exit;
            }
        });

        $seconds_to_execute = 360;
        $flush_every_seconds = 0.25;
        $number_of_flushes = $seconds_to_execute * (1 / $flush_every_seconds);

        $start_timestamp = microtime(true);
        $die_timestamp = $start_timestamp + $seconds_to_execute;

        fn_set_progress('parts', $number_of_flushes);

        $time_elapsed = 0;
        while (($now_timestamp = microtime(true)) < $die_timestamp) {
            if (($now_timestamp - $start_timestamp - $time_elapsed) >= $flush_every_seconds) {
                $time_elapsed += $now_timestamp - $start_timestamp - $time_elapsed;

                // Pefrorm progress-bar flush
                fn_set_progress('echo', __('upgrade_center.warning_msg_executed_php') .PHP_EOL. __('seconds_left', array(
                    sprintf('%d', $die_timestamp - $now_timestamp),
                    (int) sprintf('%d', $die_timestamp - $now_timestamp),
                )));
            }
        }

        // Success notification
        fn_set_notification('N', __('successful'), __('text_uc_timeout_check_success'), 'S', 'uc.timeout_check_success');

        Tygh::$app['ajax']->assign('non_ajax_notifications', true);
        Tygh::$app['ajax']->assign('force_redirection', fn_url('upgrade_center.manage'));

        exit;
    }

    return array(CONTROLLER_STATUS_REDIRECT);
}

if ($mode == 'refresh') {
    $app->clearDownloadedPackages();
    $app->checkUpgrades(false);

    $upgrade_packages = $app->getPackagesList();
    if (empty($upgrade_packages)) {
        fn_set_notification('N', __('notice'), __('text_no_upgrades_available'));

        // Clear 'upgrade_packages' cache key on refresh
        Registry::setChangedTables('addons');
        Registry::save();
    } else {
        // Set 'upgrade_packages' cache key on refresh
        Registry::registerCache('upgrade_packages', ['addons'], Registry::cacheLevel('static'));
        Registry::set('upgrade_packages', $upgrade_packages);
    }

    return array(CONTROLLER_STATUS_OK, "upgrade_center.manage?skip_checking=true");

} elseif ($mode == 'manage') {

    // Some upgrade validators keep agreement in session
    // We must clear it when upgrade install again
    unset(Tygh::$app['session']['uc_validators']);

    $tabs = array(
        'packages' => array(
            'title' => __('available_upgrades'),
            'js' => true
        ),
        'installed_upgrades' => array(
            'title' => __('installed_upgrades'),
            'js' => true
        ),
    );

    Registry::set('navigation.tabs', $tabs);

    if (empty($_REQUEST['skip_checking'])) {
        $app->checkUpgrades(false);
    }

    [$installed_addons,,] = fn_get_addons(['type' => 'installed', 'get_marketplace_info' => true]);

    $upgrade_packages = $app->getPackagesList(['installed_addons_info' => $installed_addons]);

    $search = [];
    if (!empty($upgrade_packages['addon'])) {
        if (!empty($_REQUEST['supplier'])) {
            $search['supplier'] = $_REQUEST['supplier'];
        }
        if (!empty($_REQUEST['status'])) {
            $search['status'] = $_REQUEST['status'];
        }

        $addon_suppliers = fn_get_addon_suppliers($upgrade_packages['addon']);
    }

    $app->filterPackagesList($upgrade_packages, ['search' => $search, 'installed_addons_info' => $installed_addons]);
    $sorted_upgrade_packages = $app->sortPackagesList($upgrade_packages, $installed_addons);

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
            'title' => __('addon_market'),
            'href'  => 'addons.market',
        ]
    );
    Registry::set('navigation.dynamic.active_section', 'upgrades');

    $installed_packages = $app->getInstalledPackagesList();

    $timeout_check_failed = fn_get_storage_data('timeout_check_failed');
    fn_set_storage_data('timeout_check_failed', false);

    if (!empty(Tygh::$app['session']['upgrade_notification']['custom']) && Tygh::$app['session']['upgrade_notification']['custom'] == true) {
        if (!empty(Tygh::$app['session']['upgrade_notification']['title']) && !empty(Tygh::$app['session']['upgrade_notification']['extra'])) {
            $default_title = Tygh::$app['session']['upgrade_notification']['title'];
            $extra = Tygh::$app['session']['upgrade_notification']['extra'];
            foreach (Tygh::$app['session']['notifications'] as $key => $notification) {
                if ($notification['type'] == 'I' && $notification['title'] == $default_title && $notification['extra'] != $extra) {
                    unset(Tygh::$app['session']['notifications'][$key]);
                }
            }
        }
        unset(Tygh::$app['session']['upgrade_notification']);
    }

    Tygh::$app['view']->assign('timeout_check_failed', $timeout_check_failed);
    Tygh::$app['view']->assign('show_pre_upgrade_notice', true);
    Tygh::$app['view']->assign('search', $search);
    Tygh::$app['view']->assign('upgrade_packages', $sorted_upgrade_packages);
    Tygh::$app['view']->assign('developers', !empty($addon_suppliers) ? $addon_suppliers : []);
    Tygh::$app['view']->assign('installed_packages', $installed_packages);

    if (!empty($upgrade_packages['core']['core'])) {
        $upgrade = $upgrade_packages['core']['core'];
        /** @var \Tygh\SoftwareProductEnvironment $env */
        $env = Tygh::$app['product.env'];
        Tygh::$app['view']->assign('available_core_upgrade', new SoftwareProductEnvironment(
            $env->getProductName(),
            $upgrade['to_version'],
            $env->getStoreMode(),
            $env->getProductStatus(),
            $env->getProductBuild(),
            $env->getProductEdition(),
            $upgrade['timestamp']
        ));

        if (
            !empty($upgrade['latest_available_version']) &&
            $upgrade['latest_available_version'] !== $upgrade['to_version']
        ) {
            Tygh::$app['view']->assign('latest_version_upgrade', new SoftwareProductEnvironment(
                $env->getProductName(),
                $upgrade['latest_available_version'],
                $env->getStoreMode(),
                $env->getProductStatus(),
                $env->getProductBuild(),
                $env->getProductEdition(),
                $upgrade['latest_available_version_timestamp']
            ));
        }
    }
} elseif ($mode == 'package_content' && !empty($_REQUEST['package_id'])) {
    $package_id = $_REQUEST['package_id'];
    $content = $app->getPackageContent($package_id);

    Tygh::$app['view']->assign('package_id', $package_id);
    Tygh::$app['view']->assign('content', $content);

} elseif ($mode == 'conflicts' && !empty($_REQUEST['package_id'])) {
    $package_id = $_REQUEST['package_id'];
    $params = array('id' => $package_id);

    $packages = $app->getInstalledPackagesList($params);

    if (!isset($packages[$package_id])) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    $package = $packages[$package_id];
    if (!empty($package['conflicts'])) {
        $package['conflicts'] = unserialize($package['conflicts']);
    }

    Tygh::$app['view']->assign('package_id', $package_id);
    Tygh::$app['view']->assign('package', $package);

} elseif ($mode == 'resolve_conflict' && !empty($_REQUEST['package_id'])) {
    $app->resolveConflict($_REQUEST['package_id'], $_REQUEST['file_id'], $_REQUEST['status']);

    return array(CONTROLLER_STATUS_REDIRECT, 'upgrade_center.conflicts?package_id=' . $_REQUEST['package_id']);

} elseif ($mode == 'ftp_settings') {
    Tygh::$app['view']->assign('id', $_REQUEST['package_id']);
    Tygh::$app['view']->assign('type', $_REQUEST['package_type']);
    Tygh::$app['view']->assign('uc_settings', Settings::instance()->getValues('Upgrade_center'));
}
