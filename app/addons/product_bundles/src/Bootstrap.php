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

namespace Tygh\Addons\ProductBundles;

use Tygh\Core\ApplicationInterface;
use Tygh\Core\BootstrapInterface;
use Tygh\Core\HookHandlerProviderInterface;
use Tygh\SmartyEngine\Core;

class Bootstrap implements BootstrapInterface, HookHandlerProviderInterface
{
    /**
     * @inheritDoc
     */
    public function boot(ApplicationInterface $app)
    {
        $app->register(new ServiceProvider());
    }

    /**
     * @inheritDoc
     */
    public function getHookHandlerMap()
    {
        return [
            'get_promotions' => [
                'addons.product_bundles.hook_handlers.promotions',
                'onGetPromotions',
            ],
            'promotion_apply_pre' => [
                'addons.product_bundles.hook_handlers.promotions',
                'onPrePromotionApply',
            ],
            /** @see \Tygh\Addons\ProductBundles\HookHandlers\PromotionHookHandler::onGetPromotionsPost */
            'get_promotions_post' => [
                'addons.product_bundles.hook_handlers.promotions',
                'onGetPromotionsPost',
            ],
            'delete_product_pre' => [
                'addons.product_bundles.hook_handlers.products',
                'onPreProductDelete',
            ],
            'tools_change_status' => [
                'addons.product_bundles.hook_handlers.tools',
                'onToolsChangeStatus',
            ],
            'get_products_pre' => [
                'addons.product_bundles.hook_handlers.products',
                'onGetProductsPre',
                2000,
                'product_variations',
            ],
            /** @see \Tygh\Addons\ProductBundles\HookHandlers\ProductBundlesHookHandler::onPostGetBundles */
            'product_bundle_service_get_bundles_post' => [
                'addons.product_bundles.hook_handlers.product_variations',
                'onPostGetBundles',
                null,
                'product_variations',
            ],
            'product_bundle_service_get_bundles' => [
                'addons.product_bundles.hook_handlers.product_variations',
                'onGetBundles',
                null,
                'product_variations',
            ],
            'product_bundles_service_update_hidden_promotion_before_update' => [
                'addons.product_bundles.hook_handlers.direct_payments',
                'onPreUpdatePromotion',
                null,
                'direct_payments',
            ],
            'pre_promotion_validate' => [
                'addons.product_bundles.hook_handlers.promotions',
                'onPrePromotionValidate',
            ],
            /** @see \Tygh\Addons\ProductBundles\HookHandlers\ProductTabsHookHandler::onGetProductTabsPost */
            'get_product_tabs_post' => [
                'addons.product_bundles.hook_handlers.product_tabs',
                'onGetProductTabsPost',
            ],
            /** @see \Tygh\Addons\ProductBundles\HookHandlers\ProductBundlesHookHandler::onUpdateLinks */
            'product_bundle_service_update_links' => [
                'addons.product_bundles.hook_handlers.product_variations',
                'onUpdateLinks',
                null,
                'product_variations',
            ],
            /** @see \Tygh\Addons\ProductBundles\HookHandlers\ProductBundlesHookHandler::onCheckCartForCompleteBundles */
            'product_bundle_service_check_cart_for_complete_bundles_before_getting_product_ids' => [
                'addons.product_bundles.hook_handlers.product_variations',
                'onCheckCartForCompleteBundles',
                null,
                'product_variations',
            ],
            /** @see \Tygh\Addons\ProductBundles\HookHandlers\ProductBundlesHookHandler::onHowManyBundlesCanBeInCartBeforeGettingProductAmounts */
            'product_bundle_service_how_many_bundles_can_be_in_cart_before_getting_product_amounts' => [
                'addons.product_bundles.hook_handlers.product_variations',
                'onHowManyBundlesCanBeInCartBeforeGettingProductAmounts',
                null,
                'product_variations',
            ],
            /** @see \Tygh\Addons\ProductBundles\HookHandlers\ProductBundlesHookHandler::onHowManyBundlesCanBeInCartBeforeGettingProductId */
            'product_bundle_service_how_many_bundles_can_be_in_cart_before_getting_product_id' => [
                'addons.product_bundles.hook_handlers.product_variations',
                'onHowManyBundlesCanBeInCartBeforeGettingProductId',
                null,
                'product_variations',
            ],
            'init_templater_post' => static function (Core $view) {
                $view->addPluginsDir(__DIR__ . '/SmartyPlugins');
            },
        ];
    }
}
