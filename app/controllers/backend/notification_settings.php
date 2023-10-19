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

use Tygh\Enum\ProfileFieldTypes;
use Tygh\Enum\ProfileTypes;
use Tygh\Enum\ReceiverSearchMethods;
use Tygh\Enum\UserTypes;
use Tygh\Enum\YesNo;
use Tygh\Notifications\DataValue;
use Tygh\Notifications\Receivers\SearchCondition;
use Tygh\Notifications\Transports\Mail\MailTransport;
use Tygh\Providers\EventDispatcherProvider;
use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if ($mode === 'm_update') {
        if (!isset($_REQUEST['notification_settings']) || !is_array($_REQUEST['notification_settings'])) {
            return array(CONTROLLER_STATUS_OK, 'notification_settings.manage');
        }

        foreach ($_REQUEST['notification_settings'] as $event_id => $event) {
            foreach ($event as $receiver => $transports) {
                foreach ($transports as $transport_name => $is_allowed) {
                    $is_allowed = (int) YesNo::toBool($is_allowed);
                    fn_set_notification_settings($event_id, $transport_name, $receiver, $is_allowed);
                }
            }
        }

        return [CONTROLLER_STATUS_OK, 'notification_settings.manage?receiver_type=' . $_REQUEST['receiver_type']];
    }

    if ($mode === 'update_receivers') {
        $params = array_merge([
            'receiver_type' => UserTypes::ADMIN,
            'object_type'   => 'group',
            'object_id'     => null,
            'conditions'    => [],
        ], $_REQUEST);

        if ($params['object_id'] === null) {
            return [CONTROLLER_STATUS_DENIED];
        }

        fn_update_notification_receiver_search_conditions(
            $params['object_type'],
            $params['object_id'],
            $params['receiver_type'],
            SearchCondition::makeList($params['conditions'])
        );

        return [CONTROLLER_STATUS_OK, 'notification_settings.manage?receiver_type=' . $params['receiver_type']];
    }

}

if ($mode == 'manage') {
    $selected_section = (empty($_REQUEST['selected_section']) ? 'detailed' : $_REQUEST['selected_section']);

    if (isset($_REQUEST['receiver_type'])) {
        $receiver_type = $_REQUEST['receiver_type'];
    } else {
        return array(CONTROLLER_STATUS_NO_PAGE);
    }

    $notification_settings = EventDispatcherProvider::getNotificationSettings(true);
    $events_schema = EventDispatcherProvider::getEventsSchema();
    $groups_schema = EventDispatcherProvider::getEventGroupsSchema();

    $events = [];
    $transports = [];

    foreach ($notification_settings as $event_name => $event) {
        //Grouping events by group identifier
        $events[$event['group']][$event_name] = $event;
        foreach ($event['receivers'] as $receiver => $avaliable_transports) {
            if (isset($avaliable_transports[MailTransport::getId()])) {
                $event_schema = empty($events_schema[$event_name]['receivers'][$receiver][MailTransport::getId()])
                    ? null
                    : $events_schema[$event_name]['receivers'][$receiver][MailTransport::getId()];

                if ($event_schema) {
                    $template_code = $event_schema->template_code;
                    if (!empty($template_code) && !($template_code instanceof DataValue)) {
                        $events[$event['group']][$event_name]['receivers'][$receiver]['template_code'] = $template_code;
                        $events[$event['group']][$event_name]['receivers'][$receiver]['template_area'] = $event_schema->area;
                    }
                }
            }
            foreach ($avaliable_transports as $transport => $callback) {
                //Marking transports that is using by certain receiver
                $transports[$receiver][$transport] = true;
            }
        }
    }

    if ($receiver_type == UserTypes::CUSTOMER) {
        $active_section = 'customer_notifications';
    } elseif ($receiver_type == UserTypes::ADMIN) {
        $active_section = 'admin_notifications';
    } elseif ($receiver_type == UserTypes::VENDOR) {
        $active_section = 'vendor_notifications';
    } else {
        $active_section = '';
    }

    Tygh::$app['view']->assign([
        'receiver_type'  => $receiver_type,
        'event_groups'   => $events,
        'group_settings' => $groups_schema,
        'transports'     => $transports,
        'active_section' => $active_section,
    ]);
}

if ($mode === 'get_usergroups') {
    $search_query = isset($_REQUEST['q'])
        ? $_REQUEST['q']
        : '';
    $usergroup_type = isset($_REQUEST['type'])
        ? $_REQUEST['type']
        : UserTypes::ADMIN;
    $lang_code = isset($_REQUEST['lang_code'])
        ? $_REQUEST['lang_code']
        : CART_LANGUAGE;
    $usergroup_ids = isset($_REQUEST['ids'])
        ? array_filter((array) $_REQUEST['ids'])
        : null;
    $group = isset($_REQUEST['group'])
        ? $_REQUEST['group']
        : null;
    $objects = [];

    if (!$usergroup_ids) {
        $params = [
            'q'    => $search_query,
            'type' => $usergroup_type,
        ];

        $usergroups = fn_get_usergroups($params, $lang_code);

        $usergroup_ids = array_keys($usergroups);
    }


    if ($group === 'orders') {
        $objects[] = [
            'id' => ReceiverSearchMethods::ORDER_MANAGER,
            'text' => __('order_manager'),
            'data' => [
                'method' => ReceiverSearchMethods::ORDER_MANAGER,
                'criterion' => ReceiverSearchMethods::ORDER_MANAGER,
            ]
        ];
    }

    if ($usergroup_type === UserTypes::VENDOR && fn_allowed_for('MULTIVENDOR')) {
        $objects[] = [
            'id'   => ReceiverSearchMethods::VENDOR_OWNER,
            'text' => __('vendor_owner'),
            'data' => [
                'method'    => ReceiverSearchMethods::VENDOR_OWNER,
                'criterion' => ReceiverSearchMethods::VENDOR_OWNER,
            ]
        ];
    }

    if ($usergroup_ids) {
        $usergroups_data = fn_get_usergroups(
            [
                'usergroup_id' => $usergroup_ids,
            ]
        );

        $usergroups_data = array_values(
            array_map(
                function ($usergroup) {
                    return [
                        'id'   => $usergroup['usergroup_id'],
                        'text' => $usergroup['usergroup'],
                        'data' => [
                            'method' => ReceiverSearchMethods::USERGROUP_ID,
                            'criterion' => $usergroup['usergroup_id'],
                        ]
                    ];
                },
                $usergroups_data
            )
        );

        $objects = array_merge($objects, $usergroups_data);
    }

    /** @var \Tygh\Ajax $ajax */
    $ajax = Tygh::$app['ajax'];
    $ajax->assign('objects', $objects);
    $ajax->assign('total_objects', count($objects));

    return [CONTROLLER_STATUS_NO_CONTENT];
}

if ($mode === 'get_users') {
    $search_query = isset($_REQUEST['q'])
        ? $_REQUEST['q']
        : '';
    $items_per_page = (int) (isset($_REQUEST['items_per_page'])
        ? $_REQUEST['items_per_page']
        : Registry::get('settings.Appearance.admin_elements_per_page'));
    $current_page = (int) (isset($_REQUEST['page'])
        ? $_REQUEST['page']
        : 1);
    $user_type = isset($_REQUEST['type'])
        ? $_REQUEST['type']
        : UserTypes::ADMIN;
    $lang_code = isset($_REQUEST['lang_code'])
        ? $_REQUEST['lang_code']
        : CART_LANGUAGE;
    $user_ids = isset($_REQUEST['ids'])
        ? array_filter((array) $_REQUEST['ids'])
        : null;
    $company_id = Registry::get('runtime.company_id');
    $objects = [];

    if (!$user_ids) {
        $params = [
            'page' => $current_page,
            'user_type' => $user_type,
            'search_query' => $search_query,
            'extended_search' => false,
        ];

        list($users, $params) = fn_get_users($params, Tygh::$app['session']['auth'], $items_per_page);

        $user_ids = array_column($users, 'user_id');
    }

    if ($user_ids) {
        list($users_data, $params) = fn_get_users(
            [
                'page' => $current_page,
                'user_id' => $user_ids,
            ],
            Tygh::$app['session']['auth'],
            $items_per_page
        );

        $objects = array_values(
            array_map(
                function ($user_info) use ($company_id) {
                    $email = fn_get_user_email($user_info['user_id'], $user_info);
                    $name = fn_get_user_name($user_info['user_id'], $user_info) ?: $email;

                    return [
                        'id'   => $user_info['user_id'],
                        'text' => $name,
                        'data' => [
                            'name'         => $name,
                            'email'        => $email,
                            'company_name' => (string) $user_info['company_name'],
                            'method'       => ReceiverSearchMethods::USER_ID,
                            'criterion'    => $user_info['user_id'],
                        ],
                    ];
                },
                $users_data
            )
        );
    }

    /** @var \Tygh\Ajax $ajax */
    $ajax = Tygh::$app['ajax'];
    $ajax->assign('objects', $objects);
    $ajax->assign('total_objects', isset($params['total_items']) ? $params['total_items'] : count($objects));

    return [CONTROLLER_STATUS_NO_CONTENT];
}

if ($mode === 'get_vendor_emails') {
    $search_query = isset($_REQUEST['q'])
        ? $_REQUEST['q']
        : '';
    $field_ids = isset($_REQUEST['ids'])
        ? array_filter((array) $_REQUEST['ids'])
        : [];
    $objects = [];

    $params = [
        'include_types'           => (array) ProfileFieldTypes::EMAIL,
        'profile_type'            => ProfileTypes::CODE_SELLER,
        'get_contact_email_field' => false,
        'include_ids'             => $field_ids,
        'search_query'            => $search_query,
    ];
    $vendor_email_fields = fn_get_profile_fields('', [], CART_LANGUAGE, $params);

    $email_fields = array_reduce($vendor_email_fields, static function ($fields, $field_section) {
        foreach ($field_section as $field) {
            $fields[$field['field_id']] = $field['description'];
        }
        return $fields;
    }, []);

    if ($email_fields) {
        $objects = [];

        foreach ($email_fields as $id => $email_field) {
            $objects[] = [
                'id' => $id,
                'text' => $email_field,
                'data' => [
                    'field' => $email_field,
                    'method'     => ReceiverSearchMethods::VENDOR_EMAIL,
                    'criterion'  => $id
                ]
            ];
        }
    }

    /** @var \Tygh\Ajax $ajax */
    $ajax = Tygh::$app['ajax'];
    $ajax->assign('objects', $objects);
    $ajax->assign('total_objects', count($objects));

    return [CONTROLLER_STATUS_NO_CONTENT];
}
