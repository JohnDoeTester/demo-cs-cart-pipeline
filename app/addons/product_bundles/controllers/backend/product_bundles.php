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

use Tygh\Addons\ProductBundles\ServiceProvider;
use Tygh\Enum\SiteArea;
use Tygh\Http;
use Tygh\Providers\StorefrontProvider;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

$bundle_service = ServiceProvider::getService();

/** @var string $mode */
/** @var array $auth */
$auth = & Tygh::$app['session']['auth'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    fn_trusted_vars('item_data');

    if ($mode === 'update') {
        if (empty($_REQUEST['item_data'])) {
            return [CONTROLLER_STATUS_OK];
        }
        $bundle_data = $bundle_service->validateBundleData($_REQUEST['item_data'], []);
        $bundle_service->updateBundle($bundle_data, $_REQUEST['item_id']);
        $return_url = isset($_REQUEST['return_url']) ? $_REQUEST['return_url'] : 'product_bundles.manage';
        return [CONTROLLER_STATUS_OK, $return_url];
    }

    if ($mode === 'delete') {
        $bundle_service->deleteBundle($_REQUEST['bundle_id']);
        $return_url = isset($_REQUEST['return_url']) ? $_REQUEST['return_url'] : 'product_bundles.manage';
        return [CONTROLLER_STATUS_OK, $return_url];
    }

    if ($mode === 'change_variation') {
        if (empty($_REQUEST['product_id'])) {
            return [CONTROLLER_STATUS_NO_CONTENT];
        }
        list($product,) = fn_get_products(['pid' => $_REQUEST['product_id']]);
        fn_gather_additional_products_data($product, [
            'get_icon'                        => true,
            'get_detailed'                    => true,
            'get_discounts'                   => true,
            'get_options'                     => true,
            'get_variation_features_variants' => true,
        ]);
        Tygh::$app['view']->assign('product', reset($product));
        Tygh::$app['view']->assign('row_index', $_REQUEST['row_index']);
        Tygh::$app['view']->assign('hide_amount', true);
        Tygh::$app['view']->assign('show_aoc', true);
        Tygh::$app['view']->assign('is_product_bundles', true);
        Tygh::$app['view']->display('views/products/components/products_list_row.tpl');

        return [CONTROLLER_STATUS_NO_CONTENT];
    }

    if (
        $mode === 'm_update_statuses'
        && !empty($_REQUEST['bundle_ids'])
        && !empty($_REQUEST['status'])
    ) {
        $status_to = $_REQUEST['status'];
        if (!is_array($_REQUEST['bundle_ids'])) {
            $bundle_ids = [$_REQUEST['bundle_ids']];
        } else {
            $bundle_ids = $_REQUEST['bundle_ids'];
        }

        foreach ($bundle_ids as $bundle_id) {
            fn_tools_update_status(
                [
                    'table'             => 'product_bundles',
                    'status'            => $status_to,
                    'id_name'           => 'bundle_id',
                    'id'                => $bundle_id,
                    'show_error_notice' => false,
                ]
            );
        }

        if (
            defined('AJAX_REQUEST')
            && isset($_REQUEST['redirect_url'])
        ) {
            Tygh::$app['ajax']->assign('force_redirection', $_REQUEST['redirect_url']);
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
        }
        return [CONTROLLER_STATUS_NO_CONTENT];
    }

    if (
        $mode === 'm_delete'
        && !empty($_REQUEST['bundle_ids'])
    ) {
        if (!is_array($_REQUEST['bundle_ids'])) {
            $bundle_ids = [$_REQUEST['bundle_ids']];
        } else {
            $bundle_ids = $_REQUEST['bundle_ids'];
        }

        foreach ($bundle_ids as $bundle_id) {
            $bundle_service->deleteBundle($bundle_id);
        }

        if (
            defined('AJAX_REQUEST')
            && isset($_REQUEST['redirect_url'])
        ) {
            Tygh::$app['ajax']->assign('force_redirection', $_REQUEST['redirect_url']);
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            return [CONTROLLER_STATUS_NO_CONTENT];
        }
    }
}

if ($mode === 'manage') {
    $default_params = [
        'lang_code'      => DESCR_SL,
        'page'           => 1,
        'items_per_page' => Registry::get('settings.Appearance.admin_elements_per_page'),
        'get_total'      => true,
    ];
    $params = array_merge($default_params, $_REQUEST);
    $company_id = fn_get_runtime_company_id();
    if ($company_id) {
        $params['company_id'] = $company_id;
    }

    if (
        fn_allowed_for('MULTIVENDOR:ULTIMATE')
        && !empty($auth['storefront_id'])
    ) {
        /** @var \Tygh\Storefront\Repository $repository */
        $repository = Tygh::$app['storefront.repository'];

        $storefront = $repository->findById($auth['storefront_id']);
        if ($storefront) {
            $params['company_ids'] = $storefront->getCompanyIds();
        }
    }

    list($bundles, $search) = $bundle_service->getBundles($params);
    $page = $search['page'];
    $valid_page = db_get_valid_page($page, $search['items_per_page'], $search['total_items']);

    if ($page > $valid_page) {
        $_REQUEST['page'] = $valid_page;
        return [CONTROLLER_STATUS_REDIRECT, Registry::get('config.current_url')];
    }
    foreach ($bundles as &$bundle) {
        if (!is_array($bundle['products'])) {
            $bundle['products'] = unserialize($bundle['products']);
        }
        if (empty($bundle['products'])) {
            continue;
        }
        foreach ($bundle['products'] as $product) {
            $bundle['product_ids'][] = $product['product_id'];
        }
        $bundle['product_ids'] = implode(',', $bundle['product_ids']);
    }
    unset($bundle);
    Tygh::$app['view']->assign('bundles', $bundles);
    $selected_storefront_id = isset($_REQUEST['storefront_id']) ? $_REQUEST['storefront_id'] : StorefrontProvider::getStorefront()->storefront_id;
    Tygh::$app['view']->assign('selected_storefront_id', $selected_storefront_id);
    Tygh::$app['view']->assign('search', $search);
    return [CONTROLLER_STATUS_OK];
}

if ($mode === 'update') {
    $company_id = fn_get_runtime_company_id();
    list($bundle,) = $bundle_service->getBundles(
        [
            'bundle_id'            => $_REQUEST['bundle_id'],
            'company_id'           => $company_id,
            'full_info'            => true,
            'lang_code'            => DESCR_SL,
            'allow_empty_products' => true,
        ]
    );
    if (!empty($bundle)) {
        $item = reset($bundle);
        Tygh::$app['view']->assign('item', $item);

        if (!empty($item['products'])) {
            $exclude_pid = array_column($item['products'], 'product_id');
            Tygh::$app['view']->assign('exclude_pid', implode(',', $exclude_pid));
        }
    }
    if (isset($_REQUEST['return_url'])) {
        Tygh::$app['view']->assign('return_url', $_REQUEST['return_url']);
    }
    Tygh::$app['view']->assign([
        'hide_delete_button' => !fn_check_view_permissions('product_bundles.delete', Http::POST)
    ]);
}
