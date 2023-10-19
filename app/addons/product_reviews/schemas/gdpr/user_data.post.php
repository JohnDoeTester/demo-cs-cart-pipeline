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

use Tygh\Addons\ProductReviews\ServiceProvider as ProductReviewsProvider;

/** @var array $schema */
$schema['product_reviews'] = [
    'collect_data_callback' => static function ($params) {
        $product_reviews = [];

        if (isset($params['user_id'])) {
            $product_reviews_repository = ProductReviewsProvider::getProductReviewRepository();
            list($product_reviews,) = $product_reviews_repository->find(['user_id' => (int) $params['user_id']]);
        }

        return $product_reviews;
    },
    'update_data_callback' => static function ($product_reviews) {
        if (!is_array($product_reviews)) {
            return;
        }

        foreach ($product_reviews as $review) {
            // phpcs:ignore
            if (!empty($review['product_review_id'])) {
                db_replace_into(
                    'product_reviews',
                    [
                        'product_review_id' => $review['product_review_id'],
                        'name' => $review['user_data']['name'],
                        'ip_address' => $review['user_data']['ip_address'],
                        'country_code' => $review['user_data']['country_code'],
                        'city' => $review['user_data']['city']
                    ]
                );
            }
        }
    },
    'params'        => [
        'fields_list' => [
            'name',
            'ip_address',
            'country_code',
            'city'
        ],
    ],
];

return $schema;
