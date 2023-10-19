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
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\YesNo;

defined('BOOTSTRAP') or die('Access denied');

require_once Registry::get('config.dir.addons') . 'blog/schemas/block_manager/blocks.functions.php';

/**
 * @var array<string, array> $schema
 */
$schema['blog'] = [
    'content' => [
        'items' => [
            'type'           => 'enum',
            'object'         => 'pages',
            'items_function' => 'fn_get_pages',
            'remove_indent'  => true,
            'hide_label'     => true,
            'fillings' => [
                'blog.recent_posts_scroller' => [
                    'params' => [
                        'simple'     => true,
                        'sort_by'    => 'timestamp',
                        'sort_order' => 'desc',
                        'status'     => ObjectStatuses::ACTIVE,
                        'page_type'  => PAGE_TYPE_BLOG,
                        'get_image'  => true
                    ],
                ],
                'blog.recent_posts' => [
                    'params' => [
                        'simple'     => true,
                        'sort_by'    => 'timestamp',
                        'sort_order' => 'desc',
                        'status'     => ObjectStatuses::ACTIVE,
                        'page_type'  => PAGE_TYPE_BLOG,
                    ]
                ],
                'blog.text_links' => [
                    'params' => [
                        'simple'     => true,
                        'sort_by'    => 'timestamp',
                        'sort_order' => 'desc',
                        'status'     => ObjectStatuses::ACTIVE,
                        'page_type'  => PAGE_TYPE_BLOG,
                    ],
                    'settings' => [
                        'parent_page_id' => [
                            'type'          => 'picker',
                            'default_value' => '0',
                            'picker'        => 'pickers/pages/picker.tpl',
                            'picker_params' => [
                                'multiple'     => false,
                                'use_keys'     => YesNo::NO,
                                'default_name' => __('root_level'),
                                'extra_url'    => '&page_type=' . PAGE_TYPE_BLOG
                            ],
                        ],
                        'limit' => [
                            'type'          => 'input',
                            'default_value' => 10
                        ],
                    ],
                ],
            ],
        ],
    ],
    'templates' => 'addons/blog/blocks',
    'wrappers'  => 'blocks/wrappers',
    'cache' => [
        'update_handlers'  => ['pages', 'page_descriptions'],
        'request_handlers' => ['%PAGE_ID%', '%COMPANY_ID%']
    ],
    'brief_info_function' => 'fn_block_get_blog_info'
];

if (!empty($schema['rss_feed'])) {
    $schema['rss_feed']['content']['filling']['values']['blog'] = 'blog.posts';
    $schema['rss_feed']['content']['filling']['values_settings']['blog'] = [
        'settings' => [
            'parent_page_id' => [
                'type'          => 'picker',
                'default_value' => '0',
                'picker'        => 'pickers/pages/picker.tpl',
                'picker_params' => [
                    'multiple'     => false,
                    'extra_url'    => '&page_type=' . PAGE_TYPE_BLOG,
                    'default_name' => __('root_page'),
                ],
            ]
        ]
    ];
}

return $schema;
