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

$selectable_statuses = fn_get_default_statuses('', true);

return [
    'selectable_statuses' => $selectable_statuses,
    'items'               => [
        'status'           => [
            'name'     => ['template' => 'status'],
            'type'     => GroupItem::class,
            'items'    => [
                'make_active'   => [
                    'name'          => [
                        'template' => 'change_to_status',
                        'params'   => [
                            '[status]' => __('active')
                        ],
                    ],
                    'dispatch' => 'products.m_activate',
                    'position' => 10,
                ],
                'make_disabled' => [
                    'name'     => [
                        'template' => 'change_to_status',
                        'params'   => [
                            '[status]' => __('disabled')
                        ],
                    ],
                    'dispatch' => 'products.m_disable',
                    'position' => 20,
                ],
                'make_hidden'   => [
                    'name'     => [
                        'template' => 'change_to_status',
                        'params'   => [
                            '[status]' => __('hidden')
                        ],
                    ],
                    'dispatch' => 'products.m_hide',
                    'position' => 30,
                ],
            ],
            'position' => 20,
        ],
        'actions' => [
            'name'     => ['template' => 'actions'],
            'type'     => GroupItem::class,
            'items'    => [
                'remove_variations'   => [
                    'name'     => ['template' => 'product_variations.remove_variation'],
                    'dispatch' => 'product_variations.m_delete_product',
                    'data'     => [
                        'action_class' => 'cm-confirm',
                    ],
                    'position' => 10,
                ],
                'delete_selected'   => [
                    'name'     => ['template' => 'delete_selected'],
                    'dispatch' => 'products.m_delete',
                    'data'     => [
                        'action_class' => 'cm-confirm',
                    ],
                    'position' => 20,
                ],
            ],
            'position' => 30,
        ],
    ],
];
