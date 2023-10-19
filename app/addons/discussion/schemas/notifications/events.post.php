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
use Tygh\Notifications\Transports\Mail\MailMessageSchema;
use Tygh\Enum\UserTypes;
use Tygh\Notifications\Transports\Mail\MailTransport;

defined('BOOTSTRAP') or die('Access denied');

$schema['discussion.products.new_post'] = [
    'id'        => 'discussion.products.new_post',
    'group'     => 'discussion',
    'name'      => [
        'template' => 'discussion.event.products.new_post',
        'params'   => [
        ],
    ],
    'receivers' => [
        UserTypes::ADMIN => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'company_site_administrator',
                'to'              => 'company_orders_department',
                'to_company_id'   => DataValue::create('company_id'),
                'template_code'   => 'discussion_notification',
                'legacy_template' => 'addons/discussion/notification.tpl',
                'language_code'   => DataValue::create('lang_code', CART_LANGUAGE),
                'data_modifier' => static function (array $data) {
                    $url = fn_url('admin:' . $data['url'], SiteArea::ADMIN_PANEL);

                    return array_merge($data, [
                        'url' => $url,
                    ]);
                }
            ]),
        ],
    ],
];

$schema['discussion.testimonials.new_post'] = [
    'id'        => 'discussion.testimonials.new_post',
    'group'     => 'discussion',
    'name'      => [
        'template' => 'discussion.event.testimonials.new_post',
        'params'   => [
        ],
    ],
    'receivers' => [
        UserTypes::ADMIN => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'company_site_administrator',
                'to'              => DataValue::create('email'),
                'to_company_id'   => DataValue::create('company_id'),
                'template_code'   => 'discussion_notification',
                'legacy_template' => 'addons/discussion/notification.tpl',
                'language_code'   => DataValue::create('lang_code', CART_LANGUAGE),
                'data_modifier' => static function (array $data) {
                    $url = fn_url('admin:' . $data['url'], SiteArea::ADMIN_PANEL);

                    return array_merge($data, [
                        'url' => $url,
                    ]);
                }
            ]),
        ],
    ],
];

$schema['discussion.orders.new_post'] = [
    'id'        => 'discussion.orders.new_post',
    'group'     => 'discussion',
    'name'      => [
        'template' => 'discussion.event.orders.new_post',
        'params'   => [],
    ],
    'receivers' => [
        UserTypes::ADMIN => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'default_company_orders_department',
                'to'              => 'default_company_orders_department',
                'reply_to'        => DataValue::create('email'),
                'template_code'   => 'discussion_notification',
                'legacy_template' => 'addons/discussion/notification.tpl',
                'language_code'   => DataValue::create('lang_code', CART_LANGUAGE),
                'company_id'      => DataValue::create('company_id'),
                'to_company_id'   => DataValue::create('company_id'),
                'data_modifier' => static function (array $data) {
                    $url = fn_url("admin:orders.details?order_id={$data['object']['object_id']}", SiteArea::ADMIN_PANEL);

                    return array_merge($data, [
                        'url' => $url,
                    ]);
                }
            ]),
        ],
        UserTypes::CUSTOMER => [
            MailTransport::getId() => MailMessageSchema::create([
                'area' => SiteArea::STOREFRONT,
                'from' => 'company_orders_department',
                'to'   => DataValue::create('email'),
                'template_code'   => 'discussion_notification',
                'legacy_template' => 'addons/discussion/notification.tpl',
                'language_code'   => DataValue::create('lang_code', CART_LANGUAGE),
                'data_modifier' => static function (array $data) {
                    $url = fn_url("orders.details?order_id={$data['object']['object_id']}", SiteArea::STOREFRONT);

                    return array_merge($data, [
                        'url' => $url,
                    ]);
                }
            ])
        ],
    ],
];

$schema['discussion.categories.new_post'] = [
    'id'        => 'discussion.categories.new_post',
    'group'     => 'discussion',
    'name'      => [
        'template' => 'discussion.event.categories.new_post',
        'params'   => [
        ],
    ],
    'receivers' => [
        UserTypes::ADMIN => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'company_site_administrator',
                'to'              => DataValue::create('email'),
                'to_company_id'   => DataValue::create('company_id'),
                'template_code'   => 'discussion_notification',
                'legacy_template' => 'addons/discussion/notification.tpl',
                'language_code'   => DataValue::create('lang_code', CART_LANGUAGE),
                'data_modifier' => static function (array $data) {
                    $url = fn_url('admin:' . $data['url'], SiteArea::ADMIN_PANEL);

                    return array_merge($data, [
                        'url' => $url,
                    ]);
                }
            ]),
        ],
    ],
];

$schema['discussion.pages.new_post'] = [
    'id'        => 'discussion.pages.new_post',
    'group'     => 'discussion',
    'name'      => [
        'template' => 'discussion.event.pages.new_post',
        'params'   => [
        ],
    ],
    'receivers' => [
        UserTypes::ADMIN => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'company_site_administrator',
                'to'              => DataValue::create('email'),
                'template_code'   => 'discussion_notification',
                'legacy_template' => 'addons/discussion/notification.tpl',
                'language_code'   => DataValue::create('lang_code', CART_LANGUAGE),
                'company_id'      => DataValue::create('company_id'),
                'to_company_id'   => DataValue::create('company_id'),
                'data_modifier' => static function (array $data) {
                    $url = fn_url('admin:' . $data['url'], SiteArea::ADMIN_PANEL);

                    return array_merge($data, [
                        'url' => $url,
                    ]);
                }
            ]),
        ],
    ],
];

return $schema;
