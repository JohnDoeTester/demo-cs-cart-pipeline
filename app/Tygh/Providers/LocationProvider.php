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

namespace Tygh\Providers;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Location\Location;
use Tygh\Location\Manager;
use Tygh\Location\CartUserDataStorage;
use Tygh\Registry;

class LocationProvider implements ServiceProviderInterface
{
    /** @inheritdoc */
    public function register(Container $app)
    {
        $app['location'] = function (Container $app) {

            $manager = new Manager(
                Registry::get('settings.Checkout'),
                $app['db'],
                Registry::get('runtime.company_id'),
                CART_LANGUAGE,
                $app['session']['auth']['user_id'],
                $app['location.user_data_storage']
            );

            $manager->setLocation($app['location.default_location']);

            if ($stored_location = fn_get_session_data($manager::SESSION_STORAGE_KEY)) {
                $manager->setLocationFromArray($stored_location);
            }

            return $manager;
        };

        $app['location.user_data_storage'] = function (Container $app) {
            $user_data_storage = new CartUserDataStorage($app['location.user_data_storage.storage']);

            return $user_data_storage;
        };

        $app['location.user_data_storage.storage'] = function(Container $app) {
            return null;
        };

        $app['location.default_location'] = function (Container $app) {
            $settings = Registry::get('settings.Checkout');

            $location = new Location(
                $settings['default_country'],
                $settings['default_state'],
                $settings['default_city'],
                $settings['default_address'],
                $settings['default_zipcode'],
                CART_LANGUAGE
            );

            return $location;
        };
    }
}
