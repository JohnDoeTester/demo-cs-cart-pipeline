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

$schema['pages.view'] = [
    'from' => [
        'dispatch' => 'pages.view',
        'page_id'
    ],
    'to_admin' => function (Url $url) {
        $page_id = $url->getQueryParam('page_id');

        if (empty($page_id)) {
            return false;
        }

        if ($page_id == fn_blog_get_first_blog_page_id()) {
            return [
                'dispatch' => 'pages.manage',
                'page_type' => PAGE_TYPE_BLOG
            ];
        } else {
            return [
                'dispatch' => 'pages.update',
                'page_id' => '%page_id%'
            ];
        }
    }
];

return $schema;