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
use Tygh\Enum\VendorStatuses;
use Tygh\Enum\SiteArea;
use Tygh\Notifications\DataProviders\ProfileDataProvider;
use Tygh\Notifications\DataValue;
use Tygh\Notifications\Transports\Internal\InternalMessageSchema;
use Tygh\Notifications\Transports\Internal\InternalTransport;
use Tygh\Notifications\Transports\Mail\MailMessageSchema;
use Tygh\Notifications\Transports\Mail\MailTransport;
use Tygh\NotificationsCenter\NotificationsCenter;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

require_once __DIR__ . '/events.functions.php';

/**
 * @var array<string, array> $schema
 */

$schema['profile.activated.v'] = [
    'group'     => 'profile',
    'name'      => [
        'template' => 'event.profile.activated.name',
        'params'   => [],
    ],
    'receivers' => [
        UserTypes::VENDOR => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'company_users_department',
                'to'              => DataValue::create('user_data.email'),
                'template_code'   => 'profile_activated',
                'legacy_template' => 'profiles/profile_activated.tpl',
                'company_id'      => 0,
                'to_company_id'   => DataValue::create('user_data.company_id'),
                'language_code'   => DataValue::create('user_data.lang_code', CART_LANGUAGE)
            ]),
        ],
    ],
];

$schema['profile.deactivated.v'] = [
    'group'     => 'profile',
    'name'      => [
        'template' => 'event.profile.deactivated.name',
        'params'   => [],
    ],
    'receivers' => [
        UserTypes::VENDOR => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'company_users_department',
                'to'              => DataValue::create('user_data.email'),
                'template_code'   => 'profile_deactivated',
                'legacy_template' => 'profiles/profile_deactivated.tpl',
                'company_id'      => 0,
                'to_company_id'   => DataValue::create('user_data.company_id'),
                'language_code'   => DataValue::create('user_data.lang_code', CART_LANGUAGE)
            ]),
        ],
    ],
];


$schema['profile.updated.v'] = [
    'group'     => 'profile',
    'name'      => [
        'template' => 'event.profile.updated.name',
        'params'   => [],
    ],
    'data_provider' => [ProfileDataProvider::class, 'factory'],
    'receivers' => [
        UserTypes::VENDOR => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'default_company_users_department',
                'to'              => DataValue::create('user_data.email'),
                'template_code'   => 'update_profile',
                'legacy_template' => 'profiles/update_profile.tpl',
                'company_id'      => 0,
                'to_company_id'   => DataValue::create('user_data.company_id'),
                'storefront_id'   => DataValue::create('storefront_id'),
                'language_code'   => DataValue::create('user_data.lang_code', CART_LANGUAGE),
            ]),
        ],
    ],
];

$schema['profile.created.v'] = [
    'group'     => 'profile',
    'name'      => [
        'template' => 'event.profile.created.name',
        'params'   => [],
    ],
    'data_provider' => [ProfileDataProvider::class, 'factory'],
    'receivers' => [
        UserTypes::VENDOR => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'company_users_department',
                'to'              => DataValue::create('user_data.email'),
                'template_code'   => 'create_profile',
                'legacy_template' => 'profiles/create_profile.tpl',
                'company_id'      => 0,
                'to_company_id'   => DataValue::create('user_data.company_id'),
                'language_code'   => DataValue::create('user_data.lang_code', CART_LANGUAGE)
            ]),
        ],
    ],
];

$schema['profile.usergroup_activation.v'] = [
    'group'     => 'profile',
    'name'      => [
        'template' => 'event.profile.usergroup_activation.name',
        'params'   => [],
    ],
    'receivers' => [
        UserTypes::VENDOR => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'company_users_department',
                'to'              => DataValue::create('user_data.email'),
                'template_code'   => 'usergroup_activation',
                'legacy_template' => 'profiles/usergroup_activation.tpl',
                'company_id'      => 0,
                'to_company_id'   => DataValue::create('user_data.company_id'),
                'language_code'   => DataValue::create('user_data.lang_code', CART_LANGUAGE),
                'data_modifier'   => 'fn_event_profile_usergroup_state_updated_data_modifer'
            ]),
        ],
    ],
];
$schema['profile.usergroup_disactivation.v'] = [
    'group'     => 'profile',
    'name'      => [
        'template' => 'event.profile.usergroup_disactivation.name',
        'params'   => [],
    ],
    'receivers' => [
        UserTypes::VENDOR => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'company_users_department',
                'to'              => DataValue::create('user_data.email'),
                'template_code'   => 'usergroup_disactivation',
                'legacy_template' => 'profiles/usergroup_disactivation.tpl',
                'company_id'      => 0,
                'to_company_id'   => DataValue::create('user_data.company_id'),
                'language_code'   => DataValue::create('user_data.lang_code', CART_LANGUAGE),
                'data_modifier'   => 'fn_event_profile_usergroup_state_updated_data_modifer'
            ]),
        ],
    ],
];

$schema['profile.password_recover.v'] = [
    'group'     => 'profile',
    'name'      => [
        'template' => 'event.profile.password_recovery.name',
        'params'   => [],
    ],
    'receivers' => [
        UserTypes::VENDOR => [
            MailTransport::getId() => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'default_company_users_department',
                'to'              => DataValue::create('user_data.email'),
                'template_code'   => 'recover_password',
                'legacy_template' => 'profiles/recover_password.tpl',
                'company_id'      => 0,
                'to_company_id'   => DataValue::create('user_data.company_id'),
                'language_code'   => DataValue::create('user_data.lang_code', CART_LANGUAGE),
                'storefront_id'   => DataValue::create('storefront_id'),
                'data_modifier'   => static function (array $data) {
                    return array_merge($data, [
                        'url' => fn_url('auth.recover_password?ekey=' . $data['ekey'], 'V'),
                    ]);
                }
            ]),
        ],
    ],
];

$schema['vendor_status_changed_active'] = [
    'group' => 'vendors',
    'name' => [
        'template' => 'event.vendor_status_changed.name',
        'params' => [
            '[status]' => __('active'),
        ],
    ],
    'receivers' => [
        UserTypes::VENDOR => [
            MailTransport::getId()     => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'default_company_support_department',
                'to'              => DataValue::create('user_data.email'),
                'company_id'      => 0,
                'to_company_id'   => DataValue::create('to_company_id'),
                'template_code'   => 'company_status_notification',
                'legacy_template' => 'companies/status_notification.tpl',
                'language_code'   => DataValue::create('user_data.lang_code', CART_LANGUAGE),
                'storefront_id'   => DataValue::create('company.registered_from_storefront_id'),
                'data_modifier'   => static function (array $data) {
                    if (empty($data['status']) || empty($data['user_data']['lang_code'])) {
                        return $data;
                    }

                    $data['status'] = __($data['status'], [], $data['user_data']['lang_code']);
                    return $data;
                }
            ]),
            InternalTransport::getId() => InternalMessageSchema::create([
                'tag'                       => 'vendor_status',
                'area'                      => SiteArea::VENDOR_PANEL,
                'section'                   => NotificationsCenter::SECTION_ADMINISTRATION,
                'to_company_id'             => DataValue::create('to_company_id'),
                'language_code'             => DataValue::create('company.lang_code', CART_LANGUAGE),
                'severity'                  => NotificationSeverity::NOTICE,
                'title'                     => [
                    'template' => 'event.vendor_status_changed.title',
                    'params' => [
                        '[status]' => DataValue::create('status'),
                    ]
                ],
                'message'                   => [
                    'template' => 'event.vendor_status_changed.active.message',
                ],
                'data_modifier'   => static function (array $data) {
                    if (empty($data['status']) || empty($data['company']['lang_code'])) {
                        return $data;
                    }

                    $data['status'] = __($data['status'], [], $data['company']['lang_code']);
                    return $data;
                }
            ]),
        ],
    ]
];

$schema['vendor_status_changed_pending'] = [
    'group' => 'vendors',
    'name' => [
        'template' => 'event.vendor_status_changed.name',
        'params' => [
            '[status]' => __('pending'),
        ],
    ],
    'receivers' => [
        UserTypes::VENDOR => [
            MailTransport::getId()     => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'default_company_support_department',
                'to'              => DataValue::create('user_data.email'),
                'company_id'      => 0,
                'to_company_id'   => DataValue::create('to_company_id'),
                'template_code'   => 'company_status_notification',
                'legacy_template' => 'companies/status_notification.tpl',
                'language_code'   => DataValue::create('user_data.lang_code', CART_LANGUAGE),
                'storefront_id'   => DataValue::create('company.registered_from_storefront_id'),
                'data_modifier'   => static function (array $data) {
                    if (empty($data['status']) || empty($data['user_data']['lang_code'])) {
                        return $data;
                    }

                    $data['status'] = __($data['status'], [], $data['user_data']['lang_code']);
                    return $data;
                }
            ]),
            InternalTransport::getId() => InternalMessageSchema::create([
                'tag'                       => 'vendor_status',
                'area'                      => SiteArea::VENDOR_PANEL,
                'section'                   => NotificationsCenter::SECTION_ADMINISTRATION,
                'to_company_id'             => DataValue::create('to_company_id'),
                'language_code'             => DataValue::create('company.lang_code', CART_LANGUAGE),
                'severity'                  => NotificationSeverity::NOTICE,
                'title'                     => [
                    'template' => 'event.vendor_status_changed.title',
                    'params' => [
                        '[status]' => DataValue::create('status'),
                    ]
                ],
                'message'                   => [
                    'template' => 'event.vendor_status_changed.pending.message',
                ],
                'data_modifier'   => static function (array $data) {
                    if (empty($data['status']) || empty($data['company']['lang_code'])) {
                        return $data;
                    }

                    $data['status'] = __($data['status'], [], $data['company']['lang_code']);
                    return $data;
                }
            ]),
        ],
    ]
];

$schema['vendor_status_changed_disabled'] = [
    'group' => 'vendors',
    'name' => [
        'template' => 'event.vendor_status_changed.name',
        'params' => [
            '[status]' => __('disabled'),
        ],
    ],
    'receivers' => [
        UserTypes::VENDOR => [
            MailTransport::getId()     => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'default_company_support_department',
                'to'              => DataValue::create('user_data.email'),
                'company_id'      => 0,
                'to_company_id'   => DataValue::create('company.company_id'),
                'template_code'   => 'company_status_notification',
                'legacy_template' => 'companies/status_notification.tpl',
                'language_code'   => DataValue::create('user_data.lang_code', CART_LANGUAGE),
                'storefront_id'   => DataValue::create('company.registered_from_storefront_id'),
                'data_modifier'   => static function (array $data) {
                    if (empty($data['user_data']['email']) && !empty($data['company']['company_support_department'])) {
                        $data['user_data']['email'] = $data['company']['company_support_department'];
                    }

                    if (!empty($data['status']) && !empty($data['user_data']['lang_code'])) {
                        $data['status'] = __($data['status'], [], $data['user_data']['lang_code']);
                    }

                    return $data;
                }
            ]),
        ],
    ],
];

$schema['vendor_status_changed_from_suspended'] = [
    'group' => 'vendors',
    'name' => [
        'template' => 'event.vendor_status_changed_from.name',
        'params' => [
            '[status]' => __('suspended'),
        ],
    ],
    'receivers' => [
        UserTypes::VENDOR => [
            MailTransport::getId()     => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'default_company_support_department',
                'to'              => DataValue::create('user_data.email'),
                'company_id'      => 0,
                'to_company_id'   => DataValue::create('company.company_id'),
                'template_code'   => 'company_status_changed_from_suspended_notification',
                'language_code'   => DataValue::create('user_data.lang_code', CART_LANGUAGE),
                'storefront_id'   => DataValue::create('company.registered_from_storefront_id'),
                'data_modifier'   => static function (array $data) {
                    if (empty($data['status']) || empty($data['user_data']['lang_code'])) {
                        return $data;
                    }

                    $data['status'] = __($data['status'], [], $data['user_data']['lang_code']);
                    return $data;
                }
            ]),
        ],
    ],
];

$schema['vendor_status_changed_suspended'] = [
    'group' => 'vendors',
    'name' => [
        'template' => 'event.vendor_status_changed.name',
        'params' => [
            '[status]' => __('suspended'),
        ],
    ],
    'receivers' => [
        UserTypes::VENDOR => [
            MailTransport::getId()     => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'default_company_support_department',
                'to'              => DataValue::create('user_data.email'),
                'company_id'      => 0,
                'to_company_id'   => DataValue::create('company.company_id'),
                'template_code'   => 'company_status_suspended_notification',
                'language_code'   => DataValue::create('user_data.lang_code', CART_LANGUAGE),
                'storefront_id'   => DataValue::create('company.registered_from_storefront_id'),
                'data_modifier'   => static function (array $data) {
                    if (empty($data['status']) || empty($data['user_data']['lang_code'])) {
                        return $data;
                    }

                    $data['status'] = __($data['status'], [], $data['user_data']['lang_code']);
                    return $data;
                }
            ]),
            InternalTransport::getId() => InternalMessageSchema::create([
                'tag'                       => 'vendor_status',
                'area'                      => SiteArea::VENDOR_PANEL,
                'section'                   => NotificationsCenter::SECTION_ADMINISTRATION,
                'to_company_id'             => DataValue::create('to_company_id'),
                'language_code'             => DataValue::create('company.lang_code', CART_LANGUAGE),
                'severity'                  => NotificationSeverity::NOTICE,
                'title'                     => [
                    'template' => 'event.vendor_status_changed.suspended.title',
                    'params' => [
                        '[status]' => DataValue::create('status'),
                    ]
                ],
                'message'                   => [
                    'template' => 'event.vendor_status_changed.suspended.message',
                ],
                'data_modifier'   => static function (array $data) {
                    if (empty($data['status']) || empty($data['company']['lang_code'])) {
                        return $data;
                    }

                    $data['status'] = __($data['status'], [], $data['company']['lang_code']);
                    return $data;
                }
            ]),
        ],
    ],
];

$schema['vendors_require_approval'] = [
    'group' => 'vendors',
    'name' => [
        'template' => 'event.vendors_require_approval.name',
        'params' => [],
    ],
    'receivers' => [
        UserTypes::ADMIN => [
            InternalTransport::getId() => InternalMessageSchema::create([
                'tag'                       => 'vendor_status',
                'area'                      => SiteArea::ADMIN_PANEL,
                'section'                   => NotificationsCenter::SECTION_ADMINISTRATION,
                'severity'                  => NotificationSeverity::WARNING,
                'to_company_id'             => 0,
                'language_code'             => Registry::get('settings.Appearance.backend_default_language'),
                'action_url'                => 'companies.manage?status[]=' . VendorStatuses::NEW_ACCOUNT . '&status[]=' . VendorStatuses::PENDING,
                'title'                     => [
                    'template' => 'event.vendors_require_approval.title',
                ],
                'message'                   => [
                    'template' => 'text_not_approved_vendors',
                    'params'   => [
                        '[link]' => fn_url('admin:companies.manage?status[]=' . VendorStatuses::NEW_ACCOUNT . '&status[]=' . VendorStatuses::PENDING, SiteArea::ADMIN_PANEL)
                    ]
                ]
            ]),
        ],
    ],
];

$schema['apply_for_vendor_notification'] = [
    'group' => 'vendors',
    'name'  => [
        'template' => 'event.apply_for_vendor_notification.name',
        'params'   => [],
    ],
    'receivers' => [
        UserTypes::ADMIN => [
            MailTransport::getId()     => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'default_company_support_department',
                'to'              => 'default_company_support_department',
                'company_id'      => 0,
                'to_company_id'   => 0,
                'template_code'   => 'apply_for_vendor_notification',
                'legacy_template' => 'companies/apply_for_vendor_notification.tpl',
                'language_code'   => Registry::get('settings.Appearance.backend_default_language'),
            ]),
        ],
        UserTypes::VENDOR => [
            MailTransport::getId()     => MailMessageSchema::create([
                'area'          => SiteArea::STOREFRONT,
                'from'          => 'default_company_support_department',
                'to'            => DataValue::create('company.email'),
                'company_id'    => 0,
                'to_company_id' => DataValue::create('company_id'),
                'template_code' => 'apply_for_vendor_notification',
                'language_code' => DataValue::create('company.lang_code', CART_LANGUAGE),
            ]),
        ],
    ]
];

$schema['new_vendor_notification'] = [
    'group' => 'vendors',
    'name'  => [
        'template' => 'event.new_vendor_notification.name',
        'params'   => [],
    ],
    'receivers' => [
        UserTypes::ADMIN => [
            MailTransport::getId()     => MailMessageSchema::create([
                'area'            => SiteArea::ADMIN_PANEL,
                'from'            => 'default_company_support_department',
                'to'              => 'default_company_support_department',
                'company_id'      => 0,
                'to_company_id'   => 0,
                'template_code'   => 'new_vendor_notification',
                'legacy_template' => 'companies/new_vendor_notification.tpl',
                'language_code'   => Registry::get('settings.Appearance.backend_default_language'),
            ]),
            InternalTransport::getId() => InternalMessageSchema::create([
                'tag'                       => 'vendor_status',
                'area'                      => SiteArea::ADMIN_PANEL,
                'section'                   => NotificationsCenter::SECTION_ADMINISTRATION,
                'severity'                  => NotificationSeverity::WARNING,
                'to_company_id'             => 0,
                'language_code'             => Registry::get('settings.Appearance.backend_default_language'),
                'action_url'                => 'companies.manage?status[]=' . VendorStatuses::NEW_ACCOUNT . '&status[]=' . VendorStatuses::ACTIVE,
                'title'                     => [
                    'template' => 'event.new_vendor_notification.title',
                ],
                'message'                   => [
                    'template' => 'text_created_new_vendors',
                    'params'   => [
                        '[link]' => fn_url(
                            'admin:companies.manage?status[]=' . VendorStatuses::NEW_ACCOUNT . '&status[]=' . VendorStatuses::ACTIVE,
                            SiteArea::ADMIN_PANEL
                        )
                    ]
                ]
            ]),
        ],
    ]
];

return $schema;
