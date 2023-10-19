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
    'name'        => 'OrderStatus',
    'description' => 'Represents an order status',
    'fields'      => [
        'status'      => [
            'type'        => Type::string(),
            'description' => 'Status code',
        ],
        'description' => [
            'type'        => Type::string(),
            'description' => 'Name',
        ],
        'color'       => [
            'type'        => Type::string(),
            'description' => 'Color',
        ],
    ],
];

return $schema;
