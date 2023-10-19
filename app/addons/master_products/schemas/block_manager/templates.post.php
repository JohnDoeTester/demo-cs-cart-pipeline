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

$schema['addons/master_products/blocks/products/vendor_products.tpl'] = [
    'bulk_modifier' => [
        'fn_master_products_get_vendor_products' => [
            'products' => '#this',
            'params'   => [
                'get_icon'     => false,
                'get_detailed' => false,
                'get_options'  => true,
            ],
        ],
    ],
];

return $schema;