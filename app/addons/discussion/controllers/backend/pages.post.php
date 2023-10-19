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

/** @var array $auth */
$auth = Tygh::$app['session']['auth'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'update') {
        if (!empty($_REQUEST['posts']) && fn_discussion_check_update_posts_permission($_REQUEST['posts'], $auth)) {
            fn_update_discussion_posts($_REQUEST['posts']);
        }
    }

    return;
}
if ($mode == 'update') {
    $page =  Tygh::$app['view']->getTemplateVars('page_data');
    $discussion = fn_get_discussion($_REQUEST['page_id'], DiscussionObjectTypes::PAGE, true, $_REQUEST);

    if (
        !empty($discussion)
        && $discussion['type'] !== DiscussionTypes::TYPE_DISABLED
        && fn_check_permissions('discussion', 'view', 'admin')
        && $page['page_type'] !== PAGE_TYPE_LINK
        && (fn_get_runtime_company_id() || fn_allowed_for('MULTIVENDOR'))
    ) {
        Registry::set('navigation.tabs.discussion', [
            'title' => __('discussion_title_page'),
            'js'    => true,
        ]);

        if (fn_allowed_for('MULTIVENDOR') && Registry::get('runtime.company_id') && fn_check_permissions('discussion', 'products_and_pages', 'admin')) {
            Tygh::$app['view']->assign('is_allowed_to_add_reviews', false);
        }
        Tygh::$app['view']->assign('discussion', $discussion);
    }

} elseif ($mode == 'm_update') {
    if ($selected_fields['discussion_type'] == 'Y') {
        $field_names['discussion_type'] = __('discussion_title_page');
        $fields2update[] = 'discussion_type';
    }
}
