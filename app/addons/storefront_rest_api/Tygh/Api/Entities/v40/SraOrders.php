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

namespace Tygh\Api\Entities\v40;

use Tygh\Api\Entities\Orders;
use Tygh\Enum\YesNo;
use Tygh\Tygh;

/**
 * Class SraOrders
 *
 * @package Tygh\Api\Entities
 */
class SraOrders extends Orders
{
    protected $icon_size_small = [500, 500];

    protected $icon_size_big = [1000, 1000];

    /**
     * @inheritdoc
     */
    public function index($id = 0, $params = [])
    {
        $result = parent::index($id, $params);

        if (
            $this->safeGet($params, 'get_children_orders_data', false)
            && $id
            && !empty($result['data']['is_parent_order'])
            && $result['data']['is_parent_order'] === YesNo::YES
        ) {
            $result['data']['orders'] = $this->getChildrenOrdersData($id);
        }

        $currency = $this->getCurrencyCode($params);
        $statuses = fn_storefront_rest_api_get_formatted_orders_statuses($this->getLanguageCode($params), true);

        $params['icon_sizes'] = $this->safeGet($params, 'icon_sizes', [
            'main_pair'   => [$this->icon_size_big, $this->icon_size_small],
            'image_pairs' => [$this->icon_size_small],
        ]);

        if ($id && !empty($result['data'])) {
            $result['data'] = fn_storefront_rest_api_format_order_prices($result['data'], $currency);
            $result['data'] = $this->setProductsIcons($result['data'], $params['icon_sizes']);
            $result['data']['status_data'] = $statuses[(string) $result['data']['status']];
        }

        if (!empty($result['data']['orders'])) {
            foreach ($result['data']['orders'] as &$order) {
                $order = fn_storefront_rest_api_format_order_prices($order, $currency);
                $order['status_data'] = $statuses[(string) $order['status']];

                if ($this->safeGet($params, 'get_orders_data', false)) {
                    $order = $this->setProductsIcons($order, $params['icon_sizes']);
                }
            }
            unset($order);
        }

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function create($params)
    {
        $params['action'] = $this->safeGet($params, 'action', '');

        return parent::create($params);
    }

    /**
     * Sets products icons
     *
     * @param array $order_data Order data
     * @param array $icon_sizes Icon sizes params
     *
     * @return array Order data with products icons
     */
    protected function setProductsIcons(array $order_data, $icon_sizes)
    {
        foreach ($order_data['product_groups'] as &$product_group) {
            $product_group['products'] = fn_storefront_rest_api_set_products_icons(
                $product_group['products'],
                $icon_sizes
            );
        }

        foreach ($order_data['products'] as &$product_data) {
            $product_data['main_pair'] = fn_get_cart_product_icon($product_data['product_id'], $product_data);
        }

        $order_data['products'] = fn_storefront_rest_api_set_products_icons($order_data['products'], $icon_sizes);

        return $order_data;
    }

    /**
     * Gets children orders data.
     *
     * @param int $order_id Parent order identifier
     *
     * @return array
     */
    protected function getChildrenOrdersData($parent_order_id)
    {
        $orders_data = [];

        if ($parent_order_id) {
            $order_ids = Tygh::$app['db']->getColumn('SELECT order_id FROM ?:orders WHERE parent_order_id = ?i', $parent_order_id);

            foreach ($order_ids as $order_id) {
                $order_data = parent::index($order_id, $params);

                if (!empty($order_data['data'])) {
                    $orders_data[] = $order_data['data'];
                }
            }
        }

        return $orders_data;
    }
}
