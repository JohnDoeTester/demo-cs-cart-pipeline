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

use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\YesNo;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

$schema = [
    'top'     => [],
    'central' => [
        'orders'    => [
            'position' => 100,
            'items'    => [
                'view_orders' => [
                    'href'       => 'orders.manage',
                    'alt'        => 'order_management',
                    'position'   => 100,
                    'root_title' => __('orders'),
                ],
                'shipments' => [
                    'href'     => 'shipments.manage',
                    'position' => 200,
                    'status'   => ObjectStatuses::DISABLED,
                ],
                'my_customers' => [
                    'href'     => 'profiles.manage?user_type=C',
                    'position' => 300,
                    'status'   => ObjectStatuses::DISABLED,
                ],
                'sales_reports' => [
                    'href'     => 'sales_reports.view',
                    'position' => 400,
                    'status'   => ObjectStatuses::DISABLED,
                ],
            ],
        ],
        'products'  => [
            'position' => 200,
            'title'    => __('products_menu_title'),
            'items'    => [
                'products' => [
                    'href'       => 'products.manage',
                    'alt'        => 'product_options.inventory,product_options.exceptions,products.update,products.m_update,products.add',
                    'position'   => 100,
                ],
                'features' => [
                    'href'       => 'product_features.manage',
                    'position'   => 200,
                ],
                'categories' => [
                    'href'     => 'categories.manage',
                    'position' => 300,
                    'status'   => ObjectStatuses::DISABLED,
                ],
            ],
        ],
        'content' => [
            'position' => 300,
            'status'   => ObjectStatuses::DISABLED,
            'items' => [
                'pages' => [
                    'href'     => 'pages.manage?get_tree=multi_level',
                    'position' => 100,
                    'status'   => ObjectStatuses::DISABLED,
                ],
            ],
        ],
        'marketing' => [
            'position' => 400,
            'items'    => [
                'promotions' => [
                    'href'       => 'promotions.manage',
                    'position'   => 100,
                ],
            ],
        ],
        'seller_tools'   => [
            'position' => 500,
            'items'    => [
                'vendor_accounting' => [
                    'href'     => 'companies.balance',
                    'position' => 100,
                    'strict'   => true,
                ],
                'my_administrators' => [
                    'href'     => 'profiles.manage?user_type=V',
                    'position' => 200,
                    'status'   => ObjectStatuses::DISABLED,
                ],
            ],
        ],
        'settings'  => [
            'position' => 600,
            'items'    => [
                'payment_methods'  => [
                    'href'        => 'payments.manage',
                    'position'    => 100,
                    'description' => 'vendor_panel_configurator.payment_methods.description',
                ],
                'shipping_methods' => [
                    'href'       => 'shippings.manage',
                    'position'   => 200,
                ],
                'themes'           => [
                    'href'        => 'themes.manage',
                    'position'    => 300,
                    'title'       => __('vendor_panel_configurator.theme_styles'),
                    'root_title'  => __('vendor_panel_configurator.theme_styles'),
                    'description' => 'vendor_panel_configurator.theme_styles.description',
                ],
                'layouts'          => [
                    'href'        => 'block_manager.manage',
                    'position'    => 400,
                    'title'       => __('vendor_panel_configurator.theme_layouts'),
                    'root_title'  => __('vendor_panel_configurator.theme_layouts'),
                    'description' => 'vendor_panel_configurator.theme_layouts.description',
                ],
                'files'            => [
                    'href'        => 'file_editor.manage',
                    'position'    => 500,
                ],
                'sync_data'        => [
                    'href'              => 'sync_data.manage',
                    'position'          => 600,
                    'depends_on_scheme' => true
                ]
            ],
        ],
    ],
];

if (Registry::get('settings.Vendors.allow_vendor_manage_features') !== YesNo::YES) {
    unset($schema['central']['products']['items']['features']);
}

if (Registry::get('runtime.company_id')) {
    $schema['central']['seller_tools']['items']['seller_info'] = [
        'href'     => 'companies.update?company_id=' . Registry::get('runtime.company_id'),
        'position' => 300,
        'status'   => ObjectStatuses::DISABLED,
    ];
}

return $schema;
