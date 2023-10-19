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

$schema['pages.manage&page_type'] = [
    'from' => [
        'dispatch'  => 'pages.manage',
        'page_type' => PAGE_TYPE_BLOG
    ],
    'to_customer' => [
        'dispatch' => 'pages.view',
        'page_id' => function () {
            return !empty(fn_blog_get_first_blog_page_id()) ? fn_blog_get_first_blog_page_id() : false;
        }
    ]
];

return $schema;