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

namespace Tygh\Addons\VendorRating;

use Tygh\Core\ApplicationInterface;
use Tygh\Core\BootstrapInterface;
use Tygh\Core\HookHandlerProviderInterface;

/**
 * Class Bootstrap provides instructions to load the vendor_rating add-on.
 *
 * @package Tygh\Addons\VendorRating
 */
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
            /** @see \Tygh\Addons\VendorRating\HookHandlers\ProductsHookHandler::onGetSorting() */
            'products_sorting' => [
                'addons.vendor_rating.hook_handlers.products',
                'onGetSorting'
            ],
            /** @see \Tygh\Addons\VendorRating\HookHandlers\ProductsHookHandler::onGetProducts() */
            'get_products' => [
                'addons.vendor_rating.hook_handlers.products',
                'onGetProducts'
            ],
            /** @see \Tygh\Addons\VendorRating\HookHandlers\ProductsHookHandler::afterGetProduct() */
            'get_product_data_post' => [
                'addons.vendor_rating.hook_handlers.products',
                'afterGetProduct'
            ],
            /** @see \Tygh\Addons\VendorRating\HookHandlers\CompaniesHookHandler::onGetSorting() */
            'companies_sorting' => [
                'addons.vendor_rating.hook_handlers.companies',
                'onGetSorting'
            ],
            /** @see \Tygh\Addons\VendorRating\HookHandlers\CompaniesHookHandler::onGetCompanies() */
            'get_companies' => [
                'addons.vendor_rating.hook_handlers.companies',
                'onGetCompanies'
            ],
            /** @see \Tygh\Addons\VendorRating\HookHandlers\CompaniesHookHandler::onAfterGetCompanies() */
            'get_companies_post' => [
                'addons.vendor_rating.hook_handlers.companies',
                'onAfterGetCompanies',
            ],
            /** @see \Tygh\Addons\VendorRating\HookHandlers\CompaniesHookHandler::afterGetCompany() */
            'get_company_data_post' => [
                'addons.vendor_rating.hook_handlers.companies',
                'afterGetCompany'
            ],
            /** @see \Tygh\Addons\VendorRating\HookHandlers\CompaniesHookHandler::onUpdate() */
            'update_company' => [
                'addons.vendor_rating.hook_handlers.companies',
                'onUpdate'
            ],
            /** @see \Tygh\Addons\VendorRating\HookHandlers\CompaniesHookHandler::onDelete() */
            'delete_company' => [
                'addons.vendor_rating.hook_handlers.companies',
                'onDelete'
            ],
            /** @see \Tygh\Addons\VendorRating\HookHandlers\VendorPlansHookHandler::onGetVendorPlans() */
            'vendor_plan_get_list' => [
                'addons.vendor_rating.hook_handlers.vendor_plans',
                'onGetVendorPlans'
            ],
            /** @see \Tygh\Addons\VendorRating\HookHandlers\VendorPlansHookHandler::onUpdate() */
            'vendor_plan_update' => [
                'addons.vendor_rating.hook_handlers.vendor_plans',
                'onUpdate'
            ],
            /** @see \Tygh\Addons\VendorRating\HookHandlers\VendorPlansHookHandler::onDelete() */
            'vendor_plan_after_delete' => [
                'addons.vendor_rating.hook_handlers.vendor_plans',
                'onDelete'
            ],
            /** @see \Tygh\Addons\VendorRating\HookHandlers\LogHookHandler::onSave() */
            'save_log' => [
                'addons.vendor_rating.hook_handlers.log',
                'onSave',
            ],
            /** @see \Tygh\Addons\VendorRating\HookHandlers\MasterProductsHookHandler::onGetBestProductOfferPost() */
            'get_best_product_offer_post' => [
                'addons.vendor_rating.hook_handlers.master_products',
                'onGetBestProductOfferPost',
                null,
                'master_products',
            ],
        ];
    }
}
