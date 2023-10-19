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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'update') {
        $product_id = isset($_REQUEST['product_id']) ? (int) $_REQUEST['product_id'] : 0;
        $required_product_ids = isset($_REQUEST['required_product_ids']) ? array_filter((array) $_REQUEST['required_product_ids']) : [];

        if ($product_id) {
            fn_required_products_update_products($product_id, $required_product_ids);
        }
    }
}

if ($mode == 'update') {
    $product_id = empty($_REQUEST['product_id']) ? 0 : intval($_REQUEST['product_id']);

    if (!empty($product_id)) {
        $product_data = fn_get_product_data($product_id);
        $product_company_id = !empty($product_data) ? $product_data['company_id'] : 0;

        Tygh::$app['view']->assign('product_company_id', $product_company_id);
    }

    Registry::set('navigation.tabs.required_products', array (
        'title' => __('required_products'),
        'js' => true
    ));

    $required_products = db_get_fields('SELECT required_id FROM ?:product_required_products WHERE product_id = ?i', $product_id);

    Tygh::$app['view']->assign('required_products', $required_products);
}
