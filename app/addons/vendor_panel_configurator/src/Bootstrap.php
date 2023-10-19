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

use Tygh\Core\ApplicationInterface;
use Tygh\Core\BootstrapInterface;
use Tygh\Core\HookHandlerProviderInterface;

class Bootstrap implements BootstrapInterface, HookHandlerProviderInterface
{
    /** @inheritDoc */
    public function boot(ApplicationInterface $app)
    {
        $app->register(new ServiceProvider());
    }

    /** @inheritDoc */
    public function getHookHandlerMap()
    {
        return [
            /** @see \Tygh\Addons\VendorPanelConfigurator\HookHandlers\MenuHookHandler::onAfterGetSchemaName() */
            'backend_menu_get_schema_name_post'                        => [
                'addons.vendor_panel_configurator.hook_handlers.menu',
                'onAfterGetSchemaName',
            ],
            /** @see \Tygh\Addons\VendorPanelConfigurator\HookHandlers\MenuHookHandler::onBeforeGenerateItem */
            'backend_menu_generate_before_process_item'                 => [
                'addons.vendor_panel_configurator.hook_handlers.menu',
                'onBeforeGenerateItem',
            ],
            /** @see \Tygh\Addons\VendorPanelConfigurator\HookHandlers\MenuHookHandler::onAfterGenerateItem */
            'backend_menu_generate_after_process_item'                 => [
                'addons.vendor_panel_configurator.hook_handlers.menu',
                'onAfterGenerateItem',
            ],
            /** @see \Tygh\Addons\VendorPanelConfigurator\HookHandlers\MenuHookHandler::onBeforeProcessNestedItem */
            'backend_menu_process_items_before_processing'                 => [
                'addons.vendor_panel_configurator.hook_handlers.menu',
                'onBeforeProcessNestedItem',
            ],
            /** @see \Tygh\Addons\VendorPanelConfigurator\HookHandlers\MenuHookHandler::onBeforeMergeCustomMenu */
            'backend_menu_merge_custom_menu_pre'                       => [
                'addons.vendor_panel_configurator.hook_handlers.menu',
                'onBeforeMergeCustomMenu',
            ],
            /** @see \Tygh\Addons\VendorPanelConfigurator\HookHandlers\ProductPageHookHandler::onBeforeFieldOutput */
            'smarty_component_configurable_page_field_before_output'   => [
                'addons.vendor_panel_configurator.hook_handlers.product_page',
                'onBeforeFieldOutput',
            ],
            /** @see \Tygh\Addons\VendorPanelConfigurator\HookHandlers\ProductPageHookHandler::onBeforeSectionOutput */
            'smarty_component_configurable_page_section_before_output' => [
                'addons.vendor_panel_configurator.hook_handlers.product_page',
                'onBeforeSectionOutput',
            ],
            /** @see \Tygh\Addons\VendorPanelConfigurator\HookHandlers\ProductPageHookHandler::onDispatchBeforeDisplay */
            'dispatch_before_display'                                  => [
                'addons.vendor_panel_configurator.hook_handlers.product_page',
                'onDispatchBeforeDisplay',
                PHP_INT_MAX,
            ],
            /** @see \Tygh\Addons\VendorPanelConfigurator\HookHandlers\UsersHookHandler::onInitSessionData */
            'init_user_session_data'                                   => [
                'addons.vendor_panel_configurator.hook_handlers.users',
                'onInitSessionData',
            ],
            /** @see \Tygh\Addons\VendorPanelConfigurator\HookHandlers\UsersHookHandler::onLoginUserPost */
            'login_user_post'                                          => [
                'addons.vendor_panel_configurator.hook_handlers.users',
                'onLoginUserPost',
            ],
            /** @see \Tygh\Addons\VendorPanelConfigurator\HookHandlers\LanguagesHookHandler::onInitLanguagePost */
            'init_language_post'                                       => [
                'addons.vendor_panel_configurator.hook_handlers.languages',
                'onInitLanguagePost',
            ],
            /** @see \Tygh\Addons\VendorPanelConfigurator\HookHandlers\DispatchHookHandler::onDispatchAssignTemplate */
            'dispatch_assign_template'                                       => [
                'addons.vendor_panel_configurator.hook_handlers.dispatch',
                'onDispatchAssignTemplate',
            ],
        ];
    }
}
