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
use Tygh\ContextMenu\Items\GroupItem;

defined('BOOTSTRAP') or die('Access denied!');

/** @var array|null $selectable_statuses */
$selectable_statuses = fn_get_predefined_statuses('companies');

$schema = [
    'selectable_statuses' => $selectable_statuses,
    'items'               => [
        'status'  => [
            'name'                => ['template' => 'status'],
            'type'                => GroupItem::class,
            'items'               => [],
            'permission_callback' => static function ($request, $auth, $runtime) {
                return !$runtime['company_id'];
            },
            'position'            => 20,
        ],
        'actions' => [
            'name'     => ['template' => 'actions'],
            'type'     => GroupItem::class,
            'items'    => [
                'view_vendor_products' => [
                    'name'     => ['template' => 'view_vendor_products'],
                    'dispatch' => 'products.manage',
                    'data'     => [
                        'action_attributes' => [
                            'data-ca-pass-selected-object-ids-as' => 'company_ids',
                        ],
                    ],
                    'position' => 10,
                ],
                'view_vendor_admins'   => [
                    'name'     => ['template' => 'view_vendor_admins'],
                    'dispatch' => 'profiles.manage',
                    'data'     => [
                        'action_attributes' => [
                            'data-ca-pass-selected-object-ids-as' => 'company_ids',
                        ],
                    ],
                    'position' => 20,
                ],
                'view_vendor_orders'   => [
                    'name'     => ['template' => 'view_vendor_orders'],
                    'dispatch' => 'orders.manage',
                    'data'     => [
                        'action_attributes' => [
                            'data-ca-pass-selected-object-ids-as' => 'company_ids',
                        ],
                    ],
                    'position' => 30,
                ],
                'actions_divider'      => [
                    'type'     => DividerItem::class,
                    'position' => 40,
                ],
                'delete_selected'      => [
                    'name'     => ['template' => 'delete_selected'],
                    'dispatch' => 'companies.m_delete',
                    'data'     => [
                        'action_class' => 'cm-confirm',
                    ],
                    'position' => 50,
                ],
                'export_selected'      => [
                    'name'     => ['template' => 'export_selected'],
                    'dispatch' => 'companies.export_range',
                    'position' => 60,
                ],
            ],
            'position' => 30,
        ],
    ],
];

$position = 10;
foreach ((array) $selectable_statuses as $status => $status_name) {
    $item = [
        'name'     => [
            'template' => 'change_to_status',
            'params'   => [
                '[status]' => $status_name,
            ],
        ],
        'dispatch' => 'companies.m_update_statuses',
        'data'     => [
            'action_class'      => 'cm-dialog-opener',
            'action_attributes' => [
                'data-ca-target-id' => 'content_selected_make_status_' . $status,
            ],
        ],
        'position' => $position,
    ];

    $schema['items']['status']['items']['change_to_status_' . $status] = $item;
    $position += 10;
}

return $schema;
