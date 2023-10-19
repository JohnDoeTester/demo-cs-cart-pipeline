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

namespace Tygh\Addons\ProductVariations\Product\Group;

use ArrayAccess;
use Iterator;
use Countable;

/**
 * Class GroupProductCollection
 *
 * @package Tygh\Addons\ProductVariations\Product\Group
 */
class GroupProductCollection implements ArrayAccess, Iterator, Countable
{
    /** @var \Tygh\Addons\ProductVariations\Product\Group\GroupProduct[] */
    protected $products = [];

    /** @var \Tygh\Addons\ProductVariations\Product\Group\GroupProduct[] */
    protected $combinations_map = [];

    /** @var \Tygh\Addons\ProductVariations\Product\Group\GroupProduct[] */
    protected $group_combinations_map = [];

    /** @var array<int, array<int, int>> */
    protected $feature_variants_map = [];

    /**
     * GroupProductCollection constructor
     *
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupProduct[] $group_products
     */
    public function __construct(array $group_products = [])
    {
        $this->setProducts($group_products);
    }

    /**
     * Sets group feature list
     *
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupProduct[] $group_products
     */
    public function setProducts(array $group_products)
    {
        foreach ($group_products as $group_product) {
            $this->addProduct($group_product);
        }
    }

    /**
     * Gets product identifier list
     *
     * @return int[]
     */
    public function getProductIds()
    {
        return array_keys($this->products);
    }

    /**
     * Gets group features
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupProduct[]
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * @param int    $product_id
     *
     * @return bool
     */
    public function hasProduct($product_id)
    {
        return isset($this->products[$product_id]);
    }

    /**
     * @param int $product_id
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupProduct|null
     */
    public function getProduct($product_id)
    {
        return $this->hasProduct($product_id) ? $this->products[$product_id] : null;
    }

    /**
     * Gets product by combination ID
     *
     * @param string $combination_id Combination ID
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupProduct|null
     */
    public function getProductByCombinationId($combination_id)
    {
        return isset($this->combinations_map[$combination_id])
            ? $this->combinations_map[$combination_id]
            : null;
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupProduct $product
     *
     * @return bool
     */
    public function hasCombination(GroupProduct $product)
    {
        return isset($this->combinations_map[$product->getCombinationId()]);
    }

    /**
     * Checks if combination exists at the product collection
     *
     * @param string $combination_id Combination ID
     *
     * @return bool
     */
    public function hasCombinationByCombinationId($combination_id)
    {
        return isset($this->combinations_map[$combination_id]);
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupProduct $product
     *
     * @return bool
     */
    public function isCombinationBelongsToProduct(GroupProduct $product)
    {
        return $this->hasCombination($product)
            && $this->combinations_map[$product->getCombinationId()]->getProductId() == $product->getProductId();
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupProduct $product
     *
     * @return int
     */
    public function getParentProductIdViaCombination(GroupProduct $product)
    {
        if (!$product->hasCreateVariationOfCatalogItemFeature()) {
            return 0;
        }

        $group_combination_id = $product->getGroupCombinationId();

        $product_id = isset($this->group_combinations_map[$group_combination_id])
            ? $this->group_combinations_map[$group_combination_id]->getProductId()
            : 0;

        if ($product_id === $product->getProductId()) {
            $product_id = 0;
        }

        return $product_id;
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupProduct $product
     */
    public function unsetProduct(GroupProduct $product)
    {
        unset($this->products[$product->getProductId()]);
        unset($this->combinations_map[$product->getCombinationId()]);

        if ($product->hasCreateVariationOfCatalogItemFeature() && !$product->hasParentProductId()) {
            unset($this->group_combinations_map[$product->getGroupCombinationId()]);
        }

        foreach ($product->getFeatureValues() as $feature_value) {
            unset($this->feature_variants_map[$feature_value->getVariantId()][$product->getProductId()]);

            if (empty($this->feature_variants_map[$feature_value->getVariantId()])) {
                unset($this->feature_variants_map[$feature_value->getVariantId()]);
            }
        }
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupProduct $product
     */
    public function addProduct(GroupProduct $product)
    {
        $this->products[$product->getProductId()] = $product;
        $this->combinations_map[$product->getCombinationId()] = $product;

        if (
            $product->hasCreateVariationOfCatalogItemFeature()
            && !$product->hasParentProductId()
            && !isset($this->group_combinations_map[$product->getGroupCombinationId()])
        ) {
            $this->group_combinations_map[$product->getGroupCombinationId()] = $product;
        }

        foreach ($product->getFeatureValues() as $feature_value) {
            $this->feature_variants_map[$feature_value->getVariantId()][$product->getProductId()] = $product->getProductId();
        }
    }

    /**
     * @param int $product_id
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupProduct[]
     */
    public function getProductChildren($product_id)
    {
        $result = [];
        $product = $this->getProduct($product_id);

        if (!$product || $product->getParentProductId()) {
            return $result;
        }

        foreach ($this->products as $item) {
            if ($product->getProductId() == $item->getParentProductId()) {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * @param int $product_id
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupProduct|null
     */
    public function getFirstProductChildren($product_id)
    {
        $product = $this->getProduct($product_id);

        if (!$product || $product->getParentProductId()) {
            return null;
        }

        foreach ($this->products as $item) {
            if ($product->getProductId() == $item->getParentProductId()) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Gets features vairant IDs that used to the products collection
     *
     * @return array<int, int>
     */
    public function getFeaturesVariantIds()
    {
        return array_keys($this->feature_variants_map);
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->products);
    }

    /**
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureValue $feature_value
     *
     * @return bool
     */
    public function hasFeatureValue(GroupFeatureValue $feature_value)
    {
        return isset($this->feature_variants_map[$feature_value->getVariantId()]);
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        return current($this->products);
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function next()
    {
        return next($this->products);
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function key()
    {
        return key($this->products);
    }

    /**
     * @inheritDoc
     */
    public function valid(): bool
    {
        return key($this->products) !== null;
    }

    /**
     * @inheritDoc
     */
    public function rewind(): void
    {
        reset($this->products);
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset): bool
    {
        return isset($this->products[$offset]);
    }

    /**
     * @inheritDoc
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->getProduct($offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value): void
    {
        $this->addProduct($value);
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset): void
    {
        $product = $this->getProduct($offset);

        if ($product) {
            $this->unsetProduct($product);
        }
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return count($this->products);
    }

    /**
     * @param bool $deep
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupProduct[]
     */
    public function toArray($deep = false)
    {
        if (!$deep) {
            return $this->products;
        }

        return array_map(function (GroupProduct $group_product) {
            return $group_product->toArray();
        }, $this->products);
    }

    /**
     * @param array                                                               $products
     * @param array                                                               $products_feature_values
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection $features
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupProductCollection
     * @throws \Tygh\Exceptions\InputException
     */
    public static function createFromStorageDataList(array $products, array $products_feature_values, GroupFeatureCollection $features)
    {
        $self = new self();

        foreach ($products as $product) {
            $feature_values = isset($products_feature_values[$product['product_id']]) ? $products_feature_values[$product['product_id']] : [];
            $group_product = GroupProduct::createFromStorageData($product, $feature_values, $features);

            $self->addProduct($group_product);
        }

        return $self;
    }

    /**
     * @param array                                                               $products
     * @param \Tygh\Addons\ProductVariations\Product\Group\GroupFeatureCollection $group_features
     *
     * @return \Tygh\Addons\ProductVariations\Product\Group\GroupProductCollection
     * @throws \Tygh\Exceptions\InputException
     */
    public static function createFromProducts(array $products, GroupFeatureCollection $group_features = null)
    {
        $self = new self();

        foreach ($products as $product) {
            $group_product = GroupProduct::createFromProduct($product, $group_features);

            $self->addProduct($group_product);
        }

        return $self;
    }
}
