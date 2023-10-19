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

defined('BOOTSTRAP') or die('Access denied!');

/** @var array $schema */
$schema['/abs/[i:order_id]'] = [
    'dispatch' => 'payment_notification.success',
    'payment'  => 'alpha_bank',
];

$schema['/abf/[i:order_id]'] = [
    'dispatch' => 'payment_notification.fail',
    'payment'  => 'alpha_bank',
];

return $schema;
