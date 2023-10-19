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
    'selectable_statuses' => fn_get_simple_statuses(STATUSES_ORDER, true, true),
    'items'               => [
        'status'  => [
            'name'                => ['template' => 'status'],
            'type'                => ComponentItem::class,
            'template'            => 'views/orders/components/context_menu/status.tpl',
            'permission_callback' => static function () {
                return fn_check_permissions('orders', 'update_status', 'admin');
            },
            'position'            => 20,
        ],
        'print'   => [
            'name'     => ['template' => 'print_documents'],
            'type'     => GroupItem::class,
            'items'    => [
                'invoice'      => [
                    'name'     => ['template' => 'invoice'],
                    'dispatch' => 'orders.bulk_print',
                    'data'     => [
                        'action_class' => 'cm-new-window',
                    ],
                    'position' => 10,
                ],
                'packing_slip' => [
                    'name'     => ['template' => 'packing_slip'],
                    'dispatch' => 'orders.packing_slip',
                    'data'     => [
                        'action_class' => 'cm-new-window',
                    ],
                    'position' => 30,
                ],
            ],
            'position' => 30,
        ],
        'actions' => [
            'name'     => ['template' => 'actions'],
            'type'     => GroupItem::class,
            'items'    => [
                'view_purchased_products' => [
                    'name'     => ['template' => 'view_purchased_products'],
                    'dispatch' => 'orders.products_range',
                    'position' => 10,
                ],
                'actions_divider_1'       => [
                    'type'     => DividerItem::class,
                    'position' => 20,
                ],
                'export_selected'         => [
                    'name'     => ['template' => 'export_selected'],
                    'dispatch' => 'orders.export_range',
                    'position' => 30,
                ],
                'actions_divider_2'       => [
                    'type'     => DividerItem::class,
                    'position' => 40,
                ],
                'delete_selected'         => [
                    'name'     => ['template' => 'delete_selected'],
                    'dispatch' => 'orders.m_delete',
                    'data'     => [
                        'action_class'    => 'cm-confirm'
                    ],
                    'position' => 50,
                ],
            ],
            'position' => 40,
        ],
    ],
];
