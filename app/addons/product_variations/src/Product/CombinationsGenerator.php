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

namespace Tygh\Addons\ProductVariations\Product;

use Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection;
use Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValueCollection;

/**
 * Class CombinationsGenerator
 *
 * @package Tygh\Addons\ProductVariations\Product
 */
class CombinationsGenerator
{
    /**
     * @var \Tygh\Addons\ProductVariations\Product\Repository
     */
    protected $product_repository;

    /**
     * @var bool
     */
    protected $defaul_is_active = true;

    /**
     * CombinationsGenerator constructor.
     *
     * @param \Tygh\Addons\ProductVariations\Product\Repository $product_repository
     */
    public function __construct(Repository $product_repository)
    {
        $this->product_repository = $product_repository;
    }

    /**
     * @param bool $defaul_is_active
     */
    public function setDefaulIsActive($defaul_is_active)
    {
        $this->defaul_is_active = (bool) $defaul_is_active;
    }

    public function generateByFeatureVariant(
        GroupFeatureValueCollection $feature_variants,
        array $exists_product_ids = [],
        array $combinations_data = []
    ) {
        $result = [];

        $group_features = $feature_variants->getFeatureCollection();
        $features = $this->getFeatures($feature_variants->getFeatureCollection());

        if (empty($features)) {
            return $result;
        }

        $combinations = $this->combineFeatureVariants($features, $feature_variants->getFeatureVariantIds());
        $products = $this->getProducts($exists_product_ids, $group_features);

        return $this->populateCombinations($combinations, $features, $products, $combinations_data);
    }

    public function generate(
        GroupFeatureCollection $group_features,
        array $exists_product_ids = [],
        array $filter_combination_ids = [],
        array $combinations_data = []
    ) {
        $result = [];

        $features = $this->getFeatures($group_features);

        if (empty($features)) {
            return $result;
        }

        $combinations = $this->combineFeatureVariants(
            $features,
            $this->getVariantIdsFromCombinationIds($filter_combination_ids)
        );
        $products = $this->getProducts($exists_product_ids, $group_features);

        return $this->populateCombinations(
            $combinations,
            $features,
            $products,
            $combinations_data,
            $filter_combination_ids
        );
    }

    protected function getFeatures(GroupFeatureCollection $features)
    {
        $result = $this->product_repository->findFeaturesByFeatureCollection($features);

        if (empty($result)) {
            return $result;
        }

        return $this->product_repository->loadFeaturesVariants($result);
    }

    protected function combineFeatureVariants(array $features, array $filter_variant_ids = [])
    {
        $stack_features = $features;
        $combinations = [];

        while ($stack_features) {
            reset($stack_features);
            $feature = (array) array_shift($stack_features);
            $variants = $feature['variants'];

            if (!$combinations) {
                foreach ($variants as $variant) {
                    if ($filter_variant_ids && !in_array($variant['variant_id'], $filter_variant_ids)) {
                        continue;
                    }

                    $combinations[][$feature['feature_id']] = $variant['variant_id'];
                }
            } else {
                $tmp_combinations = [];

                foreach ($variants as $variant) {
                    if ($filter_variant_ids && !in_array($variant['variant_id'], $filter_variant_ids)) {
                        continue;
                    }
                    foreach ($combinations as $item) {
                        $tmp_combinations[] = $item + [$feature['feature_id'] => $variant['variant_id']];
                    }
                    unset($item);
                }

                $combinations = $tmp_combinations;
            }
        }

        return $combinations;
    }

    protected function populateCombinations(
        array $combinations,
        array $features,
        array $products,
        array $data = [],
        array $filter_combination_ids = []
    ) {
        $result = [];
        $variation_product_feature_ids = $this->getFeaturesIdsByPurpose(
            $features,
            FeaturePurposes::CREATE_VARIATION_OF_CATALOG_ITEM
        );
        list($exists_combination_ids, $exists_parent_combination_ids)
            = $this->getCombinationIdsMapFromProducts($products);

        foreach ($combinations as $combination) {
            $key = $this->product_repository->generateCombinationId(array_values($combination));

            if ($filter_combination_ids && !in_array($key, $filter_combination_ids, true)) {
                continue;
            }

            $group_vriant_ids = array_diff_key($combination, array_flip($variation_product_feature_ids));
            $name_parts = [];

            $group_combination_id = $this->product_repository->generateCombinationId($group_vriant_ids);

            $item = [
                'active'                    => $this->defaul_is_active,
                'updated'                   => false,
                'combination_id'            => $key,
                'selected_variants'         => $combination,
                'variants_position'         => $this->getVariantsPosition($combination, $features),
                'variant_names'             => [],
                'name'                      => '',
                'group_name'                => '',
                'exists'                    => false,
                'linked'                    => false,
                'has_children'              => false,
                'product_id'                => 0,
                'product_name'              => '',
                'product_code'              => '',
                'product_price'             => 0,
                'product_amount'            => 0,
                'parent_product_id'         => 0,
                'base_product_id'           => 0,
                'parent_combination_id'     => null,
                'parent_combination_exists' => null,
                'group_combination_id'      => $group_combination_id,
                'group_position'            => $this->getVariantsPosition($group_vriant_ids, $features),
            ];

            if (isset($exists_combination_ids[$key])) {
                $product = $products[$exists_combination_ids[$key]];
                $item = $this->populdateCombinationByProduct($item, $product);
            } elseif (isset($exists_parent_combination_ids[$group_combination_id])) {
                $product = $products[$exists_parent_combination_ids[$group_combination_id]];
                $item = $this->populdateCombinationByParentProduct($item, $product);
            } elseif ($products) {
                $product = reset($products);
                $item = $this->populdateCombinationByBaseProduct($item, $product);
            }

            foreach ($combination as $feature_id => $variant_id) {
                $feature = $features[$feature_id];
                $variant = $feature['variants'][$variant_id];

                $name_parts[] = sprintf('%s: %s', $feature['description'], $variant['variant']);
                $item['variant_names'][$variant_id] = $variant['variant'];
            }

            $item['group_name'] = reset($name_parts);
            $item['name'] = implode(', ', $name_parts);

            $result[$key] = $item;
        }

        $result = $this->bindCombinations($result, $products);
        $result = $this->populateCombinationsByCombinationsData($result, $data);
        $result = $this->sortCombinations($result);
        $result = $this->bindBaseProduct($result, $products);

        return $result;
    }

    protected function populdateCombinationByProduct(array $combination, array $product)
    {
        return array_merge($combination, [
            'active'            => true,
            'exists'            => true,
            'linked'            => true,
            'product_id'        => $product['product_id'],
            'product_name'      => $product['product'],
            'product_code'      => $product['product_code'],
            'product_price'     => $product['price'],
            'product_amount'    => $product['amount'],
            'parent_product_id' => $product['parent_product_id'],
            'base_product_id'   => $product['product_id'],
        ]);
    }

    protected function populdateCombinationByParentProduct(array $combination, array $product)
    {
        return array_merge($combination, [
            'product_name'      => $product['product'],
            'product_code'      => $this->generateProductCode($product['product_code']),
            'product_price'     => $product['price'],
            'product_amount'    => $product['amount'],
            'parent_product_id' => $product['product_id'],
            'base_product_id'   => $product['product_id'],
        ]);
    }

    protected function populdateCombinationByBaseProduct(array $combination, array $product)
    {
        return array_merge($combination, [
            'product_name'      => $product['product'],
            'product_code'      => $this->generateProductCode($product['product_code']),
            'product_price'     => $product['price'],
            'product_amount'    => $product['amount'],
            'base_product_id'   => $product['product_id'],
        ]);
    }

    protected function populateCombinationsByCombinationsData(array $combinations, array $combinations_data)
    {
        foreach ($combinations_data as $combination_id => $data) {
            $combination_id = (string) $combination_id;

            if (!isset($combinations[$combination_id])) {
                continue;
            }

            if (!empty($data['set_as_default'])) {
                $combinations = $this->setDefaultCombination($combination_id, $combinations);
            }

            if (isset($data['active'])) {
                $combinations = $this->setCombinationActivity($combination_id, $data['active'], $combinations);
            }

            $combinations = $this->setCombinationUserData($combination_id, $data, $combinations);
        }

        return $combinations;
    }

    protected function bindCombinations(array $combinations, array $products)
    {
        foreach ($combinations as &$combination) {
            $combination['parent_combination_id'] = $this->getCombinationParentId(
                $combination,
                $combinations,
                $products
            );

            if ($combination['parent_combination_id']) {
                $combination['parent_combination_exists'] = $combinations[$combination['parent_combination_id']]['exists'];
            }
        }
        unset($combination);

        foreach ($combinations as &$combination) {
            $combination['has_children'] = $this->hasCombinationChildren($combination, $combinations);
        }
        unset($combination);

        return $combinations;
    }

    protected function bindBaseProduct(array $combinations, array $products)
    {
        $base_product = reset($products);

        if (!empty($base_product['variation_combination_id'])) {
            return $combinations;
        }

        foreach ($combinations as &$combination) {
            if (!empty($combination['product_id']) || empty($combination['active'])) {
                continue;
            }

            $combination['product_id'] = $base_product['product_id'];

            if (!$combination['updated']) {
                $combination['product_code'] = $base_product['product_code'];
            }

            $combination['exists'] = true;
            $combination['updated'] = true;
            break;
        }
        unset($combination);

        return $combinations;
    }

    protected function sortCombinations(array $combinations)
    {
        $combinations_positions = [];

        foreach ($combinations as $key => $combination) {
            $combinations_positions[$key] = implode('_', [
                $combination['group_position'],
                $combination['parent_combination_id'],
                $combination['variants_position']
            ]);
        }

        array_multisort($combinations_positions, $combinations, SORT_NATURAL);

        return $combinations;
    }

    protected function setCombinationActivity($combination_id, $active, array $combinations)
    {
        if (!isset($combinations[$combination_id])) {
            return $combinations;
        }

        $active = (bool) $active;
        $combination = $combinations[$combination_id];
        $parent_combination_id = $combination['parent_combination_id'];

        if (isset($combinations[$parent_combination_id]) && !$combinations[$parent_combination_id]['active']) {
            $active = false;
        }

        if ($combination['exists'] || $combination['active'] === $active) {
            return $combinations;
        }

        $combinations[$combination_id]['active'] = $active;
        $combinations[$combination_id]['updated'] = true;

        if (!$active) {
            foreach ($combinations as &$item) {
                if ($item['parent_combination_id'] !== $combination_id) {
                    continue;
                }
                $item['active'] = $active;
                $item['updated'] = true;
            }
            unset($item);
        }

        return $combinations;
    }

    protected function setDefaultCombination($combination_id, array $combinations)
    {
        if (!isset($combinations[$combination_id])) {
            return $combinations;
        }

        $combination = $combinations[$combination_id];
        $parent_combination_id = $combination['parent_combination_id'];
        $group_combination_id = $combination['group_combination_id'];

        // Setting combination as default is possible under the following conditions:
        // 1. Combination is not created
        // 2. Parent combination is not created
        if (
            $combination['exists']
            || !isset($combinations[$parent_combination_id])
            || $combinations[$parent_combination_id]['exists']
        ) {
            return $combinations;
        }

        $current_parent_combination = $combinations[$parent_combination_id];

        foreach ($combinations as &$item) {
            if ($item['combination_id'] === $combination_id) {
                $item['product_name'] = $current_parent_combination['product_name'];
                $item['has_children'] = $current_parent_combination['has_children'];
                $item['parent_combination_id'] = '0';
                $item['updated'] = true;
            } elseif ($item['group_combination_id'] === $group_combination_id) {
                $item['parent_combination_id'] = $combination_id;
                $item['updated'] = true;
                $item['has_children'] = false;
            }
        }
        unset($item);

        return $combinations;
    }

    protected function setCombinationUserData($combination_id, array $data, array $combinations)
    {
        if (!isset($combinations[$combination_id])) {
            return $combinations;
        }

        $combination = &$combinations[$combination_id];

        foreach ($data as $key => $value) {
            if (!in_array($key, ['product_name', 'product_code', 'product_price', 'product_amount'])) {
                continue;
            }

            if ($value != $combination[$key]) {
                $combination[$key] = $value;
                $combination['updated'] = true;
            }
        }

        return $combinations;
    }

    protected function getCombinationParentId(array $combination, array $combinations, array $products)
    {
        $product_id = $combination['product_id'];
        $parent_product_id = $combination['parent_product_id'];

        if (isset($products[$parent_product_id]['variation_combination_id'])) {
            $combination_id = $products[$parent_product_id]['variation_combination_id'];

            if (isset($combinations[$combination_id])) {
                return $combination_id;
            }
        } elseif (isset($products[$product_id])) {
            return 0;
        }

        $group_combination_id = $combination['group_combination_id'];
        $group_combinations = [];

        foreach ($combinations as $key => $item) {
            if ($item['group_combination_id'] != $group_combination_id) {
                continue;
            }

            if (!empty($item['parent_combination_id'])) {
                return $item['parent_combination_id'] === $combination['combination_id'] ? '0' : $item['parent_combination_id'];
            } elseif ($item['parent_combination_id'] === '0') {
                return $item['combination_id'];
            }

            $group_combinations[$key] = $item['variants_position'];
        }

        natsort($group_combinations);
        $combination_id = (string) key($group_combinations);

        return $combination_id === $combination['combination_id'] ? '0' : $combination_id;
    }

    protected function getFeaturesIdsByPurpose($features, $purpose)
    {
        $result = [];

        foreach ($features as $key => $feature) {
            if ($feature['purpose'] === $purpose) {
                $result[] = $feature['feature_id'];
            }
        }

        return $result;
    }

    protected function getVariantIdsFromCombinationIds(array $filter_combination_ids = [])
    {
        $filter_variant_ids = [];

        foreach ($filter_combination_ids as $combination_id) {
            $filter_variant_ids = array_merge(
                $filter_variant_ids,
                $this->product_repository->getVariantIdsFromCombinationId($combination_id)
            );
        }

        return array_unique($filter_variant_ids);
    }

    protected function getProducts(array $product_ids, GroupFeatureCollection $group_features)
    {
        if (empty($product_ids)) {
            return [];
        }

        $products = $this->product_repository->findProducts($product_ids);
        $products = $this->product_repository->loadProductsFeatures($products, $group_features);
        $products = $this->product_repository->generateProductsCombinationId($products);

        return $products;
    }

    protected function getCombinationIdsMapFromProducts(array $products)
    {
        $exists_combination_ids = $exists_parent_combination_ids = [];

        foreach ($products as $product) {
            $exists_combination_ids[$product['variation_combination_id']] = $product['product_id'];

            if (empty($product['parent_product_id'])) {
                $exists_parent_combination_ids[$product['parent_variation_combination_id']] = $product['product_id'];
            }
        }

        return [$exists_combination_ids, $exists_parent_combination_ids];
    }

    protected function getVariantsPosition(array $variants_map, array $features)
    {
        $positions = [];

        foreach ($features as $feature) {
            $feature_id = $feature['feature_id'];
            $variant_id = isset($variants_map[$feature_id]) ? $variants_map[$feature_id] : null;

            if (empty($feature['variants'][$variant_id])) {
                continue;
            }

            $positions[$feature_id] = implode('_', [$feature['variants'][$variant_id]['position'], $variant_id]);
        }

        return implode('_', $positions);
    }

    protected function generateProductCode($product_code)
    {
        $product_code_part = array_filter(explode('_', $product_code));

        return $product_code_part
            ? sprintf('%s_%s', reset($product_code_part), strtoupper(substr(uniqid(), -4)))
            : strtoupper(uniqid());
    }

    protected function hasCombinationChildren($combination, array $combinations)
    {
        if ($combination['parent_combination_id']) {
            return false;
        }

        foreach ($combinations as $item) {
            if ((string) $item['parent_combination_id'] === (string) $combination['combination_id']) {
                return true;
            }
        }

        return false;
    }
}
