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

namespace Tygh\Addons\PaypalCommercePlatform;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Addons\PaypalCommercePlatform\Api\Client;
use Tygh\Addons\PaypalCommercePlatform\Payments\PaypalCommercePlatform;
use Tygh\Languages\Languages;
use Tygh\Registry;
use Tygh\Tygh;

/**
 * Class OAuthHelperProvider
 *
 * @package Tygh\Addons\PaypalCommercePlatform\Providers
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @return \Tygh\Addons\PaypalCommercePlatform\ProcessorFactory
     */
    public static function getProcessorFactory()
    {
        return Tygh::$app['addons.paypal_commerce_platform.processor.factory'];
    }

    /**
     * @return \Tygh\Addons\PaypalCommercePlatform\OAuthHelper
     */
    public static function getOauthHelper()
    {
        return Tygh::$app['addons.paypal_commerce_platform.oauth_helper'];
    }

    /**
     * @inheritDoc
     */
    public function register(Container $app)
    {
        $app['addons.paypal_commerce_platform.oauth_helper'] = static function (Container $app) {

            $processor_params = PaypalCommercePlatform::getProcessorParameters();

            $api_client = new Client(
                $processor_params['client_id'],
                $processor_params['secret'],
                $processor_params['access_token'],
                $processor_params['expiry_time'],
                $processor_params['mode'] === 'test',
                isset($processor_params['bn_code'])
                    ? $processor_params['bn_code']
                    : null
            );

            $company_id = Registry::get('runtime.company_id');
            $redirect_url = fn_url('companies.paypal_commerce_platform_auth');
            $user_id = $app['session']['auth']['user_id'];
            $locale = str_replace('_', '-', (string) Languages::getLocaleByLanguageCode(CART_LANGUAGE));
            $currency = CART_PRIMARY_CURRENCY;
            $country_products = fn_get_schema('paypal_commerce_platform', 'country_products');

            return new OAuthHelper(
                $api_client,
                $processor_params,
                $redirect_url,
                $company_id,
                $user_id,
                $locale,
                $currency,
                $country_products
            );
        };

        $app['addons.paypal_commerce_platform.processor.factory'] = static function (Container $app) {
            return new ProcessorFactory(
                $app['db'],
                static::getOauthHelper(),
                fn_get_schema('paypal_commerce_platform', 'status_conversion'),
                Registry::get('settings.Checkout.tax_calculation')
            );
        };
    }
}
