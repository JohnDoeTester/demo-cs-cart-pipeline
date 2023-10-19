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

use Tygh\Enum\Addons\Discussion\DiscussionObjectTypes;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    return;
}

if ($mode == 'get_new_post_form') {
    if (!defined('AJAX_REQUEST')) {
        return;
    }

    $object_id = isset($_REQUEST['object_id']) ? $_REQUEST['object_id'] : null;
    $object_type = isset($_REQUEST['object_type']) ? $_REQUEST['object_type'] : '';
    $render_form = true;

    if (!isset($object_id) || empty($object_type)) {
        fn_set_notification('E', __('error'), __('error_occured'));
        $render_form = false;

    } elseif ($object_type === DiscussionObjectTypes::PRODUCT
        && !fn_discussion_is_user_eligible_to_write_review_for_product($auth['user_id'], $object_id)
    ) {
        fn_set_notification('E', __('error'), __('discussion.you_have_to_buy_product_before_writing_review'));
        $render_form = false;
    } elseif ($object_type === DiscussionObjectTypes::COMPANY
        && !fn_discussion_is_user_eligible_to_write_review_for_company($auth['user_id'], $object_id)
    ) {
        fn_set_notification('E', __('error'), __('discussion.you_have_to_buy_from_vendor_before_writing_review'));
        $render_form = false;
    }

    if ($render_form) {
        $discussion = fn_get_discussion($object_id, $object_type, false);

        Tygh::$app['view']->assign(array(
            'obj_id'            => $object_id,
            'obj_prefix'        => isset($_REQUEST['obj_prefix']) ? $_REQUEST['obj_prefix'] : '',
            'post_redirect_url' => isset($_REQUEST['post_redirect_url']) ? $_REQUEST['post_redirect_url'] : '',
            'discussion'        => $discussion,
            'new_post_title'    => __('write_review'),
        ));

        Tygh::$app['view']->display('addons/discussion/views/discussion/components/new_post.tpl');
    }

    exit;
}

if ($mode == 'get_user_login_form') {
    if (!defined('AJAX_REQUEST')) {
        return;
    }

    Tygh::$app['view']->assign('redirect_url', isset($_REQUEST['return_url']) ? $_REQUEST['return_url'] : '');
    Tygh::$app['view']->display('addons/discussion/views/discussion/components/login_form.tpl');
    exit;
}
