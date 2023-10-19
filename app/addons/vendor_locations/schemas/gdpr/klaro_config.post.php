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
if (!isset($schema['services']['google_maps'])) {
    $schema['services']['google_maps'] = [
        'purposes' => ['functional'],
        'name' => 'google_maps',
        'translations' => [
            'zz' => [
                'title' => 'vendor_locations.google_maps_cookie_title',
                'description' => 'vendor_locations.google_maps_cookie_description'
            ],
        ],
    ];
}

return $schema;
