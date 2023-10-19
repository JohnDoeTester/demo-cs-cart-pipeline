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

use Tygh\Addons\ProductVariations\Product\Group\Repository as GroupRepository;
use Tygh\Addons\ProductVariations\Product\Type\Type;

/**
 * This class implements the methods that help to check if the product is a parent or a child.
 * The goal is to minimize the number of SQL queries and offer a convenient way of determining what the product is.
 *
 * @package Tygh\Addons\ProductVariations\Product
 */
class ProductIdMap
{
    /** @var int */
    const CHUNK_SIZE = 1000;

    /** @var array  */
    protected $product_children_map = [];

    /** @var array  */
    protected $parent_product_id_map = [];

    /** @var \Tygh\Addons\ProductVariations\Product\Group\Repository */
    protected $repository;

    /** @var array  */
    protected $preload_group_product_ids = [];

    /** @var array  */
    protected $loaded_group_ids = [];

    /**
     * ProductIdMap constructor.
     *
     * @param \Tygh\Addons\ProductVariations\Product\Group\Repository $repository
     */
    public function __construct(GroupRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Adds the list of identifiers to the list of groups for loading.
     * If one of the identifiers requires checking if it's a child or a parent,
     *  then this information will be requested for the entire group of identifiers.
     *
     * @param array $product_ids
     */
    public function addProductIdsToPreload(array $product_ids)
    {
        $product_ids = array_filter($product_ids);

        if ($product_ids) {
            $chunk_product_ids = array_chunk($product_ids, self::CHUNK_SIZE);
            $this->preload_group_product_ids = array_merge($this->preload_group_product_ids, $chunk_product_ids);
        }
    }

    /**
     * Maps the parent and child products by an array of products.
     * If one of the products requires information if it's a parent or a child,
     *  this information will be received from the presented array of products.
     *
     * @param array $products
     */
    public function setParentProductIdMapByProducts(array $products)
    {
        foreach ($products as $product) {
            if (!isset($product['product_type'], $product['product_id'], $product['parent_product_id'])) {
                continue;
            }

            if ($product['product_type'] === Type::PRODUCT_TYPE_VARIATION) {
                $this->setParentProductId($product['product_id'], $product['parent_product_id']);
            } else {
                $this->setParentProductId($product['product_id'], 0);
            }
        }
    }

    /**
     * Map child products to parent products
     *
     * @param int $child_id
     * @param int $parent_id
     */
    public function setParentProductId($child_id, $parent_id)
    {
        $child_id = (int) $child_id;
        $parent_id = (int) $parent_id;

        $this->parent_product_id_map[$child_id] = $parent_id;
    }

    /**
     * Determines if the product is a child variation
     *
     * @param int $product_id
     *
     * @return bool
     */
    public function isChildProduct($product_id)
    {
        return !empty($this->getParentProductId($product_id));
    }

    /**
     * Determines if the product is a parent variation
     *
     * @param int $product_id
     *
     * @return bool
     */
    public function isParentProduct($product_id)
    {
        return $this->getParentProductId($product_id) === 0;
    }

    /**
     * Determines if the product is a variation
     *
     * @param int $product_id
     *
     * @return bool
     */
    public function isVariationProduct($product_id)
    {
        return $this->isChildProduct($product_id) || $this->isParentProduct($product_id);
    }

    /**
     * Gets the identifier of the parent product.
     * Returns null if the parent product doesn't exist.
     *
     * @param int $product_id
     *
     * @return null|int
     */
    public function getParentProductId($product_id)
    {
        $product_id = (int) $product_id;

        if (!$product_id) {
            return null;
        }

        $parent_product_id = $this->getParentProductIdById($product_id);

        if ($parent_product_id !== null) {
            return $parent_product_id;
        }

        if ($this->loadParentProductIdMapFromDbByGroup($product_id)) {
            return $this->getParentProductIdById($product_id);
        }

        $this->loadParentProductIdMapFromDbById($product_id);

        return $this->getParentProductIdById($product_id);
    }

    /**
     * Gets the list of identifiers of child variations.
     * Returns null if the product isn't a parent
     *
     * @param int  $product_id    Product ID
     * @param bool $force_refresh Is refreshing of the product children map required
     *
     * @return null|int[]
     */
    public function getProductChildrenIds($product_id, $force_refresh = false)
    {
        $product_id = (int) $product_id;

        if (!$product_id) {
            return null;
        }

        $this->loadProductChildrenFromDb($product_id, $force_refresh);

        return empty($this->product_children_map[$product_id]) ? null : $this->product_children_map[$product_id];
    }

    /**
     * Gets all products ids which is belong to the same variation group as product identifier
     *
     * @param int $product_id One of the sub group product identifier
     *
     * @return array All sub group product identifiers
     */
    public function getVariationSubGroupProductIds($product_id)
    {
        if (!$this->isVariationProduct($product_id)) {
            return [];
        }

        $parent_product_id = empty($this->getParentProductId($product_id)) ? $product_id : $this->getParentProductId($product_id);
        $child_product_ids = empty($this->getProductChildrenIds($parent_product_id)) ? [] : $this->getProductChildrenIds($parent_product_id);

        return array_merge([$parent_product_id], array_values($child_product_ids));
    }

    /**
     * @param int  $product_id    Product ID
     * @param bool $force_refresh Is refreshing of the product children map required
     *
     * @return void
     */
    protected function loadProductChildrenFromDb($product_id, $force_refresh)
    {
        if (!$force_refresh && isset($this->product_children_map[$product_id])) {
            return;
        }

        $this->product_children_map[$product_id] = $this->repository->getProductChildrenIds($product_id);
    }

    /**
     * @param int $product_id
     *
     * @return bool
     */
    protected function loadParentProductIdMapFromDbByGroup($product_id)
    {
        $group_id = $this->findProductGroupId($product_id);

        if ($group_id === null|| !$this->preload_group_product_ids[$group_id]) {
            return false;
        }

        if (isset($this->loaded_group_ids[$group_id]) ) {
            return true;
        }

        $this->loaded_group_ids[$group_id] = true;
        $map = $this->repository->getParentProductIdMap($this->preload_group_product_ids[$group_id]);

        foreach ($map as $child_id => $parent_id) {
            $this->setParentProductId($child_id, $parent_id);
        }

        return true;
    }

    /**
     * @param int $product_id
     */
    protected function loadParentProductIdMapFromDbById($product_id)
    {
        foreach ($this->repository->getParentProductIdMap([$product_id]) as $child_id => $parent_id) {
            $this->setParentProductId($child_id, $parent_id);
        }
    }

    /**
     * @param int $product_id
     *
     * @return int|null
     */
    protected function getParentProductIdById($product_id)
    {
        return isset($this->parent_product_id_map[$product_id]) ? (int) $this->parent_product_id_map[$product_id] : null;
    }

    /**
     * @param int $product_id
     *
     * @return int|null
     */
    protected function findProductGroupId($product_id)
    {
        foreach ($this->preload_group_product_ids as $group_id => $product_ids) {
            if (in_array($product_id, $product_ids)) {
                return $group_id;
            }
        }

        return null;
    }
}
