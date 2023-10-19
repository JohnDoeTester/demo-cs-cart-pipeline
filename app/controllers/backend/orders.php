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
use Tygh\Notifications\EventIdProviders\OrderProvider;
use Tygh\Registry;
use Tygh\Shippings\Shippings;
use Tygh\Storage;
use Tygh\Tools\SecurityHelper;
use Tygh\Tools\Url;
use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

/** @var string $mode */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $suffix = '';

    if ($mode == 'm_delete' && !empty($_REQUEST['order_ids'])) {
        foreach ($_REQUEST['order_ids'] as $v) {
            fn_delete_order($v);
        }
    }

    if ($mode === 'm_update' && !empty($_REQUEST['order_ids'])) {
        foreach ($_REQUEST['order_ids'] as $order_id) {
            fn_change_order_status($order_id, $_REQUEST['status'], '', fn_get_notification_rules($_REQUEST));
        }
    }

    if ($mode == 'update_details') {
        fn_trusted_vars('update_order');

        fn_update_order_details($_REQUEST);

        $suffix = ".details?order_id=$_REQUEST[order_id]";
    }

    if ($mode === 'bulk_print' && !empty($_REQUEST['order_ids'])) {
        echo(fn_print_order_invoices($_REQUEST['order_ids']));

        return [CONTROLLER_STATUS_NO_CONTENT];
    }

    if ($mode === 'packing_slip' && !empty($_REQUEST['order_ids'])) {
        echo(fn_print_order_packing_slips($_REQUEST['order_ids']));

        return [CONTROLLER_STATUS_NO_CONTENT];
    }

    if ($mode == 'remove_cc_info' && !empty($_REQUEST['order_ids'])) {

        fn_set_progress('parts', sizeof($_REQUEST['order_ids']));

        foreach ($_REQUEST['order_ids'] as $v) {
            $payment_info = db_get_field("SELECT data FROM ?:order_data WHERE order_id = ?i AND type = 'P'", $v);
            fn_cleanup_payment_info($v, $payment_info);
        }

        fn_set_notification('N', __('notice'), __('done'));

        if (count($_REQUEST['order_ids']) == 1) {
            $o_id = array_pop($_REQUEST['order_ids']);
            $suffix = ".details?order_id=$o_id";
        } else {
            exit;
        }
    }

    if ($mode == 'export_range') {
        if (!empty($_REQUEST['order_ids'])) {
            if (empty(Tygh::$app['session']['export_ranges'])) {
                Tygh::$app['session']['export_ranges'] = array();
            }

            if (empty(Tygh::$app['session']['export_ranges']['orders'])) {
                Tygh::$app['session']['export_ranges']['orders'] = array('pattern_id' => 'orders');
            }

            Tygh::$app['session']['export_ranges']['orders']['data'] = array('order_id' => $_REQUEST['order_ids']);

            unset($_REQUEST['redirect_url']);

            return array(CONTROLLER_STATUS_REDIRECT, 'exim.export?section=orders&pattern_id=' . Tygh::$app['session']['export_ranges']['orders']['pattern_id']);
        }
    }

    if ($mode == 'products_range') {
        if (!empty($_REQUEST['order_ids'])) {
            unset($_REQUEST['redirect_url']);

            return array(CONTROLLER_STATUS_REDIRECT, 'products.manage?order_ids=' . implode(',', $_REQUEST['order_ids']));
        }
    }


    if ($mode == 'delete') {
        fn_delete_order($_REQUEST['order_id']);

        return array(CONTROLLER_STATUS_REDIRECT);
    }

    if ($mode == 'update_status') {

        $order_info = fn_get_order_short_info($_REQUEST['id']);
        $old_status = $order_info['status'];
        if (fn_change_order_status($_REQUEST['id'], $_REQUEST['status'], '', fn_get_notification_rules($_REQUEST))) {
            $order_info = fn_get_order_short_info($_REQUEST['id']);
            fn_check_first_order($order_info);
            $new_status = $order_info['status'];
            if ($_REQUEST['status'] != $new_status) {
                Tygh::$app['ajax']->assign('return_status', $new_status);
                Tygh::$app['ajax']->assign('color', fn_get_status_param_value($new_status, 'color'));

                fn_set_notification('W', __('warning'), __('status_changed'));
            } else {
                fn_set_notification('N', __('notice'), __('status_changed'));
            }
        } else {
            fn_set_notification('E', __('error'), __('error_status_not_changed'));
            Tygh::$app['ajax']->assign('return_status', $old_status);
            Tygh::$app['ajax']->assign('color', fn_get_status_param_value($old_status, 'color'));
        }

        if (empty($_REQUEST['return_url'])) {
            exit;
        } else {
            return array(CONTROLLER_STATUS_REDIRECT, $_REQUEST['return_url']);
        }
    }

    if ($mode === 'modify_invoice') {
        fn_trusted_vars('invoice');

        $order_id = (int) $_REQUEST['order_id'];
        if (Registry::get('settings.Appearance.email_templates') === 'old' || empty($order_id)) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        $order_info = fn_get_order_info($order_id, false, true, true, false);
        if (empty($order_info)) {
            return [CONTROLLER_STATUS_NO_PAGE];
        }

        $result = fn_send_order_invoice($order_info, $_REQUEST['invoice']);
        if ($result) {
            fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('text_email_sent'));
        }

        return [CONTROLLER_STATUS_REDIRECT, 'orders.details?order_id=' . $order_id];
    }

    if ($mode == 'assign_manager') {
        $order_id = isset($_REQUEST['order_id']) ? (int) $_REQUEST['order_id'] : null;

        if ($order_id === null) {
            return array(CONTROLLER_STATUS_REDIRECT, 'orders.manage');
        }

        $user_id = (int) $auth['user_id'];

        $order_info = fn_get_order_short_info($order_id);
        if (isset($order_info['issuer_id']) && ($order_info['issuer_id'] === $user_id)) {
            return array(CONTROLLER_STATUS_REDIRECT, 'orders.details?order_id=' . $order_id);
        }

        // Log order update
        fn_log_event('orders', 'update', array(
            'order_id' => $order_id,
        ));

        db_query('UPDATE ?:orders SET issuer_id = ?i, updated_at = ?i WHERE order_id = ?i', $user_id, TIME, $order_id);

        return [CONTROLLER_STATUS_REDIRECT, 'orders.details?order_id=' . $order_id];
    }

    if ($mode === 'manage') {
        $params = [];
        if (!empty($_REQUEST['user_ids'])) {
            $params['user_id'] = (array) $_REQUEST['user_ids'];
        }

        if (!empty($_REQUEST['company_ids'])) {
            $params['company_ids'] = (array) $_REQUEST['company_ids'];
        }

        if (!empty($params)) {
            unset($_REQUEST['redirect_url'], $_REQUEST['page']);

            return [CONTROLLER_STATUS_REDIRECT, Url::buildUrn(['orders', 'manage'], $params)];
        }
    }

    return [CONTROLLER_STATUS_OK, 'orders' . $suffix];
}

$params = $_REQUEST;

if ($mode === 'print_invoice') {
    if (!empty($_REQUEST['order_id'])) {
        echo(fn_print_order_invoices($_REQUEST['order_id'], $_REQUEST));
    }

    return[CONTROLLER_STATUS_NO_CONTENT];
} elseif ($mode === 'print_packing_slip') {
    if (!empty($_REQUEST['order_id'])) {
        echo(fn_print_order_packing_slips($_REQUEST['order_id'], $_REQUEST));
    }

    return [CONTROLLER_STATUS_NO_CONTENT];
} elseif ($mode == 'details') {
    $_REQUEST['order_id'] = empty($_REQUEST['order_id']) ? 0 : $_REQUEST['order_id'];
    $selected_section = (empty($_REQUEST['selected_section']) ? 'general' : $_REQUEST['selected_section']);

    $order_info = fn_get_order_info($_REQUEST['order_id'], false, true, true, false);
    fn_check_first_order($order_info);

    if (
        empty($order_info)
        || !fn_check_permission_storefronts($order_info['storefront_id'])
    ) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    if (!empty($order_info['is_parent_order']) && $order_info['is_parent_order'] == 'Y') {
        // Get children orders
        $children_order_ids = db_get_fields('SELECT order_id FROM ?:orders WHERE parent_order_id = ?i', $order_info['order_id']);

        return array(CONTROLLER_STATUS_REDIRECT, 'orders.manage?order_id=' . implode(',', $children_order_ids));
    }

    if (isset($order_info['need_shipping']) && $order_info['need_shipping']) {
        $company_id = !empty($order_info['company_id']) ? $order_info['company_id'] : null;

        $shippings = fn_get_available_shippings($company_id);
        Tygh::$app['view']->assign('shippings', $shippings);
    }

    Registry::set('navigation.tabs', array (
        'general' => array (
            'title' => __('general'),
            'js' => true
        ),
        'addons' => array (
            'title' => __('addons'),
            'js' => true
        ),
    ));

    if (fn_allowed_for('MULTIVENDOR')) {
        Tygh::$app['view']->assign('take_surcharge_from_vendor', fn_take_payment_surcharge_from_vendor($order_info['products']));
    }

    $downloads_exist = false;

    foreach ($order_info['products'] as $k => $v) {

        if (!$downloads_exist && !empty($v['extra']['is_edp']) && $v['extra']['is_edp'] == 'Y') {
            $downloads_exist = true;
        }

        $order_info['products'][$k]['main_pair'] = fn_get_cart_product_icon(
            $v['product_id'], $order_info['products'][$k]
        );
    }

    if ($downloads_exist) {
        Registry::set('navigation.tabs.downloads', array (
            'title' => __('downloads'),
            'js' => true
        ));
        Tygh::$app['view']->assign('downloads_exist', true);
    }

    if (!empty($order_info['promotions'])) {
        Registry::set('navigation.tabs.promotions', array (
            'title' => __('promotions'),
            'js' => true
        ));
    }

    list($shipments) = fn_get_shipments_info(array('order_id' => $params['order_id'], 'advanced_info' => true));
    $use_shipments = !fn_one_full_shipped($shipments);

    // Check for the shipment access
    // If current edition is FREE, we still need to check shipments accessibility (need to display promotion link)
    if (!fn_check_user_access($auth['user_id'], 'edit_order')) {
        $order_info['need_shipment'] = false;
    }

    foreach ($shipments as $shipment_key => $shipment) {
        if (isset($order_info['shipping'][$shipment['group_key']])) {
            $order_info['shipping'][$shipment['group_key']]['shipment_keys'][] = $shipment_key;
        } else {
            $order_info['shipping'][0]['shipment_keys'][] = $shipment_key;
        }
    }

    Tygh::$app['view']->assign('shipments', $shipments);
    Tygh::$app['view']->assign('use_shipments', $use_shipments);
    Tygh::$app['view']->assign('carriers', Shippings::getCarriers());

    Tygh::$app['view']->assign('order_info', $order_info);
    Tygh::$app['view']->assign('status_settings', fn_get_status_params($order_info['status']));
    Tygh::$app['view']->assign('selected_section', $selected_section);

    // Check if customer's email is changed
    if (!empty($order_info['user_id'])) {
        $current_email = db_get_field("SELECT email FROM ?:users WHERE user_id = ?i", $order_info['user_id']);
        if (!empty($current_email) && $current_email != $order_info['email']) {
            Tygh::$app['view']->assign('email_changed', true);
        }
    }

} elseif ($mode == 'picker') {
    $_REQUEST['skip_view'] = 'Y';

    list($orders, $search) = fn_get_orders($_REQUEST, Registry::get('settings.Appearance.admin_elements_per_page'));
    Tygh::$app['view']->assign('orders', $orders);
    Tygh::$app['view']->assign('search', $search);

    Tygh::$app['view']->display('pickers/orders/picker_contents.tpl');
    exit;

} elseif ($mode == 'manage') {

    $params['include_incompleted'] = true;

    if (fn_allowed_for('MULTIVENDOR')) {
        $params['company_name'] = true;
    }

    if (isset($params['phone'])) {
        $params['phone'] = str_replace(' ', '', preg_replace('/[^0-9\s]/', '', $params['phone']));
    }
    $params['extra'][] = 'user_type';
    $params['extra'][] = 'issuers';
    list($orders, $search, $totals) = fn_get_orders($params, Registry::get('settings.Appearance.admin_elements_per_page'), true);

    if (!empty($_REQUEST['redirect_if_one']) && count($orders) == 1) {
        return array(CONTROLLER_STATUS_REDIRECT, 'orders.details?order_id=' . $orders[0]['order_id']);
    }

    $company_id = fn_get_runtime_company_id();
    $shippings = fn_get_available_shippings($company_id);
    $shippings = array_column($shippings, 'shipping', 'shipping_id');

    $remove_cc = db_get_field(
        "SELECT COUNT(*)"
        . " FROM ?:status_data"
        . " WHERE status_id IN (?n)"
            . " AND param = 'remove_cc_info'"
            . " AND value = 'N'",
        array_keys(fn_get_statuses_by_type(STATUSES_ORDER))
    );
    $remove_cc = $remove_cc > 0 ? true : false;
    Tygh::$app['view']->assign('remove_cc', $remove_cc);

    Tygh::$app['view']->assign('orders', $orders);
    Tygh::$app['view']->assign('search', $search);

    Tygh::$app['view']->assign('totals', $totals);
    Tygh::$app['view']->assign('display_totals', fn_display_order_totals($orders));
    Tygh::$app['view']->assign('shippings', $shippings);

    $payments = fn_get_payments(array('simple' => true));
    Tygh::$app['view']->assign('payments', $payments);

    if (fn_allowed_for('MULTIVENDOR')) {
        Tygh::$app['view']->assign('selected_storefront_id', empty($_REQUEST['storefront_id']) ? 0 : (int) $_REQUEST['storefront_id']);
    }
} elseif ($mode == 'get_custom_file') {
    if (!empty($_REQUEST['file']) && !empty($_REQUEST['order_id'])) {
        $order_id = (int) $_REQUEST['order_id'];
        $file_path = 'order_data/' . $order_id . '/' . fn_basename($_REQUEST['file']);

        if (Storage::instance('custom_files')->isExist($file_path)) {

            $filename = !empty($_REQUEST['filename']) ? $_REQUEST['filename'] : '';
            Storage::instance('custom_files')->get($file_path, $filename);
        }
    }

} elseif ($mode == 'modify_invoice') {
    /** @var \Tygh\Template\Document\Order\Type $document_type */
    $document_type = Tygh::$app['template.document.order.type'];
    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    $order_id = (int) $_REQUEST['order_id'];

    if (Registry::get('settings.Appearance.email_templates') === 'old' || empty($order_id)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    $order_info = fn_get_order_info($order_id, false, true, true, false);

    if (empty($order_info)) {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    $view->assign('order_info', $order_info);
    $view->assign('invoice', $document_type->renderById($order_id, 'invoice', CART_LANGUAGE));
    $view->assign('company_data', fn_get_company_placement_info($order_info['company_id']));

}

//
// Calculate gross total and totally paid values for the current set of orders
//
function fn_display_order_totals($orders)
{
    $result = array();
    $result['gross_total'] = 0;
    $result['totally_paid'] = 0;

    if (is_array($orders)) {
        foreach ($orders as $k => $v) {
            $result['gross_total'] += $v['total'];
            if ($v['status'] == 'C' || $v['status'] == 'P') {
                $result['totally_paid'] += $v['total'];
            }
        }
    }

    return $result;
}

/**
 * Updates order details in the administration panel.
 *
 * @param array $params Order details
 *
 * @internal
 */
function fn_update_order_details(array $params)
{
    // Update customer's email if its changed in customer's account
    if (!empty($params['update_customer_details']) && $params['update_customer_details'] == 'Y') {
        $u_id = db_get_field("SELECT user_id FROM ?:orders WHERE order_id = ?i", $params['order_id']);
        $current_email = db_get_field("SELECT email FROM ?:users WHERE user_id = ?i", $u_id);
        db_query("UPDATE ?:orders SET email = ?s WHERE order_id = ?i", $current_email, $params['order_id']);
    }

    // Log order update
    fn_log_event('orders', 'update', array(
        'order_id' => $params['order_id'],
    ));

    $params['update_order']['updated_at'] = isset($params['update_order']['updated_at']) ? fn_parse_date($params['update_order']['updated_at']) : TIME;
    if (!empty($params['update_order']['notes'])) {
        $params['update_order']['notes'] = SecurityHelper::sanitizeHtml($params['update_order']['notes']);
    }
    if (!empty($params['update_order']['details'])) {
        $params['update_order']['details'] = SecurityHelper::sanitizeHtml($params['update_order']['details']);
    }

    db_query('UPDATE ?:orders SET ?u WHERE order_id = ?i', $params['update_order'], $params['order_id']);

    $force_notification = fn_get_notification_rules($params);

    //Update shipping info
    if (!empty($params['update_shipping'])) {
        foreach ($params['update_shipping'] as $group_key => $shipment_group) {
            foreach($shipment_group as $shipment_id => $shipment) {
                $shipment['order_id'] = $params['order_id'];
                fn_update_shipment($shipment, $shipment_id, $group_key, true, $force_notification);
            }
        }
    }

    $edp_data = array();
    $order_info = fn_get_order_info($params['order_id']);
    if (!empty($params['activate_files'])) {
        $edp_data = fn_generate_ekeys_for_edp(array(), $order_info, $params['activate_files']);
    }

    /** @var \Tygh\Notifications\EventDispatcher $event_dispatcher */
    $event_dispatcher = Tygh::$app['event.dispatcher'];
    /** @var \Tygh\Notifications\Settings\Factory $notification_settings_factory */
    $notification_settings_factory = Tygh::$app['event.notification_settings.factory'];
    $notification_rules = $notification_settings_factory->create($force_notification);

    $event_dispatcher->dispatch(
        'order.updated',
        ['order_info' => $order_info],
        $notification_rules,
        new OrderProvider($order_info)
    );
    if ($edp_data) {
        $notification_rules = fn_get_edp_notification_rules($force_notification, $edp_data);
        $event_dispatcher->dispatch(
            'order.edp',
            [
                'order_info' => $order_info,
                'edp_data' => $edp_data
            ],
            $notification_rules,
            new OrderProvider($order_info, $edp_data)
        );
    }

    fn_order_notification($order_info, $edp_data, $force_notification);

    if (!empty($params['prolongate_data']) && is_array($params['prolongate_data'])) {
        foreach ($params['prolongate_data'] as $ekey => $v) {
            $newttl = fn_parse_date($v, true);
            db_query('UPDATE ?:product_file_ekeys SET ?u WHERE ekey = ?s', array('ttl' => $newttl), $ekey);
        }
    }

    // Update file downloads section
    if (!empty($params['edp_downloads'])) {
        foreach ($params['edp_downloads'] as $ekey => $v) {
            foreach ($v as $file_id => $downloads) {
                $max_downloads = db_get_field("SELECT max_downloads FROM ?:product_files WHERE file_id = ?i", $file_id);
                if (!empty($max_downloads)) {
                    db_query('UPDATE ?:product_file_ekeys SET ?u WHERE ekey = ?s', array('downloads' => $max_downloads - $downloads), $ekey);
                }
            }
        }
    }

    /**
     * Executes after order details were updated in the administration panel, allows to perform additional actions
     * like sending notifications.
     *
     * @param array $params             Order details
     * @param array $order_info         Order information
     * @param array $edp_data           Downloadable products data
     * @param array $force_notification Notification rules
     */
    fn_set_hook('update_order_details_post', $params, $order_info, $edp_data, $force_notification);
}
