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
use Tygh\Gdpr\DataCollector\UserDataCollector;
use Tygh\Gdpr\DataModifier\UserPersonalDataAnonymizer;
use Tygh\Gdpr\DataUpdater\UserPersonalDataUpdater;
use Tygh\Gdpr\SchemaManager;
use Faker\Factory as Faker;

/**
 * Class ServiceProvider is intended to register services and components of the "GDPR" add-on to the application
 * container.
 *
 * @package Tygh\Gdpr
 */
class GdprServiceProvider implements ServiceProviderInterface
{
    /**
     * @return void
     */
    public function register(Container $app)
    {
        $app['gdpr.schema_manager'] = static function (Container $app) {
            return new SchemaManager();
        };

        $app['gdpr.faker'] = static function (Container $app) {
            return Faker::create();
        };

        $app['gdpr.user_data_collector'] = static function (Container $app) {
            return new UserDataCollector($app['gdpr.schema_manager']);
        };

        $app['gdpr.anonymizer'] = static function (Container $app) {
            return new UserPersonalDataAnonymizer(
                $app['gdpr.schema_manager'],
                $app['gdpr.faker']
            );
        };

        $app['gdpr.user_data_updater'] = static function (Container $app) {
            return new UserPersonalDataUpdater(
                $app['gdpr.schema_manager']
            );
        };
    }
}
