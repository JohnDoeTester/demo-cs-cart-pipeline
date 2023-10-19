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

use Tygh\ContextMenu\Items\DividerItem;
use Tygh\ContextMenu\Items\ComponentItem;
use Tygh\ContextMenu\Items\GroupItem;

defined('BOOTSTRAP') or die('Access denied!');

return [
    'selectable_statuses' => fn_get_default_status_filters('', true),
    'items'               => [
        'category'         => [
            'name'                => ['template' => 'category'],
            'type'                => ComponentItem::class,
            'template'            => 'views/products/components/context_menu/categories.tpl',
            'permission_callback' => static function () {
                return fn_check_permissions('products', 'm_update_categories', 'admin');
            },
            'position'            => 20,
        ],
        'price'            => [
            'name'                => ['template' => 'bulk_edit.price_and_stock'],
            'type'                => ComponentItem::class,
            'template'            => 'views/products/components/context_menu/price.tpl',
            'permission_callback' => static function () {
                return fn_check_permissions('products', 'm_update_prices', 'admin');
            },
            'position'            => 30,
        ],
        'status'           => [
            'name'     => ['template' => 'status'],
            'type'     => GroupItem::class,
            'items'    => [
                'make_active'   => [
                    'name'          => [
                        'template' => 'change_to_status',
                        'params'   => [
                            '[status]' => __('active')
                        ],
                    ],
                    'dispatch' => 'products.m_activate',
                    'position' => 10,
                ],
                'make_disabled' => [
                    'name'     => [
                        'template' => 'change_to_status',
                        'params'   => [
                            '[status]' => __('disabled')
                        ],
                    ],
                    'dispatch' => 'products.m_disable',
                    'position' => 20,
                ],
                'make_hidden'   => [
                    'name'     => [
                        'template' => 'change_to_status',
                        'params'   => [
                            '[status]' => __('hidden')
                        ],
                    ],
                    'dispatch' => 'products.m_hide',
                    'position' => 30,
                ],
            ],
            'position' => 40,
        ],
        'edit_selected'    => [
            'type'                => ComponentItem::class,
            'template'            => 'views/products/components/context_menu/edit_selected.tpl',
            'permission_callback' => static function ($request, $auth, $runtime) {
                return fn_check_user_access($auth['user_id'], 'manage_catalog');
            },
            'position'            => 50,
        ],
        'actions'          => [
            'name'     => ['template' => 'actions'],
            'type'     => GroupItem::class,
            'items'    => [
                'clone'           => [
                    'name'     => ['template' => 'clone_selected'],
                    'dispatch' => 'products.m_clone',
                    'position' => 10,
                ],
                'export'          => [
                    'name'     => ['template' => 'export_selected'],
                    'dispatch' => 'products.export_range',
                    'position' => 20,
                ],
                'actions_divider' => [
                    'type'     => DividerItem::class,
                    'position' => 30,
                ],
                'delete'          => [
                    'name'     => ['template' => 'delete_selected'],
                    'dispatch' => 'products.m_delete',
                    'data'     => [
                        'action_class' => 'cm-confirm',
                    ],
                    'position' => 40,
                ],
            ],
            'position' => 60,
        ],
    ],
];
