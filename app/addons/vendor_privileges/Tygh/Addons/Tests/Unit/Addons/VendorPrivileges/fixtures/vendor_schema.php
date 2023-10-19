<?php

return array(
    'default_permission' => false,
    'controllers' => array(
        'auth' => array(
            'permissions' => true,
            'permissions_blocked' => true,
        ) ,
        'index' => array(
            'permissions' => true,
            'permissions_blocked' => true,
        ) ,
        'elf_connector' => array(
            'permissions' => true,
        ) ,
        'profiles' => array(
            'modes' => array(
                'update_cards' => array(
                    'permissions' => false,
                ) ,
                'delete_profile' => array(
                    'permissions' => false,
                ) ,
                'delete_card' => array(
                    'permissions' => false,
                ) ,
                'request_usergroup' => array(
                    'permissions' => false,
                ) ,
                'manage' => array(
                    'param_permissions' => array(
                        'user_type' => array(
                            'P' => false,
                        ) ,
                        'default_permission' => true,
                    ) ,
                    'condition' => array(
                        'user_type' => array(
                            'A' => array(
                                'operator' => 'and',
                                'function' => array(
                                    0 => 'fn_check_permission_manage_profiles',
                                    1 => 'A',
                                ) ,
                            ) ,
                            'V' => array(
                                'operator' => 'and',
                                'function' => array(
                                    0 => 'fn_check_permission_manage_profiles',
                                    1 => 'V',
                                ) ,
                            ) ,
                        ) ,
                    ) ,
                    'permissions_blocked' => false,
                ) ,
                'view_product_as_user' => array(
                    'permissions' => true,
                ) ,
                'act_as_user' => array(
                    'permissions' => false,
                    'condition' => array(
                        'operator' => 'or',
                        'function' => array(
                            0 => 'fn_check_permission_act_as_user',
                        ) ,
                    ) ,
                ) ,
            ) ,
            'permissions' => true,
            'permissions_blocked' => true,
        ) ,
        'companies' => array(
            'modes' => array(
                'add' => array(
                    'permissions' => false,
                ) ,
                'delete' => array(
                    'permissions' => false,
                ) ,
                'update_status' => array(
                    'permissions' => false,
                ) ,
                'm_activate' => array(
                    'permissions' => false,
                ) ,
                'm_disable' => array(
                    'permissions' => false,
                ) ,
                'm_delete' => array(
                    'permissions' => false,
                ) ,
                'export_range' => array(
                    'permissions' => false,
                ) ,
                'update' => array(
                    'permissions' => array(
                        'GET' => true,
                        'POST' => true,
                    ) ,
                ) ,
                'get_companies_list' => array(
                    'permissions' => true,
                    'permissions_blocked' => true,
                ) ,
            ) ,
            'permissions' => true,
        ) ,
        'profile_fields' => array(
            'permissions' => false,
        ) ,
        'usergroups' => array(
            'permissions' => false,
        ) ,
        'sales_reports' => array(
            'modes' => array(
                'view' => array(
                    'permissions' => true,
                ) ,
                'set_report_view' => array(
                    'permissions' => true,
                ) ,
            ) ,
            'permissions' => false,
        ) ,
        'categories' => array(
            'modes' => array(
                'delete' => array(
                    'permissions' => false,
                ) ,
                'add' => array(
                    'permissions' => false,
                ) ,
                'm_add' => array(
                    'permissions' => false,
                ) ,
                'm_update' => array(
                    'permissions' => false,
                ) ,
                'picker' => array(
                    'permissions' => true,
                ) ,
            ) ,
            'permissions' => array(
                'GET' => true,
                'POST' => false,
            ) ,
        ) ,
        'taxes' => array(
            'modes' => array(
                'update' => array(
                    'permissions' => array(
                        'GET' => true,
                        'POST' => false,
                    ) ,
                ) ,
                'manage' => array(
                    'permissions' => array(
                        'GET' => true,
                        'POST' => false,
                    ) ,
                ) ,
            ) ,
            'permissions' => false,
        ) ,
        'image' => array(
            'modes' => array(
                'barcode' => array(
                    'permissions' => true,
                ) ,
                'delete_image' => array(
                    'permissions' => true,
                ) ,
                'thumbnail' => array(
                    'permissions' => true,
                ) ,
                'upload' => array(
                    'permissions' => true,
                ) ,
            ) ,
            'permissions' => false,
        ) ,
        'search' => array(
            'modes' => array(
                'results' => array(
                    'permissions' => true,
                ) ,
            ) ,
            'permissions' => false,
        ) ,
        'states' => array(
            'modes' => array(
                'manage' => array(
                    'permissions' => true,
                ) ,
            ) ,
            'permissions' => false,
        ) ,
        'countries' => array(
            'modes' => array(
                'manage' => array(
                    'permissions' => array(
                        'GET' => true,
                        'POST' => false,
                    ) ,
                ) ,
            ) ,
            'permissions' => false,
        ) ,
        'destinations' => array(
            'modes' => array(
                'update' => array(
                    'permissions' => array(
                        'GET' => true,
                        'POST' => false,
                    ) ,
                ) ,
                'manage' => array(
                    'permissions' => array(
                        'GET' => true,
                        'POST' => false,
                    ) ,
                ) ,
            ) ,
            'permissions' => false,
        ) ,
        'localizations' => array(
            'permissions' => false,
        ) ,
        'languages' => array(
            'permissions' => false,
        ) ,
        'product_features' => array(
            'modes' => array(
                'update' => array(
                    'permissions' => array(
                        'GET' => true,
                        'POST' => false,
                    ) ,
                ) ,
                'manage' => array(
                    'permissions' => array(
                        'GET' => true,
                        'POST' => false,
                    ) ,
                ) ,
                'groups' => array(
                    'permissions' => array(
                        'GET' => true,
                        'POST' => false,
                    ) ,
                ) ,
                'get_feature_variants_list' => array(
                    'permissions' => true,
                ) ,
                'get_variants_list' => array(
                    'permissions' => true,
                ) ,
                'get_variants' => array(
                    'permissions' => array(
                        'GET' => true,
                        'POST' => false,
                    ) ,
                ) ,
            ) ,
            'permissions' => false,
        ) ,
        'statuses' => array(
            'permissions' => false,
        ) ,
        'currencies' => array(
            'modes' => array(
                'update' => array(
                    'permissions' => array(
                        'GET' => true,
                        'POST' => false,
                    ) ,
                ) ,
                'manage' => array(
                    'permissions' => true,
                ) ,
            ) ,
            'permissions' => false,
        ) ,
        'exim' => array(
            'modes' => array(
                'export' => array(
                    'param_permissions' => array(
                        'section' => array(
                            'features' => false,
                            'orders' => false,
                            'products' => true,
                            'translations' => false,
                            'users' => false,
                            'subscribers' => false,
                        ),
                    ),
                ) ,
                'import' => array(
                    'param_permissions' => array(
                        'section' => array(
                            'features' => false,
                            'orders' => false,
                            'products' => true,
                            'translations' => false,
                            'users' => false,
                            'subscribers' => false,
                        ),
                    ),
                ) ,
            ) ,
            'permissions' => true,
        ) ,
        'product_filters' => array(
            'modes' => array(
                'update' => array(
                    'permissions' => array(
                        'GET' => true,
                        'POST' => false,
                    ) ,
                ) ,
                'manage' => array(
                    'permissions' => array(
                        'GET' => true,
                        'POST' => false,
                    ) ,
                ) ,
                'delete' => array(
                    'permissions' => false,
                ) ,
            ) ,
            'permissions' => true,
        ) ,
        'orders' => array(
            'modes' => array(
                'details' => array(
                    'permissions' => true,
                ) ,
                'delete' => array(
                    'permissions' => false,
                ) ,
                'delete_orders' => array(
                    'permissions' => false,
                ) ,
                'manage' => array(
                    'permissions' => true,
                ) ,
            ) ,
            'permissions' => true,
        ) ,
        'shippings' => array(
            'permissions' => true,
        ) ,
        'tags' => array(
            'modes' => array(
                'list' => array(
                    'permissions' => true,
                ) ,
            ) ,
            'permissions' => false,
        ) ,
        'pages' => array(
            'modes' => array() ,
            'permissions' => true,
        ) ,
        'products' => array(
            'modes' => array() ,
            'permissions' => true,
        ) ,
        'product_options' => array(
            'permissions' => true,
        ) ,
        'promotions' => array(
            'permissions' => false,
        ) ,
        'shipments' => array(
            'permissions' => true,
        ) ,
        'attachments' => array(
            'permissions' => true,
        ) ,
        'block_manager' => array(
            'modes' => array(
                'manage' => array(
                    'permissions' => false,
                    'condition' => array(
                        'operator' => 'or',
                        'function' => array(
                            0 => 'fn_get_blocks_owner',
                        ) ,
                    ) ,
                ) ,
                'update_block' => array(
                    'permissions' => false,
                    'condition' => array(
                        'operator' => 'or',
                        'function' => array(
                            0 => 'fn_get_blocks_owner',
                        ) ,
                    ) ,
                ) ,
                'update_status' => array(
                    'permissions' => false,
                    'condition' => array(
                        'operator' => 'or',
                        'function' => array(
                            0 => 'fn_get_blocks_owner',
                        ) ,
                    ) ,
                ) ,
                'snapping' => array(
                    'permissions' => false,
                    'condition' => array(
                        'operator' => 'or',
                        'function' => array(
                            0 => 'fn_get_blocks_owner',
                        ) ,
                    ) ,
                ) ,
                'grid' => array(
                    'permissions' => false,
                    'condition' => array(
                        'operator' => 'or',
                        'function' => array(
                            0 => 'fn_get_blocks_owner',
                        ) ,
                    ) ,
                ) ,
                'block_selection' => array(
                    'permissions' => false,
                    'condition' => array(
                        'operator' => 'or',
                        'function' => array(
                            0 => 'fn_get_blocks_owner',
                        ) ,
                    ) ,
                ) ,
                'manage_in_tab' => array(
                    'permissions' => false,
                    'condition' => array(
                        'operator' => 'or',
                        'function' => array(
                            0 => 'fn_get_blocks_owner',
                        ) ,
                    ) ,
                ) ,
                'set_custom_container' => array(
                    'permissions' => false,
                    'condition' => array(
                        'operator' => 'or',
                        'function' => array(
                            0 => 'fn_get_blocks_owner',
                        ) ,
                    ) ,
                ) ,
                'update_grid' => array(
                    'permissions' => false,
                    'condition' => array(
                        'operator' => 'or',
                        'function' => array(
                            0 => 'fn_get_blocks_owner',
                        ) ,
                    ) ,
                ) ,
            ) ,
        ) ,
        'tools' => array(
            'modes' => array(
                'update_position' => array(
                    'param_permissions' => array(
                        'table' => array(
                            'images_links' => true,
                        ) ,
                    ) ,
                ) ,
                'update_status' => array(
                    'param_permissions' => array(
                        'table' => array(
                            'shippings' => true,
                            'products' => true,
                            'product_options' => true,
                            'attachments' => true,
                            'product_files' => true,
                            'pages' => true,
                            'shipments' => true,
                            'call_requests' => true,
                            'subscribers' => false,
                        ) ,
                    ) ,
                ) ,
                'cleanup_history' => array(
                    'permissions' => true,
                ) ,
                'view_changes' => array(
                    'permissions' => false,
                ) ,
            ) ,
        ) ,
        'logs' => array(
            'permissions' => true,
        ) ,
        'debugger' => array(
            'permissions' => true,
        ) ,
        'file_editor' => array(
            'permissions' => true,
        ) ,
        'themes' => array(
            'modes' => array(
                'manage' => array(
                    'permissions' => false,
                    'condition' => array(
                        'operator' => 'or',
                        'function' => array(
                            0 => 'fn_get_styles_owner',
                        ) ,
                    ) ,
                ) ,
                'styles' => array(
                    'permissions' => false,
                    'condition' => array(
                        'operator' => 'or',
                        'function' => array(
                            0 => 'fn_get_styles_owner',
                        ) ,
                    ) ,
                ) ,
            ) ,
        ) ,
        'customization' => array(
            'modes' => array(
                'update_mode' => array(
                    'param_permissions' => array(
                        'type' => array(
                            'theme_editor' => array(
                                'permissions' => false,
                                'condition' => array(
                                    'operator' => 'or',
                                    'function' => array(
                                        0 => 'fn_get_styles_owner',
                                    ) ,
                                ) ,
                            ) ,
                        ) ,
                    ) ,
                ) ,
            ) ,
        ) ,
        'notifications' => array(
            'permissions' => true,
            'permissions_blocked' => true,
        ) ,
        'vendor_communication' => array(
            'permissions' => true,
            'modes' => array(
                'delete_thread' => array(
                    'permissions' => false,
                ) ,
                'm_delete_thread' => array(
                    'permissions' => false,
                ) ,
                'create_thread' => array(
                    'permissions' => false,
                ) ,
            ) ,
        ) ,
        'import_presets' => array(
            'permissions' => true,
        ) ,
        'advanced_import' => array(
            'permissions' => true,
        ) ,
        'premoderation' => array(
            'modes' => array(
                'products_approval' => array(
                    'permissions' => false,
                ) ,
            ) ,
        ) ,
        'vendor_plans' => array(
            'permissions' => false,
        ) ,
        'product_variations' => array(
            'permissions' => true,
        ) ,
        'call_requests' => array(
            'permissions' => true,
        ) ,
        'discussion' => array(
            'modes' => array(
                'add' => array(
                    'permissions' => true,
                ) ,
                'update' => array(
                    'permissions' => false,
                ) ,
                'delete' => array(
                    'permissions' => false,
                ) ,
                'm_delete' => array(
                    'permissions' => false,
                ) ,
            ) ,
            'permissions' => false,
        ) ,
        'discussion_manager' => array(
            'modes' => array(
                'manage' => array(
                    'permissions' => false,
                ) ,
            ) ,
            'permissions' => true,
        ) ,
        'twigmo' => array(
            'modes' => array(
                'post' => array(
                    'permissions' => true,
                ) ,
            ) ,
            'permissions' => false,
        ) ,
        'twigmo_admin_app' => array(
            'permissions' => true,
        ) ,
    ) ,
    'export' => array(
        'sections' => array(
            'translations' => array(
                'permission' => false,
            ) ,
            'users' => array(
                'permission' => false,
            ) ,
            'features' => array(
                'permission' => false,
            ) ,
            'vendors' => array(
                'permission' => false,
            ) ,
            'subscribers' => array(
                'permission' => false,
            ) ,
        ) ,
        'patterns' => array(
            'google' => array(
                'permission' => false,
            ) ,
        ) ,
    ) ,
    'import' => array(
        'sections' => array(
            'translations' => array(
                'permission' => false,
            ) ,
            'orders' => array(
                'permission' => false,
            ) ,
            'users' => array(
                'permission' => false,
            ) ,
            'features' => array(
                'permission' => false,
            ) ,
            'vendors' => array(
                'permission' => false,
            ) ,
            'subscribers' => array(
                'permission' => false,
            ) ,
        ) ,
        'patterns' => array() ,
    ) ,
);