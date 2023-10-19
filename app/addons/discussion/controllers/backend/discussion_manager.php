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
use Tygh\Enum\Addons\Discussion\DiscussionTypes;
use Tygh\Enum\ObjectStatuses;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

if ($mode == 'manage') {

    $discussion_object_types = fn_get_discussion_objects();
    $discussion_object_titles = fn_get_discussion_titles();

    if (Registry::get('addons.product_reviews.status') === ObjectStatuses::ACTIVE) {
        unset($discussion_object_types[DiscussionObjectTypes::PRODUCT]);
    }

    $params = array_merge([
        'object_type' => null,
        'company_id'  => '',
    ], $_REQUEST);

    $runtime_company_id = fn_get_runtime_company_id();

    $discussion_manager_url = fn_query_remove(Registry::get('config.current_url'), 'object_type', 'page');
    $are_testimonials_enabled = Registry::ifGet('addons.discussion.home_page_testimonials', DiscussionTypes::TYPE_DISABLED) !== DiscussionTypes::TYPE_DISABLED;
    $is_order_communication_enabled = Registry::ifGet('config.discussion.enable_order_communication', false);
    if (!$is_order_communication_enabled) {
        unset($discussion_object_types[DiscussionObjectTypes::ORDER]);
    }

    foreach ($discussion_object_types as $obj_type => $obj) {
        if ($obj_type === DiscussionObjectTypes::TESTIMONIALS_AND_LAYOUT && !$are_testimonials_enabled) {
            continue;
        }

        if (fn_allowed_for('MULTIVENDOR')
            && $runtime_company_id
            && ($obj_type === DiscussionObjectTypes::CATEGORY || $obj_type === DiscussionObjectTypes::TESTIMONIALS_AND_LAYOUT)
        ) {
            continue;
        }

        $params['object_type'] = $params['object_type'] ?: $obj_type;
        $_name = __($discussion_object_titles[$obj_type]);

        Registry::set('navigation.tabs.' . $obj, [
            'title' => $_name,
            'href'  => $discussion_manager_url . '&object_type=' . $obj_type,
        ]);
    }

    list($posts, $search) = fn_get_discussions($params, Registry::get('settings.Appearance.admin_elements_per_page'));

    if (!empty($posts)) {
        foreach ($posts as $k => $v) {
            $posts[$k]['object_data'] = fn_get_discussion_object_data($v['object_id'], $v['object_type'], DESCR_SL);
        }
    }

    Tygh::$app['view']->assign([
        'company_id'              => $runtime_company_id,
        'posts'                   => $posts,
        'search'                  => $search,
        'discussion_object_type'  => $params['object_type'],
        'discussion_object_types' => $discussion_object_types,
    ]);
}
