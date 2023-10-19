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

namespace Tygh\Addons\ProductReviews\ProductReview;

use Tygh\Addons\ProductReviews\Service;
use Tygh\Enum\YesNo;

/**
 * Class Repository
 *
 * @package Tygh\Addons\ProductReviews\ProductReview
 *
 * @psalm-type RequestParamsType = array{
 *   page?: int|string,
 *   items_per_page?: int|string,
 *   load_product_data?: bool,
 *   message?: string,
 *   comment?: string,
 *   advantages?: string,
 *   disadvantages?: string,
 *   product_review_id?: int|array<int>,
 *   product_id?: int|array<int>,
 *   user_id?: int|array<int>,
 *   status?: string,
 *   name?: string,
 *   rating?: int,
 *   helpfulness_from?: int,
 *   helpfulness_to?: int,
 *   ip_address?: string,
 *   only_buyers?: string,
 *   with_images?: bool,
 *   has_images?: bool,
 *   period?: string,
 *   time_from?: string|int,
 *   time_to?: string|int,
 *   company_id?: int,
 *   storefront_id?: int,
 *   available_filters?: string[]
 * }
 *
 * @psalm-type SearchParamsType = array{
 *   page: int,
 *   items_per_page: int,
 *   load_product_data: bool,
 *   message?: string,
 *   comment?: string,
 *   advantages?: string,
 *   disadvantages?: string,
 *   product_review_id?: int|array<int>,
 *   product_id?: int|array<int>,
 *   user_id?: int|array<int>,
 *   status?: string,
 *   name?: string,
 *   rating?: int,
 *   helpfulness_from?: int,
 *   helpfulness_to?: int,
 *   ip_address?: string,
 *   only_buyers?: string,
 *   with_images?: bool,
 *   has_images?: bool,
 *   period?: string,
 *   time_from?: string|int,
 *   time_to?: string|int,
 *   company_id?: int,
 *   storefront_id?: int,
 *   available_filters: string[]
 * }
 *
 * @psalm-type ProductReviewType array{
 *  product_review_id: int,
 *  user_data: array{
 *   user_id: int,
 *   name: string,
 *   ip_address: string,
 *   is_buyer: string,
 *   country_code: string,
 *   country: string,
 *   city: string,
 *   is_anon: bool,
 *   is_authorized: bool,
 *  },
 *  product: array{
 *   product_id: int,
 *   product?: string
 *  },
 *  message: array{
 *   advantages: string,
 *   disadvantages: string,
 *   comment: string
 *  },
 *  helpfulness: array{
 *   helpfulness: int,
 *   vote_up: int,
 *   vote_down: int
 *  },
 *  reply: array{
 *   reply_user_id?: int,
 *   reply?: string,
 *   reply_timestamp?: int,
 *   reply_company?: string,
 *   reply_company_id?: int
 *  }
 * }
 *
 * phpcs:disable SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
 */
class Repository
{
    /** @var Service */
    private $product_reviews_service;

    /** @var ImagesService */
    private $images_service;

    /** @var array<string> */
    public $settings;

    /**
     * Repository constructor.
     *
     * @param Service       $product_reviews_service Product reviews service
     * @param ImagesService $images_service          Images service
     * @param array<string> $settings                Add-on settings
     */
    public function __construct(Service $product_reviews_service, ImagesService $images_service, array $settings)
    {
        $this->product_reviews_service = $product_reviews_service;
        $this->images_service = $images_service;
        $this->settings = $settings;
    }

    /**
     * @param int    $product_review_id Product review identifier
     * @param string $lang_code         Two-letter language code (e.g. 'en', 'ru', etc.)
     *
     * @return ProductReviewType|null
     */
    public function findById($product_review_id, $lang_code = CART_LANGUAGE)
    {
        return $this->findOne(['product_review_id' => $product_review_id], $lang_code);
    }

    /**
     * @param array  $params    Search and sort parameters
     * @param string $lang_code Two-letter language code (e.g. 'en', 'ru', etc.)
     *
     * @return array{array<ProductReviewType>, SearchParamsType} Product reviews with search and sort parameters
     *
     * @psalm-param RequestParamsType $params
     */
    public function find(array $params = [], $lang_code = CART_LANGUAGE)
    {
        $default_params = [
            'page'              => 1,
            'items_per_page'    => 0,
            'load_product_data' => false,
        ];

        $fields = [
            '?:product_reviews.*',
            '?:users.company_id as reply_company_id',
            '?:companies.company as reply_company',
            '?:country_descriptions.country as country',
        ];

        $sortings = [
            'product_review_id'        => '?:product_reviews.product_review_id',
            'helpfulness'              => '?:product_reviews.helpfulness',
            'rating_value'             => '?:product_reviews.rating_value',
            'product_review_timestamp' => '?:product_reviews.product_review_timestamp',
        ];

        $params['available_filters'] = [
            'with_images',
            'only_buyers',
        ];

        $params = array_merge($default_params, $params);
        $params['page'] = (int) $params['page'];
        $params['items_per_page'] = (int) $params['items_per_page'];
        $params['message'] = isset($params['message']) ? trim($params['message']) : null;
        $params['comment'] = isset($params['comment']) ? trim($params['comment']) : null;
        $params['advantages'] = isset($params['advantages']) ? trim($params['advantages']) : null;
        $params['disadvantages'] = isset($params['disadvantages']) ? trim($params['disadvantages']) : null;

        $condition = $join = '';

        /**
         * Executes at the beginning of the method, allowing you to modify the arguments passed to the method.
         *
         * @param array                 $params    Search and sort parameters
         * @param string                $lang_code Two-letter language code (e.g. 'en', 'ru', etc.)
         * @param string[]              $fields    Selected fields
         * @param array<string, string> $sortings  Sorting fields
         * @param string                $condition Search conditions.
         * @param string                $join      Join parameter for request.
         */
        fn_set_hook('product_reviews_find_pre', $params, $lang_code, $fields, $sortings, $condition, $join);

        if (!empty($params['product_review_id'])) {
            $condition .= db_quote(' AND ?:product_reviews.product_review_id IN (?n)', (array) $params['product_review_id']);
        }

        if (!empty($params['product_id'])) {
            $condition .= db_quote(' AND ?:product_reviews.product_id IN (?n)', $params['product_id']);
        }

        if (!empty($params['user_id'])) {
            $condition .= db_quote(' AND ?:product_reviews.user_id IN (?n)', $params['user_id']);
        }

        if (!empty($params['status'])) {
            $condition .= db_quote(' AND ?:product_reviews.status = ?s', $params['status']);
        }

        if (!empty($params['name'])) {
            $like = '%' . $params['name'] . '%';
            $condition .= db_quote(' AND (?:product_reviews.name LIKE ?l OR ?:users.firstname LIKE ?l OR ?:users.lastname LIKE ?l)', $like, $like, $like);
        }

        if (!empty($params['message'])) {
            $like = '%' . $params['message'] . '%';
            $condition .= db_quote(' AND (?:product_reviews.advantages LIKE ?l OR ?:product_reviews.disadvantages LIKE ?l OR ?:product_reviews.comment LIKE ?l)', $like, $like, $like);
        }

        if (!empty($params['comment'])) {
            $like = '%' . $params['comment'] . '%';
            $condition .= db_quote(' AND ?:product_reviews.comment LIKE ?l', $like);
        }

        if (!empty($params['advantages'])) {
            $like = '%' . $params['advantages'] . '%';
            $condition .= db_quote(' AND ?:product_reviews.advantages LIKE ?l', $like);
        }

        if (!empty($params['disadvantages'])) {
            $like = '%' . $params['disadvantages'] . '%';
            $condition .= db_quote(' AND ?:product_reviews.disadvantages LIKE ?l', $like);
        }

        if (!empty($params['rating'])) {
            $condition .= db_quote(' AND ?:product_reviews.rating_value = ?i', $params['rating']);
        }

        if (!empty($params['helpfulness_from'])) {
            $condition .= db_quote(' AND ?:product_reviews.helpfulness >= ?i', $params['helpfulness_from']);
        }

        if (!empty($params['helpfulness_to'])) {
            $condition .= db_quote(' AND ?:product_reviews.helpfulness <= ?i', $params['helpfulness_to']);
        }

        if (!empty($params['ip_address'])) {
            $condition .= db_quote(' AND ?:product_reviews.ip_address = ?s', fn_ip_to_db($params['ip_address']));
        }

        if (!empty($params['only_buyers'])) {
            $condition .= db_quote(' AND ?:product_reviews.is_buyer = ?s', YesNo::YES);
        } elseif (!empty($params['is_buyer'])) {
            $condition .= db_quote(' AND ?:product_reviews.is_buyer = ?s', $params['is_buyer']);
        }

        if (!empty($params['company_id'])) {
            $condition .= db_quote(' AND ?:products.company_id IN (?n)', $params['company_id']);
            $join .= db_quote(' LEFT JOIN ?:products ON ?:product_reviews.product_id = ?:products.product_id');
        }

        if (!empty($params['storefront_id'])) {
            $condition .= db_quote(' AND ?:product_reviews.storefront_id IN (?n)', $params['storefront_id']);
        }

        if (!empty($params['with_images'])) {
            $params['has_images'] = true;
        }

        if (isset($params['has_images']) && $params['has_images'] !== '') {
            if ($params['has_images']) {
                $condition .= db_quote(' AND ?:product_reviews.images_count > 0');
            } else {
                $condition .= db_quote(' AND ?:product_reviews.images_count = 0');
            }
        }

        if (!empty($params['period']) && $params['period'] !== 'A') {
            list($params['time_from'], $params['time_to']) = fn_create_periods($params);
            $condition .= db_quote(' AND (?:product_reviews.product_review_timestamp >= ?i AND ?:product_reviews.product_review_timestamp <= ?i)', $params['time_from'], $params['time_to']);
        }

        $join .= db_quote(
            ' LEFT JOIN ?:users'
                . ' ON ?:product_reviews.reply_user_id = ?:users.user_id'
            . ' LEFT JOIN ?:companies'
                . ' ON ?:users.company_id = ?:companies.company_id'
            . ' LEFT JOIN ?:country_descriptions'
                . ' ON ?:product_reviews.country_code = ?:country_descriptions.code'
                    . ' AND ?:country_descriptions.lang_code = ?s',
            $lang_code
        );

        $limit = '';
        if (!empty($params['items_per_page'])) {
            // FIXME must be COUNT(*)
            $params['total_items'] = count(db_get_fields('SELECT product_review_id FROM ?:product_reviews ?p WHERE 1 ?p GROUP BY ?:product_reviews.product_review_id', $join, $condition));
            $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
        }

        $order_by = db_sort($params, $sortings, 'product_review_timestamp', 'desc');

        $product_reviews = db_get_hash_array(
            'SELECT ?p'
            . ' FROM ?:product_reviews'
                . ' ?p'
            . ' WHERE 1 ?p'
            . ' ?p ?p',
            'product_review_id',
            implode(',', $fields),
            $join,
            $condition,
            $order_by,
            $limit
        );

        if (!empty($params['load_product_data']) && $product_reviews) {
            $product_ids = array_unique(array_column($product_reviews, 'product_id'));

            list($products, ) = fn_get_products(['pid' => $product_ids]);
            fn_gather_additional_products_data($products, ['get_detailed' => true]);
        }

        $images = $this->images_service->getImagePairs(array_keys($product_reviews));

        foreach ($product_reviews as $product_review_id => &$product_review) {
            if (isset($product_review['ip_address'])) {
                $product_review['ip_address'] = fn_ip_from_db($product_review['ip_address']);
            }

            $product_review['images'] = empty($images[$product_review_id]) ? [] : $images[$product_review_id];

            $product_review['product'] = [
                'product_id' => $product_review['product_id']
            ];

            if (isset($products[$product_review['product_id']])) {
                $product_review['product'] = array_merge($product_review['product'], $products[$product_review['product_id']]);
            }
        }
        unset($product_review);

        $product_reviews = $this->transformReviewsStructure($product_reviews);

        /** @var SearchParamsType $params */
        return [$product_reviews, $params];
    }

    /**
     * @param array  $params    Search and sort parameters
     * @param string $lang_code Two-letter language code (e.g. 'en', 'ru', etc.)
     *
     * @psalm-param RequestParamsType $params
     *
     * @return ProductReviewType|null
     */
    public function findOne(array $params, $lang_code = CART_LANGUAGE)
    {
        list($reviews) = $this->find($params, $lang_code);

        return $reviews ? reset($reviews) : null;
    }

    /**
     * @param array<string, string|int|null> $product_review_data Product review data
     *
     * @return int|bool $product_review_id Product review identifier
     */
    public function create(array $product_review_data)
    {
        /**
         * Executes before the review is created, allowing you to modify the arguments passed to the method.
         *
         * @param array<string, string|int|null> $product_review_data Product review data
         */
        fn_set_hook('product_reviews_create_pre', $product_review_data);

        $product_review_id = db_replace_into('product_reviews', $product_review_data);

        if (YesNo::toBool(empty($this->settings['images_upload_allowed']) ? YesNo::YES : $this->settings['images_upload_allowed'])) {
            $this->images_service->attachImages($product_review_id);

            $this->product_reviews_service->actualizeProductReviewImagesCount($product_review_id);
        }

        /**
         * Executes after the review is created, allows working with created product review.
         *
         * @param array<string, string|int|null> $product_review_data Product review data
         * @param int|false                      $product_review_id   Created review identificator
         */
        fn_set_hook('product_reviews_create_post', $product_review_data, $product_review_id);

        return $product_review_id;
    }

    /**
     * @param int                     $product_review_id   Product review identifier
     * @param array<string|int|int[]> $product_review_data Product review data
     *
     * @psalm-suppress InvalidScalarArgument
     *
     * @return int|void
     */
    public function update($product_review_id, array $product_review_data)
    {
        if (!$product_review_id) {
            return;
        }

        $new_product_review_data = [
            'product_review_id' => $product_review_id,
        ];

        if (isset($product_review_data['advantages'])) {
            $new_product_review_data['advantages'] = $product_review_data['advantages'];
        }

        if (isset($product_review_data['disadvantages'])) {
            $new_product_review_data['disadvantages'] = $product_review_data['disadvantages'];
        }

        if (isset($product_review_data['comment'])) {
            $new_product_review_data['comment'] = $product_review_data['comment'];
        }

        if (!empty($product_review_data['delete_images'])) {
            $this->images_service->deleteImagePairs($product_review_data['delete_images']);
        }

        return db_replace_into('product_reviews', $new_product_review_data);
    }

    /**
     * @param int|array<int> $product_review_ids Product reviews identifiers
     *
     * @return void
     */
    public function delete($product_review_ids)
    {
        if (!$product_review_ids) {
            return;
        }

        $affected_product_ids = $this->product_reviews_service->getProductIdsByProductReviewIds((array) $product_review_ids);

        db_query(
            'DELETE product_reviews, votes'
                . ' FROM ?:product_reviews product_reviews'
                    . ' LEFT JOIN ?:product_review_votes votes'
                        . ' ON product_reviews.product_review_id = votes.product_review_id'
            . ' WHERE product_reviews.product_review_id IN (?n)',
            $product_review_ids
        );

        foreach ((array) $product_review_ids as $product_review_id) {
            $this->images_service->deleteImagePairsByProductReviewId($product_review_id);
        }

        foreach ($affected_product_ids as $product_id) {
            $this->product_reviews_service->actualizeProductPreparedData($product_id);
        }
    }

    /**
     * @param int               $product_review_id Product review identifier
     * @param array<string|int> $reply_data        Reply data
     *
     * @return void
     */
    public function updateReply($product_review_id, $reply_data)
    {
        if (!$product_review_id) {
            return;
        }

        if (
            empty($reply_data['reply_user_id'])
            || empty($reply_data['reply'])
        ) {
            //delete reply
            $reply_data = [
                'product_review_id' => $product_review_id,
                'reply_user_id'     => 0,
                'reply'             => null,
                'reply_timestamp'   => 0,
            ];
        } else {
            //update reply
            $reply_data = [
                'product_review_id' => $product_review_id,
                'reply_user_id'     => $reply_data['reply_user_id'],
                'reply'             => $reply_data['reply'],
                'reply_timestamp'   => TIME,
            ];
        }

        db_replace_into('product_reviews', $reply_data);
    }

    /**
     * Gets product reviews sorting
     *
     * @return array<string, array<string, string>>
     */
    public function getSorting()
    {
        return [
            'product_review_timestamp'  => ['default_order' => 'desc'],
            'helpfulness'               => ['default_order' => 'asc'],
            'rating_value'              => ['default_order' => 'asc'],
        ];
    }

    /**
     * Gets available sorts
     *
     * @return array<string, string>
     */
    public function getAvailableSorts()
    {
        return [
            'product_review_timestamp-asc'  => YesNo::YES,
            'product_review_timestamp-desc' => YesNo::YES,
            'helpfulness-asc'               => YesNo::YES,
            'helpfulness-desc'              => YesNo::YES,
            'rating_value-asc'              => YesNo::YES,
            'rating_value-desc'             => YesNo::YES,
        ];
    }

    /**
     * @param array<string|int|array<int>> $params Search parameters
     *
     * @return int
     */
    public function getTotalByParams(array $params)
    {
        $condition = '';

        if (!empty($params['product_id'])) {
            $condition .= db_quote(' AND ?:product_reviews.product_id IN (?n)', $params['product_id']);
        }

        return (int) db_get_field('SELECT COUNT(?:product_reviews.product_review_id) FROM ?:product_reviews WHERE 1 ?p', $condition);
    }

    /**
     * Transforms input data to needed structure
     *
     * @param array $reviews Array to transform
     *
     * @psalm-param array<array{
     *  product_review_id: int|string,
     *  product_id: int,
     *  user_id: int|string,
     *  name: string,
     *  advantages: string,
     *  disadvantages: string,
     *  comment: string,
     *  rating_value: int,
     *  ip_address?: string,
     *  is_buyer: string,
     *  review_timestamp: int,
     *  country_code: string,
     *  country?: string,
     *  city?: string,
     *  reply_user_id: int,
     *  reply: string,
     *  reply_timestamp: int,
     *  helpfulness: int|string,
     *  status: string,
     *  storefront_id: int,
     *  reply_company_id: int|null,
     *  reply_company: string|null,
     *  country: string,
     *  vote_up: int|string,
     *  vote_down: int|string
     * }> $reviews
     *
     * @return array<ProductReviewType>
     */
    private function transformReviewsStructure(array $reviews)
    {
        foreach ($reviews as &$review) {
            $review['product_review_id'] = (int) $review['product_review_id'];
            $user_id = (int) $review['user_id'];
            $name    = $review['name'];

            $structure = [
                'user_data' => [
                    'user_id'       => $user_id,
                    'name'          => $name,
                    'ip_address'    => isset($review['ip_address']) ? $review['ip_address'] : '',
                    'is_buyer'      => $review['is_buyer'],
                    'country_code'  => $review['country_code'],
                    'country'       => isset($review['country']) ? $review['country'] : '',
                    'city'          => isset($review['city']) ? $review['city'] : '',
                    'is_anon'       => $user_id === 0 && empty($name),
                    'is_authorized' => $user_id !== 0
                ],
                'message' => [
                    'advantages'    => $review['advantages'],
                    'disadvantages' => $review['disadvantages'],
                    'comment'       => $review['comment']
                ],
                'helpfulness' => [
                    'helpfulness' => (int) $review['helpfulness'],
                    'vote_up'     => (int) $review['vote_up'],
                    'vote_down'   => (int) $review['vote_down']
                ]
            ];

            if (!empty($review['reply'])) {
                $structure['reply'] = [
                    'reply_user_id'    => $review['reply_user_id'],
                    'reply'            => $review['reply'],
                    'reply_timestamp'  => $review['reply_timestamp'],
                    'reply_company'    => $review['reply_company'],
                    'reply_company_id' => $review['reply_company_id'],
                ];
            } else {
                $structure['reply'] = [];
            }

            array_walk_recursive($structure, static function ($value, $key) use (&$review) {
                if (!isset($review[$key])) {
                    return;
                }
                unset($review[$key]);
            });

            $review = array_merge($review, $structure);
        }
        unset($review);

        /** @var array<ProductReviewType> $reviews */
        return $reviews;
    }
}
