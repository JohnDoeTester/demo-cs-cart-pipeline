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
    'items' => [
        'actions' => [
            'name'     => ['template' => 'actions'],
            'type'     => GroupItem::class,
            'items'    => [
                'delete_selected' => [
                    'name'     => ['template' => 'delete_selected'],
                    'dispatch' => 'import_presets.m_delete',
                    'data'     => [
                        'action_class'      => 'cm-confirm',
                        'action_attributes' => [
                            'data-ca-confirm-text' => __('advanced_import.file_will_be_deleted_are_you_sure_to_proceed'),
                        ],
                    ],
                    'position' => 10,
                ],
            ],
            'position' => 20,
        ],
    ],
];
