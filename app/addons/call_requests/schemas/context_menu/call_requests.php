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

$selectable_statuses = db_get_list_elements('call_requests', 'status', true, DESCR_SL, 'call_requests.status.');

$schema = [
    'selectable_statuses' => $selectable_statuses,
    'items'               => [
        'status'  => [
            'name'     => ['template' => 'status'],
            'type'     => GroupItem::class,
            'items'    => [],
            'position' => 20,
        ],
        'responsible' => [
            'name'                => ['template' => 'call_requests.responsible'],
            'type'                => ComponentItem::class,
            'template'            => 'addons/call_requests/views/call_requests/components/context_menu/responsible.tpl',
            'permission_callback' => static function ($request, $auth, $runtime) {
                return fn_check_user_access($auth['user_id'], 'manage_call_requests');
            },
            'position'            => 30,
        ],
        'actions' => [
            'name'     => ['template' => 'actions'],
            'type'     => GroupItem::class,
            'items'    => [
                'delete_selected'   => [
                    'name'     => ['template' => 'delete_selected'],
                    'dispatch' => 'call_requests.m_delete',
                    'data'     => [
                        'action_class' => 'cm-confirm',
                    ],
                    'position' => 10,
                ],
            ],
            'position' => 40,
        ],
    ],
];

$position = 10;
foreach (array_keys($selectable_statuses) as $status) {
    $item = [
        'name'     => [
            'template' => 'change_to_status',
            'params'   => [
                '[status]' => __('call_requests.status.' . $status),
            ],
        ],
        'dispatch' => 'call_requests.m_update_statuses',
        'data'     => [
            'action_attributes' => [
                'class'               => 'cm-ajax cm-post cm-ajax-send-form',
                'href'                => fn_url('call_requests.m_update_statuses?status=' . $status),
                'data-ca-target-id'   => 'pagination_contents',
                'data-ca-target-form' => '#manage_call_requests_form',
            ],
        ],
        'position' => $position,
    ];

    $schema['items']['status']['items']['change_to_status_' . $status] = $item;
    $position += 10;
}

return $schema;
