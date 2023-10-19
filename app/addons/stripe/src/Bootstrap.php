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

namespace Tygh\Addons\Stripe;

use Tygh\Core\ApplicationInterface;
use Tygh\Core\BootstrapInterface;
use Tygh\Core\HookHandlerProviderInterface;

class Bootstrap implements BootstrapInterface, HookHandlerProviderInterface
{
    public function boot(ApplicationInterface $app)
    {
        $app->register(new ServiceProvider());
    }

    /**
     * @return array
     */
    public function getHookHandlerMap()
    {
        return [
            'dispatch_assign_template'        => [
                'addons.stripe.hook_handlers.dispatch',
                'onDispatchAssignTemplate',
            ],
            'after_options_calculation'       => [
                'addons.stripe.hook_handlers.products',
                'onOptionsChange',
            ],
            'checkout_place_orders_pre_route' => [
                'addons.stripe.hook_handlers.checkout',
                'onPlaceOrderPreRoute',
            ],
            'update_payment_post'             => [
                'addons.stripe.hook_handlers.payments',
                'onUpdatePaymentPost',
            ],
            'delete_payment_pre'              => [
                'addons.stripe.hook_handlers.payments',
                'onDeletePaymentPre',
            ],
            'delete_payment_post'             => [
                'addons.stripe.hook_handlers.payments',
                'onDeletePaymentPost',
            ],
            'order_placement_routines' => [
                'addons.stripe.hook_handlers.checkout',
                'onOrderPlacementRoutines',
            ],
        ];
    }
}
