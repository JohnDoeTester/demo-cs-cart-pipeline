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

namespace Tygh\Addons\Warehouses;

class ProductStock
{
    /** @var int Product identifier */
    protected $product_id;

    /** @var ProductWarehouse[] */
    protected $product_warehouses = [];

    /** @var bool */
    protected $is_stock_split_by_warehouses = false;

    public function __construct($product_id, array $warehouses_amounts, $is_stock_split_by_warehouses = null)
    {
        $this->product_id = (int) $product_id;
        $this->initializeAmounts($warehouses_amounts);
        $this->is_stock_split_by_warehouses = !empty($this->product_warehouses) || $is_stock_split_by_warehouses;
    }

    /**
     * Determines if product has its stock split between any warehouses (at least one)
     *
     * @return bool
     */
    public function hasStockSplitByWarehouses()
    {
        return $this->is_stock_split_by_warehouses;
    }

    /**
     * Fetches product identifier
     *
     * @return mixed
     */
    public function getProductId()
    {
        return $this->product_id;
    }

    /**
     * Fetches product overall amount
     *
     * @return bool|int
     */
    public function getAmount()
    {
        return $this->getProductAmount($this->product_warehouses);
    }

    /**
     * Fetches product amount from active warehouses
     *
     * @return bool|int
     */
    public function getAmountFromActiveWarehouses()
    {
        $active_warehouses = $this->getActiveWarehouses();
        return $this->getProductAmount($active_warehouses);
    }

    /**
     * Fetches product amount that is available for provided destination
     *
     * @param int  $destination_id                 Destination identifier
     * @param bool $check_destination_availability Flag for checking availability of warehouses in destination
     *
     * @return bool|int
     */
    public function getAmountForDestination($destination_id, $check_destination_availability = false)
    {
        $warehouses = $this->getWarehousesForShippingInDestination($destination_id);

        if ($check_destination_availability && empty($warehouses)) {
            return false;
        }

        return $this->getProductAmount($warehouses);
    }

    /**
     * Fetches product amount that is available for provided warehouse
     *
     * @param int $warehouse_id Warehouse identifier
     *
     * @return false|int
     */
    public function getAmountForWarehouse($warehouse_id)
    {
        $warehouse = $this->getWarehousesById($warehouse_id);

        return $this->getProductAmount($warehouse);
    }

    /**
     * Converts stock data to an array
     *
     * @param array<ProductWarehouse> $stock Optional stock for transferring to array.
     *
     * @return array<int, array<string,int>>
     */
    public function getStockAsArray(array $stock = [])
    {
        $product_warehouses = [];
        if (empty($stock)) {
            $stock = $this->product_warehouses;
        }

        /** @var \Tygh\Addons\Warehouses\ProductWarehouse $product_warehouse */
        foreach ($stock as $warehouse) {
            $product_warehouses[$warehouse->getWarehouseId()] = $warehouse->getStockAsArray();
        }

        return $product_warehouses;
    }

    /**
     * Sets amount of product for specified warehouse
     *
     * @param int $warehouse_id Warehouse identifier
     * @param int $amount       Amount of product
     * @return $this
     */
    public function setAmountForWarehouse($warehouse_id, $amount)
    {
        $warehouse = $this->getWarehousesById($warehouse_id);
        $warehouse = reset($warehouse);
        $warehouse->setAmount($amount);

        return $this;
    }

    /**
     * Selects a function for reduce product stock
     *
     * @param string                                                                           $stock_holding_method Type of accounting product stock.
     * @param int                                                                              $amount               Product amount
     * @param array{pickup_point_id?: int, warehouses?: array<int, int>, destination_id?: int} $params               Array of parameters for reducing stock process.
     *
     * @return array<int, int>|null
     */
    public function reduceAmount($stock_holding_method, $amount, array $params = [])
    {
        switch ($stock_holding_method) {
            case 'store':
                $pickup_point_id = $params['pickup_point_id'] ?? 0;
                return $this->reduceStockByAmountForStore($amount, $pickup_point_id);
            case 'warehouses':
                $store_ids = $params['warehouses'] ?? [];
                return $this->reduceStockByAmountForWarehouses($amount, $store_ids);
            case 'destination':
                $destination_id = $params['destination_id'] ?? 0;
                return $this->reduceStockByAmountForDestination($amount, $destination_id);
            case 'amount':
            default:
                return $this->reduceStockByAmount($amount);
        }
    }

    /**
     * Sorts used in order warehouses by current priority for specified destination.
     *
     * @param int             $destination_id            Specified destination.
     * @param array<int, int> $changed_warehouse_amounts Used warehouses with specified product amounts.
     * @param bool            $should_be_reverse         Flag to reverse result.
     *
     * @return array<int, int>
     */
    public function sortUsedWarehousesByDestinationPriority($destination_id, array $changed_warehouse_amounts, $should_be_reverse = false)
    {
        $warehouse_sorted_stock = $this->getStockAsArray($this->getWarehousesForDestination($destination_id));
        $sorted_changed_warehouses = [];
        foreach (array_keys($warehouse_sorted_stock) as $warehouse_id) {
            //phpcs:ignore
            if (isset($changed_warehouse_amounts[$warehouse_id])) {
                $sorted_changed_warehouses[$warehouse_id] = $changed_warehouse_amounts[$warehouse_id];
            }
        }
        $changed_warehouse_amounts = $sorted_changed_warehouses;
        if ($should_be_reverse) {
            $changed_warehouse_amounts = array_reverse($changed_warehouse_amounts, true);
        }
        return $changed_warehouse_amounts;
    }

    /**
     * Selects a function for increase product stock
     *
     * @param string     $stock_holding_method Type of accounting product stock.
     * @param int        $amount               Product amount
     * @param array<int> $store_ids            Pickup point identifiers from order
     * @param int        $destination_id       Destination identifier from order
     *
     * @return $this|array<int, int>|null
     */
    public function increaseAmount($stock_holding_method, $amount, array $store_ids, $destination_id = 0)
    {
        switch ($stock_holding_method) {
            case 'store':
                $pickup_point_id = reset($store_ids);
                return $this->increaseStockByAmountForStore($amount, $pickup_point_id);
            case 'destination':
                return $this->increaseStockByAmountForDestination($amount, $destination_id);
            case 'warehouses':
                return $this->increaseStockForSpecifiedWarehouses($amount, $store_ids, $destination_id);
            case 'amount':
            default:
                return $this->increaseStockByAmount($amount);
        }
    }

    /**
     * Increases product stock
     *
     * @param int                                        $amount     Product amount
     * @param \Tygh\Addons\Warehouses\ProductWarehouse[] $warehouses Product warehouse data
     *
     * @return $this
     */
    public function increaseStock($amount, array $warehouses)
    {
        $first_warehouse = $warehouses[0];

        $new_amount = $first_warehouse->getAmount() + $amount;
        $first_warehouse->setAmount($new_amount);

        return $this;
    }

    /**
     * Increases product stock by provided amount
     *
     * @param int $amount Product amount
     *
     * @return $this
     */
    public function increaseStockByAmount($amount)
    {
        /** @var \Tygh\Addons\Warehouses\ProductWarehouse $warehouses */
        $warehouses = $this->product_warehouses;

        return $this->increaseStock($amount, (array) $warehouses);
    }

    /**
     * Increases product stock by provided amount from warehouses that available for provided destination
     *
     * @param int $amount         Product amount
     * @param int $destination_id Destination identifier
     *
     * @return $this
     */
    public function increaseStockByAmountForDestination($amount, $destination_id)
    {
        $warehouses = $this->getWarehousesForDestination($destination_id);

        if (empty($warehouses)) {
            return $this->increaseStockByAmount($amount);
        }

        return $this->increaseStock($amount, $warehouses);
    }

    /**
     * Increases product by provided amount in warehouses that are ship to the selected store, starting with the one
     * that was selected as the pickup point.
     *
     * @param int $amount   Product amount
     * @param int $store_id Pickup store identifier
     *
     * @return $this
     */
    public function increaseStockByAmountForStore($amount, $store_id)
    {
        $pickup_store = $this->getWarehousesById($store_id);
        $pickup_store = reset($pickup_store);

        $alternative_warehouses = $this->getWarehousesThatShipToStore($pickup_store);
        $warehouses = array_merge([$pickup_store], $alternative_warehouses);

        return $this->increaseStock($amount, $warehouses);
    }

    /**
     * Increasing product amount at specified warehouses on specified values.
     *
     * @param int             $amount             Product amount on which stock amount should be increased.
     * @param array<int, int> $changed_warehouses Specified warehouses with specified product amounts which were taken.
     * @param int             $destination_id     Destination identifier.
     *
     * @return array<int, int>
     */
    public function increaseStockForSpecifiedWarehouses($amount, array $changed_warehouses, $destination_id)
    {
        $delta = $amount;
        $new_changed_warehouses = [];
        foreach ($changed_warehouses as $warehouse_id => $changed_amount) {
            $amount_on_warehouse = $this->getAmountForWarehouse($warehouse_id);
            if ($amount_on_warehouse === false) {
                continue;
            }
            if (!$delta) {
                break;
            }
            if ($delta >= $changed_amount) {
                $new_amount = $amount_on_warehouse + $changed_amount;
                $delta -= $changed_amount;
            } else {
                $new_amount = $amount_on_warehouse + $delta;
                $delta = 0;
            }
            $new_changed_warehouses[$warehouse_id] = isset($new_changed_warehouses[$warehouse_id])
                ? $new_changed_warehouses[$warehouse_id] + $new_amount - $amount_on_warehouse
                : $new_amount - $amount_on_warehouse;
            $this->setAmountForWarehouse($warehouse_id, $new_amount);
        }

        if ($delta > 0) {
            $warehouses = $this->sortByDestinationPosition($this->getActiveWarehouses(), $destination_id);
            $in_stock_warehouses = array_filter($warehouses, static function ($warehouse) {
                /** @var \Tygh\Addons\Warehouses\ProductWarehouse $warehouse */
                return $warehouse->getAmount() > 0;
            });

            if ($in_stock_warehouses) {
                $first_warehouse = reset($in_stock_warehouses);
            } else {
                $first_warehouse = reset($warehouses);
            }
            $current_amount = $first_warehouse->getAmount();
            $new_amount = $current_amount + $delta;
            $first_warehouse_id = $first_warehouse->getWarehouseId();
            $new_changed_warehouses[$first_warehouse_id] = isset($new_changed_warehouses[$first_warehouse_id])
                ? $new_changed_warehouses[$first_warehouse_id] + $new_amount - $current_amount
                : $new_amount - $current_amount;
            $first_warehouse->setAmount($new_amount);
        }

        return $new_changed_warehouses;
    }

    /**
     * Reduces stock by provided amount
     *
     * @param int $amount Product amount
     *
     * @return array<int, int>
     */
    public function reduceStockByAmount($amount)
    {
        return $this->reduceStock($amount, $this->product_warehouses);
    }

    /**
     * Reduces stock by provided amount from warehouses that available for provided destination
     *
     * @param int $amount         Product amount
     * @param int $destination_id Destination identifier
     *
     * @return array<int, int>
     */
    public function reduceStockByAmountForDestination($amount, $destination_id)
    {
        $warehouses = $this->getWarehousesForDestination($destination_id);

        if (empty($warehouses)) {
            return $this->reduceStockByAmount($amount);
        }

        return $this->reduceStock($amount, $warehouses);
    }

    /**
     * Reduces stock by provided amount in warehouses that are ship to the selected store, starting with the one
     * that was selected as the pickup point.
     *
     * @param int $amount   Product amount
     * @param int $store_id Pickup store identifier
     *
     * @return array<int, int>
     */
    public function reduceStockByAmountForStore($amount, $store_id)
    {
        $pickup_store = $this->getWarehousesById($store_id);
        $pickup_store = reset($pickup_store);

        $alternative_warehouses = $this->getWarehousesThatShipToStore($pickup_store);
        $warehouses = array_merge([$pickup_store], $alternative_warehouses);

        return $this->reduceStock($amount, $warehouses);
    }

    /**
     * Reduce product amount at specified warehouses on specified values.
     *
     * @param int             $amount    Product amount on which stock amount should be reduced.
     * @param array<int, int> $store_ids Specified warehouses.
     *
     * @return int[]
     */
    public function reduceStockByAmountForWarehouses($amount, array $store_ids)
    {
        $warehouses = $this->getWarehousesById($store_ids);

        return $this->reduceStock($amount, $warehouses);
    }

    /**
     * Reducing stock of product with noting of previously used warehouses.
     *
     * @param int             $amount                    Product amount needed to be excluded from product stock.
     * @param array<int, int> $changed_warehouse_amounts Previously used warehouses and product amounts which were taken from warehouses.
     * @param int             $destination_id            Destination identifier
     *
     * @return array<int, int>
     */
    public function reduceStockBySpecificAmountForWarehouses($amount, array $changed_warehouse_amounts, $destination_id)
    {
        $amount_delta = $amount;
        $result_warehouses = [];
        foreach ($changed_warehouse_amounts as $warehouse_id => $warehouse_amount) {
            $warehouse_data = $this->getWarehousesById($warehouse_id);
            $warehouse_data = reset($warehouse_data);
            if (!$warehouse_data->isActive() || $this->getAmountForWarehouse($warehouse_id) === false) {
                break;
            }
            if (!$amount_delta) {
                break;
            }
            $current_warehouse_amount = (int) $this->getAmountForWarehouse($warehouse_id);
            $taken_amount = min($amount_delta, $warehouse_amount);
            if ($current_warehouse_amount < $taken_amount) {
                $amount_delta -= $taken_amount;
                $current_warehouse_amount = 0;
                $result_warehouses[$warehouse_id] = $current_warehouse_amount;
            } else {
                $amount_delta -= $taken_amount;
                $current_warehouse_amount -= $taken_amount;
                $result_warehouses[$warehouse_id] = $taken_amount;
            }
            $this->setAmountForWarehouse($warehouse_id, $current_warehouse_amount);
        }

        if ($amount_delta > 0) {
            $warehouses = $this->sortByDestinationPosition($this->getActiveWarehouses(), $destination_id);
            $in_stock_warehouses = array_filter($warehouses, static function ($warehouse) {
                /** @var \Tygh\Addons\Warehouses\ProductWarehouse $warehouse */
                return $warehouse->getAmount() > 0;
            });

            if ($in_stock_warehouses) {
                $additional_warehouses = $this->reduceStock($amount_delta, $in_stock_warehouses);
                foreach ($additional_warehouses as $additional_warehouse_id => $additional_warehouse_amount) {
                    if (isset($result_warehouses[$additional_warehouse_id])) {
                        $result_warehouses[$additional_warehouse_id] += $additional_warehouse_amount;
                    } else {
                        $result_warehouses[$additional_warehouse_id] = $additional_warehouse_amount;
                    }
                }
                return $result_warehouses;
            }

            $first_warehouse = reset($warehouses);
            $new_amount = $first_warehouse->getAmount() - $amount_delta;
            $first_warehouse->setAmount($new_amount);
            $warehouse_id = $first_warehouse->getWarehouseId();
            $result_warehouses[$warehouse_id] = isset($result_warehouses[$warehouse_id])
                ? $result_warehouses[$warehouse_id] + $amount_delta
                : $amount_delta;
        }

        return $result_warehouses;
    }

    /**
     * Reduces product stock by provided amount and returns list of warehouse ids on which stock was reduced and how much reduced it was.
     *
     * @param int                                        $amount     Product amount
     * @param \Tygh\Addons\Warehouses\ProductWarehouse[] $warehouses Product warehouses data
     *
     * @return array<int, int>
     */
    protected function reduceStock($amount, $warehouses)
    {
        $amount_delta = $amount;
        $changed_warehouse_ids = [];
        foreach ($warehouses as $warehouse) {
            if (!$amount_delta) {
                break;
            }
            $warehouse_id = $warehouse->getWarehouseId();
            $warehouse_amount = $warehouse->getAmount();

            if ($warehouse_amount <= 0) {
                $new_warehouse_amount = $warehouse_amount;
            } elseif ($warehouse_amount >= $amount_delta) {
                $changed_warehouse_ids[$warehouse_id] = isset($changed_warehouse_ids[$warehouse_id])
                    ? $changed_warehouse_ids[$warehouse_id] + $amount_delta
                    : $amount_delta;
                $new_warehouse_amount = $warehouse_amount - $amount_delta;
                $amount_delta = 0;
            } else {
                $new_warehouse_amount = 0;
                $amount_delta -= $warehouse_amount;
                $changed_warehouse_ids[$warehouse_id] = isset($changed_warehouse_ids[$warehouse_id])
                    ? $changed_warehouse_ids[$warehouse_id] + $warehouse_amount
                    : $warehouse_amount;
            }

            $warehouse->setAmount($new_warehouse_amount);
        }

        if ($amount_delta > 0) {
            $in_stock_warehouses = array_filter($warehouses, function($warehouse) {
                /** @var \Tygh\Addons\Warehouses\ProductWarehouse $warehouse */
                return $warehouse->getAmount() > 0;
            });

            if ($in_stock_warehouses) {
                return $this->reduceStock($amount_delta, $in_stock_warehouses);
            }

            $first_warehouse = reset($warehouses);
            $new_amount = $first_warehouse->getAmount() - $amount_delta;
            $first_warehouse->setAmount($new_amount);
            $warehouse_id = $first_warehouse->getWarehouseId();
            $changed_warehouse_ids[$warehouse_id] = isset($changed_warehouse_ids[$warehouse_id])
                ? $changed_warehouse_ids[$warehouse_id] + $amount_delta
                : $amount_delta;
        }

        return $changed_warehouse_ids;
    }

    /**
     * Reset stock in provided warehouses
     *
     * @param array<int, int> $warehouses Product warehouses identifiers
     *
     * @return $this
     */
    public function resetAmount(array $warehouses)
    {
        $product_warehouses = $this->product_warehouses;
        foreach ($product_warehouses as $w_info) {
            $w_id = $w_info->getWarehouseId();
            if (!in_array($w_id, $warehouses)) {
                continue;
            }
            $w_info->setAmount(0);
        }
        return $this;
    }

    /**
     * Fetches product amount from warehouses
     *
     * @param array<ProductWarehouse> $warehouses Product warehouses data
     *
     * @return false|int
     */
    protected function getProductAmount(array $warehouses)
    {
        if (!$this->hasStockSplitByWarehouses() || empty($warehouses)) {
            return false;
        }

        $amount = 0;
        /** @var \Tygh\Addons\Warehouses\ProductWarehouse $warehouse */
        foreach ($warehouses as $warehouse) {
            $amount += $warehouse->getAmount();
        }

        return $amount;
    }

    /**
     * Filters out warehouses and stores that are not available for provided destination
     *
     * @param int $destination_id Destination identifier
     *
     * @return \Tygh\Addons\Warehouses\ProductWarehouse[]
     */
    public function getWarehousesForDestination($destination_id)
    {
        $warehouses = array_filter($this->getActiveWarehouses(), function ($warehouse) use ($destination_id) {
            /** @var \Tygh\Addons\Warehouses\ProductWarehouse $warehouse */
            return $warehouse->isAvailForPickupInDestination($destination_id)
                || $warehouse->isAvailForShippingInDestination($destination_id);
        });

        $warehouses = $this->sortByDestinationPosition($warehouses, $destination_id);

        return array_values($warehouses);
    }

    /**
     * Gets warehouses that are shown for the specifed destination.
     *
     * @param int $destination_id Destination identifier
     *
     * @return \Tygh\Addons\Warehouses\ProductWarehouse[]
     */
    public function getWarehousesForPickupInDestination($destination_id)
    {
        $warehouses = array_filter($this->getActiveWarehouses(), function ($warehouse) use ($destination_id) {
            /** @var \Tygh\Addons\Warehouses\ProductWarehouse $warehouse */
            return $warehouse->isAvailForPickupInDestination($destination_id);
        });

        $warehouses = $this->sortByDestinationPosition($warehouses, $destination_id);

        return array_values($warehouses);
    }

    /**
     * Gets warehouses that ship to the specifed destination.
     *
     * @param int $destination_id Destination identifier
     *
     * @return \Tygh\Addons\Warehouses\ProductWarehouse[]
     */
    public function getWarehousesForShippingInDestination($destination_id)
    {
        $warehouses = array_filter($this->getActiveWarehouses(), function ($warehouse) use ($destination_id) {
            /** @var \Tygh\Addons\Warehouses\ProductWarehouse $warehouse */
            return $warehouse->isAvailForShippingInDestination($destination_id);
        });

        $warehouses = $this->sortByDestinationPosition($warehouses, $destination_id);

        return array_values($warehouses);
    }

    /**
     * Gets warehouses that can ship a product to the specified store.
     *
     * @param \Tygh\Addons\Warehouses\ProductWarehouse $store
     *
     * @return \Tygh\Addons\Warehouses\ProductWarehouse[]
     */
    public function getWarehousesThatShipToStore(ProductWarehouse $store)
    {
        $warehouses = $this->getWarehousesForShippingInDestination($store->getMainDestinationId());
        $warehouses = array_filter($warehouses, function($warehouse) use ($store) {
            /** @var \Tygh\Addons\Warehouses\ProductWarehouse $warehouse */
            return $store->getWarehouseId() != $warehouse->getWarehouseId()
                && $warehouse->getAmount() > 0;
        });

        return $warehouses;
    }

    /**
     * Filters out warehouses that are not the selected pickup point.
     *
     * @param int[]|int $warehouse_ids Warehouse identifier
     *
     * @return \Tygh\Addons\Warehouses\ProductWarehouse[]
     */
    public function getWarehousesById($warehouse_ids)
    {
        $warehouse_ids = (array) $warehouse_ids;
        $warehouse = array_filter($this->product_warehouses, function ($warehouse) use ($warehouse_ids) {
            /** @var \Tygh\Addons\Warehouses\ProductWarehouse $warehouse */
            return in_array($warehouse->getWarehouseId(), $warehouse_ids);
        });

        return array_values($warehouse);
    }

    /**
     * Gets warehouses
     *
     * @return \Tygh\Addons\Warehouses\ProductWarehouse[]
     */
    public function getWarehouses()
    {
        return $this->product_warehouses;
    }

    /**
     * Initializes product warehouses amounts
     *
     * @param array $warehouses_amounts Product warehouses amount data
     *
     * @return $this
     */
    private function initializeAmounts(array $warehouses_amounts)
    {
        foreach ($warehouses_amounts as $warehouse) {
            $warehouse_data = [
                'amount'                   => $warehouse['amount'],
                'position'                 => $warehouse['position'],
                'product_id'               => $this->getProductId(),
                'store_type'               => $warehouse['store_type'],
                'warehouse_id'             => $warehouse['warehouse_id'],
                'main_destination_id'      => $warehouse['main_destination_id'],
                'pickup_destination_ids'   => $warehouse['pickup_destinations_ids'],
                'shipping_destination_ids' => $warehouse['shipping_destinations_ids'],
                'destinations'             => $this->initializeDestinations($warehouse['destinations']),
                'status'                   => $warehouse['status'],
            ];
            $this->product_warehouses[] = new ProductWarehouse($warehouse_data);
        }

        return $this;
    }

    /**
     * Reorders warehouses by their priority within a rate area.
     *
     * @param \Tygh\Addons\Warehouses\ProductWarehouse[] $warehouses     Rate area warehouses
     * @param int                                        $destination_id Rate area ID
     *
     * @return \Tygh\Addons\Warehouses\ProductWarehouse[]
     */
    protected function sortByDestinationPosition(array $warehouses, $destination_id)
    {
        usort($warehouses, function($warehouse_1, $warehouse_2) use ($destination_id) {
            /** @var \Tygh\Addons\Warehouses\ProductWarehouse $warehouse_1 */
            /** @var \Tygh\Addons\Warehouses\ProductWarehouse $warehouse_2 */
            if ($warehouse_1->getPosition($destination_id) < $warehouse_2->getPosition($destination_id)) {
                return -1;
            }
            if ($warehouse_1->getPosition($destination_id) > $warehouse_2->getPosition($destination_id)) {
                return 1;
            }

            return 0;
        });

        return $warehouses;
    }

    /**
     * Creates a list of destination-specific settings for a warehouse.
     *
     * @param array $destinations
     *
     * @return array
     */
    protected function initializeDestinations(array $destinations)
    {
        $initialized_destinations = [];

        foreach ($destinations as $destination) {
            if (!$destination instanceof Destination) {
                $destination = new Destination($destination);
            }

            $initialized_destinations[$destination->getId()] = $destination;
        }

        return $initialized_destinations;
    }

    /**
     * @return \Tygh\Addons\Warehouses\ProductWarehouse[]
     */
    protected function getActiveWarehouses()
    {
        return array_filter($this->product_warehouses, function(ProductWarehouse $warehouse) {
            return $warehouse->isActive();
        });
    }
}
