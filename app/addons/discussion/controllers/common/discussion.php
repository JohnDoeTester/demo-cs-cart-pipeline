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

use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Registry;
use Tygh\Enum\Addons\Discussion\DiscussionObjectTypes;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/** @var array $auth */

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $suffix = '';
    if ($mode == 'add') {
        $post_data = isset($_REQUEST['post_data']) ? $_REQUEST['post_data'] : array();
        $discussion_object = fn_discussion_get_object($post_data);

        if (
            !isset($discussion_object['object_id'])
            || (
                !SiteArea::isStorefront(AREA)
                && UserTypes::isVendor($auth['user_type'])
                && !empty($discussion_object['company_id'])
                && (int) $auth['company_id'] !== (int) $discussion_object['company_id']
            )
        ) {
            fn_set_notification('E', __('error'), __('error_occured'));
            return array(CONTROLLER_STATUS_NO_PAGE);

        } elseif (AREA == 'C'
            && $discussion_object['object_type'] === DiscussionObjectTypes::PRODUCT
            && !fn_discussion_is_user_eligible_to_write_review_for_product($auth['user_id'], $discussion_object['object_id'])
        ) {
            fn_set_notification('E', __('error'), __('discussion.you_have_to_buy_product_before_writing_review'));
            return array(CONTROLLER_STATUS_DENIED);
        }

        $suffix = '&selected_section=discussion';

        fn_add_discussion_post($_REQUEST['post_data']);
    }

    $redirect_url = "discussion_manager.manage";
    if (!empty($_REQUEST['redirect_url'])) {
        $redirect_url = $_REQUEST['redirect_url'] . $suffix;
    }

    return array(CONTROLLER_STATUS_OK, $redirect_url);
}

if ($mode == 'view') {
    $data = array();
    $thread_id = isset($_REQUEST['thread_id']) ? $_REQUEST['thread_id'] : null;

    if ($thread_id !== null) {
        $data = fn_discussion_get_object(array(
            'thread_id' => $thread_id,
        ));
    }

    if (empty($data)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    if (AREA != 'A') {
        // Check if user has an access for this thread
        if (fn_is_accessible_discussion($data, $auth) == false) {
            return array(CONTROLLER_STATUS_DENIED);
        }

        if ($data['object_type'] == 'E' && !empty($_REQUEST['post_id'])) {
            $post_pos = db_get_field('SELECT COUNT(*) FROM ?:discussion_posts WHERE thread_id = ?i AND post_id >= ?i AND status = ?s ORDER BY timestamp DESC', $thread_id, $_REQUEST['post_id'], 'A');
            if (!empty($post_pos)) {
                $sets = Registry::get('addons.discussion');
                $discussion_object_types = fn_get_discussion_objects();
                $items_per_page = $sets[$discussion_object_types[$data['object_type']] . '_posts_per_page'];
                $page = ceil($post_pos / $items_per_page);
                if ((empty($_REQUEST['page']) && $page != 1) || (!empty($_REQUEST['page']) && $page != $_REQUEST['page'])) {
                    $_REQUEST['page'] = $page;
                }
                Tygh::$app['session']['discussion_post_id'] = $_REQUEST['post_id'];

                return array(CONTROLLER_STATUS_REDIRECT, fn_query_remove(Registry::get('config.current_url'), 'page', 'post_id'));
            }
        }
    }

    $show_discussion_crumb = true;
    if ($data['object_type'] == 'E') { // testimonials
        $show_discussion_crumb = false;
    }

    $discussion_object_data = fn_get_discussion_object_data($data['object_id'], $data['object_type']);

    fn_add_breadcrumb($discussion_object_data['description'], $discussion_object_data['url']);

    if ($show_discussion_crumb && AREA != 'A') {
        fn_add_breadcrumb(__('discussion'));
    }

    if (!empty(Tygh::$app['session']['discussion_post_id'])) {
        Tygh::$app['view']->assign('current_post_id', Tygh::$app['session']['discussion_post_id']);
        unset(Tygh::$app['session']['discussion_post_id']);
    }

    $discussion = fn_get_discussion($data['object_id'], $data['object_type'], true, $_REQUEST);

    Tygh::$app['view']->assign('search', $discussion['search']);
    Tygh::$app['view']->assign('object_id', $data['object_id']);
    Tygh::$app['view']->assign('title', $discussion_object_data['description']);
    Tygh::$app['view']->assign('object_type', $data['object_type']);
}
