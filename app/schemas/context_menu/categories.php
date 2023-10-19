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

use Tygh\ContextMenu\Items\ComponentItem;
use Tygh\ContextMenu\Items\GroupItem;
use Tygh\Http;

defined('BOOTSTRAP') or die('Access denied!');

return [
    'selectable_statuses' => fn_get_default_status_filters('', true),
    'items'               => [
        'status'        => [
            'name'     => ['template' => 'status'],
            'type'     => GroupItem::class,
            'items'    => [
                'change_to_active'   => [
                    'name'     => [
                        'template' => 'change_to_status',
                        'params'   => [
                            '[status]' => __('active'),
                        ],
                    ],
                    'dispatch' => 'categories.m_activate',
                    'position' => 10,
                ],
                'change_to_disabled' => [
                    'name'     => [
                        'template' => 'change_to_status',
                        'params'   => [
                            '[status]' => __('disabled'),
                        ],
                    ],
                    'dispatch' => 'categories.m_disable',
                    'position' => 20,
                ],
                'change_to_hidden'   => [
                    'name'     => [
                        'template' => 'change_to_status',
                        'params'   => [
                            '[status]' => __('hidden'),
                        ],
                    ],
                    'dispatch' => 'categories.m_hide',
                    'position' => 30,
                ],
            ],
            'position' => 20,
        ],
        'edit_selected' => [
            'type'                => ComponentItem::class,
            'template'            => 'views/categories/components/context_menu/edit_selected.tpl',
            'permission_callback' => static function ($request, $auth, $runtime) {
                return fn_check_permissions('categories', 'm_update', 'admin', Http::POST);
            },
            'position'            => 30,
        ],
        'actions'       => [
            'name'     => ['template' => 'actions'],
            'type'     => GroupItem::class,
            'items'    => [
                'delete' => [
                    'name'     => ['template' => 'delete_selected'],
                    'dispatch' => 'categories.m_delete',
                    'data'     => [
                        'action_class'      => 'cm-confirm',
                        'action_attributes' => [
                            'data-ca-confirm-text' => __('category_deletion_side_effects'),
                        ],
                    ],
                    'position' => 10,
                ],
                'export' => [
                    'name'     => ['template' => 'export_products'],
                    'dispatch' => 'products.export_range',
                    'position' => 20,
                ],
            ],
            'position' => 40,
        ],
    ],
];
