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

use Tygh\Enum\UserTypes;
use Tygh\Enum\YesNo;

defined('BOOTSTRAP') or die('Access denied');

$schema = [
    'conditions' => [
        'price'              => [
            'operators' => ['eq', 'neq', 'lte', 'gte', 'lt', 'gt'],
            'type'      => 'input',
            'field'     => 'base_price',
            'zones'     => ['catalog', 'cart'],
            'filter'    => 'fn_promotions_filter_float_condition_value',
        ],
        'categories'         => [
            'operators'    => ['in', 'nin'],
            'type'         => 'picker',
            'picker_props' => [
                'picker' => 'pickers/categories/picker.tpl',
                'params' => [
                    'multiple'  => true,
                    'use_keys'  => YesNo::NO,
                    'view_mode' => 'table',
                ],
            ],
            'field'        => 'category_ids',
            'zones'        => ['catalog', 'cart'],
        ],
        'products'           => [
            'operators'      => ['in', 'nin'],
            'type'           => 'picker',
            'picker_props'   => [
                'picker'         => 'pickers/products/picker.tpl',
                'params_cart'    => [
                    'type'                    => 'table',
                    'aoc'                     => YesNo::NO,
                    'only_selectable_options' => true
                ],
                'params_catalog' => [
                    'type' => 'links',
                ],
            ],
            'field_function' => ['fn_promotion_validate_product', '#this', '@product', '@cart_products'],
            'zones'          => ['catalog', 'cart'],
        ],
        'purchased_products' => [
            'operators'      => ['in'],
            'type'           => 'picker',
            'picker_props'   => [
                'picker' => 'pickers/products/picker.tpl',
                'params' => [
                    'type'    => 'table',
                    'display' => ''
                ],
            ],
            'field_function' => ['fn_promotion_validate_purchased_product', '#this', '@product', '@auth'],
            'zones'          => ['catalog'],
        ],
        'users'              => [
            'operators'    => ['in', 'nin'],
            'type'         => 'picker',
            'picker_props' => [
                'picker' => 'pickers/users/picker.tpl',
                'params' => [
                    'disable_no_item_text' => false,
                ],
            ],
            'field'        => '@auth.user_id',
            'zones'        => ['catalog', 'cart'],
        ],
        'feature'            => [
            'operators'       => ['eq', 'neq', 'lte', 'gte', 'lt', 'gt', 'in', 'nin', 'cont', 'ncont'],
            'type'            => 'chained',
            'chained_options' => [
                'parent_url' => 'product_features.get_features_list',
            ],
            'field_function'  => ['fn_promotions_check_features', '#this', '@product'],
            'zones'           => ['catalog', 'cart'],
        ],
        'usergroup' => [
            'operators'         => ['eq', 'neq'],
            'type'              => 'select',
            'variants_function' => ['fn_get_simple_usergroups', UserTypes::CUSTOMER, true],
            'field'             => '@auth.usergroup_ids',
            'zones'             => ['catalog', 'cart'],
        ],
        'country' => [
            'operators'         => ['eq', 'neq'],
            'type'              => 'select',
            'variants_function' => ['fn_get_simple_countries', true],
            'field'             => '@cart.user_data.s_country',
            'zones'             => ['cart'],
        ],
        'state' => [
            'operators' => ['eq', 'neq', 'in', 'nin'],
            'type'      => 'input',
            'field'     => '@cart.user_data.s_state',
            'zones'     => ['cart'],
        ],
        'zip_postal_code' => [
            'operators' => ['eq', 'neq', 'cont', 'ncont', 'in', 'nin'],
            'type'      => 'input',
            'field'     => '@cart.user_data.s_zipcode',
            'zones'     => ['cart'],
        ],
        'subtotal' => [
            'operators' => ['eq', 'neq', 'lte', 'gte', 'lt', 'gt', 'in', 'nin'],
            'type'      => 'input',
            'field'     => 'subtotal',
            'zones'     => ['cart'],
            'filter'    => 'fn_promotions_filter_float_condition_value',
        ],
        'products_number' => [
            'operators'      => ['eq', 'neq', 'lte', 'gte', 'lt', 'gt', 'in', 'nin'],
            'type'           => 'input',
            'field_function' => ['fn_get_products_amount', '@cart', '@cart_products', 'C'],
            'zones'          => ['cart'],
            'filter'         => 'fn_promotions_filter_float_condition_value',
        ],
        'total_weight' => [
            'operators'      => ['eq', 'neq', 'lte', 'gte', 'lt', 'gt', 'in', 'nin'],
            'type'           => 'input',
            'field_function' => ['fn_get_products_weight', '@cart', '@cart_products', 'C'],
            'zones'          => ['cart'],
            'filter'         => 'fn_promotions_filter_float_condition_value',
        ],
        'payment' => [
            'operators'         => ['eq', 'neq'],
            'type'              => 'select',
            'variants_function' => [
                'fn_get_payments',
                [
                    'simple'    => true,
                    'lang_code' => CART_LANGUAGE
                ],
            ],
            'field'             => 'payment_id',
            'zones'             => ['cart'],
        ],
        'shipping' => [
            'operators'         => ['eq', 'neq'],
            'type'              => 'select',
            'variants_function' => ['fn_get_shippings_names', fn_get_runtime_company_id()],
            'field_function'    => ['fn_promotion_shippings', '#this', '@cart'],
            'zones'             => ['cart'],
        ],
        'coupon_code' => [
            'operators'                       => ['eq', 'in'],
            // 'cont' - 'contains' was removed as ambiguous, but you can uncomment it back
            //'operators' => array ('eq', 'cont', 'in'),
            'type'                            => 'input',
            'field_function'                  => ['fn_promotion_validate_coupon', '#this', '@cart', '#id'],
            'after_conditions_check_function' => 'fn_promotion_check_coupon_code_once_per_customer',
            'zones'                           => ['cart'],
            'applicability'                   => [ // applicable for "positive" groups only
                'group' => [
                    'set_value' => true
                ],
            ],
        ],
        'number_of_usages' => [
            'operators'      => ['lte', 'lt'],
            'type'           => 'input',
            'field_function' => ['fn_promotion_get_dynamic', '#id', '#this', 'number_of_usages', '@cart'],
            'zones'          => ['cart'],
            'filter'         => 'fn_promotions_filter_int_condition_value',
        ],
        'once_per_customer' => [
            'type'           => 'statement',
            'field_function' => ['fn_promotion_get_dynamic', '#id', '#this', 'once_per_customer', '@cart', '@auth'],
            'zones'          => ['cart'],
        ],
        'auto_coupons' => [
            'type'           => 'list',
            'field_function' => ['fn_promotion_validate_coupon', '#this', '@cart', '#id'],
            'zones'          => ['cart'],
            'applicability'  => [ // applicable for "positive" groups only
                'group' => [
                    'set_value' => true
                ],
            ],
        ],
    ],
    'bonuses'    => [
        'product_discount' => [
            'function'         => ['fn_promotion_apply_catalog_rule', '#this', '@product', '@auth'],
            'discount_bonuses' => ['to_percentage', 'by_percentage', 'to_fixed', 'by_fixed'],
            'zones'            => ['catalog'],
        ],
        'order_discount' => [
            'function'         => ['fn_promotion_apply_cart_rule', '#this', '@cart', '@auth', '@cart_products'],
            'discount_bonuses' => ['to_percentage', 'by_percentage', 'to_fixed', 'by_fixed'],
            'zones'            => ['cart'],
            'filter'           => 'floatval',
        ],
        'discount_on_products' => [
            'type'             => 'picker',
            'picker_props'     => [
                'picker' => 'pickers/products/picker.tpl',
                'params' => [
                    'type' => 'links',
                ],
            ],
            'function'         => ['fn_promotion_apply_cart_rule', '#this', '@cart', '@auth', '@cart_products'],
            'discount_bonuses' => ['to_percentage', 'by_percentage', 'to_fixed', 'by_fixed'],
            'zones'            => ['cart'],
            'filter'           => 'floatval',
            'filter_field'     => 'discount_value',
        ],
        'discount_on_categories' => [
            'type'             => 'picker',
            'picker_props'     => [
                'picker' => 'pickers/categories/picker.tpl',
                'params' => [
                    'multiple'  => true,
                    'use_keys'  => YesNo::NO,
                    'view_mode' => 'table',
                ],
            ],
            'function'         => ['fn_promotion_apply_cart_rule', '#this', '@cart', '@auth', '@cart_products'],
            'discount_bonuses' => ['to_percentage', 'by_percentage', 'to_fixed', 'by_fixed'],
            'zones'            => ['cart'],
            'filter'           => 'floatval',
            'filter_field'     => 'discount_value',
        ],
        'give_usergroup' => [
            'type'              => 'select',
            'variants_function' => ['fn_get_simple_usergroups', UserTypes::CUSTOMER],
            'function'          => ['fn_promotion_apply_cart_rule', '#this', '@cart', '@auth', '@cart_products'],
            'zones'             => ['cart'],
        ],
        'give_coupon' => [
            'type'              => 'select',
            'function'          => ['fn_promotion_apply_cart_rule', '#this', '@cart', '@auth', '@cart_products'],
            'variants_function' => ['fn_get_promotions', ['zone' => 'cart', 'auto_coupons' => true, 'simple' => true]],
            'zones'             => ['cart'],
        ],
        'free_shipping' => [
            'type'              => 'select',
            'function'          => ['fn_promotion_apply_cart_rule', '#this', '@cart', '@auth', '@cart_products'],
            'variants_function' => ['fn_get_shippings_names', fn_get_runtime_company_id()],
            'zones'             => ['cart'],
        ],
        'free_products' => [
            'type'         => 'picker',
            'picker_props' => [
                'picker' => 'pickers/products/picker.tpl',
                'params' => [
                    'type' => 'table',
                    'aoc'  => true
                ],
            ],
            'function'     => ['fn_promotion_apply_cart_rule', '#this', '@cart', '@auth', '@cart_products'],
            'zones'        => ['cart'],
        ],
    ],
];

return $schema;
