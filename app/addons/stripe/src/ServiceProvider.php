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

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Addons\Stripe\HookHandlers\CheckoutHookHandler;
use Tygh\Addons\Stripe\HookHandlers\DispatchHookHandler;
use Tygh\Addons\Stripe\HookHandlers\PaymentsHookHandler;
use Tygh\Addons\Stripe\HookHandlers\ProductsHookHandler;
use Tygh\Addons\Stripe\PaymentButton\DataLoader;
use Tygh\Addons\Stripe\Payments\Stripe;
use Tygh\Addons\Stripe\Webhook\Handlers\PaymentIntentSucceeded;
use Tygh\Addons\Stripe\Webhook\StripeWebhookRepository;
use Tygh\Addons\Stripe\Webhook\Handlers\CheckoutSessionAsyncPaymentSucceeded;
use Tygh\Registry;
use Tygh\Tygh;

class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $app)
    {
        $app['addons.stripe.hook_handlers.dispatch'] = static function (Container $app) {
            return new DispatchHookHandler($app);
        };

        $app['addons.stripe.hook_handlers.products'] = static function (Container $app) {
            return new ProductsHookHandler($app);
        };

        $app['addons.stripe.hook_handlers.checkout'] = static function (Container $app) {
            return new CheckoutHookHandler($app);
        };

        $app['addons.stripe.hook_handlers.payments'] = static function (Container $app) {
            return new PaymentsHookHandler($app);
        };

        $app['addons.stripe.payment_button.data_loader'] = static function (Container $app) {
            return new DataLoader($app);
        };

        $app['addons.stripe.price_formatter'] = static function (Container $app) {
            return new PriceFormatter($app['formatter']);
        };

        $app['addons.stripe.payment_button.buttons'] = static function (Container $app) {
            return static function ($company_id) use ($app) {
                $registry_key = $company_id ? 'stripe_payment_buttons_' . $company_id : 'stripe_payment_buttons';
                Registry::registerCache(
                    $registry_key,
                    ['payments', 'payment_processors'],
                    Registry::cacheLevel('static'),
                    true
                );

                $payment_buttons = Registry::ifGet($registry_key, null);
                if ($payment_buttons === null) {
                    /** @var \Tygh\Addons\Stripe\PaymentButton\DataLoader $data_loader */
                    $data_loader = $app['addons.stripe.payment_button.data_loader'];
                    $payment_buttons = $data_loader->getSupportedPayments(['script' => Stripe::getScriptName(), 'company_id' => $company_id]);

                    Registry::set($registry_key, $payment_buttons);
                }

                return $payment_buttons;
            };
        };

        $app['addons.stripe.webhook_repository'] = static function (Container $app) {
            return new StripeWebhookRepository($app['db']);
        };

        // Webhook handlers
        $app['addons.stripe.webhook_handler.payment_intent.succeeded'] = static function (Container $app) {
            return new PaymentIntentSucceeded();
        };

        $app['addons.stripe.webhook_handler.checkout.session.async_payment_succeeded'] = static function (Container $app) {
            return new CheckoutSessionAsyncPaymentSucceeded();
        };
    }

    /**
     * Gets stripe payment buttons for specified company.
     *
     * @param int|null $company_id Company identifier
     *
     * @return array<string, string>|null
     */
    public static function getPaymentButtons($company_id = null)
    {
        return call_user_func(Tygh::$app['addons.stripe.payment_button.buttons'], $company_id);
    }

    /**
     * Gets payment icons
     *
     * @param int|null $company_id Company identifier.
     *
     * @return array<int, string>|null
     */
    public static function getPaymentMethodIcons($company_id = null)
    {
        $payment_buttons = self::getPaymentButtons($company_id);
        if (empty($payment_buttons)) {
            return $payment_buttons;
        }

        return array_unique(array_column($payment_buttons, 'payment_type'));
    }
}
