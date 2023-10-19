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

namespace Tygh\Addons\Stripe\HookHandlers;

use Tygh\Addons\Stripe\ServiceProvider;
use Tygh\Application;

class ProductsHookHandler
{
    protected $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * The "after_options_calculation" hook handler.
     *
     * Actions performed:
     *  - Actualizes price of a product for instant payment button when chaning product options.
     *
     * @see \fn_get_data_of_changed_product()
     */
    public function onOptionsChange($mode, $data, $auth)
    {
        if (AREA !== 'C' || $mode !== 'options') {
            return;
        }

        $product = fn_get_additional_product_data($data, $auth);
        $product_id = $product['product_id'];
        $product_options = empty($product['selected_options'])
            ? []
            : $product['selected_options'];
        $user_data = empty($this->application['session']['cart']['user_data'])
            ? []
            : $this->application['session']['cart']['user_data'];

        $payment_buttons = ServiceProvider::getPaymentButtons($product['company_id']);

        if ($payment_buttons) {
            /** @var \Tygh\Addons\Stripe\PaymentButton\DataLoader $loader */
            $loader = $this->application['addons.stripe.payment_button.data_loader'];
            $payment_buttons = $loader->loadPaymentRequestData($payment_buttons, $product_id, $product_options, $user_data, false);
        }

        /** @var \Tygh\SmartyEngine\Core $view */
        $view = $this->application['view'];

        $view->assign('stripe_payment_buttons', $payment_buttons);
    }
}
