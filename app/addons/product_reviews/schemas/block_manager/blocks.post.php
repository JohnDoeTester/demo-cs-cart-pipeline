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
$schema['products']['cache']['update_handlers'][] = 'product_review_prepared_data';

$schema['main']['cache_overrides_by_dispatch']['products.view']['update_handlers'][] = 'product_reviews';
$schema['main']['cache_overrides_by_dispatch']['products.view']['update_handlers'][] = 'product_review_votes';
$schema['main']['cache_overrides_by_dispatch']['products.view']['update_handlers'][] = 'product_review_prepared_data';

$schema['products']['content']['items']['fillings']['rating'] = [
    'params' => [
        'rating'          => true,
        'sort_by'         => 'rating',
        'show_only_rated' => true
    ],
];

return $schema;
