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
    'name'        => 'ProductShippingParameters',
    'description' => 'Represents product shipping parameters',
    'fields'      => [
        'min_items_in_box' => [
            'type'        => Type::int(),
            'description' => 'Items in a box: min',
        ],
        'max_items_in_box' => [
            'type'        => Type::int(),
            'description' => 'Items in a box: max',
        ],
        'box_length'       => [
            'type'        => Type::int(),
            'description' => 'Box length',
        ],
        'box_width'        => [
            'type'        => Type::int(),
            'description' => 'Box width',
        ],
        'box_height'       => [
            'type'        => Type::int(),
            'description' => 'Box height',
        ],
    ],
];

return $schema;
