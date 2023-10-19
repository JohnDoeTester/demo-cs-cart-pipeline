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

if (!defined('BOOTSTRAP')) { exit('Access denied'); }

$schema['addons/vendor_locations/blocks/closest_vendors.tpl'] = array(
    'settings' => array(
        'number_of_columns' => array(
            'type' => 'input',
            'default_value' => 5,
        ),
        'show_location' => array(
            'type' => 'checkbox',
            'default_value' => 'Y',
        ),
        'show_products_count' => array(
            'type' => 'checkbox',
            'default_value' => 'Y',
        ),
    ),
    'fillings' => array('all', 'manually'),
    'params' => array(
        'status' => 'A',
    ),
);

return $schema;
