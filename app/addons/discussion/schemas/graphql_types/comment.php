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

use Tygh\Addons\GraphqlApi\Type;

return [
    'name'        => 'Comment',
    'description' => 'Represents product comment or review',
    'fields'      => [
        'post_id'   => [
            'type'        => Type::int(),
            'description' => 'Comment ID',
        ],
        'thread_id' => [
            'type'        => Type::int(),
            'description' => 'Thread ID',
        ],
        'message'   => [
            'type'        => Type::string(),
            'description' => 'Comment text',
        ],
        'user_id'   => [
            'type'        => Type::int(),
            'description' => 'Comment author user ID',
        ],
        'name'      => [
            'type'        => Type::string(),
            'description' => 'Comment author name',
        ],
    ],
];
