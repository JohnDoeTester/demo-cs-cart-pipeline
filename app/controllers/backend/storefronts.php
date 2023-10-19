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

use Tygh\BlockManager\Layout;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\StorefrontStatuses;
use Tygh\Enum\UserTypes;
use Tygh\Helpdesk;
use Tygh\Languages\Languages;
use Tygh\Registry;
use Tygh\Storefront\Storefront;
use Tygh\Themes\Styles;
use Tygh\Themes\Themes;
use Tygh\Tools\Url;
use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

/** @var \Tygh\Storefront\Repository $repository */
$repository = Tygh::$app['storefront.repository'];
/** @var array $auth */
$auth = & Tygh::$app['session']['auth'];

if (fn_allowed_for('ULTIMATE')) {
    if ($mode === 'update') {
        if (!isset($_REQUEST['storefront_id'])) {
            return [CONTROLLER_STATUS_OK, 'companies.update?company_id='];
        }
        $storefront = $repository->findById($_REQUEST['storefront_id']);
        list($company_id) = $storefront->getCompanyIds();
        return [CONTROLLER_STATUS_OK, 'companies.update?company_id=' . $company_id];
    } elseif ($mode === 'add') {
        return [CONTROLLER_STATUS_OK, 'companies.add'];
    } elseif ($mode === 'manage') {
        return [CONTROLLER_STATUS_OK, 'companies.manage'];
    } elseif ($mode !== 'picker') {
        return [CONTROLLER_STATUS_NO_PAGE];
    }
}

/** @var \Tygh\Storefront\Factory $factory */
$factory = Tygh::$app['storefront.factory'];

/** @var \Tygh\SmartyEngine\Core $view */
$view = Tygh::$app['view'];

$is_storefronts_limit_reached = Helpdesk::isStorefrontsLimitReached();

/** @var string $mode */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'update') {
        $params = array_merge([
            'storefront_data' => [],
        ], $_REQUEST);

        $storefront_id = empty($params['storefront_data']['storefront_id'])
            ? null
            : $params['storefront_data']['storefront_id'];

        $stored_storefront = $storefront_id ? $repository->findById($storefront_id) : null;

        if ($storefront_id && !$stored_storefront) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        if (!$storefront_id && $is_storefronts_limit_reached) {
            return [CONTROLLER_STATUS_DENIED];
        }

        $storefront = $factory->fromArray($params['storefront_data'], $stored_storefront);

        $result = $repository->save($storefront);
        $result->showNotifications(true);

        if (!$storefront_id && $result->isSuccess()) {
            $params['storefront_data']['new_storefront_id'] = $result->getData();
        }

        fn_update_storefront_currencies($params['storefront_data']);
        fn_update_storefront_languages($params['storefront_data']);

        $redirect_mode = 'manage';
        $redirect_query_params = [];

        if (!$result->isSuccess()) {
            fn_save_post_data('storefront_data');

            $redirect_mode = 'add';

            if ($storefront_id) {
                $redirect_mode = 'update';
                $redirect_query_params['storefront_id'] = $storefront_id;
            }
        } else {
            $redirect_mode = 'update';
            $redirect_query_params['storefront_id'] = $result->getData();
        }

        return [CONTROLLER_STATUS_OK, Url::buildUrn(['storefronts', $redirect_mode], $redirect_query_params)];
    }

    if ($mode === 'delete') {
        $params = array_merge([
            'storefront_id' => 0,
            'redirect_url'  => 'storefronts.manage',
        ], $_REQUEST);

        if ($params['storefront_id']) {
            $storefront = $repository->findById($params['storefront_id']);
            if ($storefront->is_default) {
                fn_set_notification(NotificationSeverity::ERROR, '', __('cant_remove_default_storefront', [
                    '[url]' => $storefront->url,
                ]));
            } else {
                $repository->delete($storefront);
            }
        }

        return [CONTROLLER_STATUS_OK, $params['redirect_url']];
    }

    if ($mode === 'm_delete') {
        $params = array_merge([
            'storefront_ids' => [],
        ], $_REQUEST);

        foreach ($params['storefront_ids'] as $storefront_id) {
            $storefront = $repository->findById($storefront_id);
            if ($storefront->is_default) {
                fn_set_notification(NotificationSeverity::WARNING, '', __('cant_remove_default_storefront', [
                    '[url]' => $storefront->url,
                ]));
                continue;
            }
            $repository->delete($storefront);
        }
    }

    if ($mode === 'm_open' || $mode === 'm_close') {
        $params = array_merge([
            'storefront_ids' => [],
            'return_url'     => 'storefronts.manage',
        ], $_REQUEST);

        $status = $mode === 'm_open'
            ? StorefrontStatuses::OPEN
            : StorefrontStatuses::CLOSED;

        foreach ($params['storefront_ids'] as $storefront_id) {
            $storefront = $repository->findById($storefront_id);
            $storefront->status = $status;
            if ($storefront->access_key === '' && $status === StorefrontStatuses::CLOSED) {
                $storefront->access_key = md5(TIME);
            }
            $repository->save($storefront);
        }

        fn_set_notification(
            'W',
            __('information'),
            __('storefront_status_changed.' . $status, [
                count($params['storefront_ids']),
            ])
        );

        fn_init_storefronts_stats();

        return [CONTROLLER_STATUS_OK, $params['return_url']];
    }

    if ($mode === 'update_status') {
        $params = array_merge([
            'storefront_id' => 0,
            'status'        => null,
            'return_url'    => 'storefronts.manage',
        ], $_REQUEST);

        $storefront = $repository->findById($params['storefront_id']);
        $storefront->status = $params['status'];
        if ($storefront->access_key === '' && $params['status'] === StorefrontStatuses::CLOSED) {
            $storefront->access_key = md5(TIME);
        }
        $result = $repository->save($storefront);
        if ($result->isSuccess()) {
            fn_set_notification(
                'W',
                __('information'),
                __('storefront_status_changed.' . $params['status'], [
                    1,
                ])
            );
        }

        if (defined('AJAX_REQUEST')) {
            /** @var \Tygh\Ajax $ajax */
            $ajax = Tygh::$app['ajax'];
            $ajax->assign('result', $result->isSuccess());
        }

        fn_init_storefronts_stats();

        return [CONTROLLER_STATUS_OK, urldecode($params['return_url'])];
    }

    if ($mode === 'clone_default_layout') {
        if (fn_get_storage_data('is_storefront_layout_clone_allowed')) {
            fn_set_storage_data('is_storefront_layout_clone_allowed', false);

            $default_storefront = $repository->findDefault();
            list($storefronts,) = $repository->find();
            foreach ($storefronts as $storefront) {
                if ($storefront->is_default) {
                    continue;
                }

                $repository->installTheme(
                    $storefront->storefront_id,
                    $default_storefront->theme_name,
                    $default_storefront->storefront_id
                );
            }
        }
        exit;
    }


    return [CONTROLLER_STATUS_OK, 'storefronts.manage'];
}

if ($mode === 'manage' || $mode === 'picker') {
    $params = array_merge([
        'items_per_page' => Registry::get('settings.Appearance.admin_elements_per_page'),
    ], $_REQUEST);

    if (!empty($params['company_id'])) {
        $params['company_ids'] = [$params['company_id']];
        $params['is_search'] = true;
    } elseif (!empty($auth['company_id'])) {
        $params['company_ids'] = [$auth['company_id']];
        $params['is_search'] = true;
    }

    if (isset($params['page_size'])) {
        $params['items_per_page'] = $params['page_size'];
        unset($params['page_size']);
    }

    if (isset($params['q'])) {
        $params['name'] = $params['q'];
        $params['is_search'] = true;
        unset($params['q']);
    }

    if (isset($params['ids'])) {
        $params['storefront_id'] = $params['ids'];
        unset($params['ids']);
    }

    /** @var \Tygh\Storefront\Storefront[] $storefronts */
    list($storefronts, $search) = $repository->find($params, $params['items_per_page']);

    if ($mode === 'picker' && $action === 'inline') {
        /** @var \Tygh\Ajax $ajax */
        $ajax = Tygh::$app['ajax'];

        $ajax->assign('objects', array_values(array_map(function(Storefront $storefront) {
            $company_ids = $storefront->getCompanyIds();

            return [
                'id'   => $storefront->storefront_id,
                'text' => $storefront->name,
                'data' => [
                    'company_ids'       => $company_ids,
                    'company_id'        => reset($company_ids),
                    'access_key'        => $storefront->access_key,
                    'extra'             => $storefront->extra,
                    'is_default'        => $storefront->is_default,
                    'name'              => $storefront->name,
                    'redirect_customer' => $storefront->redirect_customer,
                    'status'            => $storefront->status,
                    'storefront_id'     => $storefront->storefront_id,
                    'theme_name'        => $storefront->theme_name,
                    'url'               => $storefront->url,
                ]
            ];
        }, $storefronts)));
        $ajax->assign('total_objects', $search['total_items']);

        return [CONTROLLER_STATUS_NO_CONTENT];
    } else {
        $currencies = fn_get_currencies_list();

        $languages = Languages::getAll();

        $countries = fn_get_simple_countries();

        $view->assign([
            'storefronts'                  => $storefronts,
            'search'                       => $search,
            'is_storefronts_limit_reached' => Helpdesk::isStorefrontsLimitReached(),
            'all_currencies'               => $currencies,
            'all_languages'                => $languages,
            'all_countries'                => $countries,
        ]);

        if ($mode === 'picker') {
            $view->display('pickers/storefronts/picker_contents.tpl');
            exit();
        }
    }
}

if ($mode === 'update' || $mode === 'add') {
    $params = array_merge([
        'storefront_id' => 0,
    ], $_REQUEST);

    $storefront = null;
    if ($storefront_data = fn_restore_post_data('storefront_data')) {
        /** @var \Tygh\Storefront\Factory $factory */
        $factory = Tygh::$app['storefront.factory'];
        $storefront = $factory->fromArray($storefront_data);
    } elseif ($params['storefront_id']) {
        $storefront = $repository->findById($params['storefront_id']);
    }

    if (
        $mode === 'update' && !$storefront
        || $mode === 'update' && !fn_check_permission_storefronts($storefront->storefront_id)
    ) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $tabs = [
        'general'   => [
            'title' => __('general'),
            'js'    => true,
        ],
        'regions'   => [
            'title' => __('regions'),
            'js'    => true,
        ],
        'companies' => [
            'title' => __('companies'),
            'js'    => true,
        ],
    ];

    $currency_ids = [];
    $language_ids = [];
    $languages = [];
    $currencies = [];
    $is_localization_picker_allowed = true;
    if ($storefront) {
        $currency_ids = $storefront->getAvailableCurrencyIds();
        $language_ids = $storefront->getAvailableLanguageIds();
        $languages = Languages::get(['status' => [ObjectStatuses::ACTIVE]]);
        $currencies = fn_get_currencies_list(['status' => ObjectStatuses::ACTIVE]);

        if (db_get_field('SELECT COUNT(1) FROM ?:storefronts') <= 1) {
            $is_localization_picker_allowed = false;
        }
    }

    $countries = fn_get_simple_countries();
    $selected_countries = [];
    if ($storefront) {
        $selected_countries = array_intersect_key($countries, array_flip($storefront->getCountryCodes()));
        $countries = array_diff_key($countries, $selected_countries);
    }

    $current_style = null;
    $current_theme = null;
    $storefront_admins = [];
    if ($storefront && $storefront->storefront_id) {
        $layout = Layout::instance(0, [], $storefront->storefront_id)->getDefault($storefront->theme_name);
        $current_theme = Themes::factory($storefront->theme_name)->getManifest()['title'];
        $current_style = empty($layout['style_id']) ? '' : Styles::factory($storefront->theme_name)->get($layout['style_id'])['name'];

        if (
            fn_allowed_for('MULTIVENDOR:ULTIMATE')
            && (empty($auth['storefront_id']) || $auth['storefront_id'] === $params['storefront_id'])
            && fn_check_user_access($auth['user_id'], 'manage_stores')
        ) {
            $tabs['administrators'] = [
                'title' => __('administrators'),
                'js'    => true,
            ];

            list($storefront_admins, ) = fn_get_users(
                [
                    'storefront_id' => $storefront->storefront_id,
                    'user_type'     => UserTypes::ADMIN
                ],
                $auth,
                Registry::get('settings.Appearance.admin_elements_per_page')
            );
        }
    }

    Registry::set('navigation.tabs', $tabs);

    $is_forbidden_change_main_info = !empty($auth['storefront_id']);

    $view->assign([
        'storefront'                     => $storefront,
        'all_currency_ids'               => $currency_ids,
        'all_language_ids'               => $language_ids,
        'all_currencies'                 => $currencies,
        'all_languages'                  => $languages,
        'all_countries'                  => $countries,
        'selected_countries_list'        => $selected_countries,
        'current_theme'                  => $current_theme,
        'current_style'                  => $current_style,
        'is_storefronts_limit_reached'   => $is_storefronts_limit_reached,
        'is_localization_picker_allowed' => $is_localization_picker_allowed,
        'is_forbidden_change_main_info'  => $is_forbidden_change_main_info,
        'storefront_admins'              => $storefront_admins
    ]);
}

return [CONTROLLER_STATUS_OK];
