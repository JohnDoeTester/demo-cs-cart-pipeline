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
use Tygh\Enum\Addons\ProductReviews\ProductReview\ProductReviewVoteValues;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\UserTypes;
use Tygh\Providers\StorefrontProvider;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

$auth = & Tygh::$app['session']['auth'];
$product_reviews_repository = ProductReviewsProvider::getProductReviewRepository();
$service = ProductReviewsProvider::getService();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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

    if (
        $mode === 'vote'
        && (
            $action === 'up'
            || $action === 'down'
        )
    ) {
        $product_review_id = empty($_REQUEST['product_review_id']) ? 0 : $_REQUEST['product_review_id'];
        $value = $action === 'up' ? ProductReviewVoteValues::VOTE_UP_VALUE : ProductReviewVoteValues::VOTE_DOWN_VALUE;
        $vote_data = [
            'product_review_id' => $product_review_id,
            'user_id'           => $auth['user_id'],
            'value'             => $value,
        ];

        if (!empty($auth['ip'])) {
            $vote_data['ip_address'] = $auth['ip'];
        } else {
            $ip = fn_get_ip();
            $vote_data['ip_address'] = $ip['host'];
        }

        $service->addVote($vote_data);

        if (!empty($_REQUEST['return_url'])) {
            return [CONTROLLER_STATUS_REDIRECT, $_REQUEST['return_url']];
        }
    }

    return [CONTROLLER_STATUS_OK];
}

if ($mode === 'get_new_post_form') {
    if (!defined('AJAX_REQUEST')) {
        return [CONTROLLER_STATUS_NO_CONTENT];
    }

    $product_id = isset($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0;
    $render_form = true;

    if (empty($product_id)) {
        fn_set_notification(NotificationSeverity::ERROR, __('error'), __('error_occured'));
        $render_form = false;
    } else {
        if (!empty($auth['ip'])) {
            $ip = fn_ip_to_db((string) $auth['ip']);
        } else {
            $ip = fn_get_ip();
            $ip = fn_ip_to_db($ip['host']);
        }

        $result = $service->isUserEligibleToWriteProductReview($auth['user_id'], $product_id, $ip);

        if ($result->isFailure()) {
            $result->showNotifications();
            $render_form = false;
        }
    }

    if ($render_form) {
        Tygh::$app['view']->assign([
            'product_id'                            => $product_id,
            'post_redirect_url'                     => isset($_REQUEST['post_redirect_url']) ? $_REQUEST['post_redirect_url'] : '',
            'new_post_title'                        => __('product_reviews.write_review'),
            'countries'                             => fn_get_simple_countries(true),
            'product_reviews_ratings'               => fn_product_reviews_get_reviews_ratings(),
            'user_data'                             => empty($auth['user_id']) ? [] : fn_get_user_info($auth['user_id']),
            'product_reviews_images_upload_allowed' => Registry::get('addons.product_reviews.images_upload_allowed'),
        ]);

        Tygh::$app['view']->display('addons/product_reviews/views/product_reviews/components/new_product_review.tpl');
    }

    return [CONTROLLER_STATUS_NO_CONTENT];
}

if ($mode === 'get_user_login_form') {
    if (!defined('AJAX_REQUEST')) {
        return [CONTROLLER_STATUS_NO_CONTENT];
    }

    Tygh::$app['view']->assign('redirect_url', isset($_REQUEST['return_url']) ? $_REQUEST['return_url'] : '');
    Tygh::$app['view']->display('addons/product_reviews/views/product_reviews/components/login_form.tpl');

    return [CONTROLLER_STATUS_NO_CONTENT];
}
