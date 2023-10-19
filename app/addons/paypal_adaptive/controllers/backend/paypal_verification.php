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

if ( !defined('AREA') ) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    return;
}

if ($mode == 'cron_get_verified_status') {
    if (!empty($_REQUEST['magic_key']) && urldecode($_REQUEST['magic_key']) == Registry::get('addons.paypal_adaptive.cron_key')) {
        list($companies, $search) = fn_get_companies(array(), Tygh::$app['session']['auth']);
        foreach ($companies as $company_key => $company) {
            fn_paypal_adaptive_get_verified_status($company);
        }
        die(__('paypal_adaptive_statuses_updated_successfully'));
    } else {
        die(__('paypal_adaptive_error_wrong_cron_key'));
    }

}