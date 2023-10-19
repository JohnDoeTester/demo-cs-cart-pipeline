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
use Tygh\Models\Company;
use Tygh\Models\VendorPlan;
use Tygh\Notifications\DataValue;
use Tygh\Notifications\Transports\Mail\MailTransport;
use Tygh\Notifications\Transports\Mail\MailMessageSchema;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');
/** @var array<string, array> $schema */

$schema['vendor_plans.plan_changed'] = [
    'group'     => 'vendor_plans',
    'name'      => [
        'template' => 'vendor_plans.event.plan_changed',
        'params'   => [],
    ],
    'receivers' => [
        UserTypes::ADMIN => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'default_company_orders_department',
                'to'              => 'company_support_department',
                'template_code'   => 'vendor_plans_vendor_plan_changed_info_for_admin',
                'legacy_template' => 'addons/vendor_plans/companies/plan_changed.tpl',
                'company_id'      => 0,
                'to_company_id'   => 0,
                'language_code'   => Registry::get('settings.Appearance.backend_default_language'),
                'data_modifier'   => static function ($data) {
                    $lang_code = Registry::get('settings.Appearance.backend_default_language');
                    $company_id = $data['company_id'];
                    $old_plan_id = $data['old_plan_id'];
                    $new_account = !empty($data['new_account']) ? $data['new_account'] : false;

                    /** @var Company $company */
                    $company = Company::model(['lang_code' => $lang_code])->find($company_id);
                    $current_plan = VendorPlan::model(['lang_code' => $lang_code])->find($old_plan_id);

                    return [
                        'company_id'  => $company_id,
                        'vendor'      => $company,
                        'plan'        => $company->plan,
                        'old_plan'    => $current_plan,
                        'vendor_name' => $company->company,
                        'new_account' => $new_account,
                    ];
                },
            ]),
        ],
        UserTypes::VENDOR => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'default_company_orders_department',
                'to'              => 'company_support_department',
                'template_code'   => 'vendor_plans_plan_changed',
                'legacy_template' => 'addons/vendor_plans/companies/plan_changed.tpl',
                'company_id'      => 0,
                'to_company_id'   => DataValue::create('company_id'),
                'language_code'   => DataValue::create('lang_code'),
                'storefront_id'   => DataValue::create('storefront_id'),
                'data_modifier'   => static function ($data) {
                    $lang_code = $data['lang_code'];
                    $company_id = $data['company_id'];
                    $old_plan_id = $data['old_plan_id'];
                    $new_account = !empty($data['new_account']) ? $data['new_account'] : false;

                    /** @var Company $company */
                    $company = Company::model(['lang_code' => $lang_code])->find($company_id);
                    $company_info = $company->getPlacementInfo();
                    $current_plan = VendorPlan::model(['lang_code' => $lang_code])->find($old_plan_id);

                    return [
                        'company_id'    => $company_id,
                        'vendor'        => $company,
                        'plan'          => $company->plan,
                        'old_plan'      => $current_plan,
                        'vendor_name'   => $company->company,
                        'lang_code'     => $company->lang_code,
                        'storefront_id' => $company_info['registered_from_storefront_id'],
                        'new_account'   => $new_account,
                    ];
                },
            ]),
        ],
    ],
];

$schema['vendor_plans.plan_payment'] = [
    'group'     => 'vendor_plans',
    'name'      => [
        'template' => 'vendor_plans.plan_payment',
        'params'   => [],
    ],
    'receivers' => [
        UserTypes::VENDOR => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'default_company_support_department',
                'to'              => 'company_support_department',
                'template_code'   => 'vendor_plans_payment',
                'legacy_template' => 'addons/vendor_plans/companies/payment.tpl',
                'company_id'      => 0,
                'to_company_id'   => DataValue::create('company_id'),
                'language_code'   => DataValue::create('lang_code'),
                'storefront_id'   => DataValue::create('storefront_id'),
                'data_modifier'   => static function ($data) {
                    $lang_code = $data['lang_code'];
                    $company_id = $data['company_id'];

                    /** @var Company $company */
                    $company = Company::model(['lang_code' => $lang_code])->find($company_id);
                    $company_info = $company->getPlacementInfo();

                    return [
                        'company_id'    => $company_id,
                        'plan'          => $company->plan,
                        'url'           => fn_url('companies.balance', 'V', 'http'),
                        'lang_code'     => $company->lang_code,
                        'storefront_id' => $company_info['registered_from_storefront_id'],
                    ];
                },
            ]),
        ],
    ],
];

return $schema;
