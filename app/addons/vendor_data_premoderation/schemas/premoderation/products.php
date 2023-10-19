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

/**
 * This schema describes how product data from different sources is premoderated.
 * The structure of this schema is the following:
 * 'table_name' => [
 *     'requires_premoderation' => table_premoderation_rule (see below)
 *     'fields' => [
 *         'field_name' => field_premoderation_rule (see below),
 *         ...
 *     ]
 * ],
 * ...
 *
 * = Table premoderation rules =
 * Changes do not require premoderation:
 * 'table_name' => [
 *     'requires_premoderation' => false
 * ],
 *
 * The `method` method of the `service` service must be called to determine if changes require premoderation.
 * `service` must be registered in the Application (Tygh::$app) container.
 * 'table_name' => [
 *     'requires_premoderation' => ['service', 'method'],
 * ],
 *
 * Changes require moderation, moderation is configured individually for each field:
 * 'table_name' => [
 *     'requires_premoderation' => true,
 *     'fields' => [
 *         // field premoderation rules (see below)
 *     ]
 * ]
 *
 * Changes in tables that contain product data but missing in this schema will trigger premoderation.
 *
 * = Field premoderation rules =
 * Changes do not require premoderation:
 * 'field_name' => [
 *     'requires_premoderation' => false
 * ],
 *
 * The `method` method of the `service` service must be called to determine if changes require premoderation.
 * `service` must be registered in the Application (Tygh::$app) container.
 * 'field_name' => [
 *     'requires_premoderation' => ['service', 'method'],
 * ],
 *
 * Changes require moderation:
 * 'field_name' => [
 *     'requires_premoderation' => true,
 * ]
 *
 * Changes in fields missing in this schema will trigger premoderation.
 */
$schema = [
    'products'       => [
        'requires_premoderation' => true,
        'fields'                 => [
            'updated_timestamp' => [
                'requires_premoderation' => false,
            ],
            'status'            => [
                'requires_premoderation' => false,
            ],
            'list_price'        => [
                'requires_premoderation' => [
                    /** @see \Tygh\Addons\VendorDataPremoderation\ProductPremoderationSettings */
                    'addons.vendor_data_premoderation.product_premoderation_settings',
                    /** @see \Tygh\Addons\VendorDataPremoderation\ProductPremoderationSettings::getFieldPremoderation() */
                    'getFieldPremoderation',
                ],
            ],
            'amount'            => [
                'requires_premoderation' => [
                    /** @see \Tygh\Addons\VendorDataPremoderation\ProductPremoderationSettings */
                    'addons.vendor_data_premoderation.product_premoderation_settings',
                    /** @see \Tygh\Addons\VendorDataPremoderation\ProductPremoderationSettings::getFieldPremoderation() */
                    'getFieldPremoderation',
                ],
            ],
        ],
    ],
    'product_prices' => [
        'requires_premoderation' => [
            /** @see \Tygh\Addons\VendorDataPremoderation\ProductPremoderationSettings */
            'addons.vendor_data_premoderation.product_premoderation_settings',
            /** @see \Tygh\Addons\VendorDataPremoderation\ProductPremoderationSettings::getSourcePremoderation() */
            'getSourcePremoderation',
        ],
    ],
];

return $schema;
