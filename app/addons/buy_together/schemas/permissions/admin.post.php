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

$schema['buy_together'] = [
    'permissions' => ['GET' => 'view_catalog', 'POST' => 'manage_catalog'],
    'modes' => [
        'delete'            => [
            'permissions' => 'manage_catalog'
        ],
        'm_delete'          => [
            'permissions' => 'manage_catalog'
        ],
        'm_update_statuses' => [
            'permissions' => 'manage_catalog'
        ],
    ],
];
$schema['tools']['modes']['update_status']['param_permissions']['table']['buy_together'] = 'manage_catalog';

return $schema;
