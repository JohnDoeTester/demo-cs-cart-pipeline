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
use GraphQL\Type\Definition\ResolveInfo;
use Tygh\Addons\GraphqlApi\Context;
use Tygh\Addons\GraphqlApi\Type;
use Tygh\Registry;
use Tygh\Tools\Url;

$schema = [
    'name'        => 'Order',
    'description' => 'Represents an order',
    'fields'      => [
        // data
        'order_id'           => [
            'type'        => Type::int(),
            'description' => 'ID',
        ],
        'status'             => [
            'type'        => Type::string(),
            'description' => 'Order status',
        ],
        'status_data'        => [
            'type'        => Type::resolveType('order_status'),
            'description' => 'Order status data',
        ],
        'secondary_currency' => [
            'type'        => Type::string(),
            'description' => 'Currency code the order was placed in',
        ],
        'timestamp'          => [
            'type'        => Type::int(),
            'description' => 'Creation UNIX timestamp',
        ],
        // costs
        'total'              => [
            'type'        => Type::float(),
            'description' => 'Total',
        ],
        'total_formatted'      => [
            'type'        => Type::resolveType('formatted_price'),
            'description' => 'Formatted total',
        ],
        'subtotal'           => [
            'type'        => Type::float(),
            'description' => 'Subtotal',
        ],
        'subtotal_formatted'      => [
            'type'        => Type::resolveType('formatted_price'),
            'description' => 'Formatted subtotal',
        ],
        'subtotal_discount'  => [
            'type'        => Type::float(),
            'description' => 'Subtotal discount',
        ],
        'subtotal_discount_formatted'      => [
            'type'        => Type::resolveType('formatted_price'),
            'description' => 'Formatted subtotal discount',
        ],
        'payment_surcharge'  => [
            'type'        => Type::float(),
            'description' => 'Payment surcharge',
        ],
        'payment_surcharge_formatted'      => [
            'type'        => Type::resolveType('formatted_price'),
            'description' => 'Formatted payment surcharge',
        ],
        'shipping_cost'      => [
            'type'        => Type::float(),
            'description' => 'Shipping cost',
        ],
        'shipping_cost_formatted'      => [
            'type'        => Type::resolveType('formatted_price'),
            'description' => 'Formatted shipping cost',
        ],
        // notes
        'notes'              => [
            'type'        => Type::string(),
            'description' => 'Customer notes',
        ],
        'details'            => [
            'type'        => Type::string(),
            'description' => 'Staff only notes',
        ],
        // customer
        'user_id'            => [
            'type'        => Type::int(),
            'description' => 'Customer ID',
        ],
        'firstname'          => [
            'type'        => Type::string(),
            'description' => 'Customer firstname',
        ],
        'lastname'           => [
            'type'        => Type::string(),
            'description' => 'Customer lastname',
        ],
        'phone'              => [
            'type'        => Type::string(),
            'description' => 'Phone',
        ],
        'email'              => [
            'type'        => Type::string(),
            'description' => 'E-mail',
        ],
        // billing
        'b_firstname'        => [
            'type'        => Type::string(),
            'description' => 'Billing: Customer firstname',
        ],
        'b_lastname'         => [
            'type'        => Type::string(),
            'description' => 'Billing: Customer lastname',
        ],
        'b_address'          => [
            'type'        => Type::string(),
            'description' => 'Billing: Address, 1st line',
        ],
        'b_address_2'        => [
            'type'        => Type::string(),
            'description' => 'Billing: Address, 2nd line',
        ],
        'b_city'             => [
            'type'        => Type::string(),
            'description' => 'Billing: City',
        ],
        'b_state'            => [
            'type'        => Type::string(),
            'description' => 'Billing: State code',
        ],
        'b_state_descr'      => [
            'type'        => Type::string(),
            'description' => 'Billing: State',
        ],
        'b_country'          => [
            'type'        => Type::string(),
            'description' => 'Billing: Country code',
        ],
        'b_country_descr'    => [
            'type'        => Type::string(),
            'description' => 'Billing: Country',
        ],
        'b_zipcode'          => [
            'type'        => Type::string(),
            'description' => 'Billing: Zipcode',
        ],
        'b_phone'            => [
            'type'        => Type::string(),
            'description' => 'Billing: Phone',
        ],
        // shipping
        's_firstname'        => [
            'type'        => Type::string(),
            'description' => 'Shipping: Customer firstname',
        ],
        's_lastname'         => [
            'type'        => Type::string(),
            'description' => 'Shipping: Customer lastname',
        ],
        's_address'          => [
            'type'        => Type::string(),
            'description' => 'Shipping: Address, 1st line',
        ],
        's_address_2'        => [
            'type'        => Type::string(),
            'description' => 'Shipping: Address, 2nd line',
        ],
        's_city'             => [
            'type'        => Type::string(),
            'description' => 'Shipping: City',
        ],
        's_state'            => [
            'type'        => Type::string(),
            'description' => 'Shipping: State code',
        ],
        's_state_descr'      => [
            'type'        => Type::string(),
            'description' => 'Shipping: State',
        ],
        's_country'          => [
            'type'        => Type::string(),
            'description' => 'Shipping: Country code',
        ],
        's_country_descr'    => [
            'type'        => Type::string(),
            'description' => 'Shipping: Country',
        ],
        's_zipcode'          => [
            'type'        => Type::string(),
            'description' => 'Shipping: Zipcode',
        ],
        's_phone'            => [
            'type'        => Type::string(),
            'description' => 'Shipping: Phone',
        ],
        // products
        'products'           => [
            'type'        => Type::listOf(Type::resolveType('product')),
            'description' => 'Ordered products',
            'resolve'     => function ($source, $args, Context $context, ResolveInfo $resolveInfo) {
                if (empty($source['products'])) {
                    return [];
                }

                $order_products = Registry::ifGet('runtime.api.__order_products', []);
                $order_products = fn_array_merge($order_products, (array) $source['products'], true);

                Registry::set('runtime.api.__order_products', $order_products);

                return new Deferred(function () use ($source, $context) {
                    $order_products = Registry::ifGet('runtime.api.order_products', null);

                    $result = [];
                    if ($order_products === null) {
                        $order_products = Registry::ifGet('runtime.api.__order_products', []);

                        fn_gather_additional_products_data($order_products, [
                            'get_icon'            => true,
                            'get_detailed'        => true,
                            'get_additional'      => true,
                            'get_features'        => true,
                            'get_taxed_prices'    => true,
                            'features_display_on' => 'A',
                        ], $context->getLanguageCode());

                        Registry::set('runtime.api.order_product', $order_products);
                    }

                    foreach ($source['products'] as $cart_id => $source_product) {
                        $product = isset($order_products[$cart_id])
                            ? $order_products[$cart_id]
                            : $source_product;

                        if (!empty($source_product['extra']['custom_files'])) {
                            foreach ($source_product['extra']['custom_files'] as $option_id => $files) {
                                $product['product_options'][$option_id]['variant_name'] = [];
                                foreach ($files as $file) {
                                    $product['product_options'][$option_id]['variant_name'][] = fn_url(
                                        Url::buildUrn(['orders', 'get_custom_file'], [
                                            'order_id' => $source['order_id'],
                                            'file'     => $file['file'],
                                            'filename' => $file['name'],
                                        ]),
                                        $context->getUserType()
                                    );
                                }
                            }
                        }

                        $result[$cart_id] = $product;
                    }

                    return $result;
                });
            },
        ],
        'product_groups'     => [
            'type'        => Type::listOf(Type::resolveType('product_group')),
            'description' => 'Product groups',
            'resolve'     => function ($source, $args, Context $context, ResolveInfo $resolve_info) {
                if (empty($source['product_groups'])) {
                    return [];
                }

                $product_groups = $source['product_groups'];
                foreach ($product_groups as $id => &$group) {
                    $group['group_id'] = $id;
                }
                unset($group);

                return $product_groups;
            },
        ],
        // shipping methods
        'shipping'           => [
            'type'        => Type::listOf(Type::resolveType('shipping_method')),
            'description' => 'Selected shipping methods',
        ],
        // payment method
        'payment_method'     => [
            'type'        => Type::resolveType('payment_method'),
            'description' => 'Selected payment method',
        ],
        'payment_info'       => [
            'type'        => Type::listOf(Type::resolveType('payment_info_field')),
            'description' => 'Payment info',
            'resolve'     => function ($source, $args, Context $context, ResolveInfo $resolve_info) {
                if (empty($source['payment_info'])) {
                    return [];
                }

                $fields = [];
                foreach ($source['payment_info'] as $key => $value) {
                    $fields[] = [
                        'id'    => $key,
                        'name'  => __($key, [], $context->getLanguageCode()),
                        'value' => is_scalar($value)
                            ? $value
                            : json_encode($value), // FIXME: Flatten payment info?
                    ];
                }

                return $fields;
            },
        ],
        // taxes
        'taxes'              => [
            'type'        => Type::listOf('tax'),
            'description' => 'Applied taxes',
        ],
    ],
];

return $schema;
