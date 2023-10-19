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

use Tygh\Registry;
use Tygh\Enum\UserTypes;

defined('BOOTSTRAP') or die('Access denied');

/*
    Every item can has any additional attributes.
    The base HTML struct of menu item is:
        <li class="some classes">
            <a href="some.html">Title</a>
        </li>

    So you can use the following array structure to specify your attrs:
    'addons' => array(
        'title' => __('addons_title'),
        'href' => 'addons.manage',
        'position' => 100,
        'attrs' => array(
            'class' => 'test-addon-class', // Classes for <li>
            'main' => array( // Attributes for <li>
                'custom-li-attr' => 'my-li-attr',
            ),
            'class_href' => 'test-addon-class', // Classes for <a>
            'href' => array( // Attributes for <a>
                'custom-a-attr' => 'my-a-attr',
            ),
        ),
    ),

    As a result you will get the following HTML code:
    <li class="some classes test-addon-class" custom-li-attr="my-li-attr">
        <a href="some.html" custom-a-attr="my-a-attr">Title</a>
    </li>
*/

$schema = [
    'top' => [
        'addons' => [
            'href'  => 'addons.manage',
            'items' => [
                'downloaded_add_ons' => [
                    'href'     => 'addons.manage',
                    'position' => 1,
                ],
                'addons.upgrades' => [
                    'href'     => 'upgrade_center.manage',
                    'position' => 10,
                ],
                'upgrades' => [
                    'type'     => 'divider',
                    'position' => 15
                ],
                'addon_market' => [
                    'href'     => 'addons.market',
                    'position' => 20,
                ],
            ],
        ],
        'administration' => [
            'items' => [
                'addons_divider' => [
                    'type' => 'divider',
                    'position' => 110,
                ],
                'payment_methods' => [
                    'href' => 'payments.manage',
                    'position' => 200,
                ],
                'shippings_taxes' => [
                    'href' => 'shippings.manage',
                    'type' => 'title',
                    'position' => 300,
                    'subitems' => [
                        'shipping_methods' => [
                            'href' => 'shippings.manage',
                            'position' => 100,
                        ],
                        'taxes' => [
                            'href' => 'taxes.manage',
                            'position' => 150,
                        ],
                        'locations_divider' => [
                            'type' => 'divider',
                            'position' => 200,
                        ],
                        'countries' => [
                            'href' => 'countries.manage',
                            'position' => 300,
                        ],
                        'states' => [
                            'href' => 'states.manage',
                            'position' => 400,
                        ],
                        'locations' => [
                            'title' => __('rate_areas'),
                            'href' => 'destinations.manage',
                            'position' => 500,
                        ],
                        'localizations' => [
                            'href' => 'localizations.manage',
                            'position' => 600,
                        ],
                    ],
                ],
                'statuses_management' => [
                    'href' => 'statuses.manage',
                    'type' => 'title',
                    'position' => 400,
                    'subitems' => [
                        'order_statuses' => [
                            'href' => 'statuses.manage?type=' . STATUSES_ORDER,
                            'position' => 100,
                        ],
                        'shipment_statuses' => [
                            'href' => 'statuses.manage?type=' . STATUSES_SHIPMENT,
                            'position' => 200,
                        ],
                    ],
                ],
                'statuses_divider' => [
                    'type' => 'divider',
                    'position' => 410,
                ],
                'profile_fields' => [
                    'href' => 'profile_fields.manage',
                    'position' => 500,
                ],
                'notifications' => [
                    'href' => 'notification_settings.manage',
                    'type' => 'title',
                    'position' => 505,
                    'subitems' => [
                        'customer_notifications' => [
                            'href' => 'notification_settings.manage?receiver_type=' . UserTypes::CUSTOMER,
                            'position' => 100,
                        ],
                        'admin_notifications' => [
                            'href' => 'notification_settings.manage?receiver_type=' . UserTypes::ADMIN,
                            'position' => 200,
                        ],
                        'code_snippets' => [
                            'href' => 'email_templates.snippets',
                            'position' => 400,
                        ],
                        'documents' => [
                            'href' => 'documents.manage',
                            'position' => 500,
                        ],
                    ],
                ],
                'profile_fields_divider' => [
                    'type' => 'divider',
                    'position' => 510,
                ],
                'currencies' => [
                    'href' => 'currencies.manage',
                    'position' => 600,
                ],
                'languages' => [
                    'href' => 'languages.manage',
                    'type' => 'title',
                    'title' => __('texts_languages'),
                    'position' => 700,
                    'subitems' => [
                        'edit_on_site' => [
                            'href' => 'customization.update_mode?type=live_editor&status=enable',
                            'attrs' => [
                                'href' => [ // Attributes for <a>
                                    'target' => '_blank',
                                ],
                            ],
                            'position' => 10,
                        ],
                        'edit_on_site_divider' => [
                            'type' => 'divider',
                            'position' => 20,
                        ],
                        'translations' => [
                            'title' => __('edit_texts'),
                            'href' => 'languages.translations',
                            'position' => 100,
                        ],
                        'manage_languages' => [
                            'href' => 'languages.manage',
                            'position' => 200,
                        ],
                    ],
                ],
                'languages_divider' => [
                    'type' => 'divider',
                    'position' => 710,
                ],
                'logs' => [
                    'href' => 'logs.manage',
                    'position' => 800,
                ],
                'logs_divider' => [
                    'type' => 'divider',
                    'position' => 900,
                ],
                'files' => [
                    'href' => 'file_editor.manage',
                    'position' => 990,
                ],
                'backup_restore' => [
                    'href' => 'datakeeper.manage',
                    'position' => 1000,
                ],
                'storage' => [
                    'href' => 'storage.index',
                    'type' => 'title',
                    'position' => 1100,
                    'subitems' => [
                        'cdn_settings' => [
                            'href' => 'storage.cdn',
                            'position' => 100,
                        ],
                        'configure_divider' => [
                            'type' => 'divider',
                            'position' => 110,
                        ],
                        'clear_cache' => [
                            'href' => 'storage.clear_cache?redirect_url=%CURRENT_URL',
                            'position' => 200,
                        ],
                        'clear_thumbnails' => [
                            'href' => 'storage.clear_thumbnails?redirect_url=%CURRENT_URL',
                            'position' => 300,
                        ],
                    ],
                ],
                'storage_divider' => [
                    'type' => 'divider',
                    'position' => 1200,
                ],
                'import_data' => [
                    'href' => 'exim.import',
                    'position' => 1300,
                    'subitems' => [
                        'orders' => [
                            'href' => 'exim.import?section=orders',
                            'position' => 200,
                        ],
                        'products_deprecated' => [
                            'href' => 'exim.import?section=products',
                            'position' => 700,
                        ],
                        'features' => [
                            'href' => 'exim.import?section=features',
                            'position' => 100,
                        ],
                        'translations' => [
                            'href' => 'exim.import?section=translations',
                            'position' => 300,
                        ],
                        'states' => [
                            'href' => 'exim.import?section=states',
                            'position' => 400,
                        ],
                        'users' => [
                            'href' => 'exim.import?section=users',
                            'position' => 500,
                        ],
                    ],
                ],
                'export_data' => [
                    'href' => 'exim.export',
                    'position' => 1400,
                    'subitems' => [
                        'orders' => [
                            'href' => 'exim.export?section=orders',
                            'position' => 200,
                        ],
                        'products' => [
                            'href' => 'exim.export?section=products',
                            'position' => 300,
                        ],
                        'features' => [
                            'href' => 'exim.export?section=features',
                            'position' => 100,
                        ],
                        'translations' => [
                            'href' => 'exim.export?section=translations',
                            'position' => 400,
                        ],
                        'states' => [
                            'href' => 'exim.export?section=states',
                            'position' => 500,
                        ],
                        'users' => [
                            'href' => 'exim.export?section=users',
                            'position' => 600,
                        ],
                    ],
                ],
                'sync_data' => [
                    'href'     => 'sync_data.manage',
                    'position' => 1500,
                ],
                'sync_data_divider' => [
                    'type' => 'divider',
                    'position' => 1600,
                ],
                'file_changes_detector' => [
                    'href' => 'tools.view_changes?check_types=C,D',
                    'position' => 1700,
                ],
                'upgrade_center' => [
                    'href' => 'upgrade_center.manage',
                    'position' => 1800,
                ],
            ],
            'position' => 600,
        ],
        'design' => [
            'items' => [
                'themes' => [
                    'href' => 'themes.manage',
                    'position' => 100,
                ],
                'layouts' => [
                    'href' => 'block_manager.manage',
                    'position' => 200,
                    'subitems' => [
                        'layouts' => [
                            'href' => 'block_manager.manage',
                            'position' => 100,
                        ],
                        'manage_blocks' => [
                            'href' => 'block_manager.blocks',
                            'position' => 200,
                        ],
                    ],
                ],
                'templates' => [
                    'href' => 'templates.manage',
                    'position' => 300,
                ],
                'templates_divider' => [
                    'type' => 'divider',
                    'position' => 310,
                ],
                'menus' => [
                    'href' => 'menus.manage',
                    'alt' => 'static_data.manage?section=A',
                    'position' => 400,
                ],
                'product_tabs' => [
                    'href' => 'tabs.manage',
                    'position' => 500,
                ],
                'product_tabs_divider' => [
                    'type' => 'divider',
                    'position' => 510,
                ],
            ],
            'position' => 800,
        ],
        'settings' => [
            'items' => [
                'General' => [
                    'href' => 'settings.manage?section_id=General',
                    'position' => 100,
                    'type' => 'setting',
                ],
                'Appearance' => [
                    'href' => 'settings.manage?section_id=Appearance',
                    'position' => 200,
                    'type' => 'setting',
                ],
                'Appearance_divider' => [
                    'type' => 'divider',
                    'position' => 300,
                ],
                'Company' => [
                    'href' => 'settings.manage?section_id=Company',
                    'position' => 400,
                    'type' => 'setting',
                ],
                'Checkout' => [
                    'href' => 'settings.manage?section_id=Checkout',
                    'position' => 500,
                    'type' => 'setting',
                ],
                'Emails' => [
                    'href' => 'settings.manage?section_id=Emails',
                    'position' => 700,
                    'type' => 'setting',
                ],
                'Thumbnails' => [
                    'href' => 'settings.manage?section_id=Thumbnails',
                    'position' => 800,
                    'type' => 'setting',
                ],
                'Sitemap' => [
                    'href' => 'settings.manage?section_id=Sitemap',
                    'position' => 900,
                    'type' => 'setting',
                ],
                'Upgrade_center' => [
                    'href' => 'settings.manage?section_id=Upgrade_center',
                    'position' => 1000,
                    'type' => 'setting',
                ],
                'Upgrade_center_divider' => [
                    'type' => 'divider',
                    'position' => 1100,
                ],
                'Security' => [
                    'href' => 'settings.manage?section_id=Security',
                    'position' => 1200,
                    'type' => 'setting',
                ],
                'Image_verification_divider' => [
                    'type' => 'divider',
                    'position' => 1400,
                ],
                'Logging' => [
                    'href' => 'settings.manage?section_id=Logging',
                    'position' => 1500,
                    'type' => 'setting',
                ],
                'Reports' => [
                    'href' => 'settings.manage?section_id=Reports',
                    'position' => 1600,
                    'type' => 'setting',
                ],
                'Reports_divider' => [
                    'position' => 1610,
                    'type' => 'divider',
                ],
                'settings_wizard' => [
                    'href'     => 'settings_wizard.view',
                    'position' => 1700,
                    'title'    => __('settings_wizard'),
                ],
                'store_mode' => [
                    'position' => 999999,
                    'type' => 'title',
                    'href' => 'settings.change_store_mode',
                    'attrs' => [
                        'class_href' => 'cm-dialog-opener cm-dialog-auto-size',
                        'href' => [
                            'data-ca-target-id' => 'store_mode_dialog',
                        ],
                    ],
                ]
            ],
            'position' => 700,
        ],
    ],

    'central' => [
        'orders' => [
            'items' => [
                'view_orders' => [
                    'href' => 'orders.manage',
                    'alt' => 'order_management',
                    'position' => 100,
                ],
                'sales_reports' => [
                    'href' => 'sales_reports.view',
                    'position' => 200,
                ],
                'shipments' => [
                    'href' => 'shipments.manage',
                    'position' => 400,
                ],
                'users_carts' => [
                    'href' => 'cart.cart_list',
                    'position' => 500,
                ],
            ],
            'position' => 100,
        ],
        'products' => [
            'title' => __('products_menu_title'),
            'items' => [
                'categories' => [
                    'href' => 'categories.manage',
                    'position' => 100,
                ],
                'products' => [
                    'href' => 'products.manage',
                    'alt' => 'product_options.inventory,product_options.exceptions,products.update,products.m_update,products.add',
                    'position' => 200,
                ],
                'features' => [
                    'href' => 'product_features.manage',
                    'position' => 300,
                ],
                'filters' => [
                    'href' => 'product_filters.manage',
                    'position' => 400,
                ],
                'options' => [
                    'href' => 'product_options.manage',
                    'position' => 500,
                ],
            ],
            'position' => 200,
        ],
        'customers' => [
            'title' => __('customers_menu_title'),
            'items' => [
                'administrators' => [
                    'href' => 'profiles.manage?user_type=A',
                    'alt' => 'profiles.update?user_type=A',
                    'position' => 200,
                ],
                'customers' => [
                    'href' => 'profiles.manage?user_type=C',
                    'alt' => 'profiles.update?user_type=C',
                    'position' => 300,
                ],
                'usergroups' => [
                    'href' => 'usergroups.manage',
                    'position' => 800,
                ],
            ],
            'position' => 300,
        ],
        'website' => [
            'items' => [
                'pages' => [
                    'href' => 'pages.manage?get_tree=multi_level',
                    'alt'  => 'pages.manage,pages.update,pages.add',
                    'position' => 100,
                ],
                'seo' => [
                    'href' => 'robots.manage',
                    'position' => 500,
                    'subitems' => [
                        'seo_robots' => [
                            'href' => 'robots.manage',
                            'position' => 600
                        ],
                    ]
                ],
                'sitemap' => [
                    'href' => 'sitemap.manage',
                    'position' => 1000,
                ],
            ],
            'position' => 500,
        ],
        'marketing' => [
            'items' => [
                'promotions' => [
                    'href' => 'promotions.manage',
                    'position' => 100,
                ],
            ],
            'position' => 400,
        ],
    ],
];

$profile_types = fn_get_schema('profiles', 'profile_types');
if (count($profile_types) > 1) {
    $schema['top']['administration']['items']['profile_fields']['type'] = 'title';
    foreach ($profile_types as $profile_code => $profile_type) {
        $schema['top']['administration']['items']['profile_fields']['subitems']['profile_types_section_' . $profile_type['name']] = [
            'href' => 'profile_fields.manage?profile_type=' . $profile_code,
        ];
    }
}

if (empty(fn_get_schema('sync_data', 'sync_data'))) {
    unset($schema['top']['administration']['items']['sync_data']);
}

if (Registry::get('config.tweaks.disable_localizations') == true) {
    unset($schema['top']['administration']['items']['shippings_taxes']['subitems']['localizations']);
}

if (Registry::get('settings.Appearance.email_templates') == 'old') {
    unset($schema['top']['design']['items']['email_templates']);
    unset($schema['top']['design']['items']['documents']);
    unset($schema['top']['administration']['items']['notifications']['subitems']['documents']);
    unset($schema['top']['administration']['items']['notifications']['subitems']['code_snippets']);
}

return $schema;
