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

$schema = [
    'profiles' => [
        'update' => [
            'request_method' => 'POST',
            'verification_scenario' => 'register',
            'save_post_data' => [
                'user_data',
            ],
            'rewrite_controller_status' => [
                CONTROLLER_STATUS_REDIRECT,
                'profiles.add',
            ],
        ],
    ],

    'orders' => [
        'track_request' => [
            'request_method' => 'POST',
            'verification_scenario' => 'track_orders',
            'terminate_process' => true,
        ],
    ],

    'auth' => [
        'login' => [
            'request_method' => 'POST',
            'verification_scenario' => 'login',
            'save_post_data' => [
                'user_login',
            ],
            'rewrite_controller_status' => [
                CONTROLLER_STATUS_REDIRECT,
            ],
        ],
    ],

    'checkout' => [
        'add_profile' => [
            'request_method'            => 'POST',
            'verification_scenario'     => 'register',
            'save_post_data'            => [
                'user_data',
            ],
            'rewrite_controller_status' => [
                CONTROLLER_STATUS_REDIRECT,
                'checkout.checkout?login_type=register',
            ],
        ],
        'place_order' => [
            'request_method'            => 'POST',
            'verification_scenario'     => 'checkout',
            'save_post_data'            => [
                'user_data',
            ],
            'rewrite_controller_status' => [
                CONTROLLER_STATUS_REDIRECT,
                'checkout.checkout?login_type=guest',
            ],
        ]
    ],
];

return $schema;
