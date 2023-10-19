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
use Tygh\Application;
use Tygh\NotificationsCenter\Factory;
use Tygh\NotificationsCenter\NotificationsCenter;
use Tygh\NotificationsCenter\Repository;
use Tygh\Registry;

class NotificationsCenterProvider implements ServiceProviderInterface
{

    /** @inheritdoc */
    public function register(Container $app)
    {
        $app['notifications_center'] = function (Application $app) {
            $nc_schema = fn_get_schema('notifications', 'notifications_center');

            $notifications_center = new NotificationsCenter(
                $app['session']['auth']['user_id'],
                AREA,
                $app['notifications_center.repository'],
                $app['notifications_center.factory'],
                $app['formatter'],
                $nc_schema,
                AREA === 'A'
                    ? Registry::get('settings.Appearance.admin_elements_per_page')
                    : Registry::get('settings.Appearance.elements_per_page')
            );

            return $notifications_center;
        };

        $app['notifications_center.repository'] = function (Application $app) {
            $repository = new Repository(
                $app['db'],
                $app['notifications_center.factory']
            );

            return $repository;
        };

        $app['notifications_center.factory'] = function (Application $app) {
            $factory = new Factory($app);

            return $factory;
        };
    }
}
