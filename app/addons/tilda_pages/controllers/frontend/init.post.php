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

use Tygh\Enum\SiteArea;
use Tygh\Themes\Themes;

defined('BOOTSTRAP') or die('Access denied');

if (isset($_REQUEST['dispatch'])) {
    if (!isset($_REQUEST['page_id'])) {
        return [CONTROLLER_STATUS_OK];
    }

    $page_id = $_REQUEST['page_id'];

    if ($page_id) {
        /** @var \Tygh\SmartyEngine\Core $view */
        $view = Tygh::$app['view'];
        $page = fn_get_page_data($page_id);

        if (!isset($page['tilda_page_id'])) {
            return [CONTROLLER_STATUS_OK];
        }

        if (!empty($page['template'])) {
            $path_template = PATH_TO_STATIC_PAGE . $page['template'] . '.tpl';
            $file = Themes::areaFactory(SiteArea::STOREFRONT)->getContentPath('templates/' . $path_template);

            if ($file) {
                $view->assign('template', $file[Themes::PATH_ABSOLUTE]);
            } else {
                return [CONTROLLER_STATUS_NO_PAGE];
            }
        }

        $view->assign('page', $page);

        if (!empty($page['page_title'])) {
            $view->assign('page_title', $page['page_title']);
        }

        if (!empty($page['meta_description']) || !empty($page['meta_keywords'])) {
            $view->assign('meta_description', $page['meta_description']);
            $view->assign('meta_keywords', $page['meta_keywords']);
        }

        $view->assign('content_tpl', 'views/pages/view.tpl');
    }
}
