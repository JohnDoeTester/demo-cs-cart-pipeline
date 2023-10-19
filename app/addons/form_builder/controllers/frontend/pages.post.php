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

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'send_form') {
        $suffix = '';

        if (fn_send_form($_REQUEST['page_id'], empty($_REQUEST['form_values']) ? array() : $_REQUEST['form_values'])) {
            $suffix = '&sent=Y';
        }

        return array(CONTROLLER_STATUS_OK, 'pages.view?page_id=' . $_REQUEST['page_id'] . $suffix);
    }

    return;
}

if ($mode == 'view' && !empty($_REQUEST['page_id'])) {
    $restored_form_values = fn_restore_post_data('form_values');
    if (!empty($restored_form_values)) {
        Tygh::$app['view']->assign('form_values', $restored_form_values);
    }

} elseif ($mode == 'sent' && !empty($_REQUEST['page_id'])) {
    $page = fn_get_page_data($_REQUEST['page_id'], CART_LANGUAGE);
    Tygh::$app['view']->assign('page', $page);
}
