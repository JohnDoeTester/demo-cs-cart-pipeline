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

use Tygh\Enum\Addons\VendorCommunication\CommunicationTypes;
use Tygh\Enum\UserTypes;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

$auth = & Tygh::$app['session']['auth'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    return [CONTROLLER_STATUS_OK];
}

/** @var string $mode */
if (
    $mode === 'details'
    && !empty($_REQUEST['order_id'])
) {
    $params = [
        'object_id'   => $_REQUEST['order_id'],
        'object_type' => VC_OBJECT_TYPE_ORDER,
    ];

    $is_user_can_view_order_thread = fn_check_user_access($auth['user_id'], 'view_order_communication');
    $navigation_tabs = Registry::get('navigation.tabs');

    foreach (CommunicationTypes::all() as $communication_type) {
        if (
            !$is_user_can_view_order_thread
            && $communication_type === CommunicationTypes::VENDOR_TO_CUSTOMER
        ) {
            continue;
        }

        $params['communication_type'] = $communication_type;

        list($threads, ) = fn_vendor_communication_get_threads($params);

        $thread = end($threads);

        if ($thread) {
            $thread['messages'] = fn_vendor_communication_get_thread_messages($thread);

            $navigation_tabs['vendor_communication_' . $communication_type] = [
                'title' => __('vendor_communication.communication_' . $communication_type),
                'js'    => true,
            ];

            if (
                $communication_type === CommunicationTypes::VENDOR_TO_ADMIN
                && UserTypes::isVendor($auth['user_type'])
            ) {
                $navigation_tabs['vendor_communication_' . $communication_type]['title'] = __('vendor_communication.communication_vendor_to_admin_with_admin');
            }

            Tygh::$app['view']->assign('order_' . $communication_type . '_thread', $thread);
        }
    }

    Registry::set('navigation.tabs', $navigation_tabs);

    Tygh::$app['view']->assign([
        'is_user_can_view_customer_order_thread'   => $is_user_can_view_order_thread,
        'is_user_can_manage_customer_order_thread' => fn_check_user_access($auth['user_id'], 'manage_order_communication'),
        'is_user_can_view_vendor_order_thread'     => fn_check_user_access($auth['user_id'], 'view_admin_communication'),
        'is_user_can_manage_vendor_order_thread'   => fn_check_user_access($auth['user_id'], 'manage_admin_communication'),
    ]);
}
