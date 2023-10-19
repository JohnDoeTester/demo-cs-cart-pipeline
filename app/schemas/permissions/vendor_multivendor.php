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

use Tygh\Enum\CustomizationModes;
use Tygh\Enum\UserTypes;
use Tygh\Enum\YesNo;
use Tygh\Registry;

$schema = [
    'default_permission' => false,
    'controllers' => [
        'auth' => [
            'permissions' => true,
        ],
        'index' => [
            'permissions' => true,
        ],
        'elf_connector' => [
            'permissions' => true,
        ],

        'order_management' => [
            'modes' => [
                'options' => [
                    'permissions' => true,
                ]
            ]
        ],

        'profiles' => [
            'modes' => [
                'update_cards' => [
                    'permissions' => false
                ],
                'delete_profile' => [
                    'permissions' => false
                ],
                'delete_card' => [
                    'permissions' => false
                ],
                'request_usergroup' => [
                    'permissions' => false
                ],
                'manage' => [
                    'param_permissions' => [
                        'user_type' => [
                            'P' => false,
                        ],
                        'default_permission' => true,
                    ],
                    'condition' => [
                        'user_type' => [
                            UserTypes::ADMIN  => [
                                'operator' => 'and',
                                'function' => ['fn_check_permission_manage_profiles', UserTypes::ADMIN],
                            ],
                            UserTypes::VENDOR => [
                                'operator' => 'and',
                                'function' => ['fn_check_permission_manage_profiles', UserTypes::VENDOR],
                            ],
                        ]
                    ],
                ],
                'view_product_as_user' => [
                    'permissions' => true,
                ],
                'act_as_user' => [
                    'permissions' => false,
                    'condition' => [
                        'operator' => 'or',
                        'function' => ['fn_check_permission_act_as_user'],
                    ],
                ],
                'login_as_vendor' => [
                    'permissions' => false
                ],
                'm_delete' => [
                    'param_permissions' => [
                        'user_type' => [
                            UserTypes::CUSTOMER => false,
                        ],
                    ],
                    'default_permission' => true,
                ],
                'anonymize' => [
                    'permissions' => false
                ],
            ],
            'permissions' => true,
        ],
        'companies' => [
            'modes' => [
                'add' => [
                    'permissions' => false
                ],
                'invite' => [
                    'permissions' => false
                ],
                'invitations' => [
                    'permissions' => false
                ],
                'm_delete_invitations' => [
                    'permissions' => false,
                ],
                'delete_invitation' => [
                    'permissions' => false,
                ],
                'delete' => [
                    'permissions' => false
                ],
                'update_status' => [
                    'permissions' => false
                ],
                'm_activate' => [
                    'permissions' => false
                ],
                'm_disable' => [
                    'permissions' => false
                ],
                'm_delete' => [
                    'permissions' => false
                ],
                'export_range' => [
                    'permissions' => false,
                ],
                'update' => [
                    'permissions' => ['GET' => true, 'POST' => true],
                ],
                'm_update_statuses' => [
                    'permissions' => false,
                ],
                'merge' => [
                    'permissions' => false,
                ]
            ],
            'permissions' => true,
        ],
        'profile_fields' => [
            /*'modes' => [
                'manage' => [
                    'permissions' => true
                ],
            ],*/
            'permissions' => false,
        ],
        'usergroups' => [
            /*'modes' => [
                'manage' => [
                    'permissions' => true
                ],
                'assign_privileges' => [
                    'permissions' => ['GET' => true, 'POST' => false],
                ],
                'update_status' => [
                    'permissions' => true,
                ],
            ],*/
            'permissions' => false,
        ],

        'sales_reports' => [
            'modes' => [
                'view' => [
                    'permissions' => true,
                ],
                'set_report_view' => [
                    'permissions' => true,
                ],
            ],
            'permissions' => false,
        ],

        'categories' => [
            'modes' => [
                'delete' => [
                    'permissions' => false
                ],
                // Why .add was true ???
                'add' => [
                    'permissions' => false
                ],
                'm_add' => [
                    'permissions' => false
                ],
                'm_update' => [
                    'permissions' => false
                ],
                'picker' => [
                    'permissions' => true
                ],
                'm_delete' => [
                    'permissions' => false
                ],
                'm_activate' => [
                    'permissions' => false
                ],
                'm_disable' => [
                    'permissions' => false
                ],
                'm_hide' => [
                    'permissions' => false
                ],
                'get_categories_list' => [
                    'permissions' => true
                ],
            ],
            'permissions' => ['GET' => true, 'POST' => false],
        ],

        'taxes' => [
            'modes' => [
                'update' => [
                    'permissions' => ['GET' => true, 'POST' => false],
                ],
                'manage' => [
                    'permissions' => ['GET' => true, 'POST' => false],
                ],
            ],
            'permissions' => false,
        ],

        'image' => [
            'modes' => [
                'barcode' => [
                    'permissions' => true,
                ],
                'delete_image' => [
                    'permissions' => true,
                ],
                'thumbnail' => [
                    'permissions' => true,
                ],
                'upload' => [
                    'permissions' => true,
                ],
            ],
            'permissions' => false,
        ],

        'search' => [
            'modes' => [
                'results' => [
                    'permissions' => true,
                ],
            ],
            'permissions' => false,
        ],

        'states' => [
            'modes' => [
                'manage' => [
                    'permissions' => true,
                ],
            ],
            'permissions' => false,
        ],

        'countries' => [
            'modes' => [
                'manage' => [
                    'permissions' => ['GET' => true, 'POST' => false],
                ],
            ],
            'permissions' => false,
        ],

        'destinations' => [
            'modes' => [
                'update' => [
                    'permissions' => ['GET' => true, 'POST' => false],
                ],
                'manage' => [
                    'permissions' => ['GET' => true, 'POST' => false],
                ],
                'selector' => [
                    'permissions' => ['GET' => true, 'POST' => false],
                ],
            ],
            'permissions' => false,
        ],

        'localizations' => [
            /*'modes' => [
                'update' => [
                    'permissions' => ['GET' => true, 'POST' => false],
                ],
                'manage' => [
                    'permissions' => true,
                ],
            ],*/
            'permissions' => false,
        ],

        'languages' => [
            /*'modes' => [
                'manage' => [
                    'permissions' => true,
                ],
            ],*/
            'permissions' => false,
        ],

        'product_features' => [
            'modes' => [
                'update' => [
                    'permissions' => ['GET' => true, 'POST' => false],
                ],
                'manage' => [
                    'permissions' => ['GET' => true, 'POST' => false],
                ],
                'groups' => [
                    'permissions' => ['GET' => true, 'POST' => false],
                ],
                'get_features_list' => [
                    'permissions' => true,
                ],
                'get_feature_variants_list' => [
                    'permissions' => true,
                ],
                'get_variants_list' => [
                    'permissions' => true,
                ],
                'get_variants' => [
                    'permissions' => ['GET' => true, 'POST' => false],
                ]
            ],
            'permissions' => false,
        ],

        'statuses' => [
            /*'modes' => [
                'update' => [
                    'permissions' => ['GET' => true, 'POST' => false],
                ],
                'manage' => [
                    'permissions' => true,
                ],
            ],*/
            'permissions' => false,
        ],

        'currencies' => [
            'modes' => [
                'update' => [
                    'permissions' => ['GET' => true, 'POST' => false],
                ],
                'manage' => [
                    'permissions' => true,
                ],
            ],
            'permissions' => false,
        ],
        'exim' => [
            'modes' => [
                'export' => [
                    'param_permissions' => [
                        'section' => [
                            'features'     => false,
                            'orders'       => true,
                            'products'     => true,
                            'translations' => false,
                            'users'        => false,
                            'states'       => false,
                        ],
                    ],
                ],
                'import' => [
                    'param_permissions' => [
                        'section' => [
                            'features'     => false,
                            'orders'       => false,
                            'products'     => true,
                            'translations' => false,
                            'users'        => false,
                            'states'       => false,
                        ],
                    ]
                ],
            ],
            'permissions' => true,
        ],

        'product_filters' => [
            'modes' => [
                'update' => [
                    'permissions' => ['GET' => true, 'POST' => false],
                ],
                'manage' => [
                    'permissions' => ['GET' => true, 'POST' => false],
                ],
                'delete' => [
                    'permissions' => false,
                ],
                'm_delete' => [
                    'permissions' => false,
                ],
                'm_create_by_features' => [
                    'permissions' => false,
                ],
                'm_update_statuses' => [
                    'permissions' => false,
                ],
                'm_update_categories' => [
                    'permissions' => false,
                ],
            ],
            'permissions' => true,
        ],

        'orders' => [
            'modes' => [
                'details' => [
                    'permissions' => true,
                ],
                'delete' => [
                    'permissions' => false,
                ],
                'delete_orders' => [
                    'permissions' => false,
                ],
                'manage' => [
                    'permissions' => true,
                ],
                'export_range' => [
                    'permissions' => true,
                ],
                'modify_invoice' => [
                    'permissions' => false,
                ],
            ],
            'permissions' => true,
        ],

        'shippings' => [
            'permissions' => true,
        ],

        'tags' => [
            'modes' => [
                'list' => [
                    'permissions' => true,
                ],
            ],
            'permissions' => false,
        ],

        'pages' => [
            'modes' => [
                /*'m_add' => [
                    'permissions' => false,
                ],
                'm_update' => [
                    'permissions' => false,
                ],*/
            ],
            'permissions' => true,
        ],

        'products' => [
            'modes' => [
            ],
            'permissions' => true,
        ],

        'product_options' => [
            'permissions' => true,
        ],

        'promotions' => [
            'permissions' => false,
        ],

        'shipments' => [
            'permissions' => true,
        ],

        'attachments' => [
            'permissions' => true,
        ],

        'block_manager' => [
            'modes' => []
        ],

        'tools' => [
            'modes' => [
                'update_position' => [
                    'param_permissions' => [
                        'table' => [
                            'images_links'  => true,
                            'custom_blocks' => true,
                        ]
                    ]
                ],
                'update_status' => [
                    'param_permissions' => [
                        'table' => [
                            'shippings'          => true,
                            'products'           => true,
                            'product_options'    => true,
                            'attachments'        => true,
                            'product_files'      => true,
                            'pages'              => true,
                            'shipments'          => true,
                            //'users'            => true,
                            /*'categories'       => 'manage_catalog',
                            'states'             => 'manage_locations',
                            'usergroups'         => 'manage_usergroups',
                            'currencies'         => 'manage_currencies',
                            'blocks'             => 'edit_files',
                            'taxes'              => 'manage_taxes',
                            'promotions'         => 'manage_promotions',
                            'static_data'        => 'manage_static_data',
                            'statistics_reports' => 'manage_reports',
                            'countries'          => 'manage_locations',

                            'languages'          => 'manage_languages',
                            'sitemap_sections'   => 'manage_sitemap',
                            'localizations'      => 'manage_locations',
                            'products'           => 'manage_catalog',
                            'destinations'       => 'manage_locations',
                            'product_options'    => 'manage_catalog',
                            'product_features'   => 'manage_catalog',
                            'payments'           => 'manage_payments',
                            'product_filters'    => 'manage_catalog',
                            'product_files'      => 'manage_catalog'
                            */
                        ]
                    ]
                ],
                'cleanup_history' => [
                    'permissions' => true
                ],
                'view_changes' => [
                    'permissions' => false
                ]
            ]
        ],
        'logs' => [
            'permissions' => true,
        ],
        'debugger' => [
            'permissions' => true,
        ],
        'file_editor' => [
            'permissions' => true
        ],
        'themes' => [
            'modes' => [
                'update_logos' => [
                    'permissions' => true
                ]
            ],
        ],
        'customization' => [
            'modes' => [
                'update_mode' => [
                    'param_permissions' => [
                        'type' => [
                            CustomizationModes::THEME_EDITOR  => true,
                            CustomizationModes::BLOCK_MANAGER => true,
                        ]
                    ],
                    'condition' => [
                        'type' => [
                            CustomizationModes::THEME_EDITOR => [
                                'operator' => 'and',
                                'function' => ['fn_get_styles_owner'],
                            ],
                            CustomizationModes::BLOCK_MANAGER => [
                                'operator' => 'and',
                                'function' => ['fn_vendor_customization_permissions'],
                            ],
                        ],
                    ],
                ],
                'disable_mode' => [
                    'permissions' => true
                ]
            ],
        ],

        'notifications' => [
            'permissions' => true,
        ],

        'notifications_center' => [
            'permissions' => true,
        ],
        'bottom_panel' => [
            'permissions' => true
        ],
        'storefronts' => [
            'modes' => [
                'picker' => [
                    'permissions' => true,
                ],
            ],
        ],
        'sync_data' => [
            'modes' => [
                'manage' => [
                    'permissions' => false,
                    'condition'   => [
                        'operator' => 'or',
                        'function' => ['fn_check_permission_sync_data'],
                    ],
                ]
            ]
        ],
        'phone_masks' => [
            'permissions' => true,
        ],
        'custom_menu' => [
            'modes' => [
                'update_block' => [
                    'permissions' => false,
                    'condition'   => [
                        'operator' => 'or',
                        'function' => ['fn_vendor_customization_permissions'],
                    ],
                ],
                'delete_block' => [
                    'permissions' => false,
                    'condition'   => [
                        'operator' => 'or',
                        'function' => ['fn_vendor_customization_permissions'],
                    ],
                ],
                'update_position' => [
                    'permissions' => false,
                    'condition'   => [
                        'operator' => 'or',
                        'function' => ['fn_vendor_customization_permissions'],
                    ],
                ],
            ],
        ],
    ],
    'export' => [
        'sections' => [
            'translations' => [
                'permission' => false,
            ],
            'users' => [
                'permission' => false,
            ],
            'features' => [
                'permission' => false,
            ],
            'vendors' => [
                'permission' => false,
            ],
            'states' => [
                'permission' => false,
            ],
        ],
        'patterns' => [
            'google' => [
                'permission' => false,
            ],
        ],
    ],
    'import' => [
        'sections' => [
            'translations' => [
                'permission' => false,
            ],
            'orders' => [
                'permission' => false,
            ],
            'users' => [
                'permission' => false,
            ],
            'features' => [
                'permission' => false,
            ],
            'vendors' => [
                'permission' => false,
            ],
            'states' => [
                'permission' => false,
            ],
        ],
        'patterns' => [],
    ],
];

if (YesNo::toBool(Registry::get('settings.Vendors.allow_vendor_manage_features'))) {
    $product_features_management_modes = [
        'update_status',
        'update',
        'add',
        'quick_add',
        'manage',
        'groups',
        'get_variants',
        'delete',
        'm_update_statuses',
        'm_set_group',
        'm_set_categories',
        'm_set_display',
        'm_delete',
    ];
    foreach ($product_features_management_modes as $mode) {
        $schema['controllers']['product_features']['modes'][$mode]['permissions']['POST'] = true;
    }

    $schema['controllers']['product_features']['modes']['add']['permissions']['GET'] = true;
    $schema['controllers']['product_features']['modes']['quick_add']['permissions']['GET'] = true;

    foreach (['export', 'import'] as $mode) {
        $schema['controllers']['exim']['modes'][$mode]['param_permissions']['section']['features'] = true;
        $schema[$mode]['sections']['features']['permission'] = true;
    }
}

foreach (
    [
        'manage',
        'update_block',
        'update_status',
        'snapping',
        'grid',
        'block_selection',
        'manage_in_tab',
        'set_custom_container',
        'update_grid'
    ] as $mode
) {
    $schema['controllers']['block_manager']['modes'][$mode] = [
        'permissions' => false,
        'condition'   => [
            'operator' => 'or',
            'function' => ['fn_get_blocks_owner'],
        ],
    ];
}

$schema['controllers']['block_manager']['modes']['update_custom_block'] = [
    'permissions' => false,
    'condition'   => [
        'operator' => 'or',
        'function' => ['fn_vendor_customization_permissions'],
    ],
];

foreach (['manage', 'styles'] as $mode) {
    $schema['controllers']['themes']['modes'][$mode] = [
        'permissions' => false,
        'condition'   => [
            'operator' => 'or',
            'function' => ['fn_get_menu_item_styles_owner'],
        ],
    ];
}

return $schema;
