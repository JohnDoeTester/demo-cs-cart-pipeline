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

$schema['product_bundles'] = [
    'show_on_locations' => ['product_tabs'],
    'templates'         => 'addons/product_bundles/blocks/product_bundles.tpl',
    'cache'             => [
        'request_handlers'  => ['product_id'],
        'update_handlers'   => [
            'products',
            'product_descriptions',
            'product_tabs',
            'product_tabs_descriptions',
            'product_prices',
        ],
    ],
];

return $schema;
