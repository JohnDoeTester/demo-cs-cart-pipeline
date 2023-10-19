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

use Tygh\Addons\GraphqlApi\Context;
use Tygh\Addons\GraphqlApi\Type;
use Tygh\Registry;

$schema = [
    'name'        => 'ProductImage',
    'description' => 'Represents a product image',
    'fields'      => [
        'pair_id'     => [
            'type'        => Type::int(),
            'description' => 'Image pair ID',
        ],
        'image_id'    => [
            'type'        => Type::int(),
            'description' => 'Image ID',
        ],
        'detailed_id' => [
            'type'        => Type::int(),
            'description' => 'Detailed image ID',
        ],
        'position'    => [
            'type'        => Type::int(),
            'description' => 'Image order',
        ],
        'detailed'    => [
            'type'        => Type::resolveType('image'),
            'description' => 'Detailed image',
        ],
        'icon'        => [
            'type'        => Type::resolveType('image'),
            'description' => 'Image icon',
            'args'        => [
                'image_x' => [
                    'type'         => Type::int(),
                    'description'  => 'Icon width',
                    'defaultValue' => (int) Registry::get('settings.Thumbnails.product_lists_thumbnail_width'),
                ],
                'image_y' => [
                    'type'         => Type::int(),
                    'description'  => 'Icon height',
                    'defaultValue' => (int) Registry::get('settings.Thumbnails.product_lists_thumbnail_height'),
                ],
            ],
            'resolve'     => function ($source, $args, Context $context, $resolve_info) {
                $icon = fn_image_to_display($source['detailed'], $args['image_x'], $args['image_y']);

                // FIXME: Compatibility layer
                $icon['image_x'] = $args['image_x'];
                $icon['image_y'] = $args['image_y'];
                $icon['http_image_path'] = defined('HTTPS')
                    ? str_replace('https://', 'http://', $icon['image_path'])
                    : $icon['image_path'];
                $icon['https_image_path'] = defined('HTTPS')
                    ? $icon['image_path']
                    : str_replace('http://', 'https://', $icon['image_path']);

                return $icon;
            },
        ],
    ],
];

return $schema;
