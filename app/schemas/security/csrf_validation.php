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

defined('BOOTSTRAP') or die('Access denied');

/**
 * Describes anti-CSRF validation requirements (see ::fn_csrf_validate_request()).
 *
 * Syntax:
 * 'area' => [
 *     'validate' => true/false,            // General area validation rule
 *     'controllers' => [
 *         'validate' => true/false,        // General controller validation rule
 *         'modes' => [
 *             'mode' => [
 *                 'validate' => true/false // Specific mode validation rule
 *             ]
 *         ]
 *     ]
 * ]
 *
 * When validating a request, the rules are applied in the following order:
 * 1. Specific mode validation rule
 * 2. General controller validation rule (if the previous one is not found)
 * 3. General area validation rule (if the previous ones are not found)
 */
$schema = [
    'A' => [
        'validate'    => true,
        'controllers' => [
            'payment_notification' => [
                'validate' => false,
            ],
        ],
    ],
    'C' => [
        'validate'    => false,
        'controllers' => [
            'payment_notification' => [
                'validate' => false,
            ],
            'auth'                 => [
                'validate' => true,
            ],
            'profiles'             => [
                'validate' => true,
            ],
            'checkout'             => [
                'validate' => true,
            ],
            'orders'               => [
                'validate' => true,
            ],
        ],
    ],
];

return $schema;
