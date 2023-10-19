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

use Tygh\Enum\YesNo;
use Tygh\Registry;
use Tygh\Settings;

defined('BOOTSTRAP') or die('Access denied');

if (!Registry::get('config.tweaks.allow_global_individual_settings')) {
    $tracking = 'inventory_tracking';
} elseif (Registry::get('settings.General.global_tracking') !== null) {
    $tracking = 'global_tracking';
} else {
    $tracking = 'default_tracking';
}

$schema = [
    'about_store' => [
        'position' => 10,
        'title' => 'sw.about_store',
        'header' => 'sw.text_about_store_header',
        'sections' => [
            [
                'items' => [
                    [
                        'type' => 'setting',
                        'is_required' => true,
                        'name' => 'company_name',
                        'description' => 'sw.company_name',
                        'decoration_class' => 'sw_size_1',
                    ],

                ],
                'decoration_class' => 'control-icon sw_company_name',
            ],
            [
                'items' => [
                    [
                        'type' => 'setting',
                        'is_email' => true,
                        'name' => 'company_site_administrator',
                        'description' => 'sw.site_admin_email',
                        'decoration_class' => 'sw_size_2',
                    ],
                ],
                'decoration_class' => 'control-icon sw_site_admin_email',
            ],
            [
                'items' => [
                    [
                        'type' => 'setting',
                        'name' => 'company_address',
                        'description' => 'sw.address_text',
                        'decoration_class' => 'sw_size_1',
                    ],
                    [
                        'type' => 'setting',
                        'name' => 'company_city',
                        'description' => 'sw.city',
                        'decoration_class' => 'sw_size_1',
                    ],
                    [
                        'type' => 'setting',
                        'name' => 'company_country',
                        'description' => 'sw.country',
                        'decoration_class' => 'sw_size_1',
                    ],
                    [
                        'type' => 'setting',
                        'name' => 'company_state',
                        'description' => 'sw.state',
                        'decoration_class' => 'sw_size_1',
                    ],
                    [
                        'type' => 'setting',
                        'name' => 'company_zipcode',
                        'description' => 'sw.zipcode',
                        'decoration_class' => 'sw_size_3',
                    ],
                ],
                'decoration_class' => 'control-icon sw_address',
            ],
            [
                'items' => [
                    [
                        'type' => 'setting',
                        'name' => 'company_phone',
                        'section' => 'Company',
                        'description' => 'sw.phone',
                        'decoration_class' => 'sw_size_2',
                    ],
                    [
                        'type' => 'setting',
                        'name' => 'company_phone_2',
                        'description' => 'sw.phone_2',
                        'decoration_class' => 'sw_size_2',
                    ],
                    [
                        'type' => 'setting',
                        'name' => 'company_fax',
                        'description' => 'sw.fax',
                        'decoration_class' => 'sw_size_2',
                    ],
                ],
                'decoration_class' => 'control-icon sw_phone',
            ],
            [
                'items' => [
                    [
                        'type' => 'setting',
                        'name' => 'company_website',
                        'description' => 'sw.website',
                        'decoration_class' => 'sw_size_2',
                    ],
                ],
                'decoration_class' => 'control-icon sw_website',
            ],
            [
                'items' => [
                    [
                        'type' => 'setting',
                        'name' => 'tracking_code',
                        'section' => 'google_analytics',
                        'description' => 'sw.google_analytics.tracking_code',
                        'decoration_class' => 'sw_size_2',
                    ],
                ],
                'decoration_class' => 'control-icon sw_statistic',
            ],
            [
                'items' => [
                    [
                        'type' => 'setting',
                        'name' => 'company_start_year',
                        'description' => 'sw.company_start_year',
                        'decoration_class' => 'sw_size_2',
                    ],
                ],
                'decoration_class' => 'control-icon sw_company_start_year',
            ],
        ]
    ],
    'design' => [
        'position' => 40,
        'title'    => 'sw.design',
        'header'   => 'sw.text_design_header',
        'extra'    => 'views/setup_wizard/components/tabs/design.tpl',
    ],
    /*'shippings' => [
        'position' => 50,
        'title'    => 'sw.shippings',
        'header'   => 'sw.text_shippings_header',
        'extra'    => 'views/setup_wizard/components/tabs/shippings.tpl',
    ],*/
    'settings' => [
        'position' => 60,
        'title' => 'sw.settings',
        'header' => 'sw.text_settings_header',
        'sections' => [
            [
                'items' => [
                    [
                        'type' => 'setting',
                        'name' => 'timezone',
                        'description' => 'sw.timezone',
                        'decoration_class' => 'sw_size_1',
                    ],
                    [
                        'type' => 'setting',
                        'name' => 'order_start_id',
                        'description' => 'sw.order_start_id',
                        'decoration_class' => 'sw_size_3',
                    ],
                    [
                        'type' => 'setting',
                        'name' => 'min_order_amount',
                        'description' => 'sw.min_order_amount',
                        'decoration_class' => 'sw_size_3',
                    ],
                ],
                'decoration_class' => 'control-icon sw_settings_icon',
            ],
            [
                'items' => [
                    [
                        'type' => 'setting',
                        'name' => 'show_out_of_stock_products',
                        'description' => 'sw.show_out_of_stock_products',
                    ],
                    [
                        'type' => 'setting',
                        'name' => 'enable_quick_view',
                        'description' => 'sw.enable_quick_view',
                    ],
                ],
                'decoration_class' => 'control-icon sw_kiosk_icon',
            ],
            [
                'items' => [
                    [
                        'type' => 'setting',
                        'name' => $tracking,
                        'description' => 'sw.enable_inventory_tracking',
                    ],
                    [
                        'type' => 'setting',
                        'name' => 'allow_negative_amount',
                        'description' => 'sw.allow_negative_amount',
                    ],
                ],
                'decoration_class' => 'control-icon sw_plus_one_icon',
            ],
        ],
        'extra' => 'views/setup_wizard/components/tabs/settings.tpl',
    ],
];

$schema['business_model'] = [
    'position' => 20,
    'title'    => 'sw.business_model',
    'header'   => 'sw.text_business_model_header',
    'extra'    => 'views/setup_wizard/components/tabs/business_model.tpl',
    'sections' => [
        [
            'items' => [
                [
                    'type'             => 'setting',
                    'name'             => 'collect_taxes_from_vendors',
                    'description'      => 'sw.collect_taxes_from_vendors',
                    'decoration_class' => 'sw_size_2',
                ]
            ],
        ]
    ]
];

$schema['vendors'] = [
    'position'           => 30,
    'title'              => 'sw.vendors',
    'header'             => 'sw.text_vendors_header',
    'show_submit_button' => YesNo::YES,
    'sections'           => [
        [
            'header'       => 'sw.vendor_registration_flow',
            'template'     => 'views/setup_wizard/components/tabs/sections/vendor_registration_flow.tpl',
            'setting_data' => true,
        ],
        [
            'header'       => 'sw.vendor_panel',
            'template'     => 'views/setup_wizard/components/tabs/sections/vendor_panel_configurator.tpl',
            'setting_data' => true,
        ],
        [
            'header'       => 'sw.location',
            'template'     => 'views/setup_wizard/components/tabs/sections/location.tpl',
            'setting_data' => true,
        ],
        [
            'header'       => 'sw.vendor_data_premoderation',
            'template'     => 'views/setup_wizard/components/tabs/sections/vendor_data_premoderation.tpl',
            'setting_data' => true,
        ],
        [
            'header' => 'sw.accounting',
            'items'  => [
                [
                    'type'             => 'setting',
                    'name'             => 'lowers_allowed_balance',
                    'section'          => 'vendor_debt_payout',
                    'description'      => 'sw.lowers_allowed_balance',
                    'decoration_class' => 'sw_size_2',
                ],
                [
                    'type'             => 'setting',
                    'name'             => 'grace_period_to_refill_balance',
                    'section'          => 'vendor_debt_payout',
                    'description'      => 'sw.grace_period_to_refill_balance',
                    'decoration_class' => 'sw_size_2',
                ],
            ],
        ]
    ]
];

return $schema;
