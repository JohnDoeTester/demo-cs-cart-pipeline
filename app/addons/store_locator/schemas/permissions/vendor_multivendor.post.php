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

$schema['controllers']['store_locator'] = [
    'modes'       => [
        'add'               => [
            'permissions' => 'manage_store_locator',
        ],
        'update'            => [
            'permissions' => 'manage_store_locator',
        ],
        'm_update'            => [
            'permissions' => 'manage_store_locator',
        ],
        'delete'            => [
            'permissions' => 'manage_store_locator',
        ],
        'm_delete'          => [
            'permissions' => 'manage_store_locator',
        ],
        'manage'            => [
            'permissions' => 'view_store_locator',
        ],
        'm_update_statuses' => [
            'permissions' => 'manage_store_locator',
        ],
        'm_update_pickup'   => [
            'permissions' => 'manage_store_locator',
        ],
    ],
    'permissions' => false,
];

$schema['controllers']['tools']['modes']['update_status']['param_permissions']['table']['store_locations'] = ['permissions' => 'manage_store_locator'];

return $schema;
