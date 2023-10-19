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

defined('BOOTSTRAP') or die('Access denied');

if ($mode === 'details') {
    /** @var array<string, array<string>|string> $order_info */
    $order_info = Tygh::$app['view']->getTemplateVars('order_info');
    if (empty($order_info['promotions'])) {
        return;
    }
    /** @var string $applied_promotion_ids */
    $applied_promotion_ids = $order_info['promotion_ids'];
    $bundle_promotions = db_get_hash_array('SELECT linked_promotion_id, bundle_id FROM ?:product_bundles', 'linked_promotion_id');
    if (empty($bundle_promotions)) {
        return;
    }

    $applied_bundles = [];
    foreach ($bundle_promotions as $bundle_promotion_id => $bundle_info) {
        if (!in_array($bundle_promotion_id, explode(',', $applied_promotion_ids))) {
            continue;
        }
        $applied_bundles[$bundle_info['linked_promotion_id']] = $bundle_info['bundle_id'];
    }

    $bundle_service = ServiceProvider::getService();
    list($bundles,) = $bundle_service->getBundles(
        [
            'bundle_id' => array_values($applied_bundles),
            'full_info' => true,
            'items_per_page' => 0,
            'page' => 0,
        ]
    );
    Tygh::$app['view']->assign('bundles', $bundles);
    Tygh::$app['view']->assign('bundle_promotions', array_keys($applied_bundles));
    Tygh::$app['view']->assign('extra_mode', 'product_bundles');
}
