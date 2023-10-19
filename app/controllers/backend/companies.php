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
use Tygh\Enum\ProductTracking;
use Tygh\Enum\ProfileTypes;
use Tygh\Enum\StorefrontStatuses;
use Tygh\Enum\VendorPayoutApprovalStatuses;
use Tygh\Enum\VendorStatuses;
use Tygh\Enum\YesNo;
use Tygh\Helpdesk;
use Tygh\Http;
use Tygh\Languages\Languages;
use Tygh\Navigation\LastView;
use Tygh\Providers\VendorServicesProvider;
use Tygh\Registry;
use Tygh\Settings;
use Tygh\Themes\Styles;
use Tygh\Themes\Themes;
use Tygh\Tools\DateTimeHelper;
use Tygh\Tygh;
use Tygh\VendorPayouts;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $suffix = '';

    // Define trusted variables that shouldn't be stripped
    fn_trusted_vars(
        'company_data'
    );

    //
    // Processing additon of new company
    //
    if ($mode === 'add') {
        $suffix = '.add';

        if (!empty($_REQUEST['company_data']['company'])) {  // Checking for required fields for new company

            if (Registry::get('runtime.simple_ultimate')) {
                Registry::set('runtime.simple_ultimate', false);
            }

            if (
                isset($_REQUEST['company_data']['is_create_vendor_admin'])
                && $_REQUEST['company_data']['is_create_vendor_admin'] === YesNo::YES
            ) {
                $params = $_REQUEST;
                $fields = isset($params['company_data']['fields'])
                    ? $params['company_data']['fields']
                    : [];
                $company_data = fn_mve_extract_company_data_from_profile($fields);
                $params['company_data']['admin_firstname'] = !empty($params['company_data']['admin_firstname'])
                    ? $params['company_data']['admin_firstname']
                    : $company_data['admin_firstname'];
                $params['company_data']['admin_lastname'] = !empty($params['company_data']['admin_lastname'])
                    ? $params['company_data']['admin_lastname']
                    : $company_data['admin_lastname'];

                if (
                    !empty($params['company_data']['admin_username'])
                    && db_get_field('SELECT COUNT(*) FROM ?:users WHERE user_login = ?s', $_REQUEST['company_data']['admin_username']) > 0
                ) {
                    fn_set_notification(NotificationSeverity::ERROR, __('error'), __('error_admin_not_created_name_already_used'));
                    fn_save_post_data('company_data', 'update'); // company data and settings
                    $suffix = '.add';
                } else {
                    // Adding company record
                    $company_id = fn_update_company($params['company_data']);

                    if (!empty($company_id)) {
                        $suffix = ".update?company_id=$company_id";
                        if (
                            isset($params['company_data']['is_create_vendor_admin'])
                            && $params['company_data']['is_create_vendor_admin'] === YesNo::YES
                        ) {
                            if (db_get_field('SELECT COUNT(*) FROM ?:users WHERE email = ?s', $params['company_data']['email']) > 0) {
                                fn_set_notification(
                                    NotificationSeverity::ERROR,
                                    __('error'),
                                    __('error_admin_not_created_email_already_used')
                                );
                            } else {
                                // Add company's administrator
                                if (fn_is_restricted_admin($params) === true) {
                                    return [CONTROLLER_STATUS_DENIED];
                                }

                                $company_data = $params['company_data'];
                                $company_data['company_id'] = $company_id;
                                $company_data['is_root'] = YesNo::NO;
                                $fields = isset($params['user_data']['fields'])
                                    ? $params['user_data']['fields']
                                    : [];

                                if (!empty($company_data['fields'])) {
                                    $fields = fn_mve_profiles_match_company_and_user_fields(
                                        $company_data['fields']
                                    ) + $fields;
                                }

                                fn_create_company_admin($company_data, $fields, true);
                            }
                        }
                    } else {
                        fn_save_post_data('company_data', 'update');
                    }
                }
            } else {
                $company_id = fn_update_company($_REQUEST['company_data']);
            }

            if (!empty($company_id)) {
                if (fn_allowed_for('ULTIMATE') && !empty($_REQUEST['update'])) {
                    fn_ult_set_company_settings_information($_REQUEST['update'], $company_id);
                }

                $suffix = ".update?company_id=$company_id";

                $redirect_url = empty($_REQUEST['redirect_url']) ? 'companies' . $suffix : $_REQUEST['redirect_url'];

                if (defined('AJAX_REQUEST')) {
                    Tygh::$app['ajax']->assign('non_ajax_notifications', true);
                    Tygh::$app['ajax']->assign('force_redirection', fn_url($redirect_url));

                    exit();
                }
            } else {
                fn_save_post_data('company_data', 'update');
            }
        }

        if (fn_allowed_for('ULTIMATE') && !empty($company_id)) {
            fn_ult_set_company_settings_information($_REQUEST['update'], $company_id);
        }
    }

    //
    // Processing updating of company element
    //
    if ($mode == 'update') {

        if (!empty($_REQUEST['company_data']['company'])) {
            if (!empty($_REQUEST['company_id']) && Registry::get('runtime.company_id') && Registry::get('runtime.company_id') != $_REQUEST['company_id']) {
                fn_company_access_denied_notification();
                fn_save_post_data('company_data', 'update');
            } else {
                // Updating company record
                fn_update_company($_REQUEST['company_data'], $_REQUEST['company_id'], DESCR_SL);
            }

            if (fn_allowed_for('ULTIMATE') && !empty($_REQUEST['company_id'])) {
                fn_ult_set_company_settings_information($_REQUEST['update'], $_REQUEST['company_id']);

                /** @var \Tygh\Storefront\Repository $repository */
                $repository = Tygh::$app['storefront.repository'];
                /** @var \Tygh\Storefront\Storefront $storefront */
                $storefront = $repository->findByCompanyId($_REQUEST['company_id']);
                $_REQUEST['storefront_data']['storefront_id'] = $storefront->storefront_id;
                fn_update_storefront_currencies($_REQUEST['storefront_data']);
                fn_update_storefront_languages($_REQUEST['storefront_data']);

                fn_clear_cache('registry'); // clean up block cache to re-generate storefront urls
            }
        }

        $suffix = ".update?company_id=$_REQUEST[company_id]";
    }

    if ($mode == 'm_delete') {

        if (!empty($_REQUEST['company_ids'])) {
            foreach ($_REQUEST['company_ids'] as $company_id) {
                fn_delete_company($company_id);
            }
        }

        return array(CONTROLLER_STATUS_OK, 'companies.manage');
    }

    if (fn_allowed_for('MULTIVENDOR')) {
        if ($mode === 'merge') {
            if (Registry::get('runtime.company_id')) {
                return [CONTROLLER_STATUS_DENIED];
            }

            if (isset($_REQUEST['from_company_id']) && isset($_REQUEST['to_company_id']) && fn_chown_company($_REQUEST['from_company_id'], $_REQUEST['to_company_id'])) {
                fn_delete_company($_REQUEST['from_company_id']);
            }

            return array(CONTROLLER_STATUS_REDIRECT, 'companies.manage');
        }

        if ($mode == 'm_delete_payouts' && !Registry::get('runtime.company_id')) {
            if (!empty($_REQUEST['payout_ids'])) {
                VendorPayouts::instance()->delete($_REQUEST['payout_ids']);
            }

            $suffix = '.balance';
        }

        if ($mode == 'payouts_add') {
            if (!empty($_REQUEST['payment']['amount'])) {
                fn_companies_add_payout($_REQUEST['payment']);
            }

            $suffix = '.balance';
        }

        if ($mode == 'update_payout_comments' && !Registry::get('runtime.company_id')) {
            if (!empty($_REQUEST['payout_comments'])) {
                foreach ($_REQUEST['payout_comments'] as $payout_id => $comment) {
                    \Tygh\VendorPayouts::instance()->update(array('comments' => $comment), $payout_id);
                }
            }
        }

        if (
            $mode === 'payouts'
            && $action === 'update_status'
            && !Registry::get('runtime.company_id')
        ) {
            $payout_ids = isset($_REQUEST['id']) ? (array) $_REQUEST['id'] : [];
            $payout_ids = isset($_REQUEST['payout_ids']) ? (array) $_REQUEST['payout_ids'] : $payout_ids;
            $notify_vendor = !empty($_REQUEST['notify_vendor']) && YesNo::toBool($_REQUEST['notify_vendor']);

            foreach ($payout_ids as $payout_id) {
                fn_companies_update_payout_status(
                    $payout_id,
                    $_REQUEST['status'],
                    $notify_vendor
                );
            }

            if (defined('AJAX_REQUEST')) {
                $redirect_url = fn_url('companies.payouts.update_status');
                if (isset($_REQUEST['redirect_url'])) {
                    $redirect_url = $_REQUEST['redirect_url'];
                }
                Tygh::$app['ajax']->assign('force_redirection', $redirect_url);
                Tygh::$app['ajax']->assign('non_ajax_notifications', true);
                return [CONTROLLER_STATUS_NO_CONTENT];
            }

            return [CONTROLLER_STATUS_REDIRECT, $_REQUEST['redirect_url']];
        }

        if ($mode === 'm_activate' || $mode === 'm_disable' || $mode === 'm_update_statuses') {
            if ($mode === 'm_activate') {
                $status = VendorStatuses::ACTIVE;
                $reason = !empty($_REQUEST['action_reason_activate']) ? $_REQUEST['action_reason_activate'] : '';
                $msg = __('text_companies_activated');
            } elseif ($mode === 'm_disable') {
                $status = VendorStatuses::DISABLED;
                $reason = !empty($_REQUEST['action_reason_disable']) ? $_REQUEST['action_reason_disable'] : '';
                $msg = __('text_companies_disabled');
            } else {
                $status = empty($_REQUEST['status']) ? '' : $_REQUEST['status'];
                $reason = empty($_REQUEST['reason_change_to_' . $status]) ? '' : $_REQUEST['reason_change_to_' . $status];
                $msg = __('status_changed');
            }

            $company_ids = empty($_REQUEST['company_ids']) ? [] : (array) $_REQUEST['company_ids'];

            $notification = !empty($_REQUEST['action_notification']) && YesNo::toBool($_REQUEST['action_notification']);

            $result = [];
            foreach ($company_ids as $company_id) {
                $status_from = '';
                $res = fn_change_company_status($company_id, $status, $reason, $status_from, false, $notification);
                if ($res) {
                    $result[] = $company_id;
                }
            }

            if ($result) {
                fn_set_notification(NotificationSeverity::NOTICE, __('notice'), $msg);
            } else {
                fn_set_notification(NotificationSeverity::ERROR, __('error'), __('error_status_not_changed'), 'I');
            }

            if (defined('AJAX_REQUEST')) {
                $redirect_url = fn_url('companies.manage');
                if (isset($_REQUEST['redirect_url'])) {
                    $redirect_url = $_REQUEST['redirect_url'];
                }
                Tygh::$app['ajax']->assign('force_redirection', $redirect_url);
                Tygh::$app['ajax']->assign('non_ajax_notifications', true);
                return [CONTROLLER_STATUS_NO_CONTENT];
            }

            return [CONTROLLER_STATUS_REDIRECT, 'companies.manage'];
        }

        if ($mode === 'export_range') {
            if (!empty($_REQUEST['company_ids'])) {
                if (empty(Tygh::$app['session']['export_ranges'])) {
                    Tygh::$app['session']['export_ranges'] = array();
                }

                if (empty(Tygh::$app['session']['export_ranges']['vendors'])) {
                    Tygh::$app['session']['export_ranges']['vendors'] = array('pattern_id' => 'vendors');
                }

                Tygh::$app['session']['export_ranges']['vendors']['data'] = array('company_id' => $_REQUEST['company_ids']);

                unset($_REQUEST['redirect_url']);

                return array(CONTROLLER_STATUS_REDIRECT, 'exim.export?section=vendors&pattern_id=' . Tygh::$app['session']['export_ranges']['vendors']['pattern_id']);
            }
        }

        if ($mode == 'invite') {
            $result = VendorServicesProvider::getInvitationsSender()->send($_REQUEST);
            $result->showNotifications();
            $suffix = '.invitations';
        }

        if ($mode == 'm_delete_invitations' && !Registry::get('runtime.company_id')) {
            if (!empty($_REQUEST['invitation_keys'])) {
                VendorServicesProvider::getInvitationsRepository()->deleteByKey($_REQUEST['invitation_keys']);
            }

            $suffix = '.invitations';
        }

        if ($mode == 'delete_invitation' && !Registry::get('runtime.company_id')) {
            if (!empty($_REQUEST['invitation_key'])) {
                VendorServicesProvider::getInvitationsRepository()->deleteByKey($_REQUEST['invitation_key']);
            }

            $suffix = '.invitations';
        }
    }

    if ($mode == 'delete') {

        fn_delete_company($_REQUEST['company_id']);

        return array(CONTROLLER_STATUS_REDIRECT, 'companies.manage');
    }

    if ($mode == 'update_status') {

        $notification = !empty($_REQUEST['notify_user']) && $_REQUEST['notify_user'] == 'Y';

        if (defined('AJAX_REQUEST') && empty($_REQUEST['show_notifications'])) {
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
        }

        if (fn_change_company_status($_REQUEST['id'], $_REQUEST['status'], '', $status_from, false, $notification)) {
            fn_set_notification('N', __('notice'), __('status_changed'));
        } else {
            fn_set_notification('E', __('error'), __('error_status_not_changed'));
            Tygh::$app['ajax']->assign('return_status', $status_from);
        }
        if (!empty($_REQUEST['return_url'])) {
            return array(CONTROLLER_STATUS_REDIRECT, $_REQUEST['return_url']);
        }
        exit;
    }

    if ($mode == 'payout_delete' && !Registry::get('runtime.company_id')) {
        VendorPayouts::instance()->delete($_REQUEST['payout_id']);
    }

    if ($mode == 'switch_storefront_status') {
        if (!fn_allowed_for('ULTIMATE')) {
            return array(CONTROLLER_STATUS_DENIED);
        }

        $status = isset($_REQUEST['status']) ? $_REQUEST['status'] : null;
        $company_id = fn_get_runtime_company_id();

        if (!$company_id && isset($_REQUEST['company_id'])) {
            $company_id = (int) $_REQUEST['company_id'];
        }

        if (empty($status) || empty($company_id)) {
            fn_set_notification('E', __('error'), __('error_occured'));
        } else {
            $is_status_changed = false;

            if ($status === StorefrontStatuses::OPEN) {
                $is_status_changed = fn_ult_open_storefront($company_id);
            } elseif ($status === StorefrontStatuses::CLOSED) {
                $is_status_changed = fn_ult_close_storefront($company_id);
            }

            if ($is_status_changed) {
                fn_init_storefronts_stats();
            } else {
                fn_set_notification('E', __('error'), __('error_occured'));
            }

            if (defined('AJAX_REQUEST')) {
                Tygh::$app['ajax']->assign('result', $is_status_changed);
            }

            if (!empty($_REQUEST['return_url'])) {
                return array(CONTROLLER_STATUS_OK, urldecode($_REQUEST['return_url']));
            }
        }

        $suffix = '.manage';
    }

    if (
        $mode === 'm_switch_storefront_status'
        && !empty($_REQUEST['company_ids'])
        && !empty($_REQUEST['status'])
    ) {
        $status = YesNo::toId($_REQUEST['status']);

        foreach ((array) $_REQUEST['company_ids'] as $company_id) {
            if ($status === StorefrontStatuses::OPEN) {
                fn_ult_open_storefront($company_id);
            } elseif ($status === StorefrontStatuses::CLOSED) {
                fn_ult_close_storefront($company_id);
            }
        }

        fn_init_storefronts_stats();
    }

    return array(CONTROLLER_STATUS_OK, 'companies' . $suffix);
}

if ($mode == 'manage') {
    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];
    if (fn_allowed_for('MULTIVENDOR')) {
        fn_companies_set_navigation_sections('vendors');
    }

    $params = $_REQUEST;
    if (fn_allowed_for('ULTIMATE')) {
        $params['extend']['storefront'] = true;
    }
    list($companies, $search) = fn_get_companies($params, $auth, Registry::get('settings.Appearance.admin_elements_per_page'));

    $view->assign([
        'companies' => $companies,
        'search'    => $search,
        'countries' => fn_get_simple_countries(true, CART_LANGUAGE),
        'states'    => fn_get_all_states(),
    ]);

    if (fn_allowed_for('ULTIMATE')) {
        $view->assign('is_companies_limit_reached', Helpdesk::isStorefrontsLimitReached());
    }

} elseif ($mode === 'update' || $mode === 'add') {
    $company_id = !empty($_REQUEST['company_id']) ? $_REQUEST['company_id'] : 0;
    $company_data = $extra = array();

    if ($company_id) {
        if (fn_allowed_for('ULTIMATE')) {
            $extra['storefront'] = true;
        } else {
            $extra['use_i18n_fields'] = true;
        }

        $company_data = fn_get_company_data($company_id, DESCR_SL, $extra);
    }

    if ($mode == 'update' && empty($company_data)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    if (fn_allowed_for('MULTIVENDOR')) {
        if (!empty($company_id)) {
            $params = $_REQUEST;
            $params['vendor'] = $company_id;
            list($time_from, $time_to) = fn_create_periods(array('period' => DateTimeHelper::PERIOD_THIS_MONTH));

            $company_data['logos'] = fn_get_logos($company_id);
            $company_data['orders_count'] = db_get_field(
                'SELECT COUNT(*) FROM ?:orders WHERE company_id = ?i', $company_id
            );

            $product_companies = fn_get_companies_active_products_count([$company_id]);
            $company_data['products_count'] = $product_companies[$company_id];

            $params = [
                'amount_to' => 0,
                'tracking' => [
                    ProductTracking::TRACK
                ],
                'get_conditions' => true,
                'extend' => ['companies'],
            ];
            list($fields, $joins, $conditions) = fn_get_products($params);

            db_query(
                'SELECT SQL_CALC_FOUND_ROWS ' . implode(', ', $fields) . ' FROM ?:products AS products ?p'
                . ' WHERE 1 AND ?w ?p'
                . ' GROUP BY products.product_id',
                $joins,
                ['companies.company_id' => $company_id],
                $conditions
            );
            $company_data['out_of_stock'] = db_get_found_rows();

            $company_data['sales'] = db_get_field(
                'SELECT SUM(total) FROM ?:orders'
                . ' WHERE company_id = ?i AND (timestamp >= ?i AND timestamp <= ?i) AND status IN (?a)',
                $company_id, $time_from, $time_to, fn_get_settled_order_statuses()
            );

            $vendor_payouts = \Tygh\VendorPayouts::instance(array('vendor' => $company_id));
            list($company_data['income'], $company_data['balance_carried_forward']) = $vendor_payouts->getIncome($params);

            list($company_data['balance'], $company_data['balance_carried_forward']) = $vendor_payouts->getBalance($params);

            Tygh::$app['view']->assign('time_from', $time_from);
            Tygh::$app['view']->assign('time_to', $time_to);
        }

        if ($mode === 'add') {
            $logos = fn_get_logos();

            if (!empty($logos['vendor']['image']['image_path'])) {
                $company_data = [
                    'logos' => [
                        'theme' => [
                            'image' => [
                                'image_path' => $logos['vendor']['image']['image_path']
                            ]
                        ],
                        'mail'  => [
                            'image' => [
                                'image_path' => $logos['vendor']['image']['image_path']
                            ]
                        ],
                    ],
                ];
            }
        }

        Tygh::$app['view']->assign('logo_types', fn_get_logo_types(true));
    }

    $restored_company_data = fn_restore_post_data('company_data');
    if (!empty($restored_company_data) && $mode == 'add') {
        if (!empty($restored_company_data['shippings'])) {
            $restored_company_data['shippings'] = implode(',', $restored_company_data['shippings']);
        }
        $company_data = fn_array_merge($company_data, $restored_company_data);
    }

    if (fn_allowed_for('ULTIMATE')) {
        if ($mode === 'update' || $mode === 'add') {
            $theme = Registry::get('config.base_theme');
            $current_theme = null;
            $current_style = null;
            $storefront_id = null;
            $storefront = null;
            if ($company_id) {
                /** @var \Tygh\Storefront\Repository $repository */
                $repository = Tygh::$app['storefront.repository'];
                /** @var \Tygh\Storefront\Storefront $storefront */
                $storefront = $repository->findByCompanyId($company_id);
                $theme = $storefront->theme_name;
                $storefront_id = $storefront->storefront_id;

                $layout = Layout::instance(0, [], $storefront->storefront_id)->getDefault($storefront->theme_name);
                $current_theme = Themes::factory($storefront->theme_name)->getManifest()['title'];
                $current_style = empty($layout['style_id']) ? '' : Styles::factory($storefront->theme_name)->get($layout['style_id'])['name'];
            }

            $currency_ids = [];
            $language_ids = [];
            $languages = [];
            $currencies = [];
            $is_localization_picker_allowed = true;
            if ($storefront) {
                $currency_ids = $storefront->getAvailableCurrencyIds();
                $language_ids = $storefront->getAvailableLanguageIds();
                $languages = Languages::getAll();
                $currencies = fn_get_currencies_list();

                if (db_get_field('SELECT COUNT(1) FROM ?:storefronts') <= 1) {
                    $is_localization_picker_allowed = false;
                }
            }

            Tygh::$app['view']->assign([
                'storefront_id'                    => $storefront_id,
                'theme'                            => $theme,
                'current_theme'                    => $current_theme,
                'current_style'                    => $current_style,
                'all_currency_ids'                 => $currency_ids,
                'all_language_ids'                 => $language_ids,
                'all_currencies'                   => $currencies,
                'all_languages'                    => $languages,
                'is_localization_picker_allowed'   => $is_localization_picker_allowed
            ]);
        }

        $countries_list = fn_get_simple_countries();

        if (!empty($company_data['countries_list'])) {
            if (!is_array($company_data['countries_list'])) {
                $company_countries = explode(',', $company_data['countries_list']);
            } else {
                $company_countries = $company_data['countries_list'];
            }
            $_countries = array();

            foreach ($company_countries as $code) {
                if (isset($countries_list[$code])) {
                    $_countries[$code] = $countries_list[$code];
                    unset($countries_list[$code]);
                }
            }

            $company_data['countries_list'] = $_countries;
            unset($_countries, $company_countries);
        }

        Tygh::$app['view']->assign('countries_list', $countries_list);

        if ($mode == 'add') {
            $schema = fn_init_clone_schemas();
            Tygh::$app['view']->assign('clone_schema', $schema);
            Tygh::$app['view']->assign('is_companies_limit_reached', Helpdesk::isStorefrontsLimitReached());
        }

        // Get "Company" settings from the DB
        $settings_values = fn_restore_post_data('update');
        $section = Settings::instance()->getSectionByName('Company');
        $settings_data = Settings::instance()->getList($section['section_id'], 0, false, $company_id, CART_LANGUAGE);
        foreach ($settings_data['main'] as $field_id => &$field_data) {
            unset($field_data['update_for_all']);
            if (!empty($settings_values) && !empty($settings_values[$field_id])) {
                $field_data['value'] = $settings_values[$field_id];
            } elseif ($mode == 'add') {
                unset($field_data['value']);
            }
        }

        Tygh::$app['view']->assign('company_settings', $settings_data['main']);
        unset($settings_data, $section);
    }

    Tygh::$app['view']->assign('company_data', $company_data);
    Tygh::$app['view']->assign('countries', fn_get_simple_countries(true, CART_LANGUAGE));
    Tygh::$app['view']->assign('states', fn_get_all_states());

    $params = array(
        'get_custom'           => true,
        'get_profile_required' => true,
    );

    if (fn_allowed_for('MULTIVENDOR')) {
        $params = array(
            'profile_type' => ProfileTypes::CODE_SELLER,
            'skip_email_field' => false,
        );
    }

    $profile_fields = fn_get_profile_fields('A', array(), CART_LANGUAGE, $params);
    Tygh::$app['view']->assign('profile_fields', $profile_fields);

    $tabs['detailed'] = array(
        'title' => __('general'),
        'js' => true
    );
    $tabs['addons'] = array(
        'title' => __('addons'),
        'js' => true
    );

    if (fn_allowed_for('MULTIVENDOR')) {
        $tabs['description'] = array(
            'title' => __('description'),
            'js' => true
        );
        $tabs['logos'] = array(
            'title' => __('logos'),
            'js' => true
        );

    } elseif (fn_allowed_for('ULTIMATE')) {
        $tabs['regions'] = array(
            'title' => __('regions'),
            'js' => true
        );
    }

    if (!Registry::get('runtime.company_id')) {
        $shippings = db_get_hash_array(
            "SELECT a.shipping_id, a.status, b.shipping"
            . " FROM ?:shippings as a"
            . " LEFT JOIN ?:shipping_descriptions as b ON a.shipping_id = b.shipping_id AND b.lang_code = ?s"
            . " WHERE a.company_id = 0 AND a.status = 'A'"
            . " ORDER BY a.position",
            'shipping_id', DESCR_SL
        );
        Tygh::$app['view']->assign('shippings', $shippings);

        if (!fn_allowed_for('ULTIMATE')) {
            $tabs['shipping_methods'] = array(
                'title' => __('shipping_methods'),
                'js' => true
            );
        }
    }

    Registry::set('navigation.tabs', $tabs);

} elseif ($mode == 'picker') {
    list($companies, $search) = fn_get_companies($_REQUEST, $auth, Registry::get('settings.Appearance.admin_elements_per_page'));

    Tygh::$app['view']->assign('companies', $companies);
    Tygh::$app['view']->assign('search', $search);

    Tygh::$app['view']->assign('countries', fn_get_simple_countries(true, CART_LANGUAGE));
    Tygh::$app['view']->assign('states', fn_get_all_states());

    Tygh::$app['view']->display('pickers/companies/picker_contents.tpl');
    exit;
}

if (fn_allowed_for('MULTIVENDOR')) {
    if ($mode === 'merge') {
        if (Registry::get('runtime.company_id')) {
            return [CONTROLLER_STATUS_DENIED];
        }

        if (empty($_REQUEST['company_id'])) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        $company_id = $_REQUEST['company_id'];
        unset ($_REQUEST['company_id']);
        $company_data = !empty($company_id) ? fn_get_company_data($company_id) : array();

        if (empty($company_data)) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        $_REQUEST['exclude_company_id'] = $company_id;

        list($companies, $search) = fn_get_companies($_REQUEST, $auth, Registry::get('settings.Appearance.admin_elements_per_page'));

        Tygh::$app['view']->assign('company_id', $company_id);
        Tygh::$app['view']->assign('company_name', $company_data['company']);
        Tygh::$app['view']->assign('companies', $companies);
        Tygh::$app['view']->assign('search', $search);
        Tygh::$app['view']->assign('countries', fn_get_simple_countries(true, CART_LANGUAGE));
        Tygh::$app['view']->assign('states', fn_get_all_states());

    } elseif ($mode == 'balance') {

        $vendor_payouts = \Tygh\VendorPayouts::instance();

        $params = $_REQUEST;

        $is_withdrawals_section = isset($params['selected_section']) && $params['selected_section'] == 'withdrawals';

        $params = LastView::instance()->update('balance', $params);

        // totals make no sense, when search by payout type or approval status is performed
        $get_totals = empty($params['payout_type']) && empty($params['approval_status']);

        if ($is_withdrawals_section) {
            $payout_types = array(\Tygh\Enum\VendorPayoutTypes::WITHDRAWAL);
        } else {
            $payout_types = array_diff(
                \Tygh\Enum\VendorPayoutTypes::getAll(),
                array(\Tygh\Enum\VendorPayoutTypes::WITHDRAWAL)
            );
            Tygh::$app['view']->assign('payout_types', $payout_types);
        }

        if (empty($params['payout_type'])) {
            $params['payout_type'] = $payout_types;
        }

        list($payouts, $search) = $vendor_payouts->getList($params, Registry::get('settings.Appearance.admin_elements_per_page'));

        Tygh::$app['view']->assign('payouts', $payouts);
        Tygh::$app['view']->assign('search', $search);

        if ($get_totals) {
            $totals = array();

            if ($is_withdrawals_section) {
                if ($vendor_payouts->getVendor()) {
                    list($totals['balance'], $totals['balance_carried_forward']) = $vendor_payouts->getBalance($params);
                }
            } else {
                list($totals['income'], $totals['income_carried_forward']) = $vendor_payouts->getIncome($params);
            }

            Tygh::$app['view']->assign('totals', $totals);
        }

        if ($vendor_payouts->getVendor()) {
            list($balance, ) = $vendor_payouts->getBalance();
            Tygh::$app['view']->assign('current_balance', $balance);
        }

        Tygh::$app['view']->assign([
            'approval_statuses'   => VendorPayoutApprovalStatuses::getWithDescriptions(),
            'is_allow_add_payout' => fn_check_permissions('companies', 'payouts_add', 'admin', Http::POST),
        ]);

        Registry::set('navigation.tabs', array(
            'transactions' => array(
                'title' => __('vendor_payouts.transactions'),
                'href' => 'companies.balance',
            ),
            'withdrawals' => array(
                'title' => __('vendor_payouts.withdrawals'),
                'href' => 'companies.balance?selected_section=withdrawals',
            ),
        ));
    } elseif ($mode == 'invite') {
        if (isset($_REQUEST['is_ajax'])) {
            Tygh::$app['view']->assign('is_ajax', $_REQUEST['is_ajax']);
        }
        return [CONTROLLER_STATUS_OK];
    } elseif ($mode == 'invitations') {
        fn_companies_set_navigation_sections('invitations');

        list($invitations, $search) = VendorServicesProvider::getInvitationsRepository()->getListWithPagination(
            $_REQUEST,
            Registry::get('settings.Appearance.admin_elements_per_page')
        );
        Tygh::$app['view']->assign([
            'invitations' => $invitations,
            'search'      => $search,
        ]);
    }
}

if (fn_allowed_for('ULTIMATE')) {
    if ($mode == 'get_object_share') {
        $sharing_schema = fn_get_schema('sharing', 'schema');
        $view = Tygh::$app['view'];

        if (!empty($_REQUEST['object_id']) && !empty($_REQUEST['object'])) {
            $schema = $sharing_schema[$_REQUEST['object']];

            $view->assign('selected_companies', fn_ult_get_object_shared_companies($_REQUEST['object'], $_REQUEST['object_id']));
            $owner = db_get_row('SELECT * FROM ?:' . $schema['table']['name'] . ' WHERE ' . $schema['table']['key_field'] . ' = ?s', $_REQUEST['object_id']);
            $owner_id = isset($owner['company_id']) ? $owner['company_id'] : '';

            $view->assign('result_ids', $_REQUEST['result_ids']);
            $view->assign('object_id', $_REQUEST['object_id']);
            $view->assign('owner_id', $owner_id);
            $view->assign('object', $_REQUEST['object']);
            $view->assign('schema', $schema);

            if (!empty($schema['no_item_text'])) {
                $view->assign('no_item_text', __($schema['no_item_text']));
            }

            $view->display('views/companies/components/share_object.tpl');
        }

        exit;
    }
}

/**
 * Set links into sidebar menu on the vendors and invitations pages
 *
 * @param string $active_section Set active section of the page
 */
function fn_companies_set_navigation_sections($active_section)
{
    $navigation_sections = [
        'vendors' => [
            'title' => __('vendors'),
            'href'  => fn_url('companies.manage'),
        ],
        'invitations' => [
            'title' => __('pending_vendor_invitations'),
            'href'  => fn_url('companies.invitations')
        ]
    ];

    Registry::set('navigation.dynamic.sections', $navigation_sections);
    Registry::set('navigation.dynamic.active_section', $active_section);
}
