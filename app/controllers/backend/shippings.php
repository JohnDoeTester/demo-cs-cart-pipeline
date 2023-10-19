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

use Tygh\Enum\ObjectStatuses;
use Tygh\Http;
use Tygh\Registry;
use Tygh\Shippings\Shippings;
use Tygh\Tygh;
use Tygh\Enum\YesNo;
use Tygh\Enum\UsergroupStatuses;
use Tygh\Enum\UsergroupTypes;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$_REQUEST['shipping_id'] = empty($_REQUEST['shipping_id']) ? 0 : $_REQUEST['shipping_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $suffix = '';

    fn_trusted_vars (
        'shipping_data'
    );

    //
    // Update shipping method
    //
    if ($mode == 'update') {
        if ((!empty($_REQUEST['shipping_id']) && fn_check_company_id('shippings', 'shipping_id', $_REQUEST['shipping_id'])) || empty($_REQUEST['shipping_id'])) {
            fn_set_company_id($_REQUEST['shipping_data']);
            $_REQUEST['shipping_id'] = fn_update_shipping($_REQUEST['shipping_data'], $_REQUEST['shipping_id']);
        }

        $_extra = empty($_REQUEST['destination_id']) ? '' : '&destination_id=' . $_REQUEST['destination_id'];
        $suffix = '.update?shipping_id=' . $_REQUEST['shipping_id'] . $_extra;
    }

    // Delete selected rates
    if ($mode == 'delete_rate_values') {
        if (fn_check_company_id('shippings', 'shipping_id', $_REQUEST['shipping_id'])) {
            foreach ($_REQUEST['delete_rate_data'] as $destination_id => $rates) {
                fn_delete_rate_values($rates, $_REQUEST['shipping_id'], $destination_id);
            }
        }

        $suffix = '.update?shipping_id=' . $_REQUEST['shipping_id'];
    }

    if ($mode == 'apply_to_vendors' && fn_allowed_for('MULTIVENDOR')) {
        if (!Registry::get('runtime.company_id') && !empty($_REQUEST['shipping_id'])) {
            $companies = fn_apply_shipping_to_vendors($_REQUEST['shipping_id']);
            fn_set_notification('N', __('notice'), __('shipping_applied_to_vendors', array('[vendors]' => $companies)));
            $suffix = '.update?shipping_id=' . $_REQUEST['shipping_id'];
        }
    }

    //
    // Update shipping methods
    //
    if ($mode == 'm_update') {

        if (!empty($_REQUEST['shipping_data']) && is_array($_REQUEST['shipping_data'])) {
            foreach ($_REQUEST['shipping_data'] as $k => $v) {
                if (empty($v)) {
                    continue;
                }

                if (fn_check_company_id('shippings', 'shipping_id', $k)) {
                    fn_update_shipping($v, $k);
                }
            }
        }

        $suffix .= '.manage';
    }

    if ($mode == 'test') {

        $shipping_data = $_REQUEST['shipping_data'];

        if (!empty($_REQUEST['shipping_id'])) {
            // Set package information (weight is only needed)
            $weight = isset($shipping_data['test_weight'])
                ? sprintf("%.3f", floatval($shipping_data['test_weight']))
                : '0.001';

            if (isset($_REQUEST['sender'])) {
                $sender = $_REQUEST['sender'];
            } else {
                $settings = Registry::get('settings.Company');
                $sender = [
                    'address' => $settings['company_address'],
                    'city'    => $settings['company_city'],
                    'country' => $settings['company_country'],
                    'state'   => $settings['company_state'],
                    'zipcode' => $settings['company_zipcode'],
                ];
            }

            $package_info = [
                'W' => $weight,
                'C' => 100,
                'I' => 1,
                'packages' => [
                    [
                        'shipping_params' => [
                            'box_length' => 10,
                            'box_width'  => 10,
                            'box_height' => 10,
                        ],
                        'products' => [],
                        'amount' => 1,
                        'weight' => $weight,
                        'cost' => 100
                    ],
                ],
                'origination' => $_REQUEST['sender'],
            ];
            $package_info['origination']['company_id'] = fn_get_runtime_company_id();

            $recipient = isset($_REQUEST['recipient'])
                ? $_REQUEST['recipient']
                : fn_get_customer_location(['user_id' => 0], []);

            // Set default location
            $location = $package_info['location'] = $recipient;
            $service_params = !empty($shipping_data['service_params']) ? $shipping_data['service_params'] : [];
            if (isset($_REQUEST['calculate_data']['company_id'])) {
                $service_params['calculate_data']['company_id'] = $_REQUEST['calculate_data']['company_id'];
            }
            $shipping_data['service_id'] = isset($shipping_data['service_id']) ? $shipping_data['service_id'] : 0;

            $shipping = Shippings::getShippingForTest($_REQUEST['shipping_id'], $shipping_data['service_id'], $service_params, $package_info);
            $rates = Shippings::calculateRates([$shipping]);

            if ($rates[0]['price'] === false) {
                $rates[0]['error'] = isset($rates[0]['error']) ? $rates[0]['error'] : __('price_rate_not_set');
                unset($rates[0]['price']);
            }
            if ($rates[0]['service_delivery_time'] === false) {
                unset($rates[0]['service_delivery_time']);
            }

            /** @var \Tygh\SmartyEngine\Core $view */
            $view = Tygh::$app['view'];
            $view->assign([
                'rates'     => $rates[0],
                'sender'    => $sender,
                'recipient' => $recipient,
                'shipping'  => $shipping,
            ]);
            $view->display('views/shippings/calculate_cost.tpl');
        }
        return [CONTROLLER_STATUS_NO_CONTENT];
    }

    //
    // Delete shipping methods
    //
    //TODO make security check for company_id
    if ($mode == 'm_delete') {

        if (!empty($_REQUEST['shipping_ids'])) {
            foreach ($_REQUEST['shipping_ids'] as $id) {
                if (fn_check_company_id('shippings', 'shipping_id', $id)) {
                    fn_delete_shipping($id);
                }
            }
        }

        $suffix = '.manage';
    }

    if ($mode === 'm_update_statuses') {
        $status_to = empty($_REQUEST['status']) ? '' : (string) $_REQUEST['status'];
        $shipping_ids = !empty($_REQUEST['shipping_ids']) && is_array($_REQUEST['shipping_ids']) ? $_REQUEST['shipping_ids'] : [];

        if (!empty($_REQUEST['status']) && !empty($shipping_ids)) {
            foreach ($_REQUEST['shipping_ids'] as $shipping_id) {
                if (!fn_check_company_id('shippings', 'shipping_id', $shipping_id)) {
                    continue;
                }
                fn_tools_update_status([
                    'table'             => 'shippings',
                    'status'            => $status_to,
                    'id_name'           => 'shipping_id',
                    'id'                => $shipping_id,
                    'show_error_notice' => false
                ]);
            }
        }
        if (defined('AJAX_REQUEST')) {
            $redirect_url = fn_url('shippings.manage');
            if (isset($_REQUEST['redirect_url'])) {
                $redirect_url = $_REQUEST['redirect_url'];
            }
            Tygh::$app['ajax']->assign('force_redirection', $redirect_url);
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            return [CONTROLLER_STATUS_NO_CONTENT];
        }
    }

    // Delete shipping method
    if ($mode == 'delete') {

        if (!empty($_REQUEST['shipping_id']) && fn_check_company_id('shippings', 'shipping_id', $_REQUEST['shipping_id'])) {
            fn_delete_shipping($_REQUEST['shipping_id']);
        }

        $suffix = '.manage';
    }

    return array(CONTROLLER_STATUS_OK, 'shippings' . $suffix);
}

if ($mode == 'configure') {

    $shipping_id = !empty($_REQUEST['shipping_id']) ? $_REQUEST['shipping_id'] : 0;

    if (Registry::get('runtime.company_id')) {
        $shipping = db_get_row("SELECT company_id, service_params FROM ?:shippings WHERE shipping_id = ?i", $shipping_id);
        if ($shipping['company_id'] != Registry::get('runtime.company_id')) {
            exit;
        }
    }

    $module = !empty($_REQUEST['module']) ? basename($_REQUEST['module']) : '';
    if (!empty($module)) {
        $view = Tygh::$app['view'];
        $service_template = '';

        $tpl = 'views/shippings/components/services/' . $module . '.tpl';
        if ($view->templateExists($tpl)) {
            $service_template = $tpl;
        } else {
            $addons = Registry::get('addons');
            foreach ($addons as $addon => $data) {
                $tpl = 'addons/' . $addon .'/views/shippings/components/services/' . $module . '.tpl';
                if ($view->templateExists($tpl)) {
                    $service_template = $tpl;
                    break;
                }
            }
        }

        if (!empty($service_template)) {

            if (isset($shipping['service_params'])) {
                $shipping['service_params'] = unserialize($shipping['service_params']);
                if (empty($shipping['service_params'])) {
                    $shipping['service_params'] = array();
                }
            } else {
                $shipping['service_params'] = fn_get_shipping_params($shipping_id);
            }
        }

        Tygh::$app['view']->assign('shipping', $shipping);
        Tygh::$app['view']->assign('service_template', $service_template);

        $code = !empty($_REQUEST['code']) ? $_REQUEST['code'] : '';
        Tygh::$app['view']->assign('code', $code);
    }
// Add new shipping method
} elseif ($mode == 'add') {

    $rate_data = array(
        'rate_value' => array(
            'C' => array(),
            'W' => array(),
            'I' => array(),
        )
    );

    $services = fn_get_shipping_services();
    Tygh::$app['view']->assign('services', $services);
    Tygh::$app['view']->assign('carriers', fn_get_carriers_from_services($services));
    Tygh::$app['view']->assign('rate_data', $rate_data);
    Tygh::$app['view']->assign('taxes', fn_get_taxes());
    Tygh::$app['view']->assign('usergroups', fn_get_usergroups(array('type' => 'C', 'status' => array('A', 'H')), DESCR_SL));

// Collect shipping methods data
} elseif ($mode == 'update') {
    $shipping = fn_get_shipping_info($_REQUEST['shipping_id'], DESCR_SL);

    if (empty($shipping)) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $company_id = Registry::get('runtime.company_id');
    if ($company_id && !fn_allowed_for('ULTIMATE')) {
        $company_data = Registry::get('runtime.company_data');
        $company_shippings = explode(',', $company_data['shippings']);

        $shipping_of_another_company = $shipping['company_id'] != $company_id;
        $shipping_not_assigned_to_company = !in_array($_REQUEST['shipping_id'], $company_shippings);
        $shipping_assigned_to_company = $shipping['company_id'] != 0;
        if ($shipping_of_another_company
            && ($shipping_not_assigned_to_company || $shipping_assigned_to_company)
        ) {
            return [CONTROLLER_STATUS_DENIED];
        }
    }

    $rates_defined = db_get_hash_array('SELECT destination_id, IF(rate_value = ?s, 0, 1) as defined, base_rate FROM ?:shipping_rates WHERE shipping_id = ?i', '', 'destination_id', $_REQUEST['shipping_id']);
    foreach ($shipping['rates'] as $rate_key => $rate) {
        if (!empty($rates_defined[$rate['destination_id']]['defined']) || !empty($rates_defined[$rate['destination_id']]['base_rate'])) {
            $shipping['rates'][$rate_key]['rate_defined'] = true;
        }
    }


    $tabs = [
        'general'          => [
            'title' => __('general'),
            'js'    => true,
        ],
        'configure'        => [
            'title' => __('configure'),
            'ajax'  => true,
        ],
        'shipping_charges' => [
            'title' => __('shipping_time_and_charges'),
            'js'    => true,
        ],
        'rate_calculation' => [
            'title' => __('test_rate_calculation'),
            'js'    => true,
            'ajax'  => true,
        ],
        'additional_settings' => [
            'title' => __('additional_settings'),
            'js'    => true,
        ]
    ];

    $services = fn_get_shipping_services();
    if (!empty($shipping['rate_calculation']) && $shipping['rate_calculation'] == 'R' && !empty($services[$shipping['service_id']]['module'])) {
        $tabs['configure']['href'] = 'shippings.configure?shipping_id=' . $shipping['shipping_id'] . '&module=' . $services[$shipping['service_id']]['module'] . '&code=' . urlencode($services[$shipping['service_id']]['code']);
        $tabs['configure']['hidden'] = YesNo::NO;
    } else {
        $tabs['configure']['hidden'] = YesNo::YES;
    }

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    $add_all = isset($_REQUEST['add_all_destinations']);
    $destinations = array_filter($shipping['rates'], static function ($rate) use ($add_all) {
        return $add_all && $rate['status'] === ObjectStatuses::ACTIVE || isset($rate['rates_defined']);
    });
    $view->assign('ids', array_keys($destinations));

    if (Registry::get('runtime.company_id') && Registry::get('runtime.company_id') != $shipping['company_id']) {
        unset($tabs['configure']);
        $view->assign('hide_for_vendor', true);
    }

    if (fn_allowed_for('MULTIVENDOR:ULTIMATE')) {
        $tabs['storefronts'] = [
            'title' => __('storefronts'),
            'js' => true,
        ];
    }

    if (fn_allowed_for('ULTIMATE')) {
        /** @var \Tygh\Storefront\Repository $repository */
        $repository = Tygh::$app['storefront.repository'];
        list($is_sharing_enabled, $is_shared) = $repository->getSharingDetails(['shipping_ids' => $shipping['shipping_id']]);
        if ($is_sharing_enabled) {
            $tabs['storefronts'] = [
                'title' => __('storefronts'),
                'js'    => true,
            ];
        }
        $view->assign([
            'is_sharing_enabled' => $is_sharing_enabled,
            'is_shared'          => $is_shared,
        ]);
    }

    Registry::set('navigation.tabs', $tabs);

    $recipient = isset($_REQUEST['recipient'])
        ? $_REQUEST['recipient']
        : fn_get_customer_location(['user_id' => 0], []);

    if (isset($_REQUEST['sender'])) {
        $sender = $_REQUEST['sender'];
    } else {
        $settings = Registry::get('settings.Company');
        $sender = [
            'address' => $settings['company_address'],
            'city'    => $settings['company_city'],
            'country' => $settings['company_country'],
            'state'   => $settings['company_state'],
            'zipcode' => $settings['company_zipcode'],
        ];
    }

    $view->assign([
        'shipping'                            => $shipping,
        'services'                            => $services,
        'carriers'                            => fn_get_carriers_from_services($services),
        'taxes'                               => fn_get_taxes(),
        'usergroups'                          => fn_get_usergroups(['type' => UsergroupTypes::TYPE_CUSTOMER, 'status' => [UsergroupStatuses::ACTIVE, UsergroupStatuses::HIDDEN]], DESCR_SL),
        'countries'                           => fn_get_simple_countries(true),
        'states'                              => fn_get_all_states(true),
        'recipient'                           => $recipient,
        'sender'                              => $sender,
        'is_allow_apply_shippings_to_vendors' => fn_check_permissions('shippings', 'apply_to_vendors', 'admin', Http::POST),
        'allow_save' => fn_allow_save_object($shipping, 'shippings') && fn_check_permissions('shippings', 'update', 'admin', Http::POST),
    ]);
// Show all shipping methods
} elseif ($mode == 'manage') {
    $company_id = fn_get_runtime_company_id() ?: null;

    if (
        fn_allowed_for('MULTIVENDOR:ULTIMATE')
        && !empty($auth['storefront_id'])
    ) {
        $storefront_id = $auth['storefront_id'];
    } else {
        $storefront_id = isset($_REQUEST['storefront_id']) ? $_REQUEST['storefront_id'] : null;
    }

    Tygh::$app['view']->assign('shippings', fn_get_available_shippings($company_id, false, $storefront_id));

    Tygh::$app['view']->assign('usergroups', fn_get_usergroups(array('type' => 'C', 'status' => array('A', 'H')), DESCR_SL));

    if (fn_allowed_for('MULTIVENDOR')) {
        Tygh::$app['view']->assign('selected_storefront_id', empty($_REQUEST['storefront_id']) ? 0 : (int) $_REQUEST['storefront_id']);
    }
}

if (in_array($mode, ['add', 'update', 'manage'])) {
    $dynamic_sections = Registry::ifGet('navigation.dynamic.sections', []);
    $dynamic_sections['shippings'] = [
        'title' => __('shipping_methods'),
        'href'  => 'shippings.manage',
    ];
    $dynamic_sections['destinations'] = [
        'title' => __('rate_areas'),
        'href'  => 'destinations.manage',
    ];

    Registry::set('navigation.dynamic.active_section', 'shippings');
    Registry::set('navigation.dynamic.sections', $dynamic_sections);
}

function fn_delete_rate_values($delete_rate_data, $shipping_id, $destination_id)
{
    $rate_values = db_get_field("SELECT rate_value FROM ?:shipping_rates WHERE shipping_id = ?i AND destination_id = ?i", $shipping_id, $destination_id);

    if (!empty($rate_values)) {
        $rate_values = unserialize($rate_values);
    }

    foreach ((array) $rate_values as $rate_type => $rd) {
        foreach ((array) $rd as $amount => $data) {
            if (isset($delete_rate_data[$rate_type][$amount]) && $delete_rate_data[$rate_type][$amount] == 'Y') {
                unset($rate_values[$rate_type][$amount]);
            }
        }
    }

    if (is_array($rate_values)) {
        foreach ($rate_values as $k => $v) {
            if ((count($v)==1) && (floatval($v[0]['value'])==0)) {
                unset($rate_values[$k]);
                continue;
            }
        }
    }

    if (fn_is_empty($rate_values)) {
        db_query("DELETE FROM ?:shipping_rates WHERE shipping_id = ?i AND destination_id = ?i", $shipping_id, $destination_id);
    } else {
        db_query("UPDATE ?:shipping_rates SET ?u WHERE shipping_id = ?i AND destination_id = ?i", array('rate_value' => serialize($rate_values)), $shipping_id, $destination_id);
    }
}

function fn_get_shipping_services($lang_code = DESCR_SL)
{
    return db_get_hash_array(
        "SELECT ?:shipping_services.service_id, ?:shipping_services.code, ?:shipping_services.module, ?:shipping_service_descriptions.description " .
        "FROM ?:shipping_services " .
        "LEFT JOIN ?:shipping_service_descriptions ON " .
            "?:shipping_service_descriptions.service_id = ?:shipping_services.service_id AND ?:shipping_service_descriptions.lang_code = ?s " .
        "ORDER BY ?:shipping_service_descriptions.description, ?:shipping_services.module",
    'service_id', $lang_code);
}

function fn_get_carriers_from_services($services)
{
    $carriers = array();
    foreach ($services as $service) {
        if (!isset($carriers[$service['module']])) {
            $carrier = Shippings::getCarrierInfo($service['module']);

            if ($carrier) {
                $carriers[$service['module']] = $carrier['name'];
            }
        }
    }

    return $carriers;
}
