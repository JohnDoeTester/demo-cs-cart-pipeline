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

use Tygh\Models\VendorPlan;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    return [CONTROLLER_STATUS_OK];
}

if ($mode === 'update' || $mode === 'add') {
    $id = 0;
    if ($mode === 'update') {
        $plan = VendorPlan::model()->find($_REQUEST['plan_id'], ['get_companies_count' => true]);
        if (!$plan) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }
        Tygh::$app['view']->assign('plan', $plan);
        if ($plan instanceof VendorPlan) {
            $id = $plan->plan_id;
        }
    }
    $new_tabs = [];
    $navigation_tabs = Registry::get('navigation.tabs');
    foreach ($navigation_tabs as $key => $tab) {
        $new_tabs[$key] = $tab;
        if ($key === 'plan_general_' . $id) {
            $new_tabs['plan_shippings_' . $id] = [
                'title' => __('shipping'),
                'js' => true,
            ];
        }
    }
    Registry::set('navigation.tabs', $new_tabs);
}
