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
use Tygh\Enum\UserTypes;
use Tygh\Notifications\DataValue;
use Tygh\Notifications\Transports\Mail\MailMessageSchema;
use Tygh\Notifications\Transports\Mail\MailTransport;

defined('BOOTSTRAP') or die('Access denied');

$schema['discussion.orders.new_post']['receivers'][UserTypes::VENDOR] = [
    MailTransport::getId() => MailMessageSchema::create([
        'area' => SiteArea::ADMIN_PANEL,
        'from' => 'company_orders_department',
        'to'   => 'company_orders_department',
        'reply_to'   => DataValue::create('email'),
        'template_code'   => 'discussion_notification',
        'legacy_template' => 'addons/discussion/notification.tpl',
        'language_code'   => DataValue::create('lang_code', CART_LANGUAGE),
        'company_id' => 0,
        'to_company_id' => DataValue::create('company_id'),
        'data_modifier' => static function (array $data) {
            $url = 'orders.details?' . http_build_query([
                'order_id' => $data['object']['object_id'],
            ]);

            return array_merge($data, [
                'url' => fn_url($url, SiteArea::VENDOR_PANEL),
            ]);
        }
    ])
];

$schema['discussion.pages.new_post']['receivers'][UserTypes::VENDOR] = [
    MailTransport::getId() => MailMessageSchema::create([
        'area'            => SiteArea::ADMIN_PANEL,
        'from'            => 'default_company_site_administrator',
        'to'              => 'company_site_administrator',
        'template_code'   => 'discussion_notification',
        'legacy_template' => 'addons/discussion/notification.tpl',
        'language_code'   => DataValue::create('lang_code', CART_LANGUAGE),
        'company_id'    => 0,
        'to_company_id' => DataValue::create('company_id'),
        'data_modifier'   => static function (array $data) {
            $url = 'pages.update?' . http_build_query([
                'page_id'          => $data['object']['object_id'],
                'selected_section' => 'discussion',
            ]);

            return array_merge($data, [
                'url' => fn_url($url, SiteArea::VENDOR_PANEL),
            ]);
        },
    ]),
];

$schema['discussion.products.new_post']['receivers'][UserTypes::VENDOR] = [
    MailTransport::getId() => MailMessageSchema::create([
        'area'            => SiteArea::ADMIN_PANEL,
        'from'            => 'default_company_site_administrator',
        'to'              => 'company_site_administrator',
        'template_code'   => 'discussion_notification',
        'legacy_template' => 'addons/discussion/notification.tpl',
        'language_code'   => DataValue::create('lang_code', CART_LANGUAGE),
        'company_id'      => 0,
        'to_company_id'   => DataValue::create('company_id'),
        'data_modifier'   => static function (array $data) {
            $url = 'products.update?' . http_build_query([
                'product_id'       => $data['object']['object_id'],
                'selected_section' => 'discussion',
            ]);

            return array_merge($data, [
                'url' => fn_url($url, SiteArea::VENDOR_PANEL),
            ]);
        }
    ]),
];

$schema['discussion.vendors.new_post'] = [
    'id'        => 'discussion.vendors.new_post',
    'group'     => 'discussion',
    'name'      => [
        'template' => 'discussion.event.vendors.new_post',
        'params'   => [
        ],
    ],
    'receivers' => [
        UserTypes::ADMIN => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'company_site_administrator',
                'to'              => DataValue::create('email'),
                'company_id'      => 0,
                'to_company_id'   => 0,
                'template_code'   => 'discussion_notification',
                'legacy_template' => 'addons/discussion/notification.tpl',
                'language_code'   => DataValue::create('lang_code', CART_LANGUAGE),
                'data_modifier' => static function (array $data) {
                    $url = fn_url($data['url'], SiteArea::ADMIN_PANEL);

                    return array_merge($data, [
                        'url' => $url,
                    ]);
                }
            ]),
        ],
        UserTypes::VENDOR => [
            MailTransport::getId() => MailMessageSchema::create([
                'area' => SiteArea::ADMIN_PANEL,
                'from' => 'default_company_site_administrator',
                'to' => 'company_site_administrator',
                'template_code' => 'discussion_notification',
                'legacy_template' => 'addons/discussion/notification.tpl',
                'language_code' => DataValue::create('lang_code', CART_LANGUAGE),
                'company_id'    => 0,
                'to_company_id' => DataValue::create('company_id'),
                'data_modifier' => static function (array $data) {
                    $url = 'companies.update?' . http_build_query([
                        'company_id'       => $data['object']['object_id'],
                        'selected_section' => 'discussion',
                    ]);

                    return array_merge($data, [
                        'url' => fn_url($url, SiteArea::VENDOR_PANEL),
                    ]);
                }
            ]),
        ]
    ],
];

return $schema;
