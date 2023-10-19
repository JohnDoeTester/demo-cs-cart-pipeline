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

use Tygh\Addons\ProductBundles\ServiceProvider as BundleServiceProvider;
use Tygh\Addons\ProductVariations\ServiceProvider;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\SiteArea;
use Tygh\Enum\YesNo;
use Tygh\Registry;

class ProductBundlesHookHandler
{
    /**
     * The `product_bundle_service_update_links` hook handler.
     *
     * Action performed:
     *     - Adds additional links to child variation products if 'any variation' attribute set.
     *
     * @param int                                   $bundle_id     Bundle identifier.
     * @param array<string>                         $products_data Bundle products information.
     * @param array<int, array<string, int|string>> $links_data    Product links information.
     *
     * @param-out array<int, array<string, int|string>> $links_data
     *
     * @see ProductBundleService::updateLinks()
     *
     * @return void
     */
    public function onUpdateLinks($bundle_id, array $products_data, array &$links_data)
    {
        $product_id_map = ServiceProvider::getProductIdMap();
        $additional_links_data = $links_data;
        foreach ($links_data as $product_link_id => $product_link_data) {
            if (!YesNo::toBool($product_link_data['all_variants'])) {
                continue;
            }
            $product_id = (int) $product_link_data['product_id'];
            $other_variations = $product_id_map->getProductChildrenIds($product_id);
            if (empty($other_variations)) {
                $parent_id = $product_id_map->getParentProductId($product_id);
                if (!$parent_id) {
                    continue;
                }
                $other_variations = $product_id_map->getProductChildrenIds($parent_id);
                if (empty($other_variations)) {
                    continue;
                }
                $additional_links_data[$product_link_id]['product_id'] = $parent_id;
            }
            $detect_existing_link_data = static function ($id, $data) {
                foreach ($data as $datum) {
                    if (($datum['product_id'] === (int) $id) && (!YesNo::toBool($datum['all_variants']))) {
                        return true;
                    }
                }
                return false;
            };
            foreach ($other_variations as $product_child) {
                if ($detect_existing_link_data($product_child, $additional_links_data)) {
                    continue;
                }
                $additional_links_data[$product_child] = $product_link_data;
                $additional_links_data[$product_child]['product_id'] = $product_child;
                $additional_links_data[$product_child]['amount'] = 1;
                $additional_links_data[$product_child]['all_variants'] = YesNo::NO;
            }
        }
        unset($product_link_data);
        $links_data = $additional_links_data;
    }

    /**
     * The `product_bundle_service_get_bundles_post` hook handlers.
     *
     * Action performed:
     *      - Changing product name to selected variation name.
     *      - Swapping bundle product for specified variation of this bundle product, if there is a specified variation.
     *
     * @param array<string> $params  Parameters for selecting bundles.
     * @param array<string> $bundles Selected bundles.
     *
     * @see ProductBundleService::getBundles()
     *
     * @return void
     */
    public function onPostGetBundles(array $params, array &$bundles)
    {
        if (empty($bundles)) {
            return;
        }

        $product_id_map = ServiceProvider::getProductIdMap();
        $bundle_service = BundleServiceProvider::getService();
        foreach ($bundles as &$bundle) {
            if (!is_array($bundle['products'])) {
                $bundle['products'] = unserialize($bundle['products']);
            }
            if (empty($bundle['products'])) {
                continue;
            }
            if (SiteArea::isStorefront(AREA)) {
                $products = [];
                foreach ($bundle['products'] as $product) {
                    $product['parent_variation_product'] = !empty($product_id_map->getProductChildrenIds($product['product_id']));
                    if ($product['parent_variation_product'] && isset($product['any_variation']) && $product['amount'] > 1) {
                        $length = $product['amount'];
                        $product['amount'] = 1;
                        for ($i = 0; $i < $length; $i++) {
                            $products[] = $product;
                        }
                    } else {
                        $products[] = $product;
                    }
                }
                $bundle['products'] = $products;
            }
            foreach ($bundle['products'] as $index => &$product) {
                if (
                    !isset($product['any_variation'])
                    && isset($product['product_data']['variation_name'])
                ) {
                    $product['product_name'] = $product['product_data']['variation_name'];
                }
                if (empty($params['product_variants'])) {
                    continue;
                }
                $product_variants = $params['product_variants'];
                if (!isset($product_variants[$index])) {
                    continue;
                }
                if (isset($product_variants[$index]['product_features'])) {
                    $feature_variants = $product_variants[$index]['product_features'];
                    list($variation_products,) = fn_get_products(['feature_variants' => $feature_variants, 'include_child_variations' => true]);
                    if ($parent_id = $product_id_map->getParentProductId($product['product_id'])) {
                        $variation_ids = $product_id_map->getProductChildrenIds($parent_id);
                        $variation_ids[] = $parent_id;
                    } else {
                        $variation_ids = $product_id_map->getProductChildrenIds($product['product_id']);
                        $variation_ids[] = $product['product_id'];
                    }
                    $needed_product = array_filter(array_keys($variation_products), static function ($variation_product_id) use ($variation_ids) {
                        return in_array($variation_product_id, $variation_ids);
                    });
                    $product = $bundle_service->swapProductWithVariant($product, reset($needed_product));
                } elseif ((int) $product_variants[$index]['product_id'] !== (int) $product['product_id']) {
                    $product = $bundle_service->swapProductWithVariant($product, $product_variants[$index]['product_id']);
                }
            }
            unset($product);
            if (empty($params['product_variants'])) {
                continue;
            }
            $bundle = $bundle_service->recalculateBundlePrices($bundle);
        }
        unset($bundle);
    }

    /**
     * The `product_bundles_service_update_hidden_promotion_before_update` hook handler.
     *
     * Action performed:
     *     - Change owner of hidden promotion assigned to product bundle.
     *
     * @param array<string> $bundle_data         Bundle information.
     * @param array<string> $bundle_descriptions Bundle descriptions.
     * @param int           $promotion_id        Promotion identifier.
     * @param array<string> $data                Promotion data.
     *
     * @see ProductBundleService::updateHiddenPromotion()
     *
     * @return void
     */
    public function onPreUpdatePromotion(array $bundle_data, array $bundle_descriptions, $promotion_id, array &$data)
    {
        if (Registry::get('addons.direct_payments.status') !== ObjectStatuses::ACTIVE) {
            return;
        }

        $data['company_id'] = $bundle_data['company_id'];
    }

    /**
     * The `product_bundle_service_get_bundles` hook handler.
     *
     * Action performed:
     *      - Allows getting bundles for main variation products if any variations allowed.
     *
     * @param array<string|int>     $params     Parameters for bundles search.
     * @param string                $fields     Requesting product bundles fields.
     * @param array<string, string> $joins      Joining tables for request.
     * @param array<string, string> $conditions Conditions of request.
     * @param array<string, string> $limit      Limit conditions of request.
     *
     * @param-out array<string|int|array<int>> $params
     *
     * @return void
     */
    public function onGetBundles(array &$params, $fields, array $joins, array &$conditions, array $limit)
    {
        if (!isset($conditions['product_id'], $params['product_id']) || !SiteArea::isStorefront(AREA)) {
            return;
        }
        $product_id = (int) $params['product_id'];
        $product_id_map = ServiceProvider::getProductIdMap();
        if (!$product_id_map->isChildProduct($product_id)) {
            return;
        }
        $child_product_id = $product_id;
        $parent_product_id = $product_id_map->getParentProductId($product_id);
        $conditions['product_id'] = db_quote(
            ' AND ((links.product_id = ?s AND links.all_variants = ?s) OR links.product_id = ?s)',
            $parent_product_id,
            YesNo::YES,
            $child_product_id
        );
    }

    /**
     * The "product_bundle_service_check_cart_for_complete_bundles_before_getting_product_ids" hook handler.
     * Actions performed:
     * - Adds parent variation when checking bundle variation product with the "Any variation" setting set.
     *
     * @param array<string, string>        $bundle_data   Bundle information
     * @param array<string, array<string>> $cart_products All products in the cart
     * @param array<string, string>        $product_info  Checked bundle product data
     * @param array<int>                   $product_ids   Product IDs to search against
     *
     * @return void
     *
     * @internal
     *
     * @see \Tygh\Addons\ProductBundles\Services\ProductBundleService::checkCartForCompleteBundles
     */
    public function onCheckCartForCompleteBundles(
        array $bundle_data,
        array $cart_products,
        array $product_info,
        array &$product_ids
    ) {
        if (
            !isset($product_info['any_variation'])
            || YesNo::isFalse($product_info['any_variation'])
        ) {
            return;
        }

        $product_id = reset($product_ids);
        $product_id_map = ServiceProvider::getProductIdMap();
        $product_ids = $product_id_map->getProductChildrenIds($product_id);
        $product_ids[] = $product_id;
    }

    /**
     * The "product_bundle_service_how_many_bundles_can_be_in_cart_before_getting_product_amounts" hook handler.
     * Actions performed:
     * - Replaces amount of variation products with the amount of parent products.
     *
     * @param int                                            $bundle_id            Bundle identifier
     * @param array<int, int>                                $cart_product_amounts Cart products amounts
     * @param array<array{amount: int, all_variants:string}> $bundle_products      Bundle products
     *
     * @return void
     *
     * @internal
     *
     * @see \Tygh\Addons\ProductBundles\Services\ProductBundleService::howManyBundlesCanBeInCart
     */
    public function onHowManyBundlesCanBeInCartBeforeGettingProductAmounts(
        $bundle_id,
        array &$cart_product_amounts,
        array &$bundle_products
    ) {
        $product_id_map = ServiceProvider::getProductIdMap();

        foreach ($cart_product_amounts as $product_id => $product_amount) {
            // phpcs:ignore
            if (
                $product_id_map->isChildProduct($product_id)
                && isset($bundle_products[$product_id_map->getParentProductId($product_id)])
                && YesNo::toBool($bundle_products[$product_id_map->getParentProductId($product_id)]['all_variants'])
            ) {
                $parent_id = $product_id_map->getParentProductId($product_id);
                $cart_product_amounts[$parent_id] = isset($cart_product_amounts[$parent_id])
                    ? $cart_product_amounts[$parent_id] + $product_amount
                    : $product_amount;
                unset($cart_product_amounts[$product_id]);
            }
        }

        foreach (array_keys($bundle_products) as $product_id) {
            //phpcs:ignore
            if (
                $product_id_map->isChildProduct($product_id)
                && isset($bundle_products[$product_id_map->getParentProductId($product_id)])
                && YesNo::toBool($bundle_products[$product_id_map->getParentProductId($product_id)]['all_variants'])
            ) {
                $bundle_products[$product_id_map->getParentProductId($product_id)]['amount'] += $bundle_products[$product_id]['amount'];
                unset($bundle_products[$product_id]);
            }
        }
    }

    /**
     * The "product_bundle_service_how_many_bundles_can_be_in_cart_before_getting_product_id" hook handler.
     * Actions performed:
     * - Replaces ID of a variation product with the ID of the parent one.
     *
     * @param int                          $bundle_id            Bundle identifier
     * @param array<int, int>              $cart_product_amounts Cart products amounts
     * @param array<array<string, string>> $bundle_products      Bundle products
     * @param array<string, string>        $bundle_product       Bundle product
     * @param int                          $product_id           Product identifier to check amount against
     *
     * @return void
     *
     * @internal
     *
     * @see \Tygh\Addons\ProductBundles\Services\ProductBundleService::howManyBundlesCanBeInCart
     */
    public function onHowManyBundlesCanBeInCartBeforeGettingProductId(
        $bundle_id,
        array $cart_product_amounts,
        array $bundle_products,
        array $bundle_product,
        &$product_id
    ) {
        $product_id_map = ServiceProvider::getProductIdMap();
        $parent_product_id = $product_id_map->getParentProductId($bundle_product['product_id']);

        if (
            !$product_id_map->isChildProduct($bundle_product['product_id'])
            || !$parent_product_id
            || !isset($bundle_products[$parent_product_id])
            || YesNo::isFalse($bundle_products[$parent_product_id]['all_variants'])
        ) {
            return;
        }

        $product_id = $product_id_map->getParentProductId($bundle_product['product_id']);
    }
}
