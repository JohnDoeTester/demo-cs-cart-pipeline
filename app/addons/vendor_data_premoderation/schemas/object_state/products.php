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

use Tygh\Addons\VendorDataPremoderation\StateFactory;

defined('BOOTSTRAP') or die('Access denied');

/**
 * This schema describes sources of product data.
 * Each table with the `product_id` column is automatically included as the data source.
 *
 * To include tables that contain product data, but don't have the `product_id` column, specify these tables and
 * data selection conditions in the `include_tables` array.
 * This array has the following structure:
 * 'include_tables' => [
 *     'table_name' => [
 *         'field_that_stores_product_id' => '$id',
 *         'field_for_condition_1' => 'value_1',
 *         ...
 *         'field_for_condition_N' => 'value_N',
 *     ],
 *     ...
 * ],
 *
 * Use `$id` as the product ID placeholder.
 * Table names are specified without the table prefix.
 *
 * To exclude tables that don't contain product data, but have the `product_id` column, specifiy these table
 * in the `exclude_tables` array.
 * This array has the following structure:
 * 'exclude_tables' => [
 *     'table_name',
 *     ...
 * ],
 * Table names are specified without the table prefix.
 */
$schema = [
    'include_tables' => [
        'images_links' => [
            'object_id'   => StateFactory::OBJECT_ID_PLACEHOLDER,
            'object_type' => 'product',
        ],
        'product_files' => [
            'product_id'                => StateFactory::OBJECT_ID_PLACEHOLDER,
            'lang_code'                 => DESCR_SL,
            'product_file_descriptions' => [
                'product_files.file_id' => 'product_file_descriptions.file_id',
            ],
        ],
        'product_file_folders' => [
            'product_id'                       => StateFactory::OBJECT_ID_PLACEHOLDER,
            'lang_code'                        => DESCR_SL,
            'product_file_folder_descriptions' => [
                'product_file_folders.folder_id' => 'product_file_folder_descriptions.folder_id',
            ],
        ],
    ],
    'exclude_tables' => [
        'user_session_products',
        'product_subscriptions',
        'premoderation_products',
        'order_details',
        'shipment_items',
        'product_file_ekeys',
        'product_popularity',
    ],
];

return $schema;
