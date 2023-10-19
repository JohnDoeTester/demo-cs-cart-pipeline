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

use Tygh\Http;

defined('BOOTSTRAP') or die('Access denied');

/** @var array $schema */
$schema['product_reviews'] = [
    'modes'       => [
        /**
         * The product_reviews.view is not used in the administration panel,
         * but this action is required for proper permissions check of vendors
         */
        'view'     => [
            'permissions' => 'view_product_reviews',
        ],
        'update'   => [
            'permissions' => [Http::GET => 'view_product_reviews', Http::POST => 'manage_product_reviews'],
        ],
        'delete'   => [
            'permissions' => 'manage_product_reviews',
        ],
        'm_delete' => [
            'permissions' => 'manage_product_reviews',
        ],
        'manage' => [
            'permissions' => 'view_product_reviews',
        ],
        'add' => [
            'permissions' => 'create_product_reviews',
        ]
    ],
    'permissions' => 'manage_product_reviews',
];

$schema['tools']['modes']['update_status']['param_permissions']['table']['product_reviews'] = 'manage_product_reviews';

return $schema;
