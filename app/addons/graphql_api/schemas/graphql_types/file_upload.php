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
    'name'        => 'FileUpload',
    'description' => 'Represents a set of data to upload a file',
    'fields'      => [
        'name'     => [
            'type'        => Type::nonNull(Type::listOf(Type::string())),
            'description' => 'File upload name',
        ],
        'error'    => [
            'type'        => Type::nonNull(Type::listOf(Type::int())),
            'description' => 'Error code',
        ],
        'size'     => [
            'type'        => Type::nonNull(Type::listOf(Type::int())),
            'description' => 'File size in bytes',
        ],
        'tmp_name' => [
            'type'        => Type::nonNull(Type::listOf(Type::string())),
            'description' => 'Temporary file location',
        ],
        'type'     => [
            'type'        => Type::nonNull(Type::listOf(Type::string())),
            'description' => 'MIME-type of uploaded file',
        ],
    ],
];

return $schema;
