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
use Tygh\Enum\UserTypes;

defined('BOOTSTRAP') or die('Access denied!');

return [
    'selectable_statuses' => fn_get_default_status_filters('', true),
    'items'               => [
        'status'  => [
            'name'                => ['template' => 'status'],
            'type'                => GroupItem::class,
            'data'                => [
                'menu_item_class' => 'cm-no-hide-input',
            ],
            'items'               => [
                'm_activate' => [
                    'name'          => [
                        'template' => 'change_to_status',
                        'params'   => [
                            '[status]' => __('active')
                        ],
                    ],
                    'dispatch' => 'profiles.m_activate',
                    'position' => 10,
                ],
                'm_disable'         => [
                    'name'     => [
                        'template' => 'change_to_status',
                        'params'   => [
                            '[status]' => __('disabled')
                        ],
                    ],
                    'dispatch' => 'profiles.m_disable',
                    'position' => 20,
                ],
                'notify_checkboxes' => [
                    'type'     => ComponentItem::class,
                    'template' => 'views/profiles/components/context_menu/notify_checkboxes.tpl',
                    'position' => 30,
                ],
            ],
            'permission_callback' => static function ($request, $auth, $runtime) {
                return !(
                    UserTypes::isVendor($auth['user_type'])
                    && UserTypes::isCustomer($request['user_type'])
                    && fn_check_permissions('profiles', 'm_activate', 'admin', 'POST', ['user_type' => $request['user_type']])
                    && fn_check_permissions('profiles', 'm_disable', 'admin', 'POST', ['user_type' => $request['user_type']])
                );
            },
            'position'            => 20,
        ],
        'actions' => [
            'name'     => ['template' => 'actions'],
            'type'     => GroupItem::class,
            'items'    => [
                'view_orders'       => [
                    'name'                => ['template' => 'view_orders'],
                    'dispatch'            => 'orders.manage',
                    'data'                => [
                        'action_attributes' => [
                            'data-ca-pass-selected-object-ids-as' => 'user_ids',
                        ],
                    ],
                    'permission_callback' => static function () {
                        return fn_check_view_permissions('orders.manage', 'GET');
                    },
                    'position'            => 10,
                ],
                'actions_divider'   => [
                    'type'     => DividerItem::class,
                    'position' => 20,
                ],
                'export_selected'   => [
                    'name'                => ['template' => 'export_selected'],
                    'dispatch'            => 'profiles.export_range',
                    'permission_callback' => static function ($request, $auth, $runtime) {
                        return (
                            fn_check_view_permissions('profiles.export_range', 'POST')
                            && (
                                fn_allowed_for('ULTIMATE')
                                || !$runtime['company_id']
                            )
                        );
                    },
                    'position'            => 30,
                ],
                'actions_divider_2' => [
                    'type'     => DividerItem::class,
                    'position' => 40,
                ],
                'delete_selected'   => [
                    'name'                => ['template' => 'delete_selected'],
                    'dispatch'            => 'profiles.m_delete',
                    'data'                => [
                        'action_class' => 'cm-confirm',
                    ],
                    'permission_callback' => static function ($request, $auth, $runtime) {
                        return fn_check_permissions('profiles', 'm_delete', 'admin', 'POST', ['user_type' => $request['user_type']]);
                    },
                    'position'            => 50,
                ],
            ],
            'position' => 30,
        ],
    ],
];
