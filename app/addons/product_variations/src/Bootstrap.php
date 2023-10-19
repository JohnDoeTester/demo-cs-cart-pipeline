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


namespace Tygh\Addons\ProductVariations;


use Tygh\Core\ApplicationInterface;
use Tygh\Core\BootstrapInterface;
use Tygh\Core\HookHandlerProviderInterface;

/**
 * This class describes instructions for loading the product_variations add-on
 *
 * @package Tygh\Addons\ProductVariations
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
            // Retrieving product data
            'get_products_pre' => [
                'addons.product_variations.hook_handlers.products',
                'onGetProductsPre'
            ],
            'get_products' => [
                'addons.product_variations.hook_handlers.products',
                'onGetProducts'
            ],
            'get_product_data_post' => [
                'addons.product_variations.hook_handlers.products',
                'onGetProductDataPost'
            ],
            'get_product_features_post' => [
                'addons.product_variations.hook_handlers.products',
                'onGetProductFeaturesPost'
            ],
            'gather_additional_products_data_params' => [
                'addons.product_variations.hook_handlers.products',
                'onGatherAdditionalProductsDataParams'
            ],
            'gather_additional_product_data_params' => [
                'addons.product_variations.hook_handlers.products',
                'onGatherAdditionalProductDataParams'
            ],
            'load_products_extra_data_pre' => [
                'addons.product_variations.hook_handlers.products',
                'onLoadProductsExtraDataPre'
            ],
            'get_product_name_post' => [
                'addons.product_variations.hook_handlers.products',
                'onGetProductNamePost'
            ],

            // Updating/deleting product data
            'update_product_pre' => [
                'addons.product_variations.hook_handlers.products',
                'onUpdateProductPre'
            ],
            'update_product_features_value_pre' => [
                'addons.product_variations.hook_handlers.products',
                'onUpdateProductFeaturesValuePre'
            ],
            'update_product_features_value_post' => [
                'addons.product_variations.hook_handlers.products',
                'onUpdateProductFeaturesValuePost'
            ],
            'update_product_post' => [
                'addons.product_variations.hook_handlers.products',
                'onUpdateProductPost'
            ],
            'update_product_categories_pre' => [
                'addons.product_variations.hook_handlers.products',
                'onUpdateProductCategoriesPre'
            ],
            'update_product_categories_post' => [
                'addons.product_variations.hook_handlers.products',
                'onUpdateProductCategoriesPost'
            ],
            'update_product_position_in_category_post' => [
                'addons.product_variations.hook_handlers.products',
                'onUpdateProductPositionInCategoryPost'
            ],
            'add_global_option_link_post' => [
                'addons.product_variations.hook_handlers.products',
                'onAddGlobalOptionLinkPost'
            ],
            'delete_global_option_link_post' => [
                'addons.product_variations.hook_handlers.products',
                'onDeleteGlobalOptionLinkPost'
            ],
            'delete_product_post' => [
                'addons.product_variations.hook_handlers.products',
                'onDeleteProductPost',
                1
            ],
            'update_product_tab_post' => [
                'addons.product_variations.hook_handlers.products',
                'onUpdateProductTabPost'
            ],
            'update_product_tab_pre' => [
                'addons.product_variations.hook_handlers.products',
                'onUpdateProductTabPre'
            ],
            'clone_product_data' => [
                'addons.product_variations.hook_handlers.products',
                'onCloneProductData'
            ],
            'delete_product_feature' => [
                'addons.product_variations.hook_handlers.products',
                'onDeleteProductFeature'
            ],
            'delete_product_feature_variants_pre' => [
                'addons.product_variations.hook_handlers.products',
                'onDeleteProductFeatureVariantsPre',
            ],
            'update_image_pairs' => [
                'addons.product_variations.hook_handlers.products',
                'onUpdateImagePairs'
            ],
            'update_image_pairs_pre' => [
                'addons.product_variations.hook_handlers.products',
                'onUpdateImagePairsPre'
            ],
            'exim_import_images_pre' => [
                'addons.product_variations.hook_handlers.products',
                'onImportProductImagesPre'
            ],
            'delete_image_pair' => [
                'addons.product_variations.hook_handlers.products',
                'onDeleteImagePair'
            ],
            'update_product_amount_post' => [
                'addons.product_variations.hook_handlers.products',
                'onUpdateProductAmountPost'
            ],
            'update_product_feature' => [
                'addons.product_variations.hook_handlers.products',
                'onUpdateProductFeature'
            ],
            'update_image' => [
                'addons.product_variations.hook_handlers.products',
                'onUpdateImage'
            ],
            'tools_change_status' => [
                'addons.product_variations.hook_handlers.products',
                'onChangeStatus'
            ],
            'get_product_exceptions_post' => [
                'addons.product_variations.hook_handlers.products',
                'onGetProductExceptionsPost'
            ],

            // Popularity
            'update_product_popularity' => [
                'addons.product_variations.hook_handlers.products',
                'onUpdateProductPopularity'
            ],

            // Routing and urls
            'url_pre' => [
                'addons.product_variations.hook_handlers.seo',
                'onUrlPre'
            ],
            'get_route' => [
                'addons.product_variations.hook_handlers.seo',
                'onGetRoute',
                1900
            ],
            'google_sitemap_generate_link_post' => [
                'addons.product_variations.hook_handlers.seo',
                'onGoogleSiteMapGenerateLinkPost',
                null,
                'google_sitemap'
            ],

            // Discussion
            'get_discussion_pre' => [
                'addons.product_variations.hook_handlers.discussions',
                'onGetDiscussionPre',
                null,
                'discussion'
            ],
            'discussions_variation_group_mark_product_as_main_post' => [
                'hook'    => 'variation_group_mark_product_as_main_post',
                'handler' => [
                    'addons.product_variations.hook_handlers.discussions',
                    'onVariationGroupMarkProductAsMainPost',
                ],
                'addon'   => 'discussion'
            ],
            'discussion_is_user_eligible_to_write_review_for_product_post' => [
                'addons.product_variations.hook_handlers.discussions',
                'onDiscussionIsUserEligibleToWriteReviewForProductPost'
            ],

            // Seo
            'seo_variation_group_mark_product_as_main_post' => [
                'hook'    => 'variation_group_mark_product_as_main_post',
                'handler' => [
                    'addons.product_variations.hook_handlers.seo',
                    'onVariationGroupMarkProductAsMainPost',
                ],
                'addon'   => 'seo'
            ],
            'seo_get_schema_org_markup_items_post' => [
                'hook'    => 'seo_get_schema_org_markup_items_post',
                'handler' => [
                    'addons.product_variations.hook_handlers.seo',
                    'onSeoGetSchemaOrgMarkupItemsPost',
                ],
                'addon'   => 'seo'
            ],

            // Attachments
            'attachments_variation_group_mark_product_as_main_post' => [
                'hook'    => 'variation_group_mark_product_as_main_post',
                'handler' => [
                    'addons.product_variations.hook_handlers.attachments',
                    'onVariationGroupMarkProductAsMainPost',
                ],
                'addon'   => 'attachments'
            ],
            'get_attachments_pre' => [
                'addons.product_variations.hook_handlers.attachments',
                'onGetAttachmentsPre',
                null,
                'attachments'
            ],

            // Data feeds
            'data_feeds_export_before_get_products' => [
                'addons.product_variations.hook_handlers.products',
                'onDataFeedsExportBeforeGetProducts'
            ],

            // Block manager
            'update_location' => [
                'addons.product_variations.hook_handlers.block_manager',
                'onUpdateLocation'
            ],
            'update_location_post' => [
                'addons.product_variations.hook_handlers.block_manager',
                'onUpdateLocationPost'
            ],
            'update_block_post' => [
                'addons.product_variations.hook_handlers.block_manager',
                'onUpdateBlockPost'
            ],
            'update_block_status_post' => [
                'addons.product_variations.hook_handlers.block_manager',
                'onUpdateBlockStatusPost'
            ],
            'update_snapping_pre' => [
                'addons.product_variations.hook_handlers.block_manager',
                'onUpdateSnappingPre'
            ],
            'update_snapping_post' => [
                'addons.product_variations.hook_handlers.block_manager',
                'onUpdateSnappingPost'
            ],

            // Cart and orders
            'get_order_info' => [
                'addons.product_variations.hook_handlers.carts',
                'onGetOrderInfo'
            ],
            'get_user_edp_post' => [
                'addons.product_variations.hook_handlers.carts',
                'onGetUserEdpPost'
            ],
            'get_cart_products_post' => [
                'addons.product_variations.hook_handlers.carts',
                'onGetCartProductsPost'
            ],
            'check_amount_in_stock_before_check' => [
                'addons.product_variations.hook_handlers.carts',
                'onCheckAmountInStockBeforeCheck'
            ],
            'get_cart_product_data' => [
                'addons.product_variations.hook_handlers.carts',
                'onGetCartProductData'
            ],

            // Others
            'dispatch_before_display' => [
                'addons.product_variations.hook_handlers.products',
                'onDispatchBeforeDisplay'
            ],
            'view_set_view_tools_pre' => [
                'addons.product_variations.hook_handlers.products',
                'onViewSetViewToolsPre'
            ],
            'last_view_init_view_tools_pre' => [
                'addons.product_variations.hook_handlers.products',
                'onLastViewInitViewToolsPre'
            ],
            'vendor_plans_companies_get_products_count_pre' => [
                'addons.product_variations.hook_handlers.products',
                'onVendorPlansCompaniesGetProductsCountPre'
            ],

            // Product reviews
            'product_reviews_find_pre' => [
                'addons.product_variations.hook_handlers.product_reviews',
                'onProductReviewsFindPre',
                null,
                'product_reviews'
            ],
            'product_reviews_create_pre' => [
                'addons.product_variations.hook_handlers.product_reviews',
                'onProductReviewsCreatePre',
                null,
                'product_reviews'
            ],
            'product_reviews_is_user_eligible_to_write_product_review' => [
                'addons.product_variations.hook_handlers.product_reviews',
                'onProductReviewsIsUserEligibleToWriteReview'
            ],
            'post_add_to_cart' => [
                'addons.product_variations.hook_handlers.carts',
                'onPostAddToCart',
            ]
        ];
    }
}
