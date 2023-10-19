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

use Tygh\Addons\AXmlScheme;
use Tygh\Addons\SchemesManager;
use Tygh\Addons\XmlScheme3;
use Tygh\BlockManager\Exim;
use Tygh\BlockManager\Layout;
use Tygh\BlockManager\Location;
use Tygh\BlockManager\ProductTabs;
use Tygh\Debugger;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\SiteArea;
use Tygh\Enum\YesNo;
use Tygh\Languages\Languages;
use Tygh\Navigation\LastView;
use Tygh\Providers\MarketplaceProvider;
use Tygh\Providers\StorefrontProvider;
use Tygh\Registry;
use Tygh\Settings;
use Tygh\Snapshot;
use Tygh\Themes\Themes;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

const FORBIDDEN_ADDONS_FILES = [
    '.htaccess',
    'index.php',
    'init.php',
    'api.php',
    'admin.php',
    'vendor.php',
    'config.php',
    'config.local.php',
    'app/Tygh'
];

/**
 * Updates addon settings
 *
 * @param string   $settings      Add-on's settings to be updated
 * @param int|null $storefront_id Storefront identifier to update settings for
 *
 * @return bool Always true
 */
function fn_update_addon($settings, $storefront_id = null)
{
    if (is_array($settings)) {
        foreach ($settings['options'] as $setting_id => $value) {
            Settings::instance(['storefront_id' => $storefront_id])->updateValueById($setting_id, $value);

            if (!empty($_REQUEST['update_all_vendors'][$setting_id])) {
                Settings::instance(['storefront_id' => $storefront_id])->resetAllOverrides($setting_id);
            }
        }

        /**
         * Executes after update the add-on.
         *
         * @param string   $settings      Add-on's settings to be updated
         * @param int|null $storefront_id Storefront identifier to update settings for
         */
        fn_set_hook('update_addon_post', $settings, $storefront_id);
    }

    return true;
}

/**
 * Uninstalls addon
 *
 * @param string $addon_name      Addon name to be uninstalled
 * @param bool   $show_message    If defined as true, additionally show notification
 * @param bool   $allow_unmanaged Whether to allow uninstall unmanaged addons in non-console environment
 * @param bool   $execute_schema_queries Allow execute queries from addon schema on important data
 *
 * @return bool True if addons uninstalled successfully, false otherwise
 */
function fn_uninstall_addon($addon_name, $show_message = true, $allow_unmanaged = false, $execute_schema_queries = true)
{
    $status = db_get_field('SELECT status FROM ?:addons WHERE addon = ?s', $addon_name);
    // Return true if addon is installed
    if (empty($status)) {
        return true;
    }

    $addon_scheme = SchemesManager::getScheme($addon_name);
    if ($addon_scheme != false) {

        /**
         * Executes before uninstall the add-on.
         *
         * @param string $addon_name             Addon name to be uninstalled
         * @param bool   $show_message           If defined as true, additionally show notification
         * @param bool   $allow_unmanaged        Whether to allow uninstall unmanaged addons in non-console environment
         * @param bool   $execute_schema_queries Allow execute queries from addon schema on important data
         */
        fn_set_hook('uninstall_addon_pre', $addon_name, $show_message, $allow_unmanaged, $execute_schema_queries);

        // Unmanaged addons can be uninstalled via console only
        if ($addon_scheme->getUnmanaged() && !($allow_unmanaged || defined('CONSOLE'))) {
            return false;
        }
        // Register custom classes
        $addon_scheme->registerAutoloadEntries();

        // Check dependencies
        $dependencies = SchemesManager::getUninstallDependencies($addon_name);
        if (!empty($dependencies)) {
            fn_set_notification('W', __('warning'), __('text_addon_uninstall_dependencies', array(
                '[addons]' => implode(',', $dependencies)
            )));

            return false;
        }

        // Execute custom functions for uninstall
        $addon_scheme->callCustomFunctions('uninstall');

        // Delete options
        db_query("DELETE FROM ?:addons WHERE addon = ?s", $addon_name);
        db_query("DELETE FROM ?:addon_descriptions WHERE addon = ?s", $addon_name);

        // Delete settings
        $section = Settings::instance()->getSectionByName($addon_name, Settings::ADDON_SECTION);
        if (isset($section['section_id'])) {
            Settings::instance()->removeSection($section['section_id']);
        }

        //Ignore schema queries if needed
        if ($execute_schema_queries) {

            // Delete language variables
            $addon_scheme->uninstallLanguageValues();

            // Revert database structure
            $addon_scheme->processQueries('uninstall', Registry::get('config.dir.addons') . $addon_name);
        }

        // Remove product tabs
        ProductTabs::instance()->deleteAddonTabs($addon_name);

        fn_uninstall_addon_templates(fn_basename($addon_name));

        // Uninstall layouts for each theme
        foreach (fn_get_installed_themes() as $theme_name) {
            $addon_layouts_path = fn_get_addon_layouts_path($addon_name, $theme_name);
            if ($addon_layouts_path) {
                $xml = simplexml_load_file($addon_layouts_path, '\\Tygh\\ExSimpleXmlElement', LIBXML_NOCDATA);
                foreach ($xml->location as $location) {
                    if (fn_allowed_for('ULTIMATE')) {
                        foreach (fn_get_all_companies_ids() as $company) {
                            $layouts = Layout::instance($company)->getList();
                            foreach ($layouts as $layout_id => $layout) {
                                Location::instance($layout_id)->removeByDispatch((string)$location['dispatch']);
                            }
                        }
                    } else {
                        $layouts = Layout::instance()->getList();
                        foreach ($layouts as $layout_id => $layout) {
                            Location::instance($layout_id)->removeByDispatch((string)$location['dispatch']);
                        }
                    }
                }
            }
        }

        if ($show_message) {
            fn_set_notification('N', __('notice'), __('text_addon_uninstalled', array(
                '[addon]' => $addon_scheme->getName()
            )));
        }

        /** @var \Tygh\Template\Document\Service $document_service */
        $document_service = Tygh::$app['template.document.service'];
        /** @var \Tygh\Template\Snippet\Service $snippet_service */
        $snippet_service = Tygh::$app['template.snippet.service'];
        /** @var \Tygh\Template\Mail\Service $mail_service */
        $mail_service = Tygh::$app['template.mail.service'];
        /** @var \Tygh\Template\Internal\Service $internal_service */
        $internal_service = Tygh::$app['template.internal.service'];

        $document_service->removeDocumentByAddon($addon_name);
        $snippet_service->removeSnippetByAddon($addon_name);
        $mail_service->removeTemplateByAddon($addon_name);
        $internal_service->removeTemplateByAddon($addon_name);

        //Clean Registry
        Registry::del('addons.' . $addon_name);
        $hooks = Registry::get('hooks');
        Registry::del('hooks');

        if (!empty($hooks)) {
            foreach ($hooks as $hook_name => $hooks_data) {
                foreach ($hooks_data as $key => $hook_data) {
                    if ($hook_data['addon'] === $addon_name) {
                         unset($hooks[$hook_name][$key]);
                    }
                }
            }
        }

        Registry::set('hooks', $hooks);
        //to update hooks in fn_set_hook
        fn_define('DISABLE_HOOK_CACHE', true);

        // Clean cache
        fn_clear_cache('assets');
        fn_clear_cache('registry');
        fn_clear_cache('static');

        /**
         * Executes after uninstall the add-on.
         *
         * @param string $addon_name      Addon name to be uninstalled
         * @param bool   $allow_unmanaged Whether to allow uninstall unmanaged addons in non-console environment
         */
        fn_set_hook('uninstall_addon_post', $addon_name, $allow_unmanaged);

        return true;
    } else {
        return false;
    }
}

/**
 * Disables addon
 *
 * @param string $addon_name        Addons name to be disabled
 * @param string $caller_addon_name TODO: NOT USED. Must be refactored.
 * @param bool   $show_notification
 *
 * @return bool Always true
 */
function fn_disable_addon($addon_name, $caller_addon_name, $show_notification = true)
{
    $func = 'fn_settings_actions_addons_' . $addon_name;
    if (function_exists($func)) {
        $new_status = 'D';
        $old_status = 'A';
        $func($new_status, $old_status, false);
    }
    db_query("UPDATE ?:addons SET status = ?s WHERE addon = ?s", 'D', $addon_name);

    if ($show_notification == true) {
        fn_set_notification('N', __('notice'), __('status_changed'));
    }

    if (Registry::isExist('addons.' . $addon_name . '.status')) {
        Registry::set('addons.' . $addon_name . '.status', 'D');
    }

    return true;
}

/**
 * Installs addon
 *
 * @param string $addon             Addon to install
 * @param bool   $show_notification Display notification if set to true
 * @param bool   $install_demo      If defined as true, addon's demo data will be installed
 * @param bool   $allow_unmanaged   Whether to allow installing unmanaged addons in non-console environment
 *
 * @return bool True if addons installed successfully, false otherwise
 */
function fn_install_addon($addon, $show_notification = true, $install_demo = false, $allow_unmanaged = false)
{
    $status = db_get_field("SELECT status FROM ?:addons WHERE addon = ?s", $addon);
    // Return true if addon is installed
    if (!empty($status)) {
        return true;
    }

    $addon_scheme = SchemesManager::getScheme($addon);

    if (empty($addon_scheme)) {
        // Required add-on was not found in store.
        return false;
    }

    // Unmanaged addons can be installed via console only
    if ($addon_scheme->getUnmanaged() && !($allow_unmanaged || defined('CONSOLE'))) {
        return false;
    }

    if ($addon_scheme != false) {
        // Register custom classes
        $addon_scheme->registerAutoloadEntries();

        if ($addon_scheme->isPromo()) {

            $texts = fn_get_addon_permissions_text();
            fn_set_notification('E', __('error'), $texts['text']);

            return false;
        }

        $_data = [
            'addon'                   => $addon_scheme->getId(),
            'priority'                => $addon_scheme->getPriority(),
            'dependencies'            => implode(',', $addon_scheme->getDependencies()),
            'conflicts'               => implode(',', $addon_scheme->getConflicts()),
            'requirements'            => $addon_scheme->getRequirements(),
            'version'                 => $addon_scheme->getVersion(),
            'separate'                => (int) ($addon_scheme->getSettingsLayout() === 'separate'),
            'has_icon'                => $addon_scheme->hasIcon(),
            'unmanaged'               => $addon_scheme->getUnmanaged(),
            'status'                  => ObjectStatuses::DISABLED,
            'install_datetime'        => time(),
            'marketplace_id'          => null,
            'marketplace_license_key' => null,
        ];

        if ($addon_scheme instanceof XmlScheme3) {
            $_data['marketplace_id'] = $addon_scheme->getMarketplaceProductID();
            $_data['marketplace_license_key'] = $addon_scheme->getMarketplaceLicenseNumber();
        }

        // Check system requirements (needed versions, installed extensions, etc.)
        if (!$addon_scheme->checkRequirements($_data['requirements'])) {
            return false;
        }

        $dependencies = SchemesManager::getInstallDependencies($_data['addon']);
        if (!empty($dependencies)) {
            if (count($dependencies) === 1) {
                $full_link = '';
                foreach ($dependencies as $dependency_key => $dependency) {
                    $url = fn_url('addons.update&addon=' . $dependency_key, SiteArea::ADMIN_PANEL);
                    $full_link = '<a href="' . $url . '">' . $dependency . '</a>';
                }
                fn_set_notification(
                    NotificationSeverity::WARNING,
                    __('warning'),
                    __(
                        'text_addon_install_dependencies',
                        [
                            '[addon]' => $full_link,
                        ]
                    )
                );
            } else {
                $all_urls = [];
                foreach ($dependencies as $dependency_key => $dependency) {
                    $url = fn_url('addons.update&addon=' . $dependency_key, SiteArea::ADMIN_PANEL);
                    $all_urls[] = '<li><a href="' . $url . '">' . $dependency . '</a></li>';
                }
                fn_set_notification(
                    NotificationSeverity::WARNING,
                    __('warning'),
                    __('addons.multiple_dependencies_required', ['[addon_list]' => implode('', $all_urls)])
                );
            }

            return false;
        }

        if ($addon_scheme->callCustomFunctions('before_install') == false) {
            fn_uninstall_addon($addon, false, $allow_unmanaged);

            return false;
        }

        // Add add-on to registry
        $is_addon_disabled = fn_is_addon_init_disabled($addon);

        Registry::set(
            'addons.' . $addon,
            [
                'status'      => ObjectStatuses::DISABLED,
                'is_disabled' => $is_addon_disabled,
                'priority'    => $_data['priority'],
            ]
        );

        // Execute optional queries
        if ($addon_scheme->processQueries('install', Registry::get('config.dir.addons') . $addon) == false) {
            fn_uninstall_addon($addon, false, $allow_unmanaged, false);

            return false;
        }

        if (fn_update_addon_settings($addon_scheme) == false) {
            fn_uninstall_addon($addon, false, $allow_unmanaged);

            return false;
        }

        db_query("REPLACE INTO ?:addons ?e", $_data);

        foreach ($addon_scheme->getAddonTranslations() as $translation) {
            db_query("REPLACE INTO ?:addon_descriptions ?e", array(
                'lang_code' => $translation['lang_code'],
                'addon' =>  $addon_scheme->getId(),
                'name' => $translation['value'],
                'description' => isset($translation['description']) ? $translation['description'] : ''
            ));
        }

        foreach ($addon_scheme->getLanguages() as $lang_code => $_v) {
            $lang_code = strtolower($lang_code);
            $path = $addon_scheme->getPoPath($lang_code);
            if (!empty($path)) {
                Languages::installLanguagePack($path, array(
                    'reinstall' => true,
                    'validate_lang_code' => $lang_code
                ));
            }
        }

        // Install templates
        fn_install_addon_templates($addon_scheme->getId());

        // Put this addon settings to the registry
        $settings = Settings::instance()->getValues($addon_scheme->getId(), Settings::ADDON_SECTION, false);
        if (!empty($settings)) {
            Registry::set('settings.' . $addon, $settings);
            $addon_data = Registry::get('addons.' . $addon);
            Registry::set('addons.' . $addon, fn_array_merge($addon_data, $settings));
        }

        fn_update_addon_language_variables($addon_scheme);

        if (fn_allowed_for('ULTIMATE')) {
            foreach (fn_get_all_companies_ids() as $company) {
                ProductTabs::instance($company)->createAddonTabs($addon_scheme->getId(), $addon_scheme->getTabOrder());
            }
        } else {
            ProductTabs::instance()->createAddonTabs($addon_scheme->getId(), $addon_scheme->getTabOrder());
        }

        $email_templates = $addon_scheme->getEmailTemplates();
        $internal_templates = $addon_scheme->getInternalTemplates();
        $document_templates = $addon_scheme->getDocumentTemplates();
        $snippet_templates = $addon_scheme->getSnippetTemplates();

        if ($email_templates) {
            /** @var \Tygh\Template\Mail\Exim $email_exim */
            $email_exim = \Tygh::$app['template.mail.exim'];
            $email_exim->import($email_templates);
        }

        if ($internal_templates) {
            /** @var \Tygh\Template\Internal\Exim $internal_exim */
            $internal_exim = \Tygh::$app['template.internal.exim'];
            $internal_exim->import($internal_templates);
        }

        if ($document_templates) {
            /** @var \Tygh\Template\Document\Exim $document_exim */
            $document_exim = \Tygh::$app['template.document.exim'];
            $document_exim->import($document_templates);
        }

        if ($snippet_templates) {
            /** @var \Tygh\Template\Snippet\Exim $snippet_exim */
            $snippet_exim = \Tygh::$app['template.snippet.exim'];
            $snippet_exim->import($snippet_templates);
        }

        // Execute custom functions
        if ($addon_scheme->callCustomFunctions('install') == false) {
            fn_uninstall_addon($addon, false, $allow_unmanaged);

            return false;
        }

        if ($show_notification == true) {
            fn_set_notification('N', __('notice'), __('text_addon_installed', array(
                '[addon]' => $addon_scheme->getName()
            )));
        }

        if ($_data['marketplace_license_key'] !== null) {
            fn_update_addon_license_key($addon, $_data['marketplace_license_key']);
        }
        // If we need to activate addon after install, call "update status" procedure
        if ($addon_scheme->getStatus() !== ObjectStatuses::DISABLED && !$is_addon_disabled) {
            fn_update_addon_status($addon, $addon_scheme->getStatus(), false, true, $allow_unmanaged);
        }

        // Install layouts individually for each theme
        fn_install_addon_layouts($addon);

        // Clean cache
        fn_clear_cache();



        if ($install_demo) {
            $addon_scheme->processQueries('demo', Registry::get('config.dir.addons') . $addon);
            if ($addon_scheme->callCustomFunctions('demo') == false) {
                fn_uninstall_addon($addon, false, $allow_unmanaged);

                return false;
            }
        }

        /**
         * Allows you to perform additional actions after installing addon.
         *
         * @param string $addon             Addon to install
         * @param bool   $show_notification Display notification if set to true
         * @param bool   $install_demo      If defined as true, addon's demo data will be installed
         * @param bool   $allow_unmanaged   Whether to allow installing unmanaged addons in non-console environment
         */
        fn_set_hook('install_addon_post', $addon, $show_notification, $install_demo, $allow_unmanaged);

        return true;
    } else {
        // Addon was not installed because scheme is not exists.
        return false;
    }
}

/**
 * Сhecks whether the addon should be disabled
 *
 * @param string $addon_name Addon name
 *
 * @return bool True if addon should be disabled, false otherwise
 */
function fn_is_addon_init_disabled($addon_name)
{
    $init_addons = Registry::get('settings.init_addons');

    if ($init_addons === 'none') {
        return true;
    }
    if ($init_addons === 'restore') {
        return false;
    }
    if ($init_addons === 'core') {
        $allowed_addons = array_filter(Snapshot::getCoreAddons(), static function ($addon) {
            $scheme = SchemesManager::getScheme($addon);
            return $scheme && !$scheme->hasSupplier();
        });
        return !in_array($addon_name, $allowed_addons);
    }
    return false;
}

/**
 * Copies addon templates from repository
 *
 * @param string $addon_name    Addons name to copy templates for
 * @param array  $target_themes Theme names to copy add-on templates for
 *
 * @return bool Always true
 */
function fn_install_addon_templates($addon_name, $target_themes = array())
{
    if (empty($target_themes)) {
        $target_themes = fn_get_installed_themes();
    }
    $design_dir = fn_get_theme_path('[themes]/', 'C');
    foreach ($target_themes as $theme_name) {
        $repo_path = Themes::factory($theme_name)->getThemeRepoPath();
        fn_copy_addon_templates_from_repo($repo_path, $design_dir, $addon_name, $theme_name);
    }

    return true;
}

/**
 * Copies files from base repository to store folder
 *
 * @param string $repo_dir   Path to the repository
 * @param string $design_dir Path to store design folder
 * @param string $addon_name Name of installing add-on
 * @param string $theme_name Using theme name
 */
function fn_copy_addon_templates_from_repo($repo_dir, $design_dir, $addon_name, $theme_name)
{
    $repo_dir = rtrim($repo_dir, '/') . '/';

    $paths = array(
        'templates/addons/' . $addon_name,
        'css/addons/' . $addon_name,
        'media/images/addons/' . $addon_name,

        // Copy Mail directory
        'mail/templates/addons/' . $addon_name,
        'mail/media/images/addons/' . $addon_name,
        'mail/css/addons/' . $addon_name,
    );

    // install templates only for the specified theme
    foreach ($paths as $path) {
        if (is_dir($repo_dir . $path)) {
            fn_copy($repo_dir . $path, $design_dir . $theme_name . '/' . $path);
        }
    }
}

/**
 * Removes addon's templates from theme folder
 *
 * @param string $addon Addon name to remove templates for
 *
 * @return bool Always true
 */
function fn_uninstall_addon_templates($addon)
{
    if (fn_is_development()) {
        return false;
    }

    $installed_themes = fn_get_installed_themes();
    $design_dir = fn_get_theme_path('[themes]/', 'C');

    foreach ($installed_themes as $theme_name) {
        $paths = array(
            $design_dir . $theme_name . '/templates/addons/' . $addon,
            $design_dir . $theme_name . '/css/addons/' . $addon,
            $design_dir . $theme_name . '/media/images/addons/' . $addon,
            $design_dir . $theme_name . '/mail/templates/addons/' . $addon,
            $design_dir . $theme_name . '/mail/media/images/addons/' . $addon,
            $design_dir . $theme_name . '/mail/css/addons/' . $addon,
        );

        foreach ($paths as $path) {
            if (is_dir($path)) {
                fn_rm($path);
            }
        }
    }

    return true;
}

/**
 * Update addon settings in database
 *
 * @param AXmlScheme $addon_scheme      Addon scheme
 * @param boolean    $execute_functions Trigger settings update functions
 * @param array      $values            Array of setting values
 * @param array      $vendor_values     Array of setting vendor values
 *
 * @return bool True on success, false otherwise
 */
function fn_update_addon_settings($addon_scheme, $execute_functions = true, $values = array(), $vendor_values = array())
{
    $section = Settings::instance()->getSectionByName($addon_scheme->getId(), Settings::ADDON_SECTION);

    if (isset($section['section_id'])) {
        Settings::instance()->removeSection($section['section_id']);
    }

    $tabs = $addon_scheme->getSections();

    // If isset section settings in xml data and that addon settings is not exists
    if (!empty($tabs)) {
        Registry::set('runtime.database.skip_errors', true);

        // Create root settings section
        $addon_section_id = Settings::instance()->updateSection(array(
            'parent_id'    => 0,
            'edition_type' => $addon_scheme->getEditionType(),
            'name'         => $addon_scheme->getId(),
            'type'         => Settings::ADDON_SECTION,
        ));

        foreach ($tabs as $tab_index => $tab) {
            // Add addon tab as setting section tab
            $section_tab_id = Settings::instance()->updateSection(array(
                'parent_id'    => $addon_section_id,
                'edition_type' => $tab['edition_type'],
                'name'         => $tab['id'],
                'position'     => $tab_index * 10,
                'type'         => isset($tab['separate']) ? Settings::SEPARATE_TAB_SECTION : Settings::TAB_SECTION,
            ));

            // Import translations for tab
            if (!empty($section_tab_id)) {
                fn_update_addon_settings_descriptions($section_tab_id, Settings::SECTION_DESCRIPTION, $tab['translations']);
                fn_update_addon_settings_originals($addon_scheme->getId(), $tab['id'], 'section', $tab['original']);

                $settings = $addon_scheme->getSettings($tab['id']);

                foreach ($settings as $k => $setting) {
                    if (!empty($setting['id'])) {

                        if (!empty($setting['parent_id'])) {
                            $setting['parent_id'] = Settings::instance()->getId($setting['parent_id'], $addon_scheme->getId());
                        }

                        $setting_id = Settings::instance()->update(array(
                            'name' =>           $setting['id'],
                            'section_id' =>     $addon_section_id,
                            'section_tab_id' => $section_tab_id,
                            'type' =>           $setting['type'],
                            'position' =>       isset($setting['position']) ? $setting['position'] : $k * 10,
                            'edition_type' =>   $setting['edition_type'],
                            'is_global' =>      'N',
                            'handler' =>        $setting['handler'],
                            'parent_id' =>      intval($setting['parent_id'])
                        ));

                        if (!empty($setting_id)) {
                            $setting_value = isset($values[$setting['id']]) ? $values[$setting['id']] : $setting['default_value'];

                            Settings::instance()->updateValueById($setting_id, $setting_value, null, $execute_functions);

                            if (
                                !empty($vendor_values[$setting['id']])
                                && Settings::instance()->areOverriddenValuesSupportedByEdition($setting['edition_type'])
                            ) {
                                foreach ($vendor_values[$setting['id']] as $id => $vendor_setting_value) {
                                    if ($setting_value != $vendor_setting_value) {
                                        if (fn_allowed_for('MULTIVENDOR')) {
                                            Settings::instance(['storefront_id' => (int) $id])->updateValueById($setting_id, $vendor_setting_value, null, $execute_functions, $id);
                                        } else {
                                            Settings::instance(['company_id' => (int) $id])->updateValueById($setting_id, $vendor_setting_value, $id, $execute_functions);
                                        }
                                    }
                                }
                            }

                            fn_update_addon_settings_descriptions($setting_id, Settings::SETTING_DESCRIPTION, $setting['translations']);
                            fn_update_addon_settings_originals($addon_scheme->getId(), $setting['id'], 'option', $setting['original']);

                            if (isset($setting['variants'])) {
                                foreach ($setting['variants'] as $variant_k => $variant) {
                                    $variant_id = Settings::instance()->updateVariant(array(
                                        'object_id'  => $setting_id,
                                        'name'       => $variant['id'],
                                         'position'   => isset($variant['position']) ? $variant['position'] : $variant_k * 10,
                                    ));

                                    if (!empty($variant_id)) {
                                        fn_update_addon_settings_descriptions($variant_id, Settings::VARIANT_DESCRIPTION, $variant['translations']);
                                        fn_update_addon_settings_originals($addon_scheme->getId(), $setting['id'] . '::' . $variant['id'], 'variant', $variant['original']);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        Registry::set('runtime.database.skip_errors', false);

        $errors = Registry::get('runtime.database.errors');
        if (!empty($errors)) {
            $error_text = '';
            foreach ($errors as $error) {
                $error_text .= '<br/>' . $error['message'] . ': <code>'. $error['query'] . '</code>';
            }
            fn_set_notification('E', __('addon_sql_error'), $error_text, '', 'addon_update_settings_sql_error');

            Registry::set('runtime.database.errors', array());

            return false;
        }
    }

    return true;
}

/**
 * Updates addon settings descriptions
 *
 * @param int    $object_id    Descriptions identifier
 * @param string $object_type  Descriptions type (Settings::VARIANT_DESCRIPTION | Settings::SETTING_DESCRIPTION |
 *                             Settings::SECTION_DESCRIPTION)
 * @param array  $translations List of descriptions @see Settings::updateDescription()
 *
 * @return bool Always true
 */
function fn_update_addon_settings_descriptions($object_id, $object_type, $translations)
{
    if (!empty($translations)) {
        foreach ($translations as $translation) {
            $translation['object_type'] = $object_type;
            $translation['object_id'] = $object_id;
            Settings::instance()->updateDescription($translation);
        }
    }

    return true;
}

/**
 * Updates settings original values
 *
 * @param string $addon_id Addon ID (discussions, gift_certificates, etc)
 * @param string $name     Text settings ID (use_search_on_page, gift_certificate_code, etc)
 * @param string $type     Setting type. Enum: section, option, variant
 * @param string $value    Setting description
 *
 * @return bool True if updated
 */
function fn_update_addon_settings_originals($addon_id, $name, $type, $value)
{
    switch (strtolower($type)) {
        case 'section':
            $context = 'SettingsSections::' . $addon_id . '::' . $name;
            break;

        case 'option':
            $context = 'SettingsOptions::' . $addon_id . '::' . $name;
            break;

        case 'variant':
            // Variant name must include option name (option_name::variant_name)
            $context = 'SettingsVariants::' . $addon_id . '::' . $name;
            break;

        default:
            $context = '';
            break;
    }

    if (empty($context)) {
        return false;
    }

    return db_query('REPLACE INTO ?:original_values (msgctxt, msgid) VALUES (?s, ?s)', $context, $value);
}

/**
 * Checks if addon has correct snapshot
 *
 * @param string      $addon Addon name (ID)
 * @param string|null $mode  Store mode
 *
 * @return bool true if correct
 */
function fn_check_addon_snapshot($addon, $mode = null)
{
    static $addons_snapshots = null;

    $mode = isset($mode) ? $mode : fn_get_storage_data('store_mode');
    $status = true;

    if ($addons_snapshots === null) {
        $addons_snapshots = fn_get_storage_data('addons_snapshots');
        $addons_snapshots = explode(',', $addons_snapshots);
    }

    if (in_array(md5($addon . ':' . $mode), $addons_snapshots)) {
        $status = false;
    }

    fn_set_hook('addon_snapshot', $addon, $status);

    return $status;
}

/**
 * Cleans up addons with incorrect snapshot
 *
 * @return bool Always true
 */
function fn_clean_up_addons()
{
    $_addons = db_get_hash_single_array("SELECT addon, status FROM ?:addons", array('addon', 'status'));
    $skipped_snapshots = fn_get_storage_data('skipped_snapshots');
    $skipped_snapshots = !empty($skipped_snapshots) ? explode(',', $skipped_snapshots) : array();

    foreach ($_addons as $addon => $status) {
        $snaphost = md5(str_rot13($addon));
        if (!fn_check_addon_snapshot($addon)) {
            if ($status == 'A') {
                fn_update_addon_status($addon, 'D');
                $skipped_snapshots[] = $snaphost;
            }
        } elseif (in_array($snaphost, $skipped_snapshots)) {
            fn_update_addon_status($addon, 'A');
            $skipped_snapshots = array_diff($skipped_snapshots, array($snaphost));
        }
    }

    $skipped_snapshots = array_unique($skipped_snapshots);
    fn_set_storage_data('skipped_snapshots', implode(',', $skipped_snapshots));

    return true;
}

/**
 * Updates addon status
 *
 * @param string $addon             Addon to update status for
 * @param string $status            Status to change to
 * @param bool   $show_notification Display notification if set to true
 * @param bool   $on_install        If status was changed right after install process
 * @param bool   $allow_unmanaged   Whether to allow change status for unmanaged addons in non-console environment
 *
 * @return bool|string True on success, old status ID if status was not changed
 */
function fn_update_addon_status($addon, $status, $show_notification = true, $on_install = false, $allow_unmanaged = false)
{
    $old_status = db_get_field("SELECT status FROM ?:addons WHERE addon = ?s", $addon);
    $new_status = $status;

    $scheme = SchemesManager::getScheme($addon);

    // Unmanaged addons can be enabled/disabled via console only
    if ($scheme->getUnmanaged() && !($allow_unmanaged || defined('CONSOLE'))) {
        return false;
    }

    /**
     * Hook is executed before changing add-on status (i.e. before add-on enabling or disabling).
     *
     * @param string                  $addon             Add-on name
     * @param string                  $status            New addon status - "A" for enabled, "D" for disabled
     * @param bool                    $show_notification Display notification if set to true
     * @param bool                    $on_install        If status was changed right after install process
     * @param bool                    $allow_unmanaged   Whether to allow change status for unmanaged addons in non-console environment
     * @param string                  $old_status        Previous addon status - "A" for enabled, "D" for disabled
     * @param \Tygh\Addons\AXmlScheme $scheme            Add-on scheme
     */
    fn_set_hook('update_addon_status_pre', $addon, $status, $show_notification, $on_install, $allow_unmanaged, $old_status, $scheme);

    if ($old_status != $new_status) {

        // Check if addon can be enabled
        $conflicts = db_get_fields("SELECT addon FROM ?:addons WHERE status = 'A' AND FIND_IN_SET(?s, conflicts)", $addon);
        if ($new_status == 'A' && !empty($conflicts)) {
            $scheme = SchemesManager::getScheme($addon);

            fn_set_notification('W', __('warning'), __('text_addon_cannot_enable', array(
                '[addons]' => implode(', ', SchemesManager::getNames($conflicts)),
                '[addon_name]' => $scheme->getName()
            )));

            return $old_status;
        }

        // Check dependencies
        if ($new_status == 'A') {
            $dependencies = array();
            foreach (SchemesManager::getScheme($addon)->getDependencies() as $addon_id) {
                $name = SchemesManager::getName($addon_id, CART_LANGUAGE);
                $dependencies[$addon_id] = $name ? $name : $addon_id;
            }
        } else {
            $dependencies = SchemesManager::getUninstallDependencies($addon);
        }

        if (!empty($dependencies)) {
            $addons = Registry::get('addons');
            foreach ($dependencies as $dependency => $display_name) {
                if (!empty($addons[$dependency]['status']) && $addons[$dependency]['status'] != $new_status) {
                    if ($new_status == 'A') {
                        fn_set_notification('W', __('warning'), __('text_addon_enable_dependencies', array(
                            '[addons]' => implode(',', $dependencies)
                        )));

                    } else {
                        fn_set_notification('W', __('warning'), __('text_addon_disable_dependencies', array(
                            '[addons]' => implode(',', $dependencies)
                        )));
                    }
                    return $old_status;
                }
            }
        }

        fn_get_schema('settings', 'actions.functions', 'php', true);

        $func = 'fn_settings_actions_addons_' . $addon;

        if (function_exists($func)) {
            $func($new_status, $old_status, $on_install);
        }

        // If status change is allowed, update it
        if ($old_status != $new_status) {
            if ($new_status != 'D') {
                // Check that addon have conflicts
                $scheme = SchemesManager::getScheme($addon);

                $conflicts = db_get_field("SELECT conflicts FROM ?:addons WHERE addon = ?s", $addon);

                if (!empty($conflicts)) {
                    $conflicts = explode(',', $conflicts);
                    $conflicted_addons = db_get_fields("SELECT addon FROM ?:addons WHERE addon IN (?a) AND status = 'A'", $conflicts);
                    if (!empty($conflicted_addons)) {
                        $lang_var = 'text_addon_confclicts_on_install';

                        if (!$on_install) {
                            foreach ($conflicts as $conflict) {
                                fn_disable_addon($conflict, $scheme->getName(), $show_notification);
                            }

                            $lang_var = 'text_addon_confclicts';
                        }

                        fn_set_notification('W', __('warning'), __($lang_var, array(
                            '[addons]' => implode(', ', SchemesManager::getNames($conflicts)),
                            '[addon_name]' => $scheme->getName()
                        )));

                        // On install we cannot enable addon with conflicts automaticly
                        if ($on_install) {
                            return $old_status;
                        }
                    }
                }
            }

            db_query("UPDATE ?:addons SET status = ?s WHERE addon = ?s", $status, $addon);

            $func = 'fn_settings_actions_addons_post_' . $addon;

            if (function_exists($func)) {
                $func($status);
            }

            if ($show_notification == true) {
                fn_set_notification('N', __('notice'), __('status_changed'));
            }

            // Enable/disable tabs for addon
            ProductTabs::instance()->updateAddonTabStatus($addon, $new_status);

            /** @var \Tygh\Template\Snippet\Service $snippet_service */
            $snippet_service = Tygh::$app['template.snippet.service'];
            $snippet_service->updateSnippetStatusByAddon($addon, $new_status);

            Registry::set('addons.' . $addon . '.status', $status);

        } else {
            return $old_status;
        }

    }

    // Clean cache
    fn_clear_cache();

    if ($status == 'A') {
        foreach (fn_get_installed_themes() as $theme_name) {

            $theme = Themes::factory($theme_name);
            $theme_manifest = $theme->getManifest();

            // Precompile addon LESS files if the theme has been converted to CSS
            if (!empty($theme_manifest['converted_to_css']) && !$theme->convertAddonToCss($addon)) {
                fn_update_addon_status($addon, 'D', $show_notification, $on_install);

                return $old_status;
            }
        }
    }

    /**
     * Hook is executed after changing add-on status (i.e. after add-on enabling or disabling).
     *
     * @param string                  $addon             Add-on name
     * @param string                  $status            New addon status - "A" for enabled, "D" for disabled
     * @param bool                    $show_notification Display notification if set to true
     * @param bool                    $on_install        If status was changed right after install process
     * @param bool                    $allow_unmanaged   Whether to allow change status for unmanaged addons in non-console environment
     * @param string                  $old_status        Previous addon status - "A" for enabled, "D" for disabled
     * @param \Tygh\Addons\AXmlScheme $scheme            Add-on scheme
     */
    fn_set_hook('update_addon_status_post', $addon, $status, $show_notification, $on_install, $allow_unmanaged, $old_status, $scheme);

    return true;
}

/**
 * Returns addon's version
 *
 * @param string $addon Addon name to return version for
 *
 * @return string Addon's version
 */
function fn_get_addon_version($addon)
{
    return db_get_field("SELECT version FROM ?:addons where addon=?s", $addon);
}

/**
 * Gets addons list.
 *
 * @param array<string,string|bool> $params         Search params
 * @param int                       $items_per_page Items per page for pagination
 * @param string                    $lang_code      Language code
 * @param int|null                  $storefront_id  Storefront ID to get settings for
 * @param int|null                  $company_id     Company ID to get settings for
 *
 * @psalm-param array{
 *   type?: string,
 *   for_company?: bool,
 *   q?: string,
 *   source?: string,
 *   supplier?: string
 * } $params
 *
 * @return array<array<string, string>> addons list and filtered search params
 *
 * @psalm-return array{
 *   array<string, array{
 *     addon: string,
 *     status: string,
 *     name: string,
 *     version: string,
 *     supplier: string,
 *     supplier_link: string,
 *     supplier_page: string,
 *     snapshot_correct: bool,
 *     description: string,
 *     has_icon : string,
 *     is_core_addon: bool,
 *     install_datetime: int|null,
 *     delete_url?: string,
 *     refresh_url?: string,
 *     licensing_url?: string,
 *     url?: string,
 *     category?: string,
 *   }>,
 *   array{
 *     type: string,
 *     for_company?: bool,
 *     q?: string,
 *     source?: string,
 *     supplier?: string,
 *   },
 *   array{
 *     installed: int,
 *     activated: int,
 *     core: int,
 *     other: int,
 *   },
 * }
 *
 * @psalm-suppress MoreSpecificReturnType
 */
function fn_get_addons(array $params, $items_per_page = 0, $lang_code = CART_LANGUAGE, $storefront_id = null, $company_id = null)
{
    if ($storefront_id === null && SiteArea::isStorefront(AREA)) {
        $storefront_id = StorefrontProvider::getStorefront()->storefront_id;
    }
    if ($company_id === null) {
        $company_id = (int) Registry::get('runtime.company_id');
    }

    $params = LastView::instance()->update('addons', $params);
    $addons_counter = [
        'installed' => 0,
        'activated' => 0,
        'core' => 0,
        'other' => 0
    ];

    $default_params = [
        'type'          => 'any',
        'get_unmanaged' => false
    ];

    /**
     * @psalm-var array{
     *   type: string,
     *   get_unmanaged: bool,
     *   for_company?: bool,
     *   q?: string,
     *   source?: string,
     *   supplier?: string,
     *   get_marketplace_info?: string,
     * } $params
     */
    $params = array_merge($default_params, $params);

    /** @var \Tygh\Settings $settings_manager */
    $settings_manager = Settings::instance(['storefront_id' => $storefront_id, 'company_id' => $company_id]);

    $addons = [];
    $sections =  $settings_manager->getAddons();
    $all_addons = fn_get_dir_contents(Registry::get('config.dir.addons'), true, false);
    $installed_addons = db_get_hash_array(
        'SELECT a.addon, a.status, b.name as name, b.description as description, a.separate, a.unmanaged, a.has_icon,'
        . ' a.install_datetime, a.marketplace_id, a.marketplace_license_key '
        . 'FROM ?:addons as a LEFT JOIN ?:addon_descriptions as b ON b.addon = a.addon AND b.lang_code = ?s'
        . 'ORDER BY b.name ASC',
        'addon',
        $lang_code
    );

    $addons_counter['installed'] = count($installed_addons);

    $current_url = Registry::get('config.current_url');
    $directory_addons = Registry::get('config.dir.addons');
    $is_development = Debugger::isActive() || fn_is_development();

    foreach ($installed_addons as $key => $addon) {
        $installed_addons[$key]['has_sections'] = $settings_manager->sectionExists($sections, $addon['addon']);
        $installed_addons[$key]['has_options'] = $installed_addons[$key]['has_sections']
            ? $settings_manager->optionsExists($addon['addon'], 'ADDON')
            : false;

        // Check add-on snapshot
        if (!fn_check_addon_snapshot($key)) {
            $installed_addons[$key]['status'] = ObjectStatuses::DISABLED;
            $installed_addons[$key]['snapshot_correct'] = false;
        } else {
            $installed_addons[$key]['snapshot_correct'] = true;
        }

        $addons_counter['activated'] += (int) ($installed_addons[$key]['status'] === ObjectStatuses::ACTIVE);
    }

    static $is_single_storefront = null;
    if ($is_single_storefront === null) {
        $is_single_storefront = Registry::ifGet('runtime.simple_ultimate', false);
    }
    $marketplace_addons_data = [];
    foreach ($all_addons as $addon) {
        $addon_scheme = SchemesManager::getScheme($addon);
        // skip addons with broken or missing scheme
        if (!$addon_scheme) {
            continue;
        }

        if ($addon_scheme->isCoreAddon()) {
            $addons_counter['core']++;
        } else {
            $addons_counter['other']++;
            if ($addon_scheme instanceof XmlScheme3) {
                $marketplace_id = $addon_scheme->getMarketplaceProductID();
                if ($marketplace_id !== null) {
                    $marketplace_addons_data[$addon] = [
                        'marketplace_id' => $marketplace_id,
                        'version'        => $addon_scheme->getVersion(),
                    ];
                }
            }
        }

        if (in_array($params['type'], ['any', 'installed', 'active', 'disabled'])) {
            switch ($params['type']) {
                case 'active':
                    $search_status = ObjectStatuses::ACTIVE;
                    break;
                case 'disabled':
                    $search_status = ObjectStatuses::DISABLED;
                    break;
                default:
                    $search_status = '';
            }

            if (!empty($installed_addons[$addon])) {
                // exclude unmanaged addons from the list
                if ((bool) $installed_addons[$addon]['unmanaged'] && !$params['get_unmanaged']) {
                    continue;
                }

                if ($search_status && $installed_addons[$addon]['status'] !== $search_status) {
                    continue;
                }

                $addons[$addon] = $installed_addons[$addon];
                $addons[$addon]['supplier'] = $addon_scheme->getSupplier();
                $addons[$addon]['supplier_link'] = $addon_scheme->getSupplierLink();
                $addons[$addon]['version'] = $addon_scheme->getVersion();
                $addons[$addon]['is_core_addon'] = $addon_scheme->isCoreAddon();
                $addons[$addon]['delete_url'] = '';
                $addons[$addon]['refresh_url'] = '';
                $addons[$addon]['url'] = fn_url("addons.update?addon={$addon}&return_url=" . urlencode($current_url));
                $addons[$addon]['supplier_page'] = $addon_scheme->getSupplierPage($addons[$addon]['status']);
                $addons[$addon]['recently_installed'] = TIME - $installed_addons[$addon]['install_datetime'] < SECONDS_IN_DAY;
                $addons[$addon]['separate'] = true;
                if ($addons[$addon]['status'] === ObjectStatuses::ACTIVE) {
                    if ($menu_items = fn_get_menu_schema_for_addon($addon)) {
                        $addons[$addon]['menu_items'] = fn_attach_addon_menu_item_parents($menu_items);
                    }
                }

                // Only root admin can handle this
                if (!$company_id || $is_single_storefront) {
                    if (fn_is_lang_var_exists($addon . '.confirmation_deleting')) {
                        $addons[$addon]['confirmation_deleting'] = __($addon . '.confirmation_deleting');
                    }
                    $addons[$addon]['delete_url'] = fn_url(
                        "addons.uninstall?addon={$addon}&return_url=" . urlencode($current_url)
                    );

                    if ($is_development) {
                        $addons[$addon]['refresh_url'] = fn_url("addons.refresh?addon={$addon}&return_url=" . urlencode($current_url));
                    }
                }

                if (!$addon_scheme->getUnmanaged()) {
                    $addons[$addon]['originals'] = $addon_scheme->getOriginals();
                }

                fn_update_lang_objects('installed_addon', $addons[$addon]);

                if (is_file($directory_addons . $addon . '/func.php')) {
                    require_once($directory_addons . $addon . '/func.php');

                    if (is_file($directory_addons . $addon . '/config.php')) {
                        require_once($directory_addons . $addon . '/config.php');
                    }

                    // Generate custom description
                    $func = 'fn_addon_dynamic_description_' . $addon;
                    if (function_exists($func)) {
                        $addons[$addon]['description'] = $func($addons[$addon]['description']);
                    }

                    //Generate custom url
                    $url_func = 'fn_addon_dynamic_url_' . $addon;
                    if (function_exists($url_func)) {
                        list($addons[$addon]['url'], $addons[$addon]['delete_url'], $addons[$addon]['refresh_url']) = $url_func(
                            $addons[$addon]['url'],
                            $addons[$addon]['delete_url'],
                            $addons[$addon]['refresh_url']
                        );
                    }
                }
            }
        }

        if (
            !empty($installed_addons[$addon])
            || !empty($params['for_company'])
            || !in_array($params['type'], ['any', 'not_installed'])
            || $addon_scheme->getUnmanaged()
        ) {
            continue;
        }

        $addons[$addon] = [
            'addon'            => $addon,
            'status'           => ObjectStatuses::NEW_OBJECT,
            'name'             => $addon_scheme->getName(),
            'version'          => $addon_scheme->getVersion(),
            'supplier'         => $addon_scheme->getSupplier(),
            'supplier_link'    => $addon_scheme->getSupplierLink(),
            'supplier_page'    => $addon_scheme->getSupplierPage(ObjectStatuses::NEW_OBJECT),
            'snapshot_correct' => fn_check_addon_snapshot($addon),
            'description'      => $addon_scheme->getDescription(),
            'has_icon'         => $addon_scheme->hasIcon(),
            'is_core_addon'    => $addon_scheme->isCoreAddon(),
            'install_datetime' => null,
        ];
    }
    if (!empty($addons)) {
        $all_addons_data = db_get_hash_array('SELECT * FROM ?:addon_data WHERE addon in (?a) ORDER BY addon ASC', 'addon', array_keys($addons));
        $addons = fn_array_merge($addons, $all_addons_data, true);
    }

    if (
        !empty($params['get_marketplace_info'])
        && $addons
        && ($client = MarketplaceProvider::getClient())
    ) {
        $result = $client->getProduct(
            '',
            [
                'pid'             => array_column($marketplace_addons_data, 'marketplace_id'),
                'addon_versions'  => array_column($marketplace_addons_data, 'version', 'marketplace_id'),
                'product_version' => PRODUCT_VERSION,
                'edition'         => PRODUCT_EDITION,
                'sl'              => $lang_code,
            ]
        );
        $categories = $client->getCategories();
        if (!empty($result)) {
            foreach ($marketplace_addons_data as $addon => $addon_data) {
                if (!isset($addons[$addon], $result[$addon_data['marketplace_id']])) {
                    continue;
                }
                $addons[$addon]['identified'] = true;
                $addons[$addon]['category'] = $result[$addon_data['marketplace_id']]['product']['main_category'];
                $addons[$addon]['category_name'] = fn_get_addon_category_name($addons[$addon]['category'], $categories);
                if (isset($result[$addon_data['marketplace_id']]['actual_package']['file_name'])) {
                    $addons[$addon]['actual_version'] = $result[$addon_data['marketplace_id']]['actual_package']['file_name'];
                }
                if (isset($result[$addon_data['marketplace_id']]['latest_upgrade_package']['file_name'])) {
                    $addons[$addon]['latest_upgrade_version'] = $result[$addon_data['marketplace_id']]['latest_upgrade_package']['file_name'];
                }
                if (isset($result[$addon_data['marketplace_id']]['current_package']['compatibility'])) {
                    $addons[$addon]['compatibility'] = $result[$addon_data['marketplace_id']]['current_package']['compatibility'];
                }
                if (isset($result[$addon_data['marketplace_id']]['upgrade_available'])) {
                    $addons[$addon]['upgrade_available'] = true;
                }
                if (!isset($result[$addon_data['marketplace_id']]['personal_review'])) {
                    continue;
                }
                $addons[$addon]['personal_review'] = $result[$addon_data['marketplace_id']]['personal_review'];
            }
        }
    }

    if (!empty($params['q'])) {
        $addons = array_filter($addons, static function ($addon_data) use ($params) {
            return (bool) preg_match('/' . preg_quote($params['q'], '/') . '/ui', $addon_data['name'] . $addon_data['supplier']);
        });
    }

    if (!empty($params['name'])) {
        $addons = array_filter($addons, static function ($addon_data) use ($params) {
            return $addon_data['addon'] === $params['name'];
        });
    }

    if (!empty($params['names']) && is_array($params['names'])) {
        $addons = array_filter($addons, static function ($addon_data) use ($params) {
            return in_array($addon_data['addon'], $params['names']);
        });
    }

    if (!empty($params['source'])) {
        $addons = array_filter($addons, static function ($addon_data) use ($params) {
            $is_core_addon = $params['source'] === 'core';
            return $is_core_addon === (bool) $addon_data['is_core_addon'];
        });
    }

    if (!empty($params['supplier'])) {
        $addons = array_filter($addons, static function ($addon_data) use ($params) {
            if (is_array($params['supplier'])) {
                return in_array($addon_data['supplier'], $params['supplier'], true);
            }
            return $addon_data['supplier'] === $params['supplier'];
        });
    }

    if (!empty($params['period']) && $params['period'] !== 'A') {
        list($params['time_from'], $params['time_to']) = fn_create_periods($params);
        $addons = array_filter($addons, static function ($addon_data) use ($params) {
            return isset($addon_data['install_datetime'])
                && $addon_data['install_datetime'] <= $params['time_to']
                && $addon_data['install_datetime'] >= $params['time_from'];
        });
    }

    if ((!empty($params['favorites']))) {
        $addons = array_filter($addons, static function ($addon_data) {
            return isset($addon_data['is_favorite']) && YesNo::toBool($addon_data['is_favorite']);
        });
    }

    if (!empty($params['unidentified'])) {
        $addons = array_filter($addons, static function ($addon_data) {
            return !isset($addon_data['identified']) && !$addon_data['is_core_addon'];
        });
    }

    if (!empty($params['without_rating'])) {
        $addons = array_filter($addons, static function ($addon_data) {
            return !isset($addon_data['personal_review']) && !$addon_data['is_core_addon'];
        });
    }

    if (!empty($params['add_pages'])) {
        $addons = array_filter($addons, static function ($addon_data) {
            return !empty($addon_data['menu_items']);
        });
    }

    if (!empty($params['category_id'])) {
        $addons = array_filter($addons, static function ($addon_data) use ($params) {
            return isset($addon_data['category']) && $addon_data['category'] === (int) $params['category_id'];
        });
    }

    if (!empty($params['store_version'])) {
        $addons = array_filter($addons, static function ($addon_data) use ($params) {
            return isset($addon_data['compatibility']) && in_array($params['store_version'], $addon_data['compatibility']);
        });
    }

    if (!empty($params['available_update'])) {
        $addons = array_filter($addons, static function ($addon_data) {
            return isset($addon_data['upgrade_available']);
        });
    }

    /**
     * @psalm-var array<string, array{
     *   addon: string,
     *   status: string,
     *   name: string,
     *   version: string,
     *   supplier: string,
     *   supplier_link: string,
     *   supplier_page: string,
     *   snapshot_correct: bool,
     *   description: string,
     *   has_icon : string,
     *   is_core_addon: bool,
     *   install_datetime: int|null,
     *   delete_url?: string,
     *   refresh_url?: string,
     *   licensing_url?: string,
     *   url?: string,
     * }> $addons
     */
    $addons = fn_sort_array_by_key($addons, 'name', SORT_ASC);

    /**
     * Executes after gets addons list, allows you to modify list.
     *
     * @param array<string,string|bool> $params         Search params
     * @param int                       $items_per_page Items per page for pagination
     * @param string                    $lang_code      Language code
     * @param int|null                  $storefront_id  Storefront ID to get settings for
     * @param int|null                  $company_id     Company ID to get settings for
     * @param array                     $addons         Addons list
     * @param array                     $addons_counter Counts addons by statuses
     */
    fn_set_hook('get_addons_post', $params, $items_per_page, $lang_code, $storefront_id, $company_id, $addons, $addons_counter);

    /** @psalm-suppress LessSpecificReturnStatement */
    return [$addons, $params, $addons_counter];
}

/**
 * Move addon pack from temporarily folder to specified place and install it if possible.
 *
 * @param string $from Source path.
 * @param string $to   Destination path.
 *
 * @return bool true if installed, false otherwise.
 */
function fn_addons_move_and_install($from, $to)
{
    if (defined('AJAX_REQUEST')) {
        Tygh::$app['ajax']->assign('non_ajax_notifications', true);
    }

    $struct = fn_get_dir_contents($from, false, true, '', '', true);
    $addon_name = '';

    foreach ($struct as $file) {
        if (preg_match('/app.+?addons[^a-zA-Z0-9_]+([a-zA-Z0-9_-]+).+?addon.xml$/i', $file, $matches)) {
            if (!empty($matches[1])) {
                $addon_name = $matches[1];
                break;
            }
        }
    }

    $relative_addon_path = str_replace(Registry::get('config.dir.root') . '/', '', Registry::get('config.dir.addons'));

    if (!file_exists($from . $relative_addon_path . $addon_name . '/addon.xml')) {
        fn_set_notification(
            NotificationSeverity::ERROR,
            __('error'),
            __('broken_addon_pack')
        );

        return false;
    }

    if (!fn_remove_addon_files($addon_name)) {
        fn_set_notification(
            NotificationSeverity::ERROR,
            __('error'),
            __('cant_remove_addon_files')
        );

        return false;
    }

    fn_copy($from, $to);

    if (fn_check_addon_exists($addon_name)) {
        $result = fn_reinstall_addon_files($addon_name);
        if ($result) {
            fn_set_notification('N', __('notice'), __('addon_files_was_copied', [
                '[addon]' => $addon_name
            ]));
        }
    } else {
        fn_install_addon($addon_name);
    }

    fn_rm($from);

    return true;
}

/**
 * @return array
 */
function fn_get_addon_permissions_text()
{
    $messages = array(
        'title' => __('text_full_mode_required'),
        'text' => __('text_forbidden_functionality', array('[product]' => PRODUCT_NAME))
    );

    fn_set_hook('addon_permissions_text', $messages);

    return $messages;
}

/**
 * Load addon
 *
 * @TODO move to add-on initialization service
 *
 * @param string $addon_name addon name
 *
 * @return boolean true if addon loaded, false otherwise
 */
function fn_load_addon($addon_name)
{
    static $cache = [];

    if (!isset($cache[$addon_name])) {
        /** @var \Tygh\Addons\AXmlScheme $addon_schema */
        $addon_schema = SchemesManager::getScheme($addon_name);

        if ($addon_schema) {
            $addon_schema->loadAddon();
            $cache[$addon_name] = true;
        }
    }

    return isset($cache[$addon_name]) ? $cache[$addon_name] : false;
}

/**
 * Update addon version
 *
 * @param string $addon
 * @param string $version
 */
function fn_update_addon_version($addon, $version)
{
    db_query("UPDATE ?:addons SET version = ?s WHERE addon = ?s", $version, $addon);
}

/**
 * Changes favorite status to specified on specified add-on.
 *
 * @param string $addon    Add-on name.
 * @param string $favorite Specified favorite status.
 *
 * @return bool
 */
function fn_update_addon_favorite_status($addon, $favorite)
{
    $addon_data = [
        'addon'       => $addon,
        'is_favorite' => $favorite,
    ];
    return (bool) db_replace_into('addon_data', $addon_data);
}

/**
 * Returns path to addon layouts file in specified theme
 *
 * @param string $addon_name Addon identifier
 * @param string $theme_name Theme name to search layout in
 *
 * @return string Path to layouts file, null otherwise
 */
function fn_get_addon_layouts_path($addon_name, $theme_name = '[theme]')
{
    $addon_layouts_path = null;
    if ($theme_name == '[theme]') {
        $theme_name = fn_get_theme_path($theme_name, 'C');
    }

    $theme = Themes::factory($theme_name);
    $theme_addon_layouts_file = $theme->getContentPath("layouts/addons/{$addon_name}/layouts.xml");

    if ($theme_addon_layouts_file) {
        $addon_layouts_path = $theme_addon_layouts_file[Themes::PATH_ABSOLUTE];
    } elseif (file_exists(Registry::get('config.dir.addons') . $addon_name . '/layouts.xml')) {
        $addon_layouts_path = Registry::get('config.dir.addons') . $addon_name . '/layouts.xml';
    }

    if ($addon_layouts_path) {
        $addon_layouts_path = fn_get_dev_files($addon_layouts_path, true);
    }

    return $addon_layouts_path;
}

/**
 * Validate addon package structure.
 *
 * @param string $addon_name Addon name
 * @param string $path       Path to extracted addon package
 *
 * @return bool
 */
function fn_validate_addon_structure($addon_name, $path)
{
    if (!is_dir($path)) {
        return false;
    }
    $relative_addon_path = fn_get_rel_dir(Registry::get('config.dir.addons'));

    $ignore_list = array(
        'README.md',
        'LICENSE.md',
        '.gitignore',
        'changelog.txt'
    );
    $require_list = array(
        "{$relative_addon_path}{$addon_name}/addon.xml"
    );

    foreach ($require_list as $item) {
        if (!file_exists($path . $item)) {
            return false;
        }
    }

    foreach ($ignore_list as $item) {
        if (file_exists($path . $item)) {
            @unlink($path . $item);
        }
    }

    return true;
}

/**
 * Extract addon package to temporary directory.
 *
 * @param string $addon_archive_path Path to addon archive file
 *
 * @return array|bool
 * Return false if package broken.
 * Return array(addon_name, path/to/extracted/package/)
 */
function fn_extract_addon_package($addon_archive_path)
{
    if (!file_exists($addon_archive_path)) {
        return false;
    }

    $addon_archive_name = pathinfo($addon_archive_path, PATHINFO_FILENAME);

    $extract_path = fn_get_cache_path(false) . 'tmp/' . $addon_archive_name . '/';

    // Re-create source folder
    fn_rm($extract_path);
    fn_mkdir($extract_path);

    if (fn_decompress_files($addon_archive_path, $extract_path)) {
        $addon_name = '';
        $package_path = $extract_path;
        $files = fn_get_dir_contents($extract_path, false, true, 'xml', '', true);
        $relative_addon_path = fn_get_rel_dir(Registry::get('config.dir.addons'));

        foreach (FORBIDDEN_ADDONS_FILES as $forbidden_file) {
            if (file_exists($package_path . $forbidden_file)) {
                return false;
            }
        }

        foreach ($files as $file) {
            if (preg_match('#(?<subpath>.*?)' . $relative_addon_path . '(?<name>[^/]+?)/addon.xml$#i', $file, $matches)) {
                if (!empty($matches['name'])) {
                    $addon_name = $matches['name'];

                    if (!empty($matches['subpath'])) {
                        $package_path .= $matches['subpath'];
                    }
                    break;
                }
            }
        }

        if (!empty($addon_name) && file_exists($package_path . $relative_addon_path . $addon_name . '/addon.xml')) {
            return array($addon_name, $package_path);
        }
    }

    return false;
}

/**
 * Updates language variables of particular addon
 *
 * @param AXmlScheme $addon_scheme  Addon scheme
 */
function fn_update_addon_language_variables(AXmlScheme $addon_scheme)
{
    $language_variables = $addon_scheme->getLanguageValues(false);
    if (!empty($language_variables)) {
        db_query('REPLACE INTO ?:language_values ?m', $language_variables);
    }

    $language_variables = $addon_scheme->getLanguageValues(true);
    if (!empty($language_variables)) {
        db_query('REPLACE INTO ?:original_values ?m', $language_variables);
    }
}

/**
 * Gets vendor values of particular addon
 *
 * @param  string $addon Addon name
 *
 * @return array Array of vendor values
 * @internal
 */
function fn_get_addon_settings_vendor_values($addon)
{
    $vendor_values = array();

    $section = Settings::instance()->getSectionByName($addon, Settings::ADDON_SECTION);
    $section_id = !empty($section['section_id']) ? $section['section_id'] : 0;
    $settings = Settings::instance()->getList($section_id, 0, true);

    foreach ($settings as $setting) {
        $vendor_values[$setting['name']] = Settings::instance()->getAllStorefrontValues($setting['name'], $addon);
    }

    return $vendor_values;
}

/**
 * Gets setting values of particular addon
 *
 * @param  string $addon Addon name
 *
 * @return array Array of setting values
 * @internal
 */
function fn_get_addon_settings_values($addon)
{
    $setting_values = array();

    if (empty($addon)) {
        return $setting_values;
    }

    $setting_values = Settings::instance()->getValues($addon, Settings::ADDON_SECTION, false);
    $setting_values = !empty($setting_values) ? $setting_values : array();

    foreach ($setting_values as $setting_name => $setting_value) {
        if (is_array($setting_value)) {
            $setting_values[$setting_name] = array_keys($setting_value);
        }
    }

    return $setting_values;
}

/**
 * Removes add-on files.
 *
 * @param string     $addon      Add-on ID
 * @param array|null $ftp_access Connection details to remove files by FTP
 *
 * @return bool Whether all add-on files and directories were removed
 */
function fn_remove_addon_files($addon, $ftp_access = null)
{
    $config_dirs = Registry::get('config.dir');

    $addon_files_list = [
        $config_dirs['addons'] . $addon,
        $config_dirs['design_backend'] . 'css/addons/' . $addon,
        $config_dirs['design_backend'] . 'mail/templates/addons/' . $addon,
        $config_dirs['design_backend'] . 'media/fonts/addons/' . $addon,
        $config_dirs['design_backend'] . 'media/images/addons/' . $addon,
        $config_dirs['design_backend'] . 'templates/addons/' . $addon,
        'js/addons/' . $addon,
    ];

    $themes_dirs_list = fn_get_dir_contents($config_dirs['design_frontend']);
    foreach ($themes_dirs_list as $theme) {
        $addon_files_list[] = $config_dirs['design_frontend'] . $theme . '/css/addons/' . $addon;
        $addon_files_list[] = $config_dirs['design_frontend'] . $theme . '/mail/templates/addons/' . $addon;
        $addon_files_list[] = $config_dirs['design_frontend'] . $theme . '/media/fonts/addons/' . $addon;
        $addon_files_list[] = $config_dirs['design_frontend'] . $theme . '/media/images/addons/' . $addon;
        $addon_files_list[] = $config_dirs['design_frontend'] . $theme . '/templates/addons/' . $addon;
    }

    $lang_dirs_list = fn_get_dir_contents($config_dirs['lang_packs']);
    foreach ($lang_dirs_list as $lang_code) {
        $addon_files_list[] = $config_dirs['lang_packs'] . $lang_code . '/addons/' . $addon . '.po';
    }

    foreach ($addon_files_list as $addon_file) {
        if (!file_exists($addon_file)) {
            continue;
        }

        if ($ftp_access !== null) {
            $result = fn_rm_by_ftp($addon_file, $ftp_access);
        } else {
            $result = fn_rm($addon_file);
        }

        if (!$result) {
            return false;
        }
    }

    return true;
}

/**
 * Installs add-on templates from repository to add-on directories.
 * Installs add-on language variables.
 * Reinstalls the add-on settings schema with preserve current settings.
 *
 * @param string $addon  Add-ons name that files must be copied into add-on's directory.
 *
 * @return bool  Returns true if add-on's files was movied successfully or false otherwise.
 */
function fn_reinstall_addon_files($addon)
{
    SchemesManager::clearInternalCache($addon);
    $addon_scheme = SchemesManager::getScheme($addon);

    if (empty($addon_scheme)) {
        return false;
    }

    fn_install_addon_templates($addon_scheme->getId());

    fn_update_addon_language_variables($addon_scheme);

    fn_update_addon_settings(
        $addon_scheme,
        true,
        fn_get_addon_settings_values($addon),
        fn_get_addon_settings_vendor_values($addon)
    );

    fn_clear_cache();

    return true;
}

/**
 * Install add-on layouts for each themes.
 *
 * @param string $addon Add-onn's name.
 */
function fn_install_addon_layouts($addon)
{
    foreach (fn_get_installed_themes() as $theme_name) {
        $addon_layouts_path = fn_get_addon_layouts_path($addon, $theme_name);
        if ($addon_layouts_path) {
            if (fn_allowed_for('ULTIMATE')) {
                foreach (fn_get_all_companies_ids() as $company) {
                    $layouts = Layout::instance($company)->getList(array('theme_name' => $theme_name));
                    foreach ($layouts as $layout_id => $layout) {
                        Exim::instance($company, $layout_id)->importFromFile($addon_layouts_path);
                    }
                }
            } else {
                $layouts = Layout::instance()->getList(array('theme_name' => $theme_name));
                foreach ($layouts as $layout_id => $layout) {
                    Exim::instance(0, $layout_id)->importFromFile($addon_layouts_path);
                }
            }
        }
    }
}

/**
 * Checks if add-on exists.
 *
 * @param string $addon_name add-on name.
 *
 * @return bool Returns true if add-on exists or false otherwise.
 */
function fn_check_addon_exists($addon_name)
{
    return (bool) db_get_field('SELECT addon FROM ?:addons WHERE addon = ?s', $addon_name);
}

/**
 * Gets add-on license key.
 *
 * @param string $addon_id Add-on identifier
 *
 * @return string
 */
function fn_get_addon_license_key($addon_id)
{
    return db_get_field('SELECT marketplace_license_key FROM ?:addons WHERE addon = ?s', $addon_id);
}

/**
 * Updates add-on license key.
 *
 * @param string $addon_id    Add-on identifier
 * @param string $license_key Marketplace license number
 */
function fn_update_addon_license_key($addon_id, $license_key)
{
    /**
     * Executes before add-on's license key is saved in the database,
     * allows you to modify saved data.
     *
     * @param string $addon_id    Add-on identifier
     * @param string $license_key Marketplace license number
     */
    fn_set_hook('update_addon_license_key_pre', $addon_id, $license_key);

    db_query(
        'UPDATE ?:addons SET ?u WHERE addon = ?s',
        [
            'marketplace_license_key' => $license_key,
        ],
        $addon_id
    );

    /**
     * Executes after add-on's license key has been saved in the database,
     * allows you perform additional data manipulation.
     *
     * @param string $addon_id    Add-on identifier
     * @param string $license_key Marketplace license number
     */
    fn_set_hook('update_addon_license_key_post', $addon_id, $license_key);
}

/**
 * Gets specific amount of suppliers from specified list of addons.
 *
 * @param array<string, array<string, string>> $addons         List of addons.
 * @param int                                  $items_per_page Required amount of suppliers.
 *
 * @psalm-param array<
 *    string, array{
 *      addon: string,
 *      delete_url?: string,
 *      description: string,
 *      has_icon: string,
 *      install_datetime: int|null,
 *      is_core_addon: bool,
 *      licensing_url?: string,
 *      name: string,
 *      refresh_url?: string,
 *      snapshot_correct: bool,
 *      status: string,
 *      supplier: string,
 *      supplier_link: string,
 *      supplier_page: string,
 *      url?: string,
 *      version: string
 *    }
 * > $addons
 *
 * @return array<string, array<string, string>>
 *
 * @psalm-return array<
 *   array{
 *     title: string,
 *     href: string,
 *     position: int,
 *   }
 * >
 */
function fn_get_addon_suppliers(array $addons, $items_per_page = 0)
{
    /**
     * @psalm-param array<
     *   string, array{
     *     title: string,
     *     href: string,
     *     position: int,
     *   }
     * > $suppliers
     */
    $suppliers = [];
    foreach ($addons as $addon) {
        if (empty($addon['supplier'])) {
            continue;
        }
        if (!isset($suppliers[$addon['supplier']])) {
            $suppliers[$addon['supplier']] = [
                'title'    => (string) $addon['supplier'],
                'href'     => 'addons.manage&supplier=' . $addon['supplier'],
                'position' => 0,
            ];
        }
        $suppliers[$addon['supplier']]['position']++;
        if (empty($addon['supplier_page'])) {
            continue;
        }
        $suppliers[$addon['supplier']]['href'] = (string) $addon['supplier_page'];
    }

    $suppliers = fn_sort_array_by_key($suppliers, 'position', SORT_DESC);

    if ($items_per_page) {
        return array_slice($suppliers, 0, $items_per_page, true);
    }

    return array_values($suppliers);
}

/**
 * Gets add-on category name by specified category id.
 *
 * @param string                                                               $category_id Add-on category id.
 * @param array<array-key, string|array<string, string|array<string, string>>> $categories  Add-ons categories.
 *
 * @return string|null
 */
function fn_get_addon_category_name($category_id, array $categories = [])
{
    if (empty($categories)) {
        return null;
    }
    foreach ($categories as $category) {
        /** @psalm-suppress PossiblyInvalidArrayOffset */
        if ((int) $category['category_id'] !== $category_id) {
            if (!isset($category['subcategories'])) {
                continue;
            }
            /** @psalm-suppress InvalidArgument */
            $category_name = fn_get_addon_category_name($category_id, $category['subcategories']);
            if ($category_name !== null) {
                return $category_name;
            }
            continue;
        }
        /** @psalm-suppress PossiblyInvalidArrayOffset */
        return $category['category'];
    }
}

/**
 * Gets active addon category ids, alongside with specified category id.
 *
 * @param array<string, string|array> $categories         All addon categories.
 * @param int                         $active_category_id Specified category.
 *
 * @return array|false|string[]
 */
function fn_get_addons_active_category_ids(array $categories, $active_category_id)
{
    foreach ($categories as $category) {
        /** @psalm-suppress PossiblyInvalidArrayOffset */
        if ((int) $category['category_id'] === $active_category_id) {
            return explode('/', $category['id_path']);
        }
        if (empty($category['subcategories'])) {
            continue;
        }
        /** @psalm-suppress InvalidArgument */
        $category_path = fn_get_addons_active_category_ids($category['subcategories'], $active_category_id);
        if (!empty($category_path)) {
            return $category_path;
        }
    }
    return [];
}

/**
 * Calculates distribution of add-on reviews by ratings.
 *
 * @param array<string, array<string>> $addon_reviews Reviews of specified add-on.
 *
 * @return int[][]
 */
function fn_get_addons_review_stats(array $addon_reviews)
{
    $review_stats = [
        5 => [
            'count' => 0,
            'percentage' => 0,
        ],
        4 => [
            'count' => 0,
            'percentage' => 0,
        ],
        3 => [
            'count' => 0,
            'percentage' => 0,
        ],
        2 => [
            'count' => 0,
            'percentage' => 0,
        ],
        1 => [
            'count' => 0,
            'percentage' => 0,
        ],
    ];
    $total_review = count($addon_reviews);
    foreach ($addon_reviews as $review) {
        $review_stats[$review['rating_value']]['count']++;
    }
    foreach ($review_stats as &$review_stat) {
        $review_stat['percentage'] = $review_stat['count'] / $total_review * 100;
    }
    unset($review_stat);
    return $review_stats;
}

/**
 * Returns list of links for support block of addon detailed page sidebar.
 *
 * @return array<string, array<string, string>>
 */
function fn_get_addons_support_links()
{
    return [
        'website' => [
            'text' => __('addons.website'),
            'url' => Registry::get('config.resources.product_url'),
        ],
        'documentation' => [
            'text' => __('addons.documentation'),
            'url' => Registry::get('config.resources.docs_url'),
        ],
        'forum' => [
            'text' => __('addons.forum'),
            'url' => Registry::get('config.resources.forum'),
        ],
        'get_support' => [
            'text' => __('addons.get_support'),
            'url' => Registry::get('config.resources.core_addons_supplier_url'),
        ],
    ];
}
