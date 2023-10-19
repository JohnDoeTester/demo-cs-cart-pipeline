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

use Tygh\Addons\TildaPages\ServiceProvider;
use Tygh\Enum\SiteArea;

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($mode === 'update') {
        $tilda_client = ServiceProvider::getTildaClient();

        $tilda_project_list = $tilda_client->getProjectsList();

        Tygh::$app['view']->assign('tilda_project_list', $tilda_project_list);
        Tygh::$app['view']->assign('auto_sync_link', fn_url('tilda_pages.import', SiteArea::STOREFRONT));
    }
}
