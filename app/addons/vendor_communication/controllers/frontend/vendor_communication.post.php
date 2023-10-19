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
use Tygh\Enum\NotificationSeverity;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($mode == 'create_thread') {

        if (!fn_vendor_communication_is_required_fields_filled($_REQUEST) || !fn_vendor_communication_is_company_exists($_REQUEST['thread']['company_id'])) {
            $redirect_url = !empty($_REQUEST['redirect_url']) ? $_REQUEST['redirect_url'] : 'vendor_communication.threads';
            return [CONTROLLER_STATUS_REDIRECT, $redirect_url];
        }

        fn_trusted_vars('thread');

        $default_thread_data = [
            'subject'     => '',
            'object_id'   => 0,
            'object_type' => '',
            'storefront_id' => Tygh::$app['storefront']->storefront_id,
        ];

        $thread_data = [
            'user_id'   => $auth['user_id'],
            'user_type' => $auth['user_type'],
        ];

        $thread_data = array_merge($default_thread_data, $_REQUEST['thread'], $thread_data);

        $result = fn_vendor_communication_create_thread($thread_data);

        if (!$result->isSuccess()) {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), __('vendor_communication.cannot_create_thread'));
        }

        $thread_id = $result->getData();

        $success_message = __(
            'vendor_communication.message_sent',
            [
                '[vendor_name]' => fn_get_company_name($thread_data['company_id']),
                '[thread_url]' => fn_url("vendor_communication.view&thread_id={$thread_id}"),
            ]
        );

        fn_set_notification(NotificationSeverity::NOTICE, __('notice'), $success_message, 'K');

        $redirect_url = !empty($_REQUEST['redirect_url']) ? $_REQUEST['redirect_url'] : 'vendor_communication.threads';
        return [CONTROLLER_STATUS_REDIRECT, $redirect_url];
    }
}

/** @var \Tygh\SmartyEngine\Core $view */
$view = Tygh::$app['view'];

if ($mode == 'threads') {
    if (!fn_vendor_communication_is_communication_type_active(CommunicationTypes::VENDOR_TO_CUSTOMER)) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    fn_add_breadcrumb(__('vendor_communication.messages'), 'vendor_communication.threads');
    $threads = $search = [];

    $params = $_REQUEST;
    $params['user_id'] = $auth['user_id'];
    $params['communication_type'] = CommunicationTypes::VENDOR_TO_CUSTOMER;
    $params['get_object_data'] = true;

    list($threads, $search) = fn_vendor_communication_get_threads(
        $params,
        Registry::get('settings.Appearance.elements_per_page')
    );

    $threads = fn_vendor_communication_get_threads_user_status($threads, $auth);

    $view->assign([
        'threads'    => $threads,
        'search'     => $search,
        'company_id' => Registry::get('runtime.company_id')
    ]);

    if (!empty($_REQUEST['active_thread'])) {
        $view->assign('active_thread', $_REQUEST['active_thread']);
    }
} elseif ($mode == 'view') {
    if (
        !isset($_REQUEST['thread_id'])
        || !fn_vendor_communication_can_user_access_thread($_REQUEST['thread_id'], $auth)
        || !fn_vendor_communication_is_communication_type_active(CommunicationTypes::VENDOR_TO_CUSTOMER)
    ) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    fn_add_breadcrumb(__('vendor_communication.messages'), 'vendor_communication.threads');
    fn_add_breadcrumb(__('vendor_communication.ticket'));

    $params = [
        'user_id'   => $auth['user_id'],
        'thread_id' => (int) $_REQUEST['thread_id'],
    ];

    $thread = fn_vendor_communication_get_thread($params);

    if (empty($thread)) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $thread_user_status = fn_vendor_communication_get_thread_user_status($thread, $auth);

    if ($thread_user_status == VC_THREAD_STATUS_HAS_NEW_MESSAGE) {
        fn_vendor_communication_mark_thread_as_viewed($thread);
    }

    $messages = fn_vendor_communication_get_thread_messages($params);

    $view->assign([
        'messages' => $messages,
        'thread_id' => $_REQUEST['thread_id'],
        'thread' => $thread,
     ]);
}
