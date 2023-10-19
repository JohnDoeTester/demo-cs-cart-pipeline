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

return [
    'products'         => [
        'func'               => 'fn_get_products',
        'item_id'            => 'product_id',
        'allow_default_view' => true,
    ],
    'pages'            => [
        'func'    => 'fn_get_pages',
        'item_id' => 'page_id'
    ],
    'profiles'         => [
        'func'    => 'fn_get_users',
        'item_id' => 'user_id',
        'auth'    => true
    ],
    'orders'           => [
        'update_mode'        => 'details',
        'func'               => 'fn_get_orders',
        'item_id'            => 'order_id',
        'links_label'        => 'order',
        'show_item_id'       => true,
        'list_mode'          => 'update_status',
        'allow_default_view' => true,
    ],
    'shipments'        => [
        'update_mode'        => 'details',
        'func'               => 'fn_get_shipments_info',
        'item_id'            => 'shipment_id',
        'allow_default_view' => true,
    ],
    'categories'       => [
        'update_mode' => 'update',
        'func'        => 'fn_get_categories',
        'item_id'     => 'category_id'
    ],
    'product_features' => [
        'func'               => 'fn_get_product_features',
        'item_id'            => 'feature_id',
        'allow_default_view' => true
    ],
    'product_options'  => [
        'func'               => 'fn_get_product_global_options',
        'item_id'            => 'option_id',
    ],
    'cart'             => [
        'list_mode'          => 'cart_list',
        'func'               => 'fn_get_carts',
        'item_id'            => 'user_id',
    ],
    'companies'         => [
        'func'               => 'fn_get_companies',
        'item_id'            => 'company_id',
        'allow_default_view' => true,
    ],
];
