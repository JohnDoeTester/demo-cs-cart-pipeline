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
 * 'copyright.txt' FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE.            *
 ****************************************************************************/

if (!defined('BOOTSTRAP')) {
    die('Access denied');
}

include_once __DIR__ . '/products.functions.php';

/** @var array<string, array<string,string>> $schema */

$schema['import_process_data']['prepare_rma_fields'] = [
    'function'    => 'fn_import_prepare_rma_data',
    'args'        => ['$primary_object_id', '$object'],
    'import_only' => true,
];

$schema['export_fields']['Returnable'] = [
    'db_field' => 'is_returnable'
];
$schema['export_fields']['Return period (days)'] = [
    'db_field' => 'return_period'
];

return $schema;
