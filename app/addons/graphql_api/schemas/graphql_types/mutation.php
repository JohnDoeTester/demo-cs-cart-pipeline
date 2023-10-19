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
use Tygh\Addons\GraphqlApi\InputType;
use Tygh\Addons\GraphqlApi\Type;

$schema = [
    'name'         => 'Mutation',
    'fields'       => [
        'delete_product' => [
            'type'        => Type::boolean(),
            'description' => 'Deletes product by ID',
            'args'        => [
                'id' => InputType::nonNull(InputType::int()),
            ],
        ],
        'create_product' => [
            'type'        => Type::int(),
            'description' => 'Creates product',
            'args'        => [
                'product' => InputType::nonNull(InputType::resolveType('create_product_input')),
            ],
        ],
        'update_product' => [
            'type'        => Type::boolean(),
            'description' => 'Updates product by ID',
            'args'        => [
                'id'      => InputType::nonNull(InputType::int()),
                'product' => InputType::nonNull(InputType::resolveType('update_product_input')),
            ],
        ],
        'update_order'   => [
            'type'        => Type::boolean(),
            'description' => 'Updates order by ID',
            'args'        => [
                'id'                => [
                    'type'        => InputType::nonNull(InputType::int()),
                    'description' => 'ID',
                ],
                'order' => [
                    'type'        => InputType::nonNull(InputType::resolveType('order_input')),
                    'description' => 'Order details',
                ],
                'notify_user'       => [
                    'type'         => InputType::boolean(),
                    'defaultValue' => true,
                    'description'  => 'Whether to notify customer',
                ],
                'notify_department' => [
                    'type'         => InputType::boolean(),
                    'defaultValue' => true,
                    'description'  => 'Whether to notify orders department',
                ],
                'notify_vendor'     => [
                    'type'         => InputType::boolean(),
                    'defaultValue' => true,
                    'description'  => 'Whether to notify vendor',
                ],
            ],
        ],
    ],
    'resolveField' => [Api::class, 'dispatchRequest'],
];

return $schema;
