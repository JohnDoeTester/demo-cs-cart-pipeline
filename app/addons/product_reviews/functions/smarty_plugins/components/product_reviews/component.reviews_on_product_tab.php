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
use Tygh\Enum\ObjectStatuses;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

/**
 * @param array{product: array<string, string|int>, request: array<string, string|int>, title: string, quicklink: string, container_id:string, locate_to_product_review_tab: bool} $params  Block params
 * @param string                                                                                                                                                                   $content Block content
 * @param \Smarty_Internal_Template                                                                                                                                                $tempale Smarty template
 *
 * @throws Exception       Internal smarty rendering error.
 * @throws SmartyException If unable to load template.
 *
 * @return string
 */
function smarty_component_product_reviews_reviews_on_product_tab(array $params, $content, Smarty_Internal_Template $tempale)
{
    $product = $params['product'];
    $search_params = $params['request'];
    $search_params['product_id'] = (int) $product['product_id'];
    $search_params['status'] = ObjectStatuses::ACTIVE;
    unset($search_params['company_id']);

    if (empty($search_params['items_per_page'])) {
        $search_params['items_per_page'] = (int) Registry::get('addons.product_reviews.reviews_per_page');
    }

    $search_params['storefront_id'] = fn_product_reviews_get_storefront_id_by_setting();

    $product_reviews_repository = ProductReviewsProvider::getProductReviewRepository();
    $service = ProductReviewsProvider::getService();

    list($product_reviews, $search) = $product_reviews_repository->find($search_params);
    $product['product_reviews'] = $product_reviews;
    $first_review = reset($product_reviews);
    $product['product_reviews_rating_stats'] = $service->getProductRatingStats(
        $first_review ? $first_review['product']['product_id'] : 0,
        $search_params['storefront_id']
    );

    $tempale->assign([
        'product'                        => $product,
        'product_id'                     => $search_params['product_id'],
        'product_reviews'                => $product['product_reviews'],
        'product_reviews_search'         => $search,
        'product_reviews_sorting'        => $product_reviews_repository->getSorting(),
        'product_reviews_sorting_orders' => ['asc', 'desc'],
        'product_reviews_avail_sorting'  => $product_reviews_repository->getAvailableSorts(),
        'title'                          => $params['title'],
        'quicklink'                      => $params['quicklink'],
        'container_id'                   => $params['container_id'],
        'locate_to_product_review_tab'   => $params['locate_to_product_review_tab'],
    ]);

    return $tempale->fetch('addons/product_reviews/views/product_reviews/view.tpl');
}
