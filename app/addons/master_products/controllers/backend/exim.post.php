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

defined('BOOTSTRAP') or die('Access denied');

use Tygh\Enum\Addons\MasterProducts\EximProducts;
use Tygh\Tygh;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    return [CONTROLLER_STATUS_OK];
}

if ($mode === 'export') {
    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    /** @var array $pattern */
    $pattern = $view->getTemplateVars('pattern');
    if ($pattern['pattern_id'] === 'products' &&
        isset(Tygh::$app['session']['export_ranges']['products']['is_master_products_export'])
    ) {

        if (Tygh::$app['session']['export_ranges']['products']['is_master_products_export']) {
            $pattern['range_options']['selector_url'] = 'products.master_products';
            $pattern['options']['master_products.exported_products']['variants'] = [
                EximProducts::PRODUCTS_THAT_VENDORS_CAN_SELL => 'master_products.products_that_vendors_can_sell',
            ];
        } else {
            $pattern['options']['master_products.exported_products']['variants'] = [
                EximProducts::PRODUCTS_BEING_SOLD => 'master_products.products_being_sold',
            ];
        }

        $view->assign('pattern', $pattern);
    }

    return [CONTROLLER_STATUS_OK];
}