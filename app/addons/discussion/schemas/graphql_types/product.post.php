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
use Tygh\Registry;

/** @var array $schema */

$schema['fields']['comments'] = [
    'type'        => Type::listOf(Type::resolveType('comment')),
    'description' => 'Comments and reviews',
    'args'        => [
        'page'           => [
            'type'         => Type::int(),
            'defaultValue' => 1,
            'description'  => 'Page',
        ],
        'items_per_page' => [
            'type'         => Type::int(),
            'defaultValue' => Registry::get('settings.Appearance.admin_elements_per_page'),
            'description'  => 'Items per page',
        ],
    ],
    'resolve'     => function ($source, $args) {
        if (empty($source['product_id'])) {
            return [];
        }
        $params = [
            'object_id'   => (int) $source['product_id'],
            'object_type' => DISCUSSION_OBJECT_TYPE_PRODUCT,
            'page'        => $args['page'],
        ];
        list($discussions, $search) = fn_get_discussions($params, $args['items_per_page']);

        return $discussions;
    },
];

return $schema;
