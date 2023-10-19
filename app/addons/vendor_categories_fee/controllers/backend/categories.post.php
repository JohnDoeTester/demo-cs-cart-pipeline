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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    return [CONTROLLER_STATUS_OK];
}

if ($mode == 'update') {
    if (empty($_REQUEST['category_id'])) {
        return;
    }

    Registry::set('navigation.tabs.vendor_fee', [
        'title' => __('vendor_categories_fee.vendor_fee'),
        'js' => true,
    ]);

    $vendor_plans = fn_vendor_categories_fee_get_vendor_plans();
    $category_fee = fn_vendor_categories_fee_get_category_fee($_REQUEST['category_id']);
    $parent_fee = !fn_vendor_categories_fee_has_all_fee_set($category_fee)
        ? fn_vendor_categories_fee_get_parent_category_fee($_REQUEST['category_id'])
        : [];

    Tygh::$app['view']->assign([
        'vendor_plans' => $vendor_plans,
        'category_fee' => $category_fee,
        'parent_fee'   => $parent_fee,
    ]);
}
