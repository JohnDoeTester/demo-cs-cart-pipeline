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

namespace Tygh\Addons\PaypalCheckout;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Registry;
use Tygh\Tygh;

/**
 * Class OAuthHelperProvider
 *
 * @package Tygh\Addons\PaypalCheckout\Providers
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @return \Tygh\Addons\PaypalCheckout\ProcessorFactory
     */
    public static function getProcessorFactory()
    {
        return Tygh::$app['addons.paypal_checkout.processor.factory'];
    }

    /**
     * @inheritDoc
     *
     * @return void
     */
    public function register(Container $app)
    {
        $app['addons.paypal_checkout.processor.factory'] = static function (Container $app) {
            return new ProcessorFactory(
                $app['db'],
                fn_get_schema('paypal_checkout', 'status_conversion'),
                Registry::get('settings.Checkout.tax_calculation')
            );
        };
    }
}
