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

namespace Tygh\Addons\MobileApp;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Addons\MobileApp\Notifications\Factory;
use Tygh\Languages\Values;
use Tygh\Tygh;

class ServiceProvider implements ServiceProviderInterface
{
    /** @inheritdoc */
    public function register(Container $app)
    {
        $app['addons.mobile_app.notifications.factory'] = function (Container $app) {
            return new Factory();
        };

        $app['addons.mobile_app.translation_manager'] = function (Container $app) {
            return new TranslationManager(new Values(), DEFAULT_LANGUAGE);
        };
    }

    /**
     * @return \Tygh\Addons\MobileApp\TranslationManager
     */
    public static function getTranslationManager()
    {
        return Tygh::$app['addons.mobile_app.translation_manager'];
    }
}