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

namespace Tygh\Addons\ProductReviews;

use Tygh\Addons\ProductReviews\ProductReview\ImagesService;
use Tygh\Common\OperationResult;
use Tygh\Database\Connection;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Enum\YesNo;
use Tygh\Registry;
use Tygh\Addons\ProductReviews\Notifications\EventIdProviders\ProductReviewsEventProvider;
use Tygh\Addons\ProductReviews\ServiceProvider as ProductReviewsProvider;
use Tygh\Providers\StorefrontProvider;
use Tygh\Tygh;

class Service
{
    /**
     * @var Connection
     */
    protected $db;

    /**
     * @param Connection $db Instance of database connection.
     */
    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * @param int   $product_id     Product identifier
     * @param int[] $storefront_ids Storefront identifiers
     *
     * @return void
     */
    public function actualizeProductPreparedData($product_id, array $storefront_ids = [])
    {
        if (!$product_id) {
            return;
        }
        if (!$storefront_ids) {
            /** @var \Tygh\Storefront\Storefront[] $storefronts */
            list($storefronts, ) = StorefrontProvider::getRepository()->find(['cache' => true, 'get_total' => false]);
            $storefront_ids = array_map(
                static function ($storefront) {
                    return $storefront->storefront_id;
                },
                $storefronts
            );
        }

        /**
         * @param int   $product_id     Product identifier
         * @param int[] $storefront_ids Storefront identifiers
         */
        fn_set_hook('product_reviews_actualize_product_prepared_data_pre', $product_id, $storefront_ids);

        $this->db->query('DELETE FROM ?:product_review_prepared_data WHERE product_id = ?i', $product_id);

        $select_query = $this->db->quote(
            'SELECT reviews.product_id, reviews.storefront_id, AVG(reviews.rating_value), COUNT(reviews.product_review_id)'
            . ' FROM ?:product_reviews reviews'
            . ' WHERE product_id = ?i'
                . ' AND storefront_id IN (?n)'
                . ' AND status = ?s'
            . ' GROUP BY storefront_id',
            $product_id,
            $storefront_ids,
            ObjectStatuses::ACTIVE
        );

        $this->db->replaceSelectionInto(
            'product_review_prepared_data',
            ['product_id', 'storefront_id', 'average_rating', 'reviews_count'],
            $select_query
        );

        // for all storefronts
        $select_query = $this->db->quote(
            'SELECT reviews.product_id, 0, AVG(reviews.rating_value), COUNT(reviews.product_review_id)'
            . ' FROM ?:product_reviews reviews'
            . ' WHERE product_id = ?i'
                . ' AND status = ?s',
            $product_id,
            ObjectStatuses::ACTIVE
        );

        $this->db->replaceSelectionInto(
            'product_review_prepared_data',
            ['product_id', 'storefront_id', 'average_rating', 'reviews_count'],
            $select_query
        );

        /**
         * @param int   $product_id     Product identifier
         * @param int[] $storefront_ids Storefront identifiers
         */
        fn_set_hook('product_reviews_actualize_product_prepared_data_post', $product_id, $storefront_ids);
    }

    /**
     * @param int $product_review_id Product review identifier
     *
     * @return void
     */
    public function actualizeProductReviewHelpfulness($product_review_id)
    {
        $this->db->query(
            'UPDATE ?:product_reviews product_reviews,'
                . ' ('
                    . 'SELECT COALESCE(SUM(value), 0) helpfulness,'
                        . ' SUM(value > 0) vote_up,'
                        . ' SUM(value < 0) vote_down'
                    . ' FROM ?:product_review_votes'
                    . ' WHERE product_review_id = ?i'
                . ') source'
            . ' SET product_reviews.helpfulness = source.helpfulness,'
                . ' product_reviews.vote_up = source.vote_up,'
                . ' product_reviews.vote_down = source.vote_down'
            . ' WHERE product_review_id = ?i',
            $product_review_id,
            $product_review_id
        );
    }

    /**
     * @param int $product_id Product identifier
     *
     * @return void
     */
    public function deleteProductReviewsByProductId($product_id)
    {
        if (!$product_id) {
            return;
        }

        $product_review_ids = $this->db->getColumn('SELECT product_review_id FROM ?:product_reviews WHERE product_id = ?i', $product_id);

        /**
         * Executes when deleting reviews by product_id.
         *
         * @param int   $product_id         Product identifier
         * @param int[] $product_review_ids Product reviews identifiers
         */
        fn_set_hook('product_reviews_delete_reviews_by_product_id', $product_id, $product_review_ids);

        $product_reviews_repository = ServiceProvider::getProductReviewRepository();
        $product_reviews_repository->delete($product_review_ids);
    }

    /**
     * @param array<string|int> $vote_data User vote data
     *
     * @return void|bool
     */
    public function addVote($vote_data)
    {
        if (
            empty($vote_data['product_review_id'])
            || !isset($vote_data['user_id'])
            || empty($vote_data['ip_address'])
            || empty($vote_data['value'])
        ) {
            return false;
        }

        $vote_data['ip_address'] = fn_ip_to_db((string) $vote_data['ip_address']);
        $vote_data['timestamp'] = TIME;
        $result = (bool) $this->db->replaceInto('product_review_votes', $vote_data);

        if (!$result) {
            return false;
        }

        $this->actualizeProductReviewHelpfulness((int) $vote_data['product_review_id']);

        return $result;
    }

    /**
     * @param int $product_id    Product identifier
     * @param int $storefront_id Storefront identifier
     *
     * @return void|array{ratings?: array<array{count: int, percentage?: float}>, total: int}
     */
    public function getProductRatingStats($product_id, $storefront_id = 0)
    {
        if (!$product_id) {
            return;
        }

        $ratings = fn_product_reviews_get_reviews_ratings();
        $result = [];
        $where = [
            'product_id' => $product_id,
            'status'     => ObjectStatuses::ACTIVE
        ];

        if ($storefront_id) {
            $where['storefront_id'] = $storefront_id;
        }

        /**
         * Executes before getting ratings data from the database.
         *
         * @param int                       $product_id    Product identifier
         * @param int                       $storefront_id Storefront identifier
         * @param array<string>             $ratings       Rating values list
         * @param array<string, string|int> $where         Array for sql query conditions
         */
        fn_set_hook('product_reviews_get_product_ratings_stats', $product_id, $storefront_id, $ratings, $where);

        $ratings_data = $this->db->getHash(
            'SELECT rating_value, COUNT(1) count'
            . ' FROM ?:product_reviews'
            . ' WHERE ?w'
            . ' GROUP BY rating_value',
            'rating_value',
            $where
        );

        ksort($ratings_data);

        $total = (int) array_sum(array_column($ratings_data, 'count'));

        foreach (array_keys($ratings) as $rating) {
            $rating_count = isset($ratings_data[$rating]) ? $ratings_data[$rating]['count'] : 0;

            $result['ratings'][$rating]['count'] = (int) $rating_count;
            $result['ratings'][$rating]['percentage'] = (float) number_format($rating_count / ($total ?: 1) * 100, 2);
        }

        $result['total'] = $total;

        return $result;
    }

    /**
     * @param int $product_review_id Product review identifier
     *
     * @return int
     */
    public function getProductIdByProductReviewId($product_review_id)
    {
        return (int) $this->db->getField('SELECT product_id FROM ?:product_reviews WHERE product_review_id = ?i', $product_review_id);
    }

    /**
     * @param int[] $product_review_ids Product review IDs
     *
     * @return int[]
     */
    public function getProductIdsByProductReviewIds(array $product_review_ids)
    {
        $ids = $this->db->getColumn('SELECT product_id FROM ?:product_reviews WHERE product_review_id IN (?n)', $product_review_ids);

        return array_map('intval', $ids);
    }

    /**
     * @param int         $user_id    User identifier
     * @param int         $product_id Product identifier
     * @param string|null $ip         IP address by fn_ip_to_db
     *
     * @return OperationResult
     */
    public function isUserEligibleToWriteProductReview($user_id, $product_id, $ip)
    {
        $result = new OperationResult(true);

        $need_to_buy_first = YesNo::toBool(Registry::get('addons.product_reviews.review_after_purchase'));
        $review_ip_check = YesNo::toBool(Registry::get('addons.product_reviews.review_ip_check'));

        if (
            $need_to_buy_first
            && !$this->isBuyer($user_id, $product_id)
        ) {
            $result->setSuccess(false);
            $result->addError('product_reviews.need_to_buy_first', __('product_reviews.you_have_to_buy_product_before_writing_review'));
        }

        if (
            $review_ip_check
            && $this->isUserAlreadyPostedProductReviewFromIp($ip, $product_id)
        ) {
            $result->setSuccess(false);
            $result->addError('product_reviews.review_already_posted_from_ip', __('product_reviews.error_already_posted'));
        }

        /**
         * Executes after check is user eligible to write review, allowing you to modify the arguments passed to the method.
         *
         * @param int             $user_id           User identifier
         * @param int             $product_id        Product identifier
         * @param string|null     $ip                IP address by fn_ip_to_db
         * @param bool            $need_to_buy_first State of the review_after_purchase setting
         * @param bool            $review_ip_check   State of the review_ip_check setting
         * @param OperationResult $result            Operation result
         */
        fn_set_hook('product_reviews_is_user_eligible_to_write_product_review', $user_id, $product_id, $ip, $need_to_buy_first, $review_ip_check, $result);

        return $result;
    }

    /**
     * @param int $user_id    User identifier
     * @param int $product_id Product identifier
     *
     * @return bool
     */
    public function isBuyer($user_id, $product_id)
    {
        return $user_id && $product_id && (bool) $this->db->getField(
            'SELECT orders.order_id FROM ?:orders AS orders '
                . 'LEFT JOIN ?:order_details AS details ON orders.order_id = details.order_id '
            . 'WHERE orders.user_id = ?i AND details.product_id = ?i LIMIT 1',
            $user_id,
            $product_id
        );
    }

    /**
     * @param array<string|int> $auth              Data about the current user
     * @param int               $product_review_id Product review identifier
     *
     * @return bool
     */
    public function isAllowUserUpdateReply($auth, $product_review_id)
    {
        if (
            !$auth
            || !$product_review_id
            || empty($auth['user_id'])
            || empty($auth['user_type'])
        ) {
            return false;
        }

        if (UserTypes::isAdmin((string) $auth['user_type'])) {
            return true;
        }

        if (UserTypes::isVendor((string) $auth['user_type'])) {
            if (!SiteArea::isStorefront(AREA)) {
                $product_reviews_repository = ProductReviewsProvider::getProductReviewRepository();
                $product_data = $product_reviews_repository->findOne([
                    'product_review_id' => $product_review_id,
                    'company_id' => (int) $auth['company_id']
                ]);

                if (!$product_data) {
                    return false;
                }
            }

            $reply_company_id = $this->db->getField(
                'SELECT ?:users.company_id'
                . ' FROM ?:product_reviews'
                    . ' LEFT JOIN ?:users'
                        . ' ON ?:product_reviews.reply_user_id = ?:users.user_id'
                . ' WHERE ?:product_reviews.product_review_id = ?i',
                $product_review_id
            );

            return ($reply_company_id === null || (int) $reply_company_id === (int) $auth['company_id']);
        }

        return false;
    }

    /**
     * @param string|null $ip         IP address by fn_ip_to_db
     * @param int         $product_id Product identifier
     *
     * @return bool
     */
    public function isUserAlreadyPostedProductReviewFromIp($ip, $product_id)
    {
        $is_posted = false;

        if ($ip && $product_id) {
            $is_exists = $this->db->getField(
                'SELECT COUNT(product_review_id) FROM ?:product_reviews WHERE product_id = ?i AND ip_address = ?s',
                $product_id,
                $ip
            );

            $is_posted = !empty($is_exists);
        }

        return $is_posted;
    }

    /**
     * @param int $product_review_id Product review identifier
     *
     * @return bool
     */
    public function isAllowedToAddReply($product_review_id)
    {
        $reply_company_id = $this->db->getField(
            'SELECT ?:users.company_id'
            . ' FROM ?:product_reviews'
                . ' LEFT JOIN ?:users'
                    . ' ON ?:product_reviews.reply_user_id = ?:users.user_id'
            . ' WHERE ?:product_reviews.product_review_id = ?i',
            $product_review_id
        );

        if (
            $reply_company_id === null
            || $reply_company_id === Registry::get('runtime.company_id')
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param array<string|int> $raw_product_review_data Product review data
     * @param array<string|int> $auth                    Data about the current user
     *
     * @return array<string, string|int|null> $product_review_data
     */
    public function prepareReviewData(array $raw_product_review_data, $auth)
    {
        $product_review_data = [
            'status'                   => ObjectStatuses::ACTIVE,
            'product_review_timestamp' => TIME,
            'product_id'               => empty($raw_product_review_data['product_id']) ? 0 : $raw_product_review_data['product_id'],
            'name'                     => empty($raw_product_review_data['name']) ? '' : $raw_product_review_data['name'],
            'advantages'               => empty($raw_product_review_data['advantages']) ? '' : $raw_product_review_data['advantages'],
            'disadvantages'            => empty($raw_product_review_data['disadvantages']) ? '' : $raw_product_review_data['disadvantages'],
            'comment'                  => empty($raw_product_review_data['comment']) ? '' : $raw_product_review_data['comment'],
            'rating_value'             => empty($raw_product_review_data['rating_value']) ? 0 : $raw_product_review_data['rating_value'],
            'country_code'             => empty($raw_product_review_data['country_code']) ? '' : $raw_product_review_data['country_code'],
            'city'                     => empty($raw_product_review_data['city']) ? '' : $raw_product_review_data['city'],
        ];

        if (
            $auth['user_id']
            && $user_data = fn_get_user_info((int) $auth['user_id'])
        ) {
            $product_review_data['country_code'] = $product_review_data['country_code'] ?: $user_data['s_country'];
            $product_review_data['city'] = $product_review_data['city'] ?: $user_data['s_city'];
            $product_review_data['name'] = $product_review_data['name'] ?: $user_data['firstname'] . ' ' . $user_data['lastname'];
        }

        if (
            isset($raw_product_review_data['is_anon'])
            && YesNo::toBool($raw_product_review_data['is_anon'])
        ) {
            $product_review_data['name'] = '';
            $product_review_data['user_id'] = 0;
        } else {
            $product_review_data['user_id'] = empty($auth['user_id']) ? 0 : $auth['user_id'];
        }

        $product_review_data['is_buyer'] = YesNo::toId($this->isBuyer((int) $product_review_data['user_id'], (int) $product_review_data['product_id']));

        if (!empty($auth['ip'])) {
            $product_review_data['ip_address'] = fn_ip_to_db((string) $auth['ip']);
        } else {
            $ip = fn_get_ip();
            $product_review_data['ip_address'] = fn_ip_to_db($ip['host']);
        }

        return $product_review_data;
    }

    /**
     * @param int           $product_review_id Product review identifier
     * @param array<string> $reply_data        Product review data
     *
     * @return bool
     */
    public function isAddReply($product_review_id, array $reply_data)
    {
        $old_reply = $this->db->getField('SELECT reply FROM ?:product_reviews WHERE product_review_id = ?i', $product_review_id);

        return !$old_reply && !empty($reply_data['reply']);
    }

    /**
     * Creates a product review and returns operation result.
     *
     * @param array<int|string>     $raw_product_review_data Product review data
     * @param array<string, string> $auth                    Data about the current user
     *
     * @return \Tygh\Common\OperationResult $result Operation result
     */
    public function createProductReview(array $raw_product_review_data, array $auth)
    {
        $result = new OperationResult(true);
        $product_reviews_repository = ProductReviewsProvider::getProductReviewRepository();

        $product_review_data = $this->prepareReviewData($raw_product_review_data, $auth);

        if (empty($product_review_data['product_id'])) {
            $result->setSuccess(false);
            $result->addError('reqired_field', __('api_required_field', ['[field]' => 'product_id']));

            return $result;
        } else {
            /** @var \Tygh\Common\OperationResult $_result */
            $_result = $this->isUserEligibleToWriteProductReview(
                (int) $auth['user_id'],
                (int) $product_review_data['product_id'],
                (string) $product_review_data['ip_address']
            );

            if ($_result->isFailure()) {
                $result->setSuccess(false);
                $result->setErrors($_result->getErrors());
                return $result;
            }
        }

        $product_review_data['storefront_id'] = StorefrontProvider::getStorefront()->storefront_id;

        $product_review_approval = Registry::ifGet('addons.product_reviews.review_approval', 'any');
        if (
            (
                $product_review_approval === 'any'
                || (
                    $product_review_approval === 'anonymous'
                    && empty($product_review_data['user_id'])
                )
            )
            && !UserTypes::isAdmin($auth['user_type'])
        ) {
            $product_review_data['status'] = ObjectStatuses::DISABLED;
        }

        $product_review_id = $product_reviews_repository->create($product_review_data);
        $result->setData($product_review_id, 'product_review_id');

        if (!empty($product_review_data['product_id'])) {
            $this->actualizeProductPreparedData((int) $product_review_data['product_id']);
        }

        if (
            $product_review_id
            && (
                empty($product_review_data['status'])
                || $product_review_data['status'] !== ObjectStatuses::ACTIVE
            )
        ) {
            $result->addMessage('review_pended', __('product_reviews.text_thank_you_for_review') . '. ' . __('product_reviews.text_review_pended'));
        }

        $receivers = [
            UserTypes::ADMIN => true,
        ];

        if (fn_allowed_for('MULTIVENDOR')) {
            $receivers[UserTypes::VENDOR] = true;
        }

        /** @var \Tygh\Notifications\Settings\Factory $notification_settings_factory */
        $notification_settings_factory = Tygh::$app['event.notification_settings.factory'];
        $notification_rules = $notification_settings_factory->create($receivers);

        /** @var \Tygh\Notifications\EventDispatcher $event_dispatcher */
        $event_dispatcher = Tygh::$app['event.dispatcher'];
        $event_dispatcher->dispatch(
            'product_reviews.new_post',
            fn_product_reviews_get_data_for_notification((int) $product_review_id, $auth),
            $notification_rules,
            new ProductReviewsEventProvider((int) $product_review_id)
        );

        return $result;
    }

    /**
     * @param int $product_review_id Product review ID
     *
     * @return void
     */
    public function actualizeProductReviewImagesCount($product_review_id)
    {
        if (!$product_review_id) {
            return;
        }

        $this->db->query(
            'UPDATE ?:product_reviews'
            . ' SET images_count = ('
                . 'SELECT COUNT(1) FROM ?:images_links WHERE object_type = ?s AND object_id = ?i'
            . ')'
            . ' WHERE product_review_id = ?i',
            ImagesService::OBJECT_TYPE,
            $product_review_id,
            $product_review_id
        );
    }

    /**
     * Gets reply user ID by product review ID
     *
     * @param int $product_review_id Product review ID
     *
     * @return int
     */
    public function getReplyUserId($product_review_id)
    {
        return (int) $this->db->getField(
            'SELECT reply_user_id FROM ?:product_reviews WHERE product_review_id = ?i',
            $product_review_id
        );
    }
}
