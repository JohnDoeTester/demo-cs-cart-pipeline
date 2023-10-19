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
use Tygh\Registry;
use Tygh\Navigation\LastView;
use Tygh\Enum\ObjectStatuses;
use Tygh\Languages\Languages;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

function fn_get_newsletter_name($newsletter_id, $lang_code = CART_LANGUAGE)
{
    if (!empty($newsletter_id)) {
        return db_get_field("SELECT newsletter FROM ?:newsletter_descriptions WHERE newsletter_id = ?i AND lang_code = ?s", $newsletter_id, $lang_code);
    }

    return false;
}

//
// Get all newsletters data
//
function fn_get_newsletters($params = array(), $items_per_page = 0, $lang_code = CART_LANGUAGE)
{
    $default_params = array(
        'type' => NEWSLETTER_TYPE_NEWSLETTER,
        'only_available' => true, // hide hidden and not available newsletters. We use 'false' for admin page
        'page' => 1,
        'items_per_page' => $items_per_page
    );

    $params = array_merge($default_params, $params);

    $_conditions = [];

    $sortings = [
        'position'      => [
            '?:newsletters.sent_date',
            '?:newsletters.status'
        ],
        'subject'       => '?:newsletter_descriptions.newsletter',
        'mailing_lists' => '?:newsletters.mailing_lists',
        'date'          => '?:newsletters.sent_date',
        'status'        => '?:newsletters.status'
    ];

    if ($params['only_available']) {
        $_conditions[] = "?:newsletters.status = 'A'";
    }

    if ($params['type']) {
        $_conditions[] = db_quote("?:newsletters.type = ?s", $params['type']);
    }

    if (!empty($_conditions)) {
        $_conditions = implode(' AND ', $_conditions);
    } else {
        $_conditions = '1';
    }

    $order_by = db_sort($params, $sortings, 'position', 'desc');

    $limit = '';
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:newsletters WHERE ?p", $_conditions);
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $newsletters = db_get_array(
        'SELECT ?:newsletters.newsletter_id, ?:newsletters.status, ?:newsletters.sent_date, '
        . '?:newsletters.status, ?:newsletters.mailing_lists, ?:newsletter_descriptions.newsletter FROM ?:newsletters '
        . 'LEFT JOIN ?:newsletter_descriptions ON ?:newsletter_descriptions.newsletter_id=?:newsletters.newsletter_id '
        . 'AND ?:newsletter_descriptions.lang_code = ?s '
        . 'WHERE ?p ?p ?p',
        $lang_code,
        $_conditions,
        $order_by,
        $limit
    );

    foreach ($newsletters as $id => $data) {
        $newsletters[$id]['mailing_lists'] = !empty($data['mailing_lists']) ? fn_explode(',', $data['mailing_lists']) : array();
    }

    return array($newsletters, $params);
}

//
// Get specific newsletter data
//
function fn_get_newsletter_data($newsletter_id, $lang_code = CART_LANGUAGE)
{
    $status_condition = (AREA == 'A') ? '' : " AND ?:newsletters.status='A' ";

    $newsletter = db_get_row("SELECT * FROM ?:newsletters LEFT JOIN ?:newsletter_descriptions ON ?:newsletter_descriptions.newsletter_id = ?:newsletters.newsletter_id AND ?:newsletter_descriptions.lang_code = ?s WHERE ?:newsletters.newsletter_id = ?i $status_condition", $lang_code, $newsletter_id);

    if (!empty($newsletter)) {
        $newsletter['mailing_lists'] = explode(',', $newsletter['mailing_lists']);
    }

    return $newsletter;
}


//
// Get mailing list data
//
function fn_get_mailing_list_data($list_id, $lang_code = CART_LANGUAGE)
{
    $status_condition = (AREA == 'A') ? '' : " AND m.status = 'A' ";

    return db_get_row("SELECT * FROM ?:mailing_lists AS m LEFT JOIN ?:common_descriptions AS d ON m.list_id = d.object_id AND d.lang_code = ?s AND d.object_holder = 'mailing_lists' WHERE m.list_id = ?i $status_condition", $lang_code, $list_id);
}

// if called first time - registers all links in db
// returns newsletter bodies with rewritten links
function fn_rewrite_links($body_html, $newsletter_id, $campaign_id)
{
    $regex = "/href=('|\")((?:http|ftp|https):\/\/[\w\.-]+[?]?[-\w:\+?\/?\.\=%&;~\[\]]+)/i";
    $url = fn_url('newsletters.track', 'C', 'http');

    $body_html = preg_replace_callback($regex, function($matches) use ($url, $newsletter_id, $campaign_id) {
        return 'href=' . $matches[1] . fn_link_attach($url, 'link=' . (fn_register_link($matches[2], $newsletter_id, $campaign_id) . "-" . $newsletter_id . "-" . $campaign_id));
    }, $body_html);

    return $body_html;
}

function fn_register_link($url, $newsletter_id, $campaign_id)
{
    $url = str_replace('&amp;', '&', rtrim($url, '/'));
    $_where = array(
        'newsletter_id' => $newsletter_id,
        'campaign_id' => $campaign_id,
        'url' => $url
    );
    $link = db_get_row("SELECT link_id FROM ?:newsletter_links WHERE ?w", $_where);
    if (empty($link)) {
        $_data = array();
        $_data['url'] = $url;
        $_data['campaign_id'] = $campaign_id;
        $_data['newsletter_id'] = $newsletter_id;
        $_data['clicks'] = 0;

        return db_query("INSERT INTO ?:newsletter_links ?e", $_data);
    } else {
        return $link['link_id'];
    }
}

function fn_send_newsletter($to, $from, $subj, $body, $attachments = array(), $lang_code = CART_LANGUAGE, $reply_to = '')
{
    $reply_to = !empty($reply_to) ? $reply_to : 'default_company_newsletter_email';
    $_from = array(
        'email' => !empty($from['from_email']) ? $from['from_email'] : 'default_company_newsletter_email',
        'name' => !empty($from['from_name']) ? $from['from_name'] : (empty($from['from_email']) ? 'default_company_name' : '')
    );

    /** @var \Tygh\Mailer\Mailer $mailer */
    $mailer = Tygh::$app['mailer'];

    return $mailer->send(array(
        'to' => $to,
        'from' => $_from,
        'reply_to' => $reply_to,
        'data' => array(
            'body' => $body,
            'subject' => $subj
        ),
        'attachments' => $attachments,
        'template_code' => 'newsletters_newsletter',
        'tpl' => 'addons/newsletters/newsletter.tpl', // this parameter is obsolete and is used for back compatibility
    ), 'C', $lang_code, fn_get_newsletters_mailer_settings());
}

/**
 * Gets mailer settings for newsletters
 * @return array
 */
function fn_get_newsletters_mailer_settings()
{
    $mailer_settings = array();
    $settings = Registry::get('addons.newsletters');

    if ($settings['mailer_send_method'] !== 'default') {
        $mailer_settings = array(
            'mailer_send_method' => $settings['mailer_send_method'],
            'mailer_smtp_host' => $settings['mailer_smtp_host'],
            'mailer_smtp_username' => $settings['mailer_smtp_username'],
            'mailer_smtp_password' => $settings['mailer_smtp_password'],
            'mailer_smtp_ecrypted_connection' => $settings['mailer_smtp_ecrypted_connection'],
            'mailer_smtp_auth' => $settings['mailer_smtp_auth'],
        );
    }

    return $mailer_settings;
}

/**
* generate unsubscribe link. if list_id=0 and subscriber_id=0 - generate stub key for test email
*
* @param int $list_id - mailing list id
* @param int $subscriber_id
* @return string unsubscribe_link
*/
function fn_generate_unsubscribe_link($list_id, $subscriber_id)
{
    if ($list_id && $subscriber_id) {
        $unsubscribe_key = db_get_field("SELECT unsubscribe_key FROM ?:user_mailing_lists WHERE subscriber_id = ?i AND list_id = ?i", $subscriber_id, $list_id);
    } else {
        $unsubscribe_key = '0';
    }

    return fn_url("newsletters.unsubscribe?list_id=$list_id&s_id=$subscriber_id&key=$unsubscribe_key", 'C', 'http');
}

/**
* generate activation link. if list_id=0 and subscriber_id=0 - generate stub key for test email
*
* @param int $list_id - mailing list id
* @param int $subscriber_id
* @return string unsubscribe_link
*/
function fn_generate_activation_link($list_id, $subscriber_id)
{
    if ($list_id && $subscriber_id) {
        $activation_key = db_get_field("SELECT activation_key FROM ?:user_mailing_lists WHERE list_id=?i AND subscriber_id=?i", $list_id, $subscriber_id);
    } else {
        $activation_key = '0';
    }

    return fn_url("newsletters.activate?list_id=$list_id&key=$activation_key&s_id=$subscriber_id", 'C', 'http');
}

/**
* get list of mailing lists
*
* @param array $params - search parameters
* @param int $items_per_page
* @param string $lang_code - language code
* @return array
*/
function fn_get_mailing_lists($params = array(), $items_per_page = 0, $lang_code = CART_LANGUAGE)
{
    $default_params = array(
        'checkout' => false,
        'registration' => false,
        'sidebar' => false,
        'only_available' => true, // hide hidden and not available newsletters. We use 'false' for admin page
        'items_per_page' => $items_per_page
    );

    $params = array_merge($default_params, $params);

    $condition = '1';
    if ($params['checkout']) {
        $condition .= db_quote(" AND ?:mailing_lists.show_on_checkout = ?i", 1);
    }

    if ($params['registration']) {
        $condition .= db_quote(" AND ?:mailing_lists.show_on_registration = ?i", 1);
    }

    if ($params['only_available']) {
        $condition .= db_quote(" AND ?:mailing_lists.status = ?s", 'A');
    }

    $limit = '';
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_hash_array("SELECT COUNT(*) FROM ?:mailing_lists WHERE ?p", 'list_id', $condition);
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $mailing_lists = db_get_hash_array("SELECT * FROM ?:mailing_lists LEFT JOIN ?:common_descriptions ON ?:common_descriptions.object_id = ?:mailing_lists.list_id AND ?:common_descriptions.object_holder = 'mailing_lists' AND ?:common_descriptions.lang_code = ?s WHERE ?p $limit", 'list_id', $lang_code, $condition);

    return array($mailing_lists, $params);
}

/**
 * Gets all recipients for newsletters
 *
 * @param array $params
 *
 * @return array
 */
function fn_newsletters_get_recipients(array $params)
{
    $list_recipients = $user_recipients = $abandoned_recipients = [];

    if (!empty($params['mailing_lists'])) {
        $list_recipients = db_get_array(
            'SELECT 0 as user_id, subscribers.email, subscribers.lang_code, mailing_lists.list_id , subscribers.subscriber_id FROM ?:subscribers AS subscribers'
            . ' LEFT JOIN ?:user_mailing_lists AS user_mailing_lists'
                . ' ON subscribers.subscriber_id = user_mailing_lists.subscriber_id'
            . ' LEFT JOIN ?:mailing_lists AS mailing_lists'
                . ' ON user_mailing_lists.list_id = mailing_lists.list_id'
            . ' WHERE user_mailing_lists.list_id IN (?n)'
                . ' AND (user_mailing_lists.confirmed = ?i OR mailing_lists.register_autoresponder = ?i)'
                . ' AND mailing_lists.status IN (?a)'
            . ' GROUP BY subscribers.subscriber_id',
            $params['mailing_lists'],
            1, 0,
            [ObjectStatuses::ACTIVE, ObjectStatuses::HIDDEN]
        );
    }

    if (!empty($params['users'])) {
        $users = fn_explode(',', $params['users']);
        $user_recipients = db_get_array('SELECT users.user_id, users.email, users.lang_code, NULL as list_id, NULL as subscriber_id FROM ?:users AS users WHERE users.user_id IN (?n)', $users);
    }

    if (!empty($params['abandoned_days'])) {
        $time = time() - (intval($params['abandoned_days']) * 24 * 60 * 60); // X days * 24 hours * 60 mins * 60 secs;
        $condition = db_quote('AND user_session_products.timestamp <= ?i', $time);
        if ($params['abandoned_type'] == 'cart') {
            $condition .= db_quote(' AND user_session_products.type = ?s', 'C');
        } elseif ($params['abandoned_type'] == 'wishlist') {
            $condition .= db_quote(' AND user_session_products.type = ?s', 'W');
        }

        if (fn_allowed_for('ULTIMATE') && !empty($params['abandoned_company_id'])) {
            $condition .= db_quote(' AND user_session_products.company_id = ?i', $params['abandoned_company_id']);
        }

        $abandoned_recipients = db_get_array(
            'SELECT users.user_id, users.email, users.lang_code, NULL as list_id, NULL as subscriber_id FROM ?:users AS users'
            . ' LEFT JOIN ?:user_session_products AS user_session_products'
                . ' ON (users.user_id = user_session_products.user_id)'
            . ' WHERE 1=1 ?p'
            . ' GROUP BY users.user_id',
            $condition
        );
    }

    return array_merge($list_recipients, $user_recipients, $abandoned_recipients);
}

/**
 * Gets send list from DB
 *
 * @param string    $key
 * @param int       $limit
 * @param int       $offset
 *
 * @return array
 */
function fn_newsletters_get_send_list($key, $limit, $offset)
{
    $limit = db_quote(' LIMIT ?i', $limit);
    $offset = db_quote(' OFFSET ?i', $offset);

    return db_get_hash_multi_array(
        'SELECT newsletter_batch_recipients.*, ?:mailing_lists.from_name, ?:mailing_lists.from_email, ?:mailing_lists.reply_to'
        . ' FROM ?:newsletter_batch_recipients as newsletter_batch_recipients'
        . ' LEFT JOIN ?:mailing_lists on newsletter_batch_recipients.list_id = ?:mailing_lists.list_id'
        . ' WHERE send_key = ?s'
        . ' ?p ?p',
        ['newsletter_id', 'send_list_id'],
        $key,
        $limit, $offset
    );
}

/**
 * Drops newsletter batch queue by key
 *
 * @param string $key
 */
function fn_newsletters_drop_newsletter_batch($key)
{
    db_query('DELETE FROM ?:newsletter_batch_recipients WHERE send_key = ?s', $key);
}

/**
 * Add recipients to newsletter batch queue
 *
 * @param array $recipient_list
 */
function fn_newsletters_add_batch_recipients(array $recipient_list)
{
    $recipient_list_chunks = array_chunk($recipient_list, 500);

    foreach ($recipient_list_chunks as $recipient_list_chunk) {
        $recipient_list_chunk = array_map(static function ($recipient) {
            // replace null values of list ID and subscriber ID for manually added subscribers
            if (!isset($recipient['list_id'])) {
                $recipient['list_id'] = 0;
            }
            if (!isset($recipient['subscriber_id'])) {
                $recipient['subscriber_id'] = 0;
            }
            return $recipient;
        }, $recipient_list_chunk);
        db_query('INSERT INTO ?:newsletter_batch_recipients ?m', $recipient_list_chunk);
    }
}

/**
* Save user mailing lists settings.
*
* @param int $subscriber_id
* @param array $user_list_ids
* @param mixed $confirmed - if passed, subscription status set to passed value, if null, depends on autoresponder
* @param array $force_notification
* @param string $lang_code
*/
function fn_update_subscriptions($subscriber_id, $user_list_ids = array(), $confirmed = NULL, $force_notification = array(), $lang_code = CART_LANGUAGE)
{
    $subscription_succeed = false;
    $subscriber = array();

    if (!empty($user_list_ids)) {
        [$lists] = fn_get_mailing_lists();
        $subscriber = db_get_row("SELECT * FROM ?:subscribers WHERE subscriber_id = ?i", $subscriber_id);

        // to prevent user from subscribing to hidden and disabled mailing lists by manual link edit
        if (AREA != 'A') {
            foreach ($user_list_ids as $k => $l_id) {
                if (!isset($lists[$l_id]) || $lists[$l_id]['status'] != 'A') {
                    unset($user_list_ids[$k]);
                }
            }
        }

        $all_lists = array_column($lists, 'list_id');

        foreach ($user_list_ids as $list_id) {
            $subscribed = db_get_array("SELECT confirmed FROM ?:user_mailing_lists WHERE subscriber_id = ?i AND list_id = ?i", $subscriber_id, $list_id);

            $already_confirmed = !empty($subscribed['confirmed']) ? true : false;
            $already_subscribed = !empty($subscribed) ? true : false;

            if ($already_confirmed) {
                $_confirmed = 1;
            } else {
                if (is_array($confirmed)) {
                    $_confirmed = !empty($confirmed[$list_id]['confirmed']) ? $confirmed[$list_id]['confirmed'] : 0;
                } else {
                    $_confirmed = !empty($lists[$list_id]['register_autoresponder']) ? 0 : 1;
                }
            }

            if ($already_subscribed && $already_confirmed == $_confirmed) {
                continue;
            }

            $_data = array(
                'subscriber_id' => $subscriber_id,
                'list_id' => $list_id,
                'activation_key' => md5(uniqid(rand())),
                'unsubscribe_key' => md5(uniqid(rand())),
                'email' => $subscriber['email'],
                'timestamp' => TIME,
                'confirmed' => $_confirmed,
            );

            $subscription_succeed = true;

            db_replace_into('user_mailing_lists', $_data);

            // send confirmation email for each mailing list
            if (empty($_confirmed)) {
                fn_send_confirmation_email($subscriber_id, $list_id, $subscriber['email'], $lang_code);
            }
        }
    }

    // Delete unchecked mailing lists
    if (!empty($user_list_ids)) {
        $lists_to_delete = array_diff($all_lists, $user_list_ids);

        if (!empty($lists_to_delete)) {
            db_query("DELETE FROM ?:user_mailing_lists WHERE subscriber_id = ?i AND list_id IN (?n)", $subscriber_id, $lists_to_delete);

            // Delete subscriber in the frontend if all lists are unchecked
            if (AREA == 'C') {
                $c = db_get_field("SELECT COUNT(*) FROM ?:user_mailing_lists WHERE subscriber_id = ?i", $subscriber_id);

                if (empty($c)) {
                    db_query("DELETE FROM ?:subscribers WHERE subscriber_id = ?i", $subscriber_id);
                }
            }
        }

        // Delete subscriber in the frontend area if all lists are unchecked
    } else {
        fn_delete_subscribers(array($subscriber_id), (AREA == 'C'));
    }

    $params = array(
        'subscribed' => $subscription_succeed,
    );

    /**
     * Allows to perform some actions after user subscriptions data is processed
     *
     * @param int   $subscriber_id Subscriber id
     * @param array $user_list_ids Subscription ids that user wants be subscribed to
     * @param array $subscriber    Subscriber data
     * @param array $params        Parameters
     */
    fn_set_hook('newsletters_update_subscriptions_post', $subscriber_id, $user_list_ids, $subscriber, $params);
}

function fn_delete_subscribers($subscriber_ids, $delete_user = true, $all_mailing_lists = array())
{
    $condition = "";
    if (!empty($all_mailing_lists)) {
        $condition = db_quote("AND list_id IN (?n)", $all_mailing_lists);

        $all_list = db_get_fields("SELECT list_id FROM ?:user_mailing_lists WHERE subscriber_id IN (?n)", $subscriber_ids);
        $mailing_lists = db_get_fields("SELECT list_id FROM ?:user_mailing_lists WHERE subscriber_id IN (?n) ?p", $subscriber_ids, $condition);

        if (count($all_list) > count($mailing_lists)) {
            $delete_user = false;
        }
    }

    // Only Root can peform this action or owner
    if (!empty($subscriber_ids) && (!Registry::get('runtime.company_id') || AREA == 'C') ) {
        if ($delete_user == true) {
            db_query("DELETE FROM ?:subscribers WHERE subscriber_id IN (?n)", $subscriber_ids);
        }
        db_query("DELETE FROM ?:user_mailing_lists WHERE subscriber_id IN (?n) ?p", $subscriber_ids, $condition);
    }
}

function fn_send_confirmation_email($subscriber_id, $list_id, $email, $lang_code = CART_LANGUAGE)
{
    $list = fn_get_mailing_list_data($list_id);
    if ($list['register_autoresponder']) {
        $autoresponder = fn_get_newsletter_data($list['register_autoresponder']);

        $body = $autoresponder['body_html'];

        $body = fn_render_newsletter($body, array('list_id' => $list_id, 'subscriber_id' => $subscriber_id, 'email' => $email));

        if (AREA == 'A') {
            fn_echo(__('sending_email_to', array(
                    '[email]' => $email
                )) . '<br />');
        }

        fn_send_newsletter($email, $list, $autoresponder['newsletter'], $body, array(), $lang_code, $list['reply_to']);
    }
}

function fn_render_newsletter($body, $subscriber)
{
    // prepare placeholder values
    if (!empty($subscriber['list_id']) && !empty($subscriber['subscriber_id'])) {
        $values['%UNSUBSCRIBE_LINK'] = fn_generate_unsubscribe_link($subscriber['list_id'], $subscriber['subscriber_id']);
        $values['%ACTIVATION_LINK'] = fn_generate_activation_link($subscriber['list_id'], $subscriber['subscriber_id']);
    } else {
        $values['%UNSUBSCRIBE_LINK'] = $values['%ACTIVATION_LINK'] = empty($subscriber['user_id']) ? ('[' . __('link_message_for_test_letter') . ']') : '';
    }
    $values['%SUBSCRIBER_EMAIL'] = $subscriber['email'];
    $values['%COMPANY_NAME'] = Registry::get('settings.Company.company_name');
    $values['%COMPANY_ADDRESS'] = Registry::get('settings.Company.company_address');
    $values['%COMPANY_PHONE'] = Registry::get('settings.Company.company_phone');

    return strtr($body, $values);

}

//
// Generate navigation
//
function fn_newsletters_generate_sections($section)
{
    Registry::set('navigation.dynamic.sections', array (
        'N' => array (
            'title' => __('newsletters'),
            'href' => 'newsletters.manage?type=' . NEWSLETTER_TYPE_NEWSLETTER,
        ),
        'T' => array (
            'title' => __('templates'),
            'href' => 'newsletters.manage?type=' . NEWSLETTER_TYPE_TEMPLATE,
        ),
        'A' => array (
            'title' => __('autoresponders'),
            'href' => 'newsletters.manage?type=' . NEWSLETTER_TYPE_AUTORESPONDER,
        ),
        'C' => array (
            'title' => __('campaigns'),
            'href' => 'newsletters.campaigns',
        ),
        'mailing_lists' => array (
            'title' => __('mailing_lists'),
            'href' => 'mailing_lists.manage',
        ),
        'subscribers' => array (
            'title' => __('subscribers'),
            'href' => 'subscribers.manage',
        ),
    ));
    Registry::set('navigation.dynamic.active_section', $section);

    return true;
}

function fn_get_shared_companies($mailing_lists)
{
    if (!empty($mailing_lists)) {
        foreach ($mailing_lists as $list_id => $list_data) {
            $shared_for_companies = db_get_fields("SELECT share_company_id FROM ?:ult_objects_sharing WHERE share_object_type = 'mailing_lists' AND share_object_id = ?i", $list_id);
            $mailing_lists[$list_id]['shared_for_companies'] = array(0 => __('ult_shared_with'));
            if (!empty($shared_for_companies)) {
                foreach ($shared_for_companies as $company_id) {
                    $mailing_lists[$list_id]['shared_for_companies'][] = fn_get_company_name($company_id);
                }
            }
        }
    }

    return $mailing_lists;
}

function fn_newsletters_get_predefined_statuses(&$type, &$statuses)
{
    if ($type == 'newsletters') {
        $statuses['newsletters'] = array(
            'A' => __('active'),
            'D' => __('disabled'),
            'S' => __('sent')
        );
    }
}

/**
 * The "tools_update_status_before_query" hook handler.
 *
 * Actions performed:
 *  - Prevents changing status to Disabled/Sent for autoresponders that are already used in mailing lists
 *
 * @param array  $params      Object search parameters
 * @param string $old_status  Old object status
 * @param array  $status_data Updated object data
 * @param array  $condition   Update condition
 *
 * @see \fn_tools_update_status()
 *
 * @return void
 *
 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
 */
function fn_newsletters_tools_update_status_before_query(array $params, $old_status, array $status_data, array &$condition)
{
    if (
        empty($params['table']) || $params['table'] !== 'newsletters' || $params['id_name'] !== 'newsletter_id'
        || empty($params['id']) || empty($status_data['status']) || $status_data['status'] === ObjectStatuses::ACTIVE
    ) {
        return;
    }

    $used_in_mailing_lists = db_get_field('SELECT COUNT(*) FROM ?:mailing_lists WHERE register_autoresponder = ?s', $params['id']);

    if (!$used_in_mailing_lists) {
        return;
    }
    // Condition for "status update" query to result in false
    $condition['newsletter_id'] = 0;

    fn_set_notification(
        NotificationSeverity::WARNING,
        __('warning'),
        __('newsletters.autoresponder_used_in_mailing_list', ['[link]' => fn_url('mailing_lists.manage')])
    );
}

/*
* Promotions
*/
function fn_get_coupons_promotions($coupon = false)
{
    $params = array(
        'coupons' => true,
        'coupon_code' => $coupon,
        'active' => true,
        'zone' => 'cart',
        'sort_by' => 'priority',
        'sort_order' => 'asc'
    );

    [$promotions, $params] = fn_get_promotions($params);

    return $promotions;
}

function fn_emails_provide_coupon()
{
    $addon_info = Registry::get('addons.newsletters');

    if (!empty($addon_info['coupon']) && $addon_info['coupon'] != 'no_promo') {
        $promotions = fn_get_coupons_promotions($addon_info['coupon']);
        if (!empty($promotions)) {
            /** @var \Tygh\Mailer\Mailer $mailer */
            $mailer = Tygh::$app['mailer'];

            $promotion = reset($promotions);
            $to = $_REQUEST['subscribe_email'];
            $from = array(
                'email' => 'default_company_newsletter_email',
                'name' => 'default_company_name'
            );
            $subject = __('subscribers_promo_subject', array(
                '[promotion]' => $promotion['name'],
                '[coupon]' => $addon_info['coupon'],
            ));

            $mailer->send(array(
                'to' => $to,
                'from' => $from,
                'data' => array(
                    'promotion' => $promotion,
                    'coupon' => $addon_info['coupon'],
                    'subject' => $subject,
                    'url' => fn_url('promotions.list', 'C')
                ),
                'template_code' => 'newsletters_promotion',
                'tpl' => 'addons/newsletters/promotion.tpl',
            ), 'C', DESCR_SL, fn_get_newsletters_mailer_settings());
        }
    }
}

function fn_settings_variants_addons_newsletters_coupon()
{
    $promotions = fn_get_coupons_promotions();
    $result = array(
        'no_promo' => __('subscribers_no_promo')
    );

    if (!empty($promotions)) {
        foreach ($promotions as $promotion) {
            if (!empty($promotion['conditions_hash'])) {
                $conditions = explode(';',$promotion['conditions_hash']);
                foreach ($conditions as $condition) {
                    $condition = explode('=', $condition);
                    if ($condition[0] == 'coupon_code') {
                        $result[$condition[1]] = $promotion['name'] . ': ' . $condition[1];
                    }
                }
            }
        }
    }

    return $result;
}
function fn_subscribed_promo($promotion_id, $promotion, $auth)
{
    if (!empty($auth['user_id'])) {
        $subscriber_id = db_get_field("SELECT a.subscriber_id FROM ?:subscribers as a LEFT JOIN ?:users as b ON a.email = b.email WHERE b.user_id = ?i", $auth['user_id']);
    }

    return !empty($subscriber_id) ? 'Y' : 'N';
}

/**
 * Get list of subscribers
 *
 * @param array $params Query parameters
 * @param int $items_per_page Items per page
 * @param string $lang_code Two-letter language code
 *
 * @return array Subscribers with subscriptions
 */
function fn_get_subscribers($params, $items_per_page = 0, $lang_code = CART_LANGUAGE)
{
    // Init filter
    $params = LastView::instance()->update('subscribers', $params);

    // Set default values to input params
    $default_params = array (
        'page' => 1,
        'items_per_page' => $items_per_page
    );

    $params = array_merge($default_params, $params);

    // Define fields that should be retrieved
    $fields = array (
        '?:subscribers.subscriber_id',
        '?:subscribers.email',
        '?:subscribers.timestamp',
        '?:subscribers.subscriber_id',
        '?:subscribers.lang_code',
    );

    // Define sort fields
    $sortings = array (
        'email' => '?:subscribers.email',
        'timestamp' => '?:subscribers.timestamp'
    );

    $condition = '';

    $group_by = '?:subscribers.subscriber_id';

    $join = db_quote(' LEFT JOIN ?:user_mailing_lists ON ?:user_mailing_lists.subscriber_id = ?:subscribers.subscriber_id');

    if (isset($params['email']) && fn_string_not_empty($params['email'])) {
        $condition .= db_quote(' AND ?:subscribers.email LIKE ?l', '%' . trim($params['email']) . '%');
    }

    if (!empty($params['list_id'])) {
        $condition .= db_quote(' AND ?:user_mailing_lists.list_id = ?i', $params['list_id']);
    }

    if (!empty($params['confirmed'])) {
        $condition .= db_quote(' AND ?:user_mailing_lists.confirmed = ?i', ($params['confirmed'] == 'Y'));
    }

    if (!empty($params['language'])) {
        $condition .= db_quote(' AND ?:subscribers.lang_code = ?s', $params['language']);
    }

    if (!empty($params['period']) && $params['period'] != 'A') {
        [$params['time_from'], $params['time_to']] = fn_create_periods($params);

        $condition .= db_quote(' AND (?:subscribers.timestamp >= ?i AND ?:subscribers.timestamp <= ?i)', $params['time_from'], $params['time_to']);
    }

    $sorting = db_sort($params, $sortings, 'timestamp', 'desc');

    $limit = '';
    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field(
            'SELECT COUNT(DISTINCT(?:subscribers.subscriber_id)) FROM ?:subscribers ?p WHERE 1 ?p', $join, $condition
        );
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $subscribers = db_get_hash_array(
        'SELECT ' . implode(', ', $fields) . ' FROM ?:subscribers ?p WHERE 1 ?p GROUP BY ?p ?p ?p',
        'subscriber_id', $join, $condition, $group_by, $sorting, $limit
    );

    if (!empty($subscribers)) {
        $mailing_lists = db_get_hash_array(
            'SELECT GROUP_CONCAT(list_id) as list_ids, subscriber_id FROM ?:user_mailing_lists'
            . ' WHERE subscriber_id IN (?a) GROUP BY subscriber_id',
            'subscriber_id', array_keys($subscribers)
        );

        $subscribers = fn_array_merge($subscribers, $mailing_lists);
    }

    return array($subscribers, $params);
}

/**
 * Update subscriber and subscriptions
 *
 * @param array $subscriber_data Subscriber data
 * @param int $subscriber_id Subscriber identifier
 *
 * @return int Subscriber identifier
 */
function fn_update_subscriber($subscriber_data, $subscriber_id = 0)
{
    $invalid_emails = array();

    if (empty($subscriber_data['list_ids'])) {
        $subscriber_data['list_ids'] = array();
    }
    if (empty($subscriber_data['mailing_lists'])) {
        $subscriber_data['mailing_lists'] = array();
    }

    $subscriber_data['list_ids'] = array_filter($subscriber_data['list_ids']);
    $subscriber_data['mailing_lists'] = array_filter($subscriber_data['mailing_lists']);

    if (empty($subscriber_id)) {
        if (!empty($subscriber_data['email'])) {
            if ($existing_subscriber_id = fn_get_subscriber_id_by_email($subscriber_data['email'])) {
                $existing_subscriptions = db_get_fields("SELECT list_id FROM ?:user_mailing_lists WHERE subscriber_id = ?i", $existing_subscriber_id);
                $subscriber_id = $existing_subscriber_id;

                $can_continue = true;
                $reason = '';
                if (empty($subscriber_data['list_ids'])) {
                    // adding new subscriber
                    $can_continue = false;
                    $reason = __('ne_warning_subscr_email_exists', array(
                        '[email]' => $subscriber_data['email']
                    ));
                } elseif (array_intersect($subscriber_data['list_ids'], $existing_subscriptions)) {
                    // adding subscriber into list
                    $can_continue = false;
                    $reason = __('addons.newsletters.email_exists_in_list', array(
                        '[email]' => $subscriber_data['email']
                    ));
                }

                if (!$can_continue) {
                    fn_set_notification('W', __('warning'), $reason);
                    return $existing_subscriber_id;
                }

                $subscriber_data['list_ids'] = array_unique(array_merge($existing_subscriptions, $subscriber_data['list_ids']));

            } else {
                if (fn_validate_email($subscriber_data['email']) == false) {
                    $invalid_emails[] = $subscriber_data['email'];
                } else {
                    $subscriber_data['timestamp'] = TIME;
                    $subscriber_id = db_query("INSERT INTO ?:subscribers ?e", $subscriber_data);
                }
            }
        }
    } else {
        db_query("UPDATE ?:subscribers SET ?u WHERE subscriber_id = ?i", $subscriber_data, $subscriber_id);
    }

    fn_update_subscriptions($subscriber_id, $subscriber_data['list_ids'], isset($subscriber_data['confirmed']) ? $subscriber_data['confirmed'] : $subscriber_data['mailing_lists'], fn_get_notification_rules($subscriber_data), $subscriber_data['lang_code']);

    if (!empty($invalid_emails)) {
        fn_set_notification('E', __('error'), __('error_invalid_emails', array(
            '[emails]' => implode(', ', $invalid_emails)
        )));
    }

    return $subscriber_id;
}

/**
 * Get subscriber identifier by email
 *
 * @param string $email Subscriber email
 *
 * @return int Subscriber identifier
 */
function fn_get_subscriber_id_by_email($email = '')
{
    $subscriber_id = intval(db_get_field("SELECT subscriber_id FROM ?:subscribers WHERE email = ?s", $email));
    return $subscriber_id;
}
/**
 * Get subscriber email by user identifier
 *
 * @param int $user_id User identifier
 *
 * @return string Subscriber email
 */
function fn_newsletters_get_subscriber_email_by_user_id($user_id)
{
    $email = db_get_field("SELECT email FROM ?:users WHERE user_id = ?i", $user_id);
    return $email;
}

/**
 * Hook handler for GPDR add-on: saves accepted email subscription agreement to the log
 */
function fn_gdpr_newsletters_update_subscriptions_post($subscriber_id, $user_list_ids, $subscriber, $params)
{
    if (AREA !== 'C') {
        return false;
    }

    if (!empty($params['subscribed'])) {
        $params = array(
            'email' => isset($subscriber['email']) ? $subscriber['email'] : '',
            'user_id' => Tygh::$app['session']['auth']['user_id'],
        );

        fn_gdpr_save_user_agreement($params, 'newsletters_subscribe');
    }
}

/**
 * Hook handler for Recaptcha verification
 */
function fn_newsletters_settings_variants_image_verification_use_for(&$objects)
{
    $objects['newsletters'] = __('newsletters.use_for_subscribe_form');
}

/**
 * Creates default mailing list
 *
 * @return int
 */
function fn_newsletters_create_default_mailing_list()
{
    $data = [
        'name'                   => __('newsletters.default_mailing_list'),
        'from_name'              => '',
        'from_email'             => Registry::get('settings.Company.company_users_department'),
        'reply_to'               => Registry::get('settings.Company.company_users_department'),
        'register_autoresponder' => false,
        'show_on_checkout'       => false,
        'show_on_registration'   => false,
        'status'                 => ObjectStatuses::ACTIVE,
    ];

    return fn_update_mailing_list($data, 0, DESCR_SL);
}

/**
 * Updates/creates mailing list
 *
 * @param array<string, string|int|bool|array> $mailing_list_data Mailing list data
 * @param int                                  $list_id           Mailing list identifier
 * @param string                               $lang_code         Two-letter language code
 *
 * @return int
 */
function fn_update_mailing_list(array $mailing_list_data, $list_id, $lang_code = DESCR_SL)
{
    if (empty($list_id)) {
        $list_id = db_query('INSERT INTO ?:mailing_lists ?e', $mailing_list_data);

        $_data = $mailing_list_data;
        $_data['object_id'] = $list_id;
        $_data['object_holder'] = 'mailing_lists';
        $_data['object'] = $_data['name'];

        $languages = array_keys(Languages::getAll());

        foreach ($languages as $_data['lang_code']) {
            db_query('REPLACE INTO ?:common_descriptions ?e', $_data);
        }
    } else {
        db_query('UPDATE ?:mailing_lists SET ?u WHERE list_id = ?i', $mailing_list_data, $list_id);

        if (isset($mailing_list_data['name'])) {
            $_data = $mailing_list_data;
            $_data['object_id'] = $list_id;
            $_data['object_holder'] = 'mailing_lists';
            $_data['object'] = $_data['name'];

            $_where = [
                'object_id'     => $list_id,
                'object_holder' => 'mailing_lists',
                'lang_code'     => $lang_code
            ];

            db_query('UPDATE ?:common_descriptions SET ?u WHERE ?w', $_data, $_where);
        }
    }

    if (!empty($mailing_list_data['add_subscribers'])) {
        foreach ((array) $mailing_list_data['add_subscribers'] as $subscriber) {
            $subscriber_id = db_get_field('SELECT subscriber_id FROM ?:subscribers WHERE email = ?s', $subscriber['email']);

            // check if subscriber exists already
            if (!$subscriber_id) {
                $_data = $subscriber;
                $_data['timestamp'] = TIME;
                $subscriber_id  = db_query('INSERT INTO ?:subscribers ?e', $_data);
            }

            // mark mailing list as active for this subscriber
            $_data = [
                'subscriber_id' => $subscriber_id,
                'list_id'       => $list_id,
                'confirmed'     => $subscriber['confirmed'],
                'timestamp'     => TIME
            ];

            db_replace_into('user_mailing_lists', $_data);
        }
    }

    return (int) $list_id;
}

/**
 * Deletes mailing lists.
 *
 * @param int[] $list_ids List identifiers
 *
 * @return void
 */
function fn_newsletters_delete_mailing_lists(array $list_ids)
{
    db_query('DELETE FROM ?:common_descriptions WHERE object_id IN (?n) AND object_holder = ?s', $list_ids, 'mailing_lists');
    db_query('DELETE FROM ?:mailing_lists WHERE list_id IN (?n)', $list_ids);
    db_query('DELETE FROM ?:user_mailing_lists WHERE list_id IN (?n)', $list_ids);
}

/**
 * Deletes a newsletter
 *
 * @param int $newsletter_id Newsletter ID
 *
 * @return void
 */
function fn_delete_newsletter($newsletter_id)
{
    db_query('DELETE FROM ?:newsletters WHERE newsletter_id = ?i', $newsletter_id);
    db_query('DELETE FROM ?:newsletter_descriptions WHERE newsletter_id = ?i', $newsletter_id);
}
