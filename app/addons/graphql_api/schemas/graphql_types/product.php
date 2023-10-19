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

use GraphQL\Deferred;
use Tygh\Addons\GraphqlApi\Context;
use Tygh\Addons\GraphqlApi\Type;
use Tygh\Registry;

$schema = [
    'name'        => 'Product',
    'description' => 'Represents a product',
    'fields'      => [
        'product_id'           => [
            'type'        => Type::int(),
            'description' => 'ID',
        ],
        'product'              => [
            'type'        => Type::string(),
            'description' => 'Name',
        ],
        'company_id'           => [
            'type'        => Type::int(),
            'description' => 'Owner company ID',
        ],
        'company_name'         => [
            'type'        => Type::string(),
            'description' => 'Owner company name',
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
        'list_price_formatted'      => [
            'type'        => Type::resolveType('formatted_price'),
            'description' => 'Formatted list price',
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
        'timestamp'            => [
            'type'        => Type::int(),
            'description' => 'Creation UNIX timestamp',
        ],
        'updated_timestamp'    => [
            'type'        => Type::int(),
            'description' => 'Update UNIX timestamp',
        ],
        'usergroup_ids'        => [
            'type'        => Type::listOf(Type::int()),
            'description' => 'Availability: User groups',
        ],
        'is_edp'               => [
            'type'        => Type::boolean(),
            'description' => 'Whether a product is electronically distributed',
        ],
        'edp_shipping'         => [
            'type'        => Type::boolean(),
            'description' => 'Whether an electronically distributed product requires shipping',
        ],
        'unlimited_download'   => [
            'type'        => Type::boolean(),
            'description' => 'Whether an electronically distributed product can be downloaded unlimited times',
        ],
        'tracking'             => [
            'type'        => Type::string(),
            'description' => 'Inventory tracking',
        ],
        'free_shipping'        => [
            'type'        => Type::boolean(),
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
            'type'        => Type::resolveType('product_shipping_parameters'),
            'description' => 'Shipping parameters',
            'resolve'     => function ($source) {
                $shipping_params = unserialize($source['shipping_params']);

                return $shipping_params;
            },
        ],
        'price'                => [
            'type'        => Type::float(),
            'description' => 'Price',
        ],
        'price_formatted'      => [
            'type'        => Type::resolveType('formatted_price'),
            'description' => 'Formatted price',
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
        'categories'           => [
            'type'        => Type::listOf(Type::resolveType('category')),
            'description' => 'Categories',
            'resolve'     => static function ($source, $args, Context $context) {
                if (empty($source['category_ids'])) {
                    return [];
                }
                $category_ids = Registry::ifGet('runtime.api.category_ids', []);
                $category_ids = array_merge($category_ids, (array) $source['category_ids']);

                Registry::set('runtime.api.category_ids', array_unique($category_ids));

                return new Deferred(static function () use ($source, $context) {
                    $categories = Registry::ifGet('runtime.api.categories', null);

                    $result = [];
                    if ($categories === null) {
                        $category_ids = Registry::ifGet('runtime.api.category_ids', []);
                        [$categories] = fn_get_categories(['category_ids' => $category_ids], $context->getLanguageCode());

                        Registry::set('runtime.api.categories', $categories);
                    }

                    foreach ($source['category_ids'] as $category_id) {
                        $result[$category_id] = isset($categories[$category_id])
                            ? $categories[$category_id]
                            : [];
                    }

                    return $result;
                });
            },
        ],
        'main_pair'            => [
            'type'        => Type::resolveType('product_image'),
            'description' => 'Main image',
        ],
        'image_pairs'          => [
            'type'        => Type::listOf(Type::resolveType('product_image')),
            'description' => 'Additional images',
        ],
        'product_features'     => [
            'type'        => Type::listOf(Type::resolveType('product_feature')),
            'description' => 'Features',
            'resolve'     => function ($source) {
                Registry::set('runtime.api.product_id', $source['product_id']);

                return $source['product_features'];
            },
        ],
        'clean_price'          => [
            'type'        => Type::float(),
            'description' => 'Price without taxes',
        ],
        'clean_price_formatted'      => [
            'type'        => Type::resolveType('formatted_price'),
            'description' => 'Formatted clean price',
        ],
        'taxed_price'          => [
            'type'        => Type::float(),
            'description' => 'Price with taxes',
        ],
        'taxed_price_formatted'      => [
            'type'        => Type::resolveType('formatted_price'),
            'description' => 'Formatted taxed price',
        ],
        'product_options'     => [
            'type'        => Type::listOf(Type::resolveType('product_option')),
            'description' => 'Selected options',
        ],
    ],
];

return $schema;
