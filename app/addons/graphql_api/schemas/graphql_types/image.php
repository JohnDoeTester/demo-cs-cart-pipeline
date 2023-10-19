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

$schema = [
    'name'        => 'Image',
    'description' => 'Represents an image',
    'fields'      => [
        'image_path'       => [
            'type'       => Type::string(),
            'description' => 'Image URL',
        ],
        'alt'        => [
            'type'        => Type::string(),
            'description' => 'Image alt',
        ],
        'image_x'          => [
            'type'       => Type::int(),
            'description' => 'Image width',
        ],
        'image_y'          => [
            'type'       => Type::int(),
            'description' => 'Image height',
        ],
        'http_image_path'  => [
            'type'       => Type::string(),
            'description' => 'Image URL with HTTP',
        ],
        'https_image_path' => [
            'type'       => Type::string(),
            'description' => 'Image URL with HTTPS',
        ],
    ],
];

return $schema;
