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

/**
 * @var string $mode
 */

if ($mode === 'view' && !empty($_REQUEST['page_id'])) {
    $page = fn_get_page_data($_REQUEST['page_id'], CART_LANGUAGE);

    if (!empty($page['dispatch'])) {
        return [CONTROLLER_STATUS_REDIRECT, $page['dispatch']];
    }

    if ($page['page_type'] === PAGE_TYPE_TILDA_PAGE && !empty($page['tilda_page_id'])) {
        /** @var \Tygh\SmartyEngine\Core $view */
        $view = Tygh::$app['view'];

        $view->assign([
            'tilda_page_upload_settings' => fn_tilda_pages_get_upload_settings($page['tilda_page_id'])
        ]);
    }
}
