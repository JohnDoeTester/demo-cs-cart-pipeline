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

use Tygh\Addons\ProductReviews\Notifications\EventIdProviders\ProductReviewsEventProvider;
use Tygh\Addons\ProductReviews\ServiceProvider as ProductReviewsProvider;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\UserTypes;
use Tygh\Enum\YesNo;
use Tygh\Enum\Addons\ProductReviews\ProductReviewsMessageTypes;
use Tygh\Http;
use Tygh\Providers\StorefrontProvider;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

$auth = & Tygh::$app['session']['auth'];
$service = ProductReviewsProvider::getService();
$product_reviews_repository = ProductReviewsProvider::getProductReviewRepository();

if (
    fn_allowed_for('MULTIVENDOR:ULTIMATE')
    && !empty($auth['storefront_id'])
) {
    $storefront_id = $auth['storefront_id'];
} else {
    $storefront_id = empty($_REQUEST['storefront_id'])
        ? 0
        : (int) $_REQUEST['storefront_id'];
}

if (fn_allowed_for('ULTIMATE')) {
    $storefront_id = 0;
    if (fn_get_runtime_company_id()) {
        $storefront_id = StorefrontProvider::getStorefront()->storefront_id;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        $mode === 'delete'
        && !empty($_REQUEST['product_review_id'])
    ) {
        $product_reviews_repository->delete((int) $_REQUEST['product_review_id']);
    }

    if (
        $mode === 'm_delete'
        && !empty($_REQUEST['reviews_ids'])
        && is_array($_REQUEST['reviews_ids'])
    ) {
        $product_reviews_repository->delete($_REQUEST['reviews_ids']);

        $redirect_url = fn_url('product_reviews.manage');
        if (isset($_REQUEST['redirect_url'])) {
            $redirect_url = $_REQUEST['redirect_url'];
            $redirect_url .= (parse_url($redirect_url, PHP_URL_QUERY) ? '&' : '?') . 'selected_section=product_reviews';
        }
        unset($_REQUEST['redirect_url']);

        return [CONTROLLER_STATUS_OK, $redirect_url];
    }

    if (
        $mode === 'update'
        && !empty($_REQUEST['product_review_data']['product_review_id'])
    ) {
        $product_review_data = $_REQUEST['product_review_data'];
        $is_add_reply = $service->isAddReply($product_review_data['product_review_id'], $product_review_data);
        $product_review_data['reply_user_id'] = $is_add_reply
            ? $auth['user_id']
            : $service->getReplyUserId($product_review_data['product_review_id']);

        if (UserTypes::isAdmin($auth['user_type'])) {
            $product_reviews_repository->update($product_review_data['product_review_id'], $product_review_data);
        }

        if ($service->isAllowUserUpdateReply($auth, $product_review_data['product_review_id'])) {
            $product_reviews_repository->updateReply($product_review_data['product_review_id'], $product_review_data);
        }

        $product_review_data = $product_reviews_repository->findById($product_review_data['product_review_id']);

        if (
            !$is_add_reply
            || empty($product_review_data['user_data']['user_id'])
        ) {
            return [CONTROLLER_STATUS_OK];
        }

        $receivers = [
            UserTypes::CUSTOMER => true,
        ];

        /** @var \Tygh\Notifications\Settings\Factory $notification_settings_factory */
        $notification_settings_factory = Tygh::$app['event.notification_settings.factory'];
        $notification_rules = $notification_settings_factory->create($receivers);

        /** @var \Tygh\Notifications\EventDispatcher $event_dispatcher */
        $event_dispatcher = Tygh::$app['event.dispatcher'];
        $event_dispatcher->dispatch(
            'product_reviews.new_reply',
            fn_product_reviews_get_data_for_notification($product_review_data['product_review_id'], $auth),
            $notification_rules,
            new ProductReviewsEventProvider($product_review_data['product_review_id'])
        );
    }

    if ($mode === 'copy_from_discussion') {
        fn_product_reviews_copy_reviews_from_discussion();

        return [CONTROLLER_STATUS_OK, 'product_reviews.manage'];
    }

    if (
        $mode === 'm_update_statuses'
        && !empty($_REQUEST['reviews_ids'])
        && !empty($_REQUEST['status'])
    ) {
        $status_to = $_REQUEST['status'];

        foreach ((array) $_REQUEST['reviews_ids'] as $reviews_id) {
            fn_tools_update_status(
                [
                    'table'             => 'product_reviews',
                    'status'            => $status_to,
                    'id_name'           => 'product_review_id',
                    'id'                => $reviews_id,
                    'show_error_notice' => false,
                ]
            );
        }

        if (defined('AJAX_REQUEST')) {
            $redirect_url = fn_url('product_reviews.manage');
            if (isset($_REQUEST['redirect_url'])) {
                $redirect_url = $_REQUEST['redirect_url'];
                $redirect_url .= (parse_url($redirect_url, PHP_URL_QUERY) ? '&' : '?') . 'selected_section=product_reviews';
            }
            Tygh::$app['ajax']->assign('force_redirection', $redirect_url);
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            return [CONTROLLER_STATUS_NO_CONTENT];
        }
    }

    if (
        $mode === 'add'
        && !empty($_REQUEST['product_review_data'])
    ) {
        /** @var \Tygh\Common\OperationResult $result */
        $result = $service->createProductReview($_REQUEST['product_review_data'], $auth);

        $result->showNotifications();

        if ($result->isFailure()) {
            return [CONTROLLER_STATUS_DENIED];
        }
    }

    return [CONTROLLER_STATUS_OK];
}

if ($mode === 'manage') {
    $split_reviews_by_storefronts = YesNo::toBool(Registry::ifGet('addons.product_reviews.split_reviews_by_storefronts', YesNo::NO));
    $params = $_REQUEST;

    if ($split_reviews_by_storefronts) {
        $params['storefront_id'] = $storefront_id;
    } else {
        unset($params['storefront_id']);
    }

    $params['items_per_page'] = empty($params['items_per_page'])
        ? (int) Registry::get('addons.product_reviews.reviews_per_page')
        : (int) $params['items_per_page'];

    if (UserTypes::isVendor($auth['user_type'])) {
        $params['company_id'] = (int) Registry::get('runtime.company_id');
    }

    $params = array_merge($params, [
        'load_product_data' => true
    ]);

    list($product_reviews, $search) = $product_reviews_repository->find($params);

    Tygh::$app['view']->assign([
        'is_allowed_to_update_product_reviews' => fn_check_view_permissions('product_reviews.update', Http::POST),
        'is_allowed_to_delete_product_reviews' => fn_check_view_permissions('product_reviews.delete', Http::POST),
        'product_reviews'                      => $product_reviews,
        'product_reviews_search'               => $search,
        'selected_storefront_id'               => $storefront_id,
        'select_storefront'                    => $split_reviews_by_storefronts,
        'available_message_types'              => ProductReviewsMessageTypes::getTypes(Registry::get('addons.product_reviews.review_fields')),
        'product_review_status_descr'          => fn_product_reviews_get_statuses_descriptions()
    ]);
} elseif (
    $mode === 'update'
    && !empty($_REQUEST['product_review_id'])
) {
    /** @pslam-var ProductReviewType $product_review */
    $product_review = $product_reviews_repository->findOne([
        'product_review_id' => (int) $_REQUEST['product_review_id'],
        'load_product_data' => true
    ]);

    if (
        !$product_review
        || (
            isset($product_review['storefront_id'])
            && !fn_check_permission_storefronts($product_review['storefront_id'])
        )
    ) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    if (UserTypes::isVendor($auth['user_type'])) {
        $product_owner_company_id = fn_get_company_id('products', 'product_id', $product_review['product']['product_id']);

        if ((int) Registry::get('runtime.company_id') !== (int) $product_owner_company_id) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }
    }

    $user_data = fn_get_user_info($product_review['user_data']['user_id']);
    $total_product_reviews = $product_reviews_repository->getTotalByParams(['product_id' => $product_review['product']['product_id']]);

    $is_allowed_to_update_product_reviews = fn_check_view_permissions('product_reviews.update', Http::POST);
    $is_allowed_update_reply = $service->isAllowUserUpdateReply($auth, $product_review['product_review_id']) && $is_allowed_to_update_product_reviews;

    Tygh::$app['view']->assign([
        'product_review'                       => $product_review,
        'user_data'                            => $user_data,
        'is_allowed_to_update_product_reviews' => $is_allowed_to_update_product_reviews,
        'is_allowed_update_reply'              => $is_allowed_update_reply,
        'total_product_reviews'                => $total_product_reviews,
        'available_message_types'              => ProductReviewsMessageTypes::getTypes(Registry::get('addons.product_reviews.review_fields')),
        'product_review_status_descr'          => fn_product_reviews_get_statuses_descriptions(),
    ]);
} elseif ($mode === 'add') {
    if (empty($_REQUEST['product_id'])) {
        return [CONTROLLER_STATUS_DENIED];
    }

    $product_id = (int) $_REQUEST['product_id'];
    $product_data = fn_get_product_data($product_id);
    if (!$product_data) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    if (!empty($auth['ip'])) {
        $ip = fn_ip_to_db((string) $auth['ip']);
    } else {
        $ip = fn_get_ip();
        $ip = fn_ip_to_db($ip['host']);
    }

    $total_product_reviews = $product_reviews_repository->getTotalByParams(['product_id' => $product_id]);
    $post_redirect_url = fn_url('products.update&product_id=' . $product_id . '&selected_section=product_reviews');

    $result = $service->isUserEligibleToWriteProductReview($auth['user_id'], $product_id, $ip);
    if ($result->isFailure()) {
        $result->showNotifications();
    }

    Tygh::$app['view']->assign([
        'create_new_product_reviews'            => true,
        'product_id'                            => $product_id,
        'post_redirect_url'                     => $post_redirect_url,
        'countries'                             => fn_get_simple_countries(true),
        'product_reviews_ratings'               => fn_product_reviews_get_reviews_ratings(),
        'user_data'                             => empty($auth['user_id']) ? [] : fn_get_user_info($auth['user_id']),
        'product_reviews_images_upload_allowed' => Registry::get('addons.product_reviews.images_upload_allowed'),
        'available_message_types'               => ProductReviewsMessageTypes::getTypes(Registry::get('addons.product_reviews.review_fields')),
        'is_allowed_update_reply'               => true,
        'total_product_reviews'                 => $total_product_reviews,
        'product_data'                          => $product_data,
    ]);
}

/**
 * @return void
 */
function fn_product_reviews_copy_reviews_from_discussion()
{
    $discussion_limit = $posts_limit = 100;
    $service = ProductReviewsProvider::getService();
    $default_storefront = StorefrontProvider::getRepository()->findDefault();
    $default_storefront_id = $default_storefront ? $default_storefront->storefront_id : 0;
    $is_ult = fn_allowed_for('ULTIMATE');
    $discussion_offset = 0;

    // execution page by page
    do {
        // get all discussion threads on products
        $discussions = db_get_array(
            'SELECT discussions.thread_id, discussions.object_id, discussions.company_id'
            . ' FROM ?:discussion discussions'
            . ' WHERE discussions.object_type = ?s'
                . ' AND discussions.type = ?s'
            . ' LIMIT ?i, ?i',
            'P', // product
            'B', // comment and rating
            $discussion_offset,
            $discussion_limit
        );

        // stop execution if discussions are over
        if (!$discussions) {
            break;
        }

        foreach ($discussions as $discussion) {
            $product_id = $discussion['object_id'];
            $storefront_id = $is_ult
                ? db_get_field('SELECT storefront_id FROM ?:storefronts_companies WHERE company_id = ?i', $discussion['company_id'])
                : $default_storefront_id;
            $posts_offset = 0;

            // execution page by page
            do {
                // get posts from discussion thread
                $posts = db_get_array(
                    'SELECT posts.*, messages.message, rating.rating_value'
                    . ' FROM ?:discussion_posts posts'
                    . ' LEFT JOIN ?:discussion_messages messages'
                        . ' ON posts.post_id = messages.post_id'
                    . ' LEFT JOIN ?:discussion_rating rating'
                        . ' ON posts.post_id = rating.post_id'
                    . ' WHERE posts.thread_id = ?i'
                    . ' LIMIT ?i, ?i',
                    $discussion['thread_id'],
                    $posts_offset,
                    $posts_limit
                );

                if (!$posts) {
                    break;
                }

                foreach ($posts as $post) {
                    $review_data = [
                        'product_id'               => $product_id,
                        'user_id'                  => $post['user_id'],
                        'name'                     => $post['name'],
                        'comment'                  => $post['message'],
                        'rating_value'             => $post['rating_value'],
                        'ip_address'               => $post['ip_address'],
                        'product_review_timestamp' => $post['timestamp'],
                        'status'                   => $post['status'],
                        'storefront_id'            => $storefront_id,
                    ];

                    db_query('INSERT INTO ?:product_reviews ?e', $review_data);
                }

                $posts_offset += $posts_limit;
            } while (true);

            $service->actualizeProductPreparedData($product_id);
        }

        $discussion_offset += $discussion_limit;
    } while (true);

    fn_set_notification(
        NotificationSeverity::NOTICE,
        __('notice'),
        __('text_items_added')
    );
}
