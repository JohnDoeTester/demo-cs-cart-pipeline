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

namespace Tygh\Addons\StripeConnect;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Addons\StripeConnect\Webhook\Handlers\PaymentIntentSucceeded;
use Tygh\Enum\YesNo;
use Tygh\Addons\StripeConnect\Payments\StripeConnect;
use Tygh\Addons\StripeConnect\Webhook\Handlers\AccountApplicationDeauthorized;
use Tygh\Registry;
use Tygh\Tygh;

class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $app)
    {
        $app['addons.stripe_connect.oauth_helper'] = function(Container $app) {
            return new OAuthHelper(
                StripeConnect::getProcessorParameters(),
                fn_url('companies.stripe_connect_auth')
            );
        };

        $app['addons.stripe_connect.account_helper'] = static function (Container $app) {
            return new AccountHelper(
                StripeConnect::getProcessorParameters()
            );
        };

        $app['addons.stripe_connect.price_formatter'] = function (Container $app) {
            return new PriceFormatter($app['formatter']);
        };

        $app['addons.stripe_connect.settings'] = function (Container $app) {
            $settings = array_merge(
                [
                    'collect_payouts' => false,
                ],
                Registry::ifGet('addons.stripe_connect', [])
            );

            $settings['collect_payouts'] = YesNo::toBool($settings['collect_payouts']);

            return $settings;
        };

        // Webhook handlers
        $app['addons.stripe_connect.webhook_handler.account.application.deauthorized'] = static function (Container $app) {
            return new AccountApplicationDeauthorized();
        };

        $app['addons.stripe_connect.webhook_handler.payment_intent.succeeded'] = static function (Container $app) {
            return new PaymentIntentSucceeded();
        };

        $app['addons.stripe_connect.processor.factory'] = static function (Container $app) {
            return new ProcessorFactory(
                $app['db'],
                $app['addons.stripe_connect.price_formatter'],
                $app['addons.stripe_connect.settings']
            );
        };
    }

    /**
     * @return \Tygh\Addons\StripeConnect\ProcessorFactory
     */
    public static function getProcessorFactory()
    {
        return Tygh::$app['addons.stripe_connect.processor.factory'];
    }

    /**
     * @return \Tygh\Addons\StripeConnect\AccountHelper
     */
    public static function getAccountHelper()
    {
        return Tygh::$app['addons.stripe_connect.account_helper'];
    }

    /**
     * @return \Tygh\Addons\StripeConnect\OAuthHelper
     */
    public static function getOAuthHelper()
    {
        return Tygh::$app['addons.stripe_connect.oauth_helper'];
    }
}
