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

namespace Tygh\Addons\Gdpr;

use Tygh\Registry;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Addons\Gdpr\DataExtractor\UserPersonalDataCollectionExtractor;
use Tygh\Addons\Gdpr\DataExtractor\UserExtraPersonalDataCollectionExtractor;
use Faker\Factory as Faker;

/**
 * Class ServiceProvider is intended to register services and components of the "GDPR" add-on to the application
 * container.
 *
 * @package Tygh\Addons\Gdpr
 */
class ServiceProvider implements ServiceProviderInterface
{
    /** @inheritdoc */
    public function register(Container $app)
    {
        $addon_settings = (array) Registry::get('settings.gdpr');
        $addon_settings['gdpr']['gdpr_settings_data'] = isset($addon_settings['gdpr']['gdpr_settings_data'])
            ? (array) json_decode($addon_settings['gdpr']['gdpr_settings_data'], true)
            : array();

        $app['addons.gdpr.service'] = static function (Container $app) use ($addon_settings) {
            $gdpr_service = new Service(
                $app['gdpr.schema_manager'],
                $app['db'],
                $addon_settings,
                $app['session']['auth'],
                $app['mailer']
            );

            return $gdpr_service;
        };

        $app['addons.gdpr.user_extra_personal_data_extractor'] = static function (Container $app) {
            $user_personal_data_extractor = new UserExtraPersonalDataCollectionExtractor(
                $app['gdpr.schema_manager']
            );

            return $user_personal_data_extractor;
        };

        $app['addons.gdpr.user_personal_data_extractor'] = static function (Container $app) {
            $user_personal_data_extractor = new UserPersonalDataCollectionExtractor(
                $app['gdpr.schema_manager']
            );

            return $user_personal_data_extractor;
        };

        $app['addons.gdpr.cookies_policy_manager'] = static function (Container $app) use ($addon_settings) {
            $cookies_policy_manager = new CookiesPolicyManager(
                $app['addons.gdpr.service'],
                $addon_settings
            );

            return $cookies_policy_manager;
        };
    }
}
