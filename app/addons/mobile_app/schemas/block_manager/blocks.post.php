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
$schema['categories']['settings']['category_appearance'] = [
    'type' => 'selectbox',
    'values' => [
        'with_background' => 'mobile_app.category_image_with_background',
        'without_background' => 'mobile_app.category_image_without_background',
        'without_image' => 'mobile_app.category_without_image',
    ],
    'default_value' => 'without_image',
    'tooltip' => __('mobile_app.category_appearance_tooltip')
];

return $schema;
