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

    fn_trusted_vars('item_data');

    if ($mode == 'update') {
        fn_buy_together_update_chain($_REQUEST['item_id'], $_REQUEST['product_id'], $_REQUEST['item_data'], $auth, DESCR_SL);

        return array(CONTROLLER_STATUS_OK, 'products.update?selected_section=buy_together&product_id=' . $_REQUEST['product_id']);
    }

    if ($mode == 'delete') {
        if (!empty($_REQUEST['chain_id'])) {
            $product_id = fn_buy_together_delete_chain($_REQUEST['chain_id']);

            return array(CONTROLLER_STATUS_REDIRECT, 'products.update?selected_section=buy_together&product_id=' . $product_id);
        }
    }

    if (
        $mode === 'm_delete'
        && !empty($_REQUEST['chain_ids'])
    ) {
        foreach ((array) $_REQUEST['chain_ids'] as $chain_id) {
            fn_buy_together_delete_chain($chain_id);
        }

        if (
            defined('AJAX_REQUEST')
            && isset($_REQUEST['redirect_url'])
        ) {
            Tygh::$app['ajax']->assign('force_redirection', $_REQUEST['redirect_url']);
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            return [CONTROLLER_STATUS_NO_CONTENT];
        }
    }

    if (
        $mode === 'm_update_statuses'
        && !empty($_REQUEST['chain_ids'])
        && !empty($_REQUEST['status'])
    ) {
        $status_to = $_REQUEST['status'];

        foreach ((array) $_REQUEST['chain_ids'] as $chain_id) {
            fn_tools_update_status(
                [
                    'table'             => 'buy_together',
                    'status'            => $status_to,
                    'id_name'           => 'chain_id',
                    'id'                => $chain_id,
                    'show_error_notice' => false,
                ]
            );
        }

        if (
            defined('AJAX_REQUEST')
            && isset($_REQUEST['redirect_url'])
        ) {
            Tygh::$app['ajax']->assign('force_redirection', $_REQUEST['redirect_url']);
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            return [CONTROLLER_STATUS_NO_CONTENT];
        }
    }

    return;
}

if ($mode == 'update') {

    $params = array(
        'chain_id' => $_REQUEST['chain_id'],
        'simple' => true,
        'full_info' => true,
    );

    $chain = fn_buy_together_get_chains($params, array(), DESCR_SL);

    Tygh::$app['view']->assign('item', $chain);
}
