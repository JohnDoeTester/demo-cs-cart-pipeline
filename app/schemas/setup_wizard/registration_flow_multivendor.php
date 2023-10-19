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

use Tygh\Enum\RegistrationFlowTypes;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\YesNo;

defined('BOOTSTRAP') or die('Access denied');

return [
    RegistrationFlowTypes::STOREFRONT_VENDOR_PANEL_ACCESS => [
        'addons'      => [
            'vendor_data_premoderation' => [
                'status'   => ObjectStatuses::ACTIVE,
                'settings' => [
                    'vendors_prior_approval' => [
                        'value' => 'none',
                    ],
                ],
            ],
        ],
        'settings'    => [
            'allow_approve_vendors_in_two_steps' => [
                'value' => YesNo::NO
            ],
        ],
        'name'        => __('sw.access_to_admin_panel_and_storefront'),
        'description' => __('sw.access_to_admin_panel_and_storefront.description'),
    ],
    RegistrationFlowTypes::VENDOR_PANEL_ACCESS => [
        'addons'      => [
            'vendor_data_premoderation' => [
                'status'   => ObjectStatuses::ACTIVE,
                'settings' => [
                    'vendors_prior_approval' => [
                        'value' => 'all',
                    ],
                ],
            ],
        ],
        'settings'    => [
            'allow_approve_vendors_in_two_steps' => [
                'value' => YesNo::NO
            ],
        ],
        'name'        => __('sw.access_to_admin_panel_approval_storefront'),
        'description' => __('sw.access_to_admin_panel_approval_storefront.description'),
    ],
    RegistrationFlowTypes::NO_ACCESS => [
        'addons'      => [
            'vendor_data_premoderation' => [
                'is_optional' => true,
                'status'      => ObjectStatuses::ACTIVE,
                'settings'    => [
                    'vendors_prior_approval' => [
                        'value' => 'none',
                    ],
                ],
            ],
        ],
        'settings'    => [
            'allow_approve_vendors_in_two_steps' => [
                'value' => YesNo::YES
            ],
        ],
        'name'        => __('sw.approval_access_to_admin_panel_and_storefront'),
        'description' => __('sw.approval_access_to_admin_panel_and_storefront.description'),
    ],
];
