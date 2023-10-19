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

$schema['seo'] = [
    'position'    => 1800,
    'title'       => 'seo',
    'is_optional' => true,
    'sections'    => [
        'main' => [
            'is_optional' => true,
            'title'       => 'main',
            'position'    => 100,
            'fields'      => [
                'seo_name_field' => ['is_optional' => false, 'title' => 'seo_name', 'position' => 100],
            ],
        ]
    ],
];

return $schema;
