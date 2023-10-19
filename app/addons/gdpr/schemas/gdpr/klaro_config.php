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

use Tygh\Addons\Gdpr\CookiesPolicyManager;
use Tygh\Enum\SiteArea;
use Tygh\Registry;

defined('BOOTSTRAP') or die('Access denied');

/** @var array $schema */
$schema = [
    'storageMethod'    => 'cookie',
    'cookieName'       => 'klaro',
    'elementID'        => 'klaro',
    'lang'             => 'zz',
    'default'          => true,
    'noNotice'         => false,
    'acceptAll'        => true,
    'hideDeclineAll'   => true,
    'hideLearnMore'    => true,
    'mustConsent'      => false,
    'noticeAsModal'    => false,
    'disablePoweredBy' => true,
    'optOut'           => true,
    'htmlTexts'        => true,
    'translations' => [
        'zz' => [
            'acceptAll'      => 'gdpr.klaro_accept_all',
            'acceptSelected' => 'gdpr.klaro_accept_selected',
            'close'          => 'gdpr.klaro_close',
            'consentModal'   => [
                'description' => 'gdpr.klaro_consent_modal_description',
                'title'       => 'gdpr.klaro_consent_modal_title'
            ],
            'consentNotice' => [
                'changeDescription' => 'gdpr.klaro_consent_notice_change_description',
                'title'             => 'gdpr.klaro_consent_notice_title',
                'description'       => Registry::get('addons.gdpr.gdpr_cookie_consent') === CookiesPolicyManager::COOKIE_POLICY_EXPLICIT ? 'gdpr.klaro_consent_notice_description' : 'gdpr.uk_cookies_law',
                'learnMore'         => 'gdpr.klaro_consent_notice_learn_more',
                'testing'           => 'gdpr.klaro_consent_notice_testing'
            ],
            'contextualConsent' => [
                'acceptAlways' => 'gdpr.klaro_contextual_consent_accept_always',
                'acceptOnce'   => 'gdpr.klaro_contextual_consent_accept_once',
                'description'  => 'gdpr.klaro_contextual_consent_description',
            ],
            'decline'       => 'gdpr.klaro_decline',
            'ok'            => 'gdpr.klaro_ok',
            'poweredBy'     => 'gdpr.klaro_powered_by',
            'privacyPolicy' => [
                'text' => 'gdpr.klaro_privacy_policy_title',
                'name' => 'gdpr.klaro_privacy_policy_name',
            ],
            'privacyPolicyUrl' => fn_url('pages.view&page_id=' . Registry::get('addons.gdpr.privacy_policy_page'), SiteArea::STOREFRONT),
            'purposeItem' => [
                'service' => 'gdpr.klaro_service',
                'services' => 'gdpr.klaro_services'
            ],
            'purposes' => [
                'strictly_necessary' => [
                    'title'       => 'gdpr.strictly_necessary_cookies_title',
                    'description' => 'gdpr.strictly_necessary_cookies_description'
                ],
                'performance' => [
                    'title'       => 'gdpr.performance_cookies_title',
                    'description' => 'gdpr.performance_cookies_description'
                ],
                'functional' => [
                    'title'       => 'gdpr.functional_cookies_title',
                    'description' => 'gdpr.functional_cookies_description'
                ],
                'marketing' => [
                    'title'       => 'gdpr.marketing_cookies_title',
                    'description' => 'gdpr.marketing_cookies_description'
                ],
            ],
            'save' => 'save',
            'service' => [
                'disableAll' => [
                    'description' => 'gdpr.disable_all_description',
                    'title'       => 'gdpr.disable_all_title'
                ],
                'optOut' => [
                    'description' => 'gdpr.opt_out_description',
                    'title'       => 'gdpr.opt_out_title'
                ],
                'purpose'  => 'gdpr.klaro_service_purpose',
                'purposes' => 'gdpr.klaro_service_purposes',
                'required' => [
                    'description' => 'gdpr.klaro_service_required_description',
                    'title'       => 'gdpr.klaro_service_required_title'
                ]
            ],
        ],
    ],
    'services' => [
        'site_functionality' => [
            'purposes' => ['strictly_necessary'],
            'name' => 'site_functionality',
            'translations' => [
                'zz' => [
                    'title' => 'gdpr.site_functionality_cookies_title',
                    'description' => 'gdpr.site_functionality_cookies_description',
                ],
            ],
            'required' => true,
        ]
    ],
];

if (fn_get_payments(['template' => 'cc_eway.tpl'])) {
    $schema['services']['eway'] = [
        'purposes' => ['strictly_necessary'],
        'name' => 'eway',
        'translations' => [
            'zz' => [
                'title' => 'gdpr.eway_cookies_title',
                'description' => 'gdpr.eway_cookies_description',
            ],
        ],
        'required' => true,
    ];
}

return $schema;
