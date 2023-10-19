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

/** @var array $schema */

$schema['vendor_locations'] = function () {
    $demo_api_key = 'AIzaSyBuezRJcs7AQaaQ4WVONn2IqSml6UOOFGA';
    $api_key = Registry::get('addons.vendor_locations.api_key');

    return $api_key && $api_key !== $demo_api_key;
};

return $schema;