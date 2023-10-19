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

namespace Tygh\Addons\ProductBundles\HookHandlers;

use Tygh\Addons\ProductBundles\ServiceProvider;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\SiteArea;
use Tygh\Enum\YesNo;

class ProductsHookHandler
{
    /**
     * The `delete_product_pre` hook handler.
     *
     * Action performed:
     *     - Changing status for bundles which contained deleted product.
     *
     * @param string $product_id Product identifier.
     * @param bool   $status     Status for product deleting process.
     *
     * @see fn_delete_product()
     *
     * @return void
     */
    public function onPreProductDelete($product_id, $status)
    {
        if (!$status) {
            return;
        }
        $product_id = (int) $product_id;
        $bundle_service = ServiceProvider::getService();

        $bundle_ids = $bundle_service->getBundleIdsByProductId($product_id);
        if (empty($bundle_ids)) {
            return;
        }
        list($bundles,) = $bundle_service->getBundles(['bundle_id' => $bundle_ids, 'with_image' => false]);
        foreach ($bundles as $bundle) {
            if (empty($bundle['products'])) {
                continue;
            }
            if (!is_array($bundle['products'])) {
                $bundle['products'] = unserialize($bundle['products']);
            }
            $bundle['products'] = array_filter($bundle['products'], static function ($product) use ($product_id) {
                return (int) $product['product_id'] !== $product_id;
            });
            $bundle['has_ignore_update_bundle_post'] = YesNo::YES;
            $bundle_service->updateBundle($bundle, $bundle['bundle_id']);
            $bundle_service->updateBundleStatus($bundle['bundle_id'], ObjectStatuses::DISABLED);
        }
        fn_set_notification(
            NotificationSeverity::WARNING,
            __('notice'),
            __(
                'product_bundles.delete_product_in_bundle',
                [count($bundle_ids), '[bundle_name]' => implode(', ', array_column($bundles, 'name'))]
            )
        );
    }

    /**
     * The `get_products_pre` hook handler.
     *
     * Action performed:
     *      - Groups child variations for product picker.
     *
     * @param array<string> $params         Product search params
     * @param int           $items_per_page Items per page
     * @param string        $lang_code      Two-letter language code (e.g. 'en', 'ru', etc.)
     *
     * @return void
     */
    public function onGetProductsPre(array &$params, $items_per_page, $lang_code)
    {
        if (
            !SiteArea::isAdmin(AREA)
            || !isset($params['segment'])
            || $params['segment'] !== 'product_bundles'
        ) {
            return;
        }
        $params['group_child_variations'] = true;
    }
}
