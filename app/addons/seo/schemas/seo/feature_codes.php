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

$schema = [
    'Brand' => [
        'name' => __('seo.brand'),
        'description' => __('seo.feature_code.brand.description'),
    ],
    'ISBN' => [
        'name' => __('seo.isbn'),
        'description' => __('seo.feature_code.isbn.description'),
    ],
    'GTIN' => [
        'name' => __('seo.gtin'),
        'description' => __('seo.feature_code.gtin.description'),
    ],
    'MPN' => [
        'name' => __('seo.mpn'),
        'description' => __('seo.feature_code.mpn.description'),
    ],
];

return $schema;