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

use Tygh\Addons\GraphqlApi\Type;

$schema = [
    'name'        => 'ProductGroup',
    'description' => 'Represents a product group',
    'fields'      => [
        'group_id' => [
            'type'        => Type::int(),
            'description' => 'ID',
        ],
        'products' => [
            'type'        => Type::listOf(Type::resolveType('product')),
            'description' => 'Products',
        ],
    ],
];

return $schema;
