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

defined('BOOTSTRAP') or die('Access denied!');

$selectable_statuses = fn_get_default_statuses('', true);

$schema = [
    'selectable_statuses' => $selectable_statuses,
    'items'               => [
        'parent'  => [
            'name'     => ['template' => 'parent'],
            'type'     => ComponentItem::class,
            'template' => 'views/pages/components/context_menu/parent.tpl',
            'position' => 20,
        ],
        'status'  => [
            'name'     => ['template' => 'status'],
            'type'     => GroupItem::class,
            'items'    => [],
            'position' => 30,
        ],
        'actions' => [
            'name'     => ['template' => 'actions'],
            'type'     => GroupItem::class,
            'items'    => [
                'clone_selected'  => [
                    'name'     => ['template' => 'clone_selected'],
                    'dispatch' => 'pages.m_clone',
                    'position' => 10,
                ],
                'delete_selected' => [
                    'name'     => ['template' => 'delete_selected'],
                    'dispatch' => 'pages.m_delete',
                    'data'     => [
                        'action_class' => 'cm-confirm',
                    ],
                    'position' => 20,
                ],
            ],
            'position' => 40,
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
        'dispatch' => 'pages.m_update_statuses',
        'data'     => [
            'action_attributes' => [
                'class'               => 'cm-ajax cm-post cm-ajax-send-form',
                'href'                => fn_url('pages.m_update_statuses?status=' . $status),
                'data-ca-target-id'   => 'pagination_contents',
                'data-ca-target-form' => '#pages_tree_form',
            ],
        ],
        'position' => $position,
    ];

    $schema['items']['status']['items']['change_to_status_' . $status] = $item;
    $position += 10;
}

return $schema;
