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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    return [CONTROLLER_STATUS_OK];
}

if ($mode === 'update') {
    $payment_id = $_REQUEST['payment_id'];

    $selected_countries = fn_payments_by_country_get_payment_countries($payment_id);
    $all_countries = array_diff(fn_get_simple_countries(), $selected_countries);

    Tygh::$app['view']->assign('selected_countries', $selected_countries);
    Tygh::$app['view']->assign('all_countries', $all_countries);
}

if ($mode === 'manage') {
    Tygh::$app['view']->assign('all_countries', fn_get_simple_countries());
}

