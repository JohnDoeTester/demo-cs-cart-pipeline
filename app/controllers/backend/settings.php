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

use Tygh\Providers\HelpdeskProvider;
use Tygh\Providers\StorefrontProvider;
use Tygh\Registry;
use Tygh\Settings;
use Tygh\Helpdesk;
use Tygh\Tools\Url;

defined('BOOTSTRAP') or die('Access denied');

if (fn_allowed_for('ULTIMATE')) {
    $storefront_id = 0;
    if (fn_get_runtime_company_id()) {
        $storefront_id = StorefrontProvider::getStorefront()->storefront_id;
    }
} else {
    if (
        fn_allowed_for('MULTIVENDOR:ULTIMATE')
        && !empty($auth['storefront_id'])
    ) {
        $storefront_id = (int) $auth['storefront_id'];
    } else {
        $storefront_id = empty($_REQUEST['storefront_id'])
            ? 0
            : (int) $_REQUEST['storefront_id'];
    }

    if (!$storefront_id) {
        $storefront_repository = StorefrontProvider::getRepository();
        if ($storefront_repository->getCount() === 1) {
            $first_storefront = $storefront_repository->findFirstActiveStorefront();
            if ($first_storefront) {
                $storefront_id = $first_storefront->storefront_id;
            }
        }
    }
}

$section_id = empty($_REQUEST['section_id'])
    ? 'General'
    : $_REQUEST['section_id'];

// Convert section name to section_id
$section = Settings::instance()->getSectionByName($section_id);
if (isset($section['section_id'])) {
    $section_id = $section['section_id'];
} else {
    return array(CONTROLLER_STATUS_NO_PAGE);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    fn_trusted_vars('update');
    $_suffix = '';

    if ($mode == 'update') {
        if (isset($_REQUEST['update']) && is_array($_REQUEST['update'])) {
            foreach ($_REQUEST['update'] as $k => $v) {
                Settings::instance(['storefront_id' => $storefront_id])->updateValueById($k, $v);

                if (!empty($_REQUEST['update_all_vendors'][$k])) {
                    Settings::instance(['storefront_id' => $storefront_id])->resetAllOverrides($k);
                }
            }
        }
        $_suffix = 'manage';
        if (defined('AJAX_REQUEST')) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

    }

    if ($mode == 'change_store_mode') {
        $store_mode = $_REQUEST['store_mode'];
        $license_number = empty($_REQUEST['license_number']) ? '' : $_REQUEST['license_number'];

        switch ($store_mode) {
            case 'full': {
                if (empty($license_number)) {
                    fn_set_storage_data('store_mode_errors', serialize(array('empty_number' => array(
                        'type' => 'E',
                        'title' => __('error'),
                        'text' => __('license_number_cannot_be_empty'),
                    ))));

                } else {
                    $current_license_status = Tygh::$app['session']['last_status'];

                    list($license_status, $server_messages, $store_mode) = Helpdesk::getStoreMode($license_number, $auth, array('store_mode_selector' => 'Y'));

                    if ($license_status == 'ACTIVE') {
                        // Save data
                        Settings::instance()->updateValue('license_number', $license_number, '', false, false, false);
                        fn_set_storage_data('store_mode', $store_mode, true);
                        fn_set_storage_data('store_mode_trial', null);

                        call_user_func(HelpdeskProvider::getAccountConnectionReporter());
                    } else {

                        $messages = $server_messages;

                        if (empty($messages)) {
                            $messages['unable_to_check'] = array(
                                'type' => 'E',
                                'title' => __('error'),
                                'text' => __('unable_to_check_license'),
                            );
                        }

                        fn_set_storage_data('store_mode_errors', serialize($messages));
                        fn_set_storage_data('store_mode_license', $license_number);

                        if (fn_get_storage_data('store_mode') !== 'new' && $license_status !== 'LICENSE_IS_INVALID') {
                            Tygh::$app['session']['last_status'] = $license_status;
                        } else {
                            Tygh::$app['session']['last_status'] = $current_license_status;
                        }
                    }

                    if ($current_license_status === 'ACTIVE' || $license_status === 'ACTIVE') {
                        unset(Tygh::$app['session']['last_status']);
                    }

                    Tygh::$app['session']['mode_recheck'] = true;
                }
                break;
            }
            case 'trial': {
                fn_set_storage_data('store_mode', $store_mode);
                Tygh::$app['session']['mode_recheck'] = true;
                break;
            }
        }

        $redirect_url = empty($_REQUEST['redirect_url']) ? fn_url() : $_REQUEST['redirect_url'];
        $has_errors = fn_get_storage_data('store_mode_errors');

        if (strpos($redirect_url, 'welcome') !== false && empty($has_errors)) {
            $redirect_url = fn_query_remove($redirect_url, 'welcome');
            $redirect_url = fn_link_attach($redirect_url, 'welcome=setup_completed');
        }

        unset($_REQUEST['redirect_url']);

        fn_clear_cache();

        return array(CONTROLLER_STATUS_REDIRECT, $redirect_url);
    }

    $redirect_url_params = [
        'section_id' => Settings::instance()->getSectionTextId($section_id),
    ];

    if (fn_allowed_for('MULTIVENDOR')) {
        $redirect_url_params['storefront_id'] = $storefront_id;
    }

    return [
        CONTROLLER_STATUS_OK,
        Url::buildUrn(['settings', $_suffix], $redirect_url_params),
    ];
}

//
// OUTPUT routines
//
if ($mode == 'manage') {
    $subsections = Settings::instance()->getSectionTabs($section_id, CART_LANGUAGE);

    $options = Settings::instance(['storefront_id' => $storefront_id])->getList($section_id);

    $ln = Settings::instance()->getSettingDataByName('license_number');
    if (!empty($options[$ln['section_tab_name']][$ln['object_id']]['value'])) {
        $options[$ln['section_tab_name']][$ln['object_id']]['value'] =
            Helpdesk::masqueLicenseNumber(
                $options[$ln['section_tab_name']][$ln['object_id']]['value'],
                Registry::ifGet('config.demo_mode', false)
            );
    }

    fn_update_lang_objects('subsections', $subsections);

    // [Page sections]
    if (!empty($subsections)) {
        Registry::set('navigation.tabs.main', array (
            'title' => __('main'),
            'js' => true
        ));
        foreach ($subsections as $k => $v) {
            Registry::set('navigation.tabs.' . $k, array (
                'title' => $v['description'],
                'js' => true
            ));
        }
    }
    // [/Page sections]

    // Set navigation menu
    $sections = Registry::get('navigation.static.top.settings.items');
    if (!Registry::get('runtime.simple_ultimate') && $storefront_id) {
        $sections = fn_filter_settings_sections_by_accessibility(
            $sections,
            Settings::instance(['storefront_id' => $storefront_id])->getCoreSections()
        );
        $sections = array_map(
            function (array $section_data) use ($storefront_id) {
                if (!isset($section_data['href'])) {
                    return $section_data;
                }

                $section_data['href'] = fn_link_attach($section_data['href'], "storefront_id={$storefront_id}");

                return $section_data;
            },
            $sections
        );
    }

    fn_update_lang_objects('sections', $sections);

    $select_storefront = false;
    //display storefront switch if at least one setting in the selected section supports multiple storefronts
    foreach ($options as $settings) {
        foreach ((array) $settings as $setting) {
            if (
                !empty($setting['edition_type'])
                && (strpos($setting['edition_type'], Settings::STOREFRONT) !== false
                || fn_allowed_for('ULTIMATE') && strpos($setting['edition_type'], Settings::VENDOR) !== false)
            ) {
                $select_storefront = true;
                break 2;
            }
        }
    }

    Registry::set('navigation.dynamic.sections', $sections);
    Registry::set('navigation.dynamic.active_section', Settings::instance()->getSectionTextId($section_id));

    Tygh::$app['view']->assign('options', $options);
    Tygh::$app['view']->assign('subsections', $subsections);
    Tygh::$app['view']->assign('section_id', Settings::instance()->getSectionTextId($section_id));
    Tygh::$app['view']->assign('settings_title', Settings::instance()->getSectionName($section_id));
    Tygh::$app['view']->assign('selected_storefront_id', $storefront_id);
    Tygh::$app['view']->assign('select_storefront', $select_storefront);
}
