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

use Tygh\Addons\PaypalCommercePlatform\Payments\PaypalCommercePlatform;
use Tygh\Enum\YesNo;

if ($mode === 'details') {
    $view = Tygh::$app['view'];

    $payment_method = $view->getTemplateVars('payment_method');

    $order_info = $view->getTemplateVars('order_info');
    $payment_info = $order_info['payment_info'];

    if (
        isset($payment_method['processor_params']['is_paypal_commerce_platform'])
        && YesNo::toBool($payment_method['processor_params']['is_paypal_commerce_platform'])
    ) {
        $processor_params = $payment_method['processor_params'];

        if (!isset($order_info['companies'])) {
            $order_info['companies'] = fn_get_products_companies($order_info['products']);
        }

        foreach ($order_info['companies'] as $company_id) {
            $processor_params['merchant_ids'][] = PaypalCommercePlatform::getChargeReceiver($company_id);
        }

        $payment_method['processor_params']
            = $payment_info['processor_params']
            = $processor_params;

        $view->assign(
            [
                'order_info'     => $order_info,
                'payment_method' => $payment_method,
            ]
        );
    }
}

return [CONTROLLER_STATUS_OK];
