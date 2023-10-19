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

use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

/** @var string $mode */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($mode === 'add') {
        if (
            !empty($dispatch_extra)
            && !empty($_REQUEST['product_id'])
            && !empty($_REQUEST['product_data'])
        ) {
            $product_data = $_REQUEST['product_data'];
            $master_product_id = $_REQUEST['product_id'];

            if (
                !isset($product_data[$dispatch_extra]['product_options'])
                && !empty($product_data[$master_product_id]['product_options'])
            ) {
                $product_data[$dispatch_extra]['product_options'] = $product_data[$master_product_id]['product_options'];

                Tygh::$app['ajax']->assign('product_data', $product_data);
            }

            if (!empty($product_data['custom_files'])) {
                foreach ($product_data['custom_files'] as $files_key => $files_value) {
                    $product_data['custom_files'][$files_key] = str_replace($master_product_id, $dispatch_extra, $files_value);
                }

                Tygh::$app['ajax']->assign('product_data', $product_data);
            }
        }
    }
}
