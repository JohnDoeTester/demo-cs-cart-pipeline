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

if ($mode === 'checkout') {
    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    /** @var array $payment_method */
    $payment_method = $view->getTemplateVars('payment_method');

    /** @var array $payment_info */
    $payment_info = $view->getTemplateVars('payment_info');

    /** @var array $cart */
    $cart = $view->getTemplateVars('cart');

    if (
        isset($payment_method['processor_params']['is_paypal_commerce_platform'])
        && YesNo::toBool($payment_method['processor_params']['is_paypal_commerce_platform'])
    ) {
        $processor_params = $payment_method['processor_params'];

        if (!isset($cart['companies'])) {
            $cart['companies'] = fn_get_products_companies($cart['products']);
        }

        foreach ($cart['companies'] as $company_id) {
            $processor_params['merchant_ids'][] = PaypalCommercePlatform::getChargeReceiver($company_id);
        }

        $payment_method['processor_params']
            = $payment_info['processor_params']
            = $cart['payment_method_data']['processor_params']
            = $processor_params;

        $view->assign(
            [
                'cart'           => $cart,
                'payment_info'   => $payment_info,
                'payment_method' => $payment_method,
            ]
        );
    }

    return [CONTROLLER_STATUS_OK];
}
