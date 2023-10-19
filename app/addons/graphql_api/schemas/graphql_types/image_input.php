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

use Tygh\Addons\GraphqlApi\InputType as Type;

$schema = [
    'name'        => 'ImageInput',
    'description' => 'Represents a set of data to update an image',
    'fields'      => [
        'upload'     => [
            'type'        => Type::resolveType('file_upload'),
            'description' => 'File upload',
        ],
        'image_path' => [
            'type'        => Type::string(),
            'description' => 'Image URL or path on server',
        ],
        'alt'        => [
            'type'        => Type::string(),
            'description' => 'Image alt',
        ],
    ],
];

return $schema;
