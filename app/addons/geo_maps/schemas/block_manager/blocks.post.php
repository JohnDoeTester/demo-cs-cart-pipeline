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

$schema['geo_maps_customer_location'] = [
    'templates' => 'addons/geo_maps/blocks/customer_location.tpl',
    'wrappers' => 'blocks/wrappers',
    'content' => [
        'location' => [
            'type' => 'function',
            'function' => ['fn_geo_maps_get_customer_stored_geolocation'],
        ],
        'location_detected' => [
            'type' => 'function',
            'function' => ['fn_geo_maps_is_customer_location_detected'],
        ],
    ],
];

return $schema;