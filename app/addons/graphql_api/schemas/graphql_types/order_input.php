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

use Tygh\Addons\GraphqlApi\InputType as Type;

$schema = [
    'name'        => 'OrderInput',
    'description' => 'Represents an order',
    'fields'      => [
        // data
        'status'          => [
            'type'         => Type::string(),
            'defaultValue' => null,
            'description'  => 'Order status',
        ],
        // shipping
        'update_shipping' => [
            'type'         => Type::listOf(Type::resolveType('shipment_info_input')),
            'defaultValue' => null,
            'description'  => 'Shipping information',
        ],
        // notes
        'notes'           => [
            'type'         => Type::string(),
            'defaultValue' => null,
            'description'  => 'Customer notes',
        ],
        'details'         => [
            'type'         => Type::string(),
            'defaultValue' => null,
            'description'  => 'Staff only notes',
        ],
    ],
];

return $schema;
