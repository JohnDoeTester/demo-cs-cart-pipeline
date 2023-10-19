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

/** @var array<string, string|array> $schema */
$schema = [
    'options_type'      => [
        'global_setting'  => 'General.global_options_type',
        'default_setting' => 'General.default_options_type',
    ],
    'exceptions_type'   => [
        'global_setting'  => 'General.global_exceptions_type',
        'default_setting' => 'General.default_exceptions_type',
    ],
    'tracking'          => [
        'global_setting'  => 'General.global_tracking',
        'default_setting' => 'General.default_tracking',
    ],
    'zero_price_action' => [
        'global_setting'  => 'Checkout.global_zero_price_action',
        'default_setting' => 'Checkout.default_zero_price_action',
    ],
    'min_qty'           => [
        'global_setting'  => 'Checkout.global_min_qty',
        'default_setting' => 'Checkout.default_min_qty',
    ],
    'max_qty'           => [
        'global_setting'  => 'Checkout.global_max_qty',
        'default_setting' => 'Checkout.default_max_qty',
    ],
    'qty_step'          => [
        'global_setting'  => 'Checkout.global_qty_step',
        'default_setting' => 'Checkout.default_qty_step',
    ],
    'list_qty_count'    => [
        'global_setting'  => 'Checkout.global_list_qty_count',
        'default_setting' => 'Checkout.default_list_qty_count',
    ],
    'details_layout' => [
        'global_setting'  => 'Appearance.global_product_details_view',
        'default_setting' => 'Appearance.default_product_details_view',
    ]
];

return $schema;
