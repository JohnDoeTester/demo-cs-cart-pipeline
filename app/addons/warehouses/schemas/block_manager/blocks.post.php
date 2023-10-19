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

require_once(__DIR__ . '/blocks.functions.php');

/** @var array $schema */
$schema['main']['cache_overrides_by_dispatch']['products.view']['callable_handlers']['customer_destination_id'] = [
    /** @see \fn_warehouses_blocks_get_customer_destination_id */
    'fn_warehouses_blocks_get_customer_destination_id',
];

$schema['main']['cache_overrides_by_dispatch']['products.view']['update_handlers'][] = 'warehouses_products_amount';
$schema['main']['cache_overrides_by_dispatch']['products.view']['update_handlers'][] = 'warehouses_destination_products_amount';
$schema['main']['cache_overrides_by_dispatch']['products.view']['update_handlers'][] = 'store_locations';


$schema['main']['cache_overrides_by_dispatch']['categories.view']['update_handlers'][] = 'warehouses_destination_products_amount';
$schema['main']['cache_overrides_by_dispatch']['categories.view']['callable_handlers']['customer_destination_id'] = [
    /** @see \fn_warehouses_blocks_get_customer_destination_id */
    'fn_warehouses_blocks_get_customer_destination_id',
];
$schema['main']['depends_by_customer_location'] = [
    'products.view',
    'categories.view'
];

$schema['products']['cache']['update_handlers'][] = 'warehouses_destination_products_amount';
$schema['products']['cache']['callable_handlers']['customer_destination_id'] = [
    /** @see \fn_warehouses_blocks_get_customer_destination_id */
    'fn_warehouses_blocks_get_customer_destination_id',
];
$schema['products']['depends_by_customer_location'] = true;

$schema['availability_in_stores'] = [
    'show_on_locations' => ['product_tabs', 'products.view'],
    'templates'         => 'addons/warehouses/blocks/availability_in_stores.tpl',
    'content'           => [
        'items' => [
            'type'     => 'function',
            'function' => [
                /** @see \fn_warehouses_blocks_get_availability_in_stores */
                'fn_warehouses_blocks_get_availability_in_stores',
            ],
        ],
    ],
    'cache'             => [
        'request_handlers'  => ['product_id'],
        'update_handlers'   => [
            'products',
            'store_location_destination_links',
            'store_location_shipping_delays',
            'warehouses_products_amount',
            'warehouses_destination_products_amount',
            'store_locations',
        ],
        'callable_handlers' => [
            'customer_location_hash' => [
                /** @see \fn_warehouses_blocks_get_customer_destination_id */
                'fn_warehouses_blocks_get_customer_destination_id',
            ],
        ],
    ],
];

$schema['product_filters']['depends_by_customer_location'] = true;

$schema['product_filters']['cache']['update_handlers'][] = 'store_locations';
$schema['product_filters']['cache']['update_handlers'][] = 'store_location_destination_links';
$schema['product_filters']['cache']['update_handlers'][] = 'warehouses_destination_products_amount';

$schema['product_filters']['cache']['callable_handlers']['customer_destination_id'] = [
    /** @see \fn_warehouses_blocks_get_customer_destination_id */
    'fn_warehouses_blocks_get_customer_destination_id'
];

return $schema;
