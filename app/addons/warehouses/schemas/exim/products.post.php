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

use Tygh\Addons\AdvancedImport\Readers\Xml;
use Tygh\Enum\Addons\AdvancedImport\ImportStrategies;
use Tygh\Registry;
use Tygh\Enum\YesNo;

defined('BOOTSTRAP') or die('Access denied');

require_once(__DIR__ . '/products.functions.php');
/** @var array<string, array> $schema */

foreach (fn_warehouses_exim_get_list() as $warehouse_name => $warehouse) {
    $field = sprintf("%s (Warehouse)", $warehouse['name']);

    $schema['export_fields'][$field] = [
        'process_get' => ['fn_warehouses_exim_get_amount', '#key', $warehouse['store_location_id']],
        'export_only' => true,
        'linked'      => false,
        'warehouse_id'  => $warehouse['store_location_id']
    ];
}

$schema['export_fields']['Quantity'] = [
    'db_field' => 'amount',
    'process_get' => ['fn_warehouses_exim_get_quantity', '#key'],
    'linked'      => false,
];

$schema['pre_processing']['reset_warehouses_inventory'] = [
    'function'    => 'fn_warehouses_exim_reset_inventory',
    'args'        => ['$import_data', '@reset_inventory'],
    'import_only' => true,
];

$schema['options']['reset_inventory']['description'] = 'warehouses.exim_reset_inventory_tooltip';

if (isset($schema['export_fields']['Product availability'])) {
    $product_availability = &$schema['export_fields']['Product availability'];
    $product_availability['table_fields']['availability_amount'] =
        db_quote(
            '(CASE products.is_stock_split_by_warehouses WHEN ?s'
            . ' THEN warehouses_sum_products_amount.amount'
            . ' ELSE products.amount END)',
            YesNo::YES
        );

    $product_availability['references']['warehouses_sum_products_amount'] = [
        'reference_fields' => [
            'product_id'    => '&product_id'
        ],
        'join_type' => 'LEFT',
        'use_storefront_condition' => [
            'default' => 0
        ]
    ];
}

return $schema;
