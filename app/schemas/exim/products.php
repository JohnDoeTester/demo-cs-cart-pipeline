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

use Tygh\Registry;
use Tygh\Settings;

include_once(Registry::get('config.dir.schemas') . 'exim/products.functions.php');
include_once(Registry::get('config.dir.schemas') . 'exim/features.functions.php');

$default_images_path = 'exim/backup/images/';
$default_files_path = 'exim/backup/downloads/';
if (fn_allowed_for('ULTIMATE') && Registry::get('runtime.simple_ultimate')) {
    $default_images_path = Registry::get('runtime.forced_company_id') . '/' . $default_images_path;
    $default_files_path = Registry::get('runtime.forced_company_id') . '/' . $default_files_path;
}

$schema = [
    'section'     => 'products',
    'name'        => __('products'),
    'pattern_id'  => 'products',
    'key'         => ['product_id'],
    'order'       => 0,
    'table'       => 'products',
    'permissions' => [
        'import' => 'manage_catalog',
        'export' => 'view_catalog',
    ],
    'references' => [
        'product_descriptions' => [
            'reference_fields' => ['product_id' => '#key', 'lang_code' => '#lang_code'],
            'join_type'        => 'LEFT'
        ],
        'product_prices' => [
            'reference_fields' => ['product_id' => '#key', 'lower_limit' => 1, 'usergroup_id' => 0],
            'join_type'        => 'LEFT'
        ],
        'images_links' => [
            'reference_fields'          => ['object_id' => '#key', 'object_type' => 'product', 'type' => 'M'],
            'join_type'                 => 'LEFT',
            'import_skip_db_processing' => true
        ],
        'companies' => [
            'reference_fields'          => ['company_id' => '&company_id'],
            'join_type'                 => 'LEFT',
            'import_skip_db_processing' => true
        ],
        'product_popularity' => [
            'reference_fields' => ['product_id' => '#key'],
            'join_type'        => 'LEFT'
        ],
    ],
    'condition' => [
        'use_company_condition' => true,
    ],
    'pre_processing' => [
        'reset_inventory' => [
            'function' => 'fn_exim_reset_inventory',
            'args'     => ['@reset_inventory'],
        ],
        'check_product_code' => [
            'function'    => 'fn_check_product_code',
            'args'        => ['$import_data'],
            'import_only' => true,
        ],
        'set_updated_timestamp' => [
            'function'    => 'fn_exim_set_product_updated_timestamp',
            'args'        => ['$import_data'],
            'import_only' => true,
        ],
        'prepare_default_categories' => [
            'function'    => 'fn_import_prepare_default_categories',
            'args'        => ['$processed_data'],
            'import_only' => true,
        ],
    ],
    'post_processing' => [
        'send_product_notifications' => [
            'function'    => 'fn_exim_send_product_notifications',
            'args'        => ['$primary_object_ids', '$import_data', '$processed_data'],
            'import_only' => true,
        ],
    ],
    'import_get_primary_object_id' => [
        'fill_products_alt_keys' => [
            'function'    => 'fn_import_fill_products_alt_keys',
            'args'        => ['$pattern', '$alt_keys', '$object', '$skip_get_primary_object_id'],
            'import_only' => true,
        ],
    ],
    'import_process_data' => [
        'unset_product_id' => [
            'function'    => 'fn_import_unset_product_id',
            'args'        => ['$object'],
            'import_only' => true,
        ],
        'sanitize_product_data' => [
            'function'    => '\Tygh\Tools\SecurityHelper::sanitizeObjectData',
            'args'        => ['product', '$object'],
            'import_only' => true,
        ],
        'skip_new_products' => [
            'function'    => 'fn_import_skip_new_products',
            'args'        => ['$primary_object_id', '$object', '$pattern', '$options', '$processed_data', '$processing_groups', '$skip_record'],
            'import_only' => true,
        ],
        'prepare_overridable_fields' => [
            'function'    => 'fn_import_prepare_product_overridable_fields',
            'args'        => ['$object'],
            'import_only' => true,
        ]
    ],
    'range_options' => [
        'selector_url' => 'products.manage',
        'object_name'  => __('products'),
    ],
    'notes' => [
        'text_exim_import_options_note',
        'text_exim_import_features_note',
        'text_exim_import_images_note',
        'text_exim_import_files_note',
    ],
    'options' => [
        'lang_code' => [
            'title'         => 'language',
            'type'          => 'languages',
            'default_value' => [DEFAULT_LANGUAGE],
            'position'      => 100,
        ],
        'skip_creating_new_products' => [
            'title'       => 'update_existing_products_only',
            'description' => 'update_existing_products_only_tooltip',
            'type'        => 'checkbox',
            'import_only' => true,
            'position'    => 200,
        ],
        'images_path' => [
            'title'         => 'images_directory',
            'description'   => 'text_images_directory',
            'type'          => 'input',
            'default_value' => $default_images_path,
            'notes'         => __('text_file_editor_notice', ['[href]' => fn_url('file_editor.manage?path=/')]),
            'position'      => 300,
        ],
        'price_dec_sign_delimiter' => [
            'title'         => 'price_dec_sign_delimiter',
            'description'   => 'text_price_dec_sign_delimiter',
            'type'          => 'input',
            'default_value' => '.',
            'position'      => 400,
        ],
        'category_delimiter' => [
            'title'         => 'category_delimiter',
            'description'   => 'text_category_delimiter',
            'type'          => 'input',
            'default_value' => '///',
            'position'      => 500,
        ],
        'features_delimiter' => [
            'title'         => 'features_delimiter',
            'description'   => 'text_features_delimiter',
            'type'          => 'input',
            'default_value' => '///',
            'position'      => 600,
        ],
        'files_path' => [
            'title'         => 'downloadable_product_files_directory',
            'description'   => 'text_files_directory',
            'type'          => 'input',
            'default_value' => $default_files_path,
            'notes'         => __('text_file_editor_notice', ['[href]' => fn_url('file_editor.manage?path=/')]),
            'position'      => 700,
        ],
        'reset_inventory' => [
            'title'       => 'reset_quantity_to_zero',
            'description' => 'exim_reset_inventory_tooltip',
            'type'        => 'checkbox',
            'import_only' => true,
            'position'    => 800,
        ],
        'delete_files' => [
            'title'       => 'delete_downloadable_product_files',
            'description' => 'delete_downloadable_product_files_tooltip',
            'type'        => 'checkbox',
            'import_only' => true,
            'position'    => 900,
        ],
    ],
    'export_fields' => [
        'Product code' => [
            'db_field'  => 'product_code',
            'alt_key'   => true,
            'required'  => true,
            'alt_field' => 'product_id'
        ],
        'Language' => [
            'table'     => 'product_descriptions',
            'db_field'  => 'lang_code',
            'type'      => 'languages',
            'required'  => true,
            'multilang' => true
        ],
        'Product id' => [
            'db_field' => 'product_id'
        ],
        'Category' => [
            'process_get' => ['fn_exim_get_product_categories', '#key', 'M', '@category_delimiter', '#lang_code'],
            'process_put' => ['fn_exim_set_product_categories', '#key', 'M', '#this', '@category_delimiter', '%Store%', '#counter', '#new'],
            'multilang'   => true,
            'linked'      => false, // this field is not linked during import-export
            'default'     => ''
        ],
        'Secondary categories' => [
            'process_get' => ['fn_exim_get_product_categories', '#key', 'A', '@category_delimiter', '#lang_code'],
            'process_put' => ['fn_exim_set_product_categories', '#key', 'A', '#this', '@category_delimiter', '%Store%', '#counter', '#new'],
            'multilang'   => true,
            'linked'      => false, // this field is not linked during import-export
        ],
        'List price' => [
            'db_field'    => 'list_price',
            'convert_put' => ['fn_exim_import_price', '#this', '@price_dec_sign_delimiter'],
            'process_get' => ['fn_exim_export_price', '#this', '@price_dec_sign_delimiter'],
        ],
        'Price' => [
            'table'       => 'product_prices',
            'db_field'    => 'price',
            'convert_put' => ['fn_exim_import_price', '#this', '@price_dec_sign_delimiter'],
            'process_put' => ['fn_import_product_price', '#key', '#this', '#new'],
            'process_get' => ['fn_exim_export_price', '#this', '@price_dec_sign_delimiter'],
        ],
        'Status' => [
            'db_field' => 'status'
        ],
        'Popularity' => [
            'table'    => 'product_popularity',
            'db_field' => 'total'
        ],
        'Quantity' => [
            'db_field' => 'amount'
        ],
        'Weight' => [
            'db_field' => 'weight'
        ],
        'Min quantity' => [
            'db_field' => 'min_qty',
        ],
        'Max quantity' => [
            'db_field' => 'max_qty',
        ],
        'Quantity step' => [
            'db_field' => 'qty_step',
        ],
        'List qty count' => [
            'db_field' => 'list_qty_count',
        ],
        'Shipping freight' => [
            'db_field'    => 'shipping_freight',
            'convert_put' => ['fn_exim_import_price', '#this', '@price_dec_sign_delimiter'],
            'process_get' => ['fn_exim_export_price', '#this', '@price_dec_sign_delimiter'],
        ],
        'Date added' => [
            'db_field'      => 'timestamp',
            'process_get'   => ['fn_timestamp_to_date', '#this'],
            'convert_put'   => ['fn_date_to_timestamp', '#this'],
            'return_result' => true,
            'default'       => ['time']
        ],
        'Downloadable' => [
            'db_field' => 'is_edp',
        ],
        'Files' => [
            'process_get' => ['fn_exim_export_file', '#key', '@files_path'],
            'process_put' => ['fn_exim_import_file', '#key', '#this', '@files_path', '@delete_files'],
            'linked'      => false, // this field is not linked during import-export
        ],
        'Ship downloadable' => [
            'db_field' => 'edp_shipping',
        ],
        'Inventory tracking' => [
            'db_field' => 'tracking',
        ],
        'Out of stock actions' => [
            'db_field' => 'out_of_stock_actions',
        ],
        'Free shipping' => [
            'db_field' => 'free_shipping',
        ],
        'Zero price action' => [
            'db_field' => 'zero_price_action',
        ],
        'Thumbnail' => [
            'table'        => 'images_links',
            'db_field'     => 'image_id',
            'use_put_from' => '%Detailed image%',
            'process_get'  => ['fn_exim_export_image', '#this', 'product', '@images_path']
        ],
        'Detailed image' => [
            'db_field'    => 'detailed_id',
            'table'       => 'images_links',
            'process_get' => ['fn_exim_export_image', '#this', 'detailed', '@images_path'],
            'process_put' => ['fn_exim_import_images', '@images_path', '%Thumbnail%', '#this', '0', 'M', '#key', 'product']
        ],
        'Product name' => [
            'table'       => 'product_descriptions',
            'db_field'    => 'product',
            'multilang'   => true,
            'process_get' => ['fn_export_product_descr', '#key', '#this', '#lang_code', 'product'],
            'process_put' => ['fn_import_product_descr', '#this', '#key', 'product', '#new'],
        ],
        'Description' => [
            'table'       => 'product_descriptions',
            'db_field'    => 'full_description',
            'multilang'   => true,
            'process_get' => ['fn_export_product_descr', '#key', '#this', '#lang_code', 'full_description'],
            'process_put' => ['fn_import_product_descr', '#this', '#key', 'full_description', '#new'],
        ],
        'Short description' => [
            'table'       => 'product_descriptions',
            'db_field'    => 'short_description',
            'multilang'   => true,
            'process_get' => ['fn_export_product_descr', '#key', '#this', '#lang_code', 'short_description'],
            'process_put' => ['fn_import_product_descr', '#this', '#key', 'short_description', '#new'],
        ],
        'Meta keywords' => [
            'table'       => 'product_descriptions',
            'db_field'    => 'meta_keywords',
            'multilang'   => true,
            'process_get' => ['fn_export_product_descr', '#key', '#this', '#lang_code', 'meta_keywords'],
            'process_put' => ['fn_import_product_descr', '#this', '#key', 'meta_keywords', '#new'],
        ],
        'Meta description' => [
            'table'       => 'product_descriptions',
            'db_field'    => 'meta_description',
            'multilang'   => true,
            'process_get' => ['fn_export_product_descr', '#key', '#this', '#lang_code', 'meta_description'],
            'process_put' => ['fn_import_product_descr', '#this', '#key', 'meta_description', '#new'],
        ],
        'Search words' => [
            'table'       => 'product_descriptions',
            'db_field'    => 'search_words',
            'multilang'   => true,
            'process_get' => ['fn_export_product_descr', '#key', '#this', '#lang_code', 'search_words'],
            'process_put' => ['fn_import_product_descr', '#this', '#key', 'search_words', '#new'],
        ],
        'Page title' => [
            'table'       => 'product_descriptions',
            'db_field'    => 'page_title',
            'multilang'   => true,
            'process_get' => ['fn_export_product_descr', '#key', '#this', '#lang_code', 'page_title'],
            'process_put' => ['fn_import_product_descr', '#this', '#key', 'page_title', '#new'],
        ],
        'Promo text' => [
            'table'       => 'product_descriptions',
            'db_field'    => 'promo_text',
            'multilang'   => true,
            'process_get' => ['fn_export_product_descr', '#key', '#this', '#lang_code', 'promo_text'],
            'process_put' => ['fn_import_product_descr', '#this', '#key', 'promo_text', '#new']
        ],
        'Taxes' => [
            'db_field'      => 'tax_ids',
            'process_get'   => ['fn_exim_get_taxes', '#this', '#lang_code'],
            'process_put'   => ['fn_exim_set_taxes', '#key', '#this'],
            'multilang'     => true,
            'return_result' => true
        ],
        'Features' => [
            'process_get' => ['fn_exim_get_product_features', '#key', '@features_delimiter', '#lang_code'],
            'process_put' => ['fn_exim_set_product_features', '#key', '#this', '@features_delimiter', '#lang_code'],
            'linked'      => false, // this field is not linked during import-export
            'multilang'   => true,
        ],
        'Options' => [
            'process_get' => ['fn_exim_get_product_options', '#key', '#lang_code', '@features_delimiter'],
            'process_put' => ['fn_exim_set_product_options', '#key', '#this', '#lang_code', '@features_delimiter'],
            'linked'      => false, // this field is not linked during import-export
            'multilang'   => true,
        ],
        'Product URL' => [
            'process_get' => ['fn_exim_get_product_url', '#key', '#lang_code'],
            'multilang'   => true,
            'linked'      => false,
            'export_only' => true,
        ],
        'Image URL' => [
            'process_get' => ['fn_exim_get_image_url', '#key', 'product', 'M', true, false, '#lang_code'],
            'multilang'   => true,
            'db_field'    => 'image_id',
            'table'       => 'images_links',
            'export_only' => true,
        ],
        'Images' => [
            'process_get' => ['fn_exim_get_all_images_url', '#key', '#lang_code'],
            'multilang'   => true,
            'db_field'    => 'image_id',
            'table'       => 'images_links',
            'export_only' => true,
        ],
        'Detailed image URL' => [
            'process_get' => ['fn_exim_get_detailed_image_url', '#key', 'product', 'M', '#lang_code'],
            'db_field'    => 'detailed_id',
            'table'       => 'images_links',
            'export_only' => true,
        ],
        'Items in box' => [
            'process_get' => ['fn_exim_get_items_in_box', '#key'],
            'process_put' => ['fn_exim_put_items_in_box', '#key', '#this'],
            'linked'      => false, // this field is not linked during import-export
        ],
        'Box size' => [
            'process_get' => ['fn_exim_get_box_size', '#key'],
            'process_put' => ['fn_exim_put_box_size', '#key', '#this'],
            'linked'      => false, // this field is not linked during import-export
        ],
        'Usergroup IDs' => [
            'db_field' => 'usergroup_ids'
        ],
        'Available since' => [
            'db_field'      => 'avail_since',
            'process_get'   => ['fn_exim_get_optional_timestamp', '#this'],
            'convert_put'   => ['fn_exim_put_optional_timestamp', '#this'],
            'return_result' => true
        ],
        'Product availability' => [
            'process_get'  => ['fn_exim_get_product_availability', '#row', '@export_type'],
            'table_fields' => [
                'out_of_stock_action' => 'products.out_of_stock_actions',
                'availability_amount' => 'products.amount',
                'min_quantity'        => 'products.min_qty',
                'tracking'            => 'products.tracking',
            ],
            'linked'       => false,
            'export_only'  => true,
        ],
        'Options type' => [
            'db_field' => 'options_type',
        ],
        'Exceptions type' => [
            'db_field' => 'exceptions_type',
        ],
    ],
];

if (Registry::get('config.tweaks.disable_localizations') === false) {
    $schema['export_fields']['Localizations'] = [
        'db_field'      => 'localization',
        'process_get'   => ['fn_exim_get_localizations', '#this', '#lang_code'],
        'process_put'   => ['fn_exim_set_localizations', '#key', '#this'],
        'return_result' => true,
        'multilang'     => true,
    ];
}

$company_schema = [
    'table'       => 'companies',
    'db_field'    => 'company',
    'process_put' => ['fn_exim_set_product_company', '#row', '#key', '#this', '#counter']
];

$schema['references']['product_descriptions']['import_skip_db_processing'] = true;

if (fn_allowed_for('ULTIMATE')) {
    $schema['pre_processing']['prepare_shared_products'] = [
        'function'    => 'fn_ult_import_prepare_products_shared_for_current_storefront',
        'args'        => ['$import_data'],
        'import_only' => true,
    ];

    $schema['references']['product_prices']['import_skip_db_processing'] = true;

    $schema['export_fields']['Store'] = $company_schema;

    $schema['export_fields']['Price']['process_put'] = ['fn_import_product_price', '#key', '#this', '#new', '#row', '%Store%'];

    $schema['export_fields']['Product name']['process_put'] = ['fn_import_product_descr', '#this', '#key', 'product', '#new', '#row'];
    $schema['export_fields']['Description']['process_put'] = ['fn_import_product_descr', '#this', '#key', 'full_description', '#new', '#row'];
    $schema['export_fields']['Short description']['process_put'] = ['fn_import_product_descr', '#this', '#key', 'short_description', '#new', '#row'];
    $schema['export_fields']['Meta keywords']['process_put'] = ['fn_import_product_descr', '#this', '#key', 'meta_keywords', '#new', '#row'];
    $schema['export_fields']['Meta description']['process_put'] = ['fn_import_product_descr', '#this', '#key', 'meta_description', '#new', '#row'];
    $schema['export_fields']['Search words']['process_put'] = ['fn_import_product_descr', '#this', '#key', 'search_words', '#new', '#row'];
    $schema['export_fields']['Page title']['process_put'] = ['fn_import_product_descr', '#this', '#key', 'page_title', '#new', '#row'];
    $schema['export_fields']['Promo text']['process_put'] = ['fn_import_product_descr', '#this', '#key', 'promo_text', '#new', '#row'];

    $schema['export_fields']['Product code']['is_allowed_for_shared_product'] = true;
    $schema['export_fields']['Language']['is_allowed_for_shared_product'] = true;
    $schema['export_fields']['Product id']['is_allowed_for_shared_product'] = true;
    $schema['export_fields']['Category']['is_allowed_for_shared_product'] = true;
    $schema['export_fields']['Secondary categories']['is_allowed_for_shared_product'] = true;
    $schema['export_fields']['Price']['is_allowed_for_shared_product'] = true;
    $schema['export_fields']['Product name']['is_allowed_for_shared_product'] = true;
    $schema['export_fields']['Description']['is_allowed_for_shared_product'] = true;
    $schema['export_fields']['Short description']['is_allowed_for_shared_product'] = true;
    $schema['export_fields']['Meta keywords']['is_allowed_for_shared_product'] = true;
    $schema['export_fields']['Meta description']['is_allowed_for_shared_product'] = true;
    $schema['export_fields']['Search words']['is_allowed_for_shared_product'] = true;
    $schema['export_fields']['Page title']['is_allowed_for_shared_product'] = true;
    $schema['export_fields']['Promo text']['is_allowed_for_shared_product'] = true;

    if (!Registry::get('runtime.company_id')) {
        $schema['export_fields']['Store']['required'] = true;
        $schema['export_fields']['Category']['process_put'] = [
            'fn_exim_set_product_categories',
            '#key',
            'M',
            '#this',
            '@category_delimiter',
            '%Store%',
            '#counter',
            '#new',
            '#row'
        ];
        $schema['export_fields']['Features']['process_put'] = [
            'fn_exim_set_product_features',
            '#key',
            '#this',
            '@features_delimiter',
            '#lang_code',
            '%Store%'
        ];
        $schema['export_fields']['Secondary categories']['process_put'] = [
            'fn_exim_set_product_categories',
            '#key',
            'A',
            '#this',
            '@category_delimiter',
            '%Store%',
            '#counter',
            '#new',
            '#row'
        ];
    } else {
        $schema['export_fields']['Category']['process_put'] = [
            'fn_exim_set_product_categories',
            '#key',
            'M',
            '#this',
            '@category_delimiter',
            '%Store%',
            '#counter',
            '#new',
            '#row'
        ];

        $schema['export_fields']['Secondary categories']['process_put'] = [
            'fn_exim_set_product_categories',
            '#key',
            'A',
            '#this',
            '@category_delimiter',
            '%Store%',
            '#counter',
            '#new',
            '#row'
        ];
    }

    $schema['import_process_data']['ult_import_filter_fields_for_shared_products'] = [
        'function'    => 'fn_ult_import_filter_fields_for_shared_products',
        'args'        => ['$object', '$pattern'],
        'import_only' => true,
    ];

    $schema['import_process_data']['ult_import_check_product_data'] = [
        'function'    => 'fn_ult_import_check_product_data',
        'args'        => ['$primary_object_id', '$object'],
        'import_only' => true,
    ];

    $schema['import_process_data']['check_product_company_id'] = [
        'function'    => 'fn_import_check_product_company_id',
        'args'        => ['$primary_object_id', '$object', '$pattern', '$options', '$processed_data', '$processing_groups', '$skip_record'],
        'import_only' => true,
    ];
}
if (fn_allowed_for('MULTIVENDOR')) {
    $schema['export_fields']['Vendor'] = $company_schema;

    $schema['import_process_data']['mve_import_check_product_data'] = [
        'function'    => 'fn_mve_import_check_product_data',
        'args'        => ['$object', '$primary_object_id','$options', '$processed_data', '$skip_record'],
        'import_only' => true,
    ];

    if (!Registry::get('runtime.company_id')) {
        $schema['export_fields']['Vendor']['required'] = true;

    } else {
        $schema['import_process_data']['mve_import_check_object_id'] = [
            'function'    => 'fn_mve_import_check_object_id',
            'args'        => ['$primary_object_id', '$processed_data', '$skip_record'],
            'import_only' => true,
        ];

        $schema['references']['product_popularity']['import_skip_db_processing'] = true;
    }
}

$overridable_product_fields_schema = fn_get_product_overridable_fields_schema();

foreach ($schema['export_fields'] as $key => $export_field) {
    if (empty($export_field['db_field']) || !isset($overridable_product_fields_schema[$export_field['db_field']])) {
        continue;
    }

    $overridable_product_field_schema = $overridable_product_fields_schema[$export_field['db_field']];

    $global_value = Settings::getSettingValue($overridable_product_field_schema['global_setting']);

    if ($global_value === null) {
        continue;
    }

    unset($schema['export_fields'][$key]);
}

return $schema;
