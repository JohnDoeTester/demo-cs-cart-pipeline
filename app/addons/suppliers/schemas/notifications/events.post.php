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

use Tygh\Notifications\Transports\Mail\MailTransport;
use Tygh\Notifications\Transports\Mail\MailMessageSchema;
use Tygh\Addons\Suppliers\Notifications\DataProviders\SuppliersDataProvider;
use Tygh\Notifications\DataValue;

defined('BOOTSTRAP') or die('Access denied');

$supplier_event = [
    'id'        => 'suppliers.order.supplier_notified',
    'group'     => 'orders',
    'name'      => [
        'template' => 'suppliers.event.order.supplier_notified.name',
        'params'   => [
            '[status]' => '',
        ],
    ],
    'data_provider' => [SuppliersDataProvider::class, 'factory'],
    'receivers' => [
        'S' => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'            => 'A',
                'from'            => 'company_orders_department',
                'to'              => DataValue::create('supplier.data.email'),
                'storefront_id'   => DataValue::create('order_info.storefront_id'),
                'reply_to'        => 'company_orders_department',
                'template_code'   => 'suppliers_notification',
                'legacy_template' => 'addons/suppliers/notification.tpl',
                'language_code'   => DataValue::create('lang_code', CART_LANGUAGE),
            ]),
        ],
    ],
];

foreach (fn_get_simple_statuses() as $status_to => $status_description) {
    $status_to = strtolower($status_to);

    $supplier_notified_event = $supplier_event;
    $supplier_notified_event['id'] = $supplier_event['id'] . ".{$status_to}";
    $supplier_notified_event['name']['params']['[status]'] = $status_description;

    foreach ($supplier_event['receivers'] as $receiver => $transports) {
        $mail_message_schema = clone $transports[MailTransport::getId()];
        $mail_message_schema->template_code = "suppliers_notification.{$status_to}";

        $supplier_notified_event['receivers'][$receiver][MailTransport::getId()] = $mail_message_schema;
    }

    $schema[$supplier_notified_event['id']] = $supplier_notified_event;
}

return $schema;
