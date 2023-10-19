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

$selectable_statuses = fn_get_simple_statuses(STATUSES_SHIPMENT);

$schema = [
    'selectable_statuses' => $selectable_statuses,
    'items'               => [
        'status'  => [
            'name'     => ['template' => 'status'],
            'type'     => GroupItem::class,
            'items'    => [],
            'position' => 20,
        ],
        'actions' => [
            'name'     => ['template' => 'actions'],
            'type'     => GroupItem::class,
            'items'    => [
                'bulk_print_packing_slip' => [
                    'name'     => ['template' => 'bulk_print_packing_slip'],
                    'dispatch' => 'shipments.packing_slip',
                    'data'     => [
                        'action_class' => 'cm-new-window',
                    ],
                    'position' => 10,
                ],
                'actions_divider'         => [
                    'type'     => DividerItem::class,
                    'position' => 20,
                ],
                'delete_selected'         => [
                    'name'     => ['template' => 'delete_selected'],
                    'dispatch' => 'shipments.m_delete',
                    'data'     => [
                        'action_class' => 'cm-confirm',
                    ],
                    'position' => 30,
                ],
            ],
            'position' => 30,
        ],
    ],
];

$position = 10;
foreach ($selectable_statuses as $status => $status_name) {
    $item = [
        'name'     => [
            'template' => 'change_to_status',
            'params'   => [
                '[status]' => $status_name,
            ],
        ],
        'dispatch' => 'shipments.m_update_statuses',
        'data'     => [
            'action_attributes' => [
                'class'               => 'cm-ajax cm-post cm-ajax-send-form',
                'href'                => fn_url('shipments.m_update_statuses?status=' . $status),
                'data-ca-target-id'   => 'shipments_content',
                'data-ca-target-form' => '#shipments_form',
            ],
        ],
        'position' => $position,
    ];

    $schema['items']['status']['items']['change_to_status_' . $status] = $item;
    $position += 10;
}

return $schema;
