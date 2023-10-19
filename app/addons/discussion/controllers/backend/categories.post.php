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
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'update') {
        if (!empty($_REQUEST['posts']) && fn_discussion_check_update_posts_permission($_REQUEST['posts'], $auth)) {
            fn_update_discussion_posts($_REQUEST['posts']);
        }
    }

    return;
}

if ($mode == 'update') {

    $discussion = fn_get_discussion($_REQUEST['category_id'], DiscussionObjectTypes::CATEGORY, true, $_REQUEST);

    if (
        !empty($discussion)
        && $discussion['type'] !== DiscussionTypes::TYPE_DISABLED
        && fn_check_permissions('discussion', 'view', 'admin')
        && (fn_get_runtime_company_id() || fn_allowed_for('MULTIVENDOR'))
    ) {
        Registry::set('navigation.tabs.discussion', [
            'title' => __('discussion_title_category'),
            'js'    => true,
        ]);

        Tygh::$app['view']->assign('discussion', $discussion);
    }

} elseif ($mode == 'm_update') {
    $selected_fields = Tygh::$app['session']['selected_fields'];

    if (!empty($selected_fields['extra']) && in_array('discussion_type', $selected_fields['extra'])) {

        $field_names = Tygh::$app['view']->getTemplateVars('field_names');
        $fields2update = Tygh::$app['view']->getTemplateVars('fields2update');

        $field_names['discussion_type'] = __('discussion_title_category');
        $fields2update[] = 'discussion_type';

        Tygh::$app['view']->assign('field_names', $field_names);
        Tygh::$app['view']->assign('fields2update', $fields2update);
    }
}
