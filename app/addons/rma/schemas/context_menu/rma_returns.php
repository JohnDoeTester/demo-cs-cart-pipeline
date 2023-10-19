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
        'actions' => [
            'name'     => ['template' => 'actions'],
            'type'     => GroupItem::class,
            'items'    => [
                'bulk_print_packing_slip' => [
                    'name'                => ['template' => 'bulk_print_packing_slip'],
                    'dispatch'            => 'rma.bulk_slip_print',
                    'data'                => [
                        'action_class'      => 'cm-new-window',
                        'action_attributes' => [
                            'data-ca-pass-selected-object-ids-as' => 'return_ids',
                        ],
                    ],
                    'position'            => 10,
                ],
                'delete_selected'   => [
                    'name'                => ['template' => 'delete_selected'],
                    'dispatch'            => 'rma.m_delete_returns',
                    'data'                => [
                        'action_class'    => 'cm-confirm',
                        'menu_item_class' => 'mobile-hide',
                    ],
                    'position'            => 20,
                ],
            ],
            'position' => 20,
        ],
    ],
];
