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

$schema['rma'] = [
    'permissions' => 'manage_rma',
    'modes'       => [
        'returns'          => [
            'permissions' => 'view_rma',
        ],
        'details'          => [
            'permissions' => 'view_rma',
        ],
        'update_details'   => [
            'permissions' => 'manage_rma',
        ],
        'decline_products' => [
            'permissions' => 'manage_rma',
        ],
        'accept_products'  => [
            'permissions' => 'manage_rma',
        ],
        'confirmation'     => [
            'permissions' => 'manage_rma',
        ],
    ],
];
$schema['tools']['modes']['update_status']['param_permissions']['table']['rma_properties'] = 'manage_rma';

return $schema;
