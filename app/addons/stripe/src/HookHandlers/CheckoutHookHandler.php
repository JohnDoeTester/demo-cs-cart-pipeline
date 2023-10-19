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

use Tygh\Application;
use Tygh\Enum\Addons\Stripe\PaymentTypes;

class CheckoutHookHandler
{
    /**
     * @var \Tygh\Application
     */
    protected $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * The "checkout_place_orders_pre_route" hook handler.
     *
     * Actions performed:
     *  - Adds information about placed order into ajax response.
     *
     * @see \fn_checkout_place_order()
     */
    public function onPlaceOrderPreRoute($cart, $auth, $params)
    {
        if (defined('AJAX_REQUEST') && !empty($cart['processed_order_id'])) {
            $order_id = min($cart['processed_order_id']);
            /** @var \Tygh\Ajax $ajax */
            $ajax = $this->application['ajax'];
            $ajax->assign('order_id', $order_id);
        }
    }

    /**
     * The "order_placement_routines" hook handler.
     *
     * Actions performed:
     *  - Removes unnecessary notification in case of card payment.
     *
     * @param int                                                                        $order_id                Order ID
     * @param array<array-key, string>                                                   $force_notification      List of force notification
     * @param array<array-key, string|array<array-key, string|array<array-key, string>>> $order_info              Order data
     * @param bool                                                                       $_error                  Error support
     * @param string                                                                     $redirect_url            Redirect URL
     * @param bool                                                                       $allow_external_redirect Allows external redirect
     *
     * @return void
     *
     * @see \fn_order_placement_routines()
     */
    public function onOrderPlacementRoutines($order_id, array $force_notification, array $order_info, $_error, $redirect_url, $allow_external_redirect)
    {
        if (
            empty($order_info['payment_method']['processor'])
            || $order_info['payment_method']['processor'] !== 'Stripe'
            || empty($order_info['payment_method']['processor_params']['payment_type'])
            || PaymentTypes::isStripeCheckoutEnabled($order_info['payment_method']['processor_params']['payment_type'])
            || !fn_notification_exists('extra', 'transaction_cancelled')
        ) {
            return;
        }

        fn_delete_notification('transaction_cancelled');
    }
}
