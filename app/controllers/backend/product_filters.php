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
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ProductFeatures;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $return_url = 'product_filters.manage';

    if ($mode == 'update') {
        $filter_id = fn_update_product_filter($_REQUEST['filter_data'], $_REQUEST['filter_id'], DESCR_SL);
        $return_url = 'product_filters.update&filter_id=' . $filter_id;
    }

    if ($mode == 'delete') {
        if (!empty($_REQUEST['filter_id'])) {
            if (fn_allowed_for('ULTIMATE')) {
                if (!fn_check_company_id('product_filters', 'filter_id', $_REQUEST['filter_id'])) {
                    fn_company_access_denied_notification();

                    return array(CONTROLLER_STATUS_REDIRECT, 'product_filters.manage');
                }
            }

            fn_delete_product_filter($_REQUEST['filter_id']);
        }
    }

    if (
        $mode === 'm_create_by_features'
        && !empty($_REQUEST['feature_ids'])
        && is_array($_REQUEST['feature_ids'])
    ) {
        $created_filter_count = 0;
        $runtime_company_id = fn_get_runtime_company_id();

        foreach ($_REQUEST['feature_ids'] as $feature_id) {
            $feature_data = fn_get_product_feature_data($feature_id);
            if (!$feature_data) {
                continue;
            }

            $result = OperationResult::wrap(
                static function () use ($feature_data, $runtime_company_id) {
                    $filter_data = [
                        'filter'          => $feature_data['description'],
                        'filter_type'     => 'FF-' . $feature_data['feature_id'],
                        'categories_path' => $feature_data['categories_path'],
                    ];
                    if (fn_allowed_for('ULTIMATE')) {
                        $filter_data = ['company_id' => $runtime_company_id ?: $feature_data['company_id']] + $filter_data;
                    }

                    return fn_update_product_filter($filter_data, 0);
                }
            );


            if ($result->isSuccess()) {
                $created_filter_count++;
            }
        }

        $messages = [];
        if ($created_filter_count) {
            $messages[] = __('text_n_filters_created', ['[n]' => $created_filter_count]);
        }

        if ($created_filter_count !== count($_REQUEST['feature_ids'])) {
            $messages[] = __('error_n_filters_already_exists', ['[n]' => count($_REQUEST['feature_ids']) - $created_filter_count]);
        }

        if ($messages) {
            fn_set_notification(NotificationSeverity::NOTICE, __('notice'), implode(' ', $messages));
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
        $mode === 'm_delete'
        && !empty($_REQUEST['filter_ids'])
    ) {
        foreach ((array) $_REQUEST['filter_ids'] as $filter_id) {
            if (
                fn_allowed_for('ULTIMATE')
                && !fn_check_company_id('product_filters', 'filter_id', $filter_id)
            ) {
                continue;
            }

            fn_delete_product_filter($filter_id);
        }
    }

    if (
        $mode === 'm_update_statuses'
        && !empty($_REQUEST['filter_ids'])
        && !empty($_REQUEST['status'])
    ) {
        $status_to = $_REQUEST['status'];

        foreach ((array) $_REQUEST['filter_ids'] as $filter_id) {
            fn_tools_update_status(
                [
                    'table'             => 'product_filters',
                    'status'            => $status_to,
                    'id_name'           => 'filter_id',
                    'id'                => $filter_id,
                    'show_error_notice' => false,
                ]
            );
        }

        if (defined('AJAX_REQUEST')) {
            $redirect_url = fn_url('product_filters.manage');
            if (isset($_REQUEST['redirect_url'])) {
                $redirect_url = $_REQUEST['redirect_url'];
            }
            Tygh::$app['ajax']->assign('force_redirection', $redirect_url);
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            return [CONTROLLER_STATUS_NO_CONTENT];
        }
    }

    if (
        $mode === 'm_update_categories'
        && !empty($_REQUEST['product_filters_ids'])
        && !empty($_REQUEST['categories_map'])
    ) {
        $product_filters_ids = (array) $_REQUEST['product_filters_ids'];
        $categories_map = (array) $_REQUEST['categories_map'];

        list($product_filters) = fn_get_product_filters(['item_ids' => $product_filters_ids]);
        $filter_fields = fn_get_product_filter_fields();

        foreach ($product_filters as $product_filter_id => $product_filter) {
            $old_category_ids = $category_ids = empty($product_filter['categories_path']) ? [] : explode(',', $product_filter['categories_path']);

            if (!empty($categories_map['A'])) {
                $category_ids = array_merge($category_ids, $categories_map['A']);
                $category_ids = array_unique($category_ids);
            }

            if (!empty($categories_map['D'])) {
                $category_ids = array_diff($category_ids, $categories_map['D']);
            }

            if ($category_ids === $old_category_ids) {
                continue;
            }

            $filter_type = '';
            if ($product_filter['feature_id']) {
                $filter_type .= 'FF-' . $product_filter['feature_id'];
            } else {
                if (!empty($filter_fields[$product_filter['field_type']]['is_range'])) {
                    $filter_type .= 'R-' . $product_filter['field_type'];
                } else {
                    $filter_type .= 'B-' . $product_filter['field_type'];
                }
            }

            $product_filter['filter_type'] = $filter_type;
            $product_filter['categories_path'] = implode(',', $category_ids);
            fn_update_product_filter($product_filter, $product_filter_id);
        }

        if (defined('AJAX_REQUEST')) {
            $redirect_url = fn_url('product_filters.manage');
            if (isset($_REQUEST['redirect_url'])) {
                $redirect_url = $_REQUEST['redirect_url'];
            }
            Tygh::$app['ajax']->assign('force_redirection', $redirect_url);
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            return [CONTROLLER_STATUS_NO_CONTENT];
        }
    }

    if(!empty($_REQUEST['return_url'])) {
        $return_url = $_REQUEST['return_url'];
    }

    return array(CONTROLLER_STATUS_OK, $return_url);
}

if ($mode == 'manage' || $mode == 'picker') {

    $params = $_REQUEST;
    $params['get_descriptions'] = true;

    list($filters, $search) = fn_get_product_filters($params, Registry::get('settings.Appearance.admin_elements_per_page'));

    Tygh::$app['view']->assign('filters', $filters);
    Tygh::$app['view']->assign('search', $search);

    if ($mode == 'manage') {
        $company_id = fn_get_runtime_company_id();
        $fields = fn_get_product_filter_fields();

        if (!empty($company_id)) {
            $field_filters = db_get_fields("SELECT field_type FROM ?:product_filters WHERE field_type != '' GROUP BY field_type");

            foreach ($fields as $key => $field) {
                if (in_array($key, $field_filters)) {
                    unset($fields[$key]);
                }
            }
        }

        Tygh::$app['view']->assign('filter_fields', $fields);

        if (empty($filters) && defined('AJAX_REQUEST')) {
            Tygh::$app['ajax']->assign('force_redirection', fn_url('product_filters.manage'));
        }

        $params = [
            'variants'        => false,
            'plain'           => true,
            'feature_types'   => [
                ProductFeatures::SINGLE_CHECKBOX,
                ProductFeatures::TEXT_SELECTBOX,
                ProductFeatures::EXTENDED,
                ProductFeatures::NUMBER_SELECTBOX,
                ProductFeatures::MULTIPLE_CHECKBOX,
                ProductFeatures::NUMBER_FIELD,
                ProductFeatures::DATE
            ],
            'exclude_group'   => true,
            'exclude_filters' => !empty($company_id)
        ];

        list($filter_features) = fn_get_product_features($params, 0, DESCR_SL);

        Tygh::$app['view']->assign('filter_features', $filter_features);
    }

    if ($mode == 'picker') {
        Tygh::$app['view']->display('pickers/filters/picker_contents.tpl');
        exit;
    }

} elseif ($mode == 'update') {

    $params = $_REQUEST;
    $params['get_variants'] = true;

    $fields = fn_get_product_filter_fields();
    list($filters) = fn_get_product_filters($params);
    foreach ($filters as &$filter) {
        $filter['slider'] = fn_get_filter_is_numeric_slider($filter);
    }

    Tygh::$app['view']->assign('filter', array_shift($filters));
    Tygh::$app['view']->assign('filter_fields', $fields);
    Tygh::$app['view']->assign('in_popup', !empty($_REQUEST['in_popup']));

    if (fn_allowed_for('ULTIMATE') && !Registry::get('runtime.company_id')) {
        Tygh::$app['view']->assign('picker_selected_companies', fn_ult_get_controller_shared_companies($_REQUEST['filter_id']));
    }

}
