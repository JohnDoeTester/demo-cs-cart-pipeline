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

defined('BOOTSTRAP') or die('Access denied');

if (in_array($mode, ['update', 'add'], true)) {
    Tygh::$app['view']->assign('zero_company_id_name_lang_var', __('marketplace'));
}

if ($mode === 'manage') {
    $company_name = Registry::get('settings.Company.company_name');
    Tygh::$app['view']->assign('marketplace_company_name', $company_name);
    Tygh::$app['view']->assign('marketplace_store_location_name', __('marketplace'));
}
