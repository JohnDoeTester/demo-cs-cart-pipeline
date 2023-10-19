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
use Tygh\Enum\UserTypes;
use Tygh\Notifications\DataValue;
use Tygh\Notifications\Transports\Internal\InternalMessageSchema;
use Tygh\Notifications\Transports\Internal\InternalTransport;
use Tygh\NotificationsCenter\NotificationsCenter;
use Tygh\Enum\SiteArea;
use Tygh\Notifications\Transports\Mail\MailTransport;
use Tygh\Notifications\Transports\Mail\MailMessageSchema;

defined('BOOTSTRAP') or die('Access denied');

/**
 * @var array<string, array> $schema
 */

$schema['vendor_debt_payout.negative_balance_reached'] = [
    'group'     => 'vendor_debt_payout',
    'name'      => [
        'template' => 'vendor_debt_payout.event.negative_balance_reached.name',
        'params'   => [],
    ],
    'receivers' => [
        UserTypes::VENDOR => [
            InternalTransport::getId() => InternalMessageSchema::create([
                'tag'                       => 'vendor_debt_payout',
                'area'                      => SiteArea::VENDOR_PANEL,
                'section'                   => NotificationsCenter::SECTION_ADMINISTRATION,
                'to_company_id'             => DataValue::create('to_company_id'),
                'language_code'             => DataValue::create('lang_code', CART_LANGUAGE),
                'action_url'                => DataValue::create('action_url'),
                'severity'                  => NotificationSeverity::NOTICE,
                'template_code'             => 'vendor_debt_payout_negative_balance_reached',
            ]),
        ]
    ],
];

$schema['vendor_debt_payout.vendor_status_changed_to_suspended'] = [
    'group'     => 'vendor_debt_payout',
    'name'      => [
        'template' => 'vendor_debt_payout.event.vendor_status_changed_to_suspended.name',
        'params'   => [],
    ],
    'receivers' => [
        UserTypes::ADMIN => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'default_company_users_department',
                'to'              => 'company_orders_department',
                'reply_to'        => 'company_orders_department',
                'template_code'   => 'vendor_debt_payout_email_admin_notification_vendor_status_changed_to_suspended',
                'language_code'   => DataValue::create('lang_code', CART_LANGUAGE),
            ]),
            InternalTransport::getId() => InternalMessageSchema::create([
                'tag'                       => 'vendor_debt_payout',
                'area'                      => SiteArea::ADMIN_PANEL,
                'section'                   => NotificationsCenter::SECTION_ADMINISTRATION,
                'to_company_id'             => 0,
                'language_code'             => DataValue::create('lang_code', CART_LANGUAGE),
                'severity'                  => NotificationSeverity::NOTICE,
                'template_code'             => 'vendor_debt_payout_internal_admin_notification_vendor_status_changed_to_suspended',
                'action_url'                => DataValue::create('action_url'),
            ]),
        ]
    ],
];

$schema['vendor_debt_payout.vendor_status_changed_to_disabled'] = [
    'group'     => 'vendor_debt_payout',
    'name'      => [
        'template' => 'vendor_debt_payout.event.vendor_status_changed_to_disabled.name',
        'params'   => [],
    ],
    'receivers' => [
        UserTypes::ADMIN => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'default_company_users_department',
                'to'              => 'company_orders_department',
                'reply_to'        => 'company_orders_department',
                'template_code'   => 'vendor_debt_payout_email_admin_notification_vendor_status_changed_to_disabled',
                'language_code'   => DataValue::create('lang_code', CART_LANGUAGE),
            ]),
            InternalTransport::getId() => InternalMessageSchema::create([
                'tag'                       => 'vendor_debt_payout',
                'area'                      => SiteArea::ADMIN_PANEL,
                'section'                   => NotificationsCenter::SECTION_ADMINISTRATION,
                'to_company_id'             => 0,
                'language_code'             => DataValue::create('lang_code', CART_LANGUAGE),
                'severity'                  => NotificationSeverity::NOTICE,
                'template_code'             => 'vendor_debt_payout_internal_admin_notification_vendor_status_changed_to_disabled',
                'action_url'                => DataValue::create('action_url'),
            ]),
        ]
    ],
];

$schema['vendor_debt_payout.vendor_days_before_suspend'] = [
    'group'     => 'vendor_debt_payout',
    'name'      => [
        'template' => 'vendor_debt_payout.event.vendor_days_before_suspend.name',
        'params'   => [],
    ],
    'receivers' => [
        UserTypes::VENDOR => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'default_company_users_department',
                'to'              => DataValue::create('vendor_email'),
                'reply_to'        => 'company_orders_department',
                'template_code'   => 'vendor_debt_payout_vendor_days_before_suspended',
                'language_code'   => DataValue::create('lang_code', CART_LANGUAGE),
                'storefront_id'   => DataValue::create('storefront_id'),
            ]),
        ]
    ],
];

$schema['vendor_debt_payout.weekly_digest_of_debtors'] = [
    'group'     => 'vendor_debt_payout',
    'name'      => [
        'template' => 'vendor_debt_payout.event.weekly_digest_of_debtors.name',
        'params'   => [],
    ],
    'receivers' => [
        UserTypes::ADMIN => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'default_company_users_department',
                'to'              => 'company_orders_department',
                'reply_to'        => 'company_orders_department',
                'template_code'   => 'vendor_debt_payout_weekly_digest_of_debtors',
                'language_code'   => DataValue::create('lang_code', CART_LANGUAGE),
            ]),
        ]
    ],
];

return $schema;
