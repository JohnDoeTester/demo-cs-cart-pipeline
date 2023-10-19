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
use Tygh\Addons\GraphqlApi\Type\BooleanInputType;

$schema = [
    'name'        => 'ProductFeatureVariantInput',
    'description' => 'Represents a set of data to update product feature variant',
    'fields'      => [
        'variant_id' => [
            'type'         => Type::int(),
            'defaultValue' => null,
            'description'  => 'Variant ID',
        ],
        'variant'    => [
            'type'         => Type::string(),
            'defaultValue' => '',
            'description'  => 'Name',
        ],
        'selected'   => [
            'type'         => Type::nonNull(Type::resolveType(BooleanInputType::class)),
            'defaultValue' => false,
            'description'  => 'Whether a variant is selected for a product',
        ],
    ],
];

return $schema;
