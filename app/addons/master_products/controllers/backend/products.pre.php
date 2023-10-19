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

use Tygh\Registry;
use Tygh\Tygh;
use Tygh\Enum\YesNo;

/** @var string $mode */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'export_range') {
        if (empty(Tygh::$app['session']['export_ranges'])) {
            Tygh::$app['session']['export_ranges'] = [];
        }
        if (empty(Tygh::$app['session']['export_ranges']['products']['pattern_id'])) {
            Tygh::$app['session']['export_ranges']['products'] = ['pattern_id' => 'products'];
        }
        Tygh::$app['session']['export_ranges']['products']['is_master_products_export'] = $action === 'master';
        if ($action === 'master') {
            if (!empty($_REQUEST['product_ids'])) {
                Tygh::$app['session']['export_ranges']['products']['data'] = ['product_id' => $_REQUEST['product_ids']];
            } elseif (!empty($_REQUEST['master_product_ids'])) {
                Tygh::$app['session']['export_ranges']['products']['data'] = ['product_id' => $_REQUEST['master_product_ids']];
            }
            unset($_REQUEST['redirect_url'], Tygh::$app['session']['export_ranges']['products']['data_provider']);

            return [CONTROLLER_STATUS_REDIRECT, 'exim.export?section=products&pattern_id=products'];
        }
    }
}

if (Registry::get('runtime.company_id') &&
    !YesNo::toBool(Registry::get('addons.master_products.allow_vendors_to_create_products')) &&
    empty($_REQUEST['product_id']) &&
    ($mode === 'add' || $mode === 'update' || $mode === 'm_add')
) {
    return [CONTROLLER_STATUS_REDIRECT, 'products.master_products'];
}
