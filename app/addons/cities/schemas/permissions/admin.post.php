<?php
/***************************************************************************
 * *
 * (c) 2004 Vladimir V. Kalynyak, Alexey V. Vinokurov, Ilya M. Shalnev *
 * *
 * This is commercial software, only users who have purchased a valid *
 * license and accept to the terms of the License Agreement can install *
 * and use this program. *
 * *
 ****************************************************************************
 * PLEASE READ THE FULL TEXT OF THE SOFTWARE LICENSE AGREEMENT IN THE *
 * "copyright.txt" FILE PROVIDED WITH THIS DISTRIBUTION PACKAGE. *
 ****************************************************************************/

defined('BOOTSTRAP') or die('Access denied');

 /** @var array $schema */
$schema['cities'] = [
    'modes' => [
        'delete' => [
            'permissions' => 'manage_cities'
        ],
        'm_delete' => [
            'permissions' => 'manage_cities'
        ]
    ],
    'permissions' => ['GET' => 'view_cities', 'POST' => 'manage_cities'],
];

$schema['tools']['modes']['update_status']['param_permissions']['table']['cities'] = 'manage_cities';

return $schema;
