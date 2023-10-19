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
use Tygh\Enum\OutOfStockActions;
use Tygh\Enum\ProductTracking;
use Tygh\Tygh;

defined('BOOTSTRAP') or die('Access denied');

if ($mode === 'view' || $mode === 'quick_view') {
    $product_id = $_REQUEST['product_id'];
    $user_data = empty(Tygh::$app['session']['cart']['user_data'])
        ? []
        : Tygh::$app['session']['cart']['user_data'];

    /** @var \Tygh\SmartyEngine\Core $view */
    $view = Tygh::$app['view'];

    /** @var array $product */
    $product = $view->getTemplateVars('product');
    if (!$product
        || !$product['price']
        || (!$product['amount']
            && $product['tracking'] !== ProductTracking::DO_NOT_TRACK
            && $product['out_of_stock_actions'] !== OutOfStockActions::BUY_IN_ADVANCE
        )
    ) {
        return [CONTROLLER_STATUS_OK];
    }

    $payment_buttons = ServiceProvider::getPaymentButtons($product['company_id']);

    if ($payment_buttons) {
        /** @var \Tygh\Addons\Stripe\PaymentButton\DataLoader $loader */
        $loader = Tygh::$app['addons.stripe.payment_button.data_loader'];
        $product_options = isset($product['selected_options'])
            ? $product['selected_options']
            : null;
        $payment_buttons = $loader->loadPaymentRequestData($payment_buttons, $product_id, $product_options, $user_data, false);
    }

    $view->assign('stripe_payment_buttons', $payment_buttons);
}

return [CONTROLLER_STATUS_OK];
