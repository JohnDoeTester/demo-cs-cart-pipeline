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

namespace Tygh\Addons\DirectPayments;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Addons\DirectPayments\Cart\Service;
use Tygh\Tygh;

class ServiceProvider implements ServiceProviderInterface
{
    /** @inheritdoc */
    public function register(Container $app)
    {
        $app['addons.direct_payments.cart.service'] = function ($app) {

            return new Service($app['session']);
        };
    }

    /**
     * @return \Tygh\Addons\DirectPayments\Cart\Service
     */
    public static function getCartService()
    {
        return Tygh::$app['addons.direct_payments.cart.service'];
    }
}