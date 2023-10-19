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

use Tygh\Addons\ProductVariations\Product\Type\Type;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * @var string $mode
 * @var string $action
 * @var array $auth
 */

if ($mode === 'view' || $mode === 'quick_view') {
    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    /** @var array $product */
    $product = $view->getTemplateVars('product');

    if ($product['product_type'] === Type::PRODUCT_TYPE_VARIATION) {
        $parent_product_id = $product['parent_product_id'];

        $is_exist = array_search($product['product_id'], Tygh::$app['session']['recently_viewed_products']);
        unset(Tygh::$app['session']['recently_viewed_products'][$is_exist]);

        fn_add_product_to_recently_viewed($parent_product_id);
    }

    if (
        defined('AJAX_REQUEST')
        && !empty($_REQUEST['product_id'])
        && empty($view->getTemplateVars('redirect_url'))
    ) {
        $view->assign('redirect_url', 'products.view?product_id=' . $_REQUEST['product_id']);
    }
}