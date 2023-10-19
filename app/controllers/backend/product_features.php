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

use Tygh\Common\OperationResult;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\ProductFeatures;
use Tygh\Enum\YesNo;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

fn_define('KEEP_UPLOADED_FILES', true);
fn_define('NEW_FEATURE_GROUP_ID', 'OG');

$navigation_sections = [
    'features' => [
        'title' => __('features'),
        'href'  => fn_url('product_features.manage'),
    ],
    'groups'   => [
        'title' => __('feature_groups'),
        'href'  => fn_url('product_features.groups'),
    ],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    fn_trusted_vars('feature_data');
    $return_url = 'product_features.manage';

    // Update features
    if ($mode === 'update') {
        if (!empty($_REQUEST['feature_data'])) {
            $feature_id = fn_update_product_feature($_REQUEST['feature_data'], $_REQUEST['feature_id'], DESCR_SL);
        } else {
            $feature_id = false;
        }

        if (defined('AJAX_REQUEST')) {
            /** @var \Tygh\Ajax $ajax */
            $ajax = Tygh::$app['ajax'];

            if ($feature_id === false) {
                $ajax->assign('success', false);
            } else {
                list($variants) = fn_get_product_feature_variants(['feature_id' => $feature_id]);

                $ajax->assign('success', true);
                $ajax->assign('feature_id', $feature_id);
                $ajax->assign('variants', $variants);
            }
        }

        if ($feature_id === false) {
            return [CONTROLLER_STATUS_NO_PAGE];
        } else {
            return [CONTROLLER_STATUS_OK, 'product_features.update?feature_id=' . $feature_id];
        }
    }

    if ($mode === 'update_status') {
        $params = array_merge(
            [
                'table'   => 'product_features',
                'id_name' => 'feature_id',
                'status'  => ObjectStatuses::ACTIVE,
                'id'      => null,
            ],
            $_REQUEST
        );
        OperationResult::wrap(
            static function () use ($params) {
                $feature_id = (int) $params['id'];
                if (!fn_check_company_id('product_features', 'feature_id', $feature_id)) {
                    fn_company_access_denied_notification();
                    return false;
                }

                if (!fn_tools_update_status($params)) {
                    return false;
                }

                if (in_array($params['status'], [ObjectStatuses::DISABLED, ObjectStatuses::HIDDEN], true)) {
                    fn_disable_product_feature_filters($feature_id);
                }

                return true;
            }
        )->showNotifications();

        return [CONTROLLER_STATUS_NO_CONTENT];
    }

    if ($mode === 'delete') {
        if (!empty($_REQUEST['feature_id'])) {
            fn_delete_feature($_REQUEST['feature_id']);
        }

        if (!empty($_REQUEST['return_url'])) {
            $return_url = $_REQUEST['return_url'];
        }
    }

    if ($mode === 'm_delete') {
        if (!empty($_REQUEST['feature_ids'])) {
            $feature_ids = (array) $_REQUEST['feature_ids'];

            foreach ($feature_ids as $feature_id) {
                fn_delete_feature($feature_id);
            }
        }

        if (!empty($_REQUEST['return_url'])) {
            $return_url = $_REQUEST['return_url'];
        }
    }

    if (
        $mode === 'm_update_statuses'
        && !empty($_REQUEST['feature_ids'])
        && is_array($_REQUEST['feature_ids'])
        && !empty($_REQUEST['status'])
        && in_array($_REQUEST['status'], [ObjectStatuses::ACTIVE, ObjectStatuses::DISABLED, ObjectStatuses::HIDDEN], true)
    ) {
        $status_to = $_REQUEST['status'];

        foreach ($_REQUEST['feature_ids'] as $feature_id) {
            OperationResult::wrap(
                static function () use ($feature_id, $status_to) {
                    $feature_id = (int) $feature_id;
                    $params = [
                        'table'             => 'product_features',
                        'status'            => $status_to,
                        'id_name'           => 'feature_id',
                        'id'                => $feature_id,
                        'show_error_notice' => false,
                    ];

                    if (!fn_check_company_id('product_features', 'feature_id', $feature_id)) {
                        fn_company_access_denied_notification();
                        return false;
                    }

                    if (!fn_tools_update_status($params)) {
                        return false;
                    }

                    if (in_array($params['status'], [ObjectStatuses::DISABLED, ObjectStatuses::HIDDEN], true)) {
                        fn_disable_product_feature_filters($feature_id);
                    }

                    return true;
                }
            )->showNotifications();
        }

        if (defined('AJAX_REQUEST')) {
            $redirect_url = fn_url('product_features.manage');
            if (isset($_REQUEST['redirect_url'])) {
                $redirect_url = $_REQUEST['redirect_url'];
            }
            Tygh::$app['ajax']->assign('force_redirection', $redirect_url);
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            return [CONTROLLER_STATUS_NO_CONTENT];
        }
    }

    if (
        $mode === 'm_set_display'
        && !empty($_REQUEST['display_on'])
        && in_array($_REQUEST['display_on'], ['product', 'catalog', 'header'], true)
        && !empty($_REQUEST['value'])
        && in_array($_REQUEST['value'], [YesNo::YES, YesNo::NO], true)
        && !empty($_REQUEST['feature_ids'])
        && is_array($_REQUEST['feature_ids'])
    ) {
        $field_name = 'display_on_' . $_REQUEST['display_on'];
        $value = $_REQUEST['value'];
        list($group_features) = fn_get_product_features(['feature_types' => ProductFeatures::GROUP], 0, DESCR_SL);

        foreach ($_REQUEST['feature_ids'] as $feature_id) {
            $feature_data = fn_get_product_feature_data($feature_id);
            $feature_data[$field_name] = $value;

            OperationResult::wrap(
                static function () use ($feature_id, $feature_data, $field_name, $group_features) {
                    if (!fn_check_company_id('product_features', 'feature_id', $feature_id)) {
                        fn_company_access_denied_notification();
                        return false;
                    }

                    if (
                        isset($group_features[$feature_data['parent_id']][$field_name])
                        && YesNo::toBool($group_features[$feature_data['parent_id']][$field_name])
                    ) {
                        return false;
                    }

                    return fn_update_product_feature($feature_data, $feature_id);
                }
            )->showNotifications();
        }

        if (defined('AJAX_REQUEST')) {
            $redirect_url = fn_url('product_features.manage');
            if (isset($_REQUEST['redirect_url'])) {
                $redirect_url = $_REQUEST['redirect_url'];
            }
            Tygh::$app['ajax']->assign('force_redirection', $redirect_url);
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            return [CONTROLLER_STATUS_NO_CONTENT];
        }
    }

    if (
        $mode === 'm_set_categories'
        && !empty($_REQUEST['features_ids'])
        && is_array($_REQUEST['features_ids'])
        && !empty($_REQUEST['categories_map'])
        && is_array($_REQUEST['categories_map'])
    ) {
        $added_categories = empty($_REQUEST['categories_map']['A']) ? [] : $_REQUEST['categories_map']['A'];
        $deleted_categories = empty($_REQUEST['categories_map']['D']) ? [] : $_REQUEST['categories_map']['D'];

        foreach ($_REQUEST['features_ids'] as $feature_id) {
            $feature_data = fn_get_product_feature_data($feature_id);

            if (!$feature_data) {
                continue;
            }

            $feature_categories = explode(',', $feature_data['categories_path']);
            $feature_categories = array_merge($feature_categories, $added_categories);
            $feature_categories = array_unique(array_filter(array_diff($feature_categories, $deleted_categories)));
            $categories_path = implode(',', $feature_categories);

            OperationResult::wrap(
                static function () use ($feature_id, $categories_path) {
                    if (!fn_check_company_id('product_features', 'feature_id', $feature_id)) {
                        fn_company_access_denied_notification();
                        return false;
                    }

                    return fn_update_product_feature(
                        [
                            'categories_path' => $categories_path,
                        ],
                        $feature_id
                    );
                }
            )->showNotifications();
        }
    }

    if (
        $mode === 'm_set_group'
        && !empty($_REQUEST['feature_ids'])
        && is_array($_REQUEST['feature_ids'])
        && isset($_REQUEST['selected_group'])
    ) {
        $group_id = (int) $_REQUEST['selected_group'];

        foreach ($_REQUEST['feature_ids'] as $feature_id) {
            OperationResult::wrap(
                static function () use ($group_id, $feature_id) {
                    if (!fn_check_company_id('product_features', 'feature_id', $feature_id)) {
                        fn_company_access_denied_notification();
                        return false;
                    }

                    return fn_update_product_feature(
                        [
                            'parent_id' => $group_id,
                        ],
                        $feature_id
                    );
                }
            )->showNotifications();
        }
    }

    return [CONTROLLER_STATUS_OK, $return_url];
}

if ($mode === 'quick_add' || $mode === 'add') {
    if (!defined('AJAX_REQUEST')) {
        return [CONTROLLER_STATUS_REDIRECT, 'product_features.manage'];
    }

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];
    $feature = isset($_REQUEST['feature_data']) ? (array) $_REQUEST['feature_data'] : [];

    list($group_features) = fn_get_product_features(['feature_types' => ProductFeatures::GROUP], 0, DESCR_SL);
    $purposes = fn_get_product_feature_purposes();

    if (!empty($_REQUEST['filter_purposes'])) {
        $filter_purposes = array_filter((array) $_REQUEST['filter_purposes']);

        $purposes = array_filter($purposes, function ($key) use ($filter_purposes) {
            return in_array($key, $filter_purposes);
        },  ARRAY_FILTER_USE_KEY);
    }

    $view->assign([
        'purposes'         => $purposes,
        'default_purpose'  => fn_get_default_product_feature_purpose(),
        'group_features'   => $group_features,
        'feature'          => $feature,
        'feature_variants' => isset($feature['variants']) ? (array)$feature['variants'] : [],
        'category_id'      => isset($_REQUEST['category_id']) ? (int)$_REQUEST['category_id'] : 0,
        'show_purposes'    => isset($_REQUEST['show_purposes']) ? (bool)$_REQUEST['show_purposes'] : false,
        'ajax_mode'        => true,
        'in_popup'         => true,
        'category_ids'     => isset($_REQUEST['category_ids']) ? array_filter((array) $_REQUEST['category_ids']) : null,
    ]);
} elseif ($mode == 'update') {
    $selected_section = (empty($_REQUEST['selected_section']) ? 'detailed' : $_REQUEST['selected_section']);

    $feature = fn_get_product_feature_data($_REQUEST['feature_id'], false, false, DESCR_SL);

    if (empty($feature)) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $purposes = fn_get_product_feature_purposes();

    if (!empty($feature['feature_type']) && !isset($purposes[$feature['purpose']])) {
        $feature['purpose'] = (string) fn_get_product_feature_purpose_by_type($feature['feature_type']);
    }

    list($group_features) = fn_get_product_features(['feature_types' => ProductFeatures::GROUP], 0, DESCR_SL);

    $params = [
        'feature_id'     => $feature['feature_id'],
        'feature_type'   => $feature['feature_type'],
        'get_images'     => true,
        'page'           => !empty($_REQUEST['page']) ? $_REQUEST['page'] : 1,
        'items_per_page' => !empty($_REQUEST['items_per_page']) ? $_REQUEST['items_per_page'] : Registry::get('settings.Appearance.admin_elements_per_page'),
    ];

    list($variants, $search) = fn_get_product_feature_variants($params, Registry::get('settings.Appearance.admin_elements_per_page'), DESCR_SL);

    Tygh::$app['view']->assign([
        'purposes'         => $purposes,
        'feature'          => $feature,
        'group_features'   => $group_features,
        'feature_variants' => $variants,
        'search'           => $search,
        'in_popup'         => $feature['feature_type'] != ProductFeatures::EXTENDED && defined('AJAX_REQUEST')
    ]);

    if (fn_allowed_for('ULTIMATE') && !Registry::get('runtime.company_id')) {
        Tygh::$app['view']->assign('picker_selected_companies', fn_ult_get_controller_shared_companies($_REQUEST['feature_id']));
    }
} elseif ($mode === 'manage') {
    Registry::set('navigation.dynamic.sections', $navigation_sections);
    Registry::set('navigation.dynamic.active_section', 'features');

    $params = $_REQUEST;
    $params['get_descriptions'] = true;
    $params['search_in_subcats'] = true;
    $params['exclude_group'] = true;

    list($features, $search) = fn_get_product_features($params, Registry::get('settings.Appearance.admin_elements_per_page'), DESCR_SL);
    list($group_features) = fn_get_product_features(['feature_types' => ProductFeatures::GROUP, 'get_descriptions' => true], 0, DESCR_SL);

    Tygh::$app['view']->assign([
        'purposes'        => fn_get_product_feature_purposes(),
        'default_purpose' => fn_get_default_product_feature_purpose(),
        'features'        => $features,
        'search'          => $search,
        'group_features'  => $group_features,
    ]);

    if (empty($features) && defined('AJAX_REQUEST')) {
        Tygh::$app['ajax']->assign('force_redirection', fn_url('product_features.manage'));
    }
} elseif ($mode === 'groups') {
    Registry::set('navigation.dynamic.sections', $navigation_sections);
    Registry::set('navigation.dynamic.active_section', 'groups');

    $params = $_REQUEST;
    $params['get_descriptions'] = true;
    $params['search_in_subcats'] = true;
    $params['exclude_group'] = true;
    $params['feature_types'] = ProductFeatures::GROUP;
    $params['get_top_features'] = true;

    list($features, $search) = fn_get_product_features($params, Registry::get('settings.Appearance.admin_elements_per_page'), DESCR_SL);
    list($group_features) = fn_get_product_features(['feature_types' => ProductFeatures::GROUP], 0, DESCR_SL);
    $is_allowed_to_change = fn_check_permissions('product_features', 'update', 'admin', 'POST');

    Tygh::$app['view']->assign([
        'features'             => $features,
        'search'               => $search,
        'group_features'       => $group_features,
        'is_allowed_to_change' => $is_allowed_to_change
    ]);

    if (empty($features) && defined('AJAX_REQUEST')) {
        Tygh::$app['ajax']->assign('force_redirection', fn_url('product_features.manage'));
    }
} elseif ($mode === 'get_feature_variants_list') {
    if (empty($_REQUEST['feature_id'])) {
        exit;
    }

    $pattern = !empty($_REQUEST['pattern']) ? $_REQUEST['pattern'] : '';
    $start = !empty($_REQUEST['start']) ? $_REQUEST['start'] : 0;
    $limit = (!empty($_REQUEST['limit']) ? $_REQUEST['limit'] : 10) + 1;
    $sorting = db_quote('?:product_feature_variants.position, ?:product_feature_variant_descriptions.variant');

    $join = db_quote(
        ' LEFT JOIN ?:product_feature_variant_descriptions'
        . ' ON ?:product_feature_variant_descriptions.variant_id = ?:product_feature_variants.variant_id'
        . ' AND ?:product_feature_variant_descriptions.lang_code = ?s',
        DESCR_SL
    );
    $condition = db_quote(' AND ?:product_feature_variants.feature_id = ?i', $_REQUEST['feature_id']);

    fn_set_hook('get_feature_variants_list', $condition, $join, $pattern, $start, $limit);

    $objects = db_get_hash_array(
        'SELECT SQL_CALC_FOUND_ROWS ?:product_feature_variants.variant_id AS value, ?:product_feature_variant_descriptions.variant AS name'
        . ' FROM ?:product_feature_variants ?p'
        . ' WHERE 1 ?p AND ?:product_feature_variant_descriptions.variant LIKE ?l'
        . ' ORDER BY ?p LIMIT ?i, ?i',
        'value',
        $join,
        $condition,
        '%' . $pattern . '%',
        $sorting,
        $start,
        $limit
    );

    if (defined('AJAX_REQUEST') && sizeof($objects) < $limit) {
        Tygh::$app['ajax']->assign('completed', true);
    } else {
        array_pop($objects);
    }

    if (empty($_REQUEST['enter_other']) || $_REQUEST['enter_other'] !== YesNo::NO) {
        $total = db_get_found_rows();
        if (!Registry::get('runtime.company_id') || (fn_allowed_for('ULTIMATE') && fn_check_company_id('product_features', 'feature_id', $_REQUEST['feature_id']))) {
            if ($start + $limit >= $total + 1) {
                $objects[] = ['value' => 'disable_select', 'name' => '-' . __('enter_other') . '-'];
            }
        }
    }

    if (!$start) {
        array_unshift($objects, ['value' => '', 'name' => '-' . __('none') . '-']);
    }

    Tygh::$app['view']->assign('objects', $objects);

    Tygh::$app['view']->assign('id', $_REQUEST['result_ids']);
    Tygh::$app['view']->display('common/ajax_select_object.tpl');

    exit;
}
/**
 * This mode renders list of the feature variants in Select2 JSON format.
 *
 * @TODO: Class that implements common methods for Select2 controller modes.
 */
elseif ($mode === 'get_variants_list') {
    if (empty($_REQUEST['feature_id']) && empty($_REQUEST['ids'])) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $feature_id = isset($_REQUEST['feature_id']) ? (int) $_REQUEST['feature_id'] : null;
    $page_number = isset($_REQUEST['page']) ? (int) $_REQUEST['page'] : 1;
    $page_size = isset($_REQUEST['page_size']) ? (int) $_REQUEST['page_size'] : 10;
    $search_query = isset($_REQUEST['q']) ? $_REQUEST['q'] : null;
    $lang_code = isset($_REQUEST['lang_code']) ? $_REQUEST['lang_code'] : DESCR_SL;

    $search = [
        'page'         => $page_number,
        'feature_id'   => $feature_id,
        'search_query' => $search_query,
        'get_images'   => true
    ];

    if (isset($_REQUEST['preselected'])) {
        $search['variant_id'] = $_REQUEST['preselected'];
    }

    if (isset($_REQUEST['ids'])) {
        $search['variant_id'] = (array) $_REQUEST['ids'];
        $page_size = 0;
    }

    if (isset($_REQUEST['product_id'])) {
        $search['product_id'] = (int) $_REQUEST['product_id'];
    }

    list($variants, $search) = fn_get_product_feature_variants($search, $page_size, $lang_code);

    $objects = array_values(array_map(function ($feature_variant) {
        $image_url = null;

        if (isset($feature_variant['image_pair'])) {
            $image_data = fn_image_to_display(
                $feature_variant['image_pair'],
                isset($_REQUEST['image_width']) ? (int) $_REQUEST['image_width'] : 50,
                isset($_REQUEST['image_height']) ? (int) $_REQUEST['image_height'] : 50
            );
            if (!empty($image_data['image_path'])) {
                $image_url = $image_data['image_path'];
            }
        }

        return [
            'id'        => $feature_variant['variant_id'],
            'text'      => $feature_variant['variant'],
            'image_url' => $image_url,
            'data'      => [
                'image_url' => $image_url,
                'name'      => $feature_variant['variant'],
                'color'     => $feature_variant['color'],
            ]
        ];
    }, $variants));

    Tygh::$app['ajax']->assign('objects', $objects);
    Tygh::$app['ajax']->assign('total_objects', isset($search['total_items']) ? $search['total_items'] : count($objects));

    exit;
}
/**
 * This mode renders list of the features in Select2 JSON format.
 *
 * @TODO: Class that implements common methods for Select2 controller modes.
 */
elseif ($mode === 'get_features_list') {
    $page_number = isset($_REQUEST['page']) ? (int) $_REQUEST['page'] : 1;
    $page_size = isset($_REQUEST['page_size']) ? (int) $_REQUEST['page_size'] : 10;
    $search_query = isset($_REQUEST['q']) ? $_REQUEST['q'] : null;
    $lang_code = isset($_REQUEST['lang_code']) ? $_REQUEST['lang_code'] : CART_LANGUAGE;

    $search = [
        'page'             => $page_number,
        'description'      => $search_query,
        'get_descriptions' => true,
    ];

    if (isset($_REQUEST['preselected'])) {
        $search['feature_id'] = $_REQUEST['preselected'];
        $search['plain'] = true;
        $search['exclude_group'] = true;
    }

    if (isset($_REQUEST['ids'])) {
        $search['feature_id'] = (array) $_REQUEST['ids'];
        $search['plain'] = true;
        $search['exclude_group'] = true;
        $page_size = 0;
    }

    if (!empty($_REQUEST['get_only_available'])) {
        $search['statuses'] = [ObjectStatuses::ACTIVE, ObjectStatuses::HIDDEN];
    }

    if (!empty($_REQUEST['get_only_selectable'])) {
        $search['feature_types'] = ProductFeatures::getSelectableList();
    }

    if (!empty($_REQUEST['feature_types'])) {
        $search['feature_types'] = $_REQUEST['feature_types'];
    }

    if (isset($_REQUEST['exclude_empty_groups'])) {
        $search['exclude_empty_groups'] = true;
    }

    if (isset($_REQUEST['purpose'])) {
        $search['purpose'] = $_REQUEST['purpose'];
    }

    if (isset($_REQUEST['exclude_feature_ids'])) {
        $search['exclude_feature_id'] = $_REQUEST['exclude_feature_ids'];
    }

    if (isset($_REQUEST['filter_category_ids'])) {
        $search['category_ids'] = fn_get_category_ids_with_parent($_REQUEST['filter_category_ids']);
    }

    list($features, $search) = fn_get_product_features($search, $page_size, $lang_code);

    // This weird recursive functional code is efficient, but is not nicely readable.
    // It will be refactored along with implementing Select2 controller mode base class.
    $feature_converter = function ($feature_list) use (&$feature_converter, $lang_code) {
        return array_values(array_map(function ($feature) use (&$feature_converter, $lang_code) {
            $return = [
                'id'     => (int) $feature['feature_id'],
                'text'   => $feature['internal_name'],
                'object' => [
                    'feature_type' => $feature['feature_type'],
                ],
                'data'  => [
                    'internal_name' => $feature['internal_name'],
                    'description'   => $feature['description']
                ]
            ];
            if ($feature['feature_type'] == ProductFeatures::SINGLE_CHECKBOX) {
                $return['object']['variants'] = [
                    ['id' => YesNo::YES, 'text' => __('yes')],
                    ['id' => YesNo::NO, 'text' => __('no')],
                ];
            } elseif ($feature['feature_type'] == ProductFeatures::GROUP) {
                $return['children'] = [];

                if (!empty($feature['subfeatures'])) {
                    // Recursive call that fills subfeatures list
                    $return['children'] = $feature_converter($feature['subfeatures']);
                }
            } elseif (in_array($feature['feature_type'], [
                ProductFeatures::TEXT_SELECTBOX,
                ProductFeatures::NUMBER_SELECTBOX,
                ProductFeatures::EXTENDED,
                ProductFeatures::MULTIPLE_CHECKBOX
            ])) {
                $return['object']['variants'] = fn_url(
                    "product_features.get_variants_list?feature_id={$feature['feature_id']}&lang_code={$lang_code}"
                );
            }

            return $return;
        }, $feature_list));
    };

    $objects = $feature_converter($features);

    Tygh::$app['ajax']->assign('objects', $objects);
    Tygh::$app['ajax']->assign('total_objects', empty($search['total_items']) ? 0 : $search['total_items']);

    exit;
}
