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
 * Describes the behavior of controllers depending on whether secure connection is enabled or not.
 *
 * Syntax:
 * 'controller' => [
 *      'secure_mode' => 'active'/'passive'
 * ]
 *
 * secure_mode - value of the "Enable secure connection for the storefront" setting. Available values: none, full.
 * active - the controller can be processed only via HTTPS.
 * passive -  the controller can be processed both via HTTP and HTTPS.
 */

return [
    'payment_notification' => [
        'none' => 'passive',
    ],
    'image' => [
        'none' => 'passive',
    ],
    'robots' => [
        'none' => 'passive',
        'full' => 'passive'
    ],
];
