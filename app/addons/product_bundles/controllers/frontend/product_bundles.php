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
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

$bundle_service = ServiceProvider::getService();

/** @var string $mode */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'change_variation' || $mode === 'change_options') {
        if (!isset($_REQUEST['bundle_id'], $_REQUEST['product_data'])) {
            return [CONTROLLER_STATUS_OK];
        }

        $bundle_id = $_REQUEST['bundle_id'];
        $product_data = $_REQUEST['product_data'];
        list($bundles,) = $bundle_service->getBundles([
            'full_info'        => true,
            'bundle_id'        => $bundle_id,
            'product_variants' => $product_data,
        ]);
        Registry::set($bundle_service::BUNDLE_CACHE_KEY, $bundles, true);

        /** @var \Tygh\SmartyEngine\Core $view */
        $view = Tygh::$app['view'];
        $view->assign('bundle', reset($bundles));
        $view->display('addons/product_bundles/views/product_bundles/get_product_bundles.tpl');

        return [CONTROLLER_STATUS_NO_CONTENT];
    }

    return [CONTROLLER_STATUS_OK];
}

if ($mode === 'get_feature_variants' || $mode === 'get_option_variants') {
    if (empty($_REQUEST['product_id']) || !isset($_REQUEST['bundle_id'], $_REQUEST['bundle_product_key'])) {
        return [CONTROLLER_STATUS_OK];
    }
    $variation_id = $_REQUEST['product_id'];
    list($bundle_product,) = fn_get_products(['pid' => $variation_id]);

    $bundle_product = reset($bundle_product);
    if (!empty($_REQUEST['selected_options'])) {
        $bundle_product['selected_options'] = $_REQUEST['selected_options'];
    }

    fn_gather_additional_products_data($bundle_product, [
        'get_icon'                        => false,
        'get_detailed'                    => false,
        'get_discounts'                   => true,
        'get_options'                     => true,
        'get_variation_features_variants' => true,
    ]);

    Tygh::$app['view']->assign('bundle', [
        'bundle_id' => $_REQUEST['bundle_id']
    ]);
    Tygh::$app['view']->assign('bundle_product_key', $_REQUEST['bundle_product_key']);
    Tygh::$app['view']->assign('bundle_product', $bundle_product);

    return [CONTROLLER_STATUS_OK];
}

if ($mode === 'get_product_bundles') {
    if (!isset($_REQUEST['bundle_id'])) {
        return [CONTROLLER_STATUS_OK];
    }

    if (isset($_REQUEST['in_popup']) && $_REQUEST['in_popup']) {
        $in_popup = true;
    } else {
        $in_popup = false;
    }

    Tygh::$app['view']->assign('bundle', [
        'bundle_id' => $_REQUEST['bundle_id'],
    ]);
    Tygh::$app['view']->assign('in_popup', $in_popup);

    return [CONTROLLER_STATUS_OK];
}
