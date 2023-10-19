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

use GraphQL\Type\Definition\ResolveInfo;
use Tygh\Addons\GraphqlApi\Context;
use Tygh\Addons\GraphqlApi\Type;

$schema = [
    'name'        => 'Category',
    'description' => 'Represents a category',
    'fields'      => [
        'category_id'   => [
            'type'        => Type::int(),
            'description' => 'Category ID',
        ],
        'parent_id'     => [
            'type'        => Type::int(),
            'description' => 'Parent category ID',
        ],
        'category'      => [
            'type'        => Type::string(),
            'description' => 'Name',
        ],
        'status'        => [
            'type'        => Type::string(),
            'description' => 'Status',
        ],
        'product_count' => [
            'type'        => Type::int(),
            'description' => 'Amount of products in this category',
        ],
        'category_path' => [
            'type'        => Type::string(),
            'description' => 'Full category path',
        ],
        'parent_path'   => [
            'type'        => Type::string(),
            'description' => 'Parent category path',
            'args'        => [
                'category_delimiter' => [
                    'type'         => Type::string(),
                    'defaultValue' => '/',
                    'description'  => 'Category path delimiter',
                ],
            ],
            'resolve'     => function ($source, $args, Context $context, ResolveInfo $resolve_info) {
                $full_path = explode($args['category_delimiter'], $source['category_path']);
                array_pop($full_path);

                return implode($args['category_delimiter'], $full_path);
            },
        ],
        'has_children'  => [
            'type'        => Type::boolean(),
            'description' => 'Whether a category has subcategories',
            'resolve'     => function ($source, $args, Context $context, ResolveInfo $resolve_info) {
                return !empty($source['has_children']);
            },
        ],
    ],
];

return $schema;
