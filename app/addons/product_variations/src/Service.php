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

use Tygh\Addons\ProductVariations\Commands\ABaseGenerateProductsCommand;
use Tygh\Addons\ProductVariations\Product\CombinationsGenerator;
use Tygh\Addons\ProductVariations\Product\FeaturePurposes;
use Tygh\Addons\ProductVariations\Product\Group\Events\ParentProductChangedEvent;
use Tygh\Addons\ProductVariations\Product\Group\Events\ProductAddedEvent;
use Tygh\Addons\ProductVariations\Product\Group\Events\ProductRemovedEvent;
use Tygh\Addons\ProductVariations\Product\Group\Events\ProductUpdatedEvent;
use Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection;
use Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValue;
use Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValueCollection;
use Tygh\Addons\ProductVariations\Product\Group\GroupProductCollection;
use Tygh\Addons\ProductVariations\Product\ProductIdMap;
use Tygh\Addons\ProductVariations\Product\Sync\ProductDataIdentityMapRepository;
use Tygh\Addons\ProductVariations\Product\Group\Group;
use Tygh\Addons\ProductVariations\Product\Group\GroupCodeGenerator;
use Tygh\Addons\ProductVariations\Product\Group\Repository as GroupRepository;
use Tygh\Addons\ProductVariations\Product\Repository as ProductRepository;
use Tygh\Addons\ProductVariations\Product\Type\Type;
use Tygh\Addons\ProductVariations\Request\ABaseGenerateProductsRequest;
use Tygh\Addons\ProductVariations\Request\GenerateProductsAndAttachToGroupRequest;
use Tygh\Addons\ProductVariations\Request\GenerateProductsAndCreateGroupRequest;
use Tygh\Common\OperationResult;
use Tygh\Enum\ObjectStatuses;
use Tygh\Exceptions\DatabaseException;
use Tygh\Exceptions\DeveloperException;
use Tygh\Exceptions\InputException;

/**
 * Implements methods for working with a variation group
 *
 * @package Tygh\Addons\ProductVariations
 */
class Service
{
    /** @var \Tygh\Addons\ProductVariations\Product\Group\Repository */
    protected $group_repository;

    /** @var \Tygh\Addons\ProductVariations\Product\Group\GroupCodeGenerator */
    protected $group_code_generator;

    /** @var \Tygh\Addons\ProductVariations\Product\Repository */
    protected $product_repository;

    /** @var \Tygh\Addons\ProductVariations\Product\Sync\ProductDataIdentityMapRepository */
    protected $identity_map_repository;

    /** @var \Tygh\Addons\ProductVariations\SyncService */
    protected $sync_service;

    /** @var \Tygh\Addons\ProductVariations\Product\ProductIdMap */
    protected $product_id_map;

    /** @var \Tygh\Addons\ProductVariations\Product\CombinationsGenerator */
    protected $combinations_generator;

    /** @var bool */
    protected $is_multivendor;

    /** @var bool */
    protected $inventory_tracking_enabled;

    /** @var bool */
    protected $auto_change_default_variation_enabled;

    /** @var int[] */
    protected $updated_state_product_ids = [];

    /**
     * Service constructor.
     *
     * @param \Tygh\Addons\ProductVariations\Product\Group\Repository                      $group_repository
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupCodeGenerator              $group_code_generator
     * @param \Tygh\Addons\ProductVariations\Product\Repository                            $product_repository
     * @param \Tygh\Addons\ProductVariations\Product\Sync\ProductDataIdentityMapRepository $identity_map_repository
     * @param \Tygh\Addons\ProductVariations\SyncService                                   $sync_service
     * @param \Tygh\Addons\ProductVariations\Product\ProductIdMap                          $product_id_map
     * @param \Tygh\Addons\ProductVariations\Product\CombinationsGenerator                 $combinations_generator
     * @param bool                                                                         $is_multivendor
     * @param bool                                                                         $inventory_tracking_enabled
     * @param bool                                                                         $auto_change_default_variation_enabled
     */
    public function __construct(
        GroupRepository $group_repository,
        GroupCodeGenerator $group_code_generator,
        ProductRepository $product_repository,
        ProductDataIdentityMapRepository $identity_map_repository,
        SyncService $sync_service,
        ProductIdMap $product_id_map,
        CombinationsGenerator $combinations_generator,
        $is_multivendor,
        $inventory_tracking_enabled,
        $auto_change_default_variation_enabled
    ) {
        $this->group_repository = $group_repository;
        $this->group_code_generator = $group_code_generator;
        $this->product_repository = $product_repository;
        $this->identity_map_repository = $identity_map_repository;
        $this->sync_service = $sync_service;
        $this->product_id_map = $product_id_map;
        $this->combinations_generator = $combinations_generator;
        $this->is_multivendor = $is_multivendor;
        $this->inventory_tracking_enabled = $inventory_tracking_enabled;
        $this->auto_change_default_variation_enabled = $auto_change_default_variation_enabled;
    }

    /**
     * Creates variations group
     *
     * @param array<int>                                                               $product_ids    Product IDs
     * @param null|string                                                              $code           Group code
     * @param null|\Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection $group_features Features data
     * @param array<string, bool|string|int>                                           $params         Params to create group
     *
     * @return \Tygh\Common\OperationResult
     */
    public function createGroup(array $product_ids, $code = null, GroupFeatureCollection $group_features = null, array $params = [])
    {
        $result = new OperationResult(true, $params);

        $product_ids = array_unique(array_filter($product_ids));
        $base_product_id = reset($product_ids);

        $this->validateProductIds($result, $product_ids);
        $this->validateCode($result, $code);

        if (empty($group_features)) {
            $features = $this->product_repository->findAvailableFeatures($base_product_id);
            $group_features = GroupFeatureCollection::createFromFeatureList($features);
        }

        $this->validateFeatures($result, $group_features);

        if (!$result->isSuccess()) {
            return $result;
        }

        $group = Group::createNewGroup($group_features, $code === null ? $this->group_code_generator->next() : $code);

        $products = $this->product_repository->findProducts($product_ids);
        $products = $this->product_repository->loadProductsFeatures($products, $group->getFeatures());

        $this->addProductsToGroup($result, $products, $group);

        if (!$result->isSuccess()) {
            return $result;
        }

        $this->saveGroup($result, $group);

        return $result;
    }

    /**
     * Add products to exists variations group
     *
     * @param int   $group_id
     * @param array $new_product_ids
     *
     * @return \Tygh\Common\OperationResult
     */
    public function attachProductsToGroup($group_id, array $new_product_ids = [])
    {
        $result = new OperationResult(true);

        $product_ids = array_filter($new_product_ids);
        $group = $this->findGroupById($group_id);

        $this->validateGroup($result, $group_id, $group);
        $this->validateProductIds($result, $product_ids);

        if (!$result->isSuccess()) {
            return $result;
        }

        $new_products = $this->product_repository->findProducts($new_product_ids);
        $new_products = $this->product_repository->loadProductsFeatures($new_products, $group->getFeatures());

        $this->addProductsToGroup($result, $new_products, $group);

        if (!$result->isSuccess()) {
            return $result;
        }

        $this->saveGroup($result, $group);

        return $result;
    }

    /**
     * Update variations group code
     *
     * @param int    $group_id
     * @param string $code
     *
     * @return \Tygh\Common\OperationResult
     */
    public function updateGroupCode($group_id, $code)
    {
        $result = new OperationResult(true);

        $group = $this->findGroupById($group_id);

        $this->validateGroup($result, $group_id, $group);
        $this->validateCode($result, $code, true, $group_id);

        if (!$result->isSuccess()) {
            return $result;
        }

        $group->setCode($code);

        $this->group_repository->updateCode($group);

        $result->setData($group, 'group');
        $result->setSuccess(true);

        return $result;
    }

    /**
     * Removes products from variations group
     *
     * @param int   $group_id
     * @param int[] $product_ids
     *
     * @return \Tygh\Common\OperationResult
     */
    public function detachProductsFromGroup($group_id, $product_ids)
    {
        $result = new OperationResult(true);

        $group = $this->findGroupById($group_id);

        $this->validateGroup($result, $group_id, $group);

        if (!$result->isSuccess()) {
            return $result;
        }

        foreach ($product_ids as $product_id) {
            $group->detachProductById($product_id);
        }

        $this->saveGroup($result, $group);

        return $result;
    }

    /**
     * Removes product from variations group
     *
     * @param int $group_id
     * @param int $product_id
     *
     * @return \Tygh\Common\OperationResult
     */
    public function detachProductFromGroup($group_id, $product_id)
    {
        return $this->detachProductsFromGroup($group_id, [$product_id]);
    }

    /**
     * Removes group and unlink products. All products will be simple (product_type = P).
     *
     * @param int $group_id
     *
     * @return \Tygh\Common\OperationResult
     */
    public function removeGroup($group_id)
    {
        $result = new OperationResult(true);

        $group = $this->findGroupById($group_id);

        $this->validateGroup($result, $group_id, $group);

        if (!$result->isSuccess()) {
            return $result;
        }

        $group->detachAllProducts();
        $this->saveGroup($result, $group);

        return $result;
    }

    /**
     * Moves products from one variation group to another
     *
     * @param int   $group_id
     * @param int[] $product_ids
     * @param array $products_feature_values
     *
     * @return \Tygh\Common\OperationResult
     */
    public function moveProductsToGroup($group_id, $product_ids, array $products_feature_values = [])
    {
        $result = new OperationResult(true);

        $group = $this->findGroupById($group_id);

        $this->validateProductIds($result, $product_ids);
        $this->validateGroup($result, $group_id, $group);

        if (!$result->isSuccess()) {
            return $result;
        }

        $products = $this->product_repository->findProducts($product_ids);
        $products = $this->product_repository->loadProductsGroupInfo($products, false);
        $products = $this->product_repository->loadProductsFeatures($products, $group->getFeatures());
        $products = $this->applyProductsFeatureValues($products, $products_feature_values);

        $this->addProductsToGroup($result, $products, $group);

        if (!$result->isSuccess()) {
            return $result;
        }

        $this->removeMovedToAnotherGroupProducts($result->getData('products_status', []), $products);
        $this->saveGroup($result, $group);

        return $result;
    }

    /**
     * Moves products from a variation group to a new variation group
     *
     * @param string $code
     * @param int[]  $product_ids
     * @param array  $products_feature_values
     *
     * @return \Tygh\Common\OperationResult
     */
    public function moveProductsToNewGroup($code, $product_ids, array $products_feature_values = [])
    {
        $result = new OperationResult(true);

        $this->validateCode($result, $code, true);
        $this->validateProductIds($result, $product_ids);

        if (!$result->isSuccess()) {
            return $result;
        }

        $base_product_id = reset($product_ids);

        $features = $this->product_repository->findAvailableFeatures($base_product_id);
        $group_features = GroupFeatureCollection::createFromFeatureList($features);

        $this->validateFeatures($result, $group_features);

        if (!$result->isSuccess()) {
            return $result;
        }

        $group = Group::createNewGroup($group_features);
        $group->setCode($code);

        $products = $this->product_repository->findProducts($product_ids);
        $products = $this->product_repository->loadProductsGroupInfo($products, false);
        $products = $this->product_repository->loadProductsFeatures($products, $group->getFeatures());
        $products = $this->applyProductsFeatureValues($products, $products_feature_values);

        $this->addProductsToGroup($result, $products, $group);

        if (!$result->isSuccess()) {
            return $result;
        }

        $this->removeMovedToAnotherGroupProducts($result->getData('products_status', []), $products);
        $this->saveGroup($result, $group);

        return $result;
    }

    /**
     * Marks product as main product
     *
     * @param int $group_id
     * @param int $product_id
     *
     * @return \Tygh\Common\OperationResult
     */
    public function setDefaultProduct($group_id, $product_id)
    {
        $result = new OperationResult(true);

        $group = $this->findGroupById($group_id);

        $this->validateGroup($result, $group_id, $group);

        if (!$result->isSuccess()) {
            return $result;
        }

        $group_product = $group->getProduct($product_id);

        if (!$group_product) {
            $result->addError('product_id', __('product_variations.error.product_not_found_in_group', [
                '[product_id]' => $product_id,
                '[group_code]' => $group->getCode()]
            ));
            return $result;
        }

        if (!$group_product->getParentProductId()) {
            $result->addError('product_id', __('product_variations.error.cannot_mark_main_product_child_product', [
                '[product_id]' => $product_id,
                '[group_code]' => $group->getCode()]
            ));
            return $result;
        }

        if ($this->auto_change_default_variation_enabled && $this->inventory_tracking_enabled) {
            $group_product_amount = $this->product_repository->getProductAmount($product_id);

            if (!$group_product_amount) {
                $result->addError(
                    'amount_error',
                    __(
                        'product_variations.error.cannot_set_as_default_out_of_stock',
                        ['[product_id]' => $product_id]
                    )
                );
                return $result;
            }
        }

        $group->setDefaultProduct($product_id);
        $this->saveGroup($result, $group);

        return $result;
    }

    /**
     * Generates products by features variants combinations and add to exists variations group
     *
     * @param \Tygh\Addons\ProductVariations\Request\GenerateProductsAndAttachToGroupRequest $request
     *
     * @return \Tygh\Common\OperationResult
     */
    public function generateProductsAndAttachToGroup($request)
    {
        if (!$request instanceof GenerateProductsAndAttachToGroupRequest) {
            $request = GenerateProductsAndAttachToGroupRequest::create(...func_get_args());
        }

        $result = new OperationResult(true);

        $group = $this->findGroupById($request->getGroupId());

        $this->validateGroup($result, $request->getGroupId(), $group);

        if (!$result->isSuccess()) {
            return $result;
        }

        $combinations = $this->generateCombinations($request, $group->getFeatures(), $group->getProductIds());

        if (empty($combinations)) {
            $result->setSuccess(false);
            $result->addError('combination_ids', __('product_variations.error.generate_combinations_is_empty'));
            return $result;
        }

        $result = $this->checkLimitNewProductVariations($combinations, $request->getBaseProductId(), $result);
        if ($result->isFailure()) {
            return $result;
        }

        $product_ids = $this->saveProductsByCombinations($combinations);

        if (empty($product_ids)) {
            $result->setSuccess(false);
            $result->addError('product_ids', __('product_variations.error.generate_products_is_empty'));
            return $result;
        }

        if (!$group->getProduct($request->getBaseProductId())) {
            array_unshift($product_ids, $request->getBaseProductId());
        }

        $result->merge($this->attachProductsToGroup($request->getGroupId(), $product_ids), true);
        $result->setSuccess(!$result->hasErrors());

        return $result;
    }

    /**
     * Generates products by features variants combinations and create variations group
     *
     * @param \Tygh\Addons\ProductVariations\Request\GenerateProductsAndCreateGroupRequest $request
     *
     * @return \Tygh\Common\OperationResult
     */
    public function generateProductsAndCreateGroup($request)
    {
        if (!$request instanceof GenerateProductsAndCreateGroupRequest) {
            $request = GenerateProductsAndCreateGroupRequest::create(...func_get_args());
        }

        $result = new OperationResult(false);

        if (empty($request->getGroupFeatures())) {
            $features = $this->product_repository->findAvailableFeatures($request->getBaseProductId());
            $group_features = GroupFeatureCollection::createFromFeatureList($features);
        } else {
            $group_features = $request->getGroupFeatures();
        }

        $combinations = $this->generateCombinations($request, $group_features);

        if (empty($combinations)) {
            $result->addError('combination_ids', __('product_variations.error.generate_combinations_is_empty'));
            return $result;
        }

        $result = $this->checkLimitNewProductVariations($combinations, $request->getBaseProductId(), $result);
        if ($result->isFailure()) {
            return $result;
        }

        $product_ids = $this->saveProductsByCombinations($combinations);

        if (empty($product_ids)) {
            $result->addError('product_ids', __('product_variations.error.generate_products_is_empty'));
            return $result;
        }

        array_unshift($product_ids, $request->getBaseProductId());

        $result = $this->createGroup($product_ids, null, $group_features);

        return $result;
    }

    /**
     * Changes products feature values
     *
     * @param int   $group_id
     * @param array $products_feature_values [product_id => [feature_id => variant_id]]
     *
     * @return \Tygh\Common\OperationResult
     */
    public function changeProductsFeatureValues($group_id, $products_feature_values)
    {
        $result = new OperationResult(true);

        $group = $this->findGroupById($group_id);

        $this->validateGroup($result, $group_id, $group);

        if (!$result->isSuccess()) {
            return $result;
        }

        $products = $this->product_repository->findProducts(array_keys($products_feature_values));
        $products = $this->product_repository->loadProductsFeatures($products, $group->getFeatures());
        $products = $this->applyProductsFeatureValues($products, $products_feature_values);

        foreach ($products as $product_id => $product) {
            if (!$group->hasProduct($product_id)) {
                unset($products[$product_id]);
                continue;
            }
        }

        if (!$products) {
            $result->setSuccess(true);
            return $result;
        }

        $this->addProductsToGroup($result, $products, $group);

        if (!$result->isSuccess()) {
            return $result;
        }

        $this->saveGroup($result, $group);

        return $result;
    }

    /**
     * Gets feature variants combinations
     *
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection $group_features
     * @param array                                                               $exists_product_ids
     * @param array                                                               $filter_combination_ids
     *
     * @return array
     */
    public function getFeaturesVariantsCombinations(GroupFeatureCollection $group_features, array $exists_product_ids = [], array $filter_combination_ids = [])
    {
        return $this->combinations_generator->generate($group_features, $exists_product_ids, $filter_combination_ids);
    }

    /**
     * Gets feature variants combinations by variations group
     *
     * @param Group $group
     *
     * @return array
     */
    public function getFeaturesVariantsCombinationsByGroup(Group $group)
    {
        return $this->getFeaturesVariantsCombinations($group->getFeatures(), $group->getProductIds());
    }

    /**
     * Marks product as product with a changed quantity in stock for auto change default product in variation group
     *
     * @param int $product_id
     *
     * @deprecated since 4.11.6 use Tygh\Addons\ProductVariations::onChangedProductQuantity()
     *
     * @see \Tygh\Addons\ProductVariations\Service::onChangedProductQuantity()
     */
    public function onChangedProductQuantityInZero($product_id)
    {
        $this->onChangedProductQuantity($product_id);
    }

    /**
     * Tries to change default product of variation group
     *
     * @param int $product_id Product identifier
     */
    public function onChangedProductQuantity($product_id)
    {
        if (
            !$this->inventory_tracking_enabled
            || !$this->auto_change_default_variation_enabled
            || !$this->product_id_map->isVariationProduct($product_id)
        ) {
            return;
        }

        $this->onChangedVariationProductState($product_id);
    }

    /**
     * Tries to change default product of variation group after variation product state changed (amount or status) if needed.
     *
     * @param int  $product_id  Product ID
     * @param bool $immediately Execute change default product immediately
     */
    public function onChangedVariationProductState($product_id, $immediately = false)
    {
        if (!$this->auto_change_default_variation_enabled || !$this->product_id_map->isVariationProduct($product_id)) {
            return;
        }

        $parent_product_id = $this->product_id_map->isParentProduct($product_id)
            ? $product_id
            : $this->product_id_map->getParentProductId($product_id);

        if ($parent_product_id === null) {
            return;
        }

        if ($immediately) {
            $this->changeDefaultProductsOnAfterParentProductChanged([$parent_product_id]);
            return;
        }

        if (empty($this->updated_state_product_ids)) {
            register_shutdown_function(function () {
                $product_ids = $this->updated_state_product_ids;
                $this->updated_state_product_ids = [];

                $this->changeDefaultProductsOnAfterParentProductChanged($product_ids);
            });
        }

        $this->updated_state_product_ids[$parent_product_id] = $parent_product_id;
    }

    /**
     * Sets the most popular child product available as the new parent product
     *
     * @param array<int> $product_ids Product IDs
     */
    protected function changeDefaultProductsOnAfterParentProductChanged(array $product_ids)
    {
        $products = $this->product_repository->findProducts($product_ids);

        $products = array_filter($products, function (array $product) {
            return ($this->inventory_tracking_enabled && (int) $product['amount'] === 0) || $product['status'] !== ObjectStatuses::ACTIVE;
        });

        if (!$products) {
            return;
        }

        $product_ids = array_keys($products);
        $this->product_id_map->addProductIdsToPreload($product_ids);
        $product_group_id_map = $this->group_repository->findGroupIdsByProductIds($product_ids);

        foreach ($product_ids as $product_id) {
            if (!isset($product_group_id_map[$product_id])) {
                continue;
            }

            $group_id = $product_group_id_map[$product_id];
            $children_ids = $this->product_id_map->getProductChildrenIds($product_id, true);

            if (empty($children_ids)) {
                continue;
            }

            $children_id = $this->product_repository->findActiveAndMorePopularProductId($children_ids, $this->inventory_tracking_enabled);

            if (!$children_id) {
                continue;
            }

            $this->setDefaultProduct($group_id, $children_id);
        }
    }

    /**
     * Creates or updates products by features variants combinations
     *
     * @param array<string, array> $combinations
     *
     * @return int[]
     */
    protected function saveProductsByCombinations(array $combinations)
    {
        if (empty($combinations)) {
            return [];
        }

        $product_ids = [];
        $update_parent_product_ids = [];
        $sync_map = [];

        foreach ($combinations as $combination) {
            if ($combination['exists']) {
                $product_id = $combination['product_id'];

                if ($combination['updated']) {
                    $this->updateProductByCombiation($product_id, $combination);

                    if (empty($combination['parent_product_id']) && $combination['has_children']) {
                        $update_parent_product_ids[] = $product_id;
                    }
                }

                if (empty($combination['linked'])) {
                    $this->product_repository->updateProductFeaturesValues(
                        $product_id,
                        $combination['selected_variants']
                    );
                }
            } else {
                $base_product_id = $combination['base_product_id'];
                $product_id = $this->createProductByCombination($combination);

                $sync_map[$base_product_id][$product_id] = $combination;
                $product_ids[] = $product_id;
            }
        }

        foreach ($sync_map as $parent_product_id => $items) {
            $ids = array_keys($items);

            $this->sync_service->copyAll($parent_product_id, $ids);

            foreach ($items as $product_id => $combination) {
                $this->updateProductByCombiation($product_id, $combination);
                $this->product_repository->updateProductFeaturesValues($product_id, $combination['selected_variants']);
            }
        }

        foreach ($update_parent_product_ids as $product_id) {
            $this->sync_service->onTableChanged(ProductRepository::TABLE_PRODUCT_DESCRIPTIONS, $product_id);
            $this->sync_service->onTableChanged(ProductRepository::TABLE_PRODUCT_ULT_DESCRIPTIONS, $product_id);
        }

        return $product_ids;
    }

    /**
     * Finds variations group by group identifier
     *
     * @param int $group_id
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\Group|null
     */
    protected function findGroupById($group_id)
    {
        return $this->group_repository->findGroupById($group_id);
    }

    /**
     * @param OperationResult                                    $result
     * @param array                                              $products
     * @param \Tygh\Addons\ProductVariations\Product\Group\Group $group
     *
     * @return \Tygh\Common\OperationResult
     */
    protected function addProductsToGroup(OperationResult $result, array $products, Group $group)
    {
        if (empty($products)) {
            $result->setSuccess(false);
            $result->addError('products', __('product_variations.error.product_ids_empty'));
            return $result;
        }

        $affected_products_count = 0;
        $products_status = [];

        /**
         * Executes before a product is added to a variation group;
         * allows to implement additional checks before adding products to variation group.
         *
         * @param \Tygh\Addons\ProductVariations\Service             $this      Instance of the service
         * @param \Tygh\Common\OperationResult                       $result    Result of current operation
         * @param \Tygh\Addons\ProductVariations\Product\Group\Group $group     Instance of variation grou
         * @param array                                              $products  List of products
         */
        fn_set_hook('variation_group_add_products_to_group', $this, $result, $products, $group, $products_status);

        try {
            $group_products = GroupProductCollection::createFromProducts($products, $group->getFeatures());

            $products_status += $group->attachProducts($group_products);

            foreach ($products_status as $product_id => $attach_result) {
                $product = $products[$product_id];

                if ($attach_result === Group::RESULT_ERROR_PRODUCT_COMBINATION_ALREADY_EXISTS) {
                    $result->addWarning('product_' . $product['product_id'], __('product_variations.error.product_with_features_combination_exits', [
                        '[product_id]' => $product['product_id'],
                        '[product]' => $product['product']
                    ]));
                    continue;
                }

                if ($attach_result === Group::RESULT_ERROR_PRODUCT_INVALID_FEATURE_VALUES) {
                    $result->addWarning('product_' . $product['product_id'], __('product_variations.error.product_has_not_required_features', [
                        '[product_id]' => $product['product_id'],
                        '[product]' => $product['product']
                    ]));
                    continue;
                }

                if ($attach_result === Group::RESULT_ERROR_PRODUCT_COMPANY_DOES_NOT_MATCH_TO_GROUP_COMPANY) {
                    if ($this->is_multivendor) {
                        $result->addWarning('product_' . $product['product_id'], __('product_variations.error.product_company_does_not_match_to_group_company.mve', [
                            '[product_id]' => $product['product_id'],
                            '[product]' => $product['product']
                        ]));
                    } else {
                        $result->addWarning('product_' . $product['product_id'], __('product_variations.error.product_company_does_not_match_to_group_company.ult', [
                            '[product_id]' => $product['product_id'],
                            '[product]' => $product['product']
                        ]));
                    }
                    continue;
                }

                if (!Group::isResultError($attach_result)) {
                    $affected_products_count++;
                }
            }
        } catch (InputException $exception) {
            $result->setSuccess(false);
            $result->addError($exception->getCode(), $exception->getMessage());
        }

        $result->setData($products_status, 'products_status');

        if (!$affected_products_count) {
            $result->setSuccess(false);
        }

        return $result;
    }

    /**
     * @param array $products
     * @param array $products_feature_values
     *
     * @return array
     */
    protected function applyProductsFeatureValues(array $products, array $products_feature_values)
    {
        foreach ($products_feature_values as $product_id => $feature_values) {
            if (!isset($products[$product_id])) {
                continue;
            }

            foreach ($feature_values as $feature_id => $variant_id) {
                if (!isset($products[$product_id]['variation_features'][$feature_id])) {
                    continue;
                }

                $products[$product_id]['variation_features'][$feature_id]['variant_id'] = $variant_id;
            }
        }

        return $products;
    }

    /**
     * @param array $results
     * @param array $products
     */
    protected function removeMovedToAnotherGroupProducts(array $results, array $products)
    {
        $on_remove_list = [];

        foreach ($results as $product_id => $attach_result) {
            if (Group::isResultError($attach_result) || empty($products[$product_id]['variation_group_id'])) {
                continue;
            }

            $product = $products[$product_id];
            $on_remove_list[$product['variation_group_id']][] = $product['product_id'];
        }

        foreach ($on_remove_list as $group_id => $product_ids) {
            $this->detachProductsFromGroup($group_id, $product_ids);
        }
    }

    /**
     * @param \Tygh\Common\OperationResult                       $result
     * @param \Tygh\Addons\ProductVariations\Product\Group\Group $group
     */
    protected function saveGroup(OperationResult $result, Group $group)
    {
        try {
            $events = $group->getEvents();

            if ($group->isEmpty()) {
                $this->group_repository->remove($group);
            } else {
                $group->setUpdatedAt('now');

                $this->group_repository->save($group);
            }

            $parent_product_ids = $child_product_ids = $delete_product_ids = [];

            foreach ($events as $event) {
                if ($event instanceof ProductRemovedEvent) {
                    $delete_product_ids[] = $event->getProduct()->getProductId();
                } elseif ($event instanceof ProductAddedEvent) {
                    $child_product_ids[$event->getProduct()->getParentProductId()][$event->getProduct()->getProductId()] = $event->getProduct()->getProductId();
                } elseif ($event instanceof ProductUpdatedEvent) {
                    if (!$event->getFrom()->hasSameParentProductId($event->getTo()->getParentProductId())) {
                        $child_product_ids[$event->getTo()->getParentProductId()][$event->getTo()->getProductId()] = $event->getTo()->getProductId();
                    }
                } elseif ($event instanceof ParentProductChangedEvent) {
                    $from_group_product = $event->getFrom();
                    $to_group_product = $event->getTo();
                    $product_id = $to_group_product->getProductId();

                    $parent_product_ids[$product_id] = $product_id;

                    /**
                     * Executes after the parent product is changed;
                     * allows to perform additional actions.
                     *
                     * @param \Tygh\Addons\ProductVariations\Service                    $this               Instance of the service
                     * @param \Tygh\Addons\ProductVariations\Product\Group\Group        $group              Instance of variation group
                     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupProduct $from_group_product Instance of the old parent product
                     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupProduct $to_group_product   Instance of the new parent product
                     */
                    fn_set_hook('variation_group_mark_product_as_main_post', $this, $group, $from_group_product, $to_group_product);
                }
            }

            foreach ($parent_product_ids as $parent_product_id) {
                $this->identity_map_repository->changeParentProductId($parent_product_id, $group->getChildProductIds($parent_product_id));
            }

            if ($delete_product_ids) {
                $this->identity_map_repository->deleteByProductIds($delete_product_ids);
            }

            foreach ($child_product_ids as $parent_product_id => $product_ids) {
                if (!$parent_product_id) {
                    continue;
                }

                $this->sync_service->syncAll($parent_product_id, $product_ids);
            }

            /**
             * Executes after the variation group is saved;
             * allows to perform additional actions and react to events that occur to variation group
             *
             * @param \Tygh\Addons\ProductVariations\Service                       $this
             * @param \Tygh\Addons\ProductVariations\Product\Group\Group           $group
             * @param \Tygh\Addons\ProductVariations\Product\Group\Events\AEvent[] $events
             */
            fn_set_hook('variation_group_save_group', $this, $group, $events);

            $result->setSuccess(true);
            $result->setData($group, 'group');
        } catch (DatabaseException $exception) {
            $result->setSuccess(false);
            $result->addError($exception->getCode(), $exception->getMessage());
        } catch (DeveloperException $exception) {
            $result->setSuccess(false);
            $result->addError($exception->getCode(), $exception->getMessage());
        }
    }


    /**
     * Validates product identifier list
     *
     * @param OperationResult $result
     * @param array           $product_ids
     */
    protected function validateProductIds(OperationResult $result, array $product_ids)
    {
        if (empty($product_ids)) {
            $result->addError('product_ids', __('product_variations.error.product_ids_empty'));
            $result->setSuccess(false);
        }
    }

    /**
     * Validates variations group code
     *
     * @param OperationResult $result
     * @param string          $code
     * @param int|bool        $required
     * @param int|string      $group_id
     */
    protected function validateCode(OperationResult $result, $code, $required = false, $group_id = null)
    {
        if (!empty($code)) {
            if (preg_match('/[^-_a-z0-9]/i', $code)) {
                $result->addError('code', __('product_variations.error.group_code_is_invalid'));
                $result->setSuccess(false);
            } elseif ($this->group_repository->exists($code, $group_id)) {
                $result->addError('code', __('product_variations.error.group_code_is_duplicate'));
                $result->setSuccess(false);
            }
        } elseif ($required) {
            $result->addError('code', __('product_variations.error.group_code_is_required'));
            $result->setSuccess(false);
        }
    }

    /**
     * Validates variations group features
     *
     * @param OperationResult        $result
     * @param GroupFeatureCollection $features
     */
    protected function validateFeatures(OperationResult $result, GroupFeatureCollection $features)
    {
        if (!$features->count()) {
            $result->addError('features', __('product_variations.error.features_is_empty'));
            $result->setSuccess(false);
            return;
        }

        if ($result->getData('skip_purpose_validation')) {
            return;
        }

        $feature_ids = $features->getFeatureIds();
        $feature_purposes = fn_get_product_feature_purposes_by_feature_ids($feature_ids);

        $not_available_feature_purposes = array_filter($feature_purposes, static function ($feature_purpose) {
            if (
                FeaturePurposes::isCreateVariationOfCatalogItem($feature_purpose)
                || FeaturePurposes::isCreateCatalogItem($feature_purpose)
            ) {
                return false;
            }

            return true;
        });

        if (empty($not_available_feature_purposes)) {
            return;
        }

        $result->addError('features', __('product_variations.error.not_available_feature_purpose'));
        $result->setSuccess(false);
    }

    /**
     * Validates variations group
     *
     * @param OperationResult $result
     * @param int             $group_id
     * @param Group|null      $group
     */
    protected function validateGroup(OperationResult $result, $group_id, $group)
    {
        if (!$group) {
            $result->addError('group_id', __('product_variations.error.group_not_found', ['[id]' => $group_id]));
            $result->setSuccess(false);
        }
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Request\ABaseGenerateProductsRequest $request
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection $group_features
     * @param array                                                               $exists_product_ids
     *
     * @return array
     */
    protected function generateCombinations(
        ABaseGenerateProductsRequest $request,
        GroupFeatureCollection $group_features,
        array $exists_product_ids = []
    ) {
        array_unshift($exists_product_ids, $request->getBaseProductId());
        $exists_product_ids = array_unique($exists_product_ids);

        if ($request->getFeaturesVariantsMap()) {
            $feature_variant_collection = new GroupFeatureValueCollection();

            foreach ($request->getFeaturesVariantsMap() as $feature_id => $variant_ids) {
                foreach ($variant_ids as $variant_id) {
                    $feature_variant_collection->addFeatureValue(new GroupFeatureValue(
                        $feature_id,
                        $group_features->getFeaturePurpose($feature_id),
                        $variant_id
                    ));
                }
            }

            $combinations = $this->combinations_generator->generateByFeatureVariant(
                $feature_variant_collection,
                $exists_product_ids,
                $request->getCombinationsData()
            );
        } else {
            $combinations = $this->combinations_generator->generate(
                $group_features,
                $exists_product_ids,
                $request->getCombinationIds(),
                $request->getCombinationsData()
            );
        }

        foreach ($combinations as $key => $combination) {
            if (!$combination['active']) {
                unset($combinations[$key]);
            }
        }

        return $combinations;
    }

    protected function createProductByCombination(array $combination)
    {
        $parent_product_id = $combination['parent_product_id'] ? $combination['parent_product_id'] : $combination['base_product_id'];
        $combination_id = $combination['combination_id'];

        $product_data = [
            'parent_product_id' => 0,
            'timestamp'         => time(),
            'updated_timestamp' => time(),
            'product_code'      => $combination['product_code'],
            'product'           => $combination['product_name'],
            'price'             => $combination['product_price'],
            'amount'            => $combination['product_amount'],
            'product_type'      => Type::PRODUCT_TYPE_SIMPLE
        ];

        /**
         * Executes before a new product is created for a combination of features;
         * allows modifying data before the product is saved.
         *
         * @param \Tygh\Addons\ProductVariations\Service $this              Instance of the service
         * @param int                                    $parent_product_id Indentifier of the parent product
         * @param string                                 $combination_id    Identifier of the combination
         * @param array                                  $combination       Combination of features
         * @param array                                  $product_data      Data of the new product
         */
        fn_set_hook('variation_group_create_products_by_combinations_item',
            $this,
            $parent_product_id,
            $combination_id,
            $combination,
            $product_data
        );

        return $this->product_repository->createProduct($product_data);
    }

    protected function updateProductByCombiation($product_id, array $combination)
    {
        $this->product_repository->updateProduct($product_id, [
            'updated_timestamp' => time(),
            'product_code'      => $combination['product_code'],
            'product'           => $combination['product_name'],
            'price'             => $combination['product_price'],
            'amount'            => $combination['product_amount']
        ]);
    }

    /**
     * Checks whether the limit on products has been reached
     *
     * @param array<string, string|int|bool> $combinations Combination list
     * @param int                            $product_id   Product id
     * @param OperationResult                $result       Result of current operation
     *
     * @return OperationResult
     */
    protected function checkLimitNewProductVariations(array $combinations, $product_id, OperationResult $result)
    {
        $combinations_count = $this->getNewCombinationsAsOneProductCount($combinations);

        /**
         * Executes before returning the result of checking for reaching the vendor plan limit.
         *
         * @param \Tygh\Addons\ProductVariations\Service $this               Instance of the service
         * @param int                                    $combinations_count Count of new combinations being created
         * @param int                                    $product_id         Product id
         * @param \Tygh\Common\OperationResult           $result             Result of current operation
         */
        fn_set_hook('product_variations_check_limit_new_product_variations', $this, $combinations_count, $product_id, $result);

        $result->setSuccess(!$result->hasErrors());

        return $result;
    }

    /**
     * Gets the number of new combinations of variations as one product.
     *
     * @param array<string, string|int|bool> $combinations Combination list
     *
     * @return int
     */
    protected function getNewCombinationsAsOneProductCount(array $combinations)
    {
        return array_reduce($combinations, static function ($carry, $combination) {
            if (
                $combination['active']
                && !$combination['linked']
                && empty($combination['parent_combination_id'])
                && empty($combination['exists'])
            ) {
                $carry++;
            }

            return $carry;
        }, 0);
    }
}
