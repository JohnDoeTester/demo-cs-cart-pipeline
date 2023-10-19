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

use Tygh\Enum\UserTypes;

defined('BOOTSTRAP') or die('Access denied');

/** @var array $schema */
$schema['central']['products']['items']['product_reviews.menu_title'] = [
    'attrs' => [
        'class' => 'is-addon'
    ],
    'href'     => 'product_reviews.manage',
    'position' => 601,
];

if (fn_check_current_user_access('view_product_reviews')) {
    $schema['top']['administration']['items']['export_data']['subitems']['product_reviews.product_reviews'] = [
        'attrs'    => [
            'class' => 'is-addon'
        ],
        'href'     => 'exim.export?section=product_reviews',
        'position' => 1000,
    ];
}

if (
    UserTypes::isAdmin(Tygh::$app['session']['auth']['user_type'])
    && fn_check_current_user_access('create_product_reviews')
) {
    $schema['top']['administration']['items']['import_data']['subitems']['product_reviews.product_reviews'] = [
        'attrs'    => [
            'class' => 'is-addon'
        ],
        'href'     => 'exim.import?section=product_reviews',
        'position' => 1000,
    ];
}

return $schema;
