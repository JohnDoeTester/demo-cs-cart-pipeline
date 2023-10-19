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

use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ObjectStatuses;
use Tygh\Http;
use Tygh\Registry;
use Tygh\Tools\SecurityHelper;
use Tygh\Languages\Languages;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

// dynamic pieces of content that admin can use in newsletters
$placeholders = array(
    NEWSLETTER_TYPE_NEWSLETTER => array(
        '%UNSUBSCRIBE_LINK' => 'unsubscribe_link',
        '%SUBSCRIBER_EMAIL' => 'subscriber_email',
        '%COMPANY_NAME' => 'company_name',
        '%COMPANY_ADDRESS' => 'company_address',
        '%COMPANY_PHONE' => 'company_phone'
     ),

     NEWSLETTER_TYPE_AUTORESPONDER => array(
         '%ACTIVATION_LINK' => 'activation_link',
        '%SUBSCRIBER_EMAIL' => 'subscriber_email',
        '%COMPANY_NAME' => 'company_name',
        '%COMPANY_ADDRESS' => 'company_address',
        '%COMPANY_PHONE' => 'company_phone'
     ),

     NEWSLETTER_TYPE_TEMPLATE => array(
         '%UNSUBSCRIBE_LINK' => 'unsubscribe_link',
         '%ACTIVATION_LINK' => 'activation_link',
        '%SUBSCRIBER_EMAIL' => 'subscriber_email',
        '%COMPANY_NAME' => 'company_name',
        '%COMPANY_ADDRESS' => 'company_address',
        '%COMPANY_PHONE' => 'company_phone'
     ),
 );

if ($_SERVER['REQUEST_METHOD']	== 'POST') {

    fn_trusted_vars('newsletter_data');

    $suffix = '.manage';
    //
    // Delete newsletters
    //
    if ($mode == 'm_delete') {
        if (!empty($_REQUEST['newsletter_ids'])) {
            foreach ($_REQUEST['newsletter_ids'] as $v) {
                fn_delete_newsletter($v);
            }
        }
    }

    //
    // Update newsletters
    //
    if ($mode == 'update') {
        $newsletter_id = fn_update_newsletter($_REQUEST['newsletter_data'], $_REQUEST['newsletter_id'], DESCR_SL);

        return array(CONTROLLER_STATUS_OK, 'newsletters.update?newsletter_id=' . $newsletter_id);
    }

    //
    // Send newsletter
    //
    if ($mode == 'send') {

        $recipient_list =  fn_newsletters_get_recipients($_REQUEST['newsletter_data']);

        if (empty($recipient_list)) {
            fn_set_notification('W', __('warning'), __('warning_newsletter_no_recipients'));

            return [CONTROLLER_STATUS_OK, 'newsletters.update?newsletter_id=' . $_REQUEST['newsletter_id']];
        }

        $key = md5(uniqid(rand()));

        $newsletter_ids = isset($_REQUEST['send_ids']) ? $_REQUEST['send_ids'] : array($_REQUEST['newsletter_id']);
        foreach ($newsletter_ids as $newsletter_id) {
            $send_data = [
                'status' => 'S',
                'sent_date' => TIME
            ];

            $newsletter_data = array_merge($_REQUEST['newsletter_data'], $send_data);

            $newsletter_id = fn_update_newsletter($newsletter_data,  $newsletter_id, DESCR_SL);

            foreach ($recipient_list as &$recipient) {
                $recipient['newsletter_id'] = $newsletter_id;
                $recipient['send_key'] = $key;
            }
            unset($recipient);

            fn_newsletters_add_batch_recipients($recipient_list);
        }

        return [CONTROLLER_STATUS_OK, 'newsletters.batch_send?key=' . $key];
    }

    // send newsletter to test email
    if ($mode == 'test_send') {

        $test_email = $_REQUEST['test_email'];
        if (fn_validate_email($test_email)) {

            $user['list_id'] = 0;
            $user['subscriber_id'] = 0;
            $user['email'] = $test_email;
            $newsletter = $_REQUEST['newsletter_data'];

            if (isset($newsletter['campaign_id'])) {
                $newsletter['body_html'] = fn_rewrite_links($newsletter['body_html'], $_REQUEST['newsletter_id'], $newsletter['campaign_id']);
            }
            $first_newsletter = fn_render_newsletter($newsletter['body_html'], $user);

            if (!empty($first_newsletter)) {
                $result = fn_send_newsletter($test_email, array(), $newsletter['newsletter'], $first_newsletter, array(), DESCR_SL, '', true);
            }

            if ((!empty($first_newsletter) && $result)) {
                fn_set_notification('N', __('notice'), __('text_newsletter_sent'));
            }
        } else {
            if (empty($test_email)) {
                fn_set_notification('W', __('warning'), __('email_cannot_be_empty'));
            } else {
                fn_set_notification('W', __('warning'), __('error_invalid_emails', array(
                    '[emails]' => $test_email
                )));
            }
        }

        if (defined('AJAX_REQUEST')) {
            exit;
        }

        return array(CONTROLLER_STATUS_OK, 'newsletters.update?newsletter_id=' . $_REQUEST['newsletter_id']);
    }

    // preview html version of newsletter
    if ($mode == 'preview_html') {
        $user['list_id'] = 0;
        $user['subscriber_id'] = 0;
        $user['email'] = 'sample@sample.com';
        $body = fn_render_newsletter($_REQUEST['newsletter_data']['body_html'], $user);
        Tygh::$app['view']->assign('body', $body);
        Tygh::$app['view']->display('addons/newsletters/views/newsletters/components/preview_popup.tpl');
        exit();
    }

    if ($mode == 'm_update_campaigns') {

        if (!empty($_REQUEST['campaigns'])) {
            $c_ids = array();
            foreach ($_REQUEST['campaigns'] as $k => $data) {
                db_query("UPDATE ?:newsletter_campaigns SET ?u WHERE campaign_id = ?i", $data, $k);

                $data['object'] = $data['name'];
                $_where = array(
                    'object_id' => $k,
                    'object_holder' => 'newsletter_campaigns',
                    'lang_code' => DESCR_SL
                );

                db_query("UPDATE ?:common_descriptions SET ?u WHERE ?w", $data, $_where);
            }
        }

        $suffix = '.campaigns';
    }

    if ($mode == 'add_campaign') {
        $data = $_REQUEST['campaign_data'];
        if (!empty($data['name'])) {
            $data['campaign_id'] = $data['object_id'] = db_query("INSERT INTO ?:newsletter_campaigns ?e", $data);
            $data['object'] = $data['name'];
            $data['object_holder'] = 'newsletter_campaigns';

            foreach (Languages::getAll() as $data['lang_code'] => $_v) {
                db_query("REPLACE INTO ?:common_descriptions ?e", $data);
            }
        }

        $suffix = '.campaigns';
    }

    if ($mode == 'm_delete_campaigns') {
        if (!empty($_REQUEST['campaign_ids'])) {
            fn_delete_campaigns($_REQUEST['campaign_ids']);
        }

        $suffix = '.campaigns';
    }

    if ($mode == 'delete') {
        if (!empty($_REQUEST['newsletter_id'])) {
            fn_delete_newsletter($_REQUEST['newsletter_id']);
        }

        $suffix = '.manage';
    }

    if ($mode == 'delete_campaign') {
        if (!empty($_REQUEST['campaign_id'])) {
            fn_delete_campaigns((array) $_REQUEST['campaign_id']);
        }

        $suffix = '.campaigns';
    }

    if (
        $mode === 'm_update_statuses'
        && !empty($_REQUEST['newsletter_ids'])
        && !empty($_REQUEST['status'])
    ) {
        $status_to = $_REQUEST['status'];

        foreach ((array) $_REQUEST['newsletter_ids'] as $newsletter_id) {
            fn_tools_update_status(
                [
                    'table'             => 'newsletters',
                    'status'            => $status_to,
                    'id_name'           => 'newsletter_id',
                    'id'                => $newsletter_id,
                    'show_error_notice' => false,
                ]
            );
        }

        if (defined('AJAX_REQUEST')) {
            $redirect_url = fn_url('newsletters.manage');
            if (isset($_REQUEST['redirect_url'])) {
                $redirect_url = $_REQUEST['redirect_url'];
            }
            Tygh::$app['ajax']->assign('force_redirection', $redirect_url);
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            return [CONTROLLER_STATUS_NO_CONTENT];
        }

        $suffix = '.manage';
    }

    if (
        $mode === 'm_update_statuses_campaigns'
        && !empty($_REQUEST['campaign_ids'])
        && !empty($_REQUEST['status'])
    ) {
        $status_to = $_REQUEST['status'];

        foreach ((array) $_REQUEST['campaign_ids'] as $campaign_id) {
            fn_tools_update_status(
                [
                    'table'             => 'newsletter_campaigns',
                    'status'            => $status_to,
                    'id_name'           => 'campaign_id',
                    'id'                => $campaign_id,
                    'show_error_notice' => false,
                ]
            );
        }

        if (defined('AJAX_REQUEST')) {
            $redirect_url = fn_url('newsletters.campaigns');
            if (isset($_REQUEST['redirect_url'])) {
                $redirect_url = $_REQUEST['redirect_url'];
            }
            Tygh::$app['ajax']->assign('force_redirection', $redirect_url);
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            return [CONTROLLER_STATUS_NO_CONTENT];
        }

        $suffix = '.campaigns';
    }

    return array(CONTROLLER_STATUS_OK, 'newsletters' . $suffix);
}

if ($mode == 'batch_send' && !empty($_REQUEST['key'])) {

    $limit = Registry::get('addons.newsletters.newsletters_per_pass');
    $offset = isset($_REQUEST['offset']) ? $_REQUEST['offset'] : 0;

    $send_list = fn_newsletters_get_send_list($_REQUEST['key'], $limit, $offset);

    if (empty($send_list)) {
        if (isset($_REQUEST['offset'])) {
            fn_newsletters_drop_newsletter_batch($_REQUEST['key']);

            fn_set_notification('N', __('notice'), __('text_newsletter_sent'));
        } else {
            fn_set_notification('W', __('warning'), __('warning_newsletter_no_recipients'));
        }

        return [CONTROLLER_STATUS_OK, 'newsletters.manage'];
    }

    $languages = Languages::getAll();

    foreach ($send_list as $send_id => $recipient_list) {
        $newsletter = [];

        foreach ($languages as $lang_code => $v) {
            $newsletter[$lang_code] = fn_get_newsletter_data($send_id, $lang_code);
            $newsletter[$lang_code]['body_html'] = fn_rewrite_links($newsletter[$lang_code]['body_html'], $send_id, $newsletter[$lang_code]['campaign_id']);
        }

        foreach ($recipient_list as $recipient) {
            $body = fn_render_newsletter($newsletter[$recipient['lang_code']]['body_html'], $recipient);

            if (!empty($newsletter[$recipient['lang_code']]['newsletter_multiple'])) {
                $subjects = explode("\n", $newsletter[$recipient['lang_code']]['newsletter_multiple']);
                $newsletter[$recipient['lang_code']]['newsletter'] = trim($subjects[rand(0, count($subjects) - 1)]);
            }

            fn_echo(__('sending_email_to', ['[email]' => $recipient['email']]) . '<br />');

            fn_send_newsletter($recipient['email'], $recipient, $newsletter[$recipient['lang_code']]['newsletter'], $body, [], $recipient['lang_code'], $recipient['reply_to']);
            $offset++;
        }
    }

    return [CONTROLLER_STATUS_OK, 'newsletters.batch_send?key=' . $_REQUEST['key'] . '&offset=' . $offset];

// return template body
} elseif ($mode == 'render') {
    if (defined('AJAX_REQUEST')) {
        $template_id = !empty($_REQUEST['template_id']) ? intval($_REQUEST['template_id']) : 0;
        if ($template_id) {
            $template = fn_get_newsletter_data($template_id, DESCR_SL);
            Tygh::$app['ajax']->assign('template', $template['body_html']);
        }

        exit();
    }

// newsletter update page
} elseif ($mode == 'update') {
    $newsletter_id = !empty($_REQUEST['newsletter_id']) ? intval($_REQUEST['newsletter_id']) : 0;

    $newsletter_data = fn_get_newsletter_data($newsletter_id, DESCR_SL);

    if (empty($newsletter_data)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    $campaigns = db_get_hash_array("SELECT * FROM ?:newsletter_campaigns AS n LEFT JOIN ?:common_descriptions AS d ON n.campaign_id = d.object_id AND d.lang_code = ?s WHERE d.object_holder = 'newsletter_campaigns' AND n.status = 'A'", 'campaign_id', DESCR_SL);

    Tygh::$app['view']->assign('newsletter_campaigns', $campaigns);

    $links = db_get_array("SELECT * FROM ?:newsletter_links WHERE newsletter_id=?i", $newsletter_id);
    Tygh::$app['view']->assign('newsletter_links', $links);

    Tygh::$app['view']->assign('newsletter', $newsletter_data);

    [$newsletter_templates] = fn_get_newsletters(['type' => NEWSLETTER_TYPE_TEMPLATE, 'only_available' => false], 0, DESCR_SL);
    Tygh::$app['view']->assign('newsletter_templates', $newsletter_templates);
    Tygh::$app['view']->assign('newsletter_type', $newsletter_data['type']);
    Tygh::$app['view']->assign('placeholders', $placeholders[$newsletter_data['type']]);

    $mailing_lists = db_get_hash_array("SELECT * FROM ?:mailing_lists AS m INNER JOIN ?:common_descriptions AS d ON m.list_id = d.object_id WHERE d.object_holder = 'mailing_lists' AND d.lang_code = ?s", 'list_id', DESCR_SL);
    if (fn_allowed_for('ULTIMATE')) {
        $mailing_lists = fn_get_shared_companies($mailing_lists);
    }
    Tygh::$app['view']->assign('mailing_lists', $mailing_lists);

    Tygh::$app['view']->assign(
        'newsletter_users',
        !empty($newsletter_data['users'])
            ? db_get_fields('SELECT user_id FROM ?:users WHERE user_id IN(?n) ', explode(',', $newsletter_data['users']))
            : []
    );

// newsletter creation page
} elseif ($mode == 'add') {

    $newsletter_type = !empty($_REQUEST['type']) ? $_REQUEST['type'] : NEWSLETTER_TYPE_NEWSLETTER;

    $campaigns = db_get_array("SELECT * FROM ?:newsletter_campaigns AS n INNER JOIN ?:common_descriptions AS d ON n.campaign_id = d.object_id AND d.lang_code = ?s WHERE d.object_holder='newsletter_campaigns'", DESCR_SL);
    Tygh::$app['view']->assign('newsletter_campaigns', $campaigns);

    [$newsletter_templates] = fn_get_newsletters(['type' => NEWSLETTER_TYPE_TEMPLATE, 'only_available' => false], 0, DESCR_SL);
    Tygh::$app['view']->assign('newsletter_templates', $newsletter_templates);
    Tygh::$app['view']->assign('newsletter_type', $newsletter_type);
    Tygh::$app['view']->assign('placeholders', $placeholders[$newsletter_type]);

    [$mailing_lists] = fn_get_mailing_lists(['only_available' => false]);
    if (fn_allowed_for('ULTIMATE')) {
        $mailing_lists = fn_get_shared_companies($mailing_lists);
    }
    Tygh::$app['view']->assign('mailing_lists', $mailing_lists);

// newsletter creation page
} elseif ($mode == 'preview_popup') {
    Tygh::$app['view']->display('addons/newsletters/views/newsletters/components/preview_popup.tpl');
    exit();

// newsletter manage page
} elseif ($mode == 'manage') {
    // do we list newsletters or templates or autoresponders?
    $newsletter_type = !empty($_REQUEST['type']) ? $_REQUEST['type'] : NEWSLETTER_TYPE_NEWSLETTER;
    // Use pagination for a newsletters
    $params = array(
        'type' => $newsletter_type,
        'only_available' => false
    );

    $items_per_page = 0;
    $params = fn_array_merge($params, $_REQUEST);
    if ($newsletter_type === NEWSLETTER_TYPE_NEWSLETTER) {
        $items_per_page = Registry::get('settings.Appearance.admin_elements_per_page');
    }

    [$newsletters, $search] = fn_get_newsletters($params, $items_per_page, DESCR_SL);
    [$mailing_lists] = fn_get_mailing_lists(['only_available' => false]);

    foreach ($newsletters as $newsletter_id => $data) {
        if (!empty($data['mailing_lists'])) {
            $lists = [];
            foreach ($data['mailing_lists'] as $mailing_list_id) {
                $lists[] = $mailing_lists[$mailing_list_id]['object'];
            }
            $newsletters[$newsletter_id]['mailing_list_names'] = implode(', ', $lists);
        }
    }

    Tygh::$app['view']->assign('newsletter_type', $newsletter_type);
    Tygh::$app['view']->assign('mailing_lists', $mailing_lists);
    Tygh::$app['view']->assign('newsletters', $newsletters);
    Tygh::$app['view']->assign('search', $search);

    fn_newsletters_generate_sections($newsletter_type);

} elseif ($mode == 'campaigns') {

    [$campaigns, $search] = fn_get_campaigns($_REQUEST, Registry::get('settings.Appearance.admin_elements_per_page'));
    Tygh::$app['view']->assign([
        'campaigns'             => $campaigns,
        'search'                => $search,
        'is_allow_add_campaign' => fn_check_permissions('newsletters', 'add_campaign', 'admin', Http::POST),
    ]);

    fn_newsletters_generate_sections('C');

} elseif ($mode == 'campaign_stats') {

    $campaign = db_get_row("SELECT c.*, d.* FROM ?:newsletter_campaigns AS c INNER JOIN ?:common_descriptions AS d ON c.campaign_id=d.object_id LEFT JOIN ?:newsletters ON c.campaign_id=?:newsletters.campaign_id WHERE d.object_holder='newsletter_campaigns' AND c.campaign_id = ?i AND d.lang_code = ?s", $_REQUEST['campaign_id'], DESCR_SL);
    $stats = db_get_array("SELECT n.*, d.*, SUM(e.clicks) AS clicks FROM ?:newsletters AS n INNER JOIN ?:newsletter_descriptions AS d ON n.newsletter_id=d.newsletter_id LEFT JOIN ?:newsletter_links AS e ON n.newsletter_id = e.newsletter_id AND e.campaign_id = n.campaign_id WHERE n.campaign_id=?i AND d.lang_code = ?s GROUP BY e.newsletter_id", $_REQUEST['campaign_id'], DESCR_SL);
    Tygh::$app['view']->assign('campaign', $campaign);
    Tygh::$app['view']->assign('campaign_stats', $stats);
}

function fn_delete_campaigns($campaign_ids)
{
    db_query("DELETE FROM ?:common_descriptions WHERE object_id IN (?n) AND object_holder = 'newsletter_campaigns'", $campaign_ids);
    db_query("DELETE FROM ?:newsletter_campaigns WHERE campaign_id IN (?n)", $campaign_ids);
    db_query("DELETE FROM ?:newsletter_links WHERE campaign_id IN (?n)", $campaign_ids);
    db_query("UPDATE ?:newsletters SET campaign_id = 0 WHERE campaign_id IN (?n)", $campaign_ids);
}

function fn_get_campaigns($params, $items_per_page = 0, $lang_code = DESCR_SL)
{
    $default_params = array (
        'page' => 1,
        'items_per_page' => $items_per_page
    );

    $params = array_merge($default_params, $params);

    $limit = '';
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:newsletter_campaigns");
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $campaigns = db_get_array("SELECT c.*, d.* FROM ?:newsletter_campaigns AS c INNER JOIN ?:common_descriptions AS d ON c.campaign_id = d.object_id AND lang_code = ?s LEFT JOIN ?:newsletters ON c.campaign_id=?:newsletters.campaign_id WHERE d.object_holder = 'newsletter_campaigns' $limit", $lang_code);

    return array($campaigns, $params);
}

function fn_update_newsletter($newsletter_data, $newsletter_id = 0, $lang_code = DESCR_SL)
{
    SecurityHelper::sanitizeObjectData('newsletter', $newsletter_data);

    if (empty($newsletter_data['mailing_lists'])) {
        $newsletter_data['mailing_lists'] = array();
    }

    if (empty($newsletter_id)) {
        if (empty($newsletter_data['newsletter'])) {
            return false;
        }

        $_data = $newsletter_data;
        $_data['mailing_lists'] = implode(',', $_data['mailing_lists']);

        $newsletter_id = db_query("INSERT INTO ?:newsletters ?e", $_data);

        if (empty($newsletter_id)) {
            return false;
        }

        $_data['newsletter_id'] = $newsletter_id;

        foreach (Languages::getAll() as $_data['lang_code'] => $v) {
            db_query("INSERT INTO ?:newsletter_descriptions ?e", $_data);
        }

    } else {
        // we do not need empty title
        if (empty($newsletter_data['newsletter'])) {
            unset($newsletter_data['newsletter']);
        }

        if (empty($newsletter_data['users'])) {
            $newsletter_data['users'] = '';
        }

        if (
            !empty($newsletter_data['type']) && $newsletter_data['type'] === NEWSLETTER_TYPE_AUTORESPONDER
            && !empty($newsletter_data['status']) && $newsletter_data['status'] !== ObjectStatuses::ACTIVE
        ) {
            $used_in_mailing_lists = db_get_field('SELECT COUNT(*) FROM ?:mailing_lists WHERE register_autoresponder = ?s', $newsletter_id);
            if ($used_in_mailing_lists) {
                $newsletter_data['status'] = ObjectStatuses::ACTIVE;
                fn_set_notification(
                    NotificationSeverity::WARNING,
                    __('warning'),
                    __('newsletters.autoresponder_used_in_mailing_list', ['[link]' => fn_url('mailing_lists.manage')])
                );
            }
        }

        $_data = $newsletter_data;
        $_data['mailing_lists'] = implode(',', $_data['mailing_lists']);

        db_query("UPDATE ?:newsletters SET ?u WHERE newsletter_id = ?i", $_data, $newsletter_id);

        db_query("UPDATE ?:newsletter_descriptions SET ?u WHERE newsletter_id=?i AND lang_code=?s", $_data, $newsletter_id, $lang_code);
    }

    if (isset($newsletter_data['campaign_id'])) {
        // for link tracking (to count user clicks on links in our newsletters) we need to rewrite urls in the newsletter.
        fn_rewrite_links($newsletter_data['body_html'], $newsletter_id, $newsletter_data['campaign_id']);
    }

    fn_set_hook('update_newsletter', $newsletter_data, $newsletter_id);

    return $newsletter_id;
}
