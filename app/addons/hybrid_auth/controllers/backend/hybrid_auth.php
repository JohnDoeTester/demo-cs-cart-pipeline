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

use Tygh\Enum\NotificationSeverity;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'update_provider' && isset($_REQUEST['provider_data'])) {
        fn_hybrid_auth_update_provider($_REQUEST['provider_data']);
    }

    if ($mode === 'delete_provider') {
        if (!empty($_REQUEST['provider_id'])) {
            fn_hybrid_auth_delete_provider($_REQUEST['provider_id']);
        }
    }

    if (
        $mode === 'm_delete_provider'
        && !empty($_REQUEST['provider_ids'])
    ) {
        foreach ((array) $_REQUEST['provider_ids'] as $provider_id) {
            fn_hybrid_auth_delete_provider($provider_id);
        }
    }

    if (
        $mode === 'm_update_statuses'
        && !empty($_REQUEST['provider_ids'])
        && !empty($_REQUEST['status'])
    ) {
        $status_to = $_REQUEST['status'];

        foreach ((array) $_REQUEST['provider_ids'] as $provider_id) {
            fn_tools_update_status(
                [
                    'table'             => 'hybrid_auth_providers',
                    'status'            => $status_to,
                    'id_name'           => 'provider_id',
                    'id'                => $provider_id,
                    'show_error_notice' => false,
                ]
            );
        }

        if (defined('AJAX_REQUEST')) {
            $redirect_url = fn_url('hybrid_auth.manage');
            if (isset($_REQUEST['redirect_url'])) {
                $redirect_url = $_REQUEST['redirect_url'];
            }
            Tygh::$app['ajax']->assign('force_redirection', $redirect_url);
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            return [CONTROLLER_STATUS_NO_CONTENT];
        }
    }

    return [CONTROLLER_STATUS_REDIRECT, 'hybrid_auth.manage'];
}

if ($mode === 'update' && !empty($_REQUEST['provider_id'])) {
    $providers_schema = fn_get_schema('hybrid_auth', 'providers');
    $available_providers = array_keys($providers_schema);

    $provider_data = fn_hybrid_auth_get_provider_data($_REQUEST['provider_id']);

    if (!isset($providers_schema[$provider_data['provider']])) {
        fn_set_notification(NotificationSeverity::ERROR, __('error'), __('hybrid_auth.provider_not_found', ['[provider]' => $provider_data['provider']]));
    }

    if (!empty($provider_data['provider_id'])) {
        Tygh::$app['view']->assign('id', $provider_data['provider_id']);
    } else {
        Tygh::$app['view']->assign('id', 0);
    }

    $all_storefront_ids = db_get_fields('SELECT storefront_id FROM ?:storefronts');
    $callback_urls = fn_hybrid_auth_get_callback_urls($providers_schema[$provider_data['provider']], $all_storefront_ids);

    Tygh::$app['view']->assign([
        'provider'            => $provider_data['provider'],
        'providers_schema'    => $providers_schema,
        'available_providers' => $available_providers,
        'provider_data'       => $provider_data,
        'all_storefront_ids'  => $all_storefront_ids,
        'callback_urls'       => $callback_urls,
    ]);

} elseif ($mode === 'manage') {
    $providers_schema = fn_get_schema('hybrid_auth', 'providers');
    $available_providers = array_keys(
        array_filter($providers_schema, function ($provider) {
            return !isset($provider['enabled']) || $provider['enabled'] !== false;
        })
    );
    $providers_list = fn_hybrid_auth_get_providers_list(false);

    $all_storefront_ids = db_get_fields('SELECT storefront_id FROM ?:storefronts');

    Tygh::$app['view']->assign('id', 0);
    Tygh::$app['view']->assign('provider', reset($available_providers));
    Tygh::$app['view']->assign('providers_schema', $providers_schema);
    Tygh::$app['view']->assign('available_providers', $available_providers);
    Tygh::$app['view']->assign('providers_list', $providers_list);
    Tygh::$app['view']->assign('all_storefront_ids', $all_storefront_ids);

} elseif ($mode === 'select_provider') {
    $providers_schema = fn_get_schema('hybrid_auth', 'providers');
    $provider_data = fn_hybrid_auth_get_provider_data($_REQUEST['id']);

    $all_storefront_ids = db_get_fields('SELECT storefront_id FROM ?:storefronts');
    $callback_urls = fn_hybrid_auth_get_callback_urls($providers_schema[$_REQUEST['provider']], $all_storefront_ids);

    Tygh::$app['view']->assign([
        'id'                 => $_REQUEST['id'],
        'provider'           => $_REQUEST['provider'],
        'providers_schema'   => $providers_schema,
        'provider_data'      => $provider_data,
        'all_storefront_ids' => $all_storefront_ids,
        'callback_urls'      => $callback_urls,
    ]);
    Tygh::$app['view']->display('addons/hybrid_auth/views/hybrid_auth/update.tpl');

    exit;
}
