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

namespace Tygh\Addons\MasterProducts\Product;


/**
 * Class ProductIdMap
 *
 * @package Tygh\Addons\MasterProducts\Product
 */
class ProductIdMap
{
    /** @var int */
    const CHUNK_SIZE = 1000;

    /** @var array  */
    protected $master_product_id_map = [];

    /** @var array  */
    protected $company_product_id_map = [];

    /** @var array  */
    protected $master_product_ids = [];

    /** @var \Tygh\Addons\MasterProducts\Product\Repository */
    protected $repository;

    /** @var array  */
    protected $preload_group_product_ids = [];

    /** @var array  */
    protected $loaded_group_ids = [];

    /**
     * ProductIdMap constructor.
     *
     * @param \Tygh\Addons\MasterProducts\Product\Repository $repository
     */
    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Add product identifiers for preload
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
     * @param array $products
     */
    public function setMastertProductIdMapByProducts(array $products)
    {
        foreach ($products as $product) {
            if (!isset($product['master_product_id'], $product['company_id'])) {
                continue;
            }

            $this->setMasterProductId($product['product_id'], $product['master_product_id'], $product['company_id']);
        }
    }

    /**
     * @param int $product_id
     * @param int $master_product_id
     * @param int $company_id
     */
    public function setMasterProductId($product_id, $master_product_id, $company_id)
    {
        $product_id = (int) $product_id;
        $master_product_id = (int) $master_product_id;
        $company_id = (int) $company_id;

        $this->master_product_id_map[$product_id] = $master_product_id;
        $this->company_product_id_map[$product_id] = $company_id;

        if ($company_id === 0 && $master_product_id === 0) {
            $this->master_product_ids[$product_id] = $product_id;
        }
    }

    /**
     * @param int $product_id
     *
     * @return bool
     */
    public function isVendorProduct($product_id)
    {
        return !empty($this->getMasterProductId($product_id));
    }

    /**
     * @param int $product_id
     *
     * @return bool
     */
    public function isMasterProduct($product_id)
    {
        if (!isset($this->company_product_id_map[$product_id]) && !$this->loadMasterProductIdMapFromDbByGroup($product_id)) {
            $this->loadMasterProductIdMapFromDbById($product_id);
        }

        return isset($this->master_product_ids[$product_id]);
    }

    /**
     * @param int $product_id
     *
     * @return int
     */
    public function getVendorProductCompanyId($product_id)
    {
        return isset($this->company_product_id_map[$product_id]) ? $this->company_product_id_map[$product_id] : 0;
    }

    /**
     * @param int $product_id
     *
     * @return null|int
     */
    public function getMasterProductId($product_id)
    {
        $product_id = (int) $product_id;

        if (!$product_id) {
            return null;
        }

        $master_product_id = $this->getMasterProductIdById($product_id);

        if ($master_product_id !== null) {
            return $master_product_id;
        }

        if ($this->loadMasterProductIdMapFromDbByGroup($product_id)) {
            return $this->getMasterProductIdById($product_id);
        }

        $this->loadMasterProductIdMapFromDbById($product_id);

        return $this->getMasterProductIdById($product_id);
    }

    /**
     * @param int $product_id
     *
     * @return bool
     */
    protected function loadMasterProductIdMapFromDbByGroup($product_id)
    {
        $group_id = $this->findProductGroupId($product_id);

        if ($group_id === null|| !$this->preload_group_product_ids[$group_id]) {
            return false;
        }

        if (isset($this->loaded_group_ids[$group_id]) ) {
            return true;
        }

        $this->loaded_group_ids[$group_id] = true;

        $products = $this->repository->findVendorProductsInfo($this->preload_group_product_ids[$group_id]);

        foreach ($products as $vendor_product_id => $item) {
            $this->setMasterProductId($vendor_product_id, $item['master_product_id'], $item['company_id']);
        }

        return true;
    }

    /**
     * @param int $product_id
     */
    protected function loadMasterProductIdMapFromDbById($product_id)
    {
        $products = $this->repository->findVendorProductsInfo([$product_id]);

        foreach ($products as $vendor_product_id => $item) {
            $this->setMasterProductId($vendor_product_id, $item['master_product_id'], $item['company_id']);
        }
    }

    /**
     * @param int $product_id
     *
     * @return int|null
     */
    protected function getMasterProductIdById($product_id)
    {
        return isset($this->master_product_id_map[$product_id]) ? (int) $this->master_product_id_map[$product_id] : null;
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

    /**
     * Remove product identifiers from $master_product_ids
     *
     * @param array<int> $product_ids Product identifiers
     */
    public function removeMasterProductsFromMap(array $product_ids)
    {
        if (empty($product_ids)) {
            return;
        }

        foreach ($product_ids as $product_id) {
            unset($this->master_product_id_map[(int) $product_id]);
            unset($this->company_product_id_map[(int) $product_id]);
            unset($this->master_product_ids[(int) $product_id]);
        }
    }
}