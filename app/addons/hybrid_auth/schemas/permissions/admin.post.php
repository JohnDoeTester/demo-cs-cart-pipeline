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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

$schema['hybrid_auth'] = [
    'modes' => [
        'delete_provider'   => [
            'permissions' => 'manage_providers'
        ],
        'm_delete_provider' => [
            'permissions' => 'manage_providers'
        ],
        'm_update_statuses' => [
            'permissions' => 'manage_providers'
        ],
    ],
    'permissions' => ['GET' => 'view_providers', 'POST' => 'manage_providers']
];

$schema['tools']['modes']['update_status']['param_permissions']['table']['hybrid_auth_providers'] = 'manage_providers';
$schema['tools']['modes']['update_position']['param_permissions']['table']['hybrid_auth_providers'] = 'manage_providers';

return $schema;
