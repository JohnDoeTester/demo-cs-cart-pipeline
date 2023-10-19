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

use Tygh\ContextMenu\Items\GroupItem;
use Tygh\Enum\YesNo;

defined('BOOTSTRAP') or die('Access denied!');

$selectable_statuses = fn_get_default_statuses('', true);

$schema = [
    'selectable_statuses' => $selectable_statuses,
    'items'               => [
        'status'  => [
            'name'     => ['template' => 'status'],
            'type'     => GroupItem::class,
            'items'    => [],
            'position' => 20,
        ],
        'display'  => [
            'name'     => ['template' => 'display'],
            'type'     => GroupItem::class,
            'items'    => [
                'show_at_checkout' => [
                    'name'     => ['template' => 'show_on_checkout'],
                    'dispatch' => 'mailing_lists.m_set_display',
                    'data'     => [
                        'action_attributes' => [
                            'class'               => 'cm-ajax cm-post cm-ajax-send-form',
                            'href'                => fn_url('mailing_lists.m_set_display?display_on=checkout&value=' . YesNo::YES),
                            'data-ca-target-form' => '#mailing_lists_form',
                        ],
                    ],
                    'position' => 10,
                ],
                'hide_at_checkout'    => [
                    'name'     => ['template' => 'hide_on_checkout'],
                    'dispatch' => 'mailing_lists.m_set_display',
                    'data'     => [
                        'action_attributes' => [
                            'class'               => 'cm-ajax cm-post cm-ajax-send-form',
                            'href'                => fn_url('mailing_lists.m_set_display?display_on=checkout&value=' . YesNo::NO),
                            'data-ca-target-form' => '#mailing_lists_form',
                        ],
                    ],
                    'position' => 20,
                ],
                'show_on_registration_and_profile_details_page' => [
                    'name'     => ['template' => 'addons.newsletters.show_on_registration_and_profile'],
                    'dispatch' => 'mailing_lists.m_set_display',
                    'data'     => [
                        'action_attributes' => [
                            'class'               => 'cm-ajax cm-post cm-ajax-send-form',
                            'href'                => fn_url('mailing_lists.m_set_display?display_on=registration&value=' . YesNo::YES),
                            'data-ca-target-form' => '#mailing_lists_form',
                        ],
                    ],
                    'position' => 30,
                ],
                'hide_on_registration_and_profile_details_page'    => [
                    'name'     => ['template' => 'addons.newsletters.hide_on_registration_and_profile'],
                    'dispatch' => 'mailing_lists.m_set_display',
                    'data'     => [
                        'action_attributes' => [
                            'class'               => 'cm-ajax cm-post cm-ajax-send-form',
                            'href'                => fn_url('mailing_lists.m_set_display?display_on=registration&value=' . YesNo::NO),
                            'data-ca-target-form' => '#mailing_lists_form',
                        ],
                    ],
                    'position' => 40,
                ],
            ],
            'position' => 30,
        ],
        'actions' => [
            'name'     => ['template' => 'actions'],
            'type'     => GroupItem::class,
            'items'    => [
                'delete_selected'   => [
                    'name'     => ['template' => 'delete_selected'],
                    'dispatch' => 'mailing_lists.m_delete',
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
foreach ($selectable_statuses as $status => $status_name) {
    $item = [
        'name'     => [
            'template' => 'change_to_status',
            'params'   => [
                '[status]' => $status_name,
            ],
        ],
        'dispatch' => 'mailing_lists.m_update_statuses',
        'data'     => [
            'action_attributes' => [
                'class'               => 'cm-ajax cm-post cm-ajax-send-form',
                'href'                => fn_url('mailing_lists.m_update_statuses?status=' . $status),
                'data-ca-target-id'   => 'mailing_lists_form',
                'data-ca-target-form' => '#mailing_lists_form',
            ],
        ],
        'position' => $position,
    ];

    $schema['items']['status']['items']['change_to_status_' . $status] = $item;
    $position += 10;
}

return $schema;
