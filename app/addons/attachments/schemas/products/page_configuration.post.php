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

/** @var array $schema */

$schema['attachments'] = [
    'position'    => 2000,
    'title'       => 'attachments',
    'is_optional' => true,
    'sections'    => [
        'main' => [
            'is_optional' => false,
            'title'       => 'main',
            'position'    => 100,
            'fields'      => [
                'attachments' => ['is_optional' => false, 'title' => 'attachments', 'position' => 100],
            ],
        ]
    ],
];

return $schema;
