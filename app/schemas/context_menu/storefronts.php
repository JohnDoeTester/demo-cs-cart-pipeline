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
    'selectable_statuses' => [],
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
                'open_selected'   => [
                    'name'     => ['template' => 'open_selected_storefronts'],
                    'dispatch' => 'storefronts.m_open',
                    'data'     => [
                        'action_class' => 'cm-confirm',
                    ],
                    'position' => 10,
                ],
                'close_selected'   => [
                    'name'     => ['template' => 'close_selected_storefronts'],
                    'dispatch' => 'storefronts.m_close',
                    'data'     => [
                        'action_class' => 'cm-confirm',
                    ],
                    'position' => 20,
                ],
                'delete_selected'   => [
                    'name'     => ['template' => 'delete_selected'],
                    'dispatch' => 'storefronts.m_delete',
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
