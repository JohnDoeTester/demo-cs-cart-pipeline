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

use Tygh\Enum\ProductFeatures;
use Tygh\Enum\ProfileFieldSections;
use Tygh\Enum\ProfileFieldTypes;
use Tygh\Enum\YesNo;
use Tygh\Registry;

require_once Registry::get('config.dir.schemas') . 'block_manager/blocks.functions.php';

/**
 * TODO: feel free to modify the description below any time you found new information about available parameters
 */

/**
 * Describes a way to describe blocks
 *
 * Structure:
 *
 * 'block type' => array(
 *     'hide_on_locations' => array('product_tabs', 'companies.view'), // array of locations that corresponds to $location['dispatch'] parameter, on which the block will be hidden on blocks selection page (block_manager.block_selection)
 *     'show_on_locations' => array('product_tabs', 'companies.view'), // array of locations that corresponds to $location['dispatch'] parameter, on which the block can legally be shown (it will be available on the section page + prevented from displaying, if added to wrong location)
 * )
 * 'templates' => 'blocks/lite_checkout/customer_address.tpl',                      // template that will be used to render the block
 * 'content' => [
 *     'items' => [
 *         'type'           => 'enum',
 *         'object'         => 'profile_fields',
 *         'items_function' => 'fn_blocks_get_lite_checkout_profile_fields',
 *         'remove_indent'  => true,
 *         'hide_label'     => true,
 *         'fillings' => [
 *             'manually' => [                                                      // manual selection of block content trough picker
 *                 'picker'        => 'pickers/profile_fields/picker.tpl',          // picker template that will be used to select objects
 *                 'picker_params' => [                                             // parameters that will be available inside picker template
 *                     'section' => ProfileFieldSections::SHIPPING_ADDRESS,         // each one as separate variable e.g. {$section}
 *                     'exclude' => ['s_country', 's_city', 's_state'],
 *                 ],
 *                 'before_save_handlers'    => [                                    // before save block routines
 *                     'handler_name' => 'handler_function_name',                   // callback that will be called after block saved e.g. callback($block_data)
 *                     'one_more_handler_name' => 'one_more_handler_function_name',
 *                 ],
 *                 'after_save_handlers'    => [                                    // after save block routines
 *                     'handler_name' => 'handler_function_name',                   // callback that will be called after block saved e.g. callback($block_data)
 *                     'one_more_handler_name' => 'one_more_handler_function_name',
 *                 ],
 *             ],
 *         ],
 *     ],
 * ],
 * 'brief_info_function' => 'fn_block_get_block_with_items_info' //callback that will be called for getting short info about block (like: content, image...) it will be used on block list
 *
 */
$schema = array(
    'menu' => array(
        'templates' => 'blocks/menu',
        'content' => array(
            'items' => array(
                'type' => 'function',
                'function' => array('fn_get_menu_items')
            ),
            'menu' => array(
                'type' => 'template',
                'template' => 'views/menus/components/block_settings.tpl',
                'hide_label' => true,
                'data_function' => array('fn_get_menus'),
            ),
        ),
        'wrappers' => 'blocks/wrappers',
        'cache' => array(
            'update_handlers' => ['menus', 'menus_descriptions', 'static_data', 'categories'],
            'callable_handlers' => array(
                'request' => ['fn_blocks_menu_get_request_hash', ['$block_data', '$_REQUEST', '$_SERVER']]
            ),
        ),
        'brief_info_function' => 'fn_block_get_menu_info'
    ),
    'my_account' => array(
        'templates' => array(
            'blocks/my_account.tpl' => array(),
        ),
        'wrappers' => 'blocks/wrappers',
        'content' => array(
            'header_class' => array(
                'type' => 'function',
                'function' => array('fn_get_my_account_title_class'),
            )
        ),
        'cache' => false
    ),
    'our_brands' => array(
        'templates' => array(
            'blocks/our_brands.tpl' => array(),
        ),
        'wrappers' => 'blocks/wrappers',
        'content' => array(
            'brands' => array(
                'type' => 'function',
                /** @see \fn_blocks_get_brands() */
                'function' => array('fn_blocks_get_brands'),
            ),
        ),
        'cache' => array(
            'update_handlers' => array(
                'product_features',
                'product_features_descriptions',
                'product_features_values',
                'product_feature_variants',
                'product_feature_variant_descriptions',
                'images_links'
            )
        )
    ),
    'cart_content' => array(
        'templates' => array(
            'blocks/cart_content.tpl' => array(),
        ),
        'settings' => array(
            'display_bottom_buttons' => array(
                'type' => 'checkbox',
                'default_value' => 'Y'
            ),
            'display_delete_icons' => array(
                'type' => 'checkbox',
                'default_value' => 'Y'
            ),
            'products_links_type' => array(
                'type' => 'selectbox',
                'values' => array(
                    'thumb' => 'thumb',
                    'text' => 'text',
                ),
                'default_value' => 'thumb'
            ),
        ),
        'wrappers' => 'blocks/wrappers',
        'cache' => array(
            'disable_cache_when' => array(
                'session_handlers' => array(
                   'cart.amount' => array('gt', 0)
                )
            )
        )
    ),
    'breadcrumbs' => array(
        'templates' => array(
            'common/breadcrumbs.tpl' => array(),
        ),
        'wrappers' => 'blocks/wrappers',
        'cache_overrides_by_dispatch' => array(
            'categories.view' => array(
                'request_handlers' => array('category_id'),
                'disable_cache_when' => array(
                    'request_handlers' => array('price_to', 'price_from', 'features_hash', 'subcats'),
                ),
                'update_handlers' => array(
                    'categories',
                    'category_descriptions',
                ),
            ),
            'products.view' => array(
                'request_handlers' => array('product_id'),
                'session_handlers' => array(
                    'last_view.lv_products.view_results'
                ),
                'update_handlers' => array(
                    'products',
                    'product_descriptions',
                    'products_categories',
                    'categories',
                    'category_descriptions',
                ),
            ),
            'pages.view' => array(
                'request_handlers' => array('page_id'),
                'update_handlers' => array(
                    'pages',
                    'page_descriptions',
                ),
            ),
        )
    ),
    'template' => array(
        'templates' => 'blocks/static_templates',
        'wrappers' => 'blocks/wrappers',
        'cache' => false,
        'brief_info_function' => 'fn_block_get_template_info'
    ),
    'main' => array(
        'hide_on_locations' => array(
            'product_tabs'
        ),
        'single_for_location' => 1,
        'wrappers' => 'blocks/wrappers',
        'cache_overrides_by_dispatch' => array(
            'categories.view' => array(
                'request_handlers' => array('category_id', 'sort_by', 'sort_order', 'page'),
                'session_handlers' => array('items_per_page', 'sort_by', 'sort_order'),
                'cookie_handlers' => array('%ALL%'),
                'disable_cache_when' => array(
                    'request_handlers' => array('price_to', 'price_from', 'features_hash'),
                    'auth_handlers' => array(
                        'user_id' => array('gt', 0),
                        'age' => array('gt', 0),
                    )
                ),
                'update_handlers' => array(
                    'products_categories',
                    'categories',
                    'category_descriptions',
                    'products',
                    'product_descriptions',
                    'product_tabs',
                    'product_tabs_descriptions',
                    'product_prices',
                    'product_files',
                    'product_file_descriptions',
                    'product_feature_variants',
                    'product_feature_variant_descriptions',
                    'product_features',
                    'product_features_descriptions',
                    'product_features_values',
                    'product_option_variants',
                    'product_option_variants_descriptions',
                    'product_options',
                    'product_options_descriptions',
                    'product_options_exceptions',
                    'product_global_option_links',
                ),
                'callable_handlers' => array(
                    'layout' => array('fn_get_products_layout', array([])),
                    'currency' => array('fn_get_secondary_currency')
                )
            ),
            'products.view' => array(
                'request_handlers' => array('product_id', 'selected_section', 'combination'),
                'update_handlers' => array(
                    'products_categories',
                    'categories',
                    'category_descriptions',
                    'products',
                    'product_descriptions',
                    'product_tabs',
                    'product_tabs_descriptions',
                    'product_prices',
                    'product_files',
                    'product_file_descriptions',
                    'product_feature_variants',
                    'product_feature_variant_descriptions',
                    'product_features',
                    'product_features_descriptions',
                    'product_features_values',
                    'product_option_variants',
                    'product_option_variants_descriptions',
                    'product_options',
                    'product_options_descriptions',
                    'product_options_exceptions',
                    'product_global_option_links',
                ),
                'callable_handlers' => array(
                    'currency' => array('fn_get_secondary_currency')
                ),
                'disable_cache_when' => array(
                    'auth_handlers' => array(
                        'user_id' => array('gt', 0)
                    )
                )
            )
        )
    ),
    'html_block' => array(
        'content' => array(
            'content' => array(
                'type' => 'text',
                'required' => true,
            )
        ),
        'templates' => 'blocks/html_block.tpl',
        'wrappers' => 'blocks/wrappers',
        'cache' => true,
        'multilanguage' => true,
    ),
    // smarty_block was deprecated since 4.16.1, use safe_smarty_block.tpl instead
    'smarty_block' => [
        'content'       => [
            'content' => [
                'type'     => 'simple_text',
                'required' => true,
            ]
        ],
        'templates'     => 'blocks/smarty_block.tpl',
        'wrappers'      => 'blocks/wrappers',
        'multilanguage' => true,
        'cache'         => false,
    ],
    'checkout' => array(
        'templates' => 'blocks/checkout',
        'wrappers' => 'blocks/wrappers',
    ),
    'products' => array(
        'content' => array(
            'items' => array(
                'type' => 'enum',
                'object' => 'products',
                'items_function' => 'fn_get_products',
                'remove_indent' => true,
                'hide_label' => true,
                'contains_id_keys' => true,
                'fillings' => array(
                    'manually' => array(
                        'picker' => 'views/products/components/picker/block_manager_picker.tpl',
                        'picker_params' => array(
                            'multiple' => true,
                            'view_mode' => 'external',
                            'show_positions' => true,
                        ),
                    ),
                    'newest' => array(
                        'params' => array(
                            'sort_by' => 'timestamp',
                            'sort_order' => 'desc',
                            'request' => array(
                                'cid' => '%CATEGORY_ID%'
                            )
                        )
                    ),
                    'recent_products' => array(
                        'params' => array(
                            'apply_limit' => true,
                            'session' => array(
                                'pid' => '%RECENTLY_VIEWED_PRODUCTS%'
                            ),
                            'request' => array(
                                'exclude_pid' => '%PRODUCT_ID%'
                            ),
                            'force_get_by_ids' => true,
                        ),
                    ),
                    'most_popular' => array(
                        'params' => array(
                            'popularity_from' => 1,
                            'sort_by' => 'popularity',
                            'sort_order' => 'desc',
                            'request' => array(
                                'cid' => '%CATEGORY_ID'
                            )
                        ),
                        'cache_ttl' => SECONDS_IN_DAY
                    ),
                ),
            ),
        ),
        'templates' => 'blocks/products',
        'settings' => array(
            'hide_add_to_cart_button' => array(
                'type' => 'checkbox',
                'default_value' => 'Y'
            )
        ),
        'wrappers' => 'blocks/wrappers',
        'cache' => array(
            'update_handlers' => array(
                'products',
                'product_descriptions',
                'product_prices',
                'products_categories',
                'product_options',
                'product_options_descriptions',
                'product_option_variants',
                'product_option_variants_descriptions',
                'product_global_option_links',
                'storefronts_companies',
            ),
            'request_handlers' => array('current_category_id' => '%CATEGORY_ID%'),
            'cookie_handlers' => array('%ALL%'),
            'callable_handlers' => array(
                'currency' => array('fn_get_secondary_currency'),
                'storefront' => ['fn_blocks_get_current_storefront_id'],
            ),
            'disable_cache_when' => array(
                'callable_handlers' => array(
                    array('fn_block_products_disable_cache', array('$block_data'))
                ),
            )
        ),
        'brief_info_function' => 'fn_block_get_block_with_items_info'
    ),
    'categories' => array(
        'content' => array(
            'items' => array(
                'type' => 'enum',
                'object' => 'categories',
                'items_function' => 'fn_get_categories',
                'remove_indent' => true,
                'hide_label' => true,
                'fillings' => array(
                    'manually' => array(
                        'params' => array(
                            'plain' => true,
                            'simple' => false,
                            'group_by_level' => false,
                        ),
                        'picker' => 'pickers/categories/picker.tpl',
                        'picker_params' => array(
                            'multiple' => true,
                            'use_keys' => 'N',
                            'status' => 'A',
                            'positions' => true,
                        ),
                    ),
                    'newest' => array(
                        'params' => array(
                            'sort_by' => 'timestamp',
                            'plain' => true,
                            'visible' => true
                        ),
                        'period' => array(
                            'type' => 'selectbox',
                            'values' => array(
                                'A' => 'any_date',
                                'D' => 'today',
                                'HC' => 'last_days',
                            ),
                            'default_value' => 'any_date'
                        ),
                        'last_days' => array(
                            'type' => 'input',
                            'default_value' => 1
                        ),
                                    'limit' => array(
                            'type' => 'input',
                            'default_value' => 3
                        )
                    ),
                    'full_tree_cat' => array(
                        'params' => array(
                            'plain' => true
                        ),
                        'update_params' => array(
                            'request' => array('%CATEGORY_ID'),
                        ),
                        'settings' => array(
                            'parent_category_id' => array(
                                'type' => 'picker',
                                'default_value' => '0',
                                'picker' => 'pickers/categories/picker.tpl',
                                'picker_params' => array(
                                    'multiple' => false,
                                    'use_keys' => 'N',
                                    'default_name' => __('root_level'),
                                ),
                            ),
                            'sort_by' => array(
                                'type' => 'selectbox',
                                'values' => array(
                                    'position' => 'position',
                                    'name' => 'name',
                                ),
                                'default_value' => 'position'
                            ),
                        ),
                    ),
                    'subcategories_tree_cat' => array(
                        'params' => array(
                            'plain' => true,
                            'request' => array(
                                'category_id' => '%CATEGORY_ID%'
                            ),
                        ),
                        'settings' => array(
                            'sort_by' => array(
                                'type' => 'selectbox',
                                'values' => array(
                                    'position' => 'position',
                                    'name' => 'name',
                                ),
                                'default_value' => 'position'
                            ),
                        ),
                    ),
                ),
            )
        ),
        'templates' => 	'blocks/categories',
        'wrappers' => 'blocks/wrappers',
        'cache' => array(
            'update_handlers' => array('categories', 'category_descriptions'),
            'session_handlers' => array('%CURRENT_CATEGORY_ID%'),
            'request_handlers' => array('%CATEGORY_ID%')
        ),
        'brief_info_function' => 'fn_block_get_block_with_items_info'
    ),
    'pages' => array(
        'content' => array(
            'items' => array(
                'type' => 'enum',
                'object' => 'pages',
                'items_function' => 'fn_get_pages',
                'remove_indent' => true,
                'hide_label' => true,
                'fillings' => array(
                    'manually' => array(
                        'picker' => 'pickers/pages/picker.tpl',
                        'picker_params' => array(
                            'multiple' => true,
                            'status' => 'A',
                            'positions' => true,
                        )
                    ),
                    'newest' => array(
                        'params' => array(
                            'sort_by' => 'timestamp',
                            'visible' => true,
                            'status' => 'A',
                        )
                    ),
                    'dynamic_tree_pages' => array(
                        'params' => array(
                            'visible' => true,
                            'get_tree' => 'plain',
                            'status' => 'A',
                            'request' => array(
                                'current_page_id' => '%PAGE_ID%'
                            ),
                            'get_children_count' => true
                        ),
                        'settings' => array(
                            'parent_page_id' => array(
                                'type' => 'picker',
                                'default_value' => '0',
                                'picker' => 'pickers/pages/picker.tpl',
                                'picker_params' => array(
                                    'multiple' => false,
                                    'status' => 'A',
                                    'default_name' => __('all_pages'),
                                ),
                            ),
                        ),
                    ),
                    'full_tree_pages' => array(
                        'params' => array(
                            'get_tree' => 'plain',
                            'status' => 'A',
                            'get_children_count' => true,
                        ),
                        'settings' => array(
                            'parent_page_id' => array(
                                'type' => 'picker',
                                'default_value' => '0',
                                'picker' => 'pickers/pages/picker.tpl',
                                'picker_params' => array(
                                    'multiple' => false,
                                    'status' => 'A',
                                    'default_name' => __('all_pages'),
                                ),
                            ),
                        ),
                    ),
                    'neighbours' => array(
                        'params' => array(
                            'get_tree' => 'plain',
                            'status' => 'A',
                            'get_children_count' => true,
                            'neighbours' => true,
                            'request' => array(
                                'neighbours_page_id' => '%PAGE_ID%',
                            )
                        ),
                    ),
                ),
            ),
        ),
        'templates' => 'blocks/pages',
        'wrappers' => 'blocks/wrappers',
        'cache' => array(
            'update_handlers' => array('pages', 'page_descriptions'),
            'session_handlers' => array('%CURRENT_CATEGORY_ID%'),
            'request_handlers' => array('%PAGE_ID%')
        ),
        'brief_info_function' => 'fn_block_get_block_with_items_info'
    ),
    'payment_methods' => array(
        'content' => array(
            'items' => array(
                'type' => 'function',
                'function' => array('fn_get_payment_methods_images'),
            ),
        ),
        'templates' => 'blocks/payments.tpl',
        'wrappers' => 'blocks/wrappers',
        'cache' => array(
            'update_handlers' => array('payments', 'payment_descriptions'),
        ),
    ),

    'shipping_methods' => array(
        'content' => array(
            'items' => array(
                'type' => 'function',
                'function' => array('fn_get_shipping_images'),
            ),
        ),
        'templates' => 'blocks/shippings.tpl',
        'wrappers' => 'blocks/wrappers',
        'cache' => array(
            'update_handlers' => array('shippings', 'shipping_descriptions'),
        ),
    ),

    'currencies' => [
        'content' => [
            'currencies' => [
                'type' => 'function',
                'function' => ['fn_block_manager_get_currencies'],
            ],
            'text' => [
                'type' => 'input',
                'default_value' => ''
            ],
        ],
        'settings' => [
            'format' => [
                'type' => 'selectbox',
                'values' => [
                    'name' => 'opt_currency_name',
                    'symbol' => 'opt_currency_symbol',
                ],
                'default_value' => 'name'
            ],
            'dropdown_limit' => [
                'type' => 'input',
                'default_value' => '0'
            ]
        ],
        'templates' => 'blocks/currencies.tpl',
        'wrappers' => 'blocks/wrappers',
        'multilanguage' => true,
    ],

    'languages' => [
        'content' => [
            'languages' => [
                'type' => 'function',
                'function' => ['fn_blocks_get_languages'],
            ],
            'text' => [
                'type' => 'input',
                'default_value' => ''
            ],
        ],
        'settings' => [
            'format' => [
                'type' => 'selectbox',
                'values' => [
                    'name' => 'opt_language_name',
                    'icon' => 'opt_language_icon',
                ],
                'default_value' => 'name'
            ],
            'dropdown_limit' => [
                'type' => 'input',
                'default_value' => '0'
            ]
        ],
        'templates' => 'blocks/languages.tpl',
        'wrappers' => 'blocks/wrappers',
        'multilanguage' => true,
    ],

    'product_filters' => array(
        'content' => array(
            'items' => array(
                'type' => 'enum',
                'object' => 'filters',
                'items_function' => 'fn_product_filters_get_filters_products_count',
                'remove_indent' => true,
                'hide_label' => true,
                'fillings' => array(
                    'manually' => array(
                        'params' => array(
                            'check_location' => true,
                            'request' => array(
                                'dispatch' => '%DISPATCH%',
                                'category_id' => '%CATEGORY_ID%',
                                'features_hash' => '%FEATURES_HASH%',
                                'variant_id' => '%VARIANT_ID%',
                                'company_id' => '%COMPANY_ID%',
                                'q' => '%Q%',
                                'search_performed' => '%SEARCH_PERFORMED%',
                                'pshort' => '%PSHORT%',
                                'pfull' => '%PFULL%',
                                'pname' => '%PNAME%',
                                'pcode_from_q' => '%PCODE_FROM_Q%',
                                'pkeywords' => '%PKEYWORDS%',
                                'pid' => '%PID%',
                                'order_ids' => '%ORDER_IDS%',
                                'match' => '%MATCH%',
                            ),
                            'process_empty_items' => true
                        ),
                        'picker' => 'pickers/filters/picker.tpl',
                        'picker_params' => array(
                            'multiple' => true,
                            'extra_url' => '&' . http_build_query(array(
                                'status' => 'A'
                            )),
                            'no_item_text' => __('all_filters'),
                        ),
                    ),
                )
            ),
        ),
        'templates' => 'blocks/product_filters/for_category',
        'wrappers' => 'blocks/wrappers',
        'cache' => array(
            'request_handlers' => array('category_id', 'sort_by', 'items_per_page', 'variant_id'),
            'disable_cache_when' => array(
                'request_handlers' => array('price_to', 'price_from', 'features_hash', 'q', 'filter_id'),
            ),
            'update_handlers' => array(
                'product_filters',
                'product_filter_descriptions',
                'product_filter_ranges',
                'product_filter_ranges_descriptions',
                'product_feature_variants',
                'product_feature_variant_descriptions',
                'product_features',
                'product_features_descriptions',
                'product_features_values',
                'products'
            ),
            'callable_handlers' => array(
                'currency' => array('fn_get_secondary_currency')
            )
        ),
        'brief_info_function' => 'fn_block_get_block_with_items_info'
    ),
    'product_filters_home' => array(
        'content' => array(
            'items' => array(
                'type' => 'enum',
                'object' => 'filters',
                'items_function' => 'fn_get_product_filters',
                'remove_indent' => true,
                'hide_label' => true,
                'fillings' => array(
                    'manually' => array(
                        'params' => array(
                            'get_variants' => true
                        ),
                        'picker' => 'pickers/filters/picker.tpl',
                        'picker_params' => array(
                            'multiple' => true,
                            'extra_url' => '&' . http_build_query(array(
                                'status' => 'A',
                                'feature_type' => array(ProductFeatures::TEXT_SELECTBOX, ProductFeatures::MULTIPLE_CHECKBOX, ProductFeatures::EXTENDED)
                            ))
                        ),
                    ),
                )
            ),
        ),
        'templates' => 'blocks/product_filters/for_home',
        'wrappers' => 'blocks/wrappers',
        'cache' => array(
            'update_handlers' => array(
                'product_filters',
                'product_filter_descriptions',
                'product_filter_ranges',
                'product_filter_ranges_descriptions',
                'product_feature_variants',
                'product_feature_variant_descriptions',
                'product_features',
                'product_features_descriptions',
                'product_features_values',
            ),
            'callable_handlers' => array(
                'currency' => array('fn_get_secondary_currency')
            )
        ),
        'brief_info_function' => 'fn_block_get_block_with_items_info'
    ),
    'lite_checkout_location' => [
        'show_on_locations' => ['checkout'],
        'templates'         => 'blocks/lite_checkout/location.tpl',
        'wrappers'          => 'blocks/lite_checkout/wrappers',
        'content'           => [
            'items' => [
                'type'           => 'enum',
                'object'         => 'profile_fields',
                'items_function' => 'fn_blocks_get_lite_checkout_profile_fields',
                'remove_indent'  => true,
                'hide_label'     => true,
                'fillings'       => [
                    'manually' => [
                        'picker'              => 'pickers/profile_fields/picker.tpl',
                        'picker_params'       => [
                            'sortable'              => true,
                            'adjust_requireability' => true,
                            'section'               => ProfileFieldSections::SHIPPING_ADDRESS,
                            'disable_required'    => ['s_country', 's_city', 's_state'],
                            'disable_description' => ['s_state' => __('state_field_required_only_for_countries_with_regions')],
                        ],
                        'after_save_handlers' => [
                            'checkout_visibility' => 'fn_blocks_update_customer_location_profile_fields_visibility',
                        ],
                    ],
                ],
            ],
        ],
        'brief_info_function' => 'fn_block_get_block_with_items_info'
    ],
    'lite_checkout_shipping_methods' => [
        'show_on_locations'   => ['checkout'],
        'templates'           => 'blocks/lite_checkout/shipping_methods.tpl',
        'wrappers'            => 'blocks/lite_checkout/wrappers',
        'content'             => [
            'content' => [
                'type' => 'simple_text',
                'required' => false,
                'default_value' => ' ',
            ]
        ],
        'multilanguage'       => true,
    ],
    'lite_checkout_customer_sign_in'     => [
        'show_on_locations' => ['checkout'],
        'templates'         => 'blocks/lite_checkout/customer_sign_in.tpl',
        'wrappers'          => 'blocks/lite_checkout/wrappers',
    ],
    'lite_checkout_customer_address'     => [
        'show_on_locations' => ['checkout'],
        'templates'         => 'blocks/lite_checkout/customer_address.tpl',
        'wrappers'          => 'blocks/lite_checkout/wrappers',
        'content'           => [
            'items' => [
                'type'           => 'enum',
                'object'         => 'profile_fields',
                'items_function' => 'fn_blocks_get_lite_checkout_profile_fields',
                'remove_indent'  => true,
                'hide_label'     => true,
                'fillings'       => [
                    'manually' => [
                        'picker'        => 'pickers/profile_fields/picker.tpl',
                        'picker_params' => [
                            'sortable'      => true,
                            'section'       => ProfileFieldSections::SHIPPING_ADDRESS,
                            'exclude_names' => ['s_country', 's_city', 's_state'],
                            'exclude_types' => [ProfileFieldTypes::FILE],
                        ],
                        'after_save_handlers' => [
                            'checkout_visibility' => 'fn_blocks_update_shipping_address_profile_fields_visibility',
                        ],
                    ],
                ],
            ],
        ],
        'brief_info_function' => 'fn_block_get_block_with_items_info'
    ],
    'lite_checkout_customer_information' => [
        'show_on_locations' => ['checkout'],
        'templates'         => 'blocks/lite_checkout/customer_information.tpl',
        'wrappers'          => 'blocks/lite_checkout/wrappers',
        'content'           => [
            'items' => [
                'type'           => 'enum',
                'object'         => 'profile_fields',
                'items_function' => 'fn_blocks_get_lite_checkout_profile_fields',
                'remove_indent'  => true,
                'hide_label'     => true,
                'fillings'       => [
                    'manually' => [
                        'picker'        => 'pickers/profile_fields/picker.tpl',
                        'picker_params' => [
                            'sortable'      => true,
                            'section'       => ProfileFieldSections::CONTACT_INFORMATION,
                            'exclude_types' => [ProfileFieldTypes::FILE],
                        ],
                        'before_save_handlers' => [
                            'checkout_required_fields' => 'fn_blocks_update_contact_information_check_required_fields',
                        ],
                        'after_save_handlers' => [
                            'checkout_visibility' => 'fn_blocks_update_contact_information_profile_fields_visibility',
                        ],
                    ],
                ],
            ],
        ],
        'brief_info_function' => 'fn_block_get_block_with_items_info'
    ],
    'lite_checkout_customer_billing' => [
        'show_on_locations' => ['checkout'],
        'templates'         => 'blocks/lite_checkout/customer_billing.tpl',
        'wrappers'          => 'blocks/lite_checkout/wrappers',
        'content'           => [
            'items' => [
                'type'           => 'enum',
                'object'         => 'profile_fields',
                'items_function' => 'fn_blocks_get_lite_checkout_profile_fields',
                'remove_indent'  => true,
                'hide_label'     => true,
                'fillings'       => [
                    'manually' => [
                        'picker'        => 'pickers/profile_fields/picker.tpl',
                        'picker_params' => [
                            'sortable' => true,
                            'section'  => ProfileFieldSections::BILLING_ADDRESS,
                            'exclude_types' => [ProfileFieldTypes::FILE],
                        ],
                        'after_save_handlers' => [
                            'checkout_visibility' => 'fn_blocks_update_billing_address_profile_fields_visibility',
                        ],
                    ],
                ],
            ],
        ],
        'brief_info_function' => 'fn_block_get_block_with_items_info'
    ],
    'lite_checkout_payment_methods'      => [
        'show_on_locations' => ['checkout'],
        'wrappers'          => 'blocks/lite_checkout/wrappers',
        'templates'         => 'blocks/lite_checkout/payment_methods.tpl',
    ],
    'lite_checkout_customer_notes'       => [
        'show_on_locations' => ['checkout'],
        'wrappers'          => 'blocks/lite_checkout/wrappers',
        'templates'         => 'blocks/lite_checkout/customer_notes.tpl',
    ],
    'lite_checkout_terms_and_conditions' => [
        'show_on_locations' => ['checkout'],
        'wrappers'          => 'blocks/lite_checkout/wrappers',
        'templates'         => 'blocks/lite_checkout/terms_and_conditions.tpl',
    ],
);

if (fn_allowed_for('MULTIVENDOR')) {

    // Breadcrumbs at "companies.products" page
    $schema['breadcrumbs']['cache_overrides_by_dispatch']['companies.products'] = array(
        'request_handlers' => array('company_id', 'category_id'),
        'disable_cache_when' => array(
            'request_handlers' => array('features_hash'),
        ),
        'update_handlers' => array(
            'categories',
            'category_descriptions',
            'companies'
        )
    );

    // Main block at "companies.products" page
    $schema['main']['cache_overrides_by_dispatch']['companies.products'] = array(
        'request_handlers' => array('company_id', 'category_id', 'sort_by', 'sort_order', 'page'),
        'session_handlers' => array('items_per_page'),
        'cookie_handlers' => array('%ALL%'),
        'disable_cache_when' => array(
            'request_handlers' => array('price_to', 'price_from', 'features_hash', 'q'),
            'auth_handlers' => array(
                'user_id' => array('gt', 0)
            )
        ),
        'update_handlers' => array(
            'products_categories',
            'categories',
            'category_descriptions',
            'products',
            'product_descriptions',
            'product_tabs',
            'product_tabs_descriptions',
            'product_prices',
            'product_files',
            'product_file_descriptions',
            'product_feature_variants',
            'product_feature_variant_descriptions',
            'product_features',
            'product_features_descriptions',
            'product_features_values',
            'product_option_variants',
            'product_option_variants_descriptions',
            'product_options',
            'product_options_descriptions',
            'product_options_exceptions',
            'product_global_option_links',
        ),
        'callable_handlers' => array(
            'layout' => array('fn_get_products_layout', array([])),
            'currency' => array('fn_get_secondary_currency')
        )
    );

    $schema['vendors'] = array(
        'content' => array(
            'items' => array(
                'type' => 'enum',
                'object' => 'vendors',
                'remove_indent' => true,
                'hide_label' => true,
                'items_function' => 'fn_blocks_get_vendors',
                'fillings' => array(
                    'all' => array(),
                    'manually' => array(
                        'picker' => 'pickers/companies/picker.tpl',
                        'picker_params' => array(
                            'multiple' => true,
                        ),
                    )
                ),
            ),
        ),
        'settings' => array(
            'displayed_vendors' => array(
                'type' => 'input',
                'default_value' => '10'
            )
        ),
        'templates' => 'blocks/vendor_list_templates',
        'wrappers' => 'blocks/wrappers',
        'cache' => [
            'update_handlers'   => ['companies', 'company_descriptions', 'products', 'storefronts_companies'],
            'callable_handlers' => [
                'storefront' => ['fn_blocks_get_current_storefront_id'],
            ],
        ],
        'brief_info_function' => 'fn_block_get_vendors_info'
    );

    $schema['pages']['content']['items']['fillings']['vendor_pages'] = array(
        'params' => array(
            'status' => 'A',
            'vendor_pages' => true,
            'request' => array(
                'company_id' => '%COMPANY_ID%',
            )
        ),
    );

    $schema['pages']['cache']['request_handlers'][] = '%COMPANY_ID%';

    $schema['vendor_information'] = array(
        'templates' => array(
            'blocks/vendors/vendor_information.tpl' => array(),
        ),
        'wrappers' => 'blocks/wrappers',
        'content' => array(
            'vendor_info' => array(
                'type' => 'function',
                'function' => array('fn_blocks_get_vendor_info'),
            )
        ),
        'cache' => array(
            'update_handlers' => array('companies', 'company_descriptions', 'logos', 'images_links', 'images'),
            'request_handlers' => array('company_id')
        ),
    );

    $schema['vendor_logo'] = array(
        'templates' => array(
            'blocks/vendors/vendor_logo.tpl' => array(),
        ),
        'wrappers' => 'blocks/wrappers',
        'content' => array(
            'vendor_info' => array(
                'type' => 'function',
                'function' => array('fn_blocks_get_vendor_info'),
            )
        ),
        'cache' => array(
            'update_handlers' => array('companies', 'company_descriptions', 'logos', 'images_links', 'images'),
            'request_handlers' => array('company_id')
        ),
    );

    $schema['vendor_categories'] = array(
        'content' => array(
            'items' => array(
                'type' => 'enum',
                'object' => 'categories',
                'items_function' => 'fn_get_vendor_categories',
                'remove_indent' => true,
                'hide_label' => true,
                'fillings' => array(
                    'manually' => array(
                        'params' => array(
                            'plain' => true,
                            'simple' => false,
                            'group_by_level' => false,
                            'request' => array(
                                'company_ids' => '%COMPANY_ID%',
                            ),
                        ),
                        'picker' => 'pickers/categories/picker.tpl',
                        'picker_params' => array(
                            'multiple' => true,
                            'use_keys' => 'N',
                            'status' => 'A',
                            'positions' => true,
                        ),
                    ),
                    'newest' => array(
                        'params' => array(
                            'sort_by' => 'timestamp',
                            'plain' => true,
                            'visible' => true,
                            'request' => array(
                                'company_ids' => '%COMPANY_ID%',
                            ),
                        ),
                        'period' => array(
                            'type' => 'selectbox',
                            'values' => array(
                                'A' => 'any_date',
                                'D' => 'today',
                                'HC' => 'last_days',
                            ),
                            'default_value' => 'any_date'
                        ),
                        'last_days' => array(
                            'type' => 'input',
                            'default_value' => 1
                        ),
                                    'limit' => array(
                            'type' => 'input',
                            'default_value' => 3
                        )
                    ),
                    'full_tree_cat' => array(
                        'params' => array(
                            'plain' => true,
                            'request' => array(
                                'company_ids' => '%COMPANY_ID%',
                            ),
                        ),
                        'update_params' => array(
                            'request' => array('%CATEGORY_ID'),
                        ),
                        'settings' => array(
                            'parent_category_id' => array(
                                'type' => 'picker',
                                'default_value' => '0',
                                'picker' => 'pickers/categories/picker.tpl',
                                'picker_params' => array(
                                    'multiple' => false,
                                    'use_keys' => 'N',
                                    'default_name' => __('root_level'),
                                ),
                            ),
                            'sort_by' => array(
                                'type' => 'selectbox',
                                'values' => array(
                                    'position' => 'position',
                                    'name' => 'name',
                                ),
                                'default_value' => 'position'
                            ),
                        ),
                    ),
                    'subcategories_tree_cat' => array(
                        'params' => array(
                            'plain' => true,
                            'request' => array(
                                'category_id' => '%CATEGORY_ID%',
                                'company_ids' => '%COMPANY_ID%',
                            )
                        ),
                        'settings' => array(
                            'sort_by' => array(
                                'type' => 'selectbox',
                                'values' => array(
                                    'position' => 'position',
                                    'name' => 'name',
                                ),
                                'default_value' => 'position'
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'templates' =>  'blocks/categories',
        'wrappers' => 'blocks/wrappers',
        'cache' => array(
            'update_handlers' => array('categories', 'category_descriptions', 'companies', 'category_vendor_product_count'),
            'session_handlers' => array('current_category_id'),
            'request_handlers' => array('category_id', 'company_id'),
        ),
        'brief_info_function' => 'fn_block_get_block_with_items_info'
    );

    $schema['vendor_search'] = array(
        'templates' => array(
            'blocks/vendors/vendor_search.tpl' => array(),
        ),
        'wrappers' => 'blocks/wrappers',
    );

    // Vendor products list filter
    $product_filters_cache = $schema['product_filters']['cache'];
    $product_filters_cache['request_handlers'][] = 'company_id';
    $schema['product_filters']['cache_overrides_by_dispatch']['companies.products'] = $product_filters_cache;

    $schema['main']['cache_overrides_by_dispatch']['categories.view']['update_handlers'][]
        = $schema['products']['cache']['update_handlers'][]
        = $schema['product_filters']['cache']['update_handlers'][]
        = $schema['product_filters_home']['cache']['update_handlers'][]
        = 'companies';

    $schema['main']['cache_overrides_by_dispatch']['categories.view']['update_handlers'][]
        = $schema['products']['cache']['update_handlers'][]
        = $schema['product_filters']['cache']['update_handlers'][]
        = $schema['product_filters_home']['cache']['update_handlers'][]
        = 'storefronts_companies';

    $schema['lite_checkout_shipping_methods']['settings'] = [
        'show_vendor_info_if_shipping_no_required' => [
            'type' => 'checkbox',
            'default_value' => YesNo::YES
        ]
    ];
}

if (fn_allowed_for('ULTIMATE')) {
    // Field sharing tables
    $schema['main']['cache_overrides_by_dispatch']['categories.view']['update_handlers'][] = 'ult_product_prices';
    $schema['main']['cache_overrides_by_dispatch']['categories.view']['update_handlers'][] = 'ult_product_descriptions';
    $schema['main']['cache_overrides_by_dispatch']['categories.view']['update_handlers'][] = 'ult_product_option_variants';

    $schema['main']['cache_overrides_by_dispatch']['products.view']['update_handlers'][] = 'ult_product_prices';
    $schema['main']['cache_overrides_by_dispatch']['products.view']['update_handlers'][] = 'ult_product_descriptions';
    $schema['main']['cache_overrides_by_dispatch']['products.view']['update_handlers'][] = 'ult_product_option_variants';

    $schema['breadcrumbs']['cache_overrides_by_dispatch']['categories.view']['update_handlers'][] = 'ult_product_descriptions';
    $schema['breadcrumbs']['cache_overrides_by_dispatch']['products.view']['update_handlers'][] = 'ult_product_descriptions';

    $schema['products']['cache']['update_handlers'][] = 'ult_product_prices';
    $schema['products']['cache']['update_handlers'][] = 'ult_product_descriptions';
    $schema['products']['cache']['update_handlers'][] = 'ult_product_option_variants';
}

if (Registry::get('config.tweaks.disable_localizations') != true) {
    $schema['localizations'] = array(
        'templates' => 'blocks/localizations.tpl',
        'wrappers' => 'blocks/wrappers',
    );
}

$schema['safe_smarty_block'] = $schema['smarty_block'];
$schema['safe_smarty_block']['templates'] = 'blocks/safe_smarty_block.tpl';

return $schema;
