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
use Tygh\Models\Company;
use Tygh\Models\VendorPlan;
use Tygh\Registry;
use Tygh\Tools\SecurityHelper;
use Tygh\Tygh;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if (!fn_allowed_for('MULTIVENDOR')) {
    return array(CONTROLLER_STATUS_DENIED);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Define trusted variables that shouldn't be stripped
    fn_trusted_vars ('plan_data');

    $suffix = 'manage';

    if ($mode == 'update') {
        $_REQUEST['plan_data']['plan_id'] = $_REQUEST['plan_id'];
        SecurityHelper::sanitizeObjectData('plan', $_REQUEST['plan_data']);
        if (!empty($_REQUEST['plan_id'])) {
            $plan = VendorPlan::model(['storefront_repository' => Tygh::$app['storefront.repository']])->find($_REQUEST['plan_id']);
        } else {
            $plan = new VendorPlan;
        }
        $plan->attributes($_REQUEST['plan_data']);
        $plan_saved = $plan->save();

        if (defined('AJAX_REQUEST')) {
            /** @var \Tygh\Ajax $ajax */
            $ajax = Tygh::$app['ajax'];

            if ($plan_saved === false) {
                $ajax->assign('success', false);
            } else {
                fn_set_notification(NotificationSeverity::NOTICE, __('successful'), __('vendor_plans.text_vendor_plan_created', [
                    '[link]' => fn_url('vendor_plans.manage#plan_' . $plan->plan_id),
                    '[plan_name]' => $plan->plan
                ]));

                $ajax->assign('success', true);
                $ajax->assign('vendor_plan_id', $plan->plan_id);
            }

            return [CONTROLLER_STATUS_NO_CONTENT];
        }
    }

    if ($mode == 'm_update') {
        if (!empty($_REQUEST['plans_data'])) {
            foreach ($_REQUEST['plans_data'] as $plan_id => $plan_data) {
                $plan = VendorPlan::model()->find($plan_id);
                $plan->attributes($plan_data);
                if (!empty($_REQUEST['default_plan']) && $_REQUEST['default_plan'] == $plan_id) {
                    $plan->is_default = true;
                }
                $plan->save();
            }
        }
    }

    if ($mode == 'delete') {
        if ($plan = VendorPlan::model()->find($_REQUEST['plan_id'])) {
            $plan_deleted = $plan->delete();
            /** @var VendorPlan $plan */
            if ($plan_deleted && $plan->is_default) {
                $new_default_plan = VendorPlan::model()->findOne(['can_be_default' => true]);
                /** @var VendorPlan $new_default_plan */
                if ($new_default_plan) {
                    $new_default_plan->attributes(['is_default' => true]);
                    $new_default_plan->save();
                }
            }
        }
    }

    if ($mode == 'm_delete') {
        if (!empty($_REQUEST['plan_ids'])) {
            $default_plan_deleted = false;
            foreach ($_REQUEST['plan_ids'] as $plan_id) {
                if ($plan = VendorPlan::model()->find($plan_id)) {
                    $plan_deleted = $plan->delete();
                    /** @var VendorPlan $plan */
                    if ($plan_deleted && $plan->is_default && !$default_plan_deleted) {
                        $default_plan_deleted = true;
                    }
                }
            }
            if ($default_plan_deleted) {
                $new_default_plan = VendorPlan::model()->findOne(['can_be_default' => true]);
                /** @var VendorPlan $new_default_plan */
                if ($new_default_plan) {
                    $new_default_plan->attributes(['is_default' => true]);
                    $new_default_plan->save();
                }
            }
        }
    }

    if ($mode == 'update_status') {

        $plan = VendorPlan::model()->find($_REQUEST['id']);
        if ($plan) {
            $previos_status = $plan->status;
            $plan->status = $_REQUEST['status'];
            if ($plan->save()) {
                fn_set_notification('N', __('notice'), __('status_changed'));
            } else {
                Tygh::$app['ajax']->assign('return_status', $previos_status);
            }
        }

        if (empty($_REQUEST['return_url'])) {
            return [CONTROLLER_STATUS_NO_CONTENT];
        } else {
            return [CONTROLLER_STATUS_REDIRECT, $_REQUEST['return_url']];
        }
    }

    if (
        $mode === 'm_update_statuses'
        && !empty($_REQUEST['plan_ids'])
        && !empty($_REQUEST['status'])
    ) {
        $plans = VendorPlan::model()->findMany(['ids' => (array) $_REQUEST['plan_ids']]);

        foreach ($plans as $plan) {
            $plan->status = $_REQUEST['status'];
            $plan->save();
        }
    }

    if (
        $mode === 'm_update_categories'
        && !empty($_REQUEST['vendor_plan_ids'])
        && !empty($_REQUEST['categories_map'])
    ) {
        $categories_map = (array) $_REQUEST['categories_map'];
        $plans = VendorPlan::model()->findMany(['ids' => (array) $_REQUEST['vendor_plan_ids']]);

        foreach ($plans as $plan) {
            $old_category_ids = $category_ids = $plan->category_ids;

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

            $plan->attributes(['categories' => $category_ids ?: null]);
            $plan->save();
        }

        if (defined('AJAX_REQUEST')) {
            $redirect_url = fn_url('vendor_plans.manage');
            if (isset($_REQUEST['redirect_url'])) {
                $redirect_url = $_REQUEST['redirect_url'];
            }
            Tygh::$app['ajax']->assign('force_redirection', $redirect_url);
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            return [CONTROLLER_STATUS_NO_CONTENT];
        }
    }

    return [CONTROLLER_STATUS_OK, 'vendor_plans.' . $suffix];
}

if ($mode === 'quick_add') {
    if (!defined('AJAX_REQUEST')) {
        return [CONTROLLER_STATUS_REDIRECT, 'vendor_plans.add'];
    }

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];
    $plan = isset($_REQUEST['plan_data']) ? (array) $_REQUEST['plan_data'] : [];

    $view->assign([
        'plan' => $plan
    ]);
}

if ($mode == 'manage') {

    $params = array_merge(
        array(
            'items_per_page'      => Registry::get('settings.Appearance.admin_elements_per_page'),
        ),
        $_REQUEST,
        array(
            'return_params'       => true,
            'get_companies_count' => true,
            'lang_code'           => DESCR_SL,
        )
    );
    list($plans, $search) = VendorPlan::model()->findMany($params);

    Tygh::$app['view']->assign('plans', $plans);
    Tygh::$app['view']->assign('search', $search);

    Tygh::$app['view']->assign('preview_uri', fn_url('companies.vendor_plans', 'C'));

} elseif ($mode == 'update' || $mode == 'add') {
    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    $id = 0;
    if ($mode == 'update') {
        $plan = VendorPlan::model()->find($_REQUEST['plan_id'], ['get_companies_count' => true]);
        if (!$plan) {
            return array(CONTROLLER_STATUS_NO_PAGE);
        }

        $view->assign('plan', $plan);
        $id = $plan->plan_id;
    } elseif (!empty($_REQUEST['plan_data'])) {
        $view->assign('plan', (array) $_REQUEST['plan_data']);
    }

    if ($mode === 'add') {
        $plans_count = VendorPlan::model()->findMany(['get_count' => true]);
        $view->assign('plans_count', $plans_count);
    }

    $tabs = [
        'plan_general_' . $id      => [
            'title' => __('general'),
            'js'    => true,
        ],
        'plan_commission_' . $id   => [
            'title' => __('vendor_plans.commission'),
            'js'    => true,
        ],
        'plan_restrictions_' . $id => [
            'title' => __('vendor_plans.restrictions'),
            'js'    => true,
        ],
    ];

    if (fn_allowed_for('MULTIVENDOR:ULTIMATE')) {
        $tabs['plan_storefronts_' . $id] = [
            'title' => __('storefronts'),
            'js'    => true,
        ];

        if ($id) {
            $affected_vendors = Company::model()->findAll(['plan_id' => $id]);
            $affected_vendors = array_map(function(Company $company) {
                return $company->company_id;
            }, $affected_vendors);
            $view->assign('affected_vendors', $affected_vendors);
        }
    }

    $tabs['plan_categories_' . $id] = [
        'title' => __('categories'),
        'js'    => true,
    ];

    if (defined('AJAX_REQUEST') && !empty($_REQUEST['_action_context'])) {
        $view->assign([
            'ajax_mode' => true
        ]);
    }

    Registry::set('navigation.tabs', $tabs);

} elseif ($mode == 'async') {

    Company::periodicityPayments();
    exit;

} elseif ($mode === 'picker') {
    if (!defined('AJAX_REQUEST')) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    /** @var \Tygh\Ajax $ajax */
    $ajax = Tygh::$app['ajax'];

    /** @var \Tygh\Tools\Formatter $formatter */
    $formatter = Tygh::$app['formatter'];

    $page_number = isset($_REQUEST['page']) ? (int) $_REQUEST['page'] : 1;
    $page_size = isset($_REQUEST['page_size']) ? (int) $_REQUEST['page_size'] : 10;
    $lang_code = isset($_REQUEST['lang_code']) ? $_REQUEST['lang_code'] : CART_LANGUAGE;
    $search_query = isset($_REQUEST['q']) ? $_REQUEST['q'] : null;

    $params = [
        'items_per_page'      => Registry::get('settings.Appearance.admin_elements_per_page'),
        'return_params'       => true,
        'get_companies_count' => true,
        'lang_code'           => $lang_code,
        'plan'                => $search_query,
        'page'                => $page_number,
    ];

    if (isset($_REQUEST['ids'])) {
        $params['ids'] = (array) $_REQUEST['ids'];
        $params['items_per_page'] = 0;
    }

    list($plans, $search) = VendorPlan::model()->findMany($params);

    $objects = array_values(array_map(function (VendorPlan $plan) use ($formatter) {
        return [
            'id'   => $plan->plan_id,
            'text' => $plan->plan,
            'data' => [
                'name'              => $plan->plan,
                'commission'        => $plan->commission,
                'commission_text'   => __('vendor_plans.commission_fee', ['[commission]' => $plan->commission]),
                'price'             => $formatter->asPrice($plan->price),
                'periodicity'       => __('vendor_plans.periodicity_' . $plan->periodicity),
                'vendor_count'      => (int) $plan->companies_count,
                'vendor_count_text' => __('n_vendors', [(int) $plan->companies_count]),
                'status'            => $plan->getStatusText(),
                'storefront_ids'    => $plan->storefront_ids,
            ]
        ];
    }, $plans));

    $ajax->assign('objects', $objects);
    $ajax->assign('total_objects', isset($search['total_items']) ? $search['total_items'] : count($objects));

    Registry::set('runtime.vendor_plans.picker.plans', $plans);

    return [CONTROLLER_STATUS_NO_CONTENT];
}

Tygh::$app['view']->assign('periodicities', VendorPlan::getPeriodicitiesList());
