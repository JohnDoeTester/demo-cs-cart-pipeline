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
use Tygh\Enum\YesNo;
use Tygh\Http;

defined('BOOTSTRAP') or die('Access denied!');

$selectable_statuses = fn_get_default_statuses('', true);

$schema = [
    'selectable_statuses' => $selectable_statuses,
    'items'               => [
        'status'   => [
            'name'     => ['template' => 'status'],
            'type'     => GroupItem::class,
            'items'    => [],
            'position' => 20,
        ],
        'category' => [
            'type'                => ComponentItem::class,
            'template'            => 'views/product_features/components/context_menu/categories.tpl',
            'permission_callback' => static function () {
                return fn_check_permissions('product_features', 'm_set_categories', 'admin', Http::POST);
            },
            'position'            => 30,
        ],
        'display'  => [
            'name'     => ['template' => 'display'],
            'type'     => GroupItem::class,
            'items'    => [
                'feature_display_on_product' => [
                    'name'     => ['template' => 'feature_display_on_product'],
                    'dispatch' => 'product_features.m_set_display',
                    'data'     => [
                        'action_attributes' => [
                            'class'               => 'cm-ajax cm-post cm-ajax-send-form',
                            'href'                => fn_url('product_features.m_set_display?display_on=product&value=' . YesNo::YES),
                            'data-ca-target-form' => '#manage_product_features_form',
                        ],
                    ],
                    'position' => 10,
                ],
                'feature_hide_on_product'    => [
                    'name'     => ['template' => 'feature_hide_on_product'],
                    'dispatch' => 'product_features.m_set_display',
                    'data'     => [
                        'action_attributes' => [
                            'class'               => 'cm-ajax cm-post cm-ajax-send-form',
                            'href'                => fn_url('product_features.m_set_display?display_on=product&value=' . YesNo::NO),
                            'data-ca-target-form' => '#manage_product_features_form',
                        ],
                    ],
                    'position' => 20,
                ],
                'feature_display_on_catalog' => [
                    'name'     => ['template' => 'feature_display_on_catalog'],
                    'dispatch' => 'product_features.m_set_display',
                    'data'     => [
                        'action_attributes' => [
                            'class'               => 'cm-ajax cm-post cm-ajax-send-form',
                            'href'                => fn_url('product_features.m_set_display?display_on=catalog&value=' . YesNo::YES),
                            'data-ca-target-form' => '#manage_product_features_form',
                        ],
                    ],
                    'position' => 30,
                ],
                'feature_hide_on_catalog'    => [
                    'name'     => ['template' => 'feature_hide_on_catalog'],
                    'dispatch' => 'product_features.m_set_display',
                    'data'     => [
                        'action_attributes' => [
                            'class'               => 'cm-ajax cm-post cm-ajax-send-form',
                            'href'                => fn_url('product_features.m_set_display?display_on=catalog&value=' . YesNo::NO),
                            'data-ca-target-form' => '#manage_product_features_form',
                        ],
                    ],
                    'position' => 40,
                ],
                'feature_display_on_header'  => [
                    'name'     => ['template' => 'feature_display_on_header'],
                    'dispatch' => 'product_features.m_set_display',
                    'data'     => [
                        'action_attributes' => [
                            'class'               => 'cm-ajax cm-post cm-ajax-send-form',
                            'href'                => fn_url('product_features.m_set_display?display_on=header&value=' . YesNo::YES),
                            'data-ca-target-form' => '#manage_product_features_form',
                        ],
                    ],
                    'position' => 50,
                ],
                'feature_hide_on_header'     => [
                    'name'     => ['template' => 'feature_hide_on_header'],
                    'dispatch' => 'product_features.m_set_display',
                    'data'     => [
                        'action_attributes' => [
                            'class'               => 'cm-ajax cm-post cm-ajax-send-form',
                            'href'                => fn_url('product_features.m_set_display?display_on=header&value=' . YesNo::NO),
                            'data-ca-target-form' => '#manage_product_features_form',
                        ],
                    ],
                    'position' => 60,
                ],
            ],
            'position' => 40,
        ],
        'actions'  => [
            'name'     => ['template' => 'actions'],
            'type'     => GroupItem::class,
            'items'    => [
                'delete_selected' => [
                    'name'     => ['template' => 'delete_selected'],
                    'dispatch' => 'product_features.m_delete',
                    'data'     => [
                        'action_class' => 'cm-confirm',
                    ],
                    'position' => 10,
                ],
            ],
            'position' => 50,
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
        'dispatch' => 'product_features.m_update_statuses',
        'data'     => [
            'action_attributes' => [
                'class'               => 'cm-ajax cm-post cm-ajax-send-form',
                'href'                => fn_url('product_features.m_update_statuses?status=' . $status),
                'data-ca-target-id'   => 'update_features_list',
                'data-ca-target-form' => '#manage_product_features_form',
            ],
        ],
        'position' => $position,
    ];

    $schema['items']['status']['items']['change_to_status_' . $status] = $item;
    $position += 10;
}

return $schema;
