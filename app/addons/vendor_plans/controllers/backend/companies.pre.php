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

if (!fn_allowed_for('MULTIVENDOR')) {
    return;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    return;
}

if ($mode == 'picker') {
    $vendor_plans = VendorPlan::model()->findMany();

    Tygh::$app['view']->assign('vendor_plans', $vendor_plans);
}
