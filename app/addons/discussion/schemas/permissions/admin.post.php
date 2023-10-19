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

use Tygh\Enum\Addons\Discussion\DiscussionObjectTypes;

defined('BOOTSTRAP') or die('Access denied');

$schema['discussion'] = [
    'modes'       => [
        'add'      => [
            'permissions' => 'manage_discussions',
        ],
        /**
         * discussion.view is not used in the administration panel,
         * but this action is required for proper permissions check of vendors
         */
        'view'     => [
            'permissions' => 'view_discussions',
        ],
        'update'   => [
            'param_permissions' => [
                'discussion_type' => [
                    DiscussionObjectTypes::TESTIMONIALS_AND_LAYOUT => 'view_discussions',
                ],
            ],
        ],
        'delete'   => [
            'permissions' => 'manage_discussions',
        ],
        'm_delete' => [
            'permissions' => 'manage_discussions',
        ],
    ],
    'permissions' => 'manage_discussions',
];

$schema['discussion_manager'] = [
    'modes' => [
        'manage' => [
            'permissions' => 'view_discussions',
        ],
    ],
];

$schema['index']['modes']['set_post_status'] = [
    'permissions' => 'manage_discussions',
];

$schema['index']['modes']['delete_post'] = [
    'permissions' => 'manage_discussions',
];

$schema['tools']['modes']['update_status']['param_permissions']['table']['discussion_posts'] = 'manage_discussions';

return $schema;
