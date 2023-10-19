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

use Tygh\Enum\ProfileFieldLocations;
use Tygh\Enum\YesNo;
use Tygh\Providers\EventDispatcherProvider;
use Tygh\Registry;
use Tygh\Enum\ProfileTypes;
use Tygh\Enum\VendorStatuses;
use Tygh\Providers\VendorServicesProvider;
use Tygh\Enum\UserTypes;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\SiteArea;
use Tygh\Shippings\Shippings;

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'apply_for_vendor') {
        if (Registry::get('settings.Vendors.apply_for_vendor') !== YesNo::YES) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        if (
            !empty($auth['user_type'])
            && (UserTypes::isAdmin($auth['user_type']) || UserTypes::isVendor($auth['user_type']))
        ) {
            $sign_out_link = fn_url('auth.logout?redirect_url=companies.apply_for_vendor');
            fn_set_notification(
                NotificationSeverity::ERROR,
                __('error'),
                __('error_admin_registers_as_vendor', ['[sign_out_link]' => $sign_out_link])
            );
            return [CONTROLLER_STATUS_REDIRECT, 'companies.apply_for_vendor'];
        }

        $data = $_REQUEST['company_data'];

        $data['timestamp'] = TIME;
        $data['status'] = VendorStatuses::NEW_ACCOUNT;
        $data['request_user_id'] = !empty($auth['user_id']) ? $auth['user_id'] : 0;

        /** @var \Tygh\Storefront\Storefront $storefront */
        $storefront = Tygh::$app['storefront'];
        $data['registered_from_storefront_id'] = $storefront->storefront_id;

        $shippings = Shippings::getShippingIdsAvailableForNewVendors(true);
        if (!empty($shippings)) {
            $data['shippings'] = $shippings;
        } else {
            $data['shippings']  = '';
        }

        $fields = isset($_REQUEST['company_data']['fields']) ? $_REQUEST['company_data']['fields'] : [];
        $company_data = fn_mve_extract_company_data_from_profile($fields);

        $account_data = [
            'company_fields'  => $fields,
            'admin_firstname' => isset($_REQUEST['company_data']['admin_firstname'])
                ? $_REQUEST['company_data']['admin_firstname']
                : $company_data['admin_firstname'],
            'admin_lastname'  => isset($_REQUEST['company_data']['admin_lastname'])
                ? $_REQUEST['company_data']['admin_lastname']
                : $company_data['admin_lastname'],
        ];

        $account_data['fields'] = fn_mve_profiles_match_company_and_user_fields($account_data['company_fields']);
        $data['request_account_data'] = serialize($account_data);

        if (empty($data['request_user_id'])) {
            $login_condition = !empty($data['request_account_name'])
                ? db_quote(' OR user_login = ?s', $data['request_account_name'])
                : '';
            $user_account_exists = db_get_field('SELECT user_id FROM ?:users WHERE email = ?s ?p', $data['email'], $login_condition);
            $company_exist = db_get_field('SELECT company FROM ?:companies WHERE company = ?s', $data['company']);

            if ($user_account_exists || $company_exist) {
                fn_save_post_data('user_data', 'company_data');

                if ($user_account_exists) {
                    fn_set_notification(NotificationSeverity::ERROR, __('error'), __('error_user_exists'));
                }
                if ($company_exist) {
                    fn_set_notification(NotificationSeverity::ERROR, __('error'), __('error_vendor_name_exists'));
                }

                return [CONTROLLER_STATUS_REDIRECT, 'companies.apply_for_vendor'];
            }
        }

        $company_id = fn_update_company($data);

        if (!$company_id) {
            fn_save_post_data('user_data', 'company_data');
            fn_set_notification('E', __('error'), __('text_error_adding_request'));

            return [CONTROLLER_STATUS_REDIRECT, 'companies.apply_for_vendor'];
        }

        if (!YesNo::toBool(Registry::ifGet('settings.Vendors.allow_approve_vendors_in_two_steps', YesNo::NO))) {
            fn_change_company_status($company_id, VendorStatuses::ACTIVE);
        }

        $data = array_merge($data, fn_get_company_data($company_id));
        if (empty($data['admin_firstname']) && empty($data['admin_lastname'])) {
            if (!empty($account_data['admin_firstname'])) {
                $data['admin_firstname'] = $account_data['admin_firstname'];
            }
            if (!empty($account_data['admin_lastname'])) {
                $data['admin_lastname'] = $account_data['admin_lastname'];
            }
        }

        $event_dispatcher = EventDispatcherProvider::getEventDispatcher();

        $notification_data = [
            'company_id'         => $company_id,
            'company'            => $data,
            'company_update_url' => fn_url('companies.update?company_id=' . $company_id, SiteArea::ADMIN_PANEL, 'http'),
            'marketplace_url'    => fn_url('', SiteArea::STOREFRONT),
        ];

        if ($data['status'] !== VendorStatuses::ACTIVE) {
            $event_dispatcher->dispatch('vendors_require_approval', []);
            $event_dispatcher->dispatch('apply_for_vendor_notification', $notification_data);
        } else {
            $event_dispatcher->dispatch('new_vendor_notification', $notification_data);
        }

        $msg = Tygh::$app['view']->fetch('views/companies/components/apply_for_vendor.tpl');
        fn_set_notification(NotificationSeverity::INFO, __('thank_you'), $msg);

        $return_url = !empty(Tygh::$app['session']['apply_for_vendor']['return_url'])
            ? Tygh::$app['session']['apply_for_vendor']['return_url']
            : fn_url('');
        unset(Tygh::$app['session']['apply_for_vendor']['return_url']);

        return [CONTROLLER_STATUS_REDIRECT, $return_url];
    }
}

if (fn_allowed_for('ULTIMATE')) {
    if ($mode == 'entry_page') {
        $countries = array();

        // FIXME: #STOREFRONTS: Must be redone to the Storefronts functionality
        $companies_countries = db_get_array('SELECT storefront, countries_list FROM ?:companies');
        foreach ($companies_countries as $data) {
            if (empty($data['countries_list'])) {
                continue;
            }
            $_countries = explode(',', $data['countries_list']);
            foreach ($_countries as $code) {
                $countries[$code] = strpos($data['storefront'], 'http://') === false ? 'http://' . $data['storefront'] : $data['storefront'];
            }
        }

        $country_descriptions = fn_get_countries_name(array_keys($countries));

        Tygh::$app['session']['entry_page'] = true;

        Tygh::$app['view']->assign('countries', $countries);
        Tygh::$app['view']->assign('country_descriptions', $country_descriptions);
        Tygh::$app['view']->display('views/companies/components/entry_page.tpl');

        exit;
    }
}

if ($mode === 'view') {
    if (!isset($_REQUEST['company_id'])) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $company_id = (int) $_REQUEST['company_id'];
    $company_params = [];
    if (fn_allowed_for('MULTIVENDOR')) {
        $company_params['use_i18n_fields'] = true;
    }

    $company_data = !empty($company_id) ? fn_get_company_data($company_id, DESCR_SL, $company_params) : [];

    /** @var \Tygh\Storefront\Storefront $storefront */
    $storefront = Tygh::$app['storefront'];
    if ($storefront->getCompanyIds() && !in_array($company_id, $storefront->getCompanyIds())) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    if (
        empty($company_data)
        || empty($company_data['status'])
        || !in_array($company_data['status'], fn_get_available_company_statuses(), true)
    ) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    fn_add_breadcrumb(__('all_vendors'), 'companies.catalog');
    fn_add_breadcrumb($company_data['company']);

    $company_products = fn_get_companies_active_products_count([$company_id]);
    $company_data['total_products'] = $company_products[$company_id];

    $company_data['logos'] = fn_get_logos($company_data['company_id']);

    $params = array(
        'company_id' => $company_id,
    );

    $company_data = fn_filter_company_data_by_profile_fields($company_data);
    $profile_fields = fn_get_profile_fields(
        ProfileFieldLocations::VENDOR_FIELDS,
        [],
        CART_LANGUAGE,
        [
            'profile_type'     => ProfileTypes::CODE_SELLER,
            'skip_email_field' => false,
            'storefront_show'  => true,
            'exclude_names'    => 'company_description'
        ]
    );

    if (!empty($company_data['company_description'])) {
        Registry::set(
            'navigation.tabs',
            [
                'description' => [
                    'title' => __('description'),
                    'js'    => true,
                ]
            ]
        );
    }

    Tygh::$app['view']->assign('company_data', $company_data);
    Tygh::$app['view']->assign('profile_fields', $profile_fields);
} elseif ($mode == 'catalog') {

    fn_add_breadcrumb(__('all_vendors'));

    $params = $_REQUEST;
    $params['status'] = 'A';
    $params['get_description'] = 'Y';

    /** @var \Tygh\Storefront\Storefront $storefront */
    $storefront = Tygh::$app['storefront'];
    if ($storefront->getCompanyIds()) {
        $params['company_id'] = $storefront->getCompanyIds();
    }

    if (fn_allowed_for('MULTIVENDOR')) {
        $params['use_i18n_fields'] = true;
    }

    $vendors_per_page = Registry::get('settings.Vendors.vendors_per_page');
    list($companies, $search) = fn_get_companies($params, $auth, $vendors_per_page);

    foreach ($companies as &$company) {
        $company['logos'] = fn_get_logos($company['company_id']);
        $company = fn_filter_company_data_by_profile_fields($company);
    }

    Tygh::$app['view']->assign('companies', $companies);
    Tygh::$app['view']->assign('search', $search);
} elseif ($mode == 'apply_for_vendor') {

    if (Registry::get('settings.Vendors.apply_for_vendor') != 'Y') {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    $restored_company_data = fn_restore_post_data('company_data');
    if (!empty($_REQUEST['invitation_key']) && empty($restored_company_data['email'])) {
        Tygh::$app['view']->assign('invitation_key', $_REQUEST['invitation_key']);

        $invite = VendorServicesProvider::getInvitationsRepository()->findInvitationByKey($_REQUEST['invitation_key']);
        if (!empty($invite['email'])) {
            $restored_company_data['email'] = $invite['email'];
        }
    }

    if ($restored_company_data) {
        Tygh::$app['view']->assign('company_data', $restored_company_data);
    }

    $restored_user_data = fn_restore_post_data('user_data');
    if ($restored_user_data) {
        Tygh::$app['view']->assign('user_data', $restored_user_data);
    }

    $params = array(
        'profile_type'     => ProfileTypes::CODE_SELLER,
        'skip_email_field' => false,
    );
    $profile_fields = fn_get_profile_fields('A', array(), CART_LANGUAGE, $params);

    Tygh::$app['view']->assign('profile_fields', $profile_fields);
    Tygh::$app['view']->assign('countries', fn_get_simple_countries(true, CART_LANGUAGE));
    Tygh::$app['view']->assign('states', fn_get_all_states());

    fn_add_breadcrumb(__('apply_for_vendor_account'));

    Tygh::$app['session']['apply_for_vendor']['return_url'] = !empty($_REQUEST['return_previous_url']) ? $_REQUEST['return_previous_url'] : fn_url('');

} elseif ($mode == 'products') {
    $company_data = !empty($_REQUEST['company_id']) ? fn_get_company_data($_REQUEST['company_id'], CART_LANGUAGE, ['use_i18n_fields' => true]) : [];

    if (
        empty($company_data)
        || !in_array($company_data['status'], fn_get_available_company_statuses(), true)
    ) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $company_id = $company_data['company_id'];

    /** @var \Tygh\Storefront\Storefront $storefront */
    $storefront = Tygh::$app['storefront'];
    if ($storefront->getCompanyIds() && !in_array($company_id, $storefront->getCompanyIds())) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    fn_add_breadcrumb(__('all_vendors'), 'companies.catalog');

    $params = $_REQUEST;

    $params['company_id'] = $company_id;
    $params['extend'] = array('description');

    if (!empty($_REQUEST['category_id'])) {
        fn_add_breadcrumb($company_data['company'], 'companies.products?company_id=' . $company_id);

        $category_id = $_REQUEST['category_id'];
        // Get full data for current category
        $category_data = fn_get_category_data($category_id);

        if (!empty($category_data)) {
            $params['cid'] = $category_id;
            if (Registry::get('settings.General.show_products_from_subcategories') == 'Y') {
                $params['subcats'] = 'Y';
            }

            // [Breadcrumbs]
            $parent_ids = explode('/', $category_data['id_path']);
            array_pop($parent_ids);

            if (!empty($parent_ids)) {
                $cats = fn_get_category_name($parent_ids);
                foreach ($parent_ids as $c_id) {
                    fn_add_breadcrumb($cats[$c_id], "companies.products?category_id=$c_id&company_id=$company_id");
                }
            }
            fn_add_breadcrumb($category_data['category']);
        } elseif ($category_data === false) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        // Get subcategories list for current category
        Tygh::$app['view']->assign('subcategories', fn_get_subcategories(
            $category_id, array('company_ids' => $company_data['company_id'])
        ));
        Tygh::$app['view']->assign('category_data', $category_data);
        Tygh::$app['view']->assign('reset_url', fn_url('companies.products?category_id=' . $category_id . '&company_id=' . $company_id));

    } else {
        if (!empty($_REQUEST['q'])) {
            fn_add_breadcrumb($company_data['company'], 'companies.products?company_id=' . $company_id);
            fn_add_breadcrumb(__('search'));
        } else {
            fn_add_breadcrumb($company_data['company']);
        }
        Tygh::$app['view']->assign('reset_url', fn_url('companies.products?company_id=' . $company_id));
    }

    if ($items_per_page = fn_change_session_param(Tygh::$app['session']['companies_params'], $_REQUEST, 'items_per_page')) {
        $params['items_per_page'] = $items_per_page;
    }
    if ($sort_by = fn_change_session_param(Tygh::$app['session']['companies_params'], $_REQUEST, 'sort_by')) {
        $params['sort_by'] = $sort_by;
    }
    if ($sort_order = fn_change_session_param(Tygh::$app['session']['companies_params'], $_REQUEST, 'sort_order')) {
        $params['sort_order'] = $sort_order;
    }

    if (isset($params['order_ids'])) {
        $order_ids = is_array($params['order_ids']) ? $params['order_ids'] : explode(',', $params['order_ids']);
        foreach ($order_ids as $order_id) {
            /** @psalm-suppress UndefinedGlobalVariable */
            if (!fn_is_order_allowed($order_id, $auth)) {
                return [CONTROLLER_STATUS_NO_PAGE];
            }
        }
    }

    list($products, $search) = fn_get_products($params, Registry::get('settings.Appearance.products_per_page'));

    fn_filters_handle_search_result($params, $products, $search);

    fn_gather_additional_products_data($products, array('get_icon' => true, 'get_detailed' => true, 'get_additional' => true, 'get_options'=> true));

    if (!empty($products)) {
        Tygh::$app['session']['continue_url'] = Registry::get('config.current_url');
    }

    $selected_layout = fn_get_products_layout($params);

    Tygh::$app['view']->assign('products', $products);
    Tygh::$app['view']->assign('search', $search);
    Tygh::$app['view']->assign('selected_layout', $selected_layout);
    Tygh::$app['view']->assign('company_id', $company_data['company_id']);

    Registry::set('runtime.vendor_id', $company_id);
}
