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

use Tygh\BlockManager\SchemesManager;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\OutOfStockActions;
use Tygh\Enum\ProductFeatures;
use Tygh\Enum\ProductTracking;
use Tygh\Enum\ProductZeroPriceActions;
use Tygh\Enum\YesNo;
use Tygh\Http;
use Tygh\Registry;
use Tygh\Tools\Url;

defined('BOOTSTRAP') or die('Access denied');

/**
 * @var string $mode
 * @var array $auth
 */

$_REQUEST['product_id'] = empty($_REQUEST['product_id']) ? 0 : $_REQUEST['product_id'];

if (fn_allowed_for('MULTIVENDOR')) {
    if (
        (isset($_REQUEST['product_id']) && !fn_company_products_check($_REQUEST['product_id']))
        ||
        (isset($_REQUEST['product_ids']) && !fn_company_products_check($_REQUEST['product_ids']))
    ) {
        return [CONTROLLER_STATUS_DENIED];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $suffix = '';

    // Define trusted variables that shouldn't be stripped
    fn_trusted_vars(
        'product_data',
        'override_products_data',
        'product_files_descriptions',
        'add_product_files_descriptions',
        'products_data',
        'product_file'
    );


    // Apply Global Option
    if ($mode === 'apply_global_option') {
        if (isset($_REQUEST['global_option']['link']) && $_REQUEST['global_option']['link'] === YesNo::NO) {
            fn_clone_product_options(0, $_REQUEST['product_id'], $_REQUEST['global_option']['id']);
        } else {
            fn_add_global_option_link($_REQUEST['product_id'], $_REQUEST['global_option']['id']);
        }

        fn_update_product(['updated_timestamp' => TIME], $_REQUEST['product_id']);

        $suffix = ".update?product_id={$_REQUEST['product_id']}";
    }

    /**
     * Create/update product
     */
    if ($mode === 'update') {
        $product_id = null;
        if (!empty($_REQUEST['product_data']['product'])) {
            $product_data = $_REQUEST['product_data'];

            if (isset($product_data['category_ids']) && !is_array($product_data['category_ids'])) {
                $product_data['category_ids'] = explode(',', $product_data['category_ids']);
            }

            if (!empty($product_data['linked_option_ids'])) {
                foreach ($product_data['linked_option_ids'] as $linked_option_id) {
                    fn_add_global_option_link($_REQUEST['product_id'], $linked_option_id);
                }
            }

            $product_id = fn_update_product($product_data, $_REQUEST['product_id'], DESCR_SL);

            if ($product_id === false) {
                // Some error occurred
                fn_save_post_data('product_data');

                return [
                    CONTROLLER_STATUS_REDIRECT,
                    !empty($_REQUEST['product_id']) ? 'products.update?product_id=' . $_REQUEST['product_id'] : 'products.add'
                ];
            }
        }

        Tygh::$app['view']->assign('product_id', $product_id);

        if (!empty($_REQUEST['product_id'])) {
            if (!empty($_REQUEST['add_users'])) {
                // Updating product subscribers
                $users = db_get_array('SELECT user_id, email FROM ?:users WHERE user_id IN (?n)', $_REQUEST['add_users']);

                if (!empty($users)) {
                    foreach ($users as $user) {
                        fn_update_product_subscriber($_REQUEST['product_id'], [
                            'user_id' => $user['user_id'],
                            'email'   => $user['email']
                        ]);
                    }
                } elseif (!empty($_REQUEST['add_subscriber'])) {
                    $added_subscriber_id = fn_update_product_subscriber($_REQUEST['product_id'], $_REQUEST['add_subscriber']);
                    if (!$added_subscriber_id) {
                        fn_set_notification('E', __('error'), __('warning_subscr_email_exists', [
                            '[email]' => $_REQUEST['add_subscriber']['email']
                        ]));
                    }
                }
            } elseif (!empty($_REQUEST['subscriber_ids'])) {
                fn_delete_product_subscribers($_REQUEST['subscriber_ids']);
            }

            return [
                CONTROLLER_STATUS_OK,
                'products.update?product_id=' . $_REQUEST['product_id'] . '&selected_section=subscribers'
            ];
        }

        if (!empty($product_id)) {
            $suffix = ".update?product_id={$product_id}"
                . (!empty($_REQUEST['product_data']['block_id']) ? "&selected_block_id={$_REQUEST['product_data']['block_id']}" : '');
        } else {
            $suffix = '.manage';
        }
    }

    // Processing mulitple addition of new product elements
    if ($mode === 'm_add') {
        if (is_array($_REQUEST['products_data'])) {
            $p_ids = [];

            foreach ($_REQUEST['products_data'] as $v) {
                if (!empty($v['product']) && !empty($v['category_ids'])) {  // Checking for required fields for new product
                    $p_id = fn_update_product($v);
                    if (!empty($p_id)) {
                        $p_ids[] = $p_id;
                    }
                }
            }

            if (!empty($p_ids)) {
                fn_set_notification('N', __('notice'), __('text_products_added'));
            }
        }
        $suffix = '.manage' . (empty($p_ids) ? '' : '?pid[]=' . implode('&pid[]=', $p_ids));
    }

    // Processing multiple updating of product elements
    if ($mode === 'm_update') {
        // Update multiple products data
        if (!empty($_REQUEST['products_data'])) {
            if (fn_allowed_for('MULTIVENDOR') && !fn_company_products_check(array_keys($_REQUEST['products_data']))) {
                return [CONTROLLER_STATUS_DENIED];
            }

            // Update images
            fn_attach_image_pairs('product_main', 'product', 0, DESCR_SL);

            fn_clear_image_pairs_request_data('product_main');

            foreach ($_REQUEST['products_data'] as $product_id => $product) {
                if (!empty($product['product'])) { // Checking for required fields for new product
                    if (fn_allowed_for('ULTIMATE,MULTIVENDOR') && Registry::get('runtime.company_id')) {
                        unset($product['company_id']);
                    }

                    if (!empty($product['category_ids']) && !is_array($product['category_ids'])) {
                        $product['category_ids'] = explode(',', $product['category_ids']);
                    }

                    fn_update_product($product, $product_id, DESCR_SL);

                    // Updating products position in category
                    if (isset($product['position']) && !empty($_REQUEST['category_id'])) {
                        fn_update_product_position_in_category($product_id, $_REQUEST['category_id'], $product['position']);
                    }
                }
            }
        }

        $suffix = '.manage';
    }

    // Processing global updating of product elements
    if ($mode === 'global_update') {
        fn_global_update_products($_REQUEST['update_data']);

        $suffix = '.global_update';
    }

    // Override multiple products with the one value
    if ($mode === 'm_override') {
        // Update multiple products data

        if (!empty(Tygh::$app['session']['product_ids'])) {
            if (fn_allowed_for('MULTIVENDOR') && !fn_company_products_check(Tygh::$app['session']['product_ids'])) {
                return [CONTROLLER_STATUS_DENIED];
            }

            $product_data = !empty($_REQUEST['override_products_data']) ? $_REQUEST['override_products_data'] : [];

            if (isset($product_data['avail_since'])) {
                $product_data['avail_since'] = fn_parse_date($product_data['avail_since']);
            }
            if (isset($product_data['timestamp'])) {
                $product_data['timestamp'] = fn_parse_date($product_data['timestamp']);
            }

            if (fn_allowed_for('ULTIMATE,MULTIVENDOR') && Registry::get('runtime.company_id')) {
                unset($product_data['company_id']);
            }

            fn_define('KEEP_UPLOADED_FILES', true);

            if (!empty($product_data['category_ids'])) {
                $product_data['category_ids'] = explode(',', $product_data['category_ids']);
            }

            foreach (Tygh::$app['session']['product_ids'] as $p_id) {
                // Update product
                fn_update_product($product_data, $p_id, DESCR_SL);
            }
        }
    }

    // Processing deleting of multiple product elements
    if ($mode === 'm_delete') {
        if (isset($_REQUEST['product_ids'])) {
            foreach ($_REQUEST['product_ids'] as $v) {
                fn_delete_product($v);
            }
        }
        unset(Tygh::$app['session']['product_ids']);
        fn_set_notification('N', __('notice'), __('text_products_have_been_deleted'));

        $suffix = '.manage';
    }

    // Processing deleting of multiple product subscriptions
    if ($mode === 'm_delete_subscr') {
        if (isset($_REQUEST['product_ids'])) {
            db_query('DELETE FROM ?:product_subscriptions WHERE product_id IN (?n)', $_REQUEST['product_ids']);
        }
        unset(Tygh::$app['session']['product_ids']);

        $suffix = '.p_subscr';
    }

    // Processing new prices from bulk editor
    if ($mode === 'm_update_prices') {
        if (!empty($_REQUEST['new_values'])) {
            foreach ($_REQUEST['new_values'] as $product_data) {
                if (!empty($product_data['values'])) {
                    fn_update_product(
                        $product_data['values'],
                        $product_data['id']
                    );
                }
            }
        }

        return [CONTROLLER_STATUS_REDIRECT, $_REQUEST['redirect_url']];
    }

    // Processing hiding or activating or disabling of multiple product elements
    if ($mode === 'm_hide' || $mode === 'm_activate' || $mode === 'm_disable') {
        if (isset($_REQUEST['product_ids'])) {
            $status = ObjectStatuses::HIDDEN;
            $status = ($mode === 'm_activate') ? ObjectStatuses::ACTIVE : $status;
            $status = ($mode === 'm_disable') ? ObjectStatuses::DISABLED : $status;

            foreach ($_REQUEST['product_ids'] as $v) {
                fn_tools_update_status([
                    'table'             => 'products',
                    'status'            => $status,
                    'id_name'           => 'product_id',
                    'id'                => $v,
                    'show_error_notice' => false
                ]);
            }
        }

        unset(Tygh::$app['session']['product_ids']);
        $suffix = '.manage';
    }

    // Processing clonning of multiple product elements
    if ($mode === 'm_clone') {
        $p_ids = [];

        if (!empty($_REQUEST['product_ids'])) {
            foreach ($_REQUEST['product_ids'] as $v) {
                $pdata = fn_clone_product($v);

                if ($pdata) {
                    $p_ids[] = $pdata['product_id'];
                }
            }
            fn_set_notification('N', __('notice'), __('text_products_cloned'));
        }

        $suffix = '.manage?pid[]=' . implode('&pid[]=', $p_ids);
        unset($_REQUEST['redirect_url'], $_REQUEST['page']); // force redirection
    }

    // Storing selected fields for using in m_update mode
    if ($mode === 'store_selection') {
        if (!empty($_REQUEST['product_ids'])) {
            Tygh::$app['session']['product_ids'] = $_REQUEST['product_ids'];
            Tygh::$app['session']['selected_fields'] = $_REQUEST['selected_fields'];

            unset($_REQUEST['redirect_url']);

            $suffix = '.m_update';
        } else {
            fn_set_notification('W', __('warning'), __('bulk_edit.some_products_were_omitted_other_storefront'));
            $suffix = '.manage';
        }
    }

    // Add edp files to the product
    if ($mode === 'update_file') {
        if (!empty($_REQUEST['product_file'])) {
            if (empty($_REQUEST['product_file']['folder_id'])) {
                $_REQUEST['product_file']['folder_id'] = null;
            }
            fn_update_product_file($_REQUEST['product_file'], $_REQUEST['file_id']);
        }

        $suffix = ".update?product_id={$_REQUEST['product_id']}";
    }

    // Add edp folder to the product
    if ($mode === 'update_folder') {
        if (!empty($_REQUEST['product_file_folder'])) {
            fn_update_product_file_folder($_REQUEST['product_file_folder'], $_REQUEST['folder_id']);
        }

        $suffix = ".update?product_id={$_REQUEST['product_id']}";
    }

    if ($mode === 'export_range') {
        if (!empty($_REQUEST['product_ids'])) {
            $product_ids = (array) $_REQUEST['product_ids'];
        } elseif (!empty($_REQUEST['category_ids'])) {
            list($products, ) = fn_get_products(['cid' => (array) $_REQUEST['category_ids'], 'load_products_extra_data' => false]);
            $product_ids = array_keys($products);

            if (empty($product_ids)) {
                fn_set_notification(NotificationSeverity::ERROR, __('error'), __('text_no_products_found'));
                return [CONTROLLER_STATUS_OK, 'categories.manage'];
            }
        } else {
            $product_ids = null;
        }

        if (!empty($product_ids)) {
            if (empty(Tygh::$app['session']['export_ranges'])) {
                Tygh::$app['session']['export_ranges'] = [];
            }

            if (empty(Tygh::$app['session']['export_ranges']['products']['pattern_id'])) {
                Tygh::$app['session']['export_ranges']['products'] = ['pattern_id' => 'products'];
            }

            Tygh::$app['session']['export_ranges']['products']['data'] = ['product_id' => $product_ids];

            unset($_REQUEST['redirect_url'], Tygh::$app['session']['export_ranges']['products']['data_provider']);

            return [
                CONTROLLER_STATUS_REDIRECT,
                'exim.export?section=products&pattern_id=' . Tygh::$app['session']['export_ranges']['products']['pattern_id'],
            ];
        }
    }

    // Delete product
    if ($mode === 'delete') {
        if (!empty($_REQUEST['product_id'])) {
            $result = fn_delete_product($_REQUEST['product_id']);

            if ($result) {
                fn_set_notification('N', __('notice'), __('text_product_has_been_deleted'));
            } else {
                return [CONTROLLER_STATUS_REDIRECT, 'products.update?product_id=' . $_REQUEST['product_id']];
            }
        }

        return [CONTROLLER_STATUS_REDIRECT, 'products.manage'];
    }

    if ($mode === 'delete_subscr') {
        if (!empty($_REQUEST['product_id'])) {
            db_query('DELETE FROM ?:product_subscriptions WHERE product_id = ?i', $_REQUEST['product_id']);
        }

        return [CONTROLLER_STATUS_REDIRECT, 'products.p_subscr'];
    }

    if ($mode === 'clone') {
        if (!empty($_REQUEST['product_id'])) {
            $pid = $_REQUEST['product_id'];
            $pdata = fn_clone_product($pid);
            if (!empty($pdata['product_id'])) {
                $pid = $pdata['product_id'];
                fn_set_notification('N', __('notice'), __('text_product_cloned'));
            }

            return [CONTROLLER_STATUS_REDIRECT, 'products.update?product_id=' . $pid];
        }
    }

    if ($mode === 'delete_file') {
        if (!empty($_REQUEST['file_id']) && !empty($_REQUEST['product_id'])) {
            if (fn_delete_product_files($_REQUEST['file_id']) === false) {
                return [CONTROLLER_STATUS_DENIED];
            }

            list($_files) = fn_get_product_files(['product_id' => $_REQUEST['product_id']]);
            list($_folder) = fn_get_product_file_folders(['product_id' => $_REQUEST['product_id']]);

            if (empty($_files) && empty($_folder)) {
                Tygh::$app['view']->assign('product_id', $_REQUEST['product_id']);
            }
        }

        return [
            CONTROLLER_STATUS_OK,
            fn_url('products.update?product_id=' . $_REQUEST['product_id'] . '&selected_section=files')
        ];
    }

    if ($mode === 'delete_folder') {
        if (!empty($_REQUEST['folder_id']) && !empty($_REQUEST['product_id'])) {
            if (fn_delete_product_file_folders($_REQUEST['folder_id'], $_REQUEST['product_id']) === false) {
                return [CONTROLLER_STATUS_DENIED];
            }

            list($product_files) = fn_get_product_files(['product_id' => $_REQUEST['product_id']]);
            list($product_file_folders) = fn_get_product_file_folders(['product_id' => $_REQUEST['product_id']]);
            $files_tree = fn_build_files_tree($product_file_folders, $product_files);

            Tygh::$app['view']->assign('product_file_folders', $product_file_folders);
            Tygh::$app['view']->assign('product_files', $product_files);
            Tygh::$app['view']->assign('files_tree', $files_tree);

            Tygh::$app['view']->assign('product_id', $_REQUEST['product_id']);
        }

        return [
            CONTROLLER_STATUS_OK,
            fn_url('products.update?product_id=' . $_REQUEST['product_id'] . '&selected_section=files')
        ];
    }

    if ($mode === 'm_update_categories') {
        $product_ids = isset($_REQUEST['products_ids']) ? (array) $_REQUEST['products_ids'] : [];
        $categories_map = isset($_REQUEST['categories_map']) ? (array) $_REQUEST['categories_map'] : [];

        if (empty($product_ids)) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        list($products) = fn_get_products([
            'pid'     => $product_ids,
            'sort_by' => null,
        ]);

        foreach ($products as $product) {
            $category_ids = $product['category_ids'];

            if (!empty($categories_map['A'])) {
                $category_ids = array_merge($category_ids, $categories_map['A']);
                $category_ids = array_unique($category_ids);
            }

            if (!empty($categories_map['D'])) {
                $category_ids = array_diff($category_ids, $categories_map['D']);
            }

            if (empty($category_ids)) {
                fn_set_notification('W', __('warning'), __('bulk_edit.some_products_were_omitted'));
                continue;
            }

            //phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
            if ($category_ids == $product['category_ids']) {
                continue;
            }

            $product['category_ids'] = $category_ids;
            fn_update_product_categories($product['product_id'], $product);
        }
    }

    if ($mode === 'update_feature') {
        $lang_code = isset($_REQUEST['lang_code']) ? (string) $_REQUEST['lang_code'] : CART_LANGUAGE;
        $product_id = isset($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : null;
        $feature_id = isset($_REQUEST['feature_id']) ? (int) $_REQUEST['feature_id'] : null;
        $feature_value = isset($_REQUEST['feature_value']) ? (string) $_REQUEST['feature_value'] : '';

        if (!$product_id || !$feature_id) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        fn_update_product(['product_features' => [$feature_id => $feature_value]], $product_id, $lang_code);

        if (isset($_REQUEST['return_url'])) {
            return [CONTROLLER_STATUS_REDIRECT, (string) $_REQUEST['return_url']];
        }

        return [
            CONTROLLER_STATUS_REDIRECT,
            Url::buildUrn('products.update', [
                'product_id'       => $product_id,
                'selected_section' => 'features'
            ])
        ];
    }

    if ($mode === 'update_option') {
        $option_id = isset($_REQUEST['option_id']) ? (int) $_REQUEST['option_id'] : null;
        $product_id = isset($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : null;

        if (!$product_id || !$option_id) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        fn_add_global_option_link($product_id, $option_id);

        if (isset($_REQUEST['return_url'])) {
            return [CONTROLLER_STATUS_REDIRECT, (string) $_REQUEST['return_url']];
        }


        return [
            CONTROLLER_STATUS_REDIRECT,
            Url::buildUrn('products.update', [
                'product_id'       => $product_id,
                'selected_section' => 'options'
            ])
        ];
    }

    if ($mode === 'manage') {
        $params = [];
        if (!empty($_REQUEST['company_ids'])) {
            $params['company_ids'] = (array) $_REQUEST['company_ids'];
        }

        if (!empty($params)) {
            unset($_REQUEST['redirect_url'], $_REQUEST['page']);

            return [CONTROLLER_STATUS_REDIRECT, Url::buildUrn(['products', 'manage'], $params)];
        }
    }

    if (
        $mode === 'm_delete_subscription'
        && !empty($_REQUEST['subscriber_ids'])
        && !empty($_REQUEST['product_id'])
    ) {
        // Get current product data
        $skip_company_condition = !fn_is_product_company_condition_required($_REQUEST['product_id']);

        $product_data = fn_get_product_data(
            $_REQUEST['product_id'],
            $auth,
            DESCR_SL,
            '',
            true,
            true,
            true,
            true,
            false,
            false,
            $skip_company_condition
        );

        if (
            !Registry::get('runtime.company_id')
            || Registry::get('runtime.company_id')
            && (int) $product_data['company_id'] === Registry::get('runtime.company_id')
        ) {
            fn_delete_product_subscribers($_REQUEST['subscriber_ids']);
        }

        return [CONTROLLER_STATUS_OK];
    }

    return [CONTROLLER_STATUS_OK, 'products' . $suffix];
}

// Management products
if ($mode === 'manage' || $mode === 'p_subscr') {
    unset(Tygh::$app['session']['product_ids']);
    unset(Tygh::$app['session']['selected_fields']);

    $params = $_REQUEST;
    $params['only_short_fields'] = true;
    $params['apply_disabled_filters'] = true;
    $params['extend'][] = 'companies';

    if (fn_allowed_for('ULTIMATE')) {
        $params['extend'][] = 'sharing';
    }

    if ($mode === 'p_subscr') {
        $params['get_subscribers'] = true;
    }

    list($products, $search) = fn_get_products($params, Registry::get('settings.Appearance.admin_elements_per_page'), DESCR_SL);
    fn_gather_additional_products_data($products, ['get_icon' => true, 'get_detailed' => true, 'get_options' => false, 'get_discounts' => false]);

    $page = $search['page'];
    $valid_page = db_get_valid_page($page, $search['items_per_page'], $search['total_items']);

    if ($page > $valid_page) {
        $_REQUEST['page'] = $valid_page;
        return [CONTROLLER_STATUS_REDIRECT, Registry::get('config.current_url')];
    }
    $has_select_permission = fn_check_permissions('products', 'm_delete', 'admin')
        || fn_check_permissions('products', 'export_range', 'admin');

    Tygh::$app['view']->assign('products', $products);
    Tygh::$app['view']->assign('search', $search);
    Tygh::$app['view']->assign('has_select_permission', $has_select_permission);

    if (!empty($_REQUEST['redirect_if_one']) && (int) $search['total_items'] === 1) {
        return [CONTROLLER_STATUS_REDIRECT, 'products.update?product_id=' . $products[0]['product_id']];
    }

    $selected_fields = fn_get_product_fields();

    Tygh::$app['view']->assign('selected_fields', $selected_fields);

    $filter_params = [
        'get_product_features' => true,
        'short'                => true,
        'feature_type'         => str_split(ProductFeatures::getAllTypes())
    ];

    if (!empty($_REQUEST['filter_variants'])) {
        $filter_params['variants_only'] = $_REQUEST['filter_variants'];
    }

    list($filters) = fn_get_product_filters($filter_params);
    Tygh::$app['view']->assign('filter_items', $filters);
    unset($filters);

    $feature_params = [
        'plain'           => true,
        'statuses'        => [ObjectStatuses::ACTIVE, ObjectStatuses::HIDDEN],
        'variants'        => true,
        'exclude_group'   => true,
        'exclude_filters' => true
    ];

    // Preload variants selected at search form. They will be shown at AJAX variants loader as pre-selected.
    if (!empty($_REQUEST['feature_variants'])) {
        $feature_params['variants_only'] = $_REQUEST['feature_variants'];
    }

    list($features, $features_search) = fn_get_product_features($feature_params, PRODUCT_FEATURES_THRESHOLD);

    if ($features_search['total_items'] <= PRODUCT_FEATURES_THRESHOLD) {
        Tygh::$app['view']->assign('feature_items', $features);
    } else {
        Tygh::$app['view']->assign('feature_items_too_many', true);
    }
}

// 'Add new product' page
if ($mode === 'add') {
    Tygh::$app['view']->assign('taxes', fn_get_taxes());

    // [Page sections]
    Registry::set('navigation.tabs', [
        'detailed'      => [
            'title' => __('general'),
            'js'    => true
        ],
        'seo'           => [
            'title' => __('seo'),
            'js'    => true
        ],
        'qty_discounts' => [
            'title' => __('qty_discounts'),
            'js'    => true
        ],
        'addons'        => [
            'title' => __('addons'),
            'js'    => true
        ],
    ]);
    // [/Page sections]

    $product_data = (array) fn_restore_post_data('product_data');

    if (isset($_REQUEST['product_data']['company_id']) && !Registry::get('runtime.company_id')) {
        $product_data['company_id'] = $_REQUEST['product_data']['company_id'];
    }

    if (isset($_REQUEST['category_id'])) {
        $product_data['category_ids'] = (array) $_REQUEST['category_id'];
    }

    if (
        empty($product_data['main_category'])
        && !empty($product_data['category_ids'])
        && is_array($product_data['category_ids'])
    ) {
        $product_data['main_category'] = reset($product_data['category_ids']);
    }

    if (fn_allowed_for('MULTIVENDOR') && Registry::get('runtime.company_id')) {
        Tygh::$app['view']->assign('disable_edit_popularity', true);
    } else {
        Tygh::$app['view']->assign('disable_edit_popularity', false);
    }

    Tygh::$app['view']->assign('product_data', $product_data);

// 'Multiple products addition' page
} elseif ($mode === 'm_add') {
// 'product update' page
} elseif ($mode === 'update') {
    $selected_section = (empty($_REQUEST['selected_section']) ? 'detailed' : $_REQUEST['selected_section']);

    // Get current product data
    $skip_company_condition = !fn_is_product_company_condition_required($_REQUEST['product_id']);

    $product_data = fn_get_product_data($_REQUEST['product_id'], $auth, DESCR_SL, '', true, true, true, true, false, false, $skip_company_condition);

    if (!empty($_REQUEST['deleted_subscription_id'])) {
        if (
            !Registry::get('runtime.company_id')
            || (Registry::get('runtime.company_id') && (int) $product_data['company_id'] === (int) Registry::get('runtime.company_id'))
        ) {
            fn_delete_product_subscribers($_REQUEST['deleted_subscription_id']);
        }
    }

    if (empty($product_data)) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    list($product_features, $features_search) = fn_get_paginated_product_features(['product_id' => $product_data['product_id']], $auth, $product_data);

    $taxes = fn_get_taxes();
    $allow_save_feature =
        fn_allowed_for('ULTIMATE')
        || !fn_get_runtime_company_id()
        || YesNo::toBool(Registry::get('settings.Vendors.allow_vendor_manage_features'));

    Tygh::$app['view']->assign([
        'product_features'   => $product_features,
        'features_search'    => $features_search,
        'product_data'       => $product_data,
        'taxes'              => $taxes,
        'allow_save_feature' => $allow_save_feature,
        'selected_section'   => $selected_section,
    ]);

    $product_options = fn_get_product_options($_REQUEST['product_id'], DESCR_SL);

    list($global_options) = fn_get_product_global_options();
    Tygh::$app['view']->assign([
        'product_options' => $product_options,
        'global_options'  => $global_options,
    ]);

    // If the product is electronnicaly distributed, get the assigned files
    list($product_files) = fn_get_product_files(['product_id' => $_REQUEST['product_id']]);
    list($product_file_folders) = fn_get_product_file_folders(['product_id' => $_REQUEST['product_id']]);
    $files_tree = fn_build_files_tree($product_file_folders, $product_files);

    $sharing_company_id = Registry::get('runtime.company_id')
        ? Registry::get('runtime.company_id')
        : $product_data['company_id'];

    $default_language = Registry::get('settings.Appearance.frontend_default_language');

    // Preview URL only exists for companies that have this product shared
    if (fn_allowed_for('ULTIMATE') && !in_array($sharing_company_id, $product_data['shared_between_companies'])) {
        $preview_url = null;
    } elseif (fn_allowed_for('MULTIVENDOR')) {
        /** @var \Tygh\Storefront\Repository $storefront_repository */
        $storefront_repository = Tygh::$app['storefront.repository'];
        $storefront = $storefront_repository->findByCompanyId($product_data['company_id']);
        $storefront = empty($storefront) ? $storefront_repository->findDefault() : $storefront;

        $preview_url = fn_get_preview_url(
            "products.view?product_id={$_REQUEST['product_id']}&storefront_id={$storefront->storefront_id}",
            $product_data,
            $auth['user_id'],
            $default_language
        );
    } else {
        $preview_url = fn_get_preview_url(
            "products.view?product_id={$_REQUEST['product_id']}",
            $product_data,
            $auth['user_id'],
            $default_language
        );
    }

    if (fn_allowed_for('MULTIVENDOR') && Registry::get('runtime.company_id')) {
        Tygh::$app['view']->assign('disable_edit_popularity', true);
    } else {
        Tygh::$app['view']->assign('disable_edit_popularity', false);
    }

    list($subscribers, $search) = fn_get_product_subscribers($_REQUEST, Registry::get('settings.Appearance.admin_elements_per_page'));

    $main_category_data = fn_get_category_data($product_data['main_category']);
    if (!empty($main_category_data)) {
        Tygh::$app['view']->assign('selected_storefront_id', $main_category_data['storefront_id']);
    }

    Tygh::$app['view']->assign([
        'view_uri'                   => $preview_url,
        'product_file_folders'       => $product_file_folders,
        'product_files'              => $product_files,
        'files_tree'                 => $files_tree,
        'expand_all'                 => true,
        'product_subscribers'        => $subscribers,
        'product_subscribers_search' => $search,
        'is_allow_update_products'   => fn_check_permissions('products', 'update', 'admin', Http::POST),
    ]);


    // [Page sections]
    $tabs = [
        'detailed'      => [
            'title' => __('general'),
            'js'    => true
        ],
        'shippings'     => [
            'title' => __('shipping_properties'),
            'js'    => true
        ],
        'options'       => [
            'title' => __('options'),
            'js'    => true
        ],
    ];

    if ($product_features) {
        $tabs['features'] = [
            'title' => __('features'),
            'js'    => true
        ];
    }

    $tabs['seo'] = [
        'title' => __('seo'),
        'js'    => true,
    ];

    $tabs['qty_discounts'] = [
        'title' => __('qty_discounts'),
        'js'    => true
    ];

    if (Registry::get('settings.General.enable_edp') === YesNo::YES) {
        $tabs['files'] = [
            'title' => __('sell_files'),
            'js'    => true
        ];
    }

    $tabs['subscribers'] = [
        'title' => __('subscribers'),
        'js'    => true
    ];

    $tabs['addons'] = [
        'title' => __('addons'),
        'js'    => true
    ];

    // [Product tabs]
    // block manager is disabled for vendors.
    if (
        !(
             (fn_allowed_for('MULTIVENDOR') && Registry::get('runtime.company_id'))
            ||
             (fn_allowed_for('ULTIMATE') && !Registry::get('runtime.company_id'))
        )
    ) {
        $dynamic_object = SchemesManager::getDynamicObject($_REQUEST['dispatch'], AREA);

        if (!empty($dynamic_object)) {
            if (Registry::get('runtime.mode') !== 'add' && !empty($_REQUEST[$dynamic_object['key']])) {
                $params = [
                    'dynamic_object'       => [
                        'object_type' => $dynamic_object['object_type'],
                        'object_id'   => $_REQUEST[$dynamic_object['key']]
                    ],
                    $dynamic_object['key'] => $_REQUEST[$dynamic_object['key']]
                ];

                $tabs['product_tabs'] = [
                    'title' => __('product_details_tabs'),
                    'href'  => 'tabs.manage_in_tab?' . http_build_query($params),
                    'ajax'  => true,
                ];
            }
        }
    }
    // [/Product tabs]
    Registry::set('navigation.tabs', $tabs);
    // [/Page sections]

// 'Mulitple products updating' page
} elseif ($mode === 'm_update') {
    if (
        empty(Tygh::$app['session']['product_ids'])
        || empty(Tygh::$app['session']['selected_fields'])
        || empty(Tygh::$app['session']['selected_fields']['object'])
        || Tygh::$app['session']['selected_fields']['object'] !== 'product'
    ) {
        return [CONTROLLER_STATUS_REDIRECT, 'products.manage'];
    }

    $product_ids = Tygh::$app['session']['product_ids'];

    if (fn_allowed_for('MULTIVENDOR') && !fn_company_products_check($product_ids)) {
        return [CONTROLLER_STATUS_DENIED];
    }

    $selected_fields = Tygh::$app['session']['selected_fields'];

    $field_groups = [
        'A' => [ // inputs
            'product'      => 'products_data',
            'product_code' => 'products_data',
            'page_title'   => 'products_data',
        ],

        'B' => [ // short inputs
            'price'            => 'products_data',
            'list_price'       => 'products_data',
            'amount'           => 'products_data',
            'min_qty'          => 'products_data',
            'max_qty'          => 'products_data',
            'weight'           => 'products_data',
            'shipping_freight' => 'products_data',
            'box_height'       => 'products_data',
            'box_length'       => 'products_data',
            'box_width'        => 'products_data',
            'min_items_in_box' => 'products_data',
            'max_items_in_box' => 'products_data',
            'qty_step'         => 'products_data',
            'list_qty_count'   => 'products_data',
            'popularity'       => 'products_data'
        ],

        'C' => [ // checkboxes
            'free_shipping' => 'products_data',
        ],

        'D' => [ // textareas
            'short_description' => 'products_data',
            'full_description'  => 'products_data',
            'meta_keywords'     => 'products_data',
            'meta_description'  => 'products_data',
            'search_words'      => 'products_data',
            'promo_text'        => 'products_data',
        ],
        'T' => [ // dates
            'timestamp'   => 'products_data',
            'avail_since' => 'products_data',
        ],
        'S' => [ // selectboxes
            'out_of_stock_actions' => [
                'name'     => 'products_data',
                'variants' => [
                    OutOfStockActions::NONE           => 'none',
                    OutOfStockActions::BUY_IN_ADVANCE => 'buy_in_advance',
                    OutOfStockActions::SUBSCRIBE      => 'sign_up_for_notification'
                ],
            ],
            'status' => [
                'name'     => 'products_data',
                'variants' => [
                    ObjectStatuses::ACTIVE   => 'active',
                    ObjectStatuses::DISABLED => 'disabled',
                    ObjectStatuses::HIDDEN   => 'hidden'
                ],
            ],
            'tracking' => [
                'name'     => 'products_data',
                'variants' => [
                    ProductTracking::TRACK        => 'track',
                    ProductTracking::DO_NOT_TRACK => 'dont_track'
                ],
            ],
            'zero_price_action' => [
                'name'     => 'products_data',
                'variants' => [
                    ProductZeroPriceActions::NOT_ALLOW_ADD_TO_CART => 'zpa_refuse',
                    ProductZeroPriceActions::ALLOW_ADD_TO_CART     => 'zpa_permit',
                    ProductZeroPriceActions::ASK_TO_ENTER_PRICE    => 'zpa_ask_price'
                ],
            ],
        ],
        'E' => [ // categories
            'categories' => 'products_data'
        ],
        'W' => [ // Product details layout
            'details_layout' => 'products_data'
        ],
        'L' => [ // multiple selectbox (localization)
            'localization' => [
                'name' => 'localization'
            ],
        ]
    ];

    if (Registry::get('settings.General.enable_edp') === YesNo::YES) {
        $field_groups['C']['is_edp'] = 'products_data';
        $field_groups['C']['edp_shipping'] = 'products_data';
    }

    $data = array_keys($selected_fields['data']);
    $get_taxes = false;
    $get_features = false;

    $fields2update = $data;

    if (!empty($selected_fields['data']['features']) && $selected_fields['data']['features'] === YesNo::YES) {
        $fields2update[] = 'features';
        $get_features = true;

        list($all_product_features, $all_features_search) = fn_get_paginated_product_features(['over' => true], $auth, []);

        Tygh::$app['view']->assign([
            'all_product_features' => $all_product_features,
            'all_features_search'  => $all_features_search,
        ]);
    }

    // Process fields that are not in products or product_descriptions tables
    if (!empty($selected_fields['categories']) && $selected_fields['categories'] === YesNo::YES) {
        $fields2update[] = 'categories';
    }
    if (!empty($selected_fields['main_pair']) && $selected_fields['main_pair'] === YesNo::YES) {
        $fields2update[] = 'main_pair';
    }
    if (!empty($selected_fields['data']['taxes']) && $selected_fields['data']['taxes'] === YesNo::YES) {
        Tygh::$app['view']->assign('taxes', fn_get_taxes());
        $fields2update[] = 'taxes';
        $get_taxes = true;
    }

    $product_features = [];
    $features_search = [];
    $products_data = [];

    foreach ($product_ids as $value) {
        $products_data[$value] = fn_get_product_data($value, $auth, DESCR_SL, '?:products.*, ?:product_descriptions.*', false, true, $get_taxes, false, false, false, true);
        $products_data[$value]['price'] = fn_format_price($products_data[$value]['price'], CART_PRIMARY_CURRENCY, 2, false);
        $products_data[$value]['base_price'] = $products_data[$value]['price'];

        if ($get_features) {
            list($product_features[$value], $features_search[$value]) = fn_get_paginated_product_features(['product_id' => $value], $auth, $products_data[$value]);
        }
    }

    Tygh::$app['view']->assign([
        'product_features' => $product_features,
        'features_search'  => $features_search,
    ]);

    $filled_groups = [];
    $field_names = [];

    foreach ($fields2update as $field) {
        if ($field === 'main_pair') {
            $desc = 'image_pair';
        } elseif ($field === 'tracking') {
            $desc = 'inventory';
        } elseif ($field === 'edp_shipping') {
            $desc = 'downloadable_shipping';
        } elseif ($field === 'is_edp') {
            $desc = 'downloadable';
        } elseif ($field === 'timestamp') {
            $desc = 'creation_date';
        } elseif ($field === 'categories') {
            $desc = 'categories';
        } elseif ($field === 'status') {
            $desc = 'status';
        } elseif ($field === 'avail_since') {
            $desc = 'available_since';
        } elseif ($field === 'min_qty') {
            $desc = 'min_order_qty';
        } elseif ($field === 'max_qty') {
            $desc = 'max_order_qty';
        } elseif ($field === 'qty_step') {
            $desc = 'quantity_step';
        } elseif ($field === 'list_qty_count') {
            $desc = 'list_quantity_count';
        } elseif ($field === 'usergroup_ids') {
            $desc = 'usergroups';
        } elseif ($field === 'details_layout') {
            $desc = 'product_details_view';
        } elseif ($field === 'max_items_in_box') {
            $desc = 'maximum_items_in_box';
        } elseif ($field === 'min_items_in_box') {
            $desc = 'minimum_items_in_box';
        } elseif ($field === 'amount') {
            $desc = 'quantity';
        } else {
            $desc = $field;
        }

        if (!empty($field_groups['A'][$field])) {
            $filled_groups['A'][$field] = __($desc);
            continue;
        } elseif (!empty($field_groups['B'][$field])) {
            $filled_groups['B'][$field] = __($desc);
            continue;
        } elseif (!empty($field_groups['C'][$field])) {
            $filled_groups['C'][$field] = __($desc);
            continue;
        } elseif (!empty($field_groups['D'][$field])) {
            $filled_groups['D'][$field] = __($desc);
            continue;
        } elseif (!empty($field_groups['S'][$field])) {
            $filled_groups['S'][$field] = __($desc);
            continue;
        } elseif (!empty($field_groups['T'][$field])) {
            $filled_groups['T'][$field] = __($desc);
            continue;
        } elseif (!empty($field_groups['E'][$field])) {
            $filled_groups['E'][$field] = __($desc);
            continue;
        } elseif (!empty($field_groups['L'][$field])) {
            $filled_groups['L'][$field] = __($desc);
            continue;
        } elseif (!empty($field_groups['W'][$field])) {
            $filled_groups['W'][$field] = __($desc);
            continue;
        }

        $field_names[$field] = __($desc);
    }

    ksort($filled_groups, SORT_STRING);

    Tygh::$app['view']->assign([
        'field_names'   => $field_names,
        'field_groups'  => $field_groups,
        'filled_groups' => $filled_groups,
        'products_data' => $products_data,
    ]);
} elseif ($mode === 'get_file') {
    if (fn_get_product_file($_REQUEST['file_id'], !empty($_REQUEST['file_type'])) === false) {
        return [CONTROLLER_STATUS_DENIED];
    }
    exit;
} elseif ($mode === 'update_file') {
    if (!empty($_REQUEST['product_id'])) {
        if (!empty($_REQUEST['file_id'])) {
            $params = [
                'product_id' => $_REQUEST['product_id'],
                'file_ids'   => $_REQUEST['file_id']
            ];

            list($product_files) = fn_get_product_files($params);

            if (!$product_files) {
                return [CONTROLLER_STATUS_NO_PAGE];
            }

            $product_file = reset($product_files);
            $product_file['company_id'] = db_get_field('SELECT company_id FROM ?:products WHERE product_id = ?i', $_REQUEST['product_id']);

            Tygh::$app['view']->assign('product_file', $product_file);
        }

        list($product_file_folders) = fn_get_product_file_folders(['product_id' => $_REQUEST['product_id']]);
        Tygh::$app['view']->assign('product_file_folders', $product_file_folders);

        Tygh::$app['view']->assign('product_id', $_REQUEST['product_id']);
    }
} elseif ($mode === 'update_folder') {
    if (!empty($_REQUEST['product_id'])) {
        if (!empty($_REQUEST['folder_id'])) {
            $params = [
                'product_id' => $_REQUEST['product_id'],
                'folder_ids' => $_REQUEST['folder_id']
            ];

            list($product_file_folders) = fn_get_product_file_folders($params);
            if (!$product_file_folders) {
                return [CONTROLLER_STATUS_NO_PAGE];
            }

            $product_file_folder = reset($product_file_folders);
            $product_file_folder['company_id'] = db_get_field('SELECT company_id FROM ?:products WHERE product_id = ?i', $_REQUEST['product_id']);

            Tygh::$app['view']->assign('product_file_folder', $product_file_folder);
        }

        Tygh::$app['view']->assign('product_id', $_REQUEST['product_id']);
    }
} elseif ($mode === 'get_features') {
    list($product_features, $features_search) = fn_get_paginated_product_features($_REQUEST, $auth, []);

    Tygh::$app['view']->assign('product_features', $product_features);
    Tygh::$app['view']->assign('features_search', $features_search);
    Tygh::$app['view']->assign('product_id', $_REQUEST['product_id']);

    if (!empty($_REQUEST['over'])) {
        Tygh::$app['view']->assign('over', $_REQUEST['over']);
    }
    if (!empty($_REQUEST['data_name'])) {
        Tygh::$app['view']->assign('data_name', $_REQUEST['data_name']);
    }

    if (!empty($_REQUEST['multiple'])) {
        Tygh::$app['view']->display('views/products/components/products_m_update_features.tpl');
    } else {
        Tygh::$app['view']->display('views/products/components/products_update_features.tpl');
    }
    exit;
} elseif ($mode === 'get_products_list') {
    if (!defined('AJAX_REQUEST')) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    /** @var \Tygh\Ajax $ajax */
    $ajax = Tygh::$app['ajax'];

    /** @var \Tygh\Tools\Formatter $formatter */
    $formatter = Tygh::$app['formatter'];

    $page_size = isset($_REQUEST['page_size']) ? (int) $_REQUEST['page_size'] : 10;
    $lang_code = isset($_REQUEST['lang_code']) ? $_REQUEST['lang_code'] : CART_LANGUAGE;

    if (isset($request['ids'])) {
        $page_size = 0;
    }

    $image_width = Registry::get('settings.Thumbnails.product_admin_mini_icon_width');
    $image_height = Registry::get('settings.Thumbnails.product_admin_mini_icon_height');

    $image_width = $image_width ?: $image_height;
    $image_height = $image_height ?: $image_width;

    $params = fn_products_form_product_list_params($_REQUEST);

    list($products, $params) = fn_get_products($params, $page_size, $lang_code);

    if (!empty($params['pid'])) {
        $products = fn_sort_by_ids($products, $params['pid']);
    }

    fn_gather_additional_products_data($products, [
        'get_icon'      => true,
        'get_detailed'  => true,
        'get_options'   => false,
        'get_discounts' => false
    ]);

    $objects = array_values(array_map(static function ($product) use ($formatter, $image_width, $image_height) {
        $data = [
            'product_id'      => $product['product_id'],
            'product'         => $product['product'],
            'price'           => $product['price'],
            'price_formatted' => $formatter->asPrice($product['price']),
            'product_code'    => $product['product_code'],
            'company_id'      => $product['company_id'],
            'amount_text'     => __('n_in_stock_products_short', [
                '[quantity]'    => $product['amount']
            ]),
            'image_width'     => $image_width,
            'image_height'    => $image_height,
            'image'           => empty($product['main_pair']) ? [] : fn_image_to_display($product['main_pair'], $image_width, $image_height),
            'url'             => fn_url('products.update?product_id=' . $product['product_id']),
            'has_options'     => $product['has_options'],
            'aoc'             => isset($_REQUEST['aoc']) ? YesNo::YES : YesNo::NO
        ];

        $company_name = fn_get_company_name($product['company_id']);
        if ($company_name) {
            $data['company_name'] = $company_name;
        }

        return [
            'id'              => $product['product_id'],
            'text'            => $product['product'],
            'price'           => $product['price'],
            'code'            => $product['product_code'],
            'data'            => $data
        ];
    }, $products));

    $ajax->assign('objects', $objects);
    $ajax->assign('total_objects', isset($params['total_items']) ? $params['total_items'] : count($objects));

    Registry::set('runtime.get_products_list.products', $products);

    return [CONTROLLER_STATUS_NO_CONTENT];
} elseif ($mode === 'export_found') {
    if (empty(Tygh::$app['session']['export_ranges'])) {
        Tygh::$app['session']['export_ranges'] = [];
    }

    if (empty(Tygh::$app['session']['export_ranges']['products']['pattern_id'])) {
        Tygh::$app['session']['export_ranges']['products'] = ['pattern_id' => 'products'];
    }

    Tygh::$app['session']['export_ranges']['products']['data_provider'] = [
        'count_function' => 'fn_exim_get_last_view_products_count',
        'function'       => 'fn_exim_get_last_view_product_ids_condition',
    ];

    unset($_REQUEST['redirect_url'], Tygh::$app['session']['export_ranges']['products']['data']);

    return [
        CONTROLLER_STATUS_OK,
        'exim.export?section=products&pattern_id=' . Tygh::$app['session']['export_ranges']['products']['pattern_id'],
    ];
}
