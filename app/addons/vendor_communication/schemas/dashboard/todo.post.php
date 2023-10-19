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
use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Tools\Url;

defined('BOOTSTRAP') or die('Access denied');

/**
 * @var array<string, array> $schema
 */

$schema['vendor_communication_admin_to_vendor_messages'] = [
    'type'             => NotificationSeverity::NOTICE,
    'area'             => SiteArea::VENDOR_PANEL,
    'content_callback' => static function ($auth) {
        $thread_ids = db_get_fields(
            'SELECT thread_id FROM ?:vendor_communications'
            . ' WHERE company_id = ?s AND status = ?s AND communication_type = ?s AND last_message_user_type = ?s',
            $auth['company_id'],
            VC_THREAD_STATUS_HAS_NEW_MESSAGE,
            CommunicationTypes::VENDOR_TO_ADMIN,
            UserTypes::ADMIN
        );

        if (empty($thread_ids)) {
            return false;
        }

        $threads_count = count($thread_ids);

        return [
            'text' => __('vendor_communication.dashboard.todo.messages_from_administrator', [$threads_count]),
            'action_url' => Url::buildUrn(['vendor_communication', 'threads'], ['communication_type' => CommunicationTypes::VENDOR_TO_ADMIN])
        ];
    }
];

$schema['vendor_communication_customer_to_vendor_messages'] = [
    'type'             => NotificationSeverity::NOTICE,
    'area'             => SiteArea::VENDOR_PANEL,
    'content_callback' => static function ($auth) {
        $thread_ids = db_get_fields(
            'SELECT thread_id FROM ?:vendor_communications'
            . ' WHERE company_id = ?s AND status = ?s AND communication_type = ?s AND last_message_user_type IN (?a)',
            $auth['company_id'],
            VC_THREAD_STATUS_HAS_NEW_MESSAGE,
            CommunicationTypes::VENDOR_TO_CUSTOMER,
            [UserTypes::CUSTOMER, UserTypes::ADMIN]
        );

        if (empty($thread_ids)) {
            return false;
        }

        $threads_count = count($thread_ids);

        return [
            'text' => __('vendor_communication.dashboard.todo.messages_from_customers', [$threads_count]),
            'action_url' => Url::buildUrn(['vendor_communication', 'threads'], ['communication_type' => CommunicationTypes::VENDOR_TO_CUSTOMER])
        ];
    }
];

$schema['vendor_communication_vendor_to_admin_messages'] = [
    'type'             => NotificationSeverity::NOTICE,
    'area'             => SiteArea::ADMIN_PANEL,
    'content_callback' => static function ($auth) {
        $thread_ids = db_get_fields(
            'SELECT thread_id FROM ?:vendor_communications'
            . ' WHERE status = ?s AND communication_type = ?s AND last_message_user_type = ?s',
            VC_THREAD_STATUS_HAS_NEW_MESSAGE,
            CommunicationTypes::VENDOR_TO_ADMIN,
            UserTypes::VENDOR
        );

        if (empty($thread_ids)) {
            return false;
        }

        $threads_count = count($thread_ids);

        return [
            'text' => __('vendor_communication.dashboard.todo.messages_from_vendors', [$threads_count]),
            'action_url' => Url::buildUrn(['vendor_communication', 'threads'], ['communication_type' => CommunicationTypes::VENDOR_TO_ADMIN])
        ];
    }
];

$schema['vendor_communication_customer_to_admin_messages'] = [
    'type'             => NotificationSeverity::NOTICE,
    'area'             => SiteArea::ADMIN_PANEL,
    'content_callback' => static function ($auth) {
        if (!fn_allowed_for('ULTIMATE')) {
            return false;
        }

        $thread_ids = db_get_fields(
            'SELECT thread_id FROM ?:vendor_communications'
            . ' WHERE status = ?s AND communication_type = ?s AND last_message_user_type = ?s',
            VC_THREAD_STATUS_HAS_NEW_MESSAGE,
            CommunicationTypes::VENDOR_TO_CUSTOMER,
            UserTypes::CUSTOMER
        );

        if (empty($thread_ids)) {
            return false;
        }

        $threads_count = count($thread_ids);

        return [
            'text' => __('vendor_communication.dashboard.todo.messages_from_customers', [$threads_count]),
            'action_url' => Url::buildUrn(['vendor_communication', 'threads'], ['communication_type' => CommunicationTypes::VENDOR_TO_CUSTOMER])
        ];
    }
];

return $schema;
