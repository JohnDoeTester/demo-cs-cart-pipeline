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

namespace Tygh\Addons\VendorPanelConfigurator;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Tygh\Addons\VendorPanelConfigurator\HookHandlers\DispatchHookHandler;
use Tygh\Addons\VendorPanelConfigurator\HookHandlers\MenuHookHandler;
use Tygh\Addons\VendorPanelConfigurator\HookHandlers\ProductPageHookHandler;
use Tygh\Addons\VendorPanelConfigurator\HookHandlers\UsersHookHandler;
use Tygh\Addons\VendorPanelConfigurator\HookHandlers\LanguagesHookHandler;
use Tygh\Registry;
use Tygh\Settings;
use Tygh\Tygh;

class ServiceProvider implements ServiceProviderInterface
{
    /**
     * @param \Pimple\Container $app Application instance
     *
     * @return void
     */
    public function register(Container $app)
    {
        $app['addons.vendor_panel_configurator.settings'] = static function ($app) {
            $config = Registry::get('addons.vendor_panel_configurator');
            $config['product_fields_configuration'] = json_decode($config['product_fields_configuration'], true);
            $config['product_tabs_configuration'] = json_decode($config['product_tabs_configuration'], true);

            return $config;
        };

        $app['addons.vendor_panel_configurator.hook_handlers.menu'] = static function ($app) {
            return new MenuHookHandler($app['session']['auth']['user_type']);
        };

        $app['addons.vendor_panel_configurator.hook_handlers.product_page'] = static function ($app) {
            // page configuration is loaded on-demand in the hook handler methods
            return new ProductPageHookHandler($app['session']['auth']['user_type']);
        };

        $app['addons.vendor_panel_configurator.hook_handlers.users'] = static function () {
            return new UsersHookHandler();
        };

        $app['addons.vendor_panel_configurator.hook_handlers.languages'] = static function () {
            return new LanguagesHookHandler();
        };

        $app['addons.vendor_panel_configurator.hook_handlers.dispatch'] = static function () {
            return new DispatchHookHandler();
        };

        $app['addons.vendor_panel_configurator.settings_service'] = static function () {
            return new SettingsService(
                fn_get_schema('products', 'page_configuration'),
                static::getSettings(),
                Settings::instance()
            );
        };
    }

    /**
     * @return \Tygh\Addons\VendorPanelConfigurator\SettingsService
     */
    public static function getSettingsService()
    {
        return Tygh::$app['addons.vendor_panel_configurator.settings_service'];
    }

    /**
     * @return array{
     *   product_fields_configuration: array<
     *     string, array<
     *       string, array<
     *         string, bool
     *       >
     *     >
     *   >,
     *   product_tabs_configuration: array<
     *     string, bool
     *   >
     * }
     */
    private static function getSettings()
    {
        return Tygh::$app['addons.vendor_panel_configurator.settings'];
    }
}
