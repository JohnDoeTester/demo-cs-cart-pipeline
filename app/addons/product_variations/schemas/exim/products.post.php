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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * @var array $schema
 */

include_once __DIR__ . '/products.functions.php';

$schema['export_fields'][PRODUCT_VARIATION_EXIM_CODE_FIELD] = [
    'process_get'   => ['fn_product_variations_exim_get_variation_group_code', '#row', '#key', '#this'],
    'process_put'   => ['fn_product_variations_exim_set_variation_group_code', '#row', PRODUCT_VARIATION_EXIM_CODE_FIELD],
    'return_field'  => 'variation_group_code',
    'return_result' => true,
    'linked'        => false
];

$schema['export_fields'][PRODUCT_VARIATION_EXIM_ID_FIELD] = [
    'process_get'   => ['fn_product_variations_exim_get_variation_group_id', '#row', '#key', '#this'],
    'export_only'   => true,
    'linked'        => false
];

$schema['export_fields'][PRODUCT_VARIATION_EXIM_PARENT_PRODUCT_ID] = [
    'process_get'   => ['fn_product_variations_exim_get_variation_parent_product_id', '#row', '#key', '#this'],
    'export_only'   => true,
    'linked'        => false
];

$schema['export_fields'][PRODUCT_VARIATION_EXIM_SUB_GROUP_ID] = [
    'process_get'   => ['fn_product_variations_exim_get_variation_sub_group_id', '#row'],
    'export_only'   => true,
    'linked'        => false
];

$schema['export_fields'][PRODUCT_VARIATION_EXIM_DEFAULT_VARIATION] = [
    'process_get'   => ['fn_product_variations_exim_get_variation_set_as_default', '#row'],
    'process_put'   => ['fn_product_variations_exim_set_variation_set_as_default', '#row', PRODUCT_VARIATION_EXIM_DEFAULT_VARIATION],
    'return_field'  => 'variation_set_as_default',
    'return_result' => true,
    'linked'        => false
];

foreach (fn_product_variations_exim_get_features() as $feature) {
    $field = sprintf(PRODUCT_VARIATION_EXIM_FEATURE_FIELD_TEMPLATE, $feature['internal_name']);

    $schema['export_fields'][$field] = [
        'process_get' => ['fn_product_variations_exim_get_variation_feature_value', '#key', $feature['feature_id'], '#lang_code'],
        'export_only' => true,
        'linked'      => false,
        'feature_id'  => $feature['feature_id'],
        'multilang'   => true
    ];
}

$schema['export_pre_moderation']['product_variations'] = [
    'function' => 'fn_product_variations_exim_pre_processing',
    'args'     => ['$pattern', '$export_fields', '$options'],
];

$schema['pre_export_process']['product_variations'] = [
    'function' => 'fn_product_variations_exim_pre_export_process',
    'args'     => ['$pattern', '$table_fields'],
];

$schema['post_processing']['product_variations'] = [
    'function'    => 'fn_product_variations_exim_post_processing',
    'args'        => ['$primary_object_ids', '$import_data', '$processed_data', '$final_import_notification'],
    'import_only' => true,
];

return $schema;