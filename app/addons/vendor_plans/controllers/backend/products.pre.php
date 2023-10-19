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

if (!fn_allowed_for('MULTIVENDOR')) {
    return;
}

$company = Company::current();
if (!$company) {
    return;
}

$return_url = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'products.manage';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode === 'clone' || $mode === 'm_clone' || $mode === 'sell_master_product') {
        if (!$company->canAddProduct(true)) {
            return array(CONTROLLER_STATUS_REDIRECT, $return_url);
        }
    }

    return;
}

if ($mode === 'add') {
    if (!$company->canAddProduct(true)) {
        return array(CONTROLLER_STATUS_REDIRECT, $return_url);
    }
}
