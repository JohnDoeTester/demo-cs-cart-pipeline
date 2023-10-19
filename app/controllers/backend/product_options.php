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
use Tygh\Tygh;
use Tygh\Enum\NotificationSeverity;

defined('BOOTSTRAP') or die('Access denied');

/**
 * @var string $mode
 * @var array $auth
 */

fn_define('KEEP_UPLOADED_FILES', true);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $suffix = '';

    if ($mode === 'add_exceptions') {
        foreach ($_REQUEST['add_options_combination'] as $k => $v) {
            $_data = [
                'product_id'  => $_REQUEST['product_id'],
                'combination' => $v,
            ];

            fn_update_exception($_data);
        }

        fn_recalculate_exceptions($_REQUEST['product_id']);

        $suffix = '.exceptions?product_id=' . $_REQUEST['product_id'];
    }

    if ($mode === 'm_delete_exceptions') {
        foreach ($_REQUEST['exception_ids'] as $id) {
            fn_delete_exception($id);
        }

        $suffix = '.exceptions?product_id=' . $_REQUEST['product_id'];
    }

    // Apply global options to the selected products
    if ($mode === 'apply') {
        if (!empty($_REQUEST['apply_options']['options'])) {
            $_data = $_REQUEST['apply_options'];
            $updated_products = [];

            foreach ($_data['options'] as $value) {
                $products_ids = empty($_data['product_ids']) ? [] : explode(',', $_data['product_ids']);

                foreach ($products_ids as $k) {
                    $updated_products[$k] = db_get_row(
                        'SELECT a.product_id, a.company_id, b.product FROM ?:products as a'
                        . ' LEFT JOIN ?:product_descriptions as b ON a.product_id = b.product_id'
                        . ' AND lang_code = ?s'
                        . ' WHERE a.product_id = ?i',
                        CART_LANGUAGE,
                        $k
                    );

                    if ($_data['link'] === 'N') {
                        fn_clone_product_options(0, $k, $value);
                    } else {
                        fn_add_global_option_link($k, $value);
                    }
                }
            }

            if (!empty($updated_products)) {
                fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('options_have_been_applied_to_products'));
            }
        }

        if (isset($_REQUEST['option_ids'])) {
            return [CONTROLLER_STATUS_OK, 'product_options.apply?' . http_build_query(['option_ids' => $_REQUEST['option_ids']])];
        }

        $suffix = '.apply';
    }

    if ($mode === 'update') {
        fn_trusted_vars('option_data', 'regexp');

        if (fn_allowed_for('MULTIVENDOR')) {
            $option_data = [];

            if (!empty($_REQUEST['option_id'])) {
                $condition = fn_get_company_condition('?:product_options.company_id');
                $option_data = db_get_row("SELECT * FROM ?:product_options WHERE option_id = ?i $condition", $_REQUEST['option_id']);

                if (empty($option_data)) {
                    fn_set_notification('W', __('warning'), __('access_denied'));
                    return [CONTROLLER_STATUS_REDIRECT, 'product_options.manage'];
                }
            }

            $_REQUEST['option_data'] = array_merge($option_data, $_REQUEST['option_data']);
            fn_set_company_id($_REQUEST['option_data']);
        }

        $option_id = fn_update_product_option($_REQUEST['option_data'], $_REQUEST['option_id']);
        if (defined('AJAX_REQUEST')) {
            /** @var \Tygh\Ajax $ajax */
            $ajax = Tygh::$app['ajax'];

            if ($option_id === false) {
                $ajax->assign('success', false);
            } else {
                $ajax->assign('success', true);
                $ajax->assign('option_id', $option_id);
            }
        } elseif (!empty($_REQUEST['object']) && $_REQUEST['object'] === 'product') {
            return [CONTROLLER_STATUS_OK, $_SERVER['HTTP_REFERER'] . '&selected_section=options'];
        }

        if ($option_id !== false) {
            $suffix = '.manage';
        } else {
            return [CONTROLLER_STATUS_OK, 'product_options.update?option_id=' . $option_id];
        }
    }

    if ($mode === 'm_delete') {
        $return_url = isset($_REQUEST['return_url']) ? $_REQUEST['return_url'] : 'product_options.manage';

        if (!empty($_REQUEST['option_ids'])) {
            $option_ids = (array) $_REQUEST['option_ids'];

            foreach ($option_ids as $option_id) {
                if (!fn_check_company_id('product_options', 'option_id', $option_id)) {
                    fn_company_access_denied_notification();
                    continue;
                }
                fn_delete_product_option($option_id);
            }
        }

        return [CONTROLLER_STATUS_OK, $return_url];
    }

    if (
        $mode === 'm_update_statuses'
        && !empty($_REQUEST['option_ids'])
        && is_array($_REQUEST['option_ids'])
        && !empty($_REQUEST['status'])
    ) {
        $status_to = (string) $_REQUEST['status'];

        foreach ($_REQUEST['option_ids'] as $option_id) {
            if (!fn_check_company_id('product_options', 'option_id', $option_id)) {
                continue;
            }
            fn_tools_update_status([
                'table'             => 'product_options',
                'status'            => $status_to,
                'id_name'           => 'option_id',
                'id'                => $option_id,
                'show_error_notice' => false
            ]);
        }

        if (defined('AJAX_REQUEST')) {
            $redirect_url = fn_url('product_options.manage');
            if (isset($_REQUEST['redirect_url'])) {
                $redirect_url = $_REQUEST['redirect_url'];
            }
            Tygh::$app['ajax']->assign('force_redirection', $redirect_url);
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            return [CONTROLLER_STATUS_NO_CONTENT];
        }
    }

    if ($mode === 'delete') {
        $option_deleted = false;

        if (!isset($_REQUEST['option_id'])) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        $option_id = $_REQUEST['option_id'];

        if (!empty($_REQUEST['product_id'])) {
            $option_is_linked = db_get_field(
                'SELECT 1 FROM ?:product_global_option_links WHERE option_id = ?i AND product_id = ?i',
                $option_id,
                $_REQUEST['product_id']
            );
        }

        if (
            (
                empty($option_is_linked)
                &&  !fn_check_company_id('product_options', 'option_id', $option_id)
            )
            || (
                !empty($_REQUEST['product_id'])
                && !fn_check_company_id('products', 'product_id', $_REQUEST['product_id'])
            )
        ) {
            fn_company_access_denied_notification();
            return [CONTROLLER_STATUS_REDIRECT, 'product_options.manage'];
        }

        $product_id = isset($_REQUEST['product_id']) ? $_REQUEST['product_id'] : 0;

        $option_deleted = fn_delete_product_option($option_id, $product_id);

        if (!$option_deleted) {
            if (!empty($product_id)) {
                $redirect_url = 'products.update&product_id=' . $product_id . '&selected_section=options';
            } else {
                $redirect_url = 'product_options.manage';
            }

            // this workaround required for MULTIVENDOR because it has specific logic when changing product vendor
            // on product update page (see app/controllers/backend/products.php line ~ 582)
            if (defined('AJAX_REQUEST')) {
                Tygh::$app['ajax']->assign('non_ajax_notifications', true);
                Tygh::$app['ajax']->assign('force_redirection', fn_url($redirect_url));
                return [CONTROLLER_STATUS_OK];
            }

            return [CONTROLLER_STATUS_REDIRECT, $redirect_url];
        }

        if (!empty($product_id)) {
            $_options = fn_get_product_options($product_id);

            if (empty($_options)) {
                Tygh::$app['view']->display('views/product_options/manage.tpl');
            }

            exit;
        }

        $suffix = '.manage';
    }

    if ($mode === 'delete_exception') {
        if (!empty($_REQUEST['exception_id'])) {
            db_query('DELETE FROM ?:product_options_exceptions WHERE exception_id = ?i', $_REQUEST['exception_id']);
        }

        $suffix = ".exceptions?product_id={$_REQUEST['product_id']}";
    }

    return [CONTROLLER_STATUS_OK, 'product_options' . $suffix];
}

if ($mode === 'quick_add' || $mode === 'add') {
    if (!defined('AJAX_REQUEST')) {
        return [CONTROLLER_STATUS_REDIRECT, 'product_options.add'];
    }

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];
    $option = isset($_REQUEST['option_data']) ? (array) $_REQUEST['option_data'] : [];

    $view->assign([
        'option_data' => $option,
        'ajax_mode'   => true
    ]);
}

if ($mode === 'manage') {
    $params = $_REQUEST;

    list($product_options, $search) = fn_get_product_global_options($params, Registry::get('settings.Appearance.admin_elements_per_page'), DESCR_SL);

    Tygh::$app['view']->assign('product_options', $product_options);
    Tygh::$app['view']->assign('search', $search);
    Tygh::$app['view']->assign('object', 'global');

    if (empty($product_options) && defined('AJAX_REQUEST')) {
        Tygh::$app['ajax']->assign('force_redirection', fn_url('product_options.manage'));
    }

// Apply options to products
} elseif ($mode === 'apply') {
    $search = [];

    if (fn_allowed_for('MULTIVENDOR')) {
        $search = [
            'company_ids' => [
                0,
                Registry::get('runtime.company_id'),
            ],
        ];
    }

    $option_ids = isset($_REQUEST['option_ids']) ? $_REQUEST['option_ids'] : [];

    list($product_options, $search) = fn_get_product_global_options($search);

    Tygh::$app['view']->assign('product_options', $product_options);
    Tygh::$app['view']->assign('option_ids', $option_ids);

// Update option
} elseif ($mode === 'update') {
    $product_id = !empty($_REQUEST['product_id']) ? $_REQUEST['product_id'] : 0;

    $o_data = fn_get_product_option_data($_REQUEST['option_id'], $product_id);

    if (!empty($_REQUEST['product_id']) && fn_allowed_for('ULTIMATE')) {
        Tygh::$app['view']->assign('shared_product', fn_ult_is_shared_product($_REQUEST['product_id']));
        Tygh::$app['view']->assign('product_company_id', db_get_field('SELECT company_id FROM ?:products WHERE product_id = ?i', $_REQUEST['product_id']));
    }

    if (isset($_REQUEST['object'])) {
        Tygh::$app['view']->assign('object', $_REQUEST['object']);
    }
    Tygh::$app['view']->assign('option_data', $o_data);
    Tygh::$app['view']->assign('option_id', $_REQUEST['option_id']);
} elseif ($mode === 'get_available_options_list') {
    $page_number = isset($_REQUEST['page']) ? (int) $_REQUEST['page'] : 1;
    $page_size = isset($_REQUEST['page_size']) ? (int) $_REQUEST['page_size'] : 10;
    $search_query = isset($_REQUEST['q']) ? $_REQUEST['q'] : null;
    $lang_code = isset($_REQUEST['lang_code']) ? $_REQUEST['lang_code'] : DESCR_SL;

    $search = [
        'page'        => $page_number,
        'q'           => $search_query,
        'company_ids' => [0],
    ];

    if (isset($_REQUEST['product_id'])) {
        $linked_product_option_ids = array_keys(fn_get_product_options($_REQUEST['product_id']));
        $search['excluded_ids'] = $linked_product_option_ids;
        $search['company_ids'][] = fn_get_company_id('products', 'product_id', $_REQUEST['product_id']);
    }

    list($global_product_options, $search) = fn_get_product_global_options($search, $page_size, $lang_code);

    $objects = array_values(array_map(static function ($product_option) {
        $text = $product_option['internal_option_name'] . (($product_option['option_name']) ? '/' . $product_option['option_name'] : '');
        return [
            'id'   => $product_option['option_id'],
            'text' => $text
        ];
    }, $global_product_options));

    Tygh::$app['ajax']->assign('objects', $objects);
    Tygh::$app['ajax']->assign('total_objects', isset($search['total_items']) ? $search['total_items'] : count($objects));

    exit;
}

// Product options exceptions
if ($mode === 'exceptions') {
    $exceptions = fn_get_product_exceptions($_REQUEST['product_id']);
    $product_options = fn_get_product_options($_REQUEST['product_id'], DESCR_SL, true);
    $product_data = fn_get_product_data($_REQUEST['product_id'], $auth, DESCR_SL, '', true, true, true, true);

    Tygh::$app['view']->assign('product_options', $product_options);
    Tygh::$app['view']->assign('exceptions', $exceptions);
    Tygh::$app['view']->assign('product_data', $product_data);
}

if (!empty($_REQUEST['product_id'])) {
    Tygh::$app['view']->assign('product_id', $_REQUEST['product_id']);
}
