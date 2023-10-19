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

use Tygh\Addons\Stripe\ServiceProvider;
use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

if ($mode === 'checkout') {
    $cart = Tygh::$app['session']['cart'];

    if (!empty($cart['payment_method_data']['processor_params']['is_stripe'])) {
        /** @var \Tygh\Addons\Stripe\PriceFormatter $price_formatter */
        $price_formatter = Tygh::$app['addons.stripe.price_formatter'];
        $total = $price_formatter->asCents($cart['total'],
            $cart['payment_method_data']['processor_params']['currency']);

        /** @var \Tygh\SmartyEngine\Core $view */
        $view = Tygh::$app['view'];

        $view->assign('stripe_cart_total', $total);
    }
}

if (($mode === 'customer_info' || $mode === 'update_shipping') && $action === 'instant_payment') {

    $product = reset($_REQUEST['products']);

    $product_id = $product['product_id'];
    $product_options = empty($product['product_options'])
        ? []
        : $product['product_options'];
    $user_data = empty($_REQUEST['user_data'])
        ? Tygh::$app['session']['cart']['user_data']
        : $_REQUEST['user_data'];
    $shipping_id = empty($_REQUEST['shipping_ids'])
        ? null
        : reset($_REQUEST['shipping_ids']);

    $payment_buttons = ServiceProvider::getPaymentButtons();

    if ($payment_buttons) {
        /** @var \Tygh\Addons\Stripe\PaymentButton\DataLoader $loader */
        $loader = Tygh::$app['addons.stripe.payment_button.data_loader'];
        $payment_buttons = $loader->loadPaymentRequestData($payment_buttons, $product_id, $product_options, $user_data, $shipping_id);
    }

    /** @var \Tygh\Ajax $ajax */
    $ajax = Tygh::$app['ajax'];
    $ajax->assign('stripe_payment_buttons', $payment_buttons);
    exit;
}

return [CONTROLLER_STATUS_OK];
