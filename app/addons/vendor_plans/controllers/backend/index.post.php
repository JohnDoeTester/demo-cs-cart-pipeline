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

use Tygh\Models\Company;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'index') {

    if (defined('AJAX_REQUEST') && $company = Company::current()) {
        $usage = array(
            'products'   => array(
                'title'      => __('products'),
                'current'    => $_current = $company->getCurrentProducts(),
                'limit'      => $_limit   = floatval($company->products_limit),
                'percentage' => $_limit ? round($_current / $_limit * 100) : 0,
            ),
            'revenue'    => array(
                'title'      => __('vendor_plans.revenue'),
                'current'    => $_current = $company->getCurrentRevenue(),
                'limit'      => $_limit   = floatval($company->revenue_limit),
                'percentage' => $_limit ? round($_current / $_limit * 100) : 0,
                'is_price'   => true,
            ),
        );
        Tygh::$app['view']->assign('plan_usage', $usage);
        Tygh::$app['view']->assign('plan_data', $company->plan);
    }
}
