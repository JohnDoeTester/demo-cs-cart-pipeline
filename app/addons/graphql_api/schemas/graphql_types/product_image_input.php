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
    'name'        => 'ProductImageInput',
    'description' => 'Represents a set of data to update product image',
    'fields'      => [
        'detailed' => [
            'type'        => Type::resolveType('image_input'),
            'description' => 'Detailed image',
        ],
        'icon'     => [
            'type'        => Type::resolveType('image_input'),
            'description' => 'Image icon',
        ],
    ],
];

return $schema;
