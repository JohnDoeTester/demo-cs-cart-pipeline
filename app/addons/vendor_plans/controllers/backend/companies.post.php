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

use Tygh\Enum\YesNo;
use Tygh\Registry;
use Tygh\Models\VendorPlan;

if (!fn_allowed_for('MULTIVENDOR')) {
    return;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    return;
}

if ($mode == 'manage' || $mode == 'update' || $mode == 'add') {
    $company_id = Registry::get('runtime.company_id');
    $vendor_plans = [];
    $default_vendor_plan = null;
    $current_plan = null;
    $params = [
        'allowed_for_company_id' => $company_id,
    ];

    if ($mode == 'update' || $mode == 'add') {
        $params['check_availability'] = true;
    }
    if (empty($company_id) || YesNo::toBool(Registry::get('addons.vendor_plans.allow_vendors_to_change_plan'))) {
        $vendor_plans = VendorPlan::model()->findMany($params);

        foreach ($vendor_plans as $vendor_plan) {
            if (!empty($vendor_plan->is_default)) {
                $default_vendor_plan = $vendor_plan;
                break;
            }
        }

        if (!$default_vendor_plan) {
            $default_vendor_plan = reset($vendor_plans);
        }
    } else {
        $company_data = Tygh::$app['view']->getTemplateVars('company_data');
        if (!empty($company_data['plan_id'])) {
            $default_vendor_plan = VendorPlan::model()->find($company_data['plan_id'], [
                'allowed_for_company_id' => $company_id
            ]);
            if ($default_vendor_plan) {
                $vendor_plans = [$default_vendor_plan];
            }
        }
    }

    /** @var array{plan_id: int} $company_data */
    $company_data = Tygh::$app['view']->getTemplateVars('company_data');

    if (!empty($company_data['plan_id'])) {
        $current_plan = VendorPlan::model()->find($company_data['plan_id']);
    }

    Tygh::$app['view']->assign('default_vendor_plan', $default_vendor_plan);
    Tygh::$app['view']->assign('current_plan', $current_plan);
    Tygh::$app['view']->assign('vendor_plans', $vendor_plans);
}

if ($mode == 'update' || $mode == 'add') {
    Registry::set('navigation.tabs.plan', array(
        'title' => __('vendor_plans.plan'),
        'js' => true,
    ));
}
