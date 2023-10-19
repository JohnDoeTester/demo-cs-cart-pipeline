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

use Tygh\Enum\UserTypes;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

$auth = & Tygh::$app['session']['auth'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    return ;
}

if ($mode === 'update' || $mode === 'add') {
    $params = [];

    if (fn_allowed_for('MULTIVENDOR') && UserTypes::isVendor($auth['user_type'])) {
        $params['company_ids'] = [0, (int) Registry::get('runtime.company_id')];
    } else {
        $params['direct_payments_skip_company_id'] = true;
    }

    Tygh::$app['view']->assign('payments', fn_get_payments($params));

}
