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

return [
    'auth' => [
        'allow' => true
    ],
    'image' => [
        'default_allow' => true,
        'allow'         => [
            'upload' => false
        ],
        'areas' => ['A', 'C'],
    ],
    'payment_notification' => [
        'allow' => true
    ],
    'profiles' => [
        'allow' => [
            'password_reminder' => true,
        ],
    ],
    'helpdesk_connector' => [
        'allow' => true
    ],
    'robots' => [
        'allow' => true,
        'areas' => ['C']
    ],
    'bottom_panel' => [
        'allow' => true
    ],
];
