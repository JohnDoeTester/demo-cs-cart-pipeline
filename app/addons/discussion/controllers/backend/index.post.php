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

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode == 'set_post_status') {

        $new_status = ($_REQUEST['new_status'] === 'A') ? 'A' : 'D';
        db_query("UPDATE ?:discussion_posts SET ?u WHERE post_id = ?i", array('status' => $new_status), $_REQUEST['post_id']);

        $post = db_get_row("SELECT * FROM ?:discussion_posts WHERE post_id = ?i", $_REQUEST['post_id']);
        Tygh::$app['view']->assign('post', $post);
        if (defined('AJAX_REQUEST')) {
            Tygh::$app['view']->display('addons/discussion/views/index/components/dashboard_status.tpl');
            exit;
        }

        return array(CONTROLLER_STATUS_OK, fn_url());
    }

    if ($mode == 'delete_post' && defined('AJAX_REQUEST')) {
        db_query("DELETE FROM ?:discussion_messages WHERE post_id = ?i", $_REQUEST['post_id']);
        db_query("DELETE FROM ?:discussion_rating WHERE post_id = ?i", $_REQUEST['post_id']);
        db_query("DELETE FROM ?:discussion_posts WHERE post_id = ?i", $_REQUEST['post_id']);

        return array(CONTROLLER_STATUS_OK, fn_url());
    }
    
    return;
}

// No action for vendor at the index
if (Registry::get('runtime.company_id') && fn_allowed_for('MULTIVENDOR')) {
    return;
}

if ($mode == 'delete_post' && defined('AJAX_REQUEST')) { // FIXME - bad style
    Tygh::$app['view']->display('addons/discussion/views/index/components/dashboard.tpl');
    exit;
}
