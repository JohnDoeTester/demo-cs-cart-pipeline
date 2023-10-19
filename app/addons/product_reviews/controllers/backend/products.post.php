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
use Tygh\Enum\YesNo;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\Addons\ProductReviews\ProductReviewsMessageTypes;
use Tygh\Http;
use Tygh\Providers\StorefrontProvider;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

$product_reviews_service = ProductReviewsProvider::getService();
$product_reviews_repository = ProductReviewsProvider::getProductReviewRepository();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    return [CONTROLLER_STATUS_OK];
}

if ($mode === 'update') {
    /** @var array $product_data */
    $product_data = Tygh::$app['view']->getTemplateVars('product_data');
    $is_allowed_to_view_reviews = fn_check_view_permissions('product_reviews.manage', Http::GET);
    $is_allowed_to_add_reviews  = fn_check_view_permissions('product_reviews.add', Http::GET);

    Tygh::$app['view']->assign([
        'is_allowed_to_view_product_reviews'   => $is_allowed_to_view_reviews,
        'is_allowed_to_update_product_reviews' => fn_check_view_permissions('product_reviews.update', Http::POST),
        'is_allowed_to_add_product_reviews'    => fn_check_view_permissions('product_reviews.add', Http::POST),
    ]);

    if (!$is_allowed_to_view_reviews) {
        if ($is_allowed_to_add_reviews) {
            Registry::set('navigation.tabs.product_reviews', [
                'title' => __('product_reviews.title'),
                'js'    => true,
            ]);
        }

        return [CONTROLLER_STATUS_OK];
    }

    $params = [
        'product_id' => (int) $product_data['product_id'],
    ];

    if (empty($_REQUEST['items_per_page'])) {
        $params['items_per_page'] = (int) Registry::get('addons.product_reviews.reviews_per_page');
    }

    if (
        fn_allowed_for('ULTIMATE')
        && Registry::get('runtime.company_id')
    ) {
        $params['storefront_id'] = fn_product_reviews_get_storefront_id_by_setting();
    }

    $params = array_merge($_REQUEST, $params);

    list($product_reviews, $search) = $product_reviews_repository->find($params);

    Registry::set('navigation.tabs.product_reviews', [
        'title' => __('product_reviews.title'),
        'js'    => true,
    ]);

    Tygh::$app['view']->assign([
        'is_allowed_to_delete_product_reviews' => fn_check_view_permissions('product_reviews.delete', Http::POST),
        'product_reviews_rating_stats'         => $product_reviews_service->getProductRatingStats(
            $product_data['product_id'],
            isset($params['storefront_id'])
                ? $params['storefront_id']
                : 0
        ),
        'product_reviews'                      => $product_reviews,
        'product_reviews_search'               => $search,
        'available_message_types'              => ProductReviewsMessageTypes::getTypes(Registry::get('addons.product_reviews.review_fields')),
        'product_review_status_descr'          => fn_product_reviews_get_statuses_descriptions()
    ]);
} elseif ($mode === 'add') {
    Tygh::$app['view']->assign([
        'is_allowed_to_view_product_reviews'   => fn_check_view_permissions('product_reviews.manage', Http::GET),
        'is_allowed_to_update_product_reviews' => fn_check_view_permissions('product_reviews.update', Http::POST),
    ]);
}
