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

include_once(Registry::get('config.dir.schemas') . 'exim/vendors.functions.php');

return [
    'section' => 'vendors',
    'pattern_id' => 'vendors',
    'name' => __('vendors'),
    'key' => ['company_id'],
    'order' => 0,
    'table' => 'companies',
    'permissions' => [
        'edition' => 'MULTIVENDOR',
        'import' => 'manage_vendors',
        'export' => 'view_vendors',
    ],
    'references' => [
        'company_descriptions' => [
            'reference_fields' => ['company_id' => '#key', 'lang_code' => '#lang_code'],
            'join_type' => 'LEFT'
        ]
    ],
    'options' => [
        'lang_code' => [
            'title' => 'language',
            'type' => 'languages',
            'default_value' => [DEFAULT_LANGUAGE],
        ],
        'images_path' => [
            'title' => 'images_directory',
            'description' => 'text_images_directory',
            'type' => 'input',
            'default_value' => 'exim/backup/images/',
            'notes' => __('text_file_editor_notice', ['[href]' => fn_url('file_editor.manage?path=/')]),
        ],
    ],
    'range_options' => [
        'selector_url' => 'companies.manage',
        'object_name' => __('vendors'),
    ],
    'import_process_data' => [
        'import_status' => [
            'function' => 'fn_exim_vendors_import_status',
            'args' => ['$object'],
            'import_only' => true,
        ],
        'import_shippings' => [
            'function' => 'fn_exim_vendors_set_shippings',
            'args' => ['$object'],
            'import_only' => true,
        ]
    ],
    'import_after_process_data' => [
        'init_vendor_logos' => [
            'function' => 'fn_exim_vendor_init_logos',
            'args' => ['$primary_object_id', '$object', '$object_exists'],
            'import_only' => true,
        ],
    ],
    'export_fields' => [
        'Vendor name' => [
            'db_field' => 'company',
            'required' => true,
        ],
        'Vendor language' => [
            'process_get' => ['fn_exim_vendors_export_language', '#key'],
            'process_put' => ['fn_exim_vendors_import_language', '%E-mail%', '#this'],
            'linked' => false
        ],
        'Status' => [
            'db_field' => 'status',
        ],
        'Language' => [
            'table' => 'company_descriptions',
            'db_field' => 'lang_code',
            'type' => 'languages',
            'multilang' => true,
            'required' => true,
        ],
        'Description' => [
            'table' => 'company_descriptions',
            'db_field' => 'company_description',
            'multilang' => true,
        ],
        'E-mail' => [
            'db_field' => 'email',
            'required' => true,
            'alt_key' => true,
        ],
        'Phone' => [
            'db_field' => 'phone',
        ],
        'Url' => [
            'db_field' => 'url',
        ],
        'Address' => [
            'db_field' => 'address',
        ],
        'City' => [
            'db_field' => 'city',
        ],
        'Country' => [
            'db_field' => 'country',
        ],
        'State' => [
            'db_field' => 'state',
        ],
        'Zipcode' => [
            'db_field' => 'zipcode',
        ],
        'Shippings' => [
            'db_field' => 'shippings',
        ],
        'Logo for the customer area' => [
            'process_get' => ['fn_exim_vendors_export_logo', '#key', 'theme', '@images_path'],
            'process_put' => ['fn_exim_vendors_import_logo', '%E-mail%', 'theme', '#this', '@images_path'],
            'linked' => false,
        ],
        'Logo for invoices' => [
            'process_get' => ['fn_exim_vendors_export_logo', '#key', 'mail', '@images_path'],
            'process_put' => ['fn_exim_vendors_import_logo', '%E-mail%', 'mail', '#this', '@images_path'],
            'linked' => false,
        ],
        'Date added' => [
            'db_field' => 'timestamp',
            'process_get' => ['fn_timestamp_to_date', '#this'],
            'convert_put' => ['fn_date_to_timestamp', '#this'],
            'return_result' => true,
            'default' => ['time']
        ],
        'Tax number' => [
            'db_field' => 'tax_number',
        ],
    ],
];
