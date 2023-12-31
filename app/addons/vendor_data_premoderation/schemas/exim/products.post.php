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

require_once __DIR__ . '/products.functions.php';

/**
 * @var array<string, array<string, string>> $schema
 */
$schema['import_process_data']['load_initial_product_state'] = [
    'function'    => 'fn_exim_vendor_data_premoderation_load_initial_product_state',
    'args'        => ['$primary_object_id', '$object'],
    'import_only' => true,
];

$schema['post_processing']['set_approval_status'] = [
    'function'    => 'fn_exim_vendor_data_premoderation_set_approval_status',
    'args'        => ['$primary_object_ids'],
    'import_only' => true,
];

$schema['import_after_process_data']['set_approval_status_new_active_products'] = [
    'function'    => 'fn_exim_vendor_data_premoderation_set_approval_status_new_active_products',
    'args'        => ['$primary_object_id'],
    'import_only' => true,
];

return $schema;
