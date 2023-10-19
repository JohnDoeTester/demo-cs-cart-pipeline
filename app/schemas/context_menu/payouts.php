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
use Tygh\ContextMenu\Items\DividerItem;
use Tygh\ContextMenu\Items\GroupItem;
use Tygh\Enum\UserTypes;
use Tygh\Enum\VendorPayoutApprovalStatuses;
use Tygh\Http;

defined('BOOTSTRAP') or die('Access denied!');

$selectable_statuses = VendorPayoutApprovalStatuses::getWithDescriptions();

$schema = [
    'selectable_statuses' => $selectable_statuses,
    'items'               => [
        'status'  => [
            'name'                => ['template' => 'status'],
            'type'                => GroupItem::class,
            'items'               => [],
            'permission_callback' => static function ($request, $auth, $runtime) {
                return fn_check_permissions('companies', 'payouts', 'admin', Http::POST)
                    && UserTypes::isAdmin($auth['user_type']);
            },
            'position'            => 20,
        ],
        'actions' => [
            'name'     => ['template' => 'actions'],
            'type'     => GroupItem::class,
            'items'    => [
                'delete_selected' => [
                    'name'                => ['template' => 'delete_selected'],
                    'dispatch'            => 'companies.m_delete_payouts',
                    'data'                => [
                        'action_class' => 'cm-confirm',
                    ],
                    'permission_callback' => static function ($request, $auth, $runtime) {
                        return fn_check_permissions('companies', 'm_delete_payouts', 'admin', Http::POST)
                            && UserTypes::isAdmin($auth['user_type']);
                    },
                    'position'            => 10,
                ],
            ],
            'position' => 30,
        ],
    ],
];

unset($selectable_statuses[VendorPayoutApprovalStatuses::PENDING]);

$position = 10;
foreach (array_keys($selectable_statuses) as $status) {
    $item = [
        'name'     => [
            'template' => 'change_to_status',
            'params'   => [
                '[status]' => __('vendor_payouts.approval_status.' . $status),
            ],
        ],
        'dispatch' => 'companies.payouts.update_status',
        'data'     => [
            'action_attributes' => [
                'class'               => 'cm-ajax cm-post cm-ajax-send-form',
                'href'                => fn_url('companies.payouts.update_status?status=' . $status),
                'data-ca-target-id'   => 'manage_payouts_form',
                'data-ca-target-form' => '#manage_payouts_form',
            ],
        ],
        'position' => $position,
    ];

    $schema['items']['status']['items']['change_to_status_' . $status] = $item;
    $position += 10;
}

$schema['items']['status']['items']['status_divider'] = [
    'type'     => DividerItem::class,
    'position' => $position,
];
$position += 10;
$schema['items']['status']['items']['notify_checkboxes'] = [
    'type'     => ComponentItem::class,
    'template' => 'views/companies/components/context_menu/notify_checkboxes.tpl',
    'position' => $position,
];

return $schema;
