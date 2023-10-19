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

return [
    'detailed' => [
        'is_optional' => false,
        'title'       => 'general',
        'position'    => 100,
        'sections'    => [
            'information'  => [
                'is_optional' => false,
                'title'       => 'information',
                'position'    => 100,
                'fields'      => [
                    'parent_id'     => ['is_optional' => false, 'title' => 'location', 'position' => 100],
                    'category'      => ['is_optional' => false, 'title' => 'name', 'position' => 200],
                    'storefront_id' => ['is_optional' => false, 'title' => 'storefront', 'position' => 300],
                    'description'   => ['is_optional' => false, 'title' => 'price', 'position' => 400],
                    'status'        => ['is_optional' => true, 'title' => 'status', 'position' => 500],
                    'images'        => ['is_optional' => false, 'title' => 'images', 'position' => 600],
                ],
            ],
            'seo'          => [
                'is_optional' => true,
                'title'       => 'seo_meta_data',
                'position'    => 200,
                'fields'      => [
                    'page_title'       => ['is_optional' => true, 'title' => 'page_title', 'position' => 100],
                    'meta_description' => ['is_optional' => true, 'title' => 'meta_description', 'position' => 200],
                    'meta_keywords'    => ['is_optional' => true, 'title' => 'meta_keywords', 'position' => 300],
                ],
            ],
            'availability' => [
                'is_optional' => true,
                'title'       => 'pricing_inventory',
                'position'    => 300,
                'fields'      => [
                    'usergroup_ids' => ['is_optional' => true, 'title' => 'usergroups', 'position' => 100],
                    'position'      => ['is_optional' => true, 'title' => 'position', 'position' => 200],
                    'timestamp'     => ['is_optional' => true, 'title' => 'creation_date', 'position' => 300],
                ],
            ],
        ],
    ],
    'addons'   => [
        'position'    => 200,
        'title'       => 'addons',
        'is_optional' => true,
    ],
    'views'    => [
        'position'    => 300,
        'title'       => 'appearance',
        'is_optional' => true,
    ],
];
