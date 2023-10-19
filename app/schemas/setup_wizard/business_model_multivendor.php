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

use Tygh\Enum\MoneyTransferTypes;
use Tygh\Enum\ObjectStatuses;
use Tygh\Addons\SchemesManager;

defined('BOOTSTRAP') or die('Access denied');

return [
    MoneyTransferTypes::SPLIT => [
        'addons' => [
            [
                'direct_payments'          => ObjectStatuses::DISABLED,
            ],
            [
                'vendor_plans'             => ObjectStatuses::ACTIVE,
            ],
            //if array, it means that one of the addons (in array) must be active or disabled
            [
                'paypal_commerce_platform' => ObjectStatuses::ACTIVE,
                'stripe_connect'           => ObjectStatuses::ACTIVE
            ]
        ],
        'processors_scripts' => [
            'paypal_commerce_platform' => 'paypal_commerce_platform.php',
            'stripe_connect'           => 'stripe_connect.php'
        ],
        'name'        => __('sw.money_split_automaticaly'),
        'description' => __('sw.money_split_automaticaly_descr', [
            '[stripe_connect]'           => SchemesManager::getName('stripe_connect', CART_LANGUAGE),
            '[paypal_commerce_platform]' => SchemesManager::getName('paypal_commerce_platform', CART_LANGUAGE),
        ]),
    ],
    MoneyTransferTypes::TO_VENDOR => [
        'addons' => [
            [
                'paypal_commerce_platform' => ObjectStatuses::DISABLED,
            ],
            [
                'stripe_connect'           => ObjectStatuses::DISABLED,
            ],
            [
                'direct_payments'          => ObjectStatuses::ACTIVE,
            ]
        ],
        'name'        => __('sw.money_goes_to_vendor'),
        'description' => __('sw.money_goes_to_vendor_descr')
    ],
    MoneyTransferTypes::TO_OWNER => [
        'addons' => [
            [
                'direct_payments'          => ObjectStatuses::DISABLED,
            ],
            [
                'paypal_commerce_platform' => ObjectStatuses::DISABLED,
            ],
            [
                'stripe_connect'           => ObjectStatuses::DISABLED,
            ]
        ],
        'name'        => __('sw.money_goes_to_owner'),
        'description' => __('sw.money_goes_to_owner_descr')
    ],
];
