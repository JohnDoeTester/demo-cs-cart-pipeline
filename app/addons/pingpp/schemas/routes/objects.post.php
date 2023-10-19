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

use Tygh\Payments\Addons\Pingpp\Pingpp;

$schema['/pingpp_notify/[i:order_id]/[**:channel]'] = array(
    'dispatch' => 'payment_notification.notify',
    'payment'  => Pingpp::getPaymentName(),
);

$schema['/pingpp_cancel/[i:order_id]/[**:channel]'] = array(
    'dispatch' => 'payment_notification.cancel',
    'payment'  => Pingpp::getPaymentName(),
);

$schema['/pingpp_fail/[i:order_id]/[**:channel]'] = array(
    'dispatch' => 'payment_notification.fail',
    'payment'  => Pingpp::getPaymentName(),
);

$schema['/pingpp_continue/[i:order_id]/[**:channel]'] = array(
    'dispatch' => 'payment_notification.continue',
    'payment'  => Pingpp::getPaymentName(),
);

return $schema;