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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($mode === 'update' || $mode === 'add') {
        if ($mode === 'update') {
            $page_type = Tygh::$app['view']->getTemplateVars('page_type');
        } else {
            $page_type = $_REQUEST['page_type'];
        }

        if ($page_type === PAGE_TYPE_TILDA_PAGE) {
            Tygh::$app['view']->assign('tilda_page_list', fn_tilda_pages_get_page_list_from_tilda());
        }
    }
}
