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

/** @var string $mode */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    return [CONTROLLER_STATUS_OK];
}

if ($mode === 'details') {
    /** @var array $order_info */
    $order_info = Tygh::$app['view']->getTemplateVars('order_info');

    Tygh::$app['view']->assign(
        'is_show_transfer_funds_button',
        $order_info && fn_stripe_connect_is_allowed_transfer_funds_by_order_info($order_info)
    );

    $is_stripe_connect_payment = isset($order_info['payment_method']['processor_params']['is_stripe_connect']);
    Tygh::$app['view']->assign('is_stripe_connect_payment', $is_stripe_connect_payment);
}
