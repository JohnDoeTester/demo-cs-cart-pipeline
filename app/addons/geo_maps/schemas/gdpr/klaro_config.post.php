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

use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

/** @var array $schema */
if (Registry::get('addons.geo_maps.provider') === 'google') {
    if (!isset($schema['services']['google_maps'])) {
        $schema['services']['google_maps'] = [
            'purposes' => ['functional'],
            'name' => 'google_maps',
            'translations' => [
                'zz' => [
                    'title' => 'geo_maps.google_maps_cookie_title',
                    'description' => 'geo_maps.google_maps_cookie_description'
                ],
            ],
        ];
    }
} else {
    $schema['services']['yandex_maps'] = [
        'purposes' => ['functional'],
        'name' => 'yandex_maps',
        'translations' => [
            'zz' => [
                'title' => 'geo_maps.yandex_maps_cookie_title',
                'description' => 'geo_maps.yandex_maps_cookie_description'
            ],
        ],
    ];
}

return $schema;
