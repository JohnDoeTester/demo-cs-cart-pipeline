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


namespace Tygh\Addons\ProductVariations\HookHandlers;


use Tygh;
use Tygh\Addons\ProductVariations\ServiceProvider;
use Tygh\Application;
use Tygh\Enum\YesNo;

/**
 * This class describes the hook handlers related to cart, checkout, and order management
 *
 * @package Tygh\Addons\ProductVariations\HookHandlers
 */
class CartsHookHandler
{
    protected $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * The "get_cart_products_post" hook handler.
     *
     * Actions performed:
     *  - Fetches the information for all products about the variation groups to which they belong.
     *
     * @see fn_get_cart_products
     */
    public function onGetCartProductsPost($user_id, $params, &$cart_products, $fields, $conditions)
    {
        if (empty($cart_products)) {
            return;
        }
    
        $cart_products = ServiceProvider::getProductRepository()->loadProductsGroupInfo($cart_products);
    }

    /**
     * The "get_order_info" hook handler.
     *
     * Actions performed:
     *  - Fetches the information for all products about the variation groups to which they belong.
     *
     * @see fn_get_order_info
     */
    public function onGetOrderInfo(&$order, $additional_data)
    {
        if (empty($order['products'])) {
            return;
        }
        $order['products'] = ServiceProvider::getProductRepository()->loadProductsGroupInfo($order['products']);
    }

    /**
     * The "get_user_edp_post" hook handler.
     *
     * Actions performed:
     *  - Fetches the information for all products about the variation groups to which they belong.
     *
     * @see fn_get_user_edp
     */
    public function onGetUserEdpPost($params, $items_per_page, &$products)
    {
        if (empty($products)) {
            return;
        }

        $products = ServiceProvider::getProductRepository()->loadProductsGroupInfo($products);
    }

    /**
     * The "check_amount_in_stock_before_check" hook handler.
     *
     * Actions performed:
     *  - Actualizes the product name for variation (product name + feature values).
     *
     * @see fn_check_amount_in_stock
     */
    public function onCheckAmountInStockBeforeCheck($product_id, $amount, $product_options, $cart_id, $is_edp, $original_amount, $cart, $update_id, &$product, $current_amount)
    {
        if (!ServiceProvider::getProductIdMap()->isChildProduct($product_id)) {
            return;
        }

        $product['product'] = fn_get_product_name($product_id);
    }

    /**
     * The `get_cart_product_data` hook handler.
     *
     * Action performed:
     *     - Allows to consider every variation of product for same product for calculation quantity discounts reasons.
     *
     * @param int                                                     $product_id   Product ID
     * @param array<string, float>                                    $_pdata       Product data
     * @param array<string, int>                                      $product_data External state of product data
     * @param array<string, string>                                   $auth         Array with authorization data
     * @param array<string, array<string, array<string, string|int>>> $cart         Array of cart content and user information necessary for purchase
     * @param int                                                     $hash         Unique product HASH
     *
     * @see fn_get_cart_product_data()
     *
     * @return void
     */
    public function onGetCartProductData($product_id, array &$_pdata, array $product_data, array $auth, array $cart, $hash)
    {
        if (empty($cart['products']) || !ServiceProvider::isAllowQuantityDiscountOnDifferentVariantion()) {
            return;
        }
        $cart_products = $cart['products'];
        $group_repository = ServiceProvider::getGroupRepository();
        $combined_amount = 0;
        $variation_group_id = $group_repository->findGroupIdByProductId($product_id);
        if (!$variation_group_id) {
            return;
        }
        $product_ids = $group_repository->findGroupProductIdsByGroupIds([$variation_group_id]);
        foreach ($cart_products as $cart_product) {
            if (!in_array($cart_product['product_id'], $product_ids)) {
                continue;
            }
            $combined_amount += (int) $cart_product['amount'];
        }
        $_pdata['price'] = fn_get_product_price($product_id, $combined_amount, $auth);
        $_pdata['base_price'] = (isset($product_data['stored_price']) && YesNo::toBool($product_data['stored_price']))
            ? $product_data['price']
            : $_pdata['price'];
    }

    /**
     * The `post_add_to_cart` hook handler.
     *
     * Action performed:
     *     - Reshuffle cart products after replacing one variation to another, sets new variation in a position of an old one.
     *
     * @param array<string, string>                                                                 $product_data Product data.
     * @param array{prev_variation_position?: int, products?: array<string, array<string, string>>} $cart         Current cart state.
     * @param array<string, string>                                                                 $auth         Authentication info.
     * @param bool                                                                                  $update       Flag, is this an update of existing product or adding new one.
     * @param array<string, int>                                                                    $ids          Array of cart id - product_id pairs.
     *
     * @see fn_add_product_to_cart()
     *
     * @return void
     */
    public function onPostAddToCart(array $product_data, array &$cart, array $auth, $update, array $ids)
    {
        if ($update || empty($cart['products']) || !isset($cart['prev_variation_position'])) {
            return;
        }
        $new_variation_cart_id = key($ids);
        $i = 0;
        $cart_products = [];
        foreach ($cart['products'] as $cart_id => $cart_product_data) {
            if ($i++ === $cart['prev_variation_position']) {
                $cart_products[$new_variation_cart_id] = $cart['products'][$new_variation_cart_id];
            }
            if ($cart_id === $new_variation_cart_id) {
                continue;
            }
            $cart_products[$cart_id] = $cart_product_data;
        }
        unset($cart['prev_variation_position']);
        $cart['products'] = $cart_products;
    }
}
