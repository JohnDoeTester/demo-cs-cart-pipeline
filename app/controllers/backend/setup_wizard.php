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

use Tygh\Registry;
use Tygh\Settings;
use Tygh\BlockManager\Layout;
use Tygh\Themes\Themes;
use Tygh\Themes\Styles;
use Tygh\Enum\YesNo;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\SiteArea;
use Tygh\Enum\NotificationSeverity;

defined('BOOTSTRAP') or die('Access denied');

/** @var array<string, int|string|array> $auth */
$auth = Tygh::$app['session']['auth'];

if (defined('RESTRICTED_ADMIN') || !fn_is_setup_wizard_panel_available($auth)) {
    return [CONTROLLER_STATUS_DENIED];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'update') {
        if (empty($_REQUEST['settings'])) {
            return [CONTROLLER_STATUS_OK, 'setup_wizard.manage'];
        }

        foreach ($_REQUEST['settings'] as $setting_name => $value) {
            Settings::instance()->updateValue($setting_name, $value);
        }
    } elseif ($mode === 'update_themes') {
        if (empty($_REQUEST['theme_name'])) {
            return [CONTROLLER_STATUS_OK, 'setup_wizard.manage'];
        }

        /** @var \Tygh\Storefront\Repository $storefront_repository */
        $storefront_repository = Tygh::$app['storefront.repository'];
        /** @var \Tygh\Storefront\Storefront $storefront */
        $storefront = Tygh::$app['storefront'];

        $storefront->theme_name = $_REQUEST['theme_name'];
        $storefront_repository->save($storefront);

        $layout = Layout::instance(0, [], $storefront->storefront_id)->getList([
            'theme_name' => $storefront->theme_name,
            'is_default' => true,
        ]);
        $layout = reset($layout);

        if (!empty($_REQUEST['style'])) {
            $theme = Themes::factory(fn_get_theme_path('[theme]', SiteArea::STOREFRONT));
            $theme_manifest = $theme->getManifest();

            if (empty($theme_manifest['converted_to_css'])) {
                Styles::factory($_REQUEST['theme_name'])->setStyle($layout['layout_id'], $_REQUEST['style']);
            }
        }

        // We need to re-init layout
        fn_init_layout(['s_layout' => $layout['layout_id']]);

        // Delete compiled CSS file
        fn_clear_cache('assets');

        fn_clear_cache('registry');

        fn_clear_template_cache();
    } elseif ($mode === 'update_logos') {
        fn_attach_image_pairs('sw_logotypes', 'logos');
    } elseif ($mode === 'update_shippings') {
        fn_update_shipping($_REQUEST['shipping_data'], $_REQUEST['shipping_id']);
    } elseif ($mode === 'change_money_transfer') {
        fn_setup_wizard_update_money_transfer_type($_REQUEST['money_transfer_type']);
    } elseif ($mode === 'change_registration_flow') {
        fn_setup_wizard_update_vendor_registration_flow($_REQUEST['registration_flow_type']);
    } elseif ($mode === 'change_vendor_data_premoderation') {
        fn_setup_wizard_update_vendor_data_premoderation($_REQUEST['vendor_data_premoderation']);
    } elseif ($mode === 'update_addon_status') {
        $addon_name = isset($_REQUEST['addon_name']) ? $_REQUEST['addon_name'] : null;
        if (!isset($addon_name)) {
            return [CONTROLLER_STATUS_OK, 'setup_wizard.manage'];
        }

        foreach ($addon_name as $name) {
            $addon_state = (isset($_REQUEST[$name . '_state'])) ? YesNo::toBool($_REQUEST[$name . '_state']) : null;

            if (!isset($addon_state)) {
                break;
            }

            if ($addon_state) {
                fn_setup_wizard_install_addon($name);

                continue;
            }

            if (Registry::get('addons.' . $name . '.status') === ObjectStatuses::ACTIVE) {
                fn_update_addon_status($name, ObjectStatuses::DISABLED);
            }
        }

        return [CONTROLLER_STATUS_OK, 'setup_wizard.manage'];
    } elseif ($mode === 'remove_demo_data') {
        // Fallback to remove demo data for root admin via request
        if ($auth['is_root'] !== YesNo::YES) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        if (fn_get_storage_data('sw_demo_data_removed') === YesNo::YES) {
            return [CONTROLLER_STATUS_OK, 'index.index'];
        }

        $res = fn_remove_demo_data();

        if ($res) {
            fn_set_storage_data('sw_demo_data_removed', YesNo::YES);
        }

        return [CONTROLLER_STATUS_OK, 'index.index'];
    }

    return [CONTROLLER_STATUS_OK, 'setup_wizard.manage'];
}

if ($mode === 'manage') {
    $setup_wizard = fn_get_schema('setup_wizard', 'settings');
    $setup_wizard = fn_sort_array_by_key($setup_wizard, 'position', SORT_ASC);

    foreach ($setup_wizard as $tab_id => &$tab_data) {
        if (empty($tab_data['sections'])) {
            continue;
        }

        foreach ($tab_data['sections'] as $section_id => &$section) {
            if (isset($section['template'])) {
                if (isset($section['callback']) && is_callable($section['callback'])) {
                    $section['setting_data'] = call_user_func($section['callback']);
                }

                if (empty($section['setting_data'])) {
                    unset($tab_data['sections'][$section_id]);
                }
                continue;
            }

            if (!empty($section['items'])) {
                foreach ($section['items'] as $item_id => &$item) {
                    $item['setting_data'] = fn_setup_wizard_get_setting_data($item);

                    if (!empty($item['setting_data'])) {
                        continue;
                    }

                    unset($section['items'][$item_id]);
                }
            }

            if (empty($section['items'])) {
                unset($tab_data['sections'][$section_id]);
            }

            if (empty($section['hidden_items'])) {
                continue;
            }

            foreach ($section['hidden_items'] as $item_id => &$item) {
                $item['setting_data'] = fn_setup_wizard_get_setting_data($item);
            }
        }
        if (empty($tab_data['sections']) && empty($tab_data['extra'])) {
            unset($setup_wizard[$tab_id]);
        }
    }

    $company_id = fn_get_runtime_company_id();
    /** @var \Tygh\Storefront\Storefront $storefront */
    $storefront = Tygh::$app['storefront'];
    if ($storefront) {
        /** @var \Tygh\Storefront\Repository $storefront_repository */
        $storefront_repository = Tygh::$app['storefront.repository'];
        $storefront = $storefront_repository->findById($storefront->storefront_id);

        if ($storefront) {
            $layout = Layout::instance(0, [], $storefront->storefront_id)->getList([
                'theme_name' => $storefront->theme_name,
                'is_default' => true,
            ]);
            $layout = reset($layout);
            $available_themes = fn_get_available_themes($storefront->theme_name);

            Tygh::$app['view']->assign('layout', $layout);
            Tygh::$app['view']->assign('available_themes', $available_themes);

            Tygh::$app['view']->assign('cse_logo_types', [
                'theme' => ['text' => 'text_customer_area_logo'],
                'mail'  => ['text' => 'text_mail_area_logo'],
            ]);
            Tygh::$app['view']->assign('cse_logos', fn_get_logos($company_id, $layout['layout_id'], $layout['style_id'], $storefront->storefront_id));
        }
    }

    Tygh::$app['view']->assign('setup_wizard', $setup_wizard);
    Tygh::$app['view']->assign('shippings', fn_get_available_shippings($company_id));
    Tygh::$app['view']->assign('vendors_settings', fn_setup_wizard_prepare_vendors_settings($setup_wizard['vendors']));
    Tygh::$app['view']->assign('sw_demo_data_removed', fn_get_storage_data('sw_demo_data_removed'));
}

