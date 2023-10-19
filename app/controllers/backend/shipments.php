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

use Tygh\Enum\NotificationSeverity;
use Tygh\Registry;
use Tygh\Shippings\Shippings;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $suffix = '.manage';

    if ($mode === 'add' && !empty($_REQUEST['shipment_data'])) {
        $force_notification = fn_get_notification_rules($_REQUEST);
        fn_update_shipment($_REQUEST['shipment_data'], 0, 0, false, $force_notification);

        if (empty($_REQUEST['shipment_data']['tracking_number']) && empty($_REQUEST['shipment_data']['carrier'])) {
            fn_set_notification(NotificationSeverity::ERROR, __('notice'), __('error_shipment_not_created'));
        }

        $suffix = '.details?order_id=' . $_REQUEST['shipment_data']['order_id'];
    }

    if ($mode === 'update') {
        $shipment_data = $_REQUEST['shipment_data'];
        if (!empty($shipment_data['date'])) {
            $shipment_data['timestamp'] = fn_parse_datetime($shipment_data['date']['date'] . ' ' . $shipment_data['date']['time']);
        }

        fn_update_shipment($shipment_data, $_REQUEST['shipment_id']);

        return [CONTROLLER_STATUS_OK, 'shipments.details?shipment_id=' . $_REQUEST['shipment_id']];
    }

    if ($mode === 'packing_slip' && !empty($_REQUEST['shipment_ids'])) {
        echo(fn_print_shipment_packing_slips($_REQUEST['shipment_ids'], $_REQUEST));

        return [CONTROLLER_STATUS_NO_CONTENT];
    }

    if ($mode == 'm_delete' && !empty($_REQUEST['shipment_ids'])) {
        fn_delete_shipments($_REQUEST['shipment_ids']);

        if (!empty($_REQUEST['redirect_url'])) {
            return array(CONTROLLER_STATUS_REDIRECT, $_REQUEST['redirect_url']);
        }
    }

    if (
        $mode === 'm_update_statuses'
        && !empty($_REQUEST['shipment_ids'])
        && is_array($_REQUEST['shipment_ids'])
        && !empty($_REQUEST['status'])
    ) {
        $status_to = (string) $_REQUEST['status'];

        foreach ($_REQUEST['shipment_ids'] as $shipment_id) {
            fn_tools_update_status([
                'table'             => 'shipments',
                'status'            => $status_to,
                'id_name'           => 'shipment_id',
                'id'                => $shipment_id,
                'show_error_notice' => false
            ]);
        }

        if (defined('AJAX_REQUEST')) {
            $redirect_url = fn_url('shipments.manage');
            if (isset($_REQUEST['redirect_url'])) {
                $redirect_url = $_REQUEST['redirect_url'];
            }
            Tygh::$app['ajax']->assign('force_redirection', $redirect_url);
            Tygh::$app['ajax']->assign('non_ajax_notifications', true);
            return [CONTROLLER_STATUS_NO_CONTENT];
        }
    }

    if ($mode == 'delete' && !empty($_REQUEST['shipment_ids']) && is_array($_REQUEST['shipment_ids'])) {
        $shipment_ids = implode(',', $_REQUEST['shipment_ids']);

        fn_delete_shipments($shipment_ids);

        return array(CONTROLLER_STATUS_OK, 'shipments.manage');
    }

    return array(CONTROLLER_STATUS_OK, 'orders' . $suffix);
}

$params = $_REQUEST;

if ($mode == 'details') {
    if (empty($params['order_id']) && empty($params['shipment_id'])) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    if (!empty($params['shipment_id'])) {
        $params['order_id'] = db_get_field('SELECT ?:shipment_items.order_id FROM ?:shipment_items WHERE ?:shipment_items.shipment_id = ?i', $params['shipment_id']);
    }

    $shippings = db_get_array("SELECT a.shipping_id, a.min_weight, a.max_weight, a.position, a.status, b.shipping, b.delivery_time, a.usergroup_ids FROM ?:shippings as a LEFT JOIN ?:shipping_descriptions as b ON a.shipping_id = b.shipping_id AND b.lang_code = ?s WHERE a.status = ?s ORDER BY a.position", DESCR_SL, 'A');

    $order_info = fn_get_order_info($params['order_id'], false, true, true);
    if (empty($order_info)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }
    if (!empty($params['shipment_id'])) {
        $params['advanced_info'] = true;

        list($shipment, $search) = fn_get_shipments_info($params);

        if (!empty($shipment)) {
            $shipment = array_pop($shipment);

            foreach ($order_info['products'] as $item_id => $item) {
                if (isset($shipment['products'][$item_id])) {
                    $order_info['products'][$item_id]['amount'] = $shipment['products'][$item_id];
                } else {
                    $order_info['products'][$item_id]['amount'] = 0;
                }
            }
        } else {
            $shipment = array();
        }

        Tygh::$app['view']->assign('shipment', $shipment);
    }

    Tygh::$app['view']->assign('shippings', $shippings);
    Tygh::$app['view']->assign('order_info', $order_info);
    Tygh::$app['view']->assign('carriers', Shippings::getCarriers());
    Tygh::$app['view']->assign('shipment_statuses', fn_get_simple_statuses(STATUSES_SHIPMENT));

} elseif ($mode == 'manage') {
    list($shipments, $search) = fn_get_shipments_info($params, Registry::get('settings.Appearance.admin_elements_per_page'));

    Tygh::$app['view']->assign('shipments', $shipments);
    Tygh::$app['view']->assign('search', $search);
    Tygh::$app['view']->assign('shipment_statuses', fn_get_simple_statuses(STATUSES_SHIPMENT));

} elseif ($mode == 'packing_slip' && !empty($_REQUEST['shipment_ids'])) {
    echo(fn_print_shipment_packing_slips($_REQUEST['shipment_ids'], $_REQUEST));

    return [CONTROLLER_STATUS_NO_CONTENT];
}
