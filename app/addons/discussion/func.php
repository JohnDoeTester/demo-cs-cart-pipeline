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

use Tygh\Addons\Discussion\Notifications\EventIdProviders\DiscussionProvider;
use Tygh\Enum\Addons\Discussion\DiscussionObjectTypes;
use Tygh\Enum\Addons\Discussion\DiscussionTypes;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\ReceiverSearchMethods;
use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Enum\YesNo;
use Tygh\Navigation\LastView;
use Tygh\Notifications\Receivers\SearchCondition;
use Tygh\Registry;
use Tygh\Settings;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Checks the edition and builds the company condition for discussion tables if necessary
 *
 * @param string     $field      Name of the table company field
 * @param int|string $company_id Company identifier if empty then will be defined by runtime
 *
 * @return string Part of sql query which is checked company condition.
 */
function fn_get_discussion_company_condition($field, $company_id = '')
{
    if (fn_allowed_for('ULTIMATE')) {
        return fn_get_company_condition($field, true, $company_id);
    }

    return '';
}

/**
 * Fetches list of disscussions.
 *
 * @param array $params Request parameters
 * @param int $items_per_page Amount of posts per page
 *
 * @return array List of posts
 */
function fn_get_discussions($params, $items_per_page = 0)
{
    /**
     * Executes at the beginning of the function, allowing you to modify the arguments passed to the function.
     *
     * @param array $params         Request parameters
     * @param int   $items_per_page Amount of posts per page
     */
    fn_set_hook('get_discussions_pre', $params, $items_per_page);

    // Init filter
    $params = LastView::instance()->update('discussion', $params);

    // Set default values to input params
    $default_params = [
        'page'           => 1,
        'items_per_page' => $items_per_page,
        'company_id'     => '',
    ];

    $params = array_merge($default_params, $params);

    // Define fields that should be retrieved
    $fields = array(
        '?:discussion_posts.*',
        '?:discussion_messages.message',
        '?:discussion_rating.rating_value',
        '?:discussion.*'
    );

    // Define sort fields
    $sortings = array(
        'object' => "?:discussion.object_type",
        'name' => "?:discussion_posts.name",
        'ip_address' => "?:discussion_posts.ip_address",
        'timestamp' => "?:discussion_posts.timestamp",
        'status' => "?:discussion_posts.status",
    );

    $sorting = db_sort($params, $sortings, 'timestamp', 'desc');

    $condition = $join = $limit = '';

    if (!empty($params['user_id'])) {
        $condition .= db_quote(' AND ?:discussion_posts.user_id IN (?n)', (array) $params['user_id']);
    }

    if (isset($params['name']) && fn_string_not_empty($params['name'])) {
        $condition .= db_quote(" AND ?:discussion_posts.name LIKE ?l", "%" . trim($params['name']) . "%");
    }

    if (isset($params['message']) && fn_string_not_empty($params['message'])) {
        $condition .= db_quote(" AND ?:discussion_messages.message LIKE ?l", "%" . trim($params['message']) . "%");
    }

    if (!empty($params['type'])) {
        $condition .= db_quote(" AND ?:discussion.type = ?s", $params['type']);
    }

    if (!empty($params['status'])) {
        $condition .= db_quote(" AND ?:discussion_posts.status = ?s", $params['status']);
    }

    if (!empty($params['post_id'])) {
        $condition .= db_quote(" AND ?:discussion_posts.post_id = ?i", $params['post_id']);
    }

    if (isset($params['ip_address']) && fn_string_not_empty($params['ip_address'])) {
        $condition .= db_quote(" AND ?:discussion_posts.ip_address = ?s", fn_ip_to_db(trim($params['ip_address'])));
    }

    if (!empty($params['rating_value'])) {
        $condition .= db_quote(" AND ?:discussion_rating.rating_value = ?i", $params['rating_value']);
    }

    if (!empty($params['object_type'])) {
        $condition .= db_quote(" AND ?:discussion.object_type = ?s", $params['object_type']);
    }

    if (!empty($params['object_id'])) {
        $condition .= db_quote(" AND ?:discussion.object_id = ?i", $params['object_id']);
    }

    $condition .= fn_get_company_condition('?:discussion.company_id', true, $params['company_id']);

    if (!empty($params['period']) && $params['period'] != 'A') {
        list($params['time_from'], $params['time_to']) = fn_create_periods($params);
        $condition .= db_quote(" AND (?:discussion_posts.timestamp >= ?i AND ?:discussion_posts.timestamp <= ?i)", $params['time_from'], $params['time_to']);
    }

    $join .= " INNER JOIN ?:discussion ON ?:discussion.thread_id = ?:discussion_posts.thread_id";
    $join .= " INNER JOIN ?:discussion_messages ON ?:discussion_messages.post_id = ?:discussion_posts.post_id";
    $join .= " INNER JOIN ?:discussion_rating ON ?:discussion_rating.post_id = ?:discussion_posts.post_id";

    $fields = implode(',', $fields);

    /**
     * Executes right before performing discussions-fetching query, allowing you to modify the SQL-query.
     *
     * @param array  $params         Request parameters
     * @param int    $items_per_page Amount of posts per page
     * @param string $field_list     String of comma-separated SQL fields to be selected in an SQL-query
     * @param string $join           String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $condition      String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string $sorting        String containing the SQL-query ORDER BY field
     * @param string $limit          String containing the SQL-query LIMIT field
     */
    fn_set_hook('get_discussions', $params, $items_per_page, $fields, $join, $condition, $sorting, $limit);

    if (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:discussion_posts $join WHERE 1 $condition");
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $posts = db_get_array(
        "SELECT ?p FROM ?:discussion_posts ?p WHERE 1 ?p ?p ?p", $fields, $join, $condition, $sorting, $limit
    );

    foreach ($posts as $k => $post) {
        if (isset($post['ip_address'])) {
            $posts[$k]['ip_address'] = fn_ip_from_db($post['ip_address']);
        }
    }

    /**
     * Executes after all discussions were fetched from DB and all data post-processing was done.
     *
     * @param array  $params         Request parameters
     * @param int    $items_per_page Amount of posts per page
     * @param array  $posts          List of posts in discussion
     */
    fn_set_hook('get_discussions_post', $params, $items_per_page, $posts);

    return array($posts, $params);
}

/**
 * Fetches discussion related to specified object.
 *
 * @param int        $object_id   Object identifier
 * @param string     $object_type One-letter object type identifier
 * @param bool       $get_posts   If true, posts in discussion will be fetched
 * @param array      $params      Extra parameteres
 * @param int|string $company_id  Additional condition for searching discussion by company identifier
 *
 * @return array|bool Array containing discussion object, false if none found
 */
function fn_get_discussion($object_id, $object_type, $get_posts = false, array $params = [], $company_id = '')
{
    static $cache = [];

    /**
     * Executes at the beginning of the function, allowing you to modify the arguments passed to the function.
     *
     * @param int    $object_id   Object identifier
     * @param string $object_type One-letter object type identifier
     * @param bool   $get_posts   If true, posts in discussion will be fetched
     * @param array  $params      Extra parameteres
     */
    fn_set_hook('get_discussion_pre', $object_id, $object_type, $get_posts, $params);

    $_cache_key = $object_id . '_' . $object_type;

    if (empty($cache[$_cache_key])) {
        $field_list = [
            'object_id',
            'thread_id',
            'type',
            'object_type'
        ];
        if (!fn_allowed_for('MULTIVENDOR')) {
            $field_list[] = 'company_id';
        }
        $join = $order_by = $limit = '';
        $condition = db_quote(" AND object_id = ?i AND object_type = ?s ?p", $object_id, $object_type, fn_get_discussion_company_condition('?:discussion.company_id', $company_id));

        $field_list = implode(', ', $field_list);

        /**
         * Executes right before performing discussion-fetching query, allowing you to modify the SQL-query.
         *
         * @param int    $object_id    Object identifier
         * @param string $object_type  One-letter object type identifier
         * @param bool   $get_posts    If true, posts in discussion will be fetched
         * @param array  $params       Extra parameteres
         * @param string $field_list   String of comma-separated SQL fields to be selected in an SQL-query
         * @param string $join         String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
         * @param string $condition    String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
         * @param string $order_by     String containing the SQL-query ORDER BY field
         * @param string $limit        String containing the SQL-query LIMIT field
         */
        fn_set_hook('get_discussion_before_sql', $object_id, $object_type, $get_posts, $params, $field_list, $join, $condition, $order_by, $limit);

        $discussion = db_get_row('SELECT ?p FROM ?:discussion ?p WHERE 1 ?p ?p ?p', $field_list, $join, $condition, $order_by, $limit);

        if (empty($discussion) && $object_type === DiscussionObjectTypes::COMPANY) {
            $company_discussion_type = Registry::ifGet('addons.discussion.company_discussion_type', DiscussionTypes::TYPE_DISABLED);
            if ($company_discussion_type !== DiscussionTypes::TYPE_DISABLED) {
                $discussion = [
                    'object_type' => DiscussionObjectTypes::COMPANY,
                    'object_id'   => $object_id,
                    'type'        => $company_discussion_type,
                    'company_id'  => empty($params['company_id']) ? 0 : $params['company_id'],
                ];

                if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.company_id')) {
                    $discussion['company_id'] = Registry::get('runtime.company_id');
                }

                $discussion['thread_id'] = db_query('INSERT INTO ?:discussion ?e', $discussion);
            }
        }

        if (!empty($discussion) && SiteArea::isStorefront(AREA) && $object_type === DiscussionObjectTypes::COMPANY) {
            $cannot_detect_if_user_bought_from_vendor = !isset(Tygh::$app['session']['auth']['user_id'])
                && YesNo::toBool(Registry::ifGet('addons.discussion.company_only_buyers', YesNo::YES));

            if (
                $cannot_detect_if_user_bought_from_vendor
                || !fn_discussion_is_user_eligible_to_write_review_for_company(Tygh::$app['session']['auth']['user_id'], $object_id)
            ) {
                $discussion['disable_adding'] = true;
            }
        }

        /**
         * Executes after discussion was fetched from DB before performing data post-processing.
         *
         * @param int    $object_id   Object identifier
         * @param string $object_type One-letter object type identifier
         * @param array  $discussion  Discussion object, false if none found
         */
        fn_set_hook('get_discussion', $object_id, $object_type, $discussion);

        $cache[$_cache_key] = $discussion;
    }

    if (!empty($cache[$_cache_key]) && !isset($cache[$_cache_key]['posts']) && $get_posts == true) {
        $params['thread_id'] = $cache[$_cache_key]['thread_id'];
        $params['avail_only'] = (AREA == 'C'); // FIXME

        $discussion_object_types = fn_get_discussion_objects();

        list($cache[$_cache_key]['posts'], $cache[$_cache_key]['search']) = fn_get_discussion_posts($params, Registry::get('addons.discussion.' . $discussion_object_types[$cache[$_cache_key]['object_type']] . '_posts_per_page'));

        $cache[$_cache_key]['average_rating'] = fn_discussion_get_average_object_rating($object_id, $object_type);
    }

    $saved_post_data = fn_restore_post_data('post_data');
    if (!empty($saved_post_data)) {
        $cache[$_cache_key]['post_data'] = $saved_post_data;
    }

    $discussion = !empty($cache[$_cache_key]) ? $cache[$_cache_key] : false;

    /**
     * Executes after discussion was fetched from DB and all data post-processing was done.
     *
     * @param int        $object_id   Object identifier
     * @param string     $object_type One-letter object type identifier
     * @param bool       $get_posts   If true, posts in discussion will be fetched
     * @param array      $params      Extra parameteres
     * @param array|bool Array containing discussion object, false if none found
     */
    fn_set_hook('get_discussion_post', $object_id, $object_type, $get_posts, $params, $discussion);

    return $discussion;
}

/**
 * Gets discussion posts
 *
 * @param array $params Request parameters
 * @param int $items_per_page Amount of posts per page
 * @return array Array with posts and query parameters
 */
function fn_get_discussion_posts($params, $items_per_page = 0)
{
    /**
     * Modifies request parameters for fetching discussion posts
     * @param array $params Query parameters
     * @param int $items_per_page Amount of posts per page
     */
    fn_set_hook('get_discussion_posts_pre', $params, $items_per_page);

    // Set default values to input params
    $default_params = array(
        'page' => 1,
        'thread_id' => 0,
        'avail_only' => false,
        'random' => false,
        'items_per_page' => $items_per_page
    );

    $params = array_merge($default_params, $params);

    $thread_data = db_get_row(
        "SELECT thread_id, type, object_type, object_id FROM ?:discussion WHERE thread_id = ?i ?p",
        $params['thread_id'], fn_get_discussion_company_condition('?:discussion.company_id')
    );

    if ($thread_data['type'] == 'D') {
        return array(array(), $params);
    }

    $condition = fn_generate_thread_condition($thread_data);

    $join = $fields = '';

    if ($thread_data['type'] == 'C' || $thread_data['type'] == 'B') {
        $join .= " LEFT JOIN ?:discussion_messages ON ?:discussion_messages.post_id = ?:discussion_posts.post_id ";
        $fields .= ", ?:discussion_messages.message";

        if ($thread_data['type'] == 'C') {
            $condition .= " AND ?:discussion_messages.message <> ''";
        }
    }

    if ($thread_data['type'] == 'R' || $thread_data['type'] == 'B') {
        $join .= " LEFT JOIN ?:discussion_rating ON ?:discussion_rating.post_id = ?:discussion_posts.post_id ";
        $fields .= ", ?:discussion_rating.rating_value";

        if ($thread_data['type'] == 'R') {
            $condition .= " AND ?:discussion_rating.rating_value > 0";
        }
    }

    if ($params['avail_only'] == true) {
        $condition .= " AND ?:discussion_posts.status = 'A'";
    }

    $limit = '';

    if (!empty($params['limit'])) {
        $limit = db_quote("LIMIT ?i", $params['limit']);

    } elseif (!empty($params['items_per_page'])) {
        $params['total_items'] = db_get_field("SELECT COUNT(*) FROM ?:discussion_posts $join WHERE $condition");
        $limit = db_paginate($params['page'], $params['items_per_page'], $params['total_items']);
    }

    $order_by = (isset($params['random']) && $params['random'] == 'Y')
        ? 'RAND()'
        : '?:discussion_posts.timestamp DESC';

    /**
     * Modifies discuission posts query
     *
     * @param array $params Query parameters
     * @param int $items_per_page Amount of posts per page
     * @param string $fields String of comma-separated SQL fields to be selected in an SQL-query
     * @param string $join String with the complete JOIN information (JOIN type, tables and fields) for an SQL-query
     * @param string $condition String containing SQL-query condition possibly prepended with a logical operator (AND or OR)
     * @param string $order_by String containing SQL-query ordering conditions
     * @param string $limit String containing SQL-query limit conditions
     */
    fn_set_hook('get_discussion_posts', $params, $items_per_page, $fields, $join, $condition, $order_by, $limit);

    $posts = db_get_array(
        "SELECT ?:discussion_posts.* $fields FROM ?:discussion_posts $join "
        . "WHERE $condition ORDER BY ?p $limit",
        $order_by
    );

    foreach ($posts as $k => $post) {
        if (isset($post['ip_address'])) {
            $posts[$k]['ip_address'] = fn_ip_from_db($post['ip_address']);
        }
    }

    /**
     * Modifies discussion posts and request parameters
     *
     * @param array $params Query parameters
     * @param int $items_per_page Amount of posts per page
     * @param array $posts Discussion posts
     */
    fn_set_hook('get_discussion_posts_post', $params, $items_per_page, $posts);

    return array($posts, $params);
}

function fn_generate_thread_condition($thread_data)
{
    $thread_condition = '';

    if (AREA == 'C') {
        if ($thread_data['object_type'] == 'P') {
            $thread_condition = fn_generate_thread_condition_by_setting('product_share_discussion', $thread_data);
        } elseif ($thread_data['object_type'] == 'A') {
            $thread_condition = fn_generate_thread_condition_by_setting('page_share_discussion', $thread_data);
        } elseif ($thread_data['object_type'] == 'E') {
            $thread_condition = fn_generate_thread_condition_by_setting('testimonials_from_all_stores', $thread_data);
        }
    }

    if (empty($thread_condition)) {
        $thread_condition = db_quote("?:discussion_posts.thread_id = ?i", $thread_data['thread_id']);
    }

    return $thread_condition;
}

function fn_generate_thread_condition_by_setting($setting_name, $thread_data)
{
    if (!empty($thread_data['object_type']) && isset($thread_data['object_id'])) {
        if (Registry::ifGet('addons.discussion.' . $setting_name, 'N') == 'Y') {
            return  db_quote(
                "?:discussion_posts.thread_id IN (?n)",
                db_get_fields(
                    "SELECT thread_id FROM ?:discussion WHERE object_type = ?s AND object_id = ?i",
                    $thread_data['object_type'], $thread_data['object_id']
                )
            );
        }
    }

    return '';
}

/**
 * Deletes discussion for object.
 *
 * @param int|array<int> $object_id   Discussed object identifier
 * @param string         $object_type Discussed object type
 * @param int|null       $company_id  Identifier of a company that owns the specified discussion object
 *
 * @return bool True if discussion is deleted
 */
function fn_delete_discussion($object_id, $object_type, $company_id = null)
{
    /**
     * Modifies discussed object properties.
     *
     * @param int|array<int> $object_id   Discussed object identifier
     * @param string         $object_type Discussed object type
     * @param int|null       $company_id  Identifier of a company that owns the specified discussion object
     */
    fn_set_hook('delete_discussion_pre', $object_id, $object_type, $company_id);

    $conditions = [
        'object_id' => (array) $object_id,
        'object_type' => $object_type,
    ];
    if ($company_id) {
        $conditions['company_id'] = (int) $company_id;
    }

    $thread_id = db_get_field('SELECT thread_id FROM ?:discussion WHERE ?w', $conditions);

    $is_deleted = false;

    if (!empty($thread_id)) {
        db_query('DELETE FROM ?:discussion_messages WHERE thread_id = ?i', $thread_id);
        db_query('DELETE FROM ?:discussion_posts WHERE thread_id = ?i', $thread_id);
        db_query('DELETE FROM ?:discussion_rating WHERE thread_id = ?i', $thread_id);
        db_query('DELETE FROM ?:discussion WHERE thread_id = ?i', $thread_id);

        $is_deleted = true;
    }

    /**
     * Modifies deletion results.
     *
     * @param int|array<int> $object_id   Discussed object identifier
     * @param string         $object_type Discussed object type
     * @param bool           $is_deleted  Deletion result
     * @param int|null       $company_id  Identifier of a company that owns the specified discussion object
     */
    fn_set_hook('delete_discussion_post', $object_id, $object_type, $is_deleted, $company_id);

    return $is_deleted;
}

function fn_add_discussion_post($post_data, $send_notifications = true)
{
    $auth = &Tygh::$app['session']['auth'];
    $discussion_settings = Registry::get('addons.discussion');
    $discussion_object_types = fn_get_discussion_objects();

    $object = fn_discussion_get_object($post_data);

    if (empty($object) || !fn_discussion_check_thread_permissions($object, $auth)) {
        fn_set_notification('E', __('error'), __('cant_find_thread'));

        return false;
    }
    $post_data['thread_id'] = $object['thread_id'];
    $object_data = fn_get_discussion_object_data($object['object_id'], $object['object_type']);
    $object_name = $discussion_object_types[$object['object_type']];
    $post_data['status'] = 'A';

    if (!empty($auth['ip'])) {
        $post_data['ip_address'] = $auth['ip'];
    } else {
        $ip = fn_get_ip();
        $post_data['ip_address'] = $ip['host'];
    }

    // Check if post is permitted from this IP address
    if (AREA === 'C' && !fn_discussion_is_user_can_leave_review_from_ip($post_data['ip_address'], $object['object_type'], $object['thread_id'])) {
        fn_set_notification('E', __('error'), __('error_already_posted'));
        return false;
    }

    $post_data['ip_address'] = fn_ip_to_db($post_data['ip_address']);

    // Check if post needs to be approved
    if (AREA != 'A' && !empty($discussion_settings[$object_name . '_post_approval'])) {
        if (
            $discussion_settings[$object_name . '_post_approval'] == 'any'
            || ($discussion_settings[$object_name . '_post_approval'] == 'anonymous' && empty($auth['user_id']))
        ) {
            fn_set_notification('W', __('text_thank_you_for_post'), __('text_post_pended'));
            $post_data['status'] = 'D';
        }
    }

    if (!empty($post_data['date'])) {
        $post_data['timestamp'] = fn_parse_datetime($post_data['date'] . ' ' . $post_data['time']);
    } else {
        $post_data['timestamp'] = TIME;
    }

    // Validate rating value
    if (
        !empty($post_data['rating_value'])
        && !in_array($post_data['rating_value'], array_keys(fn_get_discussion_ratings()))
    ) {
        unset($post_data['rating_value']);
    }

    $post_data['user_id'] = $auth['user_id'];
    $post_data['post_id'] = db_query('INSERT INTO ?:discussion_posts ?e', $post_data);

    db_query('REPLACE INTO ?:discussion_messages ?e', $post_data);
    db_query('REPLACE INTO ?:discussion_rating ?e', $post_data);

    if ($send_notifications) {
        $fn_prepare_subject = function($type, $lang_code) {
            return __('discussion_title_' . $type, '', $lang_code) . ' - ' . __($type, '', $lang_code);
        };

        /** @var \Tygh\Notifications\EventDispatcher $event_dispatcher */
        $event_dispatcher = Tygh::$app['event.dispatcher'];

        $receivers = [
            UserTypes::ADMIN    => true,
            UserTypes::CUSTOMER => true,
        ];
        if (fn_allowed_for('MULTIVENDOR')) {
            $receivers[UserTypes::VENDOR] = true;
        }
        $receivers[$auth['user_type']] = false;

        /** @var \Tygh\Notifications\Settings\Factory $notification_settings_factory */
        $notification_settings_factory = Tygh::$app['event.notification_settings.factory'];
        $notification_rules = $notification_settings_factory->create($receivers);

        $url = "discussion_manager.manage?object_type={$object['object_type']}&post_id={$post_data['post_id']}";
        $lang_code = fn_get_company_language(Registry::get('runtime.company_id'));
        $discussion_data = [
            'post_id'       => $post_data['post_id'],
            'object'        => $object,
            'object_data'   => $object_data,
            'post_data'     => $post_data,
            'object_name'   => $object_name,
            'subject'       => $fn_prepare_subject($discussion_object_types[$object['object_type']], $lang_code),
            'url'           => $url,
        ];
        switch ($object['object_type']) {
            case DiscussionObjectTypes::ORDER:
                $order_info = db_get_row(
                    'SELECT email, company_id FROM ?:orders WHERE order_id = ?i',
                    $object['object_id']
                );

                $lang_code = Registry::get('settings.Appearance.backend_default_language');
                $discussion_data = [
                    'post_id'       => $post_data['post_id'],
                    'object'        => $object,
                    'object_data'   => $object_data,
                    'post_data'     => $post_data,
                    'object_name'   => $object_name,
                    'email'         => $order_info['email'],
                    'subject'       => $fn_prepare_subject($discussion_object_types[$object['object_type']], $lang_code),
                    'company_id'    => $order_info['company_id'],
                ];

                $event_dispatcher->dispatch(
                    'discussion.orders.new_post',
                    $discussion_data,
                    $notification_rules,
                    new DiscussionProvider($discussion_data)
                );

                break;
            case DiscussionObjectTypes::PAGE:
                $discussion_data['company_id'] = (int) fn_get_company_id('pages', 'page_id', $object['object_id']);
                $event_dispatcher->dispatch(
                    'discussion.pages.new_post',
                    $discussion_data,
                    $notification_rules,
                    new DiscussionProvider($discussion_data)
                );
                break;
            case DiscussionObjectTypes::CATEGORY:
                $discussion_data['company_id'] = (int) fn_get_company_id('categories', 'category_id', $object['object_id']);
                $event_dispatcher->dispatch(
                    'discussion.categories.new_post',
                    $discussion_data,
                    $notification_rules,
                    new DiscussionProvider($discussion_data)
                );
                break;
            case DiscussionObjectTypes::PRODUCT:
                $discussion_data['company_id'] = (int) fn_get_company_id('products', 'product_id', $object['object_id']);
                $event_dispatcher->dispatch(
                    'discussion.products.new_post',
                    $discussion_data,
                    $notification_rules,
                    new DiscussionProvider($discussion_data)
                );
                break;
            case DiscussionObjectTypes::TESTIMONIALS_AND_LAYOUT:
                $discussion_data['company_id'] = 0;
                $event_dispatcher->dispatch(
                    'discussion.testimonials.new_post',
                    $discussion_data,
                    $notification_rules,
                    new DiscussionProvider($discussion_data)
                );
                break;
            case DiscussionObjectTypes::COMPANY:
                $discussion_data['company_id'] = (int) $object['object_id'];
                $event_dispatcher->dispatch(
                    'discussion.vendors.new_post',
                    $discussion_data,
                    $notification_rules,
                    new DiscussionProvider($discussion_data)
                );
                break;
        }
    }
    /**
     * This hook allows you to access the data of discussions after that data was formatted, recorded to the database,
     * and sent to users by email.
     *
     * @param $post_data
     * @param $send_notifications
     */
    fn_set_hook('add_discussion_post_post', $post_data, $send_notifications);

    return $post_data['post_id'];
}

/**
 * The "update_product_post" hook handler.
 *
 * Actions performed:
 *  - Update discussion data
 *
 * @see fn_update_product
 */
function fn_discussion_update_product_post(&$product_data, &$product_id, $lang_code, $create)
{
    if (empty($product_id) || (empty($product_data['discussion_type']) && !$create)) {
        return;
    }

    $company_id = (int) fn_get_runtime_company_id();

    if (empty($company_id) && isset($product_data['company_id'])) {
        $company_id = (int) $product_data['company_id'];
    }

    if (empty($company_id)) {
        $company_id = (int) db_get_field('SELECT company_id FROM ?:products WHERE product_id = ?i', $product_id);
    }

    if (fn_allowed_for('ULTIMATE') && empty($company_id)) {
        return;
    }
    $product_data['company_id'] = (int) $company_id;

    $discussion_type = (empty($product_data['discussion_type']) || is_array($product_data['discussion_type']))
        ? fn_discussion_get_discussion_setting('product_discussion_type', $company_id)
        : (string) $product_data['discussion_type'];

    if (empty($discussion_type)) {
        return;
    }
    $product_data['discussion_type'] = $discussion_type;

    $discussion = [
        'object_type' => DiscussionObjectTypes::PRODUCT,
        'object_id'   => $product_id,
        'type'        => $product_data['discussion_type'],
        'company_id'  => $product_data['company_id']
    ];

    fn_update_discussion($discussion);
}

function fn_discussion_delete_product_post(&$product_id)
{
    return fn_delete_discussion($product_id, 'P');
}

/**
 * The "update_category_post" hook handler.
 *
 * Actions performed:
 *  - Sets discussion type by default from add-on settings during creating new category.
 *
 * @param array<string, int|string|array<string, int|string>> $category_data Category data
 * @param int                                                 $category_id   Category identifier
 * @param string                                              $lang_code     Two-letter language code (e.g. 'en', 'ru', etc.)
 * @param bool                                                $create        True if category was created, false otherwise
 *
 * @see fn_update_category()
 */
function fn_discussion_update_category_post(&$category_data, &$category_id, $lang_code, $create)
{
    if (empty($category_id) || (empty($category_data['discussion_type']) && !$create)) {
        return;
    }

    $company_id = (int) fn_get_runtime_company_id();

    if (empty($company_id) && !empty($category_data['company_id'])) {
        $company_id = (int) $category_data['company_id'];
    }

    if (empty($company_id)) {
        $company_id = (int) db_get_field(
            'SELECT company_id FROM ?:categories WHERE category_id = ?i',
            $category_id
        );
    }

    if (fn_allowed_for('ULTIMATE') && empty($company_id)) {
        return;
    }
    $category_data['company_id'] = (int) $company_id;

    $discussion_type = (empty($category_data['discussion_type']) || is_array($category_data['discussion_type']))
        ? fn_discussion_get_discussion_setting('category_discussion_type', $company_id)
        : (string) $category_data['discussion_type'];

    if (empty($discussion_type)) {
        return false;
    }
    $category_data['discussion_type'] = $discussion_type;

    $discussion = array(
        'object_type' => DiscussionObjectTypes::CATEGORY,
        'object_id'   => $category_id,
        'type'        => $category_data['discussion_type'],
        'company_id'  => $category_data['company_id']
    );

    fn_update_discussion($discussion);
}

function fn_discussion_delete_category_after(&$category_id)
{
    return fn_delete_discussion($category_id, 'C');
}

function fn_discussion_delete_order(&$order_id)
{
    return fn_delete_discussion($order_id, 'O');
}

/**
 * The "update_page_post" hook handler.
 *
 * Actions performed:
 *  - Sets discussion type by default from add-on settings during creating new page.
 *
 * @param array<string, int|string|array<string, int|string>> $page_data Page data
 * @param int                                                 $page_id   Page identifier, if equals zero new page will be created
 * @param string                                              $lang_code Two letters language code
 * @param bool                                                $create    True if page was created, false otherwise
 *
 * @see fn_update_page()
 */
function fn_discussion_update_page_post(&$page_data, &$page_id, $lang_code, $create)
{
    if (empty($page_id) || (empty($page_data['discussion_type']) && !$create)) {
        return;
    }

    $company_id = (int) fn_get_runtime_company_id();

    if (empty($company_id) && !empty($page_data['company_id'])) {
        $company_id = (int) $page_data['company_id'];
    }

    if (empty($company_id)) {
        $company_id = $page_data['company_id'] = (int) db_get_field(
            'SELECT company_id FROM ?:pages WHERE page_id = ?i',
            $page_id
        );
    }

    if (fn_allowed_for('ULTIMATE') && empty($company_id)) {
        return;
    }

    $page_data['company_id'] = (int) $company_id;

    $discussion_type = (empty($page_data['discussion_type']) || is_array($page_data['discussion_type']))
        ? fn_discussion_get_discussion_setting('page_discussion_type', $company_id)
        : (string) $page_data['discussion_type'];

    if (empty($discussion_type)) {
        return false;
    }

    $page_data['discussion_type'] = $discussion_type;

    $discussion = [
        'object_type'       => DiscussionObjectTypes::PAGE,
        'object_id'         => $page_id,
        'type'              => $page_data['discussion_type'],
        'company_id'        => $page_data['company_id'],
        'for_all_companies' => fn_allowed_for('ULTIMATE'),
    ];

    fn_update_discussion($discussion);
}

function fn_discussion_delete_page(&$page_id)
{
    return fn_delete_discussion($page_id, 'A');
}

function fn_discussion_update_event(&$event_data, &$event_id)
{
    if (empty($event_data['discussion_type'])) {
        return false;
    }

    $discussion = array(
        'object_type' => 'G',
        'object_id' => $event_id,
        'type' => $event_data['discussion_type']
    );

    fn_update_discussion($discussion);
}

// FIX-EVENTS
function fn_discussion_delete_event(&$event_id)
{
    return fn_delete_discussion($event_id, 'G');
}

//
// Get average rating
//
function fn_get_discussion_rating($rating_value)
{
    static $cache = array();

    if (!isset($cache[$rating_value])) {
        $cache[$rating_value] = array();
        $cache[$rating_value]['full'] = floor($rating_value);
        $cache[$rating_value]['part'] = $rating_value - $cache[$rating_value]['full'];
        $cache[$rating_value]['empty'] = 5 - $cache[$rating_value]['full'] - (($cache[$rating_value]['part'] == 0) ? 0 : 1);

        if (!empty($cache[$rating_value]['part'])) {
            if ($cache[$rating_value]['part'] <= 0.25) {
                $cache[$rating_value]['part'] = 1;
            } elseif ($cache[$rating_value]['part'] <= 0.5) {
                $cache[$rating_value]['part'] = 2;
            } elseif ($cache[$rating_value]['part'] <= 0.75) {
                $cache[$rating_value]['part'] = 3;
            } elseif ($cache[$rating_value]['part'] <= 0.99) {
                $cache[$rating_value]['part'] = 4;
            }
        }
    }

    return $cache[$rating_value];
}

/**
 * Returns average rating of an object taking into account:
 * - whether the object is shared or not
 * - reviews setting: Communication and Rating or just Rating
 * - discussion posts status - active
 *
 * @param $object_id
 * @param $object_type
 *
 * @return string
 */
function fn_discussion_get_average_object_rating($object_id, $object_type)
{
    if ($object_type === DISCUSSION_OBJECT_TYPE_PRODUCT) {
        $share_status = Registry::get('addons.discussion.product_share_discussion');
    } elseif ($object_type === DISCUSSION_OBJECT_TYPE_PAGE) {
        $share_status = Registry::get('addons.discussion.page_share_discussion');
    } else {
        $share_status = null;
    }

    $rating = db_get_field(
        'SELECT AVG (dr.rating_value) FROM ?:discussion_rating AS dr'
        . ' LEFT JOIN ?:discussion_posts AS dp ON dp.post_id = dr.post_id'
        . ' LEFT JOIN ?:discussion AS d ON d.thread_id = dp.thread_id'
        . ' WHERE d.object_id = ?i AND d.object_type = ?s AND type IN ( ?a ) ?p'
        . ' AND dp.status = ?s AND dr.rating_value > ?i',
        $object_id,
        $object_type,
        array(DISCUSSION_REVIEW_COMMUNICATION_AND_RATING, DISCUSSION_REVIEW_RATING),
        $share_status === 'Y' ? '' : fn_get_discussion_company_condition('d.company_id'),
        DISCUSSION_POST_STATUS_ACTIVE,
        0
    );

    return fn_discussion_round_rating($rating);
}

//
// Get thread average rating
//
function fn_get_average_rating($discussion)
{
    if (empty($discussion) || ($discussion['type'] != 'R' && $discussion['type'] != 'B')) {
        return false;
    }

    $rating = db_get_field("SELECT AVG(a.rating_value) as val FROM ?:discussion_rating as a LEFT JOIN ?:discussion_posts as b ON a.post_id = b.post_id WHERE a.thread_id = ?i AND b.status = 'A' AND a.rating_value > ?i", $discussion['thread_id'], 0);

    return fn_discussion_round_rating($rating);
}

function fn_get_discussion_object_data($object_id, $object_type, $lang_code = CART_LANGUAGE)
{
    $data = array();

    // product
    if ($object_type == 'P') {
        $data['description'] = db_get_field("SELECT product FROM ?:product_descriptions WHERE product_id = ?i AND lang_code = ?s", $object_id, $lang_code);
        if (AREA == 'A') {
            $data['url'] = "products.update?product_id=$object_id&selected_section=discussion";
        } else {
            $data['url'] = "products.view?product_id=$object_id";
        }
    } elseif ($object_type == 'C') { // category
        $data['description'] = db_get_field("SELECT category FROM ?:category_descriptions WHERE category_id = ?i AND lang_code = ?s", $object_id, $lang_code);
        if (AREA == 'A') {
            $data['url'] = "categories.update?category_id=$object_id&selected_section=discussion";
        } else {
            $data['url'] = "categories.view?category_id=$object_id";
        }

    } elseif ($object_type == 'M') { // company
        $data['description'] = fn_get_company_name($object_id);
        if (AREA == 'A') {
            $data['url'] = "companies.update?company_id=$object_id&selected_section=discussion";
        } else {
            $data['url'] = "companies.view?company_id=$object_id";
        }

    // order
    } elseif ($object_type == 'O') {
        $data['description'] = '#' . $object_id;
        if (AREA == 'A') {
            $data['url'] = "orders.details?order_id=$object_id&selected_section=discussion";
        } else {
            $data['url'] = "orders.details?order_id=$object_id";
        }

    // page
    } elseif ($object_type == 'A') {
        $data['description'] = db_get_field("SELECT page FROM ?:page_descriptions WHERE page_id = ?i AND lang_code = ?s", $object_id, $lang_code);

        if (AREA == 'A') {
            $data['url'] = "pages.update?page_id=$object_id&selected_section=discussion";
        } else {
            $data['url'] = "pages.view?page_id=$object_id";
        }

    // Site layout/testimonials
    } elseif ($object_type == 'E') {
        $data['description'] = __('discussion_title_home_page');
        if (AREA == 'A') {
            $data['url'] = "discussion.update?discussion_type=E";
        } else {
            $data['url'] = '';
        }
    }

    fn_set_hook('get_discussion_object_data', $data, $object_id, $object_type);

    return $data;
}

function fn_get_discussion_objects()
{
    static $discussion_object_types = array(
        'P' => 'product',
        'C' => 'category',
        'A' => 'page',
        'O' => 'order',
        'E' => 'home_page',
    );

    if (fn_allowed_for('MULTIVENDOR')) {
        $discussion_object_types['M'] = 'company';
    }

    fn_set_hook('get_discussion_objects', $discussion_object_types);

    return $discussion_object_types;
}

function fn_discussion_get_object($params)
{
    $condition = array();

    if (!empty($params['thread_id'])) {
        $condition[] = db_quote("thread_id = ?i", $params['thread_id']);
    }

    if (isset($params['object_id']) && !empty($params['object_type'])) {
        $condition[] = db_quote("object_id = ?i", $params['object_id']);
        $condition[] = db_quote("object_type = ?s", $params['object_type']);
    }

    if (!$condition) {
        return array();
    }

    return db_get_row(
        'SELECT * FROM ?:discussion WHERE ' . implode(' AND ', $condition)
    );
}

/**
 * Clones discussion
 *
 * @param int    $object_id     Object id to clone from
 * @param int    $new_object_id Object id to clone to
 * @param string $object_type   One-letter object type
 *
 * @return mixed
 */
function fn_clone_discussion($object_id, $new_object_id, $object_type)
{
    // Clone attachment
    $data = db_get_row('SELECT * FROM ?:discussion WHERE object_id = ?i AND object_type = ?s', $object_id, $object_type);

    if (empty($data)) {
        return false;
    }

    $data['object_id'] = $new_object_id;
    unset($data['thread_id']);
    $thread_id = db_query('REPLACE INTO ?:discussion ?e', $data);

    return $thread_id;
}

function fn_discussion_clone_product(&$product_id, &$to_product_id)
{
    fn_clone_discussion($product_id, $to_product_id, 'P');
}

function fn_get_rating_list($object_type, $parent_object_id = '')
{

    $object2parent_links = array(
        'P' => array(	//	for product
            'table' => '?:categories',
            'field' => 'category_id',
            'join' => array('?:products_categories' => "?:discussion.object_id=?:products_categories.product_id AND ?:products_categories.link_type='M'",
                            '?:categories' => "?:products_categories.category_id=?:categories.category_id"),
        )/*,
        'A' => array(	// for page
            'table' => '?:topics',
            'field' => 'topic_id',
            'join' => array('?:pages_topics' => "?:discussion.object_id=?:pages_topics.page_id AND ?:pages_topics.link_type='M'",
            '?:topics' => "?:pages_topics.topic_id=?:topics.topic_id"),
        )*/
    );

    $query = db_quote(" object_type = ?s AND ?:discussion.type IN ('R', 'B') AND !(?:discussion_rating.rating_value IS NULL) ", $object_type);
    $join = array();
    if (isset($object2parent_links[$object_type]) && !empty($parent_object_id)) {
        $path = db_get_field("SELECT id_path FROM {$object2parent_links[$object_type]['table']} WHERE {$object2parent_links[$object_type]['field']} = ?i", $parent_object_id);
        $parent_object_ids = db_get_fields("SELECT {$object2parent_links[$object_type]['field']} FROM {$object2parent_links[$object_type]['table']} WHERE id_path LIKE ?l", "$path/%");
        $parent_object_ids[] = $parent_object_id;
        $query .= " AND {$object2parent_links[$object_type]['table']}.{$object2parent_links[$object_type]['field']} IN ('" . implode("','", $parent_object_ids) . "') AND {$object2parent_links[$object_type]['table']}.status='A'";
        $join = $object2parent_links[$object_type]['join'];
    }

    if ($object_type == 'P') {
        // Adding condition for the "Show out of stock products" setting
        if (
            Registry::get('settings.General.inventory_tracking') !== YesNo::NO
            && Registry::get('settings.General.show_out_of_stock_products') === YesNo::NO
            && SiteArea::isStorefront(AREA)
        ) {
            $join['?:products'] = "?:products.product_id=?:discussion.object_id";
            $query .= db_quote(' AND ?:products.amount > 0');
        }
    }

    $join_conditions = '';
    foreach ($join as $table => $j_cond) {
        $join_conditions .= " LEFT JOIN $table ON $j_cond ";
    }

    return db_get_hash_array(
        "SELECT object_id, avg(rating_value) AS rating FROM ?:discussion "
        . "LEFT JOIN ?:discussion_rating ON ?:discussion.thread_id=?:discussion_rating.thread_id $join_conditions "
        . "WHERE ?p GROUP BY ?:discussion.thread_id ORDER BY rating DESC",
        'object_id', $query . fn_get_discussion_company_condition('?:discussion.company_id')
    );
}

function fn_is_accessible_discussion($data, &$auth)
{
    $access = false;

    if ($data['object_type'] == 'P') {//product
        $access = fn_get_product_data($data['object_id'], $auth, CART_LANGUAGE, $field_list = '?:products.product_id', false, false, false);

    } elseif ($data['object_type'] == 'C') {//category
        $access = fn_get_category_data($data['object_id'], '', $field_list = '?:categories.category_id', false);

    } elseif ($data['object_type'] == 'M') {//company
        $access = fn_get_company_data($data['object_id']);

    } elseif ($data['object_type'] == 'O') {//order
        if (!empty($auth['user_id'])) {
            $access = db_get_field("SELECT order_id FROM ?:orders WHERE order_id = ?i AND user_id = ?i", $data['object_id'], $auth['user_id']);
        } elseif (!empty($auth['order_ids'])) {
            $access = in_array($data['object_id'], $auth['order_ids']);
        }

    } elseif ($data['object_type'] == 'A') {// page
        $access = fn_get_page_data($data['object_id'], CART_LANGUAGE);

    } elseif ($data['object_type'] == 'E') {// testimonials
        $access = true;
    }

    fn_set_hook('is_accessible_discussion', $data, $auth, $access);

    return !empty($access);
}

function fn_discussion_get_product_data(&$product_id, &$field_list, &$join)
{
    $field_list .= ", ?:discussion.type as discussion_type";
    $join .= " LEFT JOIN ?:discussion ON ?:discussion.object_id = ?:products.product_id AND ?:discussion.object_type = 'P'";

    if (fn_allowed_for('ULTIMATE') && Registry::ifGet('addons.discussion.product_share_discussion', 'N') == 'N' && Registry::get('runtime.company_id')) {
        $join .= " AND ?:discussion.company_id = " . Registry::get('runtime.company_id');
    }

    return true;
}

/**
 * Updates object discussion data
 *
 * @param array<string, int|string|bool> $data Discussion data
 *
 * @return bool Always true
 */
function fn_update_discussion($data)
{
    /**
     * This hook allows you to change discussion parameters before they are processed and recorded to the database
     *
     * @param array $data Discussion params (type, object_type, company_id, etc)
     */
    fn_set_hook('update_discussion_pre', $data);

    if (!empty($data['for_all_companies'])) {
        if (isset($data['thread_id'])) {
            unset($data['thread_id']);
        }

        foreach (fn_get_all_companies_ids() as $company) {
            $data['company_id'] = $company;
            db_replace_into('discussion', $data);
        }
    } else {
        if (!isset($data['company_id']) && fn_allowed_for('ULTIMATE')) {
            $data['company_id'] = fn_get_runtime_company_id();
        }

        db_replace_into('discussion', $data);
    }

    /**
     * This hook allows you to perform some actions after recorded discussion data to the database
     *
     * @param array<string, int|string> $data Discussion data
     */
    fn_set_hook('discussion_update_discussion_post', $data);

    return true;
}

function fn_discussion_get_products(&$params, &$fields, &$sortings, &$condition, &$join, &$sorting, &$group_by, &$lang_code, &$having)
{
    if (
        !empty($params['rating'])
        && Registry::get('addons.product_reviews.status') !== ObjectStatuses::ACTIVE
    ) {
        $fields[] = 'AVG(?:discussion_rating.rating_value) AS average_rating';
        $fields[] = '?:discussion.type AS discussion_type';
        $fields[] = '?:discussion.thread_id AS discussion_thread_id';
        $join .= db_quote(" LEFT JOIN ?:discussion ON ?:discussion.object_id = products.product_id AND ?:discussion.object_type = 'P'");

        if (fn_allowed_for('ULTIMATE') && Registry::ifGet('addons.discussion.product_share_discussion', 'N') == 'N' && Registry::get('runtime.company_id')) {
            $join .= " AND ?:discussion.company_id = " . Registry::get('runtime.company_id');
        }

        $join .= db_quote(" LEFT JOIN ?:discussion_posts ON ?:discussion_posts.thread_id = ?:discussion.thread_id AND ?:discussion_posts.status = 'A'");
        if (!empty($params['start_rating_period'])) {
            $join .= db_quote(' AND ?:discussion_posts.timestamp > ?i', $params['start_rating_period']);
        }

        $join .= db_quote(" LEFT JOIN ?:discussion_rating ON ?:discussion.thread_id = ?:discussion_rating.thread_id AND ?:discussion_rating.post_id = ?:discussion_posts.post_id AND ?:discussion_rating.rating_value != 0");

        $having[] = db_quote("average_rating > 0");
        $params['sort_by'] = 'rating';
        $params['sort_order'] = 'desc';
        $sortings['rating'] = 'average_rating';
    }

    return true;
}


/**
 * Hook "load_products_extra_data" handler.
 * Performs deferred calculation of average rating of products when there is no need in sorting products by rating.
 *
 * @param $extra_fields
 * @param $products
 * @param $product_ids
 * @param $params
 * @param $lang_code
 */
function fn_discussion_load_products_extra_data(&$extra_fields, $products, $product_ids, $params, $lang_code)
{
    if (
        !empty($params['rating'])
        || !empty($params['skip_rating'])
        || Registry::get('addons.product_reviews.status') === ObjectStatuses::ACTIVE
    ) {
        return;
    }

    $extra_fields['?:discussion'] = array(
        'primary_key' => 'product_id',
        'fields' => array(
            'product_id' => '?:discussion.object_id',
            'average_rating' => 'AVG(?:discussion_rating.rating_value)',
            'discussion_type' => '?:discussion.type',
            'discussion_thread_id' => '?:discussion.thread_id',
        ),
        'join' =>
            ' LEFT JOIN ?:discussion_posts' .
            '   ON ?:discussion_posts.thread_id = ?:discussion.thread_id AND ?:discussion_posts.status = "A"' .
            ' LEFT JOIN ?:discussion_rating' .
            '   ON ?:discussion.thread_id = ?:discussion_rating.thread_id' .
            '   AND ?:discussion_rating.post_id = ?:discussion_posts.post_id' .
            '   AND ?:discussion_rating.rating_value != 0',
        'condition' => ' AND ?:discussion.object_type = "P"',
        'group_by' => 'GROUP BY ?:discussion.object_id'
    );

    // Append company condition
    if (fn_allowed_for('ULTIMATE')
        && Registry::ifGet('addons.discussion.product_share_discussion', 'N') == 'N'
        && Registry::get('runtime.company_id')
    ) {
        $extra_fields['?:discussion']['condition'] .= ' AND ?:discussion.company_id = ' . Registry::get('runtime.company_id');
    }
}

/**
 * Hook load_products_extra_data_post handler
 * Rounds the average rating of the product
 *
 * @param array  $products    List of products
 * @param array  $product_ids List of product identifiers
 * @param array  $params      Parameters passed to fn_get_products()
 * @param string $lang_code   Language code passed to fn_get_products()
 */
function fn_discussion_load_products_extra_data_post(&$products, $product_ids, $params, $lang_code)
{
    if (Registry::get('addons.product_reviews.status') === ObjectStatuses::ACTIVE) {
        return;
    }

    foreach ($products as $id => $product) {
        if (!empty($product['average_rating'])) {
            $rating = fn_discussion_round_rating($product['average_rating']);
            $products[$id]['average_rating'] = $rating;
        }
    }
}

function fn_discussion_get_categories(&$params, &$join, &$condition, &$fields, &$group_by, &$sortings)
{
    if (!empty($params['rating'])) {
        $fields[] = 'avg(?:discussion_rating.rating_value) AS rating';
        $join .= db_quote(" INNER JOIN ?:discussion ON ?:discussion.object_id = ?:categories.category_id AND ?:discussion.object_type = 'C'");
        $join .= db_quote(" INNER JOIN ?:discussion_rating ON ?:discussion.thread_id=?:discussion_rating.thread_id");
        $join .= db_quote(" INNER JOIN ?:discussion_posts ON ?:discussion_posts.post_id=?:discussion_rating.post_id AND ?:discussion_posts.status = 'A'");
        $group_by = 'GROUP BY ?:discussion_rating.thread_id';
        $sortings['rating'] = 'rating';
        $params['sort_by'] = 'rating';
        $params['sort_order'] = 'asc';
    }

    return true;
}

function fn_discussion_get_pages(&$params, &$join, &$conditions, &$fields, &$group_by, &$sortings)
{
    if (!empty($params['rating'])) {
        $fields[] = 'avg(?:discussion_rating.rating_value) AS rating';
        $join .= db_quote(" INNER JOIN ?:discussion ON ?:discussion.object_id = ?:pages.page_id AND ?:discussion.object_type = 'A'");

        if (fn_allowed_for('ULTIMATE') && Registry::ifGet('addons.discussion.page_share_discussion', 'N') == 'N' && Registry::get('runtime.company_id')) {
            $join .= " AND ?:discussion.company_id = " . Registry::get('runtime.company_id');
        }

        $join .= db_quote(" INNER JOIN ?:discussion_rating ON ?:discussion.thread_id=?:discussion_rating.thread_id");
        $join .= db_quote(" INNER JOIN ?:discussion_posts ON ?:discussion_posts.post_id=?:discussion_rating.post_id AND ?:discussion_posts.status = 'A'");
        $group_by = '?:discussion_rating.thread_id';
        $sortings['rating'] = 'rating';
        $params['sort_by'] = 'rating';
        $params['sort_order'] = 'desc';
    }

    return true;
}

/**
 * The "get_companies" hook handler
 *
 * @param array<string, string> $params    Search params
 * @param array<string>         $fields    Fields to get from the database
 * @param array<string, string> $sortings  Available sortings
 * @param string                $condition SQL query conditions
 * @param string                $join      JOIN SQL query part
 * @param array<string, string> $auth      Current user auth data
 * @param string                $lang_code Two-letter language code
 * @param string                $group_by  GROUP BY SQL query part
 *
 * @return void
 *
 * @see \fn_get_companies()
 */
function fn_discussion_get_companies(&$params, &$fields, &$sortings, &$condition, &$join, &$auth, &$lang_code, &$group_by)
{
    fn_discussion_prepare_company_data_query($fields, $join, false, $params);
    $group_by = 'GROUP BY company_thread_ids';

    if (empty($params['sort_by'])) {
        return;
    }
    if ($params['sort_by'] !== 'rating') {
        return;
    }

    $sortings['rating'] = 'average_rating';
}

/**
 * The "get_company_data" hook handler
 *
 * @param int                   $company_id Company ID
 * @param string                $lang_code  The 2-letter language code (e.g. 'en', 'ru', etc.)
 * @param array<int, string>    $extra      Array with extra parameters
 * @param array<string, string> $fields     Array with tables fields for SQL query
 * @param string                $join       String with SQL join statements
 * @param string                $condition  String with conditions for the WHERE SQL statement
 *
 * @psalm-suppress ReferenceConstraintViolation
 *
 * @return void
 *
 * @see \fn_get_company_data()
 */
function fn_discussion_get_company_data($company_id, $lang_code, array $extra, array &$fields, &$join, $condition)
{
    fn_discussion_prepare_company_data_query($fields, $join);
}

/**
 * Prepares query to select company data
 *
 * @param array<int|string, string> $fields          Array with tables fields for SQL query
 * @param string                    $join            String with SQL join statements
 * @param bool                      $companies_alias If the alias is used for the companies table
 * @param array<string, string>     $params          Search params
 *
 * @return void
 */
function fn_discussion_prepare_company_data_query(array &$fields, &$join, $companies_alias = true, array $params = [])
{
    $fields[] = 'AVG(?:discussion_rating.rating_value) AS average_rating';
    $fields[] = 'CONCAT(' . ($companies_alias ? '' : '?:') . "companies.company_id, '_', IF ( "
    . "?:discussion_rating.thread_id, ?:discussion_rating.thread_id, '0')) AS company_thread_ids";

    $join .= db_quote(' LEFT JOIN ?:discussion ON ?:discussion.object_id = '
        . ($companies_alias ? '' : '?:')
        . 'companies.company_id AND ?:discussion.object_type = ?s', DISCUSSION_OBJECT_TYPE_COMPANY);

    $join .= db_quote(' LEFT JOIN ?:discussion_posts ON ?:discussion_posts.thread_id = ?:discussion.thread_id AND ?:discussion_posts.status = ?s', 'A');
    if (!empty($params['start_rating_period'])) {
        $join .= db_quote(' AND ?:discussion_posts.timestamp > ?i', $params['start_rating_period']);
    }

    $join .= db_quote(' LEFT JOIN ?:discussion_rating ON ?:discussion.thread_id = ?:discussion_rating.thread_id AND ?:discussion_rating.post_id = ?:discussion_posts.post_id');
}

/**
 * The "get_company_data_post" hook handler
 *
 * @param int                       $company_id   Company ID
 * @param string                    $lang_code    The 2-letter language code (e.g. 'en', 'ru', etc.)
 * @param array<string, string>     $extra        Array with extra parameters
 * @param array<string, string|int> $company_data Array with company data
 *
 * @psalm-suppress ReferenceConstraintViolation
 * @psalm-suppress UndefinedConstant
 *
 * @return void
 */
function fn_discussion_get_company_data_post($company_id, $lang_code, array $extra, array &$company_data)
{
    $posts_count = db_get_array(
        'SELECT a.object_id, COUNT(b.post_id) as total_items'
        . ' FROM ?:discussion as a LEFT JOIN ?:discussion_posts as b'
        . ' ON a.thread_id=b.thread_id WHERE a.object_type = ?s AND b.status = ?s'
        . ' GROUP BY a.object_id',
        DISCUSSION_OBJECT_TYPE_COMPANY,
        'A'
    );

    if (!empty($company_data['average_rating'])) {
        $rating = fn_discussion_round_rating((float) $company_data['average_rating']);
        $company_data['average_rating'] = $rating;
    }

    $company_data['discussion']['posts_count'] = empty($posts_count['total_items']) ? 0 : (int) $posts_count['total_items'];
}

function fn_discussion_companies_sorting(&$sorting)
{
    if (in_array(Registry::get('addons.discussion.company_discussion_type'), array('B', 'R'))) {
        $sorting['rating'] = array('description' => __('rating'), 'default_order' => 'desc');
    }
}

/**
 * The "delete_company" hook handler.
 *
 * Actions performed:
 *     - Deletes related discussion data
 *
 * @param int $company_id Company identifier
 *
 * @see \fn_delete_company()
 */
function fn_discussion_delete_company(&$company_id)
{
    db_query('DELETE FROM ?:discussion_messages WHERE thread_id IN (SELECT thread_id FROM ?:discussion WHERE company_id = ?i)', $company_id);
    db_query('DELETE FROM ?:discussion_posts WHERE thread_id IN (SELECT thread_id FROM ?:discussion WHERE company_id = ?i)', $company_id);
    db_query('DELETE FROM ?:discussion_rating WHERE thread_id IN (SELECT thread_id FROM ?:discussion WHERE company_id = ?i)', $company_id);
    db_query('DELETE FROM ?:discussion WHERE company_id = ?i', $company_id);
}

function fn_discussion_get_predefined_statuses(&$type, &$statuses)
{
    if ($type == 'discussion') {
        $statuses['discussion'] = array(
            'A' => __('approved'),
            'D' => __('disapproved')
        );
    }
}

/**
 * Delete post by identifier
 *
 * @param int $post_id Post identifier
 * @return boolean Always true
 */
function fn_discussion_delete_post($post_id)
{
    /**
     * Modifies deleting discussion post identifier
     *
     * @param int $post_id Post identifier
     */
    fn_set_hook('discussion_delete_post_pre', $post_id);

    db_query("DELETE FROM ?:discussion_messages WHERE post_id = ?i", $post_id);
    db_query("DELETE FROM ?:discussion_rating WHERE post_id = ?i", $post_id);
    db_query("DELETE FROM ?:discussion_posts WHERE post_id = ?i", $post_id);

    /**
     * Modifies deleted discussion post identifier
     *
     * @param int $post_id Post identifier
     */
    fn_set_hook('discussion_delete_post_post', $post_id);

    return true;
}

/**
 * Update multiple posts at once
 * @param array $posts posts data
 * @return boolean always true
 */
function fn_update_discussion_posts($posts)
{
    if (!empty($posts) && is_array($posts)) {
        $threads = db_get_hash_single_array("SELECT post_id, thread_id FROM ?:discussion_posts WHERE post_id IN (?n)", array('post_id', 'thread_id'), array_keys($posts));
        $messages_exist = db_get_fields("SELECT post_id FROM ?:discussion_messages WHERE post_id IN (?n)", array_keys($posts));
        $rating_exist = db_get_fields("SELECT post_id FROM ?:discussion_rating WHERE post_id IN (?n)", array_keys($posts));
        fn_delete_notification('company_access_denied');

        foreach ($posts as $p_id => $data) {
            unset($data['thread_id'], $data['post_id']);

            if (!empty($data['date'])) {
                $data['timestamp'] = fn_parse_datetime($data['date'] . ' ' . $data['time']);
            }

            // Validate rating value
            if (
                !empty($data['rating_value'])
                && !in_array($data['rating_value'], array_keys(fn_get_discussion_ratings()))
            ) {
                unset($data['rating_value']);
            }

            db_query("UPDATE ?:discussion_posts SET ?u WHERE post_id = ?i", $data, $p_id);

            if (in_array($p_id, $messages_exist)) {
                db_query("UPDATE ?:discussion_messages SET ?u WHERE post_id = ?i", $data, $p_id);
            } else {
                $data['thread_id'] = $threads[$p_id];
                $data['post_id'] = $p_id;
                db_query("INSERT INTO ?:discussion_messages ?e", $data);
            }

            if (in_array($p_id, $rating_exist)) {
                db_query("UPDATE ?:discussion_rating SET ?u WHERE post_id = ?i", $data, $p_id);
            } else {
                $data['thread_id'] = $threads[$p_id];
                $data['post_id'] = $p_id;
                db_query("INSERT INTO ?:discussion_rating ?e", $data);
            }
        }
        /**
         * This hook allows you to access the discussions that were already processed and recorded to the database
         *
         * @param array $posts Discussion data
         */
        fn_set_hook('update_discussion_posts_post', $posts);
    }

    return true;
}

/**
 * Gets available rating values with titles
 *
 * @return array Rating values list
 */
function fn_get_discussion_ratings()
{
    $rates = array(
        5 => __("excellent"),
        4 => __("very_good"),
        3 => __("average"),
        2 => __("fair"),
        1 => __("poor")
    );

    return $rates;
}

/**
 * Gets langvar names for discussion_manager tabs
 *
 * @return array List of tab langvars
 */
function fn_get_discussion_titles()
{
    $discussion_object_titles = array(
        'P' => 'discussion_tab_products',
        'C' => 'discussion_tab_categories',
        'A' => 'discussion_tab_pages',
        'O' => 'discussion_tab_orders',
        'E' => 'discussion_tab_home_page'
    );

    if (fn_allowed_for('MULTIVENDOR')) {
        $discussion_object_titles['M'] = 'discussion_tab_companies';
    }

    /**
     * Modifies title strings for different discussion types.
     * @param array $discussion_object_titles Title language variable ids
     *                                         per type.
     */
    fn_set_hook('get_discussion_titles', $discussion_object_titles);

    return $discussion_object_titles;
}

function fn_create_empty_thread($type, $company_id = null)
{
    $discussion = array(
        'type' => $type,
        'object_type' => 'E',
        'object_id' => 0,
    );

    if (is_null($company_id)) {
        if (fn_allowed_for('ULTIMATE')) {
            if (!Registry::get('runtime.company_id')) {
                $discussion['for_all_companies'] = 1;
            } else {
                $discussion['company_id'] = Registry::get('runtime.company_id');
            }
        }
    } else {
        $discussion['company_id'] = $company_id;
    }

    if (function_exists('fn_update_discussion')) {
        fn_update_discussion($discussion);
    }

    return true;
}

function fn_discussion_update_company(&$company_data, &$company_id, &$lang_code, &$action)
{
    if (!fn_allowed_for('ULTIMATE')) {
        return;
    }

    if ($action == 'add') {
        $type = Registry::get('addons.discussion.home_page_testimonials');
        if ($type != 'D') {
            fn_create_empty_thread($type, $company_id);
        }
    }
}

function fn_discussion_settings_variants_image_verification_use_for(&$objects)
{
    $objects['discussion'] = __('use_for_discussion');
}

/**
 * Checking access permissions to thread object (e.g. order)
 *
 * @param  mixed $data Thread ID (int) or Thread data (array)
 * @param  array $auth Auth
 * @return bool
 */
function fn_discussion_check_thread_permissions($thread, $auth)
{
    if (is_numeric($thread)) {
        $thread = db_get_row("SELECT * FROM ?:discussion WHERE thread_id = ?i", $thread);
    } elseif ((empty($thread['object_type']) || empty($thread['object_id'])) && !empty($thread['thread_id'])) {
        $thread = db_get_row("SELECT * FROM ?:discussion WHERE thread_id = ?i", $thread['thread_id']);
    }

    if (!$thread) {
        return false;
    }

    if (AREA == 'C') {
        if ($thread['object_type'] == 'O') { // Order
            return fn_is_order_allowed($thread['object_id'], $auth);
        }
    }

    return true;
}

/**
 * Hook handler
 * Rounds the average rating of the companies
 *
 * @param array   $params          Selection parameters
 * @param array   $auth            Array of user authentication data (e.g. uid, usergroup_ids, etc.)
 * @param int     $items_per_page  Items per page
 * @param string  $lang_code       2-letter language code (e.g. 'en', 'ru', etc.)
 * @param array   $companies       Information about the companies
 * */
function fn_discussion_get_companies_post($params, $auth, $items_per_page, $lang_code, &$companies)
{
    $posts_count = db_get_hash_array(
        'SELECT a.object_id, COUNT(b.post_id) as total_items'
        . ' FROM ?:discussion as a LEFT JOIN ?:discussion_posts as b'
        . ' ON a.thread_id=b.thread_id WHERE a.object_type = ?s AND b.status = ?s'
        . ' GROUP BY a.object_id',
        'object_id', DISCUSSION_OBJECT_TYPE_COMPANY, 'A'
    );

    foreach($companies as $key => $company) {
        if (!empty($company['average_rating'])) {
            $rating = fn_discussion_round_rating($company['average_rating']);
            $companies[$key]['average_rating'] = $rating;
        }

        $companies[$key]['discussion']['posts_count'] = empty($posts_count[$company['company_id']]['total_items']) ? 0 : $posts_count[$company['company_id']]['total_items'];
    }
}

/**
 * Round a discussion rating
 *
 * @param float $rating
 * @param int   $decimals
 *
 * @return string
 * */
function fn_discussion_round_rating($rating, $decimals = 2)
{
    return number_format((float) $rating, $decimals);
}

/**
 * Checks whether the user can update posts
 *
 * @param array $posts Discussion posts
 * @param array $auth  User authentication data
 *
 * @return bool
 */
function fn_discussion_check_update_posts_permission($posts, $auth)
{
    return $auth['user_type'] === 'A';
}

/**
 * Hook handler for GPDR add-on: saves accepted discussion review agreement to the log
 */
function fn_gdpr_add_discussion_post_post($post_data, $send_notifications)
{
    if (AREA !== 'C') {
        return false;
    }

    $email = '';
    $user_id = isset($post_data['user_id']) ? (int) $post_data['user_id'] : 0;

    if ($user_id) {
        $user_info = fn_get_user_info($user_id);
        $email = isset($user_info['email']) ? $user_info['email'] : '';
    }

    $params = array(
        'user_id' => $user_id,
        'email' => $email,
    );

    return fn_gdpr_save_user_agreement($params, 'discussions_add_review');
}

/**
 * Fetches all available discussion types list
 *
 * @return array
 */
function fn_discussion_get_discussion_types()
{
    return DiscussionTypes::getAll();
}

/**
 * Checks if user can leave review for specific product
 *
 * @param int $user_id    User identifier
 * @param int $product_id Product identifier
 *
 * @return bool
 */
function fn_discussion_is_user_eligible_to_write_review_for_product($user_id, $product_id)
{
    $result = true;
    $need_to_buy_first = Registry::get('addons.discussion.product_review_after_purchase') == YesNo::YES;

    if ($need_to_buy_first) {
        $result = $user_id && (bool) db_get_field(
            'SELECT orders.order_id FROM ?:orders AS orders '
            . 'LEFT JOIN ?:order_details AS details ON orders.order_id = details.order_id '
            . 'WHERE orders.user_id = ?i AND details.product_id = ?i LIMIT 1',
            $user_id,
            $product_id
        );
    }

    /**
     * Executes at the ending of the function, allows to modify result of function.
     *
     * @param int  $user_id           User identifier
     * @param int  $product_id        Product identifier
     * @param bool $result            Availability to post a review for product by this user
     * @param bool $need_to_buy_first State of the product_review_after_purchase setting
     */
    fn_set_hook('discussion_is_user_eligible_to_write_review_for_product_post', $user_id, $product_id, $result, $need_to_buy_first);


    return $result;
}

/**
 * Checks if user can leave review for specific company
 *
 * @param int $user_id    User identifier
 * @param int $company_id Company identifier
 *
 * @return bool
 */
function fn_discussion_is_user_eligible_to_write_review_for_company($user_id, $company_id)
{
    static $customer_companies = [];
    $need_to_buy_first = Registry::ifGet('addons.discussion.company_only_buyers', 'Y') === 'Y';

    if (!$need_to_buy_first) {
        return true;
    }

    if (!isset($customer_companies[$user_id])) {
        $customer_companies[$user_id] = db_get_hash_single_array(
            'SELECT company_id FROM ?:orders WHERE user_id = ?i',
            ['company_id', 'company_id'],
            $user_id
        );
    }

    return !empty($customer_companies[$user_id][$company_id]);
}

/**
 * Checks if the customer can leave review from this IP.
 *
 * @param string $ip          Customer ip address
 * @param string $object_type Thread object type
 * @param int    $thread_id   Thread identifier
 *
 * @return bool
 */
function fn_discussion_is_user_can_leave_review_from_ip($ip, $object_type, $thread_id)
{
    $can_post = true;

    $discussion_settings = Registry::get('addons.discussion');

    $discussion_object_types = fn_get_discussion_objects();
    $object_type = $discussion_object_types[$object_type];

    if (
        $ip
        && !empty($discussion_settings[$object_type . '_post_ip_check'])
        && $discussion_settings[$object_type . '_post_ip_check'] === YesNo::YES
    ) {
        $is_exists = Tygh::$app['db']->getField(
            'SELECT COUNT(*) FROM ?:discussion_posts WHERE thread_id = ?i AND ip_address = ?s',
            $thread_id, fn_ip_to_db($ip)
        );

        $can_post = empty($is_exists);
    }

    return $can_post;
}

/**
 * The "check_and_update_product_sharing" hook handler.
 *
 * Actions performed:
 *  - Sets default review option value for new company which is added to product sharing
 *
 * @see fn_check_and_update_product_sharing
 */
function fn_discussion_check_and_update_product_sharing($product_id, $shared, $existing_company_ids, $product_categories_company_ids, $added_company_ids)
{
    if (empty($product_id) || empty($added_company_ids)) {
        return;
    }

    foreach ($added_company_ids as $added_company_id) {
        $existing_discussion = fn_get_discussion($product_id, DiscussionObjectTypes::PRODUCT, false, [], $added_company_id);
        if (!empty($existing_discussion)) {
            continue;
        }

        $discussion_settings = Settings::instance()->getValues('discussion', Settings::ADDON_SECTION, false, $added_company_id);
        if (empty($discussion_settings['product_discussion_type'])) {
            continue;
        }

        $discussion_type = $discussion_settings['product_discussion_type'];

        $discussion = [
            'object_type' => DiscussionObjectTypes::PRODUCT,
            'object_id'   => $product_id,
            'type'        => $discussion_type,
            'company_id'  => $added_company_id
        ];

        fn_update_discussion($discussion);
    }
}

/**
 * The "seo_get_schema_org_markup_items_post" hook handler.
 *
 * Actions performed:
 *     - Adds aggregate rating for the Product markup item.
 *     - Adds reviews for the Product markup item.
 *
 * @see \fn_seo_get_schema_org_markup_items()
 */
function fn_discussion_seo_get_schema_org_markup_items_post($product_data, $show_price, $currency, &$markup_items)
{
    if (empty($markup_items['product']) || Registry::get('addons.product_reviews.status') === ObjectStatuses::ACTIVE) {
        return;
    }

    if (empty($product_data['discussion']['search']['total_items'])
        || empty($product_data['discussion']['average_rating'])
    ) {
        return;
    }

    $product_item = $markup_items['product'];

    $product_item['aggregateRating'] = [
        '@type'       => 'http://schema.org/AggregateRating',
        'reviewCount' => (int) $product_data['discussion']['search']['total_items'],
        'ratingValue' => (float) $product_data['discussion']['average_rating'],
    ];

    if (!empty($product_data['discussion']['posts'])) {
        foreach ($product_data['discussion']['posts'] as $post) {
            $product_item['review'][] = [
                '@type'        => 'http://schema.org/Review',
                'author'       => [
                    '@type' => 'http://schema.org/Person',
                    'name'  => $post['name'],
                ],
                'reviewRating' => [
                    '@type'       => 'http://schema.org/Rating',
                    'ratingValue' => (float) $post['rating_value'],
                    'bestRating'  => 5,
                ],
            ];
        }
    }

    $markup_items['product'] = $product_item;
}

function fn_discussion_addon_install()
{
    list($root_admins,) = fn_get_users([
        'is_root' => YesNo::YES,
        'user_type' => UserTypes::ADMIN,
    ], Tygh::$app['session']['auth']);

    foreach ($root_admins as $root_admin) {
        if (!$root_admin['company_id']) {
            fn_update_notification_receiver_search_conditions(
                'group',
                'discussion',
                UserTypes::ADMIN,
                [
                    new SearchCondition(ReceiverSearchMethods::USER_ID, $root_admin['user_id']),
                ]
            );
            break;
        }
    }

    if (fn_allowed_for('MULTIVENDOR')) {

        $search_conditions = fn_get_default_vendor_notification_search_conditions(true);

        fn_update_notification_receiver_search_conditions(
            'group',
            'discussion',
            UserTypes::VENDOR,
            $search_conditions
        );
    }
}

function fn_discussion_addon_uninstall()
{
    fn_update_notification_receiver_search_conditions(
        'group',
        'discussion',
        UserTypes::ADMIN,
        []
    );

    fn_update_notification_receiver_search_conditions(
        'group',
        'discussion',
        UserTypes::VENDOR,
        []
    );
}

/**
 * Get discussion setting by setting name
 *
 * @param string   $setting_name Setting name
 * @param int|null $company_id   Company identifier, null for multivendor
 *
 * @return string|false Returns setting value, false otherwise
 */
function fn_discussion_get_discussion_setting($setting_name, $company_id = null)
{
    $discussion_settings = Settings::instance()->getValues(
        'discussion',
        Settings::ADDON_SECTION,
        false,
        fn_allowed_for('MULTIVENDOR') ? null : $company_id
    );

    return empty($discussion_settings[$setting_name]) ? false : (string) $discussion_settings[$setting_name];
}
