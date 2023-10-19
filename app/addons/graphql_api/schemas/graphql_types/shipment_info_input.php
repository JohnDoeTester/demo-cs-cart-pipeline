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
    'name'        => 'ShipmentInfoInput',
    'description' => 'Represents a shipment details',
    'fields'      => [
        'shipment_id'     => [
            'type'         => Type::int(),
            'defaultValue' => 0,
            'description'  => 'ID',
        ],
        'group_id'        => [
            'type'         => Type::nonNull(Type::int()),
            'defaultValue' => 0,
            'description'  => 'Product group ID',
        ],
        'shipping_id'     => [
            'type'         => Type::nonNull(Type::int()),
            'defaultValue' => 0,
            'description'  => 'Shipping method ID',
        ],
        'carrier'         => [
            'type'         => Type::string(),
            'defaultValue' => '',
            'description'  => 'Carrier ID',
        ],
        'tracking_number' => [
            'type'         => Type::string(),
            'defaultValue' => '',
            'description'  => 'Tracking number',
        ],
    ],
];

return $schema;
