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
use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Notifications\DataValue;
use Tygh\Notifications\Transports\Internal\InternalMessageSchema;
use Tygh\Notifications\Transports\Internal\InternalTransport;
use Tygh\Notifications\Transports\Mail\MailMessageSchema;
use Tygh\Notifications\Transports\Mail\MailTransport;
use Tygh\NotificationsCenter\NotificationsCenter;

defined('BOOTSTRAP') or die('Access denied');

/** @var array $schema */
$schema['product_reviews.new_post']['receivers'][UserTypes::VENDOR] = [
    MailTransport::getId() => MailMessageSchema::create([
        'area'            => SiteArea::ADMIN_PANEL,
        'from'            => 'default_company_site_administrator',
        'to'              => 'company_site_administrator',
        'template_code'   => 'product_reviews_notification',
        'legacy_template' => 'addons/product_reviews/product_review_notification.tpl',
        'language_code'   => DataValue::create('company.lang_code', CART_LANGUAGE),
        'company_id'      => 0,
        'to_company_id'   => DataValue::create('product_data.company_id'),
        'data_modifier'   => static function (array $data) {
            if (
                empty($data['product_review_data']['product_review_id'])
                || empty($data['product_review_data']['product_id'])
            ) {
                return $data;
            }

            return array_merge($data, [
                'product_review_url'  => fn_url(
                    'product_reviews.update?product_review_id=' . $data['product_review_data']['product_review_id'],
                    SiteArea::VENDOR_PANEL
                ),
                'product_url' => fn_url(
                    'products.update?selected_section=product_reviews&product_id=' . $data['product_review_data']['product_id'],
                    SiteArea::VENDOR_PANEL
                ),
            ]);
        },
    ]),
    InternalTransport::getId() => InternalMessageSchema::create([
        'tag'           => 'product_reviews.new_post',
        'area'          => SiteArea::VENDOR_PANEL,
        'section'       => NotificationsCenter::SECTION_PRODUCTS,
        'action_url'    => DataValue::create('product_review_url'),
        'to_company_id' => DataValue::create('product_data.company_id'),
        'language_code' => DataValue::create('company.lang_code', CART_LANGUAGE),
        'severity'      => NotificationSeverity::NOTICE,
        'title'         => [
            'template' => 'product_reviews.event.new_post.title',
        ],
        'message'       => [
            'template' => 'product_reviews.event.new_post.message',
            'params'   => [
                '[product]' => DataValue::create('product_data.product'),
            ]
        ],
        'data_modifier' => static function (array $data) {
            if (empty($data['product_review_data']['product_review_id'])) {
                return $data;
            }

            return array_merge($data, [
                'product_review_url' => fn_url(
                    'product_reviews.update?product_review_id=' . $data['product_review_data']['product_review_id'],
                    SiteArea::VENDOR_PANEL
                )
            ]);
        }
    ]),
];

return $schema;
