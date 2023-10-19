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
use Tygh\Tools\Url;

include_once(Registry::get('config.dir.schemas') . 'bottom_panel/vendor.functions.php');

$schema['pages.view']['to_vendor'] = function (Url $url) {
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
        return fn_bottom_panel_mve_get_page_url_params($url);
    }
};

return $schema;