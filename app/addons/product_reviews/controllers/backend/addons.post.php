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

use Tygh\Addons\ProductReviews\ServiceProvider as ProductReviewsProvider;
use Tygh\Addons\ProductVariations\ServiceProvider as ProductVariationsProvider;
use Tygh\Enum\YesNo;
use Tygh\Providers\StorefrontProvider;
use Tygh\Registry;
use Tygh\Settings;

defined('BOOTSTRAP') or die('Access denied');

$is_product_reviews_addon = !empty($_REQUEST['addon']) && $_REQUEST['addon'] === 'product_reviews';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        $mode === 'update'
        && $is_product_reviews_addon
        && isset($_REQUEST['split_reviews_for_variations_as_separate_products'])
    ) {
        Settings::instance()->updateValue(
            'split_reviews_for_variations_as_separate_products',
            YesNo::toId($_REQUEST['split_reviews_for_variations_as_separate_products']),
            'product_reviews'
        );

        if (
            $_REQUEST['split_reviews_for_variations_as_separate_products']
            !== Registry::ifGet('addons.product_reviews.split_reviews_for_variations_as_separate_products', YesNo::YES)
        ) {
            Registry::set(
                'addons.product_reviews.split_reviews_for_variations_as_separate_products',
                $_REQUEST['split_reviews_for_variations_as_separate_products']
            );

            $reviews_service = ProductReviewsProvider::getService();
            $group_repository = ProductVariationsProvider::getGroupRepository();
            $product_id_map = ProductVariationsProvider::getProductIdMap();
            $groups = $group_repository->findAllGroupIds();

            /** @var \Tygh\Storefront\Storefront[] $storefronts */
            list($storefronts,) = StorefrontProvider::getRepository()->find(['cache' => true, 'get_total' => false]);
            $storefront_ids = array_map(
                static function ($storefront) {
                    return $storefront->storefront_id;
                },
                $storefronts
            );

            if (YesNo::toBool(Registry::ifGet('addons.product_reviews.split_reviews_for_variations_as_separate_products', YesNo::YES))) {
                // when the setting is turned on
                $groups_product_ids = $group_repository->findGroupProductIdsByGroupIds($groups);
                $parent_product_ids = [];

                foreach ($groups_product_ids as $group_product_id) {
                    if ($product_id_map->isParentProduct($group_product_id)) {
                        $parent_product_ids[] = $group_product_id;
                    }
                }

                foreach ($parent_product_ids as $product_id) {
                    $reviews_service->actualizeProductPreparedData($product_id, $storefront_ids);
                }

                $sync_service = ProductVariationsProvider::getSyncService();
                $sync_service->onTableChanged('product_review_prepared_data', $parent_product_ids);
            } else {
                // when the setting is turned off
                foreach ($groups as $group) {
                    fn_product_variations_product_reviews_actualize_variations_prepared_data(
                        $group_repository->findGroupProductIdsByGroupIds([$group]),
                        $storefront_ids,
                        $product_id_map
                    );
                }
            }
        }
    }

    return [CONTROLLER_STATUS_OK];
}

if (
    $mode === 'update'
    && $is_product_reviews_addon
) {
    $setting_value = Settings::instance()->getValue(
        'split_reviews_for_variations_as_separate_products',
        'product_reviews'
    );

    Tygh::$app['view']->assign('split_reviews_for_variations_as_separate_products', $setting_value);
}
