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
    'name'        => 'ProductFeatureVariant',
    'description' => 'Represents product feature variant',
    'fields'      => [
        'variant_id'  => [
            'type'        => Type::int(),
            'description' => 'Variant ID',
        ],
        'variant'     => [
            'type'        => Type::string(),
            'description' => 'Name',
        ],
        'image_pairs' => [
            'type'        => Type::listOf(Type::resolveType('image')),
            'description' => 'Variant images',
        ],
        'selected'    => [
            'type'        => Type::boolean(),
            'description' => 'Whether a variant is selected for a product',
            'resolve'     => function ($source) {
                return $source['selected'] !== null;
            },
        ],
    ],
];

return $schema;
