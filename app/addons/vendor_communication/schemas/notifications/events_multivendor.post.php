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

use Tygh\Addons\VendorCommunication\Notifications\DataProviders\CommunicationDataProvider;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Notifications\DataValue;
use Tygh\Notifications\Transports\Internal\InternalMessageSchema;
use Tygh\Notifications\Transports\Internal\InternalTransport;
use Tygh\Notifications\Transports\Mail\MailMessageSchema;
use Tygh\Notifications\Transports\Mail\MailTransport;
use Tygh\NotificationsCenter\NotificationsCenter;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

/** @var array $schema */
$schema['vendor_communication.message_received']['name']['template'] = 'vendor_communication.event.message_received.name';

$schema['vendor_communication.message_received']['receivers'][UserTypes::VENDOR] = [
    MailTransport::getId() => MailMessageSchema::create([
        'area'          => SiteArea::ADMIN_PANEL,
        'from'          => 'default_company_users_department',
        'to'            => DataValue::create('to.vendor'),
        'company_id'    => 0,
        'to_company_id' => DataValue::create('to_company_id'),
        'template_code' => 'vendor_communication.notify_admin',
        'language_code' => DataValue::create('language_codes.vendor', CART_LANGUAGE),
        'storefront_id' => DataValue::create('storefront_id'),
        'data_modifier' => static function (array $data) {
            $thread_url = fn_url(
                'vendor_communication.view?thread_id=' . $data['thread_id']
                . '&communication_type=' . $data['communication_type'],
                SiteArea::VENDOR_PANEL
            );
            if (!empty($data['last_message_user_id'])) {
                $message_from = fn_vendor_communication_get_user_name($data['last_message_user_id']);
            }
            $message_from = !empty($message_from) ? $message_from : __('customer');

            return array_merge($data, [
                'thread_url' => $thread_url,
                'message_from' => $message_from,
            ]);
        }
    ]),
    InternalTransport::getId() => InternalMessageSchema::create([
        'tag'                       => 'vendor_communication',
        'area'                      => SiteArea::VENDOR_PANEL,
        'section'                   => NotificationsCenter::SECTION_COMMUNICATION,
        'to_company_id'             => DataValue::create('to_company_id'),
        'language_code'             => DataValue::create('language_codes.vendor', CART_LANGUAGE),
        'action_url'                => DataValue::create('action_url'),
        'severity'                  => NotificationSeverity::NOTICE,
        'template_code'             => 'vendor_communication_message_received',
    ]),
];

$schema['vendor_communication.vendor_to_admin_message_received'] = [
    'group'     => 'vendor_communication',
    'name'      => [
        'template' => 'vendor_communication.event.vendor_to_admin_message_received.name',
        'params'   => [
        ],
    ],
    'data_provider' => [CommunicationDataProvider::class, 'factory'],
    'receivers' => [
        UserTypes::ADMIN => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'          => SiteArea::ADMIN_PANEL,
                'from'          => 'default_company_users_department',
                'template_code' => 'vendor_communication.notify_admin',
                'language_code' => DataValue::create('language_codes.admin', CART_LANGUAGE),
                'to'            => DataValue::create('to.admin', 'company_users_department'),
                'company_id'    => 0,
                'to_company_id' => DataValue::create('to_company_id'),
                'data_modifier' => static function (array $data) {
                    $thread_url = fn_url(
                        'admin:vendor_communication.view?thread_id=' . $data['thread_id']
                        . '&communication_type=' . $data['communication_type'],
                        SiteArea::ADMIN_PANEL
                    );
                    if (!empty($data['last_message_user_id'])) {
                        $message_from = fn_vendor_communication_get_user_name($data['last_message_user_id']);
                    }
                    $message_from = !empty($message_from) ? $message_from : __('customer');

                    return array_merge($data, [
                        'thread_url' => $thread_url,
                        'message_from' => $message_from,
                    ]);
                }
            ]),
            InternalTransport::getId() => InternalMessageSchema::create([
                'tag'                       => 'vendor_communication',
                'area'                      => SiteArea::ADMIN_PANEL,
                'section'                   => NotificationsCenter::SECTION_COMMUNICATION,
                'to_company_id'             => DataValue::create('to_company_id'),
                'language_code'             => Registry::get('settings.Appearance.backend_default_language'),
                'action_url'                => DataValue::create('action_url'),
                'severity'                  => NotificationSeverity::NOTICE,
                'template_code'             => 'vendor_communication_message_received',
            ]),
        ],
        UserTypes::VENDOR => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'          => SiteArea::ADMIN_PANEL,
                'from'          => 'default_company_users_department',
                'to'            => DataValue::create('to.vendor'),
                'company_id'    => 0,
                'to_company_id' => DataValue::create('to_company_id'),
                'template_code' => 'vendor_communication.notify_admin',
                'language_code' => DataValue::create('language_codes.vendor', CART_LANGUAGE),
                'data_modifier' => static function (array $data) {
                    $thread_url = fn_url(
                        'vendor_communication.view?thread_id=' . $data['thread_id']
                        . '&communication_type=' . $data['communication_type'],
                        SiteArea::VENDOR_PANEL
                    );
                    if (!empty($data['last_message_user_id'])) {
                        $message_from = fn_vendor_communication_get_user_name($data['last_message_user_id']);
                    }
                    $message_from = !empty($message_from) ? $message_from : __('customer');

                    return array_merge($data, [
                        'thread_url' => $thread_url,
                        'message_from' => $message_from,
                    ]);
                }
            ]),
            InternalTransport::getId() => InternalMessageSchema::create([
                'tag'                       => 'vendor_communication',
                'area'                      => SiteArea::VENDOR_PANEL,
                'section'                   => NotificationsCenter::SECTION_COMMUNICATION,
                'to_company_id'             => DataValue::create('to_company_id'),
                'language_code'             => DataValue::create('language_codes.vendor', CART_LANGUAGE),
                'action_url'                => DataValue::create('action_url'),
                'severity'                  => NotificationSeverity::NOTICE,
                'template_code'             => 'vendor_communication_message_received',
            ]),
        ]
    ],
];

$schema['vendor_communication.order_message_received']['receivers'][UserTypes::VENDOR] = $schema['vendor_communication.message_received']['receivers'][UserTypes::VENDOR];

return $schema;
