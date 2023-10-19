<?php
/****************************************************************************
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

use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\YesNo;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

include_once(Registry::get('config.dir.addons') . 'product_reviews/schemas/exim/product_reviews.functions.php');

$default_images_path = 'exim/backup/images/';
if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.simple_ultimate')) {
    $default_images_path = Registry::get('runtime.forced_company_id') . '/' . $default_images_path;
}

$schema = [
    'section' => 'product_reviews',
    'pattern_id' => 'product_reviews',
    'name' => __('product_reviews.product_reviews'),
    'key' => ['product_review_id'],
    'order' => 0,
    'table' => 'product_reviews',
    'permissions' => [
        'import' => 'create_product_reviews',
        'export' => 'view_product_reviews',
    ],
    'references' => [
        'images_links' => [
            'reference_fields'          => ['object_id' => '#key', 'object_type' => 'product_reviews', 'type' => 'A'],
            'join_type'                 => 'LEFT',
            'import_skip_db_processing' => true,
        ],
    ],
    'post_processing' => [
        'actualize_prepared_data' => [
            'function'    => 'fn_actualize_prepared_data',
            'args'        => ['$primary_object_ids', '$import_data'],
            'import_only' => true,
        ],
    ],
    'options' => [
        'images_path' => [
            'title' => 'images_directory',
            'description' => 'text_images_directory',
            'type' => 'input',
            'default_value' => $default_images_path,
            'notes' => __('text_file_editor_notice', ['[href]' => fn_url('file_editor.manage?path=/')]),
        ],
        'image_delimiter' => [
            'title'         => 'images_delimiter',
            'description'   => 'text_images_delimiter',
            'type'          => 'input',
            'default_value' => '///',
            'position'      => 500,
        ],
    ],
    'import_get_primary_object_id' => [
        'skip_get_primary_object_id' => [
            'function'    => 'fn_import_skip_get_primary_object_id',
            'args'        => ['$alt_keys', '$skip_get_primary_object_id'],
            'import_only' => true,
        ],
    ],
    'import_process_data' => [
        'access_to_product' => [
            'function'    => 'fn_import_access_to_product',
            'args'        => ['$object', '$processed_data', '$skip_record'],
            'import_only' => true,
        ],
        'prepare_user_data' => [
            'function'    => 'fn_import_prepare_user_data',
            'args'        => ['$object', '$processed_data', '$skip_record'],
            'import_only' => true,
        ],
        'storefront_data' => [
            'function'    => 'fn_import_prepare_storefront',
            'args'        => ['$object', '$processed_data', '$skip_record'],
            'import_only' => true,
        ],
    ],
    'export_fields' => [
        'Product review ID' => [
            'db_field' => 'product_review_id',
            'alt_key' => true,
        ],
        'Product ID' => [
            'db_field' => 'product_id',
            'required' => true,
        ],
        'User ID' => [
            'db_field' => 'user_id',
            'default'  => '0',
        ],
        'User name' => [
            'db_field' => 'name',
        ],
        'Advantages' => [
            'db_field' => 'advantages',
            'process_get' => ['fn_product_reviews_line_break_to_br', '#this'],
            'convert_put' => ['fn_product_reviews_br_to_line_break', '#this'],
        ],
        'Disadvantages' => [
            'db_field' => 'disadvantages',
            'process_get' => ['fn_product_reviews_line_break_to_br', '#this'],
            'convert_put' => ['fn_product_reviews_br_to_line_break', '#this'],
        ],
        'Comment' => [
            'db_field' => 'comment',
            'required' => true,
            'process_get' => ['fn_product_reviews_line_break_to_br', '#this'],
            'convert_put' => ['fn_product_reviews_br_to_line_break', '#this'],
        ],
        'Rating value' => [
            'db_field' => 'rating_value',
            'required' => true,
            'convert_put' => ['fn_import_round_rating_value', '#this'],
        ],
        'Review timestamp' => [
            'db_field' => 'product_review_timestamp',
            'process_get' => ['fn_timestamp_to_date', '#this'],
            'convert_put' => ['fn_date_to_timestamp', '#this'],
            'default'     => ['time'],
        ],
        'IP address' => [
            'db_field' => 'ip_address',
            'process_get' => ['fn_ip_from_db', '#this'],
            'convert_put' => ['fn_ip_to_db', '#this'],
            'default'     => '0',
        ],
        'Is buyer' => [
            'db_field' => 'is_buyer',
            'default'  => YesNo::NO,
        ],
        'Country code' => [
            'db_field' => 'country_code',
        ],
        'City' => [
            'db_field' => 'city',
        ],
        'Reply user ID' => [
            'db_field' => 'reply_user_id',
            'default'  => '0',
        ],
        'Reply' => [
            'db_field' => 'reply',
            'process_get' => ['fn_product_reviews_line_break_to_br', '#this'],
            'convert_put' => ['fn_product_reviews_br_to_line_break', '#this'],
        ],
        'Reply timestamp' => [
            'db_field' => 'reply_timestamp',
            'process_get' => ['fn_timestamp_to_date', '#this'],
            'convert_put' => ['fn_date_to_timestamp', '#this'],
        ],
        'Status' => [
            'db_field' => 'status',
            'default'  => ObjectStatuses::DISABLED,
        ],
        'Images' => [
            'db_field'    => 'object_id',
            'table'       => 'images_links',
            'process_get' => ['fn_exim_get_product_review_images', '#key', 'product_reviews', '@image_delimiter', '@images_path'],
            'process_put' => ['fn_exim_put_product_review_images', '@image_delimiter', '@images_path', '%Thumbnail%', '#this', 'A', '#key', 'product_reviews']
        ],
    ],
    'pre_export_process' => [
        'fn_product_reviews_pre_export_process_merge_product_reviews' => [
            'function' => 'fn_product_reviews_pre_export_process_merge_product_reviews',
            'args'     => [
                '$joins',
            ],
        ],
    ],
];

if (
    fn_allowed_for('ULTIMATE')
    && !Registry::get('runtime.company_id')
) {
    $schema['export_fields']['Store'] = [
        'db_field' => 'storefront_id',
        'required' => true,
        'process_get' => ['fn_import_get_storefront_name', '#this'],
        'convert_put' => ['fn_import_get_storefront_id', '#this']
    ];
}

if (fn_allowed_for('MULTIVENDOR')) {
    $schema['export_fields']['Store'] = [
        'db_field' => 'storefront_id',
        'process_get' => ['fn_import_get_storefront_name', '#this'],
        'convert_put' => ['fn_import_get_storefront_id', '#this']
    ];
}

return $schema;
