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
use Tygh\Http;

defined('BOOTSTRAP') or die('Access denied!');

return [
    'items' => [
        'send_message' => [
            'name'                => ['template' => 'vendor_communication.send_message'],
            'type'                => ComponentItem::class,
            'template'            => 'addons/vendor_communication/views/vendor_communication/components/context_menu/send_message.tpl',
            'permission_callback' => static function ($request, $auth, $runtime) {
                return fn_check_permissions('vendor_communication', 'm_post_message', 'admin', Http::POST, $request);
            },
            'position'            => 30,
        ],
        'actions'      => [
            'name'     => ['template' => 'actions'],
            'type'     => GroupItem::class,
            'items'    => [
                'delete_selected' => [
                    'name'     => ['template' => 'delete_selected'],
                    'dispatch' => 'vendor_communication.m_delete_thread',
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
