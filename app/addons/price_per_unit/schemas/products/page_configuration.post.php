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

/** @var array $schema */

$schema['detailed']['sections']['price_per_unit'] = [
    'is_optional' => true,
    'title'       => 'price_per_unit',
    'position'    => 350,
    'fields'      => [
        'unit_name'              => ['is_optional' => true, 'title' => 'unit_name', 'position' => 100],
        'units_in_product'       => ['is_optional' => true, 'title' => 'units_in_product', 'position' => 200],
        'show_price_per_x_units' => ['is_optional' => true, 'title' => 'show_price_per_x_units', 'position' => 300],
    ],
];

return $schema;
