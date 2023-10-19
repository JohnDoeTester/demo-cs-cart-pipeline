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

if (isset($schema['options'])) {
    $schema['options']['skip_creating_new_products']['tab']
        = $schema['options']['reset_inventory']['tab']
        = $schema['options']['delete_files']['tab']
        = 'settings';

    $schema['options']['delete_files']['section']
        = 'additional';

    $schema['options']['images_path']['hidden']
        = $schema['options']['skip_creating_new_products']['hidden']
        = true;

    $schema['options']['skip_creating_new_products']['option_data_pre_modifier'] = array(
        'legacy_fallback' => 'fn_advanced_import_convert_import_strategy_to_set_skip_creating_new_products_option',
    );
}

if (isset($schema['import_process_data'])) {
    $schema['import_process_data']['skip_updating_or_creating_new_products'] = array(
        'function'    => 'fn_advanced_import_skip_updating_or_creating_new_products',
        'args'        => array(
            '$primary_object_id',
            '$options',
            '$skip_record',
            '$processed_data',
        ),
        'import_only' => true,
    );
}

return $schema;