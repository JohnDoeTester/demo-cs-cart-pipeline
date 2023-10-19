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


namespace Tygh\Addons\Warehouses\CommerceML\Dto;


use ArrayIterator;
use IteratorAggregate;
use Countable;
use Tygh\Addons\CommerceML\Dto\ProductPropertyValue;
use Tygh\Addons\CommerceML\Dto\IdDto;

/**
 * Class ProductWarehouseQuantityDtoCollection
 *
 * @package Tygh\Warehouses\Addons\CommerceML\Dto
 *
 * phpcs:disable SlevomatCodingStandard.TypeHints.DisallowMixedTypeHint
 */
class ProductWarehouseQuantityDtoCollection implements IteratorAggregate, Countable, ProductPropertyValue
{
    /**
     * @var array<\Tygh\Addons\Warehouses\CommerceML\Dto\ProductWarehouseQuantityDto>
     */
    private $collections = [];

    /**
     * Adds product warehouse quantity to collection
     *
     * @param \Tygh\Addons\Warehouses\CommerceML\Dto\ProductWarehouseQuantityDto $warehouse_quantity Product warehouse quantity instance
     */
    public function add(ProductWarehouseQuantityDto $warehouse_quantity)
    {
        $this->collections[$warehouse_quantity->warehouse_id->getId()] = $warehouse_quantity;
    }

    /**
     * Checks if collection has the product warehouse quantity object
     *
     * @param string $warehouse_id External or local warehouse ID
     *
     * @return bool
     */
    public function has($warehouse_id)
    {
        $warehouse_id = (string) $warehouse_id;

        return isset($this->collections[$warehouse_id]);
    }

    /**
     * Removes product warehouse quantity object from collection
     *
     * @param string $warehouse_id External or local feature ID
     */
    public function remove($warehouse_id)
    {
        $warehouse_id = (string) $warehouse_id;

        unset($this->collections[$warehouse_id]);
    }

    /**
     * Gets product warehouse quantity object from collection
     *
     * @param string $warehouse_id  External or local warehouse ID
     * @param int    $default_value If collection has not property, then method will return new PropertyDto
     *                              where $default_value used as value on new object
     *
     * @return \Tygh\Addons\Warehouses\CommerceML\Dto\ProductWarehouseQuantityDto
     */
    public function get($warehouse_id, $default_value = 0)
    {
        $warehouse_id = (string) $warehouse_id;

        if (!$this->has($warehouse_id)) {
            return ProductWarehouseQuantityDto::create(IdDto::createByExternalId($warehouse_id), $default_value);
        }

        return $this->collections[$warehouse_id];
    }

    /**
     * Gets all product warehouse
     *
     * @return array<\Tygh\Addons\Warehouses\CommerceML\Dto\ProductWarehouseQuantityDto>
     */
    public function getAll()
    {
        return $this->collections;
    }

    /**
     * Merges current collection with $collection
     *
     * @param \Tygh\Addons\Warehouses\CommerceML\Dto\ProductWarehouseQuantityDtoCollection $collection Product warehouse collection instance
     */
    public function mergeWith(ProductWarehouseQuantityDtoCollection $collection)
    {
        foreach ($collection as $item) {
            $this->add($item);
        }
    }

    /**
     * @return array<int|string, int> Warehouse quantity values
     */
    public function getPropertyValue()
    {
        $warehouses = [];

        /** @var \Tygh\Addons\Warehouses\CommerceML\Dto\ProductWarehouseQuantityDto $warehouse_quantity_dto */
        foreach ($this->getAll() as $warehouse_quantity_dto) {
            if (empty($warehouse_quantity_dto->warehouse_id->local_id)) {
                continue;
            }
            $quantity = ($warehouse_quantity_dto->quantity < 0) ? 0 : (int) $warehouse_quantity_dto->quantity;
            if (!isset($warehouses[$warehouse_quantity_dto->warehouse_id->local_id])) {
                $warehouses[$warehouse_quantity_dto->warehouse_id->local_id] = $quantity;
            } else {
                $warehouses[$warehouse_quantity_dto->warehouse_id->local_id] += $quantity;
            }
        }

        return $warehouses;
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        return new ArrayIterator($this->collections);
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        return count($this->collections);
    }
}
