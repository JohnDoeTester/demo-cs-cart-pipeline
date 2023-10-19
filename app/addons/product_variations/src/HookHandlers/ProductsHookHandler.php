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


namespace Tygh\Addons\ProductVariations\HookHandlers;


use Tygh;
use Tygh\Addons\ProductVariations\Product\Group\Repository as GroupRepository;
use Tygh\Addons\ProductVariations\Product\Repository as ProductRepository;
use Tygh\Addons\ProductVariations\Product\Type\Type as ProductType;
use Tygh\Addons\ProductVariations\ServiceProvider;
use Tygh\Application;
use Tygh\Enum\NotificationSeverity;
use Tygh\Enum\ProductFeatures;
use Tygh\Enum\YesNo;
use Tygh\Registry;
use Tygh\Tools\Url;
use Tygh\Enum\ImagePairTypes;

/**
 * This class describes the hook handlers related to product management
 *
 * @package Tygh\Addons\ProductVariations\HookHandlers
 */
class ProductsHookHandler
{
    protected $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * The "get_products_pre" hook handler.
     *
     * Actions performed:
     * - Adds include_child_variations to the search parameters if search from the list of orders
     * - Modifies also_bought_for_product_id search params with taking into account variation sub group products
     *
     * @see fn_get_products
     */
    public function onGetProductsPre(&$params)
    {
        $product_id_map = ServiceProvider::getProductIdMap();

        if (!empty($params['order_ids'])) {
            $params['include_child_variations'] = YesNo::YES;
        }

        if (
            !empty($params['variations_in_stock'])
            && $params['variations_in_stock'] === YesNo::YES
            && Registry::get('settings.General.inventory_tracking') !== YesNo::NO
            && Registry::get('settings.General.allow_negative_amount') !== YesNo::YES
        ) {
            $params['amount_from'] = 1;
        }

        if (empty($params['also_bought_for_product_id']) || !$product_id_map->isVariationProduct($params['also_bought_for_product_id'])) {
            return;
        }

        $params['also_bought_for_product_ids'] = $product_id_map->getVariationSubGroupProductIds($params['also_bought_for_product_id']);
        $params['also_bought_for_product_id'] = null;
    }

    /**
     * The "get_products" hook handler.
     *
     * Actions performed:
     *  - Implements filtering by parent products
     *  - Implements filtering by product type, based on the following schema: product_variations/product_types
     *  - Implements filtering by linked variations; is used for the following block: "View all variations as list"
     *  - Implements filtering by the presence of features
     *  - Implements filtering by the presence of a product in a variation group
     *  - Implements filtering by the identifier of a variation group
     *  - Determines whether or not to include child variations into selection
     *  - Determines whether or not to group child variations as one product
     *
     * @see fn_get_products
     */
    public function onGetProducts(&$params, &$fields, $sortings, &$condition, &$join, $sorting, &$group_by, $lang_code, $having)
    {
        $params['include_child_variations'] = $this->isWhetherToNeedIncludeChildVariations($params);
        $params['group_child_variations'] = $this->isWhetherToGroupChildVariations($params);

        if (!$params['include_child_variations'] && !$this->isArrayValueNotEmpty($params, 'parent_product_id')) {
            $params['parent_product_id'] = 0;
        }

        if ($params['group_child_variations']) {
            $fields['product_id'] = '(CASE WHEN products.parent_product_id <> 0 THEN products.parent_product_id ELSE products.product_id END) AS product_id';
            $fields['product_ids'] = 'GROUP_CONCAT(products.product_id ORDER BY products.parent_product_id ASC, products.product_id ASC) AS product_ids';
            $fields['product_types'] = 'GROUP_CONCAT(products.product_type ORDER BY products.parent_product_id ASC, products.product_id ASC) AS product_types';
            $fields['parent_product_ids'] = 'GROUP_CONCAT(products.parent_product_id ORDER BY products.parent_product_id ASC, products.product_id ASC) AS parent_product_ids';

            $group_by = 'product_id';
        }

        if ($this->isArrayValueNotEmpty($params, 'product_type')) {
            $product_types = (array) $params['product_type'];
            $product_type_conditions = [];

            foreach ($product_types as $product_type) {
                $product_type_instance = ProductType::create($product_type);
                $product_type_conditions[] = $product_type_instance->createProductSearchCriteria();
            }

            $condition .= sprintf(' AND (%s)', implode(' OR ', $product_type_conditions));
        }

        if ($this->isArrayValueNotEmpty($params, 'variations_by_product_id')) {
            $params['variation_group_id'] = ServiceProvider::getGroupRepository()->findGroupIdByProductId($params['variations_by_product_id']);

            if ($params['variation_group_id'] === null) {
                $condition .= db_quote(' AND 1 != 1');
            }
        } elseif ($this->isArrayValueNotEmpty($params, 'parent_product_id')) {
            if (is_array($params['parent_product_id'])) {
                $condition .= db_quote(' AND products.parent_product_id IN (?n)', $params['parent_product_id']);
            } else {
                $condition .= db_quote(' AND products.parent_product_id = ?i', $params['parent_product_id']);
            }
        }

        if ($this->isArrayValueNotEmpty($params, 'has_features')) {
            $sub_query = db_quote('SELECT product_id FROM ?:product_features_values '
                . ' WHERE feature_id IN (?n) AND variant_id > 0 AND lang_code = ?s'
                . ' GROUP BY product_id HAVING COUNT(feature_id) = ?i',
                $params['has_features'], $lang_code, count($params['has_features'])
            );

            $condition .= db_quote(' AND products.product_id IN (?p)', $sub_query);
        }

        if (isset($params['has_not_variation_group'])) {
            $condition .= db_quote(sprintf(' AND  products.product_id NOT IN (SELECT product_id FROM ?:%s)', GroupRepository::TABLE_GROUP_PRODUCTS));
        } elseif (isset($params['has_variation_group'])) {
            $condition .= db_quote(sprintf(' AND  products.product_id IN (SELECT product_id FROM ?:%s)', GroupRepository::TABLE_GROUP_PRODUCTS));
        }

        if ($this->isArrayValueNotEmpty($params, 'variation_group_id')) {
            $join .= db_quote(sprintf(
                ' INNER JOIN ?:%s AS variation_group_products ON variation_group_products.product_id = products.product_id',
                GroupRepository::TABLE_GROUP_PRODUCTS
            ));
            $fields['variation_group_id'] = 'variation_group_products.group_id AS variation_group_id';
            $condition .= db_quote(' AND variation_group_products.group_id IN (?n)', (array) $params['variation_group_id']);
        }

        $fields['product_type'] = 'products.product_type';
        $fields['parent_product_id'] = 'products.parent_product_id';
    }

    /**
     * The "load_products_extra_data_pre" hook handler.
     *
     * Actions performed:
     *  - Normalizes product data when the selection includes child variations grouped as one product.
     *      This is where the correct product identifier is determined. For example, if a child product matches the filter,
     *          but a parent product doesn't, the identifier of the child product will still be returned;
     *          that way the child product will appear in search results.
     *  - Loads the match between a parent product and a child product to the following service: \Tygh\Addons\ProductVariations\Product\ProductIdMap.
     *      This lowers the number of SQL queries by product identifier for determining whether the product is a child.
     *      For example, it is necessary when generating a URL address by variation.
     *
     * @see fn_load_products_extra_data
     */
    public function onLoadProductsExtraDataPre(&$products, &$params, $lang_code)
    {
        if (empty($products)) {
            return;
        }

        if (!empty($params['group_child_variations'])) {
            foreach ($products as &$product) {
                $sub_product_ids = explode(',', $product['product_ids']);
                $sub_product_types = explode(',', $product['product_types']);
                $sub_parent_product_ids = explode(',', $product['parent_product_ids']);

                $product['product_id'] = reset($sub_product_ids);
                $product['product_type'] = reset($sub_product_types);
                $product['parent_product_id'] = reset($sub_parent_product_ids);
            }

            unset($product);

            if (in_array('prices', $params['extend'])) {
                $params['extend'] = array_diff($params['extend'], ['prices2']);

                if ($params['sort_by'] == 'price') {
                    $params['sort_by'] = null;
                }
            }
        }

        ServiceProvider::getProductIdMap()->setParentProductIdMapByProducts($products);
    }

    /**
     * The "get_product_name" hook handler.
     *
     * Actions performed:
     *  - Returns variation name (product name + feature values).
     *      The implementation is excessive enough; please use fn_get_product_name with caution.
     *
     * @see fn_get_product_name
     */
    public function onGetProductNamePost($product_id, $lang_code, $as_array, &$result)
    {
        if (empty($result)) {
            return;
        }

        $product_id_map = ServiceProvider::getProductIdMap();

        if (!$product_id_map->isVariationProduct($product_id)) {
            return;
        }

        $product_repository = ServiceProvider::getProductRepository();

        $base_lang_code = $product_repository->getLangCode();
        $product_repository->setLangCode($lang_code);

        $product = $product_repository->findProduct($product_id);

        if (!$product) {
            return;
        }

        $product = $product_repository->loadProductGroupInfo($product);
        $product = $product_repository->loadProductVariationName($product);

        $product_repository->setLangCode($base_lang_code);

        if (empty($product['variation_name'])) {
            return;
        }

        if ($as_array) {
            $result = [$product_id => $product['variation_name']];
        } else {
            $result = $product['variation_name'];
        }
    }

    /**
     * The "gather_additional_products_data_params" hook handler.
     *
     * Actions performed:
     *  - Implements the loading of data for products. The following parameters are available:
     *      - get_variation_info - loads the basic information about the variation group to which the product belongs
     *      - get_variation_features_variants - loads combinations of feature variants with products
     *      - get_product_type - loads the product type object
     *      - get_variation_name - loads variation name (product name + feature values) for a product
     *
     *
     * @see fn_gather_additional_products_data
     */
    public function onGatherAdditionalProductsDataParams($product_ids, &$params, &$products, $auth)
    {
        if (!isset($params['get_variation_info'])) {
            $params['get_variation_info'] = $params['get_options'];
        }

        if (!isset($params['get_variation_features_variants'])) {
            $params['get_variation_features_variants'] = $params['get_options'];
        }

        if (!isset($params['get_variation_name'])) {
            $params['get_variation_name'] = true;
        }

        if ($params['get_variation_features_variants'] || $params['get_variation_name']) {
            $params['get_variation_info'] = true;
        }

        if (!isset($params['get_product_type'])) {
            $params['get_product_type'] = false;
        }

        if (AREA === 'A') {
            $params['get_variation_info'] = true;
        }

        if (!$params['get_variation_info']
            && !$params['get_variation_features_variants']
            && !$params['get_variation_name']
        ) {
            return;
        }

        $repository = ServiceProvider::getProductRepository();

        if ($params['get_variation_info']) {
            $products = $repository->loadProductsGroupInfo($products);
        }

        if ($params['get_variation_features_variants']) {
            $products = $repository->loadProductsFeaturesVariants($products);
        }

        if ($params['get_variation_name']) {
            $products = $repository->loadProductsVariationName($products);
        }

        if ($params['get_product_type']) {
            foreach ($products as &$product) {
                $product['product_type_instance'] = ProductType::createByProduct($product);
            }
            unset($product);
        }
    }

    /**
     * The "gather_additional_product_data_params" hook handler.
     *
     * Actions performed:
     *  - Links the loading of combinations of feature values with the loading of options
     *
     *
     * @see fn_gather_additional_product_data
     */
    public function onGatherAdditionalProductDataParams($product, &$params)
    {
        $params['get_variation_features_variants'] = $params['get_options'];
    }

    /**
     * The "get_product_data_post" hook handler.
     *
     * Actions performed:
     *  - Saves information to the product that it is being looked at via "preview".
     *      This is necessary for showing the feature selection block properly,
     *      because "preview" shouldn't include the conditions related to product status.
     *  - Loads the match between a parent product and a child product to the following service: \Tygh\Addons\ProductVariations\Product\ProductIdMap.
     *      This lowers the number of SQL queries by product identifier for determining whether the product is a child.
     *      For example, it is necessary when generating a URL address by variation.
     *
     * @see fn_get_product_data
     */
    public function onGetProductDataPost(&$product_data, $auth, $preview, $lang_code)
    {
        ServiceProvider::getProductIdMap()->setParentProductIdMapByProducts([$product_data]);

        $product_data['detailed_params']['is_preview'] = $preview;
    }

    /**
     * The "dispatch_before_display" hook handler.
     *
     * Actions performed:
     *  - Limits the output of tabs on the product editing page by the conditions imposed by the product type
     *
     * @see fn_dispatch
     */
    public function onDispatchBeforeDisplay()
    {
        $controller = Registry::get('runtime.controller');
        $mode = Registry::get('runtime.mode');

        if (AREA !== 'A' || $controller !== 'products' || $mode !== 'update') {
            return;
        }

        /** @var \Tygh\SmartyEngine\Core $view */
        $view = Tygh::$app['view'];

        /** @var array $product_data */
        $product_data = $view->getTemplateVars('product_data');

        if (!$product_data) {
            return;
        }

        $product_type = ProductType::createByProduct($product_data);

        $tabs = Registry::get('navigation.tabs');

        if (is_array($tabs)) {
            foreach ($tabs as $key => $tab) {
                if (!$product_type->isTabAvailable($key)) {
                    unset($tabs[$key]);
                }
            }

            Registry::set('navigation.tabs', $tabs);
        }
    }

    /**
     * The "update_product_pre" hook handler.
     *
     * Actions performed:
     *  - Implements the ability to update the values of features on which variations are built
     *  - Forbids updating the name of a variation if the name isn't supposed to be editable for this product type
     *
     * @see fn_update_product
     */
    public function onUpdateProductPre(&$product_data, $product_id, $lang_code, &$can_update)
    {
        if (!$product_id) {
            return;
        }

        $product_id_map = ServiceProvider::getProductIdMap();
        $is_child_product = $product_id_map->isChildProduct($product_id);

        if ($is_child_product) {
            $type_collection = ServiceProvider::getTypeCollection();

            $type = $type_collection->get(ProductType::PRODUCT_TYPE_VARIATION);

            if (isset($product_data['product']) && !$type->isFieldAvailable('product')) {
                unset($product_data['product']);
            }
        }

        if (isset($product_data['variation_feature_values'])) {
            $group_repository = ServiceProvider::getGroupRepository();

            $group_id = $group_repository->findGroupIdByProductId($product_id);

            if ($group_id) {
                $result = ServiceProvider::getService()->changeProductsFeatureValues($group_id, [
                    $product_id => $product_data['variation_feature_values']
                ]);
                $result->showNotifications();
            }
        }
    }

    /**
     * phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint
     *
     * The "update_product_features_value_pre" hook handler.
     *
     * @param int                     $product_id       Product identifier
     * @param array<array-key, mixed> $product_features List of feature values
     * @param array<array-key, mixed> $add_new_variant  List of new variants that will be added when the features of a product are saved
     * @param string                  $lang_code        Two-letter language code (e.g. 'en', 'ru', etc.)
     * @param array<array-key, mixed> $params           List of additional parameters
     *
     * Actions performed:
     *  - For a variation, forbids changing the values of features on which variations are built
     *
     * @see fn_update_product_features_value
     */
    public function onUpdateProductFeaturesValuePre($product_id, array &$product_features, array &$add_new_variant, $lang_code, array $params)
    {
        if (!empty($params['is_manual_features_processing'])) {
            return;
        }

        $group_repository = ServiceProvider::getGroupRepository();

        $group_id = $group_repository->findGroupIdByProductId($product_id);

        if (!$group_id) {
            return;
        }

        $feature_ids = $group_repository->findGroupFeatureIdsByProductId($product_id);

        foreach ($feature_ids as $feature_id) {
            unset($product_features[$feature_id]);
            unset($add_new_variant[$feature_id]);
        }
    }

    /**
     * The "update_product_post" hook handler.
     *
     * Actions performed:
     *  - Starts the syncing of data if a variation product is changed
     *
     * @param array<string, string|int> $product_data Product data
     * @param int                       $product_id   Product ID
     *
     * @see fn_update_product
     *
     * @phpcs:disable SlevomatCodingStandard.ControlStructures.EarlyExit.EarlyExitNotUsed
     */
    public function onUpdateProductPost(array $product_data, $product_id)
    {
        $sync_service = ServiceProvider::getSyncService();

        $sync_service->onTableChanged('products', $product_id);
        $sync_service->onTableChanged('product_descriptions', $product_id);
        $sync_service->onTableChanged('product_popularity', $product_id);

        if (fn_allowed_for('ULTIMATE')) {
            $sync_service->onTableChanged('ult_product_descriptions', $product_id);
        }

        if (isset($product_data['amount'])) {
            ServiceProvider::getService()->onChangedProductQuantity($product_id);
        }

        if (isset($product_data['status'])) {
            ServiceProvider::getService()->onChangedVariationProductState($product_id);
        }
    }

    /**
     * The "update_product_categories_pre" hook handler.
     *
     * Actions performed:
     *  - Forbids changing the category of child variations directly
     *
     * @see fn_update_product_categories
     */
    public function onUpdateProductCategoriesPre($product_id, &$product_data, $rebuild, $company_id)
    {
        if (empty($product_data['category_ids'])) {
            return;
        }

        $product_id_map = ServiceProvider::getProductIdMap();

        if ($product_id_map->isChildProduct($product_id)) {
            $product_data['category_ids'] = [];
        }
    }

    /**
     * The "update_product_categories_post" hook handler.
     *
     * Actions performed:
     *  - Starts the syncing of categories when they are changed for a variation product
     *
     * @see fn_update_product_categories
     */
    public function onUpdateProductCategoriesPost($product_id)
    {
        $sync_service = ServiceProvider::getSyncService();
        $sync_service->onTableChanged('products_categories', $product_id);
    }

    /**
     * The "update_product_position_in_category_post" hook handler.
     *
     * Actions performed:
     *  - Starts the syncing of categories when they are changed for a variation product
     *
     * @see fn_update_product_position_in_category
     */
    public function onUpdateProductPositionInCategoryPost($product_id)
    {
        $product_id_map = ServiceProvider::getProductIdMap();

        if ($product_id_map->isParentProduct($product_id)) {
            $sync_service = ServiceProvider::getSyncService();
            $sync_service->onTableChanged('products_categories_position', $product_id);
        }
    }

    /**
     * The "add_global_option_link_post" hook handler.
     *
     * Actions performed:
     *  - Starts the syncing of global options when a global option is added to a variation
     *
     * @see fn_add_global_option_link
     */
    public function onAddGlobalOptionLinkPost($product_id, $option_id)
    {
        $sync_service = ServiceProvider::getSyncService();
        $sync_service->onTableChanged('product_global_option_links', $product_id, ['option_id' => $option_id]);
    }

    /**
     * The "delete_global_option_link_post" hook handler.
     *
     * Actions performed:
     *  - Starts the syncing of global options when a global option is removed from a variation
     *
     * @see fn_delete_global_option_link
     */
    public function onDeleteGlobalOptionLinkPost($product_id, $option_id)
    {
        $sync_service = ServiceProvider::getSyncService();
        $sync_service->onTableChanged('product_global_option_links', $product_id, ['option_id' => $option_id]);
    }

    /**
     * The "update_product_features_value_post" hook handler.
     *
     * Actions performed:
     *  - Starts the syncing of feature values
     *
     * @see fn_update_product_features_value
     */
    public function onUpdateProductFeaturesValuePost($product_id)
    {
        $sync_service = ServiceProvider::getSyncService();
        $sync_service->onTableChanged('product_features_values', $product_id);
    }

    /**
     * The "delete_product_post" hook handler.
     *
     * Actions performed:
     *  - Removes products from a feature group.
     *      IMPORTANT! This handler must run as early as possible (compared to other add-ons),
     *      because removing a parent product from a variation group assigns a new parent product,
     *      that must receive all the attachments and comments of the removed parent product.
     *
     * @see fn_delete_product
     */
    public function onDeleteProductPost($product_id, $product_deleted)
    {
        if (!$product_id || !$product_deleted) {
            return;
        }

        $group_repository = ServiceProvider::getGroupRepository();

        $group_id = $group_repository->findGroupIdByProductId($product_id);

        if ($group_id) {
            $service = ServiceProvider::getService();
            $service->detachProductFromGroup($group_id, $product_id);
        }
    }

    /**
     * The "view_set_view_tools_pre" hook handler.
     *
     * Actions performed:
     *  - Notifies service \Tygh\Addons\ProductVariations\Product\ProductIdMap,
     *      that a query whether the $next_id and $prev_id products are child products might occur;
     *      this may lower the number of SQL queries.
     *
     * @see \Tygh\Navigation\LastView\ACommon::_setViewTools
     */
    public function onViewSetViewToolsPre($last_view, $current_pos, $next_id, $prev_id, $total_items, $url_params)
    {
        /** @var \Tygh\Navigation\LastView\ACommon $last_view */
        if ($last_view->getController() !== 'products' || $last_view->getMode() !== 'view') {
            return;
        }

        ServiceProvider::getProductIdMap()->addProductIdsToPreload([$next_id, $prev_id]);
    }

    /**
     * The "last_view_init_view_tools_pre" hook handler.
     *
     * Actions performed:
     *  - Replaces the ID of the child product to the ID of the parent product
     *
     * @see \Tygh\Navigation\LastView\ACommon::_initViewTools
     */
    public function onLastViewInitViewToolsPre($last_view, &$params)
    {
        /** @var \Tygh\Navigation\LastView\ACommon $last_view */
        if (AREA !== 'C'
            || $last_view->getController() !== 'products'
            || $last_view->getMode() !== 'view'
            || empty($params['product_id'])
        ) {
            return;
        }

        $product_id_map = ServiceProvider::getProductIdMap();

        if ($product_id_map->isChildProduct($params['product_id'])) {
            $params['product_id'] = $product_id_map->getParentProductId($params['product_id']);
        }
    }

    /**
     * The "clone_product_data" hook handler.
     *
     * Actions performed:
     *  - Resets the type of a product and its parent for successful cloning of a child variation
     *
     * @see fn_clone_product
     */
    public function onCloneProductData($product_id, &$data)
    {
        if (empty($data)) {
            return;
        }

        if ($data['product_type'] === ProductType::PRODUCT_TYPE_VARIATION) {
            $data['product_type'] = ProductType::PRODUCT_TYPE_SIMPLE;
            $data['parent_product_id'] = 0;
        }
    }

    /**
     * The "data_feeds_export_before_get_products" hook handler.
     *
     * Actions performed:
     *  - Implements filtering of products by product type for the data_feeds add-on
     *
     * @see fn_data_feeds_export
     */
    public function onDataFeedsExportBeforeGetProducts($datafeed_data, $pattern, &$params)
    {
        if (!empty($datafeed_data['params']['product_types'])) {
            $params['product_type'] = $datafeed_data['params']['product_types'];
        }
    }

    /**
     * The "update_product_tab_pre" hook handler.
     *
     * Actions performed:
     *  - Saves the list of product identifiers for which th tab is available
     *      This will be necessary later for determining what products were added/removed from the tab settings.
     *
     * @see \Tygh\BlockManager\ProductTabs::update
     */
    public function onUpdateProductTabPre($tab_id, &$tab_data)
    {
        if (empty($tab_data['tab_id'])) {
            return;
        }

        $query = ServiceProvider::getQueryFactory()->createQuery(
            'product_tabs',
            ['tab_id' => $tab_data['tab_id']],
            ['product_ids']
        );

        $tab_data['current_product_ids'] = $query->scalar();
    }

    /**
     * The "update_product_tab_post" hook handler.
     *
     * Actions performed:
     *  - If variations were removed when the tab settings, this hook handler will launch tab syncing.
     *      This is necessary because the product page of a child variation must not differ from the product page of its parent product.
     *
     * @see \Tygh\BlockManager\ProductTabs::update
     */
    public function onUpdateProductTabPost($tab_id, $tab_data)
    {
        if (empty($tab_data['product_ids'])) {
            return;
        }

        $current_product_ids = [];
        $product_ids = fn_explode(',', $tab_data['product_ids']);

        if (!empty($tab_data['current_product_ids'])) {
            $current_product_ids = fn_explode(',', $tab_data['current_product_ids']);
        }

        $deleted_product_ids = array_diff($current_product_ids, $product_ids);
        $added_product_ids = array_diff($product_ids, $current_product_ids);
        $affected_product_ids = array_merge($deleted_product_ids, $added_product_ids);

        if (empty($affected_product_ids)) {
            return;
        }

        $sync_service = ServiceProvider::getSyncService();
        $sync_service->onTableChanged('product_tabs', $affected_product_ids, ['tab_id' => $tab_id]);
    }

    /**
     * The "get_product_features_post" hook handler.
     *
     * Actions performed:
     *  - Marks the features that are used in the variation group of the current product.
     *
     * @see fn_get_product_features
     */
    public function onGetProductFeaturesPost(&$data, $params, $has_ungroupped)
    {
        if (AREA !== 'A' || empty($data) || empty($params['product_id'])) {
            return;
        }

        $product_id = (int) $params['product_id'];
        $group_info = ServiceProvider::getGroupRepository()->findGroupInfoByProductId($product_id);

        if (empty($group_info)) {
            return;
        }

        $set_feature_group_info = function ($features, $group_info, $set_feature_group_info) {
            foreach ($features as &$item) {
                if (empty($item['feature_id'])) {
                    continue;
                }

                if (in_array($item['feature_id'], $group_info['feature_ids'])) {
                    $item['product_variation_group'] = $group_info;
                } else {
                    $item['product_variation_group'] = [];
                }
                if (!empty($item['subfeatures'])) {
                    $item['subfeatures'] = $set_feature_group_info($item['subfeatures'], $group_info, $set_feature_group_info);
                }
            }
            unset($item);

            return $features;
        };

        $data = $set_feature_group_info($data, $group_info, $set_feature_group_info);
    }

    /**
     * The "delete_product_feature" hook handler.
     *
     * Actions performed:
     *  - Forbids deleting features that are used at least by one variation group
     *
     * @see fn_delete_feature
     */
    public function onDeleteProductFeature($feature_id, $feature_type, &$can_delete)
    {
        if (!$feature_id || $feature_type === ProductFeatures::GROUP) {
            return;
        }

        $group_ids = ServiceProvider::getGroupRepository()->findGroupIdsByFeaturesIds([$feature_id]);

        if (empty($group_ids)) {
            return;
        }

        $search_link = Url::buildUrn(['products', 'manage'], ['variation_group_id' => $group_ids]);

        fn_set_notification(
            NotificationSeverity::WARNING,
            __('warning'),
            __('product_variations.feature_can_be_deleted', [
                '[href]' => fn_url($search_link, 'A'),
            ])
        );

        $can_delete = false;
    }

    /**
     * The "delete_product_feature_variants_pre" hook handler.
     *
     * Actions performed:
     *  - Forbids deleting feature variants that are used at least by one variation group
     *
     * @see fn_delete_product_feature_variants
     */
    public function onDeleteProductFeatureVariantsPre(&$feature_id, &$variant_ids)
    {
        if (empty($feature_id) && empty($variant_ids)) {
            return;
        }

        if (!empty($feature_id)) {
            $group_ids = ServiceProvider::getGroupRepository()->findGroupIdsByFeaturesIds([$feature_id]);

            if (empty($group_ids)) {
                return;
            }

            $search_link = Url::buildUrn(['products', 'manage'], ['variation_group_id' => $group_ids]);

            $feature_id = 0;
            $variant_ids = [];

            fn_set_notification(
                NotificationSeverity::WARNING,
                __('warning'),
                __('product_variations.feature_can_be_deleted', [
                    '[href]' => fn_url($search_link, 'A'),
                ])
            );

            return;
        }

        if (!empty($variant_ids)) {
            $product_repository = ServiceProvider::getProductRepository();
            $group_repository = ServiceProvider::getGroupRepository();

            $query = ServiceProvider::getQueryFactory()->createQuery(
                $product_repository::TABLE_PRODUCT_FEATURE_VARIANTS,
                ['variant_id' => $variant_ids],
                ['feature_id']
            );

            $feature_ids = $query->column('feature_id');
            $group_ids = $group_repository->findGroupIdsByFeaturesIds($feature_ids);

            if (!$feature_ids || !$group_ids) {
                return;
            }

            $query = ServiceProvider::getQueryFactory()->createQuery(
                $product_repository::TABLE_PRODUCT_FEATURE_VALUES,
                ['variant_id' => $variant_ids, 'lang_code' => CART_LANGUAGE],
                ['fv.variant_id', 'gp.group_id', 'fv.feature_id'],
                'fv'
            );

            $query
                ->addInnerJoin('gp', $group_repository::TABLE_GROUP_PRODUCTS, ['product_id' => 'product_id'])
                ->addInnerJoin('gf', $group_repository::TABLE_GROUP_FEATURES, ['feature_id' => 'feature_id'])
                ->addConditions(['feature_id' => $feature_ids], 'gf')
                ->addConditions(['group_id' => $group_ids], 'gp')
                ->setGroupBy(['fv.variant_id', 'gp.group_id']);

            $variants_by_group = $query->select();

            if (!$variants_by_group) {
                return;
            }

            $excluded_variants = $group_ids = [];

            foreach ($variants_by_group as $variant_by_group) {
                $excluded_variants[$variant_by_group['feature_id']][] = $variant_by_group['variant_id'];
                $group_ids[] = $variant_by_group['group_id'];
            }

            $group_ids = array_unique($group_ids);

            foreach ($excluded_variants as $feature => $excluded_variant) {
                $excluded_variants[$feature] = array_unique($excluded_variant);
                $variant_ids = array_diff($variant_ids, array_values($excluded_variant));
            }

            $search_link = Url::buildUrn(['products', 'manage'], ['variation_group_id' => $group_ids, 'feature_variants' => $excluded_variants]);

            fn_set_notification(
                NotificationSeverity::WARNING,
                __('warning'),
                __('product_variations.feature_can_be_deleted', [
                    '[href]' => fn_url($search_link, 'A'),
                ])
            );
        }
    }

    /**
     * The "update_image_pairs" hook handler.
     *
     * Actions performed:
     *  - Starts the syncing of images when the image of a variation product changes
     *
     * @see fn_update_image_pairs
     */
    public function onUpdateImagePairs($pair_ids, $icons, $detailed, $pairs_data, $object_id, $object_type)
    {
        if (empty($pair_ids) || empty($object_id) || $object_type !== 'product') {
            return;
        }

        ServiceProvider::getSyncService()->onTableChanged('images_links', $object_id);
    }

    /**
     * The "exim_import_images_pre" hook handler.
     *
     * Actions performed:
     *  - Forbids to perform image import actions if own image for child variation was not allowed
     *
     * @see fn_exim_import_images
     */
    public function onImportProductImagesPre($prefix, $image_file, $detailed_file, $position, $type, $object_id, $object, $import_options, &$perform_import)
    {
        if (empty($object_id) || $object !== 'product' || ServiceProvider::isAllowOwnImages()) {
            return;
        }

        if (ServiceProvider::getProductIdMap()->isChildProduct($object_id)) {
            $perform_import = false;
        }
    }

    /**
     * The "update_image_pairs_pre" hook handler.
     *
     * Actions performed:
     *  - Forbids to update the child variation image if the special setting is turn on
     *
     * @param array<int, array<string, int>>                   $icons           Icons images data
     * @param array<int|string, array<string, array<int>|int>> $detailed        Detailed images data
     * @param array<int, array<string, int|string>>            $pairs_data      Pairs data
     * @param int                                              $object_id       Object identifier
     * @param string                                           $object_type     Object type
     * @param array<int>                                       $object_ids      Objects identifier
     * @param bool                                             $update_alt_desc True if image alt text should be updated
     * @param string                                           $lang_code       Two-letter language code
     *
     * @see fn_update_image_pairs
     */
    public function onUpdateImagePairsPre(
        array $icons,
        array &$detailed,
        array &$pairs_data,
        $object_id,
        $object_type,
        array $object_ids,
        $update_alt_desc,
        $lang_code
    ) {
        if ($object_type !== 'product') {
            return;
        }

        $product_id_map = ServiceProvider::getProductIdMap();

        foreach ($pairs_data as $pair_id => $pair_data) {
            $product_id = empty($object_id) ? (int) $pair_data['object_id'] : $object_id;

            if (
                empty($product_id)
                || empty($detailed[$pair_id])
                || !$product_id_map->isVariationProduct($product_id)
            ) {
                continue;
            }

            if (!ServiceProvider::isAllowOwnImages() && $product_id_map->isChildProduct($product_id)) {
                unset($pairs_data[$pair_id]);
                continue;
            }

            $is_new = isset($pair_data['is_new']) ? YesNo::toBool($pair_data['is_new']) : null;

            if ($pair_data['type'] !== ImagePairTypes::MAIN || $is_new) {
                continue;
            }

            $main_image = fn_get_image_pairs($product_id, $object_type, $pair_data['type'], true, true, $lang_code);

            if (empty($main_image)) {
                continue;
            }

            $image_id = empty($main_image['image_id']) ? $main_image['detailed_id'] : $main_image['image_id'];

            $is_image_in_use = db_get_row(
                'SELECT object_id FROM ?:images_links WHERE (detailed_id = ?i OR image_id = ?i) AND object_id <> ?i LIMIT 1',
                $image_id,
                $image_id,
                $product_id
            );

            if (empty($is_image_in_use)) {
                continue;
            }

            if (!isset($detailed[$pair_id]['pairs_to_remove'])) {
                $detailed[$pair_id]['pairs_to_remove'] = [];
            }

            /** @var array<int> $detailed[$pair_id]['pairs_to_remove'] */
            $detailed[$pair_id]['pairs_to_remove'][] = (int) $main_image['pair_id'];

            $pairs_data[$pair_id]['pair_id'] = 0;
            $pairs_data[$pair_id]['is_new'] = YesNo::YES;
        }
    }

    /**
     * The "update_image" hook handler.
     *
     * Actions performed:
     *  - Remove products pairs links
     *
     * @param array<string, string|int|array> $image_data Image data
     */
    public function onUpdateImage(array $image_data)
    {
        if (!isset($image_data['pairs_to_remove'])) {
            return;
        }

        /** @var array<int> $image_data['pairs_to_remove'] */
        foreach ($image_data['pairs_to_remove'] as $pair_id) {
            db_query('DELETE FROM ?:images_links WHERE pair_id = ?i', $pair_id);
        }
    }

    /**
     * The "delete_image_pair" hook handler.
     *
     * Actions performed:
     *  - Starts the syncing of images when the image of a variation product gets removed
     *
     * @see fn_delete_image_pair
     */
    public function onDeleteImagePair($pair_id, $object_type, $image)
    {
        if (empty($image) || empty($image['object_id']) || $image['object_type'] !== 'product') {
            return;
        }

        ServiceProvider::getSyncService()->onTableChanged('images_links', $image['object_id']);
    }

    /**
     * The "update_product_amount_post" hook handler.
     *
     * Actions performed:
     *  - Starts the automatic selection of a new parent product if the quantity of a product changes to 0
     *
     * @see fn_update_product_amount
     */
    public function onUpdateProductAmountPost($product_id, $amount_delta, $product_options, $sign, $tracking, $current_amount, $new_amount, $product_code, $notify)
    {
        if (!ServiceProvider::getProductIdMap()->isVariationProduct($product_id)) {
            return;
        }

        ServiceProvider::getService()->onChangedProductQuantity($product_id);
    }

    /**
     * The "update_product_feature" hook handler.
     *
     * Actions performed:
     *  - Prevents feature categories update if variations grouped by this feature will lose assigned feature values
     *    because they are not present in new feature groups.
     *
     * @see fn_update_product_feature
     *
     * @phpcs:disable SlevomatCodingStandard.Operators.DisallowEqualOperators
     */
    public function onUpdateProductFeature(array &$feature_data, $feature_id, $lang_code, array $old_feature_data, array &$old_categories, array &$new_categories)
    {
        $deleted_categories = array_diff($old_categories, $new_categories);
        $added_categories = [];
        if (!$old_categories) {
            $added_categories = array_diff($new_categories, $old_categories);
        }

        if (
            !$new_categories
            || $old_categories == $new_categories
            || !($deleted_categories || $added_categories)
        ) {
            return;
        }

        // find variation groups that are based on the feature
        $feature_ids = array_merge(
            [$feature_id],
            array_column($old_feature_data['subfeatures'], 'feature_id')
        );

        $group_ids = ServiceProvider::getGroupRepository()->findGroupIdsByFeaturesIds($feature_ids);
        if (!$group_ids) {
            return;
        }

        $groups = ServiceProvider::getGroupRepository()->findGroupsByIds($group_ids);

        // find products that are included in these variation groups
        $products_groups = $affected_products = [];
        foreach ($groups as $group) {
            $products_groups += array_fill_keys($group->getProductIds(), $group->getId());
        }

        if ($deleted_categories) {
            // filter products that are included in these variation groups and are present in deleted feature categories
            $query = ServiceProvider::getQueryFactory()->createQuery(
                'products_categories',
                [
                    ['IN', 'product_id', array_keys($products_groups)],
                    ['IN', 'category_id', $deleted_categories],
                ],
                ['pc.product_id'],
                'pc'
            );

            $affected_products = $query->column(['product_id', 'product_id']);
        }

        if ($added_categories) {
            // filter products that are included in these variation groups and are not present in new feature categories
            $query = ServiceProvider::getQueryFactory()->createQuery(
                'products_categories',
                [
                    ['IN', 'product_id', array_keys($products_groups)],
                    ['NOT IN', 'category_id', $added_categories],
                ],
                ['pc.product_id'],
                'pc'
            );

            $affected_products = $affected_products + $query->column(['product_id', 'product_id']);
        }

        $products_groups = array_intersect_key($products_groups, $affected_products);

        if (!$products_groups) {
            return;
        }

        // prevent feature group and feature categories update if it affects variation groups
        $feature_data['parent_id'] = $old_feature_data['parent_id'];
        $feature_data['categories_path'] = $old_feature_data['categories_path'];
        $new_categories = $old_categories;

        $search_link = Url::buildUrn([
            'products', 'manage'],
            ['variation_group_id' => array_unique(array_values($products_groups))]
        );

        fn_set_notification(
            NotificationSeverity::WARNING,
            __('warning'),
            __('product_variations.cant_edit_feature_categories', [
                '[href]' => fn_url($search_link, 'A'),
            ])
        );
    }

    /**
     * The "vendor_plans_companies_get_products_count_pre" hook handler.
     *
     * Actions performed:
     *  - Excludes child variations from the number of vendor's products.
     *
     * @see \Tygh\Models\Company::getCurrentProductsCount
     */
    public function onVendorPlansCompaniesGetProductsCountPre($instance, &$coditions)
    {
        $coditions['parent_product_id'] = 'parent_product_id = 0';
    }

    /**
     * The "update_product_popularity" hook handler.
     *
     * Actions performed:
     *  - Executes child products sync, if popularity of parent product changed.
     *    If child product popularity was changed, changes parent product popularity and sync child products.
     *
     * @see fn_update_product_popularity()
     */
    public function onUpdateProductPopularity($product_id)
    {
        $product_id_map = ServiceProvider::getProductIdMap();
        $sync_service = ServiceProvider::getSyncService();

        if ($product_id_map->isParentProduct($product_id)) {
            $parent_id = $product_id;
        } elseif ($product_id_map->isChildProduct($product_id)) {
            $parent_id = $product_id_map->getParentProductId($product_id);
            $sync_service->sync('product_popularity', $product_id, [$parent_id]);
        } else {
            return;
        }

        $child_ids = $product_id_map->getProductChildrenIds($parent_id);

        if (!empty($child_ids)) {
            $sync_service->sync('product_popularity', $parent_id, $child_ids);
        }
    }

    /**
     * The "tools_change_status" hook handler.
     *
     * Actions performed:
     *  - Starts the automatic selection of a new parent product if the status of a product changes to not active
     *
     * @param array<string, string> $params Request params
     * @param int                   $result Updating status result
     *
     * @see \fn_tools_update_status()
     */
    public function onChangeStatus(array $params, $result)
    {
        if (!$result || $params['table'] !== 'products') {
            return;
        }

        $product_id = (int) $params['id'];

        ServiceProvider::getService()->onChangedVariationProductState($product_id, true);
    }

    /**
     * phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint
     *
     * The "get_product_exceptions_post" hook handler.
     *
     * Actions performed:
     *  - For child variations, replaces the combination of options with the parent value
     *
     * @param int                     $product_id Product identifier
     * @param array<array-key, mixed> $exceptions Exceptions data
     * @param bool                    $short_list Flag determines if exceptions list should be returned in short format
     *
     * @return void
     *
     * phpcs:enable
     */
    public function onGetProductExceptionsPost($product_id, array &$exceptions, $short_list)
    {
        if (!empty($exceptions)) {
            return;
        }

        $parent_product_id = ServiceProvider::getProductIdMap()->getParentProductId($product_id);

        if (!$parent_product_id) {
            return;
        }

        $exceptions = fn_get_product_exceptions($parent_product_id, $short_list);
    }

    /**
     * Determines if the parameters of product selection have product code as a search condition
     *
     * @param array $params
     *
     * @return bool
     */
    protected function isParamsHasFiltersByProductCode(array $params)
    {
        return (!empty($params['pcode']) && fn_strlen($params['pcode']) > 3)
            || (
                !empty($params['q']) && fn_strlen($params['q']) > 3
                && isset($params['pcode_from_q']) && $params['pcode_from_q'] === 'Y'
            );
    }

    /**
     * Determines if the parameters of product selection have price as a search condition
     *
     * @param array $params
     *
     * @return bool
     */
    protected function isParamsHasFiltersByPrice(array $params)
    {
        return (isset($params['price_from']) && fn_is_numeric($params['price_from']))
            || (isset($params['price_to']) && fn_is_numeric($params['price_to']));
    }

    /**
     * Determines if the parameters of product selection have a feature that is used in at least one variation group as a search condition
     *
     * @param array $params
     *
     * @return bool
     */
    protected function isParamsHasFiltersByVariations(array $params)
    {
        if (empty($params['filter_variants'])
            && empty($params['features_hash'])
            && empty($params['feature_variants'])
        ) {
            return false;
        }

        if (ServiceProvider::isAllowOwnFeatures()) {
            return true;
        }

        $selected_filters = $filter_ids = $variant_ids = [];

        if (!empty($params['filter_variants'])) {
            $selected_filters = $params['filter_variants'];
        } elseif (!empty($params['features_hash'])) {
            $selected_filters = fn_parse_filters_hash($params['features_hash']);
            $filter_ids = array_keys($selected_filters);
        }

        if (!empty($params['feature_variants'])) {
            $selected_filters = array_replace($selected_filters, $params['feature_variants']);
        }

        foreach ($selected_filters as $items) {
            foreach ($items as $variant_id) {
                $variant_id = (int) $variant_id;

                if ($variant_id) {
                    $variant_ids[$variant_id] = $variant_id;
                }
            }
        }

        if (!$variant_ids && !$filter_ids) {
            return false;
        }

        if ($filter_ids) {
            $query = ServiceProvider::getQueryFactory()->createQuery(
                [GroupRepository::TABLE_GROUP_FEATURES => 'gf']
            );

            $query->addInnerJoin(
                'pf', ProductRepository::TABLE_PRODUCT_FILTERS,
                ['feature_id' => 'feature_id']
            );

            $query
                ->addCondition('pf.feature_id > 0')
                ->addInCondition('filter_id', $filter_ids, 'pf')
                ->addField('pf.feature_id')
                ->setLimit(1);

            return (bool) $query->scalar();
        }

        $query = ServiceProvider::getQueryFactory()->createQuery(
            ProductRepository::TABLE_PRODUCT_FEATURE_VALUES,
            ['variant_id' => $variant_ids, 'lang_code' => CART_LANGUAGE],
            ['fv.feature_id'],
            'fv'
        );

        $query
            ->addInnerJoin('gf', GroupRepository::TABLE_GROUP_FEATURES, ['feature_id' => 'feature_id'])
            ->setLimit(1);

        return (bool) $query->scalar();
    }

    /**
     * Determines if child variations must be included in product selection. Works by the following rules:
     * - If parameter "include_child_variations" is explicitly stated, then the function will return its value
     * - If the selection is performed in the admin panel, child variations will be included in the selection
     * - If the selection is performed by the parent product ID, child variations will be included in the selection
     * - If at least one of the following conditions are met, child variations will be inlcuded in the product selection:
     *  - The selection occurs in a picker
     *  - The selection occurs by product identifiers
     *  - If fn_get_products is called to get the product selection conditions (this is used in filters at least)
     *  - The selection conditions have search by product code
     *  - The selection conditions have search by product price
     *  - The selection conditions have search by a feature that is used in at least one variation group
     *
     * @param array $params
     *
     * @return bool
     */
    protected function isWhetherToNeedIncludeChildVariations(array $params)
    {
        if (isset($params['include_child_variations'])) {
            return $params['include_child_variations'];
        }

        if (!empty($params['only_for_counting'])) {
            return false;
        }

        if ($params['area'] === 'A') {
            return true;
        }

        if (isset($params['parent_product_id'])) {
            return true;
        }

        return !empty($params['is_picker'])
            || !empty($params['pid'])
            || !empty($params['get_conditions'])
            || $this->isParamsHasFiltersByProductCode($params)
            || $this->isParamsHasFiltersByPrice($params)
            || $this->isParamsHasFiltersByVariations($params);
    }

    /**
     * Determines if child variations must be grouped. Works by the following rules:
     * - If parameter "group_child_variations" is explicitly stated, then the function will return its value
     * - Child variations will be grouped if all of these conditions are met:
     *  - The selection is performed for the customer area
     *  - Child variations are included in the product selection
     *  - The product selection is performed OUTSIDE the product picker
     *  - The product selection is performed NOT by product identifiers
     *  - The fn_get_products function is called NOT to get the conditions of product selection
     *  - The conditions of product selection conditions don't include filtering by related variations
     *
     * @param array $params
     *
     * @return bool
     */
    protected function isWhetherToGroupChildVariations(array $params)
    {
        if (isset($params['group_child_variations'])) {
            return $params['group_child_variations'];
        }

        return $params['area'] === 'C'
            && !empty($params['include_child_variations'])
            && empty($params['is_picker'])
            && empty($params['pid'])
            && empty($params['get_conditions'])
            && empty($params['variations_by_product_id'])
            && empty($params['order_ids']);
    }

    /**
     * Returns a value indicating whether the give value is "empty".
     *
     * The value is considered "empty", if one of the following conditions is satisfied:
     *
     * - it is `null`,
     * - an empty string (`''`),
     * - a string containing only whitespace characters,
     * - or an empty array.
     *
     * @param mixed $value
     *
     * @return bool if the value is empty
     */
    protected function isValueEmpty($value)
    {
        return $value === '' || $value === [] || $value === null || is_string($value) && trim($value) === '';
    }

    protected function isArrayValueNotEmpty(array $params, $key)
    {
        if (!array_key_exists($key, $params)) {
            return false;
        }

        return !$this->isValueEmpty($params[$key]);
    }
}
