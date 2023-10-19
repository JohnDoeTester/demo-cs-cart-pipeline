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
use Tygh\Models\VendorPlan;
use Tygh\Models\Company;
use Tygh\Tygh;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    return;
}

if ($mode == 'apply_for_vendor') {

    if (!fn_allowed_for('MULTIVENDOR')) {
        return;
    }

    $vendor_plans = VendorPlan::model()->findMany(array(
        'allowed_for_company_id' => Registry::get('runtime.company_id'),
    ));

    Tygh::$app['view']->assign('vendor_plans', $vendor_plans);

} elseif ($mode == 'vendor_plans') {

    if (!fn_allowed_for('MULTIVENDOR')) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    $vendor_plans = VendorPlan::model()->findMany(array(
        'allowed_for_company_id' => 0,
        'storefront_id'          => Tygh::$app['storefront']->storefront_id,
    ));

    if (empty($vendor_plans)) {
        return [CONTROLLER_STATUS_REDIRECT, 'companies.apply_for_vendor'];
    }

    Tygh::$app['view']->assign('vendor_plans', $vendor_plans);

    fn_add_breadcrumb(__('vendor_plans.choose_your_plan'));

} elseif ($mode == 'view') {

    $company = Company::model()->find($_REQUEST['company_id']);
    if (!$company->vendor_store) {
        Tygh::$app['view']->assign('hide_vendor_store', true);
    }

}
