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

$schema['controllers']['vendor_communication'] = [
    'modes' => [
        'delete_thread' => [
            'permissions' => false,
        ],
        'm_delete_thread' => [
            'permissions' => false,
        ],
        'create_thread' => [
            'param_permissions' => [
                'communication_type' => [
                    CommunicationTypes::VENDOR_TO_ADMIN => true,
                    CommunicationTypes::VENDOR_TO_CUSTOMER => true,
                ],
            ],
            'default_permissions' => false,
        ],
        'threads' => [
            'param_permissions' => [
                'communication_type' => [
                    CommunicationTypes::VENDOR_TO_ADMIN => true,
                    CommunicationTypes::VENDOR_TO_CUSTOMER => true,
                ],
            ],
            'default_permissions' => false,
        ],
        'post_message' => [
            'permissions' => true
        ],
        'view' => [
            'param_permissions' => [
                'communication_type' => [
                    CommunicationTypes::VENDOR_TO_ADMIN  => true,
                    CommunicationTypes::VENDOR_TO_CUSTOMER => true,
                ],
            ],
            'default_permissions' => false,
        ],
        'm_post_message' => [
            'permissions' => false,
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
