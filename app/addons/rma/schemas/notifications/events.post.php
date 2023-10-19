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

use Tygh\Addons\Rma\Notifications\DataProviders\ReturnRequestDataProvider;
use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Notifications\DataValue;
use Tygh\Notifications\Transports\Mail\MailMessageSchema;
use Tygh\Notifications\Transports\Mail\MailTransport;

defined('BOOTSTRAP') or die('Access denied');

$rma_event = [
    'id' => 'rma.status_changed',
    'group'     => 'rma',
    'name'      => [
        'template' => 'event.rma.status_changed.name',
        'params'   => [
            '[status]' => '',
        ],
    ],
    'data_provider' => [ReturnRequestDataProvider::class, 'factory'],
    'receivers' => [
        UserTypes::CUSTOMER => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'            => SiteArea::STOREFRONT,
                'from'            => 'company_orders_department',
                'to'              => DataValue::create('order_info.email'),
                'template_code'   => 'rma_slip_notification',
                'legacy_template' => 'addons/rma/slip_notification.tpl',
                'company_id'      => DataValue::create('order_info.company_id'),
                'to_company_id'   => DataValue::create('order_info.company_id'),
                'language_code'   => DataValue::create('lang_code', CART_LANGUAGE)
            ]),
        ],
        UserTypes::ADMIN => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'default_company_orders_department',
                'to'              => 'default_company_orders_department',
                'reply_to'        => DataValue::create('order_info.email'),
                'template_code'   => 'rma_slip_notification',
                'legacy_template' => 'addons/rma/slip_notification.tpl',
                'company_id'      => DataValue::create('order_info.company_id'),
                'to_company_id'   => DataValue::create('order_info.company_id'),
                'language_code'   => DataValue::create('lang_code', CART_LANGUAGE)
            ])
        ],
    ],
];


if (fn_allowed_for('MULTIVENDOR')) {
    $rma_event['receivers'][UserTypes::VENDOR] = [
        MailTransport::getId() => MailMessageSchema::create(
            [
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'default_company_orders_department',
                'to'              => 'company_orders_department',
                'reply_to'        => DataValue::create('order_info.email'),
                'template_code'   => 'rma_slip_notification',
                'legacy_template' => 'addons/rma/slip_notification.tpl',
                'company_id'      => 0,
                'to_company_id'   => DataValue::create('order_info.company_id'),
                'language_code'   => DataValue::create('lang_code', CART_LANGUAGE),
            ]
        ),
    ];
}

foreach (fn_get_simple_statuses(STATUSES_RETURN) as $status_id => $status_description) {
    $status_id = strtolower($status_id);

    $rma_change_status_event = $rma_event;
    $rma_change_status_event['id'] = "rma.status_changed.{$status_id}";
    $rma_change_status_event['name']['params']['[status]'] = $status_description;

    $schema[$rma_change_status_event['id']] = $rma_change_status_event;
}

return $schema;
