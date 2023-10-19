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

include_once(Registry::get('config.dir.schemas') . 'exim/qty_discounts.functions.php');
include_once(Registry::get('config.dir.schemas') . 'exim/products.functions.php');

$schema = [
    'section'       => 'products',
    'name'          => __('qty_discounts'),
    'pattern_id'    => 'qty_discounts',
    'key'           => ['product_id'],
    'order'         => 3,
    'table'         => 'products',
    'permissions'   => [
        'import' => 'manage_catalog',
        'export' => 'view_catalog',
    ],
    'update_only'   => true,
    'references'    => [
        'product_prices' => [
            'reference_fields' => ['product_id' => '#key'],
            'join_type'        => 'INNER',
            'alt_key'          => ['lower_limit', 'usergroup_id', '#key'],
        ],
    ],
    'condition'     => [
        'use_company_condition' => true,
    ],
    'range_options' => [
        'selector_url' => 'products.manage',
        'object_name'  => __('products'),
    ],
    'options'       => [
        'lang_code' => [
            'title'         => 'language',
            'type'          => 'languages',
            'default_value' => [DEFAULT_LANGUAGE],
        ],
        'price_dec_sign_delimiter' => [
            'title'         => 'price_dec_sign_delimiter',
            'description'   => 'text_price_dec_sign_delimiter',
            'type'          => 'input',
            'default_value' => '.',
        ],
    ],
    'export_fields' => [
        'Product code' => [
            'required' => true,
            'alt_key'  => true,
            'db_field' => 'product_code',
        ],
        'Language' => [
            'process_get' => ['', '#lang_code'],
            'type'        => 'languages',
            'linked'      => false,
            'required'    => true,
            'multilang'   => true,
        ],
        'Price' => [
            'table'       => 'product_prices',
            'db_field'    => 'price',
            'required'    => true,
            'convert_put' => ['fn_exim_import_price', '#this', '@price_dec_sign_delimiter'],
            'process_get' => ['fn_exim_export_price', '#this', '@price_dec_sign_delimiter'],
            'process_put' => ['fn_qty_update_prices', '#key', '#row'],
        ],
        'Percentage discount' => [
            'table'    => 'product_prices',
            'db_field' => 'percentage_discount',
            'default'  => '0',
        ],
        'Lower limit' => [
            'table'         => 'product_prices',
            'db_field'      => 'lower_limit',
            'key_component' => true,
            'required'      => true,
            'pre_insert'    => ['fn_exim_check_discount', '#row', '#lang_code'],
        ],
        'User group' => [
            'db_field'      => 'usergroup_id',
            'table'         => 'product_prices',
            'key_component' => true,
            'process_get'   => ['fn_exim_get_usergroup', '#this', '#lang_code'],
            'convert_put'   => ['fn_exim_put_usergroup', '#this', '#lang_code'],
            'return_result' => true,
            'required'      => true,
            'multilang'     => true,
        ]
    ],
];

if (fn_allowed_for('ULTIMATE')) {
    $schema['pre_processing']['prepare_shared_products'] = [
        'function'    => 'fn_ult_import_prepare_products_shared_for_current_storefront',
        'args'        => ['$import_data'],
        'import_only' => true,
    ];

    $schema['references']['product_prices']['import_skip_db_processing'] = true;

    $schema['import_get_primary_object_id'] = [
        'fill_primary_object_company_id' => [
            'function'    => 'fn_qty_apply_company',
            'args'        => ['$alt_keys', '$object'],
            'import_only' => true,
        ],
    ];
    $schema['import_process_data'] = [
        'check_product_company_id' => [
            'function'    => 'fn_import_check_product_company_id',
            'args'        => ['$primary_object_id', '$object', '$pattern', '$options', '$processed_data', '$processing_groups', '$skip_record'],
            'import_only' => true,
        ],
    ];
}

if (fn_allowed_for('MULTIVENDOR')) {
    if (Registry::get('runtime.company_id')) {
        $schema['import_process_data']['mve_import_check_object_id'] = [
            'function'    => 'fn_mve_import_check_object_id',
            'args'        => ['$primary_object_id', '$processed_data', '$skip_record'],
            'import_only' => true,
        ];
    }
}

return $schema;
