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

use Tygh\Enum\UserTypes;

defined('BOOTSTRAP') or die('Access denied');

return [
    'categories' => static function () {
        list($categories_list) = fn_get_categories(['plain' => true]);
        foreach ($categories_list as $category) {
            if (empty($category['category_id'])) {
                continue;
            }
            fn_delete_category($category['category_id']);
        }
    },
    'products' => static function () {
        $product_ids = db_get_fields('SELECT product_id FROM ?:products');
        foreach ($product_ids as $product_id) {
            fn_delete_product($product_id);
        }
    },
    'features' => static function () {
        $feature_ids = db_get_fields('SELECT feature_id FROM ?:product_features');
        foreach ($feature_ids as $feature_id) {
            fn_delete_feature($feature_id);
        }
    },
    'filters' => static function () {
        $filter_ids = db_get_fields('SELECT filter_id FROM ?:product_filters');
        foreach ($filter_ids as $filter_id) {
            fn_delete_product_filter($filter_id);
        }
    },
    'options' => static function () {
        $option_ids = db_get_fields('SELECT option_id FROM ?:product_options');
        foreach ($option_ids as $option_id) {
            fn_delete_product_option($option_id);
        }
    },
    'users' => static function () {
        $user_ids = db_get_fields('SELECT user_id FROM ?:users WHERE user_type <> ?s', UserTypes::ADMIN);
        foreach ($user_ids as $user_id) {
            fn_delete_user($user_id);
        }
    },
    'promotions' => static function () {
        $promotion_ids = db_get_fields('SELECT promotion_id FROM ?:promotions');
        foreach ($promotion_ids as $promotion_id) {
            fn_delete_promotions($promotion_id);
        }
    },
    'orders' => static function () {
        $order_ids = db_get_fields('SELECT order_id FROM ?:orders');
        foreach ($order_ids as $order_id) {
            fn_delete_order($order_id);
        }
    },
];
