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

use Tygh\Enum\YesNo;
use Tygh\Registry;

include_once(Registry::get('config.dir.schemas') . 'exim/users.functions.php');

$schema = [
    'section'       => 'users',
    'pattern_id'    => 'users',
    'name'          => __('users'),
    'key'           => ['user_id'],
    'order'         => 0,
    'table'         => 'users',
    'permissions'   => [
        'import' => 'manage_users',
        'export' => 'view_users',
    ],
    'references'    => [
        'user_profiles' => [
            'reference_fields' => ['user_id' => '#key', 'profile_type' => 'P'],
            'join_type'        => 'LEFT',
        ],
    ],
    'range_options' => [
        'selector_url' => 'profiles.manage',
        'object_name'  => __('users'),
    ],
    'export_fields' => [
        'E-mail' => [
            'db_field' => 'email',
            'alt_key'  => true,
            'required' => true,
        ],
        'User type' => [
            'db_field' => 'user_type',
        ],
        'Status' => [
            'db_field' => 'status',
        ],
        'Password' => [
            'db_field'   => 'password',
            'pre_insert' => ['fn_exim_process_password', '#row'],
        ],
        'Salt' => [
            'db_field' => 'salt',
        ],
        'First name' => [
            'db_field' => 'firstname',
        ],
        'Last name' => [
            'db_field' => 'lastname',
        ],
        'Company' => [
            'db_field' => 'company',
        ],
        'Fax' => [
            'db_field' => 'fax',
        ],
        'Phone' => [
            'db_field' => 'phone',
        ],
        'Web site' => [
            'db_field' => 'url',
        ],
        'Tax exempt' => [
            'db_field' => 'tax_exempt',
        ],
        'Registration date' => [
            'db_field'    => 'timestamp',
            'process_get' => ['fn_timestamp_to_date', '#this'],
            'convert_put' => ['fn_date_to_timestamp', '#this'],
            'default'     => ['time'],
        ],
        'Language' => [
            'db_field' => 'lang_code'
        ],
        'Billing: first name' => [
            'db_field' => 'b_firstname',
            'table'    => 'user_profiles',
        ],
        'Billing: last name' => [
            'db_field' => 'b_lastname',
            'table'    => 'user_profiles',
        ],
        'Billing: address' => [
            'db_field' => 'b_address',
            'table'    => 'user_profiles',
        ],
        'Billing: address (line 2)' => [
            'db_field' => 'b_address_2',
            'table'    => 'user_profiles',
        ],
        'Billing: city' => [
            'db_field' => 'b_city',
            'table'    => 'user_profiles',
        ],
        'Billing: state' => [
            'db_field' => 'b_state',
            'table'    => 'user_profiles',
        ],
        'Billing: country' => [
            'db_field' => 'b_country',
            'table'    => 'user_profiles',
        ],
        'Billing: zipcode' => [
            'db_field' => 'b_zipcode',
            'table'    => 'user_profiles',
        ],
        'Billing: phone' => [
            'db_field' => 'b_phone',
            'table'    => 'user_profiles',
        ],
        'Shipping: first name' => [
            'db_field' => 's_firstname',
            'table'    => 'user_profiles',
        ],
        'Shipping: last name' => [
            'db_field' => 's_lastname',
            'table'    => 'user_profiles',
        ],
        'Shipping: address' => [
            'db_field' => 's_address',
            'table'    => 'user_profiles',
        ],
        'Shipping: address (line 2)' => [
            'db_field' => 's_address_2',
            'table'    => 'user_profiles',
        ],
        'Shipping: city' => [
            'db_field' => 's_city',
            'table'    => 'user_profiles',
        ],
        'Shipping: state' => [
            'db_field' => 's_state',
            'table'    => 'user_profiles',
        ],
        'Shipping: country' => [
            'db_field' => 's_country',
            'table'    => 'user_profiles',
        ],
        'Shipping: zipcode' => [
            'db_field' => 's_zipcode',
            'table'    => 'user_profiles',
        ],
        'Shipping: phone' => [
            'db_field' => 's_phone',
            'table'    => 'user_profiles',
        ],
        'Extra fields' => [
            'linked'      => false,
            'process_get' => ['fn_exim_get_extra_fields', '#key', '#lang_code'],
            'process_put' => ['fn_exim_set_extra_fields', '#this', '#key'],
        ],
        'User group IDs' => [
            'process_get' => ['fn_exim_get_usergroups', '#key'],
            'process_put' => ['fn_exim_set_usergroups', '#key', '#this'],
            'linked'      => false, // this field is not linked during import-export
        ],
    ],
];

if (fn_allowed_for('MULTIVENDOR')) {
    $schema['export_fields']['Vendor'] = [
        'db_field'    => 'company_id',
        'process_get' => ['fn_get_company_name', '#this'],
    ];

    if (!Registry::get('runtime.company_id')) {
        $schema['export_fields']['Vendor']['required'] = true;
    }

    $schema['import_process_data'] = [
        'check_company_id' => [
            'function'    => 'fn_import_check_user_company_id',
            'args'        => ['$primary_object_id', '$object', '$processed_data', '$skip_record'],
            'import_only' => true,
        ],
        'check_user_type' => [
            'function'    => 'fn_import_check_user_type',
            'args'        => ['$object', '$processed_data', '$skip_record'],
            'import_only' => true,
        ],
    ];

    $schema['pre_processing'] = [
        'set_user_company_id' => [
            'function'    => 'fn_import_set_user_company_id',
            'args'        => ['$import_data'],
            'import_only' => true,
        ],
        'set_default_value' => [
            'function'    => 'fn_import_set_default_value',
            'args'        => ['$import_data'],
            'import_only' => true,
        ],
    ];
}

if (fn_allowed_for('ULTIMATE')) {
    $schema['export_fields']['Store'] = [
        'db_field'    => 'company_id',
        'process_get' => ['fn_get_company_name', '#this'],
    ];

    $schema['key'][] = 'company_id';

    if (!Registry::get('runtime.company_id')) {
        $schema['export_fields']['Store']['required'] = true;
    }

    $schema['pre_processing'] = [
        'set_user_company_id' => [
            'function'    => 'fn_import_set_user_company_id',
            'args'        => ['$import_data'],
            'import_only' => true,
        ],
        'set_default_value' => [
            'function'    => 'fn_import_set_default_value',
            'args'        => ['$import_data'],
            'import_only' => true,
        ],
    ];

    $schema['pre_export_process'] = [
        'set_allowed_company_ids' => [
            'function'    => 'fn_set_allowed_company_ids',
            'args'        => ['$conditions'],
            'export_only' => true,
        ],
    ];

    $schema['import_process_data'] = [
        'check_company_id' => [
            'function'    => 'fn_import_check_user_company_id',
            'args'        => ['$primary_object_id', '$object', '$processed_data', '$skip_record'],
            'import_only' => true,
        ],
        'check_user_type' => [
            'function'    => 'fn_import_check_user_type',
            'args'        => ['$object', '$processed_data', '$skip_record'],
            'import_only' => true,
        ],
    ];

    //We should add company_id as alt key that $primary_object_id will be filled correctly.
    if (YesNo::isFalse(Registry::get('settings.Stores.share_users')) && !Registry::get('runtime.simple_ultimate')) {
        $schema['export_fields']['Store']['alt_key'] = true;
    }
}

return $schema;
