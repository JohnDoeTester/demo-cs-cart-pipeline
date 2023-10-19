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
    'name'        => 'UpdateProductInput',
    'description' => 'Represents a set of data required to update a product',
    'fields'      => [
        'product'              => [
            'type'        => Type::string(),
            'description' => 'Name',
        ],
        'company_id'           => [
            'type'        => Type::int(),
            'description' => 'Owner company ID',
        ],
        'product_code'         => [
            'type'        => Type::string(),
            'description' => 'CODE',
        ],
        'product_type'         => [
            'type'        => Type::string(),
            'description' => 'Type',
        ],
        'status'               => [
            'type'        => Type::string(),
            'description' => 'Status',
        ],
        'list_price'           => [
            'type'        => Type::float(),
            'description' => 'List price',
        ],
        'amount'               => [
            'type'        => Type::int(),
            'description' => 'Inventory amount',
        ],
        'weight'               => [
            'type'        => Type::float(),
            'description' => 'Weight',
        ],
        'length'               => [
            'type'        => Type::int(),
            'description' => 'Length',
        ],
        'width'                => [
            'type'        => Type::int(),
            'description' => 'Width',
        ],
        'height'               => [
            'type'        => Type::int(),
            'description' => 'Height',
        ],
        'shipping_freight'     => [
            'type'        => Type::float(),
            'description' => 'Shipping freight',
        ],
        'usergroup_ids'        => [
            'type'        => Type::listOf(Type::int()),
            'description' => 'Availability: User groups',
        ],
        'is_edp'               => [
            'type'        => Type::resolveType(BooleanInputType::class),
            'description' => 'Whether a product is electronically distributed',
        ],
        'edp_shipping'         => [
            'type'        => Type::resolveType(BooleanInputType::class),
            'description' => 'Whether an electronically distributed product requires shipping',
        ],
        'unlimited_download'   => [
            'type'        => Type::resolveType(BooleanInputType::class),
            'description' => 'Whether an electronically distributed product can be downloaded unlimited times',
        ],
        'tracking'             => [
            'type'        => Type::string(),
            'description' => 'Inventory tracking',
        ],
        'free_shipping'        => [
            'type'        => Type::resolveType(BooleanInputType::class),
            'description' => 'Whether a free shipping is enabled for a product',
        ],
        'zero_price_action'    => [
            'type'        => Type::string(),
            'description' => 'Zero price action',
        ],
        'avail_since'          => [
            'type'        => Type::int(),
            'description' => 'Available since UNIX timestamp',
        ],
        'out_of_stock_actions' => [
            'type'        => Type::string(),
            'description' => 'Out of stock actions',
        ],
        'min_qty'              => [
            'type'        => Type::int(),
            'description' => 'Minimum order quantity',
        ],
        'max_qty'              => [
            'type'        => Type::int(),
            'description' => 'Maximum order quantity',
        ],
        'qty_step'             => [
            'type'        => Type::int(),
            'description' => 'Quantity step',
        ],
        'list_qty_count'       => [
            'type'        => Type::int(),
            'description' => 'List quantity count',
        ],
        'tax_ids'              => [
            'type'        => Type::listOf(Type::int()),
            'description' => 'Taxes',
        ],
        'options_type'         => [
            'type'         => Type::string(),
            'descriptions' => 'Options type',
        ],
        'exceptions_type'      => [
            'type'         => Type::string(),
            'descriptions' => 'Exceptions type',
        ],
        'details_layout'       => [
            'type'         => Type::string(),
            'descrtiption' => 'Product details view',
        ],
        'shipping_params'      => [
            'type'        => Type::resolveType('product_shipping_parameters_input'),
            'description' => 'Shipping parameters',
        ],
        'price'                => [
            'type'        => Type::float(),
            'description' => 'Price',
        ],
        'category_ids'         => [
            'type'        => Type::listOf(Type::int()),
            'description' => 'Category IDs',
        ],
        'main_category'        => [
            'type'        => Type::int(),
            'description' => 'Main category ID',
        ],
        'full_description'     => [
            'type'        => Type::string(),
            'description' => 'Full description',
        ],
        'short_description'    => [
            'type'        => Type::string(),
            'description' => 'Short description',
        ],
        'main_pair'            => [
            'type'        => Type::resolveType('product_image_input'),
            'description' => 'Main image',
        ],
        'image_pairs'          => [
            'type'        => Type::listOf(Type::resolveType('product_image_input')),
            'description' => 'Additional images',
        ],
        'product_features'     => [
            'type'        => Type::listOf(Type::resolveType('product_feature_input')),
            'description' => 'Features',
        ],
        'removed_image_pair_ids' => [
            'type'        => Type::listOf(Type::int()),
            'description' => 'Removed pair IDs',
        ],
    ],
];

return $schema;
