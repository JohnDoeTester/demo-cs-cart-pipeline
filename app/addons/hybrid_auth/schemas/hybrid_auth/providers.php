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

use Tygh\Enum\YesNo;
use Tygh\Registry;

/** @var array $schema */
$schema = [
    'openid' => [
        'provider' => 'OpenID',
        'enabled'  => false,
    ],
    'aol' => [
        'provider' => 'AOL',
        'enabled'  => false,
    ],
    'google' => [
        'provider' => 'Google',
        'keys' => [
            'id' => [
                'db_field' => 'app_id',
                'label'    => 'id',
                'required' => true
            ],
            'secret' => [
                'db_field' => 'app_secret_key',
                'label'    => 'secret_key',
                'required' => true
            ],
        ],
        'wrapper' => [
            'class' => '\Tygh\HybridProvidersGoogle',
        ],
        'params' => [
            'google_callback' => [
                'type'     => 'template',
                'template' => 'addons/hybrid_auth/components/callback_url.tpl',
            ]
        ],
        'instruction'           => 'hybrid_auth.instruction_google',
        'hauth_done_param_name' => 'hauth.done',
    ],
    'facebook' => [
        'provider' => 'Facebook',
        'keys' => [
            'id' => [
                'db_field' => 'app_id',
                'label'    => 'id',
                'required' => true
            ],
            'secret' => [
                'db_field' => 'app_secret_key',
                'label'    => 'secret_key',
                'required' => true
            ],
        ],
        'wrapper' => [
            'class' => '\Tygh\HybridProvidersFacebookNewScope',
        ],
        'params' => [
            'facebook_oauth_redirect_uris' => [
                'type'     => 'template',
                'template' => 'addons/hybrid_auth/components/callback_url.tpl',
                'label'    => __('hybrid_auth.facebook_oauth_redirect_uris'),
            ]
        ],
        'instruction' => 'hybrid_auth.instruction_facebook_login',
    ],
    'paypal' => [
        'provider' => 'Paypal',
        'keys' => [
            'id' => [
                'db_field' => 'app_id',
                'label'    => 'id',
                'required' => true
            ],
            'secret' => [
                'db_field' => 'app_secret_key',
                'label'    => 'secret_key',
                'required' => true
            ],
        ],
        'wrapper' => [
            'class' => '\Tygh\HybridProvidersPaypal',
        ],
        'params' => [
            'paypal_seamless' => [
                'type'    => 'checkbox',
                'label'   => 'paypal_seamless',
                'default' => YesNo::YES
            ],
            'paypal_sandbox' => [
                'type'  => 'checkbox',
                'label' => 'paypal_sandbox',
            ],
            'paypal_callback' => [
                'type'     => 'template',
                'template' => 'addons/hybrid_auth/components/callback_url.tpl',
            ]
        ],
        'instruction' => 'hybrid_auth.instruction_paypal_application',
    ],
    'twitter' => [
        'provider' => 'Twitter',
        'keys' => [
            'key' => [
                'db_field' => 'app_id',
                'label'    => 'id',
                'required' => true
            ],
            'secret' => [
                'db_field' => 'app_secret_key',
                'label'    => 'secret_key',
                'required' => true
            ],
        ],
        'params' => [
            'twitter_callback' => [
                'type'         => 'template',
                'template'     => 'addons/hybrid_auth/components/callback_url.tpl',
                'callback_url' => '/auth/twitter',
            ]
        ],
        'instruction' => 'hybrid_auth.instruction_twitter'
    ],
    'yahoo' => [
        'provider' => 'Yahoo',
        'keys' => [
            'id' => [
                'db_field' => 'app_id',
                'label'    => 'id',
                'required' => true
            ],
            'secret' => [
                'db_field' => 'app_secret_key',
                'label'    => 'secret_key',
                'required' => true
            ],
        ],
        'wrapper' => [
            'class' => '\Tygh\HybridProvidersYahoo',
        ],
        'params' => [
            'yahoo_callback' => [
                'type'         => 'template',
                'template'     => 'addons/hybrid_auth/components/callback_url.tpl',
                'callback_url' => '/' . Registry::get('config.customer_index'),
            ]
        ],
        'instruction' => 'hybrid_auth.instruction_yahoo'
    ],
    'live' => [
        'provider' => 'Live',
        'keys' => [
            'id' => [
                'db_field' => 'app_id',
                'label'    => 'id',
                'required' => true
            ],
            'secret' => [
                'db_field' => 'app_secret_key',
                'label'    => 'secret_key',
                'required' => true
            ]
        ],
        'params' => [
            'redirect_url' => [
                'type'     => 'template',
                'template' => 'addons/hybrid_auth/components/redirect_url.tpl',
            ],
        ],
        'instruction' => 'hybrid_auth.instruction_live'
    ],
    'linkedin' => [
        'provider' => 'LinkedIn',
        'keys' => [
            'id' => [
                'db_field' => 'app_id',
                'label'    => 'id',
                'required' => true
            ],
            'secret' => [
                'db_field' => 'app_secret_key',
                'label'    => 'secret_key',
                'required' => true
            ]
        ],
        'params' => [
            'linkedin_callback' => [
                'type'     => 'template',
                'template' => 'addons/hybrid_auth/components/callback_url.tpl',
            ],
            'version' => [
                'type'     => 'select',
                'required' => true,
                'label'    => 'hybrid_auth.linkedin_api_version',
                'tooltip'  => 'ttc_hybrid_auth.linkedin_api_version',
                'default'  => 'linkedin_api_v2',
                'options'  => [
                    'linkedin_api_v1' => 'hybrid_auth.linkedin_api_v1',
                    'linkedin_api_v2' => 'hybrid_auth.linkedin_api_v2'
                ]
            ]
        ],
        'versions' => [
            'linkedin_api_v1' => [
                'wrapper' => [
                    'path'  => 'Providers/LinkedInV1.php',
                    'class' => '\Tygh\HybridProvidersLinkedInV1'
                ]
            ],
            'linkedin_api_v2' => [
                'wrapper' => [
                    'class' => '\Tygh\HybridProvidersLinkedIn'
                ]
            ]
        ],
        'instruction' => 'hybrid_auth.instruction_linkedin'
    ],
    'foursquare' => [
        'provider' => 'Foursquare',
        'keys' => [
            'id' => [
                'db_field' => 'app_id',
                'label'    => 'id',
                'required' => true
            ],
            'secret' => [
                'db_field' => 'app_secret_key',
                'label'    => 'secret_key',
                'required' => true
            ]
        ],
        'wrapper' => [
            'class' => '\Tygh\HybridProvidersFoursquare',
        ],
        'instruction' => 'hybrid_auth.instruction_foursquare'
    ],
];

return $schema;
