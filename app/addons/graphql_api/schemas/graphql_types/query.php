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

use Tygh\Addons\GraphqlApi\Api;
use Tygh\Addons\GraphqlApi\Type;
use Tygh\Registry;

$schema = [
    'name'         => 'Query',
    'fields'       => [
        'product'          => [
            'type'        => Type::resolveType('product'),
            'description' => 'Returns a product by ID',
            'args'        => [
                'id'                  => [
                    'type'        => Type::nonNull(Type::int()),
                    'description' => 'Product ID',
                ],
                'get_icon'            => [
                    'type'         => Type::boolean(),
                    'defaultValue' => true,
                    'description'  => 'Whether to get product icons',
                ],
                'get_detailed'        => [
                    'type'         => Type::boolean(),
                    'defaultValue' => true,
                    'description'  => 'Whether to get product detailed image',
                ],
                'get_additional'      => [
                    'type'         => Type::boolean(),
                    'defaultValue' => true,
                    'description'  => 'Whether to get additional product images',
                ],
                'get_features'        => [
                    'type'         => Type::boolean(),
                    'defaultValue' => true,
                    'description'  => 'Whether to get product features',
                ],
                'get_taxed_prices'    => [
                    'type'         => Type::boolean(),
                    'defaultValue' => true,
                    'description'  => 'Whether to get clean and taxed prices',
                ],
                'features_display_on' => [
                    'type'         => Type::string(),
                    'defaultValue' => 'A',
                    'description'  => 'Area of site to display features on',
                ],
            ],
        ],
        'products'         => [
            'type'        => Type::listOf('product'),
            'description' => 'Returns products list',
            'args'        => [
                'page'                => [
                    'type'         => Type::int(),
                    'defaultValue' => 1,
                    'description'  => 'Page',
                ],
                'items_per_page'      => [
                    'type'         => Type::int(),
                    'defaultValue' => Registry::get('settings.Appearance.admin_elements_per_page'),
                    'description'  => 'Items per page',
                ],
                'get_icon'            => [
                    'type'         => Type::boolean(),
                    'defaultValue' => true,
                    'description'  => 'Whether to get product icons',
                ],
                'get_detailed'        => [
                    'type'         => Type::boolean(),
                    'defaultValue' => true,
                    'description'  => 'Whether to get product detailed image',
                ],
                'get_additional'      => [
                    'type'         => Type::boolean(),
                    'defaultValue' => true,
                    'description'  => 'Whether to get additional product images',
                ],
                'get_features'        => [
                    'type'         => Type::boolean(),
                    'defaultValue' => true,
                    'description'  => 'Whether to get product features',
                ],
                'get_taxed_prices'    => [
                    'type'         => Type::boolean(),
                    'defaultValue' => true,
                    'description'  => 'Whether to get clean and taxed prices',
                ],
                'features_display_on' => [
                    'type'         => Type::string(),
                    'defaultValue' => 'A',
                    'description'  => 'Area of site to display features on',
                ],
                'company_id'          => [
                    'type'         => Type::int(),
                    'defaultValue' => 0,
                    'description'  => 'Company ID to fetch products for (customer only)',
                ],
            ],
        ],
        'category'         => [
            'type'        => Type::resolveType('category'),
            'description' => 'Returns a category by its ID',
            'args'        => [
                'id'                 => [
                    'type'        => Type::nonNull(Type::int()),
                    'description' => 'Category ID',
                ],
                'category_delimiter' => [
                    'type'         => Type::string(),
                    'defaultValue' => '/',
                    'description'  => 'Category path delimiter',
                ],
            ],
        ],
        'categories'       => [
            'type'        => Type::listOf(Type::resolveType('category')),
            'description' => 'Returns categories list',
            'args'        => [
                'parent_category_id' => [
                    'type'         => Type::int(),
                    'defaultValue' => 0,
                    'description'  => 'Parent category ID',
                ],
                'page'               => [
                    'type'         => Type::int(),
                    'defaultValue' => 1,
                    'description'  => 'Page',
                ],
                'items_per_page'     => [
                    'type'         => Type::int(),
                    'defaultValue' => Registry::get('settings.Appearance.admin_elements_per_page'),
                    'description'  => 'Items per page',
                ],
                'category_delimiter' => [
                    'type'         => Type::string(),
                    'defaultValue' => '/',
                    'description'  => 'Category path delimiter',
                ],
            ],
        ],
        'product_feature'  => [
            'type'        => Type::resolveType('product_feature'),
            'description' => 'Returns a product feature by its ID',
            'args'        => [
                'id' => [
                    'type'        => Type::nonNull(Type::int()),
                    'description' => 'Product feature ID',
                ],
            ],
        ],
        'product_features' => [
            'type'        => Type::listOf(Type::resolveType('product_feature')),
            'description' => 'Returns product features list',
            'args'        => [
                'product_id'           => [
                    'type'         => Type::int(),
                    'defaultValue' => null,
                    'description'  => 'Product ID',
                ],
                'page'           => [
                    'type'         => Type::int(),
                    'defaultValue' => 1,
                    'description'  => 'Page',
                ],
                'items_per_page' => [
                    'type'         => Type::int(),
                    'defaultValue' => Registry::get('settings.Appearance.admin_elements_per_page'),
                    'description'  => 'Items per page',
                ],
                'feature_types'  => [
                    'type'         => Type::listOf(Type::string()),
                    'defaultValue' => [],
                    'description'  => 'Feature types',
                ],
                'exclude_group'  => [
                    'type'         => Type::boolean(),
                    'defaultValue' => false,
                    'description'  => 'Whether to exclude feature groups',
                ],
                'parent_id'      => [
                    'type'         => Type::int(),
                    'defaultValue' => null,
                    'description'  => 'Feature group ID',
                ],
            ],
        ],
        'currencies'       => [
            'type'        => Type::listOf(Type::resolveType('currency')),
            'description' => 'Returns currencies list',
        ],
        'currency'         => [
            'type'        => Type::resolveType('currency'),
            'description' => 'Returns currency by its code',
            'args'        => [
                'id' => [
                    'type'        => Type::nonNull(Type::string()),
                    'description' => 'Currency code',
                ],
            ],
        ],
        'orders'           => [
            'type'        => Type::listOf(Type::resolveType('order')),
            'description' => 'Returns orders list',
            'args'        => [
                'page'            => [
                    'type'         => Type::int(),
                    'defaultValue' => 1,
                    'description'  => 'Page',
                ],
                'items_per_page'  => [
                    'type'         => Type::int(),
                    'defaultValue' => Registry::get('settings.Appearance.admin_elements_per_page'),
                    'description'  => 'Items per page',
                ],
                'native_language' => [
                    'type'         => Type::boolean(),
                    'defaultValue' => false,
                    'description'  => 'Whether to get orders information using the language the order was placed on',
                ],
                'phone'           => [
                    'type'         => Type::string(),
                    'defaultValue' => null,
                    'description'  => 'Search criteria: phone',
                ],
                'cname'           => [
                    'type'         => Type::string(),
                    'defaultValue' => null,
                    'description'  => 'Search criteria: customer name',
                ],
                'email'           => [
                    'type'         => Type::string(),
                    'defaultValue' => null,
                    'description'  => 'Search criteria: customer email',
                ],
                'total_from'      => [
                    'type'         => Type::float(),
                    'defaultValue' => null,
                    'description'  => 'Search criteria: minimal order total',
                ],
                'total_to'        => [
                    'type'         => Type::float(),
                    'defaultValue' => null,
                    'description'  => 'Search criteria: maximal order total',
                ],
                'status'          => [
                    'type'         => Type::string(),
                    'defaultValue' => null,
                    'description'  => 'Search criteria: order status',
                ],
                'p_ids'           => [
                    'type'         => Type::listOf(Type::int()),
                    'defaultValue' => [],
                    'description'  => 'Search criteria: product IDs',
                ],
            ],
        ],
        'order'            => [
            'type'        => Type::resolveType('order'),
            'description' => 'Returns an orders by its ID',
            'args'        => [
                'id'              => [
                    'type'        => Type::nonNull(Type::int()),
                    'description' => 'Order ID',
                ],
                'native_language' => [
                    'type'         => Type::boolean(),
                    'defaultValue' => false,
                    'description'  => 'Whether to get an order information using the language the order was placed on',
                ],
            ],
        ],
        'carriers'         => [
            'type'        => Type::listOf(Type::resolveType('carrier')),
            'description' => 'Returns list of shipping carriers',
        ],
        'order_statuses'   => [
            'type'        => Type::listOf(Type::resolveType('order_status')),
            'description' => 'Returns list of order statuses',
        ],
    ],
    'resolveField' => [Api::class, 'dispatchRequest'],
];

return $schema;
