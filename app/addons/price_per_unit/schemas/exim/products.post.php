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

/**
 * @var array $schema
 */

$schema['export_fields']['Unit name'] = [
    'table'    => 'product_descriptions',
    'db_field' => 'unit_name',
    'multilang'   => true,
    'process_get' => ['fn_export_product_descr', '#key', '#this', '#lang_code', 'unit_name', true],
    'process_put' => ['fn_import_product_descr', '#this', '#key', 'unit_name', '#new']
];

$schema['export_fields']['Units in product'] = [
    'db_field' => 'units_in_product'
];

$schema['export_fields']['Show price per X units'] = [
    'db_field' => 'show_price_per_x_units'
];

if (fn_allowed_for('ULTIMATE')) {
    $schema['export_fields']['Unit name']['process_put'] = ['fn_import_product_descr', '#this', '#key', 'unit_name', '#new', '#row', true];
}

return $schema;
