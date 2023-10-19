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
    'name'        => 'ProductFeatureInput',
    'description' => 'Represents a set of data to update product feature',
    'fields'      => [
        'feature_id' => [
            'type'        => Type::nonNull(Type::int()),
            'description' => 'Feature ID',
        ],
        'value'      => [
            'type'         => Type::string(),
            'defaultValue' => '',
            'description'  => 'Feature value (text features only)',
        ],
        'variants'   => [
            'type'         => Type::listOf(Type::resolveType('product_feature_variant_input')),
            'defaultValue' => [],
            'description'  => 'Selected feature variants (selectable features only)',
        ],
    ],
];

return $schema;
