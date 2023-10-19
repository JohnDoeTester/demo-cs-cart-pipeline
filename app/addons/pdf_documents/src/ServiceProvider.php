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

namespace Tygh\Addons\PdfDocuments;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Addons\PdfDocuments\HookHandlers\OrdersHookHandler;
use Tygh\Addons\PdfDocuments\HookHandlers\ShipmentsHookHandler;
use Tygh\Registry;

class ServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers add-on services.
     *
     * @param Container $app Application instance
     *
     * @return void
     */
    public function register(Container $app)
    {
        $app['addons.pdf_documents.hook_handlers.orders'] = static function () {
            return new OrdersHookHandler();
        };

        $app['addons.pdf_documents.hook_handlers.shipments'] = static function () {
            return new ShipmentsHookHandler();
        };

        $app['addons.pdf_documents.service_url'] = static function () {
            $addon_service_url = Registry::get('addons.pdf_documents.service_url');

            $service_url = Registry::ifGet(
                'config.pdf_documents.service_url',
                $addon_service_url
            );

            return rtrim($service_url, '/');
        };
    }
}
