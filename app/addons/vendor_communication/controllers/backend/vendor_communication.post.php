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
use Tygh\Enum\UserTypes;
use Tygh\Enum\ObjectStatuses;
use Tygh\Enum\YesNo;
use Tygh\Registry;
use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

$auth = & Tygh::$app['session']['auth'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode == 'delete_thread') {

        if (isset($_REQUEST['thread_id'])) {
            $result = fn_vendor_communication_mark_threads_as_deleted(['thread_id' => $_REQUEST['thread_id']]);

            if ($result) {
                fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('vendor_communication.thread_deleted'));
            } else {
                fn_set_notification(NotificationSeverity::ERROR, __('error'), __('vendor_communication.cannot_delete_thread'));
            }

            $communication_type = isset($_REQUEST['communication_type']) ? $_REQUEST['communication_type'] : CommunicationTypes::VENDOR_TO_CUSTOMER;

            return [CONTROLLER_STATUS_REDIRECT, 'vendor_communication.threads?communication_type=' . $communication_type];
        } else {
            return [CONTROLLER_STATUS_NO_PAGE];
        }
    }

    if ($mode == 'm_delete_thread') {

        if (isset($_REQUEST['thread_ids'])) {
            $result = fn_vendor_communication_mark_threads_as_deleted_by_ids($_REQUEST['thread_ids']);

            if ($result) {
                fn_set_notification(NotificationSeverity::NOTICE, __('notice'), __('vendor_communication.threads_deleted'));
            } else {
                fn_set_notification(NotificationSeverity::ERROR, __('error'), __('vendor_communication.cannot_delete_threads'));
            }

            $communication_type = isset($_REQUEST['communication_type']) ? $_REQUEST['communication_type'] : CommunicationTypes::VENDOR_TO_CUSTOMER;

            return [CONTROLLER_STATUS_REDIRECT, 'vendor_communication.threads?communication_type=' . $communication_type];
        } else {
            return [CONTROLLER_STATUS_NO_PAGE];
        }
    }

    if ($mode == 'create_thread') {
        fn_trusted_vars('thread.message');

        if (!fn_vendor_communication_is_required_fields_filled($_REQUEST)) {
            $redirect_url = isset($_REQUEST['redirect_url'])
                    ? $_REQUEST['redirect_url']
                    : 'vendor_communication.threads?communication_type=' . CommunicationTypes::VENDOR_TO_ADMIN;

            return [CONTROLLER_STATUS_REDIRECT, $redirect_url];
        }

        $default_thread_data = [
            'subject'     => '',
            'object_id'   => 0,
            'object_type' => '',
        ];

        if (
            $_REQUEST['thread']['communication_type'] === CommunicationTypes::VENDOR_TO_CUSTOMER
            && $_REQUEST['thread']['object_type'] === VC_OBJECT_TYPE_ORDER
            && $order_info = fn_get_order_info($_REQUEST['thread']['object_id'])
        ) {
            $thread_data = [
                'user_id'     => $order_info['user_id'],
                'user_type'   => UserTypes::CUSTOMER,
                'sender_id'   => $auth['user_id'],
                'sender_type' => $auth['user_type'],
            ];
        } else {
            $thread_data = [
                'user_id'   => $auth['user_id'],
                'user_type' => $auth['user_type'],
            ];
        }

        $thread_data = array_merge($default_thread_data, $_REQUEST['thread'], $thread_data);

        $company_ids = [];

        if (isset($_REQUEST['thread']['companies']['all'])) {
            list($company_list) = fn_get_companies(['status' => ObjectStatuses::ACTIVE], $auth);
            $company_ids = array_column($company_list, 'company_id');
        } elseif ($auth['user_type'] != UserTypes::VENDOR) {
            if (isset($_REQUEST['thread']['company_id'])) {
                $company_ids = [$_REQUEST['thread']['company_id']];
            } else {
                $company_ids = $_REQUEST['thread']['companies'];
            }
        } else {
            $company_ids = [$auth['company_id']];
        }

        $thread_list = [];

        foreach ($company_ids as $company_id) {
            $thread_data['company_id'] = $company_id;
            $result = fn_vendor_communication_create_thread($thread_data);
            $thread_list[] = $result->getData();
        }

        if (!$thread_list) {
            fn_set_notification(NotificationSeverity::ERROR, __('error'), __('vendor_communication.cannot_create_thread'));
        }

        $success_message = '';
        if (count($thread_list) > 1) {
            $success_message = __(
                'vendor_communication.vendor_to_admin.message_list_sent',
                ['[thread_url]' => fn_url('vendor_communication.threads?communication_type=' . CommunicationTypes::VENDOR_TO_ADMIN)]
            );
        } else {
            switch ($thread_data['communication_type']) {
                case CommunicationTypes::VENDOR_TO_ADMIN:
                    $company_id = reset($company_ids);
                    $thread_id = reset($thread_list);

                    $success_message = __(
                        'vendor_communication.vendor_to_admin.message_sent',
                        [
                            '[name]' => $auth['user_type'] === UserTypes::VENDOR ? __('administrator') : fn_get_company_name($company_id),
                            '[thread_url]' => fn_url(
                                'vendor_communication.view?thread_id=' . $thread_id . '&communication_type=' . CommunicationTypes::VENDOR_TO_ADMIN
                            ),
                        ]
                    );
                    break;
                case CommunicationTypes::VENDOR_TO_CUSTOMER:
                    $thread_id = reset($thread_list);

                    $success_message = __(
                        'vendor_communication.vendor_to_customer.message_sent',
                        [
                            '[name]' => fn_get_user_name($thread_data['user_id']),
                            '[thread_url]' => fn_url(
                                'vendor_communication.view?thread_id=' . $thread_id . '&communication_type=' . CommunicationTypes::VENDOR_TO_CUSTOMER
                            ),
                        ]
                    );
                    break;
            }
        }

        fn_set_notification(NotificationSeverity::NOTICE, __('notice'), $success_message, 'K');

        if (!isset($thread_data['object_id'])) {
            return [CONTROLLER_STATUS_OK, fn_url('vendor_communication.threads?communication_type=' . CommunicationTypes::VENDOR_TO_ADMIN)];
        }
    }

    if (
        $mode === 'm_post_message'
        && !empty($_REQUEST['message'])
        && !empty($_REQUEST['thread_ids'])
        && !empty($_REQUEST['communication_type'])
    ) {
        fn_trusted_vars('message');

        foreach ((array) $_REQUEST['thread_ids'] as $thread_id) {
            if (!$thread_id || !fn_vendor_communication_can_user_access_thread($thread_id, $auth)) {
                continue;
            }

            $message = [
                'user_id'   => $auth['user_id'],
                'user_type' => $auth['user_type'],
                'message'   => (string) $_REQUEST['message'],
                'thread_id' => $thread_id,
            ];

            fn_vendor_communication_add_thread_message($message, true);
        }
    }

    return;
}

/** @var \Tygh\SmartyEngine\Core $view */
$view = Tygh::$app['view'];

if ($mode == 'create_thread') {
    $object_id = isset($_REQUEST['object_id']) ? (int) $_REQUEST['object_id'] : 0;
    $object_type = isset($_REQUEST['object_type']) ? (string) $_REQUEST['object_type'] : '';
    $communication_type = isset($_REQUEST['communication_type']) ? (string) $_REQUEST['communication_type'] : CommunicationTypes::VENDOR_TO_ADMIN;

    $object = fn_vendor_communication_get_object($object_id, $object_type);

    $view->assign([
        'object'             => $object,
        'object_id'          => $object_id,
        'object_type'        => $object_type,
        'communication_type' => $communication_type,
        'return_url'         => empty($_REQUEST['return_url']) ? '' : $_REQUEST['return_url'],
    ]);

    if (defined('AJAX_REQUEST')) {
        Tygh::$app['view']->display('addons/vendor_communication/views/vendor_communication/create_thread.tpl');
        exit;
    }
} elseif ($mode == 'threads') {

    $communication_type = empty($_REQUEST['communication_type']) ? CommunicationTypes::VENDOR_TO_CUSTOMER : (string) $_REQUEST['communication_type'];

    if (!fn_vendor_communication_is_communication_type_active($communication_type)) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $threads = $search = [];

    $default_params = [
        'communication_type' => CommunicationTypes::VENDOR_TO_CUSTOMER,
    ];

    $params = array_merge($default_params, $_REQUEST);

    $params['get_object_data'] = true;
    $company_id = Registry::get('runtime.company_id');

    if (!empty($company_id)) {
        $params['company_id'] = $company_id;
    }

    if (
        !fn_check_user_access($auth['user_id'], 'view_order_communication')
        && $params['communication_type'] === CommunicationTypes::VENDOR_TO_CUSTOMER
    ) {
        $params['object_type'] = [
            VC_OBJECT_TYPE_PRODUCT,
            VC_OBJECT_TYPE_COMPANY,
            VC_OBJECT_TYPE_PRODUCT_FEATURE,
            VC_OBJECT_TYPE_IMPORT_PRESET
        ];
    }

    $communication_type_list = CommunicationTypes::all();

    $enabled_communication_type_list = [];

    foreach ($communication_type_list as $type) {
        if (Registry::get('addons.vendor_communication.' . $type . '_communication') === YesNo::YES) {
            $enabled_communication_type_list[] = $type;
        }
    }

    if (count($enabled_communication_type_list) > 1) {
        foreach ($enabled_communication_type_list as $type) {
            if (
                fn_check_permissions(
                    'vendor_communication',
                    'threads',
                    'admin',
                    'GET',
                    ['communication_type' => $type]
                )
            ) {
                if ($auth['user_type'] === UserTypes::VENDOR && $type === CommunicationTypes::VENDOR_TO_ADMIN) {
                    Registry::set(
                        'navigation.dynamic.sections.' . $type,
                        [
                            'title' => __('vendor_communication.tab_' . $type . '_for_vendor_panel'),
                            'href'  => 'vendor_communication.threads?communication_type=' . $type,
                        ]
                    );
                } else {
                    Registry::set(
                        'navigation.dynamic.sections.' . $type,
                        [
                            'title' => __('vendor_communication.tab_' . $type),
                            'href'  => 'vendor_communication.threads?communication_type=' . $type,
                        ]
                    );
                }
            }
        }
    }

    Registry::set('navigation.dynamic.active_section', $params['communication_type']);

    list($threads, $search) = fn_vendor_communication_get_threads(
        $params,
        Registry::get('settings.Appearance.admin_elements_per_page')
    );
    /** @var array $auth */
    $threads = fn_vendor_communication_get_threads_user_status($threads, $auth);

    $view->assign([
        'threads'                 => $threads,
        'search'                  => $search,
        'communication_type'      => $params['communication_type'],
        'communication_type_list' => $communication_type_list,
        'company_id'              => Registry::get('runtime.company_id'),
    ]);

} elseif ($mode == 'view') {

    if (!isset($_REQUEST['thread_id']) || !fn_vendor_communication_can_user_access_thread($_REQUEST['thread_id'], $auth)) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    if (!isset($_REQUEST['communication_type'])) {
        $redirect_url = fn_vendor_communication_get_redirect_to_communication_type($_REQUEST['thread_id']);
        if ($redirect_url) {
            return [CONTROLLER_STATUS_REDIRECT, $redirect_url];
        }
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    if (!fn_vendor_communication_is_communication_type_active($_REQUEST['communication_type'])) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $company_id = Registry::get('runtime.company_id');

    $params = [
        'get_object' => true,
        'thread_id' => (int) $_REQUEST['thread_id'],
        'communication_type' => $_REQUEST['communication_type']
    ];

    if (!empty($company_id)) {
        $params['company_id'] = $company_id;
    }

    if (
        !fn_check_user_access($auth['user_id'], 'view_order_communication')
        && $params['communication_type'] === CommunicationTypes::VENDOR_TO_CUSTOMER
    ) {
        $params['object_type'] = [
            VC_OBJECT_TYPE_PRODUCT,
            VC_OBJECT_TYPE_COMPANY,
            VC_OBJECT_TYPE_PRODUCT_FEATURE,
            VC_OBJECT_TYPE_IMPORT_PRESET
        ];
    }

    $thread = fn_vendor_communication_get_thread($params);

    if (empty($thread)) {
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    $thread_user_status = fn_vendor_communication_get_thread_user_status($thread, $auth);

    if ($thread_user_status == VC_THREAD_STATUS_HAS_NEW_MESSAGE) {
        fn_vendor_communication_mark_thread_as_viewed($thread);
    }

    $messages = fn_vendor_communication_get_thread_messages($params);

    $allow_send = fn_check_permissions(
        'vendor_communication',
        'post_message',
        'admin',
        'POST',
        ['communication_type' => $thread['communication_type']]
    );

    if (
        $thread['object_type'] === VC_OBJECT_TYPE_ORDER
        && $thread['communication_type'] === CommunicationTypes::VENDOR_TO_CUSTOMER
    ) {
        $allow_send = $allow_send && fn_check_user_access($auth['user_id'], 'manage_order_communication');
    }

    $view->assign([
        'messages'   => $messages,
        'thread_id'  => $_REQUEST['thread_id'],
        'thread'     => $thread,
        'allow_send' => $allow_send,
    ]);
}
