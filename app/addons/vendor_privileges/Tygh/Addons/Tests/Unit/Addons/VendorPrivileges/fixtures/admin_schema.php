<?php

return array(
    'orders'             => array(
        'modes'       => array(
            'update_status'  => array(
                'permissions' => 'change_order_status',
            ),
            'delete_orders'  => array(
                'permissions' => 'delete_orders',
            ),
            'delete'         => array(
                'permissions' => 'delete_orders',
            ),
            'm_delete'       => array(
                'permissions' => 'delete_orders',
            ),
            'bulk_print'     => array(
                'permissions' => 'view_orders',
            ),
            'remove_cc_info' => array(
                'permissions' => 'edit_order',
            ),
            'update_details' => array(
                'permissions' => 'edit_order',
            ),
            'assign_manager' => array(
                'permissions' => 'edit_order',
            ),
        ),
        'permissions' => 'view_orders',
    ),
    'taxes'              => array(
        'modes'       => array(
            'delete' => array(
                'permissions' => 'manage_taxes',
            ),
        ),
        'permissions' => array(
            'GET'  => 'view_taxes',
            'POST' => 'manage_taxes',
        ),
    ),
    'sitemap'            => array(
        'permissions' => 'manage_sitemap',
    ),
    'datakeeper'         => array(
        'permissions' => 'backup_restore',
    ),
    'product_options'    => array(
        'modes'       => array(
            'delete' => array(
                'permissions' => 'manage_catalog',
            ),
        ),
        'permissions' => array(
            'GET'  => 'view_catalog',
            'POST' => 'manage_catalog',
        ),
    ),
    'tabs'               => array(
        'modes'       => array(
            'delete'        => array(
                'permissions' => 'manage_catalog',
            ),
            'update_status' => array(
                'permissions' => 'manage_catalog',
            ),
            'update'        => array(
                'permissions' => 'manage_catalog',
            ),
            'add'           => array(
                'permissions' => 'manage_catalog',
            ),
            'manage'        => array(
                'permissions' => 'view_catalog',
            ),
            'picker'        => array(
                'permissions' => 'view_catalog',
            ),
        ),
        'permissions' => array(
            'GET'  => 'view_catalog',
            'POST' => 'manage_catalog',
        ),
    ),
    'products'           => array(
        'modes'       => array(
            'delete'  => array(
                'permissions' => 'manage_catalog',
            ),
            'clone'   => array(
                'permissions' => 'manage_catalog',
            ),
            'add'     => array(
                'permissions' => 'manage_catalog',
            ),
            'manage'  => array(
                'permissions' => 'view_catalog',
            ),
            'picker'  => array(
                'permissions' => 'view_catalog',
            ),
            'options' => array(
                'permissions' => 'edit_order',
            ),
        ),
        'permissions' => array(
            'GET'  => 'view_catalog',
            'POST' => 'manage_catalog',
        ),
    ),
    'product_filters'    => array(
        'modes'       => array(
            'delete' => array(
                'permissions' => 'manage_catalog',
            ),
        ),
        'permissions' => array(
            'GET'  => 'view_catalog',
            'POST' => 'manage_catalog',
        ),
    ),
    'shippings'          => array(
        'modes'       => array(
            'delete_shipping' => array(
                'permissions' => 'manage_shipping',
            ),
            'add'             => array(
                'permissions' => 'manage_shipping',
            ),
        ),
        'permissions' => array(
            'GET'  => 'view_shipping',
            'POST' => 'manage_shipping',
        ),
    ),
    'usergroups'         => array(
        'modes'       => array(
            'update_status' => array(
                'permissions' => 'manage_usergroups',
            ),
            'delete'        => array(
                'permissions' => 'manage_usergroups',
            ),
            'update'        => array(
                'permissions' => 'manage_usergroups',
                'condition'   => array(
                    'operator' => 'and',
                    'function' => array(
                        0 => 'fn_check_permission_manage_usergroups',
                    ),
                ),
            ),
        ),
        'permissions' => array(
            'GET'  => 'view_usergroups',
            'POST' => 'manage_usergroups',
        ),
    ),
    'customization'      => array(
        'modes' => array(
            'update_mode' => array(
                'param_permissions' => array(
                    'type' => array(
                        'live_editor'  => 'manage_translation',
                        'design'       => 'manage_design',
                        'theme_editor' => 'manage_design',
                    ),
                ),
            ),
        ),
    ),
    'profiles'           => array(
        'modes' => array(
            'delete'         => array(
                'permissions' => 'manage_users',
            ),
            'delete_profile' => array(
                'permissions' => 'manage_users',
            ),
            'm_delete'       => array(
                'permissions' => 'manage_users',
            ),
            'add'            => array(
                'permissions' => 'manage_users',
            ),
            'update'         => array(
                'permissions' => array(
                    'GET'  => 'view_users',
                    'POST' => 'manage_users',
                ),
                'condition'   => array(
                    'operator' => 'or',
                    'function' => array(
                        0 => 'fn_check_permission_manage_own_profile',
                    ),
                ),
            ),
            'update_status'  => array(
                'permissions' => 'manage_users',
            ),
            'manage'         => array(
                'permissions' => 'view_users',
            ),
            'export_range'   => array(
                'permissions' => 'exim_access',
            ),
            'act_as_user'    => array(
                'permissions' => 'manage_users',
                'condition'   => array(
                    'operator' => 'or',
                    'function' => array(
                        0 => 'fn_check_permission_act_as_user',
                    ),
                ),
            ),
        ),
    ),
    'cart'               => array(
        'permissions' => array(
            'GET'  => 'view_users',
            'POST' => 'manage_users',
        ),
    ),
    'pages'              => array(
        'modes'       => array(
            'delete' => array(
                'permissions' => 'manage_pages',
            ),
        ),
        'permissions' => array(
            'GET'  => 'view_pages',
            'POST' => 'manage_pages',
        ),
    ),
    'profile_fields'     => array(
        'permissions' => array(
            'GET'  => 'view_users',
            'POST' => 'manage_users',
        ),
    ),
    'logs'               => array(
        'modes'       => array(
            'clean' => array(
                'permissions' => 'delete_logs',
            ),
        ),
        'permissions' => 'view_logs',
    ),
    'categories'         => array(
        'modes'       => array(
            'delete' => array(
                'permissions' => 'manage_catalog',
            ),
        ),
        'permissions' => array(
            'GET'  => 'view_catalog',
            'POST' => 'manage_catalog',
        ),
    ),
    'settings'           => array(
        'modes'       => array(
            'change_store_mode' => array(
                'permissions' => 'upgrade_store',
            ),
        ),
        'permissions' => array(
            'GET'  => 'view_settings',
            'POST' => 'update_settings',
        ),
    ),
    'settings_wizard'    => array(
        'permissions' => 'update_settings',
    ),
    'robots'             => array(
        'permissions' => 'update_settings',
    ),
    'upgrade_center'     => array(
        'permissions' => 'upgrade_store',
    ),
    'payments'           => array(
        'modes'       => array(
            'delete' => array(
                'permissions' => 'manage_payments',
            ),
        ),
        'permissions' => array(
            'GET'  => 'view_payments',
            'POST' => 'manage_payments',
        ),
    ),
    'currencies'         => array(
        'modes'       => array(
            'delete' => array(
                'permissions' => 'manage_currencies',
            ),
        ),
        'permissions' => array(
            'GET'  => 'view_currencies',
            'POST' => 'manage_currencies',
        ),
    ),
    'destinations'       => array(
        'modes'       => array(
            'delete' => array(
                'permissions' => 'manage_locations',
            ),
        ),
        'permissions' => array(
            'GET'  => 'view_locations',
            'POST' => 'manage_locations',
        ),
    ),
    'localizations'      => array(
        'permissions' => 'none',
    ),
    'exim'               => array(
        'modes'       => array(
            'export'          => array(
                'param_permissions' => array(
                    'section' => array(
                        'features'     => 'view_catalog',
                        'orders'       => 'view_orders',
                        'products'     => 'view_catalog',
                        'translations' => 'view_languages',
                        'users'        => 'view_users',
                        'vendors'      => 'view_vendors',
                        'subscribers'  => 'view_newsletters',
                    ),
                ),
            ),
            'import'          => array(
                'param_permissions' => array(
                    'section' => array(
                        'features'     => 'manage_catalog',
                        'orders'       => 'edit_order',
                        'products'     => 'manage_catalog',
                        'translations' => 'manage_languages',
                        'users'        => 'manage_users',
                        'vendors'      => 'manage_vendors',
                        'subscribers'  => 'manage_newsletters',
                    ),
                ),
            ),
            'export_datafeed' => array(
                'use_company' => false,
            ),
            'cron_export'     => array(
                'use_company' => false,
            ),
        ),
        'permissions' => 'exim_access',
    ),
    'languages'          => array(
        'modes'       => array(
            'delete_variable' => array(
                'permissions' => 'manage_languages',
            ),
            'delete_language' => array(
                'permissions' => 'manage_languages',
            ),
        ),
        'permissions' => array(
            'GET'  => 'view_languages',
            'POST' => 'manage_languages',
        ),
    ),
    'product_features'   => array(
        'modes'       => array(
            'delete' => array(
                'permissions' => 'manage_catalog',
            ),
        ),
        'permissions' => array(
            'GET'  => 'view_catalog',
            'POST' => 'manage_catalog',
        ),
    ),
    'static_data'        => array(
        'modes'       => array(
            'delete' => array(
                'permissions' => 'manage_static_data',
            ),
        ),
        'permissions' => array(
            'GET'  => 'view_static_data',
            'POST' => 'manage_static_data',
        ),
    ),
    'statuses'           => array(
        'permissions' => 'manage_order_statuses',
    ),
    'sales_reports'      => array(
        'modes'       => array(
            'view'            => array(
                'permissions' => 'view_reports',
            ),
            'set_report_view' => array(
                'permissions' => 'view_reports',
            ),
        ),
        'permissions' => 'manage_reports',
    ),
    'addons'             => array(
        'permissions' => 'update_settings',
    ),
    'states'             => array(
        'modes'       => array(
            'delete' => array(
                'permissions' => 'manage_locations',
            ),
        ),
        'permissions' => array(
            'GET'  => 'view_locations',
            'POST' => 'manage_locations',
        ),
    ),
    'countries'          => array(
        'permissions' => array(
            'GET'  => 'view_locations',
            'POST' => 'manage_locations',
        ),
    ),
    'order_management'   => array(
        'modes'       => array(
            'edit' => array(
                'permissions' => 'edit_order',
            ),
            'new'  => array(
                'permissions' => 'create_order',
            ),
            'add'  => array(
                'permissions' => 'create_order',
            ),
        ),
        'permissions' => 'edit_order',
        'condition'   => array(
            'operator' => 'or',
            'function' => array(
                0 => 'fn_check_current_user_access',
                1 => 'create_order',
            ),
        ),
    ),
    'file_editor'        => array(
        'permissions' => 'edit_files',
    ),
    'block_manager'      => array(
        'permissions' => 'edit_blocks',
    ),
    'menus'              => array(
        'modes'       => array(
            'delete' => array(
                'permissions' => 'edit_blocks',
            ),
        ),
        'permissions' => 'edit_blocks',
    ),
    'promotions'         => array(
        'permissions' => 'manage_promotions',
    ),
    'shipments'          => array(
        'modes'       => array(
            'manage' => array(
                'permissions' => 'view_orders',
            ),
            'delete' => array(
                'permissions' => 'edit_order',
            ),
            'picker' => array(
                'permissions' => 'edit_order',
            ),
            'add'    => array(
                'permissions' => 'edit_order',
            ),
        ),
        'permissions' => 'view_orders',
    ),
    'tools'              => array(
        'modes' => array(
            'update_position' => array(
                'param_permissions' => array(
                    'table' => array(
                        'product_tabs'           => 'manage_catalog',
                        'template_table_columns' => 'manage_document_templates',
                        'statuses'               => 'manage_order_statuses',
                        'hybrid_auth_providers'  => 'manage_providers',
                    ),
                ),
            ),
            'view_changes'    => array(
                'permissions' => 'view_file_changes',
            ),
            'update_status'   => array(
                'param_permissions' => array(
                    'table' => array(
                        'categories'             => 'manage_catalog',
                        'states'                 => 'manage_locations',
                        'usergroups'             => 'manage_usergroups',
                        'currencies'             => 'manage_currencies',
                        'blocks'                 => 'edit_blocks',
                        'pages'                  => 'manage_pages',
                        'taxes'                  => 'manage_taxes',
                        'promotions'             => 'manage_promotions',
                        'static_data'            => 'manage_static_data',
                        'statistics_reports'     => 'manage_reports',
                        'countries'              => 'manage_locations',
                        'shippings'              => 'manage_shipping',
                        'languages'              => 'manage_languages',
                        'sitemap_sections'       => 'manage_sitemap',
                        'localizations'          => 'manage_locations',
                        'products'               => 'manage_catalog',
                        'destinations'           => 'manage_locations',
                        'product_options'        => 'manage_catalog',
                        'product_features'       => 'manage_catalog',
                        'payments'               => 'manage_payments',
                        'product_filters'        => 'manage_catalog',
                        'product_files'          => 'manage_catalog',
                        'orders'                 => 'change_order_status',
                        'template_emails'        => 'manage_email_templates',
                        'template_table_columns' => 'manage_document_templates',
                        'tags'                   => 'manage_catalog',
                        'newsletter_campaigns'   => 'manage_newsletters',
                        'mailing_lists'          => 'manage_newsletters',
                        'attachments'            => 'manage_catalog',
                        'vendor_plans'           => 'manage_vendor_plans',
                        'store_locations'        => 'manage_store_locator',
                        'call_requests'          => 'manage_call_requests',
                        'form_options'           => 'manage_pages',
                        'banners'                => 'manage_banners',
                        'discussion_posts'       => 'manage_discussions',
                        'data_feeds'             => 'manage_catalog',
                        'hybrid_auth_providers'  => 'manage_providers',
                    ),
                ),
            ),
        ),
    ),
    'storage'            => array(
        'permissions' => 'manage_storage',
    ),
    'themes'             => array(
        'permissions' => 'manage_themes',
    ),
    'email_templates'    => array(
        'permissions' => 'manage_email_templates',
    ),
    'documents'          => array(
        'permissions' => 'manage_document_templates',
    ),
    'templates'          => array(
        'permissions' => 'edit_files',
    ),
    'root'               => array(
        'localizations' => array(
            'permissions' => 'none',
        ),
    ),
    'companies'          => array(
        'modes'       => array(
            'manage'             => array(
                'permissions' => array(
                    'GET'  => 'view_vendors',
                    'POST' => 'manage_vendors',
                ),
            ),
            'add'                => array(
                'permissions' => 'manage_vendors',
            ),
            'update'             => array(
                'permissions' => array(
                    'GET'  => 'view_vendors',
                    'POST' => 'manage_vendors',
                ),
            ),
            'get_companies_list' => array(
                'permissions' => 'view_vendors',
            ),
            'payouts_m_delete'   => array(
                'permissions' => 'manage_payouts',
            ),
            'payouts_add'        => array(
                'permissions' => 'manage_payouts',
            ),
            'payout_delete'      => array(
                'permissions' => 'manage_payouts',
            ),
            'balance'            => array(
                'permissions' => 'view_payouts',
            ),
        ),
        'permissions' => 'manage_vendors',
    ),
    'tags'               => array(
        'permissions' => 'manage_catalog',
    ),
    'newsletters'        => array(
        'modes'       => array(
            'delete'          => array(
                'permissions' => 'manage_newsletters',
            ),
            'delete_campaign' => array(
                'permissions' => 'manage_newsletters',
            ),
        ),
        'permissions' => array(
            'GET'  => 'view_newsletters',
            'POST' => 'manage_newsletters',
        ),
    ),
    'subscribers'        => array(
        'modes'       => array(
            'delete' => array(
                'permissions' => 'manage_newsletters',
            ),
        ),
        'permissions' => array(
            'GET'  => 'view_newsletters',
            'POST' => 'manage_newsletters',
        ),
    ),
    'campaigns'          => array(
        'permissions' => array(
            'GET'  => 'view_newsletters',
            'POST' => 'manage_newsletters',
        ),
    ),
    'mailing_lists'      => array(
        'modes'       => array(
            'delete' => array(
                'permissions' => 'manage_newsletters',
            ),
        ),
        'permissions' => array(
            'GET'  => 'view_newsletters',
            'POST' => 'manage_newsletters',
        ),
    ),
    'attachments'        => array(
        'permissions' => array(
            'GET'  => 'view_catalog',
            'POST' => 'manage_catalog',
        ),
        'modes'       => array(
            'delete' => array(
                'permissions' => 'manage_catalog',
            ),
        ),
    ),
    'import_presets'     => array(
        'permissions' => 'manage_catalog',
    ),
    'advanced_import'    => array(
        'permissions' => 'manage_catalog',
        'modes'       => array(
            'import' => array(
                'permissions' => 'manage_catalog',
            ),
        ),
    ),
    'premoderation'      => array(
        'permissions' => 'manage_product_premoderation',
    ),
    'vendor_plans'       => array(
        'permissions' => array(
            'GET'  => 'view_vendor_plans',
            'POST' => 'manage_vendor_plans',
        ),
    ),
    'gift_certificates'  => array(
        'permissions' => 'manage_gift_certificates',
    ),
    'store_locator'      => array(
        'permissions' => array(
            'GET'  => 'view_store_locator',
            'POST' => 'manage_store_locator',
        ),
        'modes'       => array(
            'delete' => array(
                'permissions' => 'manage_store_locator',
            ),
        ),
    ),
    'product_variations' => array(
        'permissions' => 'manage_catalog',
        'modes'       => array(
            'list' => array(
                'permissions' => 'view_catalog',
            ),
        ),
    ),
    'seo_rules'          => array(
        'modes'       => array(
            'delete'   => array(
                'permissions' => 'manage_seo_rules',
            ),
            'manage'   => array(
                'vendor_only' => true,
                'use_company' => true,
                'page_title'  => 'seo_rules',
                'permissions' => 'view_seo_rules',
            ),
            'update'   => array(
                'use_company' => true,
            ),
            'm_update' => array(
                'use_company' => true,
            ),
        ),
        'permissions' => array(
            'GET'  => 'view_seo_rules',
            'POST' => 'manage_seo_rules',
        ),
    ),
    'seo_redirects'      => array(
        'modes'       => array(
            'delete'   => array(
                'permissions' => 'manage_seo_rules',
            ),
            'manage'   => array(
                'vendor_only' => true,
                'use_company' => true,
                'page_title'  => 'seo.redirects_manager',
            ),
            'update'   => array(
                'use_company' => true,
            ),
            'm_update' => array(
                'use_company' => true,
            ),
        ),
        'permissions' => array(
            'GET'  => 'view_seo_rules',
            'POST' => 'manage_seo_rules',
        ),
    ),
    'reward_points'      => array(
        'permissions' => 'manage_reward_points',
        'modes'       => array(
            'manage' => array(
                'use_company' => true,
            ),
            'update' => array(
                'use_company' => true,
            ),
        ),
    ),
    'call_requests'      => array(
        'permissions' => array(
            'GET'  => 'view_call_requests',
            'POST' => 'manage_call_requests',
        ),
        'modes'       => array(
            'delete' => array(
                'permissions' => 'manage_call_requests',
            ),
        ),
    ),
    'yml'                => array(
        'modes'       => array(),
        'permissions' => array(
            'GET'  => 'view_yml',
            'POST' => 'manage_yml',
        ),
    ),
    'banners'            => array(
        'permissions' => 'manage_banners',
    ),
    'discussion_manager' => array(
        'permissions' => array(
            'GET'  => 'view_discussions',
            'POST' => 'manage_discussions',
        ),
    ),
    'discussion'         => array(
        'permissions' => array(
            'GET'  => 'view_discussions',
            'POST' => 'manage_discussions',
        ),
    ),
    'index'              => array(
        'modes' => array(
            'delete_post' => array(
                'permissions' => 'manage_discussions',
            ),
        ),
    ),
    'data_feeds'         => array(
        'permissions' => array(
            'GET'  => 'view_catalog',
            'POST' => 'manage_catalog',
        ),
    ),
    'hybrid_auth'        => array(
        'modes'       => array(
            'delete_provider' => array(
                'permissions' => 'manage_providers',
            ),
        ),
        'permissions' => array(
            'GET'  => 'view_providers',
            'POST' => 'manage_providers',
        ),
    ),
);