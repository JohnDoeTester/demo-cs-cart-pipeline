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

use Tygh\Addons\CallRequests\Notifications\DataProviders\RequestAboutProductCreatedDataProvider;
use Tygh\Enum\SiteArea;
use Tygh\Enum\UserTypes;
use Tygh\Notifications\DataValue;
use Tygh\Notifications\Transports\Mail\MailMessageSchema;
use Tygh\Notifications\Transports\Mail\MailTransport;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

$schema['call_requests.request_about_product_created'] = [
    'group'     => 'call_requests',
    'name'      => [
        'template' => 'call_requests.event.request_about_product_created.name',
        'params'   => [],
    ],
    'data_provider' => [RequestAboutProductCreatedDataProvider::class, 'factory'],
    'receivers' => [
        UserTypes::ADMIN => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'default_company_orders_department',
                'to'              => 'company_orders_department',
                'template_code'   => 'call_requests_buy_with_one_click',
                'legacy_template' => 'addons/call_requests/buy_with_one_click.tpl',
                'company_id'      => DataValue::create('call_request_data.company_id'),
                'to_company_id'   => DataValue::create('call_request_data.company_id'),
                'language_code'   => Registry::get('settings.Appearance.backend_default_language'),
            ]),
        ],
    ],
];

return $schema;
