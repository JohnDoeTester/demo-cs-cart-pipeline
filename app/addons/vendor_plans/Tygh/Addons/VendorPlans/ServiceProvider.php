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

namespace Tygh\Addons\VendorPlans;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Application;
use Tygh\Registry;
use Tygh\Tygh;

class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $app)
    {
        $app['addons.vendor_plans.price_formatter'] = function (Application $app) {
            $currency = Registry::get('currencies.' . CART_PRIMARY_CURRENCY);

            return new PriceFormatter((int) $currency['decimals']);
        };
    }

    /**
     * @return \Tygh\Addons\VendorPlans\PriceFormatter
     */
    public static function getPriceFormatter()
    {
        return Tygh::$app['addons.vendor_plans.price_formatter'];
    }
}
