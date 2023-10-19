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

if ($mode === 'view') {
    /** @var \Tygh\Storefront\Storefront $storefront */
    $storefront = Tygh::$app['storefront'];
    $page = isset($_REQUEST['page'])
        ? (int) $_REQUEST['page']
        : null;

    $sitemap_file_path = fn_google_sitemap_get_sitemap_path($storefront->storefront_id, $page);

    if (file_exists($sitemap_file_path)) {
        header('Content-Type: text/xml;charset=utf-8');
        readfile($sitemap_file_path);
        exit();
    }

    return [CONTROLLER_STATUS_NO_PAGE];
}
