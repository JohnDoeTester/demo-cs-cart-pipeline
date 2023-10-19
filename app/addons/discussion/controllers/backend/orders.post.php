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

if (!Registry::ifGet('config.discussion.enable_order_communication', false)) {
    return [CONTROLLER_STATUS_OK];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'update_details') {
        if (!empty($_REQUEST['posts'])
            && is_array($_REQUEST['posts'])
            && fn_discussion_check_update_posts_permission($_REQUEST['posts'], $auth)
        ) {

            foreach ($_REQUEST['posts'] as $p_id => $data) {
                db_query('UPDATE ?:discussion_posts SET ?u WHERE post_id = ?i', $data, $p_id);
                db_query('UPDATE ?:discussion_messages SET ?u WHERE post_id = ?i', $data, $p_id);
                db_query('UPDATE ?:discussion_rating SET ?u WHERE post_id = ?i', $data, $p_id);
            }
        }

        if (!empty($_REQUEST['discussion']) && !empty($_REQUEST['discussion']['object_id']) && !empty($_REQUEST['discussion']['object_type'])) {

            $discussion = fn_get_discussion($_REQUEST['discussion']['object_id'], $_REQUEST['discussion']['object_type']);

            if (!empty($discussion['thread_id']) && $discussion['type'] != $_REQUEST['discussion']['type']) {
                db_query('UPDATE ?:discussion SET ?u WHERE thread_id = ?i', $_REQUEST['discussion'], $discussion['thread_id']);
                if ($_REQUEST['discussion']['type'] !== DiscussionTypes::TYPE_DISABLED) {
                    $_REQUEST['selected_section'] = 'discussion';
                }
            } elseif (empty($discussion['thread_id']) && !empty($_REQUEST['discussion']['type'])) {
                $data = $_REQUEST['discussion'];
                $data['company_id'] = Registry::ifGet('runtime.company_id', 0);

                if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id')) {
                    $data['company_id'] = Registry::get('runtime.company_id');
                } elseif (fn_allowed_for('ULTIMATE') && Registry::get('runtime.simple_ultimate')) {
                    $data['company_id'] = Registry::get('runtime.forced_company_id');
                }

                db_replace_into('discussion', (array) $data);
                if ($_REQUEST['discussion']['type'] !== DiscussionTypes::TYPE_DISABLED) {
                    $_REQUEST['selected_section'] = 'discussion';
                }
            }
        }
    }
}

if ($mode === 'details') {
    $discussion = fn_get_discussion($_REQUEST['order_id'], DiscussionObjectTypes::ORDER, true, $_REQUEST);
    if (
        !empty($discussion)
        && $discussion['type'] !== DiscussionTypes::TYPE_DISABLED
        && fn_check_permissions('discussion', 'view', 'admin')
        && (fn_get_runtime_company_id() || fn_allowed_for('MULTIVENDOR'))
    ) {
        Registry::set('navigation.tabs.discussion', [
            'title' => __('communication'),
            'js'    => true,
        ]);

        Tygh::$app['view']->assign('discussion', $discussion);
    }
}
