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
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    return [CONTROLLER_STATUS_OK];
}

if (
    $mode === 'details'
    && !empty($_REQUEST['order_id'])
) {
    $params = [
        'object_id'          => $_REQUEST['order_id'],
        'object_type'        => VC_OBJECT_TYPE_ORDER,
        'communication_type' => CommunicationTypes::VENDOR_TO_CUSTOMER,
    ];

    list($threads, ) = fn_vendor_communication_get_threads($params);

    $thread = end($threads);

    if ($thread) {
        $thread['messages'] = fn_vendor_communication_get_thread_messages($thread);

        $navigation_tabs = Registry::get('navigation.tabs');
        $navigation_tabs['vendor_communication'] = [
            'title' => __('vendor_communication.communication'),
            'js'    => true,
        ];

        Registry::set('navigation.tabs', $navigation_tabs);

        Tygh::$app['view']->assign('vendor_communication_order_thread', $thread);

        return [CONTROLLER_STATUS_OK];
    }

    /** @var array<int|string> $order_info */
    $order_info = Tygh::$app['view']->getTemplateVars('order_info');

    if (isset($order_info['company_id'])) {
        Tygh::$app['view']->assign('vendor_name', fn_get_company_name((int) $order_info['company_id']));
    }
}
