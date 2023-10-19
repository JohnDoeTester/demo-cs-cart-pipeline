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

use Tygh\Enum\Addons\VendorCommunication\CommunicationTypes;

defined('BOOTSTRAP') or die('Access denied');

$schema['vendor_communication'] = [
    'modes' => [
        'delete_thread' => [
            'permissions' => 'manage_vendor_communication',
        ],
        'm_delete_thread' => [
            'permissions' => 'manage_vendor_communication',
        ],
        'create_thread' => [
            'param_permissions' => [
                'communication_type' => [
                    CommunicationTypes::VENDOR_TO_ADMIN => 'manage_admin_communication',
                    CommunicationTypes::VENDOR_TO_CUSTOMER => 'manage_vendor_communication',
                ],
            ],
        ],
        'threads' => [
            'param_permissions' => [
                'communication_type' => [
                    CommunicationTypes::VENDOR_TO_ADMIN => 'view_admin_communication',
                    CommunicationTypes::VENDOR_TO_CUSTOMER => 'view_vendor_communication',
                ],
            ],
        ],
        'post_message' => [
            'param_permissions' => [
                'communication_type' => [
                    CommunicationTypes::VENDOR_TO_ADMIN  => 'manage_admin_communication',
                    CommunicationTypes::VENDOR_TO_CUSTOMER => 'manage_vendor_communication',
                ],
            ],
        ],
        'view' => [
            'param_permissions' => [
                'communication_type' => [
                    CommunicationTypes::VENDOR_TO_ADMIN  => 'view_admin_communication',
                    CommunicationTypes::VENDOR_TO_CUSTOMER => 'view_vendor_communication',
                ],
            ],
        ],
        'm_post_message' => [
            'param_permissions' => [
                'communication_type' => [
                    CommunicationTypes::VENDOR_TO_ADMIN    => 'manage_admin_communication',
                    CommunicationTypes::VENDOR_TO_CUSTOMER => 'manage_vendor_communication',
                ],
            ],
        ],
        // for vendor_privileges add-on
        'view_customer_order_thread' => [
            'permissions' => 'view_order_communication',
        ],
        'manage_customer_order_thread' => [
            'permissions' => 'manage_order_communication',
        ],
    ],
];

return $schema;
