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

use Tygh\Enum\NotificationSeverity;

defined('BOOTSTRAP') or die('Access denied');

if ($mode === 'cart') {
    /** @var \Tygh\Location\Manager $manager */
    $location_manager = Tygh::$app['location'];
    $destination_id = $location_manager->getDestinationId();

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    /** @var array $cart_products */
    $cart_products = $view->getTemplateVars('cart_products');
    foreach ($cart_products as &$product) {
        $product = array_merge($product, fn_warehouses_get_availability_summary($product['product_id'], $destination_id, CART_LANGUAGE, $product['amount']));
    }
    unset($product);

    $view->assign('cart_products', $cart_products);
}

if ($mode === 'checkout') {
    /** @var \Tygh\Location\Manager $manager */
    $location_manager = Tygh::$app['location'];
    $destination_id = $location_manager->getDestinationId();

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    /** @var array $cart_products */
    $cart_products = $view->getTemplateVars('cart_products');
    /** @var array $cart */
    $cart = $view->getTemplateVars('cart');

    $cart['warn_about_delay'] = false;
    foreach ($cart_products as &$product) {
        $product = array_merge($product, fn_warehouses_get_availability_summary($product['product_id'], $destination_id, CART_LANGUAGE, $product['amount']));

        if (!empty($product['warn_about_delay'])) {
            $cart['warn_about_delay'] = true;
            break;
        }
    }
    unset($product);

    $view->assign('cart', $cart);
}

unset(Tygh::$app['session']['warehouses']['out_of_stock_products']);
