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

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'update' && isset($_REQUEST['sync_provider_id'])) {
        $provider_id = $_REQUEST['sync_provider_id'];

        $sync_provider_list = fn_get_schema('sync_data', 'sync_data');

        if (
            !isset($sync_provider_list[$provider_id])
            || !isset($_REQUEST['sync_data_settings'])
            || !isset($_REQUEST['sync_data_settings'][$provider_id])
        ) {
            return [CONTROLLER_STATUS_DENIED];
        }

        $settings = $_REQUEST['sync_data_settings'][$provider_id];
        $company_id = fn_get_runtime_company_id();

        fn_save_sync_data_settings($provider_id, $company_id, $settings);

        return [CONTROLLER_STATUS_OK, 'sync_data.update?sync_provider_id=' . $_REQUEST['sync_provider_id']];
    }

    return [CONTROLLER_STATUS_DENIED];
}

if ($mode === 'manage') {
    $sync_provider_list = fn_get_schema('sync_data', 'sync_data');
    $company_id = fn_get_runtime_company_id();

    $last_sync_info = [];

    foreach ($sync_provider_list as $provider_id => $provider_data) {
        if (!isset($provider_data['last_sync_info'])) {
            continue;
        }

        if (isset($provider_data['last_sync_info']['function']) && is_callable($provider_data['last_sync_info']['function'])) {
            $last_sync_info[$provider_id] = call_user_func($provider_data['last_sync_info']['function'], $provider_id, $company_id);
        }
    }

    Tygh::$app['view']->assign([
        'sync_provider_list' => $sync_provider_list,
        'last_sync_info'     => $last_sync_info,
    ]);
}

if ($mode === 'update') {
    if (empty($_REQUEST['sync_provider_id'])) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $provider_id = $_REQUEST['sync_provider_id'];
    $sync_provider_list = fn_get_schema('sync_data', 'sync_data');

    if (!isset($sync_provider_list[$provider_id])) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $company_id = fn_get_runtime_company_id();

    $sync_data_settings = fn_get_sync_data_settings($provider_id, $company_id);

    Tygh::$app['view']->assign([
        'provider_data' => $sync_provider_list[$provider_id],
        'sync_settings' => $sync_data_settings
    ]);
}
