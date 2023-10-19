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
    'name'        => 'ProductOption',
    'description' => 'Represents an option',
    'fields'      => [
        'option_id'    => [
            'type'        => Type::int(),
            'description' => 'ID',
        ],
        'option_name'  => [
            'type'        => Type::string(),
            'description' => 'Name',
        ],
        'variant_name' => [
            'type'        => Type::listOf(Type::string()),
            'description' => 'Selected variant name',
            'resolve'     => function ($source) {
                return (array) $source['variant_name'];
            },
        ],
        'option_type'  => [
            'type'        => Type::string(),
            'descrtipion' => 'Type',
        ],
        'multiupload'  => [
            'type'        => Type::boolean(),
            'description' => 'Whether an option supports multiple variants',
        ],
    ],
];

return $schema;
