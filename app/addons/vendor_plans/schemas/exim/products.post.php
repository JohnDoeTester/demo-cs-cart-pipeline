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

if (fn_allowed_for('MULTIVENDOR') && Registry::get('runtime.company_id')) {
    $schema['import_process_data']['vendor_plans_import_check_object_id'] = array(
        'function' => 'fn_vendor_plans_import_check_object_id',
        'args' => array('$primary_object_id', '$processed_data', '$skip_record'),
        'import_only' => true,
    );
}

/** @var array{import_process_data: array<array<string|bool|array<string>>>} $schema */
$schema['import_process_data']['vendor_plans_import_skip_products_with_unavailable_categories'] = [
    'function'    => 'fn_vendor_plans_import_skip_products_with_unavailable_categories',
    'args'        => [
        '%company_id',
        '%company',
        '%Category',
        '%Secondary categories',
        '@category_delimiter',
        '$processed_data',
        '%lang_code',
        '$skip_record',
        '$primary_object_id'
    ],
    'import_only' => true,
];

return $schema;

