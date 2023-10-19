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

namespace Tygh\Addons\OrderFulfillment;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Addons\OrderFulfillment\HookHandlers\CartHookHandler;
use Tygh\Addons\OrderFulfillment\HookHandlers\CheckoutHookHandler;
use Tygh\Addons\OrderFulfillment\HookHandlers\CompaniesHookHandler;
use Tygh\Addons\OrderFulfillment\HookHandlers\GeoMapsHookHandler;
use Tygh\Addons\OrderFulfillment\HookHandlers\OrdersHookHandler;
use Tygh\Addons\OrderFulfillment\HookHandlers\PromotionsHookHandler;
use Tygh\Addons\OrderFulfillment\HookHandlers\ShippingsHookHandler;
use Tygh\Addons\OrderFulfillment\HookHandlers\StoreLocatorHookHandler;
use Tygh\Addons\OrderFulfillment\HookHandlers\VendorPlansHookHandler;
use Tygh\Addons\OrderFulfillment\HookHandlers\OrderManagementHookHandler;
use Tygh\Application;

class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     *
     * @return void
     */
    public function register(Container $app)
    {
        $app['addons.order_fulfillment.hook_handlers.companies'] = static function (Application $app) {
            return new CompaniesHookHandler();
        };

        $app['addons.order_fulfillment.hook_handlers.orders'] = static function (Application $app) {
            return new OrdersHookHandler();
        };

        $app['addons.order_fulfillment.hook_handlers.shippings'] = static function (Application $app) {
            return new ShippingsHookHandler();
        };

        $app['addons.order_fulfillment.hook_handlers.vendor_plans'] = static function (Application $app) {
            return new VendorPlansHookHandler();
        };

        $app['addons.order_fulfillment.hook_handlers.checkout'] = static function (Application $app) {
            return new CheckoutHookHandler();
        };

        $app['addons.order_fulfillment.hook_handlers.promotions'] = static function (Application $app) {
            return new PromotionsHookHandler();
        };

        $app['addons.order_fulfillment.hook_handlers.store_locator'] = static function (Application $app) {
            return new StoreLocatorHookHandler();
        };

        $app['addons.order_fulfillment.hook_handlers.cart'] = static function () {
            return new CartHookHandler();
        };

        $app['addons.order_fulfillment.hook_handlers.geo_maps'] = static function () {
            return new GeoMapsHookHandler();
        };

        $app['addons.order_fulfillment.hook_handlers.order_management'] = static function (Application $app) {
            return new OrderManagementHookHandler();
        };
    }
}
