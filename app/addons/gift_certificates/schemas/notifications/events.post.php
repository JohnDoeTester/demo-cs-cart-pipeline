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

use Tygh\Enum\SiteArea;
use Tygh\Notifications\DataValue;
use Tygh\Addons\GiftCertificates\Notifications\DataProviders\GiftCertificateDataProvider;
use Tygh\Notifications\Transports\Mail\MailMessageSchema;
use Tygh\Enum\UserTypes;
use Tygh\Notifications\Transports\Mail\MailTransport;

defined('BOOTSTRAP') or die('Access denied');

$certificate_event = [
    'id'        => 'gift_certificates.gift_certificate.status_changed',
    'group'     => 'gift_certificate',
    'name'      => [
        'template' => 'gift_certificates.event.gift_certificate.status_changed.name',
        'params'   => [
            '[status]' => '',
        ],
    ],
    'data_provider' => [GiftCertificateDataProvider::class, 'factory'],
    'receivers' => [
        UserTypes::CUSTOMER => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'            => SiteArea::STOREFRONT,
                'from'            => 'company_orders_department',
                'to'              => DataValue::create('certificate_data.email'),
                'template_code'   => 'gift_certificates_notification',
                'legacy_template' => 'addons/gift_certificates/gift_certificate.tpl',
                'language_code'   => DataValue::create('lang_code', CART_LANGUAGE),
                'storefront_id'   => DataValue::create('storefront_id'),
            ]),
        ],
    ],
];

foreach (fn_get_simple_statuses(STATUSES_GIFT_CERTIFICATE) as $status_to => $status_description) {
    $status_to = strtolower($status_to);

    $certificate_change_status_event = $certificate_event;
    $certificate_change_status_event['id'] = $certificate_event['id'] . ".{$status_to}";
    $certificate_change_status_event['name']['params']['[status]'] = $status_description;

    $schema[$certificate_change_status_event['id']] = $certificate_change_status_event;
}

$certificate_update_status_event = $certificate_event;
$certificate_update_status_event['id'] = 'gift_certificates.gift_certificate.updated';
$certificate_update_status_event['name']['template'] = 'gift_certificates.event.gift_certificate.updated.name';
$schema[$certificate_update_status_event['id']] = $certificate_update_status_event;


return $schema;
