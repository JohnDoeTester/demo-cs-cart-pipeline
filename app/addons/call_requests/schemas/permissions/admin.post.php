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

$schema['call_requests'] = [
    'permissions' => ['GET' => 'view_call_requests', 'POST' => 'manage_call_requests'],
    'modes' => [
        'delete' => [
            'permissions' => 'manage_call_requests'
        ],
        'm_delete' => [
            'permissions' => 'manage_call_requests'
        ],
        'm_update_statuses' => [
            'permissions' => 'manage_call_requests'
        ],
    ],
];

$schema['tools']['modes']['update_status']['param_permissions']['table']['call_requests'] = 'manage_call_requests';

return $schema;
