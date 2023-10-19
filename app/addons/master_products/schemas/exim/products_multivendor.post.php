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

use Tygh\Enum\Addons\MasterProducts\EximProducts;
use Tygh\Registry;

/** @var array $schema */

require_once Registry::get('config.dir.addons') . 'master_products/schemas/exim/products.functions.php';

$runtime_company_id = Registry::get('runtime.company_id');

$sections = [
    'options',
    'pre_export_process',
    'import_get_primary_object_id',
    'import_after_process_data',
    'import_process_data',
];

foreach ($sections as $section) {
    if (!isset($schema[$section])) {
        $schema[$section] = [];
    }
}

// add aliases for importable aggregate fields
if (isset($schema['export_fields']['Items in box'])) {
    $schema['export_fields']['Items in box']['field_aliases'] = ['min_items_in_box', 'max_items_in_box'];
}

if (isset($schema['export_fields']['Box size'])) {
    $schema['export_fields']['Box size']['field_aliases'] = ['box_height', 'box_length', 'box_width'];
}

// add exported product type option
$schema['options']['master_products.exported_products'] = [
    'title'       => 'master_products.exported_products',
    'type'        => 'select',
    'export_only' => true,
    'position'    => 100,
    'variants'    => [
        EximProducts::PRODUCTS_BEING_SOLD            => 'master_products.products_being_sold',
        EximProducts::PRODUCTS_THAT_VENDORS_CAN_SELL => 'master_products.products_that_vendors_can_sell',
        EximProducts::PRODUCTS_ALL                   => 'master_products.all_products',
    ],
];

// filter products before export (common products, vendor products, all products)
$schema['pre_export_process']['master_products_exim_filter_products_by_company'] = [
    'function'    => 'fn_master_products_exim_filter_products_by_company',
    'args'        => ['$pattern', '$options', '$conditions', $runtime_company_id],
    'export_only' => true,
];

// actualize master product price when importing a vendor product
$schema['post_processing']['master_products_actualize_master_products_prices'] = [
    'function'    => 'fn_master_products_exim_actualize_master_products_prices',
    'args'        => ['$primary_object_ids'],
    'import_only' => true,
];

$schema['post_processing']['master_products_sync_vendor_products'] = [
    'function'    => 'fn_master_products_exim_sync_vendor_products',
    'args'        => ['$primary_object_ids'],
    'import_only' => true,
];

// adds company_id field to retrieve from DB
$schema['pre_export_process']['master_products'] = [
    'function' => 'fn_master_products_exim_pre_export_process',
    'args'     => ['$table_fields'],
];

// add company ID condition to the product ID detection
$schema['import_get_primary_object_id']['master_products_exim_set_company_id'] = [
    'function'    => 'fn_master_products_exim_set_company_id',
    'args'        => ['$alt_keys', '$skip_get_primary_object_id', $runtime_company_id],
    'import_only' => true,
];


if (!$runtime_company_id) {
    // disable vendor requirement
    if (isset($schema['export_fields']['Vendor'])) {
        $schema['export_fields']['Vendor']['process_get'] = ['fn_master_products_exim_get_product_vendor', '#this', '#row'];
        $schema['export_fields']['Vendor']['process_put'] = ['fn_master_products_exim_set_product_vendor', '#row', '#key', '#this', '#counter'];
    }

    // update vendor products' categories after master product is updated
    $schema['import_after_process_data']['master_products_exim_update_vendor_products_descriptions'] = [
        'function'    => 'fn_master_products_exim_update_vendor_products_descriptions',
        'args'        => ['$primary_object_id'],
        'import_only' => true,
    ];
} else {

    // forbid creating products via import when setting is disabled
    if (Registry::ifGet('addons.master_products.allow_vendors_to_create_products', 'N') === 'N') {
        $schema['import_process_data']['master_products_exim_skip_product_creation'] = [
            'function'    => 'fn_master_products_exim_skip_product_creation',
            'args'        => ['$primary_object_id', '$object', '$skip_record', '$processed_data'],
            'import_only' => true,
        ];
    }

    // start selling master product when importing products with matching properties
    $schema['import_process_data']['master_products_exim_sell_master_product'] = [
        'function'    => 'fn_master_products_exim_sell_master_product',
        'args'        => ['$primary_object_id', '$object', '$processed_data', '$skip_record', $runtime_company_id, '$options'],
        'import_only' => true,
    ];
}

return $schema;
