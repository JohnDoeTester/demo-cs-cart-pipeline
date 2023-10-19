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

namespace Tygh\Addons\ProductBundles;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Addons\ProductBundles\HookHandlers\ProductBundlesHookHandler;
use Tygh\Addons\ProductBundles\HookHandlers\ProductsHookHandler;
use Tygh\Addons\ProductBundles\HookHandlers\ProductTabsHookHandler;
use Tygh\Addons\ProductBundles\HookHandlers\PromotionHookHandler;
use Tygh\Addons\ProductBundles\HookHandlers\ToolsHookHandler;
use Tygh\Addons\ProductBundles\Services\ProductBundleService;
use Tygh\Tygh;

class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $app)
    {
        $app['addons.product_bundles.service'] = static function () {
            return new ProductBundleService();
        };

        $app['addons.product_bundles.hook_handlers.promotions'] = static function () {
            return new PromotionHookHandler();
        };

        $app['addons.product_bundles.hook_handlers.product_variations'] = static function () {
            return new ProductBundlesHookHandler();
        };

        $app['addons.product_bundles.hook_handlers.products'] = static function () {
            return new ProductsHookHandler();
        };

        $app['addons.product_bundles.hook_handlers.tools'] = static function () {
            return new ToolsHookHandler();
        };

        $app['addons.product_bundles.hook_handlers.direct_payments'] = static function () {
            return new ProductBundlesHookHandler();
        };

        $app['addons.product_bundles.hook_handlers.product_tabs'] = static function () {
            return new ProductTabsHookHandler();
        };
    }

    /**
     * @return ProductBundleService
     */
    public static function getService()
    {
        return Tygh::$app['addons.product_bundles.service'];
    }
}
