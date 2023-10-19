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

defined('BOOTSTRAP') or die('Access denied');

$schema['controllers']['discussion'] = [
    'modes' => [
        'add'      => [
            'permissions' => true,
        ],
        /**
         * discussion.view is not used in the administration panel,
         * but this action is required for proper permissions check of vendors
         */
        'view'     => [
            'permissions' => true,
        ],
        'update'   => [
            'permissions' => false,
        ],
        'delete'   => [
            'permissions' => false,
        ],
        'm_delete' => [
            'permissions' => false,
        ],
        /**
         * For add-on Vendor privileges
         */
        'products_and_pages' => [
            'permissions' => true,
        ],
    ],
];

$schema['controllers']['discussion_manager'] = [
    'modes' => [
        'manage' => [
            'permissions' => true,
        ],
    ],
];

$schema['index']['modes']['set_post_status'] = [
    'permissions' => false,
];

$schema['index']['modes']['delete_post'] = [
    'permissions' => false,
];

$schema['tools']['modes']['update_status']['param_permissions']['table']['discussion_posts'] = false;

return $schema;
