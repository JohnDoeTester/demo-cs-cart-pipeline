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

use Tygh\Tools\Url;

return [
    'categories.view' => [
        'from' => [
            'dispatch' => 'categories.view',
            'category_id'
        ],
        'to_admin' => [
            'dispatch' => 'products.manage',
            'cid' => '%category_id%',
            'subcats' => 'Y'
        ],
    ],
    'products.view' => [
        'from' => [
            'dispatch' => 'products.view',
            'product_id'
        ],
        'to_admin' => [
            'dispatch'   => 'products.update',
            'product_id' => '%product_id%'
        ]
    ],
    'checkout.cart' => [
        'from' => [
            'dispatch' => 'checkout.cart'
        ],
        'to_admin' => [
            'dispatch'   => 'cart.cart_list'
        ]
    ],
    'profiles.update' => [
        'from' => 'profiles.update',
        'to_admin' => [
            'dispatch' => 'profiles.update',
            'user_id'  => Tygh::$app['session']['auth']['user_id']
        ]
    ],
    'orders.search' => [
        'from' => [
            'dispatch' => 'orders.search'
        ],
        'to_admin' => [
            'dispatch'   => 'orders.manage'
        ]
    ],
    'orders.details' => [
        'from' => [
            'dispatch' => 'orders.details',
            'order_id'
        ],
        'to_admin' => [
            'dispatch' => 'orders.details',
            'order_id' => '%order_id%'
        ]
    ],
    'pages.view' => [
        'from' => [
            'dispatch' => 'pages.view',
            'page_id'
        ],
        'to_admin' => [
            'dispatch' => 'pages.update',
            'page_id' => '%page_id%'
        ]
    ],
    'product_features.view_all' => [
        'from' => [
            'dispatch' => 'product_features.view_all',
            'filter_id'
        ],
        'to_admin' => function (Url $url) {
            $filter_id = $url->getQueryParam('filter_id');

            list($filter) = fn_get_product_filters([
                'filter_id' => $filter_id,
                'short'     => true
            ]);

            if (!empty($filter[$filter_id]['feature_id'])) {
                return [
                    'dispatch' => 'product_features.update',
                    'feature_id' => $filter[$filter_id]['feature_id'],
                    'selected_section' => sprintf('tab_feature_variants_%s', $filter[$filter_id]['feature_id'])
                ];
            } else {
                return false;
            }
        }
    ],
    'sitemap.view' => [
        'from' => [
            'dispatch' => 'sitemap.view'
        ],
        'to_admin' => [
            'dispatch'   => 'sitemap.manage'
        ]
    ],
    'product_features.compare' => [
        'from' => [
            'dispatch' => 'product_features.compare'
        ],
        'to_admin' => [
            'dispatch'   => 'product_features.manage'
        ]
    ],
    'promotions.list' => [
        'from' => [
            'dispatch' => 'promotions.list'
        ],
        'to_admin' => [
            'dispatch'   => 'promotions.manage'
        ]
    ],
    'products.search' => [
        'from' => [
            'dispatch' => 'products.search'
        ],
        'to_admin' => [
            'dispatch' => 'products.manage'
        ],
    ],
];