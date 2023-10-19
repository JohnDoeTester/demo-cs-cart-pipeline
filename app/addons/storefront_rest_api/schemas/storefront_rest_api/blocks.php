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

$schema = [
    'categories' => [
        'content' => [
            'items' => [
                'fillings'      => [
                    'manually'               => [
                        'params' => [
                            'get_images' => true,
                        ],
                    ],
                    'newest'                 => [
                        'params' => [
                            'get_images' => true,
                        ],
                    ],
                    'full_tree_cat'          => [
                        'params' => [
                            'get_images' => true,
                        ],
                    ],
                    'subcategories_tree_cat' => [
                        'params' => [
                            'get_images' => true,
                        ],
                    ],
                ],
                'post_function' => function ($categories, $blocks_schema, $block, $params) {
                    $icon_sizes = isset($params['icon_sizes']['categories'])
                        ? $params['icon_sizes']['categories']
                        : $params['icon_sizes'];

                    $categories = fn_storefront_rest_api_set_categories_icons($categories, $icon_sizes);

                    return $categories;
                },
            ],
        ],
    ],
    'products'   => [
        'content' => [
            'items' => [
                'post_function' => static function ($products, $block_schema, $block, $params) {
                    $currency = isset($params['currency'])
                        ? $params['currency']
                        : CART_PRIMARY_CURRENCY;
                    $icon_sizes = isset($params['icon_sizes']['products'])
                        ? $params['icon_sizes']['products']
                        : $params['icon_sizes'];

                    $products = fn_storefront_rest_api_format_products_prices($products, $currency);
                    $products = fn_storefront_rest_api_set_products_icons($products, $icon_sizes);

                    return $products;
                },
            ],
        ],
    ],
    'banners'    => [
        'content' => [
            'items' => [
                'post_function' => function ($banners, $block_schema, $block, $params) {
                    $icon_sizes = isset($params['icon_sizes']['banners'])
                        ? $params['icon_sizes']['banners']
                        : $params['icon_sizes'];

                    $banners = fn_storefront_rest_api_set_banners_icons($banners, $icon_sizes);

                    return $banners;
                },
            ],
        ],
    ],
];

return $schema;
