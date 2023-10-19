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

use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

if ($mode === 'details') {
    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    /** @var array $payment_method */
    $payment_method = $view->getTemplateVars('payment_method') ?: [];

    /** @var array $order_info */
    $order_info = $view->getTemplateVars('order_info');

    if (!empty($payment_method['processor_params']['is_stripe'])) {
        /** @var \Tygh\Addons\Stripe\PriceFormatter $price_formatter */
        $price_formatter = Tygh::$app['addons.stripe.price_formatter'];
        $total = $price_formatter->asCents($order_info['total'],
            $payment_method['processor_params']['currency']);

        $view->assign('stripe_cart_total', $total);
    }
}

return [CONTROLLER_STATUS_OK];
