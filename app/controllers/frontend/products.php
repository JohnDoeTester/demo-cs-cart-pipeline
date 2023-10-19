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
use Tygh\BlockManager\ProductTabs;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

//
// Search products
//
if ($mode == 'search') {

    $params = $_REQUEST;
    fn_add_breadcrumb(__('search_results'));

    if (!empty($params['search_performed']) || !empty($params['features_hash'])) {

        $params['extend'] = array('description');

        if (isset($params['order_ids'])) {
            $order_ids = is_array($params['order_ids']) ? $params['order_ids'] : explode(',', $params['order_ids']);
            foreach ($order_ids as $order_id) {
                if (!fn_is_order_allowed($order_id, $auth)) {
                    return [CONTROLLER_STATUS_NO_PAGE];
                }
            }
        }

        if ($items_per_page = fn_change_session_param(Tygh::$app['session']['search_params'], $_REQUEST, 'items_per_page')) {
            $params['items_per_page'] = $items_per_page;
        }
        if ($sort_by = fn_change_session_param(Tygh::$app['session']['search_params'], $_REQUEST, 'sort_by')) {
            $params['sort_by'] = $sort_by;
        }
        if ($sort_order = fn_change_session_param(Tygh::$app['session']['search_params'], $_REQUEST, 'sort_order')) {
            $params['sort_order'] = $sort_order;
        }

        list($products, $search) = fn_get_products($params, Registry::get('settings.Appearance.products_per_page'));

        fn_filters_handle_search_result($params, $products, $search);

        fn_gather_additional_products_data($products, array(
            'get_icon' => true,
            'get_detailed' => true,
            'get_additional' => true,
            'get_options'=> true
        ));

        if (!empty($products)) {
            Tygh::$app['session']['continue_url'] = Registry::get('config.current_url');
        }

        $selected_layout = fn_get_products_layout($params);

        Tygh::$app['view']->assign('products', $products);
        Tygh::$app['view']->assign('search', $search);
        Tygh::$app['view']->assign('is_selected_filters', !empty($params['features_hash']));
        Tygh::$app['view']->assign('selected_layout', $selected_layout);
    }

//
// View product details
//
} elseif ($mode == 'view' || $mode == 'quick_view') {

    $_REQUEST['product_id'] = empty($_REQUEST['product_id']) ? 0 : $_REQUEST['product_id'];

    if (!empty($_REQUEST['product_id']) && empty($auth['user_id'])) {

        $uids = explode(',', db_get_field('SELECT usergroup_ids FROM ?:products WHERE product_id = ?i', $_REQUEST['product_id']));

        if (!in_array(USERGROUP_ALL, $uids) && !in_array(USERGROUP_GUEST, $uids)) {
            return array(CONTROLLER_STATUS_REDIRECT, 'auth.login_form?return_url=' . urlencode(Registry::get('config.current_url')));
        }
    }

    $product_params = [];
    if (fn_allowed_for('MULTIVENDOR')) {
        $product_params['use_i18n_fields'] = true;
    }

    $product = fn_get_product_data(
        $_REQUEST['product_id'],
        $auth,
        CART_LANGUAGE,
        '',
        true,
        true,
        true,
        true,
        fn_is_preview_action($auth, $_REQUEST),
        true,
        false,
        true,
        $product_params
    );

    if (empty($product)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    if (fn_allowed_for('MULTIVENDOR')) {
        /** @var \Tygh\Storefront\Storefront $storefront */
        $storefront = Tygh::$app['storefront'];
        if (
            !empty($product['company_id'])
            && $storefront->getCompanyIds()
            && !in_array($product['company_id'], $storefront->getCompanyIds())
        ) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }
    }

    if ((empty(Tygh::$app['session']['current_category_id']) || empty($product['category_ids'][Tygh::$app['session']['current_category_id']])) && !empty($product['main_category'])) {
        if (!empty(Tygh::$app['session']['breadcrumb_category_id']) && in_array(Tygh::$app['session']['breadcrumb_category_id'], $product['category_ids'])) {
            Tygh::$app['session']['current_category_id'] = Tygh::$app['session']['breadcrumb_category_id'];
        } else {
            Tygh::$app['session']['current_category_id'] = $product['main_category'];
        }
    }

    if (!empty($product['meta_description']) || !empty($product['meta_keywords'])) {
        Tygh::$app['view']->assign('meta_description', $product['meta_description']);
        Tygh::$app['view']->assign('meta_keywords', $product['meta_keywords']);

    } else {
        $meta_tags = db_get_row(
            "SELECT meta_description, meta_keywords"
            . " FROM ?:category_descriptions"
            . " WHERE category_id = ?i AND lang_code = ?s",
            Tygh::$app['session']['current_category_id'],
            CART_LANGUAGE
        );
        if (!empty($meta_tags)) {
            Tygh::$app['view']->assign('meta_description', $meta_tags['meta_description']);
            Tygh::$app['view']->assign('meta_keywords', $meta_tags['meta_keywords']);
        }
    }
    if (!empty(Tygh::$app['session']['current_category_id'])) {
        Tygh::$app['session']['continue_url'] = "categories.view?category_id=".Tygh::$app['session']['current_category_id'];

        $parent_ids = fn_get_category_ids_with_parent(Tygh::$app['session']['current_category_id']);

        if (!empty($parent_ids)) {
            Registry::set('runtime.active_category_ids', $parent_ids);
            $cats = fn_get_category_name($parent_ids);
            foreach ($parent_ids as $c_id) {
                fn_add_breadcrumb($cats[$c_id], "categories.view?category_id=$c_id");
            }
        }
    }
    fn_add_breadcrumb($product['product']);

    if (!empty($_REQUEST['combination'])) {
        $product['combination'] = $_REQUEST['combination'];
    }

    fn_gather_additional_product_data($product, true, true);
    Tygh::$app['view']->assign('product', $product);
    Tygh::$app['view']->assign('product_id', $_REQUEST['product_id']);

    // If page title for this product is exist than assign it to template
    if (!empty($product['page_title'])) {
        Tygh::$app['view']->assign('page_title', $product['page_title']);
    }

    $params = array (
        'product_id' => $_REQUEST['product_id'],
        'preview_check' => true
    );
    list($files) = fn_get_product_files($params);

    if (!empty($files)) {
        Tygh::$app['view']->assign('files', $files);
    }

    // Initialize product tabs
    fn_init_product_tabs($product);

    // Set recently viewed products history
    fn_add_product_to_recently_viewed($_REQUEST['product_id']);

    // Increase product popularity
    fn_set_product_popularity($_REQUEST['product_id']);

    Tygh::$app['view']->assign('show_qty', true);

    // custom vendor blocks
    if ($vendor_id = fn_get_runtime_vendor_id()) {
        Tygh::$app['view']->assign('company_id', $vendor_id);
        $_REQUEST['company_id'] = $vendor_id;
    }

    if ($mode == 'quick_view') {
        if (defined('AJAX_REQUEST')) {
            fn_prepare_product_quick_view($_REQUEST);

            if (!empty($_REQUEST['is_microstore'])) {
                Tygh::$app['view']->assign('is_microstore', 'Y');
            }

            Registry::set('runtime.root_template', 'views/products/quick_view.tpl');
        } else {
            return array(CONTROLLER_STATUS_REDIRECT, 'products.view?product_id=' . $_REQUEST['product_id']);
        }
    }

} elseif ($mode == 'options') {

    if (!defined('AJAX_REQUEST') && !empty($_REQUEST['product_data'])) {
        $_data = reset($_REQUEST['product_data']);
        $product_id = key($_REQUEST['product_data']);
        $product_id = isset($_data['product_id']) ? $_data['product_id'] : $product_id;

        return array(CONTROLLER_STATUS_REDIRECT, 'products.view?product_id=' . $product_id);
    }
} elseif ($mode == 'product_notifications') {
    $email = '';

    if (!empty(Tygh::$app['session']['cart']['user_data']['email'])) {
        $email = Tygh::$app['session']['cart']['user_data']['email'];
    } elseif (!empty($_REQUEST['email'])) {
        $email = $_REQUEST['email'];
    }

    if ($email && !empty($_REQUEST['product_id'])) {
        $subscription_data = array(
            'product_id' => $_REQUEST['product_id'],
            'user_id' => Tygh::$app['session']['auth']['user_id'],
            'email' => $email,
            'enable' => !empty($_REQUEST['enable']) ? $_REQUEST['enable'] : 'Y',
        );

        fn_update_product_notifications($subscription_data);
    } else {
        fn_set_notification('E', __('error'), __('product_notification_subscription_error'));
    }

    exit;
} elseif ($mode === 'subscription_form') {
    $_REQUEST['product_id'] = empty($_REQUEST['product_id']) ? 0 : $_REQUEST['product_id'];

    $product_notification_email = (isset(Tygh::$app['session']['product_notifications']) ? Tygh::$app['session']['product_notifications']['email'] : '');
    $product_notification_enabled = fn_check_product_notification_setting(
        $_REQUEST['product_id'],
        Tygh::$app['session']['auth']['user_id'],
        $product_notification_email
    );

    Tygh::$app['view']->assign('product_id', $_REQUEST['product_id']);
    Tygh::$app['view']->assign('product_notification_enabled', $product_notification_enabled);
    Tygh::$app['view']->assign('product_notification_email', $product_notification_email);
}

function fn_add_product_to_recently_viewed($product_id, $max_list_size = MAX_RECENTLY_VIEWED)
{
    $added = false;

    if (!empty(Tygh::$app['session']['recently_viewed_products'])) {
        $is_exist = array_search($product_id, Tygh::$app['session']['recently_viewed_products']);
        // Existing product will be moved on the top of the list
        if ($is_exist !== false) {
            // Remove the existing product to put it on the top later
            unset(Tygh::$app['session']['recently_viewed_products'][$is_exist]);
            // Re-sort the array
            Tygh::$app['session']['recently_viewed_products'] = array_values(Tygh::$app['session']['recently_viewed_products']);
        }

        array_unshift(Tygh::$app['session']['recently_viewed_products'], $product_id);
        $added = true;
    } else {
        Tygh::$app['session']['recently_viewed_products'] = array($product_id);
    }

    if (count(Tygh::$app['session']['recently_viewed_products']) > $max_list_size) {
        array_pop(Tygh::$app['session']['recently_viewed_products']);
    }

    return $added;
}

function fn_set_product_popularity($product_id, $popularity_view = POPULARITY_VIEW)
{
    if (empty(Tygh::$app['session']['products_popularity']['viewed'][$product_id])) {
        $popularity = [
            'viewed' => 1,
            'total' => $popularity_view
        ];

        fn_update_product_popularity($product_id, $popularity);

        Tygh::$app['session']['products_popularity']['viewed'][$product_id] = true;

        return true;
    }

    return false;
}

function fn_update_product_notifications($data)
{
    $deleted = $subscribed = false;

    /**
     * Processes product subscription data
     *
     * @param array  $data  Subscription notification data
     */
    fn_set_hook('update_product_notifications_pre', $data);

    if (!empty($data['email']) && fn_validate_email($data['email'])) {
        Tygh::$app['session']['product_notifications']['email'] = $data['email'];

        if ($data['enable'] === 'Y') {
            $subscribed = db_query('REPLACE INTO ?:product_subscriptions ?e', $data);

            if (!isset(Tygh::$app['session']['product_notifications']['product_ids'])
                || (is_array(Tygh::$app['session']['product_notifications']['product_ids'])
                    && !in_array($data['product_id'], Tygh::$app['session']['product_notifications']['product_ids']))
            ) {
                Tygh::$app['session']['product_notifications']['product_ids'][] = $data['product_id'];
            }

            fn_set_notification('N', __('notice'), __('product_notification_subscribed'));
        } else {
            $where = array(
                'product_id' => (int) $data['product_id'],
                'user_id' => (int) $data['user_id'],
                'email' => $data['email'],
            );

            /**
             * Processes product subscription data before deleting it from the database
             *
             * @param array  $data   Subscription notification data
             * @param array  $where  Where clause data
             */
            fn_set_hook('update_product_notifications_before_delete', $data, $where);

            $deleted = db_query('DELETE FROM ?:product_subscriptions WHERE ?w', $where);

            if (isset(Tygh::$app['session']['product_notifications']['product_ids'])
                && in_array($data['product_id'], Tygh::$app['session']['product_notifications']['product_ids'])
            ) {
                Tygh::$app['session']['product_notifications']['product_ids'] = array_diff(
                    Tygh::$app['session']['product_notifications']['product_ids'],
                    array($data['product_id'])
                );
            }

            if (!empty($deleted)) {
                fn_set_notification('N', __('notice'), __('product_notification_unsubscribed'));
            }
        }
    }

    /**
     * Processes product subscription data after updating product notification
     *
     * @param array $data       Subscription notification data
     * @param mixed $subscribed Subscription result
     * @param mixed $delete     Deletion result
     */
    fn_set_hook('update_product_notifications_post', $data, $subscribed, $deleted);
}
