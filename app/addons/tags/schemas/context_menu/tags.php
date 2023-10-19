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

defined('BOOTSTRAP') or die('Access denied!');

return [
    'selectable_statuses' => fn_get_default_statuses('', false),
    'items'               => [
        'status'  => [
            'name'     => ['template' => 'status'],
            'type'     => GroupItem::class,
            'items'    => [
                'approve' => [
                    'name'     => [
                        'template' => 'change_to_status',
                        'params'   => [
                            '[status]' => __('active'),
                        ],
                    ],
                    'dispatch' => 'tags.approve',
                    'position' => 10,
                ],
                'disapprove' => [
                    'name'     => [
                        'template' => 'change_to_status',
                        'params'   => [
                            '[status]' => __('disabled'),
                        ],
                    ],
                    'dispatch' => 'tags.disapprove',
                    'position' => 20,
                ],
            ],
            'position' => 20,
        ],
        'actions' => [
            'name'     => ['template' => 'actions'],
            'type'     => GroupItem::class,
            'items'    => [
                'delete_selected' => [
                    'name'     => ['template' => 'delete_selected'],
                    'dispatch' => 'tags.m_delete',
                    'data'     => [
                        'action_class' => 'cm-confirm',
                    ],
                    'position' => 10,
                ],
            ],
            'position' => 30,
        ],
    ],
];
