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

namespace Tygh\Addons\TildaPages;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Addons\TildaApi\TildaClient;
use Tygh\Registry;
use Tygh\Tygh;

/**
 * Class ServiceProvider is intended to register services and components of the "Landing pages from Tilda" add-on to the application container.
 *
 * @package Tygh\Addons\TildaPages
 */
class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     *
     * @return void
     */
    public function register(Container $app)
    {
        $app['addons.tilda_pages.tilda_client'] = static function (Container $app) {
            return new TildaClient(
                Registry::get('addons.tilda_pages.tilda_public_api_key'),
                Registry::get('addons.tilda_pages.tilda_private_api_key'),
                Registry::get('addons.tilda_pages.tilda_project_id')
            );
        };
    }

    /**
     * @return \Tygh\Addons\TildaApi\TildaClient
     */
    public static function getTildaClient()
    {
        return Tygh::$app['addons.tilda_pages.tilda_client'];
    }
}
