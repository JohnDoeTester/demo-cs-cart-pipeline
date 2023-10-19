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

use Tygh\Enum\ReceiverSearchMethods;
use Tygh\Enum\UserTypes;

defined('BOOTSTRAP') or die('Access denied');

/**
 * This schema describes the availability and appearance of the notification receivers editor on the Administration > Notifications page.
 * Event groups that are not present in this schema will use the default specification (`__default`).
 *
 * The syntax of the schema is the following:
 * [
 *    (stirng) {GroupId} => [
 *        (string) {ReceiverType} => [
 *            'is_configurable' => bool {IsReceiverConfigurable},
 *            'methods' => [
 *                (string) {ReceiverSearchMethod} => bool {IsReceiverSeachMethodAvailable},
 *            ]
 *        ]
 *    ]
 * ]
 *
 * - {GroupId} - event group identifier
 * - {ReceiverType} — receiver type identifier (@see \Tygh\Enum\UserTypes)
 * - {IsReceiverConfigurable} — whether notification receivers editor is available at all for the specified event group and receiver type
 * - {ReceiverSearchMethod} — receiver search method (@see \Tygh\Enum\ReceiverSearchMethods)
 * - {IsReceiverSeachMethodAvailable} - whether specified receiver search method is available in the receivers editor
 */
$schema = [
    '__default' => [
        UserTypes::ADMIN => [
            'is_configurable' => true,
            'methods'         => [
                ReceiverSearchMethods::USERGROUP_ID => true,
                ReceiverSearchMethods::USER_ID      => true,
                ReceiverSearchMethods::EMAIL        => true,
            ],
        ],
    ],

    'profile' => [],
];

return $schema;
